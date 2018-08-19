<?php
namespace Home\Controller;
use Think\Controller;
use Common\Model\GoodsRelationModel;
use Common\Model\GoodsSpecModel;
use Home\Model\OrderGoodsModel;

class GoodsController extends LoginController {


    //商品情页
    public function details() {
        $id = intval($_GET['id']);
        $userId = session('userId');

        redirect('/html/view/details/details.html?id='.$id.'&mid='.$userId);
        exit(0);
        $userInfo = D('User')->getUserInfo($userId);
        $id = intval($_GET['id']);
        $model = new GoodsRelationModel;
        $data = $model->where(['id'=>$id,'status'=>1])->relation(true)->find();
        if(!$data){
            $this->error('该商品已下架!','/');
//            redirect('/',1,'该商品已下架!');
        }

        // 获取用户等级
        $level = M('user')->where(['id' => $userId])->getField('level');
        // 爆品库价格
        $goods_price = M('ExpGoods')->where("goods_id={$id} and find_in_set({$level},level)")->getField('price');

        $imgArray = explode(',', $data["goods_detail_img"]);

        foreach ($data['exp_config'] as $key => $value) {
            if(in_array($userInfo['level'],explode(',',$value['res_level']))) {
                $data['exp_config'] = $value;
            }
        }
//        dump($data);die;
        //获取推荐商品
        $goodsType = M('GoodsType')->field('goods_id')->where(['type' => 1])->select();
        if(!empty($goodsType)) {
            foreach ($goodsType as $key => $val) {
                if($val['goods_id'] == $id) {
                    continue;
                }
                $ids[] = $val['goods_id'];
            }
            $recommendGoods = M('goods')->where(['id'=>['IN',$ids],'status'=>1])->select();
        }else{
            $recommendGoods = [];
        }

        // 0元购
        if($data['acttype'] == 2){
            // 获取返数据
            $returnPrice = M('ReturnCash')->where(['goods_id' => $id])->getField('return_price');
            if($returnPrice){
                $returnPrice = setCurr($returnPrice);
            } else {
                $returnPrice = setCurr(0);
            }
        }
        //盟豆
        if(!empty($goods_price)){
            $returnPoints = floor($data['points_times']*$goods_price);
        }else{
            $returnPoints = floor($data['points_times']*$data['goods_price']);
        }
        $this->assign('goods_price',$goods_price);
        $this->assign('user_info',$userInfo);
        $this->assign('goods_attribute', $recommendGoods);
        $this->assign('detailimgs', $imgArray);
        $this->assign('imgs', $data['goods_logo']);
        $this->assign('data', $data);
        $this->assign('returnPrice', $returnPrice);
        $this->assign('returnPoints', $returnPoints);
        $this->display();
    }

    /**
     * 获取商品库存及价格
     */
    public function getGoodsInfo()
    {
        $goods_id = $_GET['id'];
        $goodsModel = D('goods');
        $data = $goodsModel->getOne(['id'=>$goods_id],'goods_num,goods_price');
        $this->ajaxReturn($data,'json');
    }
    /**
     * 获取商品规格列表
     */
    public function getGoodsSpec()
    {
        $goods_id = $_GET['id'];
        $model = new GoodsSpecModel;
        $this->ajaxReturn($model->getFormatGoodsSpec($goods_id),'json');
    }

    /**
     * 获取商品规格详情 计算价格 判断库存
     */
    public function getGoodsPrice()
    {

        $goods_id = $_POST['id'];
        $num = $_POST['num'];
        unset($_POST['id']);
        unset($_POST['num']);
        $goods_spec_id = $_POST;

        $model = new GoodsSpecModel;
        // 检测库存
        $goodsNum = $model->checkGoodsNum($goods_spec_id,$goods_id,$num);
        if($goodsNum === false) {
            $this->ajaxReturn(['code'=>3000, 'msg' => '库存不足'],'json');
        }
        $userId = session('userId');
        $user = D('user')->getOne(['id' => $userId],'level');
        $goodsModel = D('goods');
        $goods = $goodsModel->getOne(['id'=>$goods_id],'acttype');

        if($goods['acttype'] == 4) {

            $level = M('user')->where(['id' => $userId])->getField('level');
            // 爆品库价格
            $goods_price = M('ExpGoods')->where("goods_id={$goods_id} and find_in_set({$level},level)")->getField('price');
            $realPrice = setCurr($goods_price*$num);
        }else{
            $realPrice = $model->getRealPrice($goods_spec_id,$goods_id,$num);
        }


        if($realPrice === false) {
            $this->ajaxReturn(['code'=>3000, 'msg' => '该规格或该商品不存在'],'json');
        }
        $this->ajaxReturn(['code' => 2000,'msg' => '','real_price'=> setCurr($realPrice)],'json');
    }
    /**
     * 支付订单确认页面
     */
    public function isPay() {

        $orderId = I('get.id');
        redirect('/html/view/order/pay.html?id='.$orderId);
        exit(0);
        
        $uid = session('userId');

        $order = M('order')->where(['id' => $orderId,'user_id'=>$uid])->find();

        // 验证订单状态
        if($order['status'] != 1){
            $this->error('订单已支付或已取消!', U('/user/orderList'));
        }

        $orderGoods = M('OrderGoods')->where(['order_id' => $orderId,'user_id'=>$uid])->find();

        // 验证商品类型 及 购买权限
        $orderGoodsModel  = new OrderGoodsModel(['uid' => $uid,'num' => $order['num'],'goods_id' =>$orderGoods['goods_id']]);

        $result = $orderGoodsModel->validata(1);
        if(!$result) {
            $this->error($orderGoodsModel->getError());
        }

        $data['wx_sn'] = date('YmdHis',time()).mt_rand(10000000,99999999);//商户订单号
        $data['pay_type'] = 1; //微信支付
        $data['created_at'] = time(); //创建时间
        /*$res = D('Order')->where(['id' => $orderId,'user_id' => $uid])->save($data);
        if ($res != 0){
            $this->toPay($orderId,$uid);
        }*/
        $check = D('Order')->find($orderId);
        if ($check['wx_sn'] != ' '){
            $this->toPay($orderId,$uid);
        }else{
            $res = D('Order')->where(['id' => $orderId,'user_id' => $uid])->save($data);
            if ($res != 0){
                $this->toPay($orderId,$uid);
            }
        }

    }

    public function toPay($orderId,$uid){
        $orderInfo = M('order')->where(['id' => $orderId,'user_id'=>$uid])->find();
        $outTradeNo = $orderInfo['wx_sn'];
        $payAmount = $orderInfo['money'];
       
        $time = time();
        $sign = setSign($orderId.$uid, 'alipay_order', time());
        $signStr = "?id={$orderId}&uid={$uid}&_time={$time}&_sign={$sign}";
        include_once "./Public/Wxpay/jsapi.php";
    }

    /**
     * 获取520商品详情
     */
    public function f_order_details() {

        // 获取520 商品
        $data = D('goods')->getOne(['status' => 1, 'acttype' => 1]);

        $this->assign('data', $data);
        $this->assign('spec', []);
        $this->display();
    }

    public function setSpec($arr)
    {   
        if(count($arr) < 2) {
            return null;
        }
        $ret['title'] = $arr[0];
        for ($i = 1; $i < count($arr); $i++) { 
            $ret['key'][] = $arr[$i];
        }

        return $ret;
    }
    public function datails_1(){
           $this->display();
    }
}
