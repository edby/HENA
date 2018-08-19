<?php
namespace Home\Controller;

use Common\Model\GoodsRelationModel;

class IndexController extends CommonController {

    //获取商品数据
    public function index(){

       /* redirect('/iview/#/index');
        exit(0);*/
        $model = new GoodsRelationModel;

       /* // 520商品
        $list1 = $model->where(array('acttype'=>1,'status'=>1))->order('id asc')->limit(6)->find(); 
        $this->assign('list1',$list1);*/

        //0元购
        $goodsIds = D('return_cash')->getGoodsIds();
        $list2 = [];
        if(!empty($goodsIds)) {
            $list2 = $model->where(array('acttype'=>2,'id'=>['IN',$goodsIds],'status'=>1, 'show_index' => 1))->relation(true)->order('id desc')->limit(6)->select(); //
        }
        $this->assign('list2',$list2);

        /*//爆品库
        $list3 = $model->where(array('acttype'=>4,'status'=>1))->order('id desc')->select();
        $this->assign('list3',$list3);
*/
        $this->assign('ag', session('_ag1'));

        $this->display();
    }
    public function ajaxlogin(){
        if(IS_GET){
            $mid = session('mid');
            if ($mid == 0){
                $this->error('请扫描推荐人二维码进行注册!',U('Home/Index/index'),2);
                exit();
            }
            $this->display('/User/login');
        }else{
            $mid = session('mid');
            if ($mid == 0){
                $this->error('请扫描推荐人二维码进行注册!',U('Home/Index/index'),2);
                exit();
            }
            $tel = I('post.tel');
            $telcode = I('post.telcode');
            //获取当前session中具体的信息
            $data = session('telcode');
            //判断验证码是否过期
            if($data['time']+$data['limit'] < time()){
                $this->ajaxReturn(array('status'=>0,'msg'=>'手机验证码无效'));
            }
            if($data['tel'] != $tel){
                $this->ajaxReturn(array('status'=>0,'msg'=>'手机号不匹配'));
            }
            if($data['code'] != $telcode){
                $this->ajaxReturn(array('status'=>0,'msg'=>'手机验证码错误'));
            }

            $data = array(
                'gender' => 0,
                'tel' => $tel,
                'level' => 0,
                'inv_id' => session('mid'),
                'regtime' => time(),
                'lasttime' => time(),
            );
            $info = D('User')->where('tel='.$tel)->find();
            if($info){
                D('User')->where('tel='.$tel)->setField('lasttime',time());
                session('userId',$info['id']);
                $this->ajaxReturn(array('status' => 1,'msg' => '登陆成功'));
            }else{
                $res = D('User')->reg($data);
                if(!$res){
                    $this->ajaxReturn(array('status' => 0,'msg' => '操作失败'));
                }
                session('userId',$res);
                $this->ajaxReturn(array('status' => 1,'msg' => '登陆成功'));
            }
        }
    }

    //生成推广二维码
    public function qrcode()
    {
        $userId = getUserId();

        // 验证用户是否有权限生成推广码
        $leve = D('user')->where(['id' => $userId])->getField('level');

        if($leve <= 1){
            $this->ajaxReturn(['code' => 3000, 'msg' => '没有推广权限，请先购买520!']);
        }

        $qr_path = './Public/Home/images/QrCode/';
        if(!is_file($qr_path)) {
            mkdir($qr_path, 0775);
        }

        $path = './Public/Home/images/QrCode/QrCode_';

        // 判断是否存在二维码
        if(file_exists($path.$userId.'.png')){
            $this->ajaxReturn(['code' => 2000, 'msg' => '二维码生成', 'url' => $path.$userId.'.png']);
        }

        $model = D('User');
        $res = $model->getUserInfoById($userId);
        if(!$res){
            $this->error('不好意思，您不是本平台的会员用户，没有权限邀请他人!');
        }
        $url= 'http://'.$_SERVER['HTTP_HOST'].U('Home/User/index',array('mid' => $res['id']));
        $level=3;
        $size=8;
        Vendor('phpqrcode.phpqrcode');
        $errorCorrectionLevel =intval($level) ;//容错级别
        $matrixPointSize = intval($size);//生成图片大小
        //生成二维码图片
        $object = new \QRcode();

        $object->png($url, $path.$userId.'.png', $errorCorrectionLevel, $matrixPointSize, true);

        // 生成缩略图
        $percent = 0.6;
        list($width, $height) = getimagesize($path.$userId.'.png'); //获取原图尺寸 
        $newwidth = $width * $percent; 
        $newheight = $height * $percent; 
        $src_im = imagecreatefrompng($path.$userId.'.png'); 
        $dst_im = imagecreatetruecolor($newwidth, $newheight); 
        
        imagecopyresized($dst_im, $src_im, 0, 0, 0, 0, $newwidth, $newheight, $width, $height); 
        imagepng($dst_im, './Public/Home/images/QrCode/QrCode_'.$userId.'_thum.png'); //输出压缩后的图片 
        imagedestroy($dst_im); 
        imagedestroy($src_im);

        $_path = '/Public/Home/images/QrCode/QrCode_';
        $this->ajaxReturn(['code' => 2000, 'msg' => '二维码生成', 'url' => $_path.$userId.'.png']);

    }

    /**
     * 隐藏公告
     */
    public function hideAg()
    {
        return session('_ag1', 1);
    }

    public function tb()
    {

        // 判断浏览器类型
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        //MicroMessenger 是android/iphone版微信所带的
        //Windows Phone 是winphone版微信带的  (这个标识会误伤winphone普通浏览器的访问)
        if(strpos($userAgent, 'MicroMessenger') == false && strpos($userAgent, 'Windows Phone') == false){ // 非微信用户

            header('Location:http://tao.hena360.com'); 
            exit(0);
        }
        $this->display();
    }

    /**
     * 获取普通商品列表
     */
    public function getGeneralList()
    {
        //3是普通商品
        $perpage = I('post.per_page',20);//每页20条
        $newPage = I('post.new_page',1);//要获取第几页数据，默认第1页
        $newPage = $newPage - 1;
        $limit = ($newPage * $perpage) .','.$perpage;
        $model = D('Goods');
        $goodsList = $model->where(['acttype' => 3,'status' => 1])->order('id desc')->limit($limit)->select();
        if($goodsList){
            $this->ajaxReturn(['status' => 1,'new_page' => $newPage + 1,'goods_list' => $goodsList]);
        }else{
            $this->ajaxReturn(['status' => 0,'new_page' => $newPage + 1]);
        }
    }

    /**
     * 获取爆品库商品列表
     */
    public function getExpList()
    {
        //4是爆品库
        $perpage = I('post.per_page',20);//每页20条
        $newPage = I('post.new_page',1);//要获取第几页数据，默认第1页
        $newPage = $newPage - 1;
        $limit = ($newPage * $perpage) .','.$perpage;
        $model = D('Goods');
        $goodsList = $model->where(['acttype' => 4,'status' => 1])->order('id desc')->limit($limit)->select();
        foreach ($goodsList as &$value) {
            $value['goods_logo'] = C('RESOURCES_DOMAIN').$value['goods_logo'];
            $value['bg_img'] = C('RESOURCES_DOMAIN').$value['bg_img'];
        }

        if($goodsList){
            $this->ajaxReturn(['status' => 1,'new_page' => $newPage + 1,'goods_list' => $goodsList]);
        }else{
            $this->ajaxReturn(['status' => 0,'new_page' => $newPage + 1]);
        }
    }

    /**
     * 获取福利库商品列表
     */
    public function getSocialList()
    {
        //2是福利库（0元购）
        $perpage = I('post.per_page',20);//每页20条
        $newPage = I('post.new_page',1);//要获取第几页数据，默认第1页
        $newPage = $newPage - 1;
        $limit = ($newPage * $perpage) .','.$perpage;
        $model = D('Goods');
        $goodsList = $model
            ->alias('g')
            ->field('g.*,r.start_time,r.end_time')
            ->join('LEFT JOIN `hn_return_cash` AS r ON g.id = r.goods_id')
            ->where(['g.acttype' => 2,'g.status' => 1])->order('g.id desc')->limit($limit)->select();
        if($goodsList){
            $curGoods = $oldGoods = [];
            $curTime = time();
            foreach ($goodsList as $k => $goods){
                $goods['goods_logo'] = C('RESOURCES_DOMAIN').$goods['goods_logo'];
                $goods['bg_img'] = C('RESOURCES_DOMAIN').$goods['bg_img'];
                if ( $curTime <= $goods['pre_sale_end']){
                    $goods['pre_sale_start'] = date('m.d',$goods['pre_sale_start']);
                    $goods['pre_sale_end'] = date('m.d',$goods['pre_sale_end']);
                    $curGoods[] = $goods;
                }else{
                    $goods['pre_sale_start'] = date('m.d',$goods['pre_sale_start']);
                    $goods['pre_sale_end'] = date('m.d',$goods['pre_sale_end']);
                    $oldGoods[] = $goods;
                }
            }
            $this->ajaxReturn(['status' => 1,'new_page' => $newPage + 1,'cur_goods_list' => $curGoods,'old_goods_list' => $oldGoods]);
        }else{
            $this->ajaxReturn(['status' => 0,'new_page' => $newPage + 1]);
        }
    }

    /**
     * 福利库列表页
     */
    public function welfare()
    {
        $this->display();
    }

    /**
     * 爆品库列表页
     */
    public function detonating()
    {
        $this->display();
    }
    public function Share()
       {
           $this->display();
       }
    public function giftbag()
          {
              $this->display();
          }
    public function alipay($id, $uid, $_sign, $_time)
    {

        if(!validSign($_sign, $id.$uid, 'alipay_order', $_time, 30*60)){
            $this->error('支付超时,请返回重新支付!');
        }

        $order = D('order')->where(['id' => $id, 'user_id' => $uid])->find();
        if($order['status'] == 2){
            $this->redirect('/user/orderDetail', ['orderid' => $id, 'mid' => $uid]);
        }

        $str = "?id={$id}&uid={$uid}&_sign={$_sign}&_time={$_time}";
        $this->assign('str', $str);
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
        if(!isLogin()){
             $this->ajaxReturn(['code' => 2000, 'status' => 1000,'signPackage' => $signPackage,'mid' => $mid,'url' => $url]);   
        }
        $this->ajaxReturn(['code' => 2000, 'status' => 1,'signPackage' => $signPackage,'mid' => $mid,'url' => $url]);
    }
}
