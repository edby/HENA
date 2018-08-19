<?php
namespace Home\Controller;
class UserController extends LoginController
{
    /**
 * 获取用户基本信息
 */
    public function index()
    {
        $userId = getUserId();
        $userInfo = D('user')->getOne(['id' => $userId]);

        $userInfo['levelname'] = D('level')->getOneField(['id' => $userInfo['level']], 'levelname');
        $userInfo['nickname'] = emoji_decode($userInfo['nickname']);
        $this->assign('info' ,$userInfo);
        $this->display();
    }

    //个人信息
    public function account(){
        $userId = getUserId();
        $userInfo = D('user')->getOne(['id' => $userId], 'nickname, avatar, tel, inv_id');
        $userInfo['nickname'] = emoji_decode($userInfo['nickname']);
        $this->assign('info', $userInfo);

        // 推荐人
        $nickname = '';
        if($userInfo['inv_id'] != 0){
            $nickname = D('user')->where(['id' => $userInfo['inv_id']])->getField('nickname');
        }
        $nickname = empty($nickname) ? '暂无推荐人' : $nickname;
        $this->assign('nickname', $nickname);
        $this->display();
    }

    //我的粉丝
    public function fans(){
        $userId = getUserId();
        $model = D('User');
        $info = $model->getUserInfo($userId);
        /*if($info['level'] == 7){
            header("Location:liandong");
            exit;
        }*/
        $data = $model->getList($info['id']);
        $this->assign('data',$data);
        $this->display();
    }

    public function invite(){
        $this->display();
    }

    public function liandong(){
        return ;
        $model = D('User');
        $info = $model->getUserInfo();
        $flag = 0;
        if($info['level'] == 2){
            $flag = 1;
        }
        $this->assign('flag',$flag);
        $this->display();
    }

    public function lianchuang(){
        $this->display();
    }

    public function information(){
        if(IS_GET){
            $this->display();
        }else{
            $tel = I('post.tel');
            $realname = I('post.name');
            $info = D('User')->where('tel='.$tel)->find();
            if(!$info){
                $this->ajaxReturn(array('status'=>0,'msg'=>'用户不存在!请重新填写'));
            }elseif($info['level'] != 2){
                $this->ajaxReturn(array('status'=>0,'msg'=>'该手机号不具备推荐资格'));
            }
            $outTradeNo = date('YmdHis') . rand(100, 999); //你自己的商品订单号
            D('User')->where(array('username'=>session('username')))->save(array('realname'=>$realname,'renling'=>$info['id'],'order_no'=>$outTradeNo));
            $this->ajaxReturn(array('status'=>1,'realname'=>$info['realname']?:$info['nickname'],'avatar'=>$info['avatar'],'order_no'=>$outTradeNo));
        }
    }

    public function payback(){
        //$this->display();
    }

    public function produce(){
        $goodsId = I('post.goods_id');

        $userInfo = D('User')->getUserInfo();
        if(! $userInfo){
            $this->ajaxReturn(array('status'=>0,'msg'=>'用户未在微信端登录或登录已失效!'));
        }
        //检验用户身份，是否可以购买520礼包
        if($userInfo['level'] != 7){
            $this->ajaxReturn(array('status'=>0,'msg'=>'您已是平台创客，无需购买520礼包!'));
        }
        //校验是否已存在已支付的520订单
        $orderModel = M('Order_five');
        $checkFlag = $orderModel->field('1')->where(array('user_id' => $userInfo['id'],'is_pay' => 1))->find();
        if ($checkFlag){
            $this->ajaxReturn(array('status'=>0,'msg'=>'您已购买过520礼包，请与平台客服联系!'));
        }
        //生成520订单
        $data = array();
        $data['order_no'] = '520'.date('YmdHis',time()).mt_rand(100,900);//商户订单号
        $data['goods_id'] = $goodsId;
        $data['user_id'] = $userInfo['id'];
        $data['price'] = 520;
        $data['is_pay'] = 0;
        $data['created_time'] = time();

        $res = $orderModel->add($data);
        if($res){
            $this->ajaxReturn(array('status'=>1,'msg'=>$data['order_no']));
        }
        $this->ajaxReturn(array('status'=>0,'msg'=>'服务器繁忙，请稍后重试!'));
    }

    /**
     * 我的钱包
     */
    public function wallet()
    {
        
        $userId = getUserId();
        $balance = D('user')->getOneField(['id' => $userId], 'balance');
        $balance = setCurr($balance / 10);
        $this->assign('balance', $balance);
        $this->display();
    }

    /**
     * 推广奖金明细
     */
    public function bonusDetail()
    {
        $model = D('Bonus_detail');
        $userId = session('userId');
        $bonus = M('User')->where(['id' => $userId])->getField('bonus');

        $detailList = $model->where(['user_id' => $userId, 'b_type' => 2])->order('created_at desc')->limit(100)->select();
       
        $this->assign('bonus',$bonus);
        $this->assign('list',$detailList);

        $this->display();
    }

    /**
     * 推广奖金明细 ajax分页
     */
    public function ajaxBonusDetail(){
        $perpage = 20;//每页20条
        $nowPage = I('post.now_page');
        $newPage = $nowPage + 1;//返回的当前页数
        $limit = ($newPage * $perpage) .','.$perpage;
        $model = D('Bonus_detail');
        $userId = session('userId');
        $detailList = $model->where(['user_id' => $userId,'b_type' => 2])->order('created_at desc')->limit($limit)->select();
        if($detailList){
            foreach ($detailList as $k => $v){
                $detailList[$k]['order_time'] = date('Y-m-d H:i:s',$v['order_time']);
            }
            $this->ajaxReturn(['status' => 1,'new_page' => $newPage,'list' => $detailList]);
        }else{
            $this->ajaxReturn(['status' => 0,'new_page' => $newPage]);
        }
    }

    /**
     * 余额明细
     */
    public function balanceDetail()
    {
        $this->display();
    }
    public function getbalanceDetailList()
    {
        $page = isset($_GET['page']) ? $_GET['page'] : 0;
        $userId = session('userId');
        $model = D('BalanceDetail');
        $pageSize = 10;
        $offsite = ($pageSize*$page);
        $balanceList = $model->getList(['user_id' => $userId],$offsite,$pageSize,'*','id desc');
        foreach ($balanceList as $k => $info){
            $balanceList[$k]['money'] = setCurr($info['money']/10);
            $balanceList[$k]['balance'] = setCurr($info['balance']/10);
        }
        $this->ajaxReturn($balanceList,'json');
    }
    /**
     * 订单列表
     */
    public function orderList($status = 0)
    {
        redirect('/iview/#/user/order/orderlist');
        exit(0);

        $userId = getUserId();
        $where['status'] = ["IN",[1, 2, 3, 4]];
        if(in_array($status, [1, 2, 3, 4])){
            $where['status'] = $status;
        }

        $where['user_id'] = $userId;

        $count = D('order')->where($where)->count();
        $Page = new \Think\Page($count,10);
        $list = D('order')->where($where)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();

        //$list = D('order')->getList($where, 0, 100, '*', 'id desc');
        foreach ($list as $key => &$value) {
            
            // 获取商品信息
            $value['order_goods'] = D('order_goods')->getOne(['order_id' => $value['id']]);
        }
        

        $this->assign('list',$list);
        $this->display();
    }

    /**
     * 订单详情
     */
    public function orderDetail($orderid = 0)
    {       
        if($orderid == 0){
            redirect('/iview/#/user/order/orderlist');
            exit(0);
        }
        redirect('/iview/#/user/order/orderdatails?id='.$orderid);
        exit(0);

        $data = D('order')->getOne(['id' => $orderid, ['user_id' => getUserId()]]);
        if(!$data) { // 订单不存在
            redirect(U('/user/orderList'));
        }

        $data['order_goods'] = D('order_goods')->getOne(['order_id' => $data['id']]);
        $data['order_addr'] = D('order_addr')->getOne(['order_id' => $data['id']]);
        //var_dump($data['order_goods']);die;
        $this->assign('data',$data);
        $this->display();
    }

    /**
     * 删除订单
     */
    public function orderDel()
    {
        $orderId = intval($_POST['order_id']);
        $order = D('order')->getOne(['id' =>$orderId, 'user_id' => getUserId()]);
        if(!$order) {
            $this->ajaxReturn(array('status' => 0,'msg' => '订单不存!'));
        }

        if(!in_array($order['status'], [4])) {
            $this->ajaxReturn(array('status' => 0,'msg' => '当前订单不可删除!'));
        }

        if(D('Order')->updateData(['id' => $orderId], ['status' => 10])) {
            $this->ajaxReturn(array('status' => 1,'msg' => '删除成功!'));
        } else {
            $this->ajaxReturn(array('status' => 0,'msg' => '删除失败!'));
        }
    }
    
    /**
     * 0元购返单列表
     */
    public function backList()
    {
        $userId = session('userId');
        $model = M('Order_goods');
        $list = D('backPurchase')->where(['user_id' => $userId])->select();
        foreach ($list as $key => &$value) {
            $value['money'] = setCurr($value['money']/10);
            $diffTime = $value['back_time'] - time();
            $date = floor($diffTime/86400);
            $value['r_day'] = $date;
            if($value['money'] <= 0 || $value['r_day']< 0){
                $value['r_day'] = 0;
            }
            $orderGoods = $model->where(['order_id'=>$value['order_id'],'acttype'=>2,'delivery_status'=>1])->find();
            $value['goods_name'] = $orderGoods['goods_name'];
            $value['goods_img'] = $orderGoods['goods_img'];

        }
      
        $this->assign('list',$list);
        $this->display();
    }

    /**
     * 返单列表做显示处理
     * 计算剩余返单时间、状态、
     * @param $backList array 返单列表
     */
    public function handleBackList($backList)
    {
        foreach ($backList as $k => $backInfo){
            $backTime = $backInfo['receiving_at'] + $backInfo['day'] * 24 * 3600 - time();//距离返单开始的时间
            if ($backTime <= 0){
                $backTime = 0;//距离返单开始的时间  天数
            }else{
                $backTime = ceil($backTime / (3600 * 24));//距离返单开始的时间  天数
            }
            $backList[$k]['to_back_begin'] = $backTime;
            $backList[$k]['back_total'] = $backInfo['number'] * $backInfo['price'];
            $status = ($backInfo['back_status'] == 2) ? 1 : 0;//返单状态 1 已完成  0 未完成
            $backList[$k]['back_status'] = $status;
        }
        return $backList;
    }


    //推广页面
    public function campcode()
    {
        $userId = session('userId');
        //这里需要进行解码操作
        $userInfo = D('User')->getUserInfoById($userId);
        //判断用户等级，是否符合生成二维码的要求(创客)
        $flag = 0;
        if($userInfo['level'] != 1){
            $flag = 1;
        }
        $this->assign('flag',$flag);
        $this->assign('user_id',$userId);
        $this->display('/User/inviation');
    }

    /**
     * 获取用户ID，为地址栏添加mid提供的方法
     * @return int userID
     */
    public function getUserId()
    {
        $mid = getUserId();
        $userLevel = M('User')->where(['id' => $mid])->getField('level');
        if ($userLevel == 1){
            $mid = 0;
        }
        $this->ajaxReturn(['mid' => $mid,'status' => 1]);
    }

    /**
     * 获取用户银行卡信息
     */
    public function getAccount()
    {

        if(IS_GET) {
            $uid = session('userId');
            // 获取用户奖励金余额
            $bonus = D('user')->getOneField(['id' => $uid], 'bonus');
            $userBank = D('BankInfo')->getAccount($uid);
            $data = ['code'=>2000,'msg' => '','user_bank'=>'', 'bonus' => $bonus];
            if(!empty($userBank)) {
                $bankName = D('bank_name')->getOne(['id' => $userBank['bank_id']]);
                $bankNum = substr($userBank['bank_num'],-9,9);
                $bankNum = substr_replace($bankNum, '*****', 0, 5);
                $data['user_bank'] = $userBank;
                $data['user_bank']['bank_num'] = $bankNum;
                $data['user_bank']['bank_real_num'] = $userBank['bank_num'];
                $data['user_bank']['bank_name'] = $bankName['bank_name'];
            }

            $this->ajaxReturn($data, 'json');
        }
        $this->ajaxReturn(['code' => 3000,'msg'=>'非法请求', 'bonus' => $bonus], 'json');
    }

    /**
     * 获取银行卡列表
     */
    public function getAccountList()
    {
        if(IS_GET) {
            $list = D('bank_name')->getAll([]);
            $this->ajaxReturn($list, 'json');
        }
        $this->ajaxReturn(['code'=>3000,'msg'=>'非法请求'], 'json');

    }
    /**
     * 添加银行卡
     */
    public function addBank()
    {
        if(IS_POST) {
            // 用户银行卡信息
            $uid = session('userId');
            $model = D('BankInfo');
            $userBank = $model->getAccount($uid);

            $data['bank_num'] = trim($_POST['bank_num']);
            $data['bank_username'] = $_POST['bank_username'];
            $data['bank_id'] = $_POST['bank_id'];
            $data['user_id'] = $uid;


            if(!$model->create($data)){
                $this->ajaxReturn(['code'=>3000,'msg'=>$model->getError()]);
            }
            if(empty($userBank)) {
                $result = $model->data($data)->add();
            }else{
                $data['id'] = $userBank['id'];
                $result = $model->save($data);
            }
            if($result === false) {
                $this->ajaxReturn(['code'=>3000,'msg'=>'绑定失败']);
            }
            $this->ajaxReturn(['code'=>2000,'msg'=>'绑定成功']);
        }
        $this->display();
    }

    /**
     * 提现
     */
    public function withdraw()
    {
       

        if (IS_POST) {
            // 用户银行卡信息
            //$this->ajaxReturn(['code' => 3000, 'msg' => '即将开放，敬请期待'], 'json');
            
            $uid = session('userId');
            // 验证是否绑定手机号
            $tel = D('user')->where(['id' => $uid])->getField('tel');
            if($tel ==  ''){
                $this->ajaxReturn(['code' => 3003, 'msg' => '请绑定手机号后提现!'], 'json');
            }
            // 验证用户权限 提现配置成 withdraw
            $powerId = M('power')->where(['route' => 'withdraw'])->getField('id');
            if($powerId){

                $relPowerId = M('RelPower')->where("user_id={$uid} and find_in_set({$powerId},power_id)")->getField('id');
                if($relPowerId){
                    $this->ajaxReturn(['code' => 3003, 'msg' => '没有操作权限!'], 'json');
                }
            }

            $userBank = D('BankInfo')->getOne(['user_id' => $uid]);
            if (empty($userBank)) {
                $this->ajaxReturn(['code' => 3000, 'msg' => '请绑定银行卡'], 'json');
            }
            $money = floatval($_POST['money']);
            if ($money < 100) {
                $this->ajaxReturn(['code' => 3000, 'msg' => '提现金额小于100,无法提现'], 'json');
            }
            if($money%100 != 0) {
                $this->ajaxReturn(['code' => 3000, 'msg' => '提现金额必须为100的整数倍'], 'json');
            }
            $left = strtotime('9:0:0');
            $right = strtotime('17:0:0');
            $now = time();
            $day = date("w");
            if($day == '6' || $day == '0') {
                $this->ajaxReturn(['code' => 3000, 'msg' => '请在工作日内（周一至周五）进行提现申请'], 'json');
            }

            if($left > $now || $right < $now) {
                $this->ajaxReturn(['code' => 3000, 'msg' => '每日提现时间为早上9点--下午17点'], 'json');
            }
            $service_charge = setCurr($money * 0.03);

            $totalMoney = $money - $service_charge;
            $userInfo = D('user')->getOne(['id' => $uid]);
            if ($money > $userInfo['bonus']) {
                $this->ajaxReturn(['code' => 3000, 'msg' => '提现奖金大于您的奖金余额,无法提现'], 'json');
            }
            $time = time();
            $withdraw_sn = orderSn();
            $data['bank_info_id'] = $userBank['id'];
            $data['withdraw_sn'] = $withdraw_sn;
            $data['user_id'] = $uid;
            $data['withdraw_money'] = $totalMoney;
            $data['service_charge'] = $service_charge;
            $data['status'] = 0;
            $data['bank_num'] = $userBank['bank_num'];
            $data['bank_username'] = $userBank['bank_username'];
            $data['bank_id'] = $userBank['bank_id'];
            $data['created_at'] = $data['updated_at'] = $time;

            $bonusDetail = [
                'user_id' => $uid,
                'b_type' => 4,
                'type' => 1,
                'money' => $userInfo['bonus'] - $money,
                'bonus' => $money,
                'title' => '提现奖金',
                'detail' => '用户奖金提现,' . '提现金额：' . $money . ',手续费：' . $service_charge . ',共扣除：' . $money.',实际到款金额：'. $totalMoney,
                'order_sn' => $withdraw_sn,
                'order_time' => $time,
                'source_type' => 0,
                'created_at' => $time,
            ];

            $log = [
                'description' => '用户奖金提现,' . '提现金额：' . $money . ',手续费：' . $service_charge . ',共扣除：' . $money.',实际到款金额：'. $totalMoney,
                'created_at' => $time,
            ];


            M()->startTrans();
            $result = $withdraw_id = M('withdraw_bonus')->data($data)->add();

            $result = $result && M('user')->where(['id' => $uid])->save(['bonus' => $userInfo['bonus'] - $money]);

            $result = $result && M('bonus_detail')->data($bonusDetail)->add();
            $log['withdraw_bonus_id'] = $withdraw_id;
            $result = $result && M('withdraw_bonus_log')->data($log)->add();
            if ($result) {
                M()->commit();
                $this->ajaxReturn(['code' => 2000, 'msg' => '申请提现成功'], 'json');

            } else {
                M()->rollback();
                $this->ajaxReturn(['code' => 3000, 'msg' => '申请提现失败'], 'json');
            }

        }
        $this->display();
    }
    
    public function getShareInfo(){
        $url = I('post.url');//分享地址
        //分享地址重置
        $mid = getUserId();
        $userLevel = M('User')->where(['id' => $mid])->getField('level');
        if ($userLevel == 1){
            $mid = 0;
        }
        $url = preg_replace('/\&amp;mid/','&mid',$url);
        $url = preg_replace('/\&amp;from/','&from',$url);
        /*$url = preg_replace('/\&mid=\d+/','&mid='.$mid,$url);
        $url = preg_replace('/\&amp;mid=\d+/','&mid='.$mid,$url);*/
        /*$url = preg_replace('/\&from=groupmessage/','',$url);
        $url = preg_replace('/\&amp;from=groupmessage/','',$url);*/
        //分享地址重置end
        $wechat = C('WECHAT');
        $appid = $wechat['appid'];
        $secret = $wechat['appKey'];
        Vendor("Weixin.jssdk");
        /*分享*/
        $jssdk = new \JSSDK($appid, $secret);
        $signPackage = $jssdk->getSignPackageNew($url);
        $this->ajaxReturn(['status' => 1,'signPackage' => $signPackage,'mid' => $mid,'url' => $url]);
    }

    /**
     * 奖金提现表
     */
    public function withdraw_bonus(){

        $withdrawBonus = M('withdraw_bonus'); 

        $condition['user_id'] = getUserId();
        $count = $withdrawBonus->where($condition)->count();
        $Page = new \Think\Page($count, 50);
        $show = $Page->show();
       
        $list = $withdrawBonus->where($condition)->order('created_at desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        
        $this->assign('list',$list);
        $this->assign('page',$show);
        $this->display(); 
    }

    public function userQrCode($skin = '_pic503')
    {
        $userId = getUserId();

        // 验证用户是否有权限生成推广码
        $leve = D('user')->where(['id' => $userId])->getField('level');
        if($leve <= 1){
            $this->ajaxReturn(['code' => 3000, 'msg' => '没有推广权限，请先购买520!']);
        }

        // 生成海报图片名
        $imgName = 'QrCode_'.$userId.'_'.$skin.'.png';

        // 图片路径
        $path = './Public/Home/images/QrCode/';

        // 文件
        $file = $path.$imgName;

        if(file_exists($file)){
            $this->ajaxReturn(['code' => 2000, 'msg' => '二维码海报生成', 'url' => $file]);
        }

        // 背景skin
        $skin = 'http://img.hena360.cn/'.$skin.'.png';  
        /*if(!file_exists($skin)){
            $skin = str_replace('.png','.jpg',$skin);
            if (!file_exists($skin)){
                $this->ajaxReturn(['code' => 3000, 'msg' => '海报不存在', 'skin' => $skin]);
            }
        }*/

        // 二维码 
        $QrCodePath = $path.'QrCode_'.$userId.'.png';
        if(!file_exists($QrCodePath)){
            $this->ajaxReturn(['code' => 3000, 'msg' => '二维码生成失败!']);
        }

        $QrCode = $path.'QrCode_'.$userId.'_thum.png';

        $getimage = getimagesize($skin);
        if (!$getimage){
            $getimage = getimagesize(str_replace('.png','.jpg',$skin));
        }
        if(strpos($getimage['mime'], 'jpeg')) {
            $obj_skin = imagecreatefromjpeg(str_replace('.png','.jpg',$skin));
            if (! $obj_skin){
                $obj_skin = imagecreatefromjpeg($skin);
            }
            $x = 230;
            $y = 610;
        } else if (strpos($getimage['mime'], 'png')){
            $obj_skin = imagecreatefrompng($skin);
            if (! $obj_skin){
                $obj_skin = imagecreatefrompng(str_replace('.png','.jpg',$skin));
            }
            $x = 230;
            $y = 610;
        }
        $obj_QrCode = imagecreatefrompng($QrCode);  
        
        // 合成图片  
        imagecopymerge($obj_skin, $obj_QrCode, $x, $y, 0, 0, imagesx($obj_QrCode), imagesy($obj_QrCode), 100);  

        // 输出合成图片  
        if(imagepng($obj_skin, $file)){
            $this->ajaxReturn(['code' => 2000, 'msg' => '二维码生成!', 'url' => $file]);
        } else {
            $this->ajaxReturn(['code' => 3000, 'msg' => '二维码生成失败，请刷新重试']);
        }
    }

    public function pay()
    {
        $this->display();
    }
    public function withdraw_1()
    {
        $this->display();
    }
    public function addbank_1()
    {
        $this->display();
    }
     public function withdraw_bonus_1()
        {
            $this->display();
        }
     public function help(){

        $this->display();
     }
     public function help_cont(){
        $this->display();
     }
     public function Collection(){
        $this->display();
     }

    public function integral()
    {
       $this->display('integral1');
    }
     public function integral1()
    {
       $this->display();
    }
    public function integralDetalis()
    {
       $this->display();
    }
    public function IntegralMall()
    {
       $this->display();
    }
     public function time()
    {
       $this->display();
    }
     public function timeList()
    {
       $this->display();
    }

    public function set_inv()
    {
         $this->display();
    }
  public function setBinding()
    {
         $this->display();
    }
}