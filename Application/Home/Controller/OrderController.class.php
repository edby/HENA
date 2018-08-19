<?php
namespace Home\Controller;
use Home\Model\OrderGoodsModel;
use Home\Model\OrderModel;
use Think\Controller;
use Common\Model\GoodsSpecModel;

class OrderController extends LoginController {

    /**
     * 微信支付的参数
     */
    public function wxNum()
    {
        $orderId = I('post.order_id');
        //生成微信支付订单
        $data = array();
        $data['wx_sn'] = date('YmdHis',time()).mt_rand(10000000,99999999);//商户订单号
        $data['pay_type'] = 1; //微信支付
        $data['created_at'] = time(); //创建时间
        $res = M('Order')->where('id='.$orderId)->save($data);
        if($res){
            $this->ajaxReturn(array('status'=>1,'msg'=>$data['wx_sn']));
        }
        $this->ajaxReturn(array('status'=>0,'msg'=>'服务器繁忙，请稍后重试!'));
    }

    /**
     * 确认订单
     */
    public function confirmOrder($goods_id,  $num = 1, $spec = '', $address_id = 0, $deduc = 0)
    {

        $userId = getUserId();
        $goodsId = intval($goods_id);
        $addressId = intval($address_id);
        $num = intval($num);
        $num = $num <= 0 ? 1 : $num;
        $specData = $spec;
        $goods_spec_id = explode(',',$specData);



        // 获取商品信息
        $goods = D('Goods')->getOne(['id' => $goodsId]);

        if(empty($specData)){
            $this->error('商品规格未选择!');
        }

        // 验证是否绑定手机号
        $tel = D('user')->where(['id' => $userId])->getField('tel');
        if($tel == ''){
            $this->error('请绑定手机号后购买!');
        }

        // 验证规格
        $specNum = D('goodsSpec')->where(['goods_spec_id' => ['in', $goods_spec_id], 'goods_id' => $goodsId])->getField('count(*)');
        if($specNum != count($goods_spec_id)){
            $this->error('商品规格错误，请重新选择!');
        }

        //验证用户是否有未支付订单，有则提醒去支付
        $user_order_goods = D()->query("SELECT a.*,b.* FROM hn_order a LEFT JOIN hn_order_goods b ON a.id = b.order_id WHERE a.status=1 AND b.goods_id=".$goodsId." AND b.user_id = ".$userId);
        if($user_order_goods){
            $this->error('存在未支付的订单，请先支付或取消订单!');
        }

        // 验证商品类型 及 购买权限
        $orderGoodsModel  = new OrderGoodsModel(['uid' => $userId, 'num' => $num,'goods_id' => $goodsId]);
        $result = $orderGoodsModel->validata();
        if(!$result) {
            $this->error($orderGoodsModel->getError());
        }

        // 获取地址
        if($addressId == 0){
            $address = D('address')->getOne(['user_id' => $userId, 'status' => 1]);
        } else {
            $address = D('address')->getOne(['id' => $addressId, 'user_id' => $userId, 'status' =>['IN', [0, 1]]]);
        }

        $unitPrice = $goods['goods_price']; // 单价
       
            
        $model = new GoodsSpecModel;
        // 判断规格是否都选中
        $count = count($goods_spec_id);
        $result = $model->countGoodsSpecNum($goodsId);
        if($count < $result) {
            $this->error('规格未全部选中');
        }

        // 检测库存
        $goodsNum = $model->checkGoodsNum($goods_spec_id,$goods_id,$num);
        if($goodsNum === false) {
            $this->error('库存不足');
        }
        // 商品总价
        $realMoney = $model->getRealPrice($goods_spec_id,$goodsId,$num);

        if($realMoney === false) {
            $this->error('该商品或规格不存在');
        }
        // 商品单价
        $unitPrice = $realMoney/$num;

        //  爆品库商品总价
        if($goods['acttype'] == 4 ) {
            $level = M('user')->where(['id' => $userId])->getField('level');
            // 爆品库价格
            $bPrice = M('ExpGoods')->where("goods_id={$goodsId} and find_in_set({$level},level)")->getField('price');
            if($bPrice){
                $unitPrice = $bPrice;
                $realMoney = setCurr($unitPrice*$num);
            }
            
        }

        // 520|0元购|爆品库支付总价
        $totalExp = setCurr($goods['express_fee']+$goods['up_express_fee']*($num-1));

        $totalMoney = $realMoney + $totalExp;
        $spec = $model->getSpecStr($goods_spec_id);

        $hBalance = 0;
        if($goods['h_balance'] > 0){
            // 获取用户的纳豆数
            $uBalance = D('user')->getOneField( ['id' => $userId], 'balance');
            $hBalance = $unitPrice;
            $hBalance = $uBalance >= $hBalance ? $hBalance : $uBalance;
        }
        

        $totalMoney = $totalMoney - $balance;
        //赠送盟豆数量
        $points =  floor($goods['points_times']*$unitPrice)*$num;
        $this->assign('goods', $goods);
        $this->assign('address', $address);
        $this->assign('num', $num);
        $this->assign('deduc', $deduc);
        $this->assign('totalExp', $totalExp);
        $this->assign('spec', $spec);
        $this->assign('specData',$specData);
        $this->assign('totalMoney', setCurr($totalMoney));
        $this->assign('unitPrice', setCurr($unitPrice));

        $this->assign('hBalance', setCurr($hBalance)); // 纳豆抵扣
        $this->assign('points', $points); // 盟豆数量

        $this->display();

    }


    /**
     * 创建订单
     */
    public function createOrder()
    {

        $userId = session('userId');
        $goodsId = intval($_POST['goods_id']);
        $addressId = intval($_POST['address_id']);
        $deduc = intval($_POST['deduc']);
        $specData = $_POST['spec'];
        $goods_spec_id = explode(',',$specData);

        $num = $_POST['goods_num']; // 购买数量
        $num = intval($num);
        $num = $num <= 0 ? 1 : $num;

        // 验证商品id
        if(empty($goodsId)) {
            $this->error('商品参数错误!');
        }

        // 验证规格
        if(empty($specData)){
            $this->error('规格不存在，请重新选规格!');
        }

        
        // 验证是否绑定手机号
        $tel = D('user')->where(['id' => $userId])->getField('tel');
        if($tel == ''){
            $this->error('请绑定手机号后购买!');
        }

        // 验证规格
        $specNum = D('goodsSpec')->where(['goods_spec_id' => ['in', $goods_spec_id], 'goods_id' => $goodsId])->getField('count(*)');
        if($specNum != count($goods_spec_id)){
            $this->error('商品规格错误，请重新选择!');
        }

        //验证用户是否有未支付订单，有则提醒去支付
        $user_order_goods = D()->query("SELECT a.*,b.* FROM hn_order a LEFT JOIN hn_order_goods b ON a.id = b.order_id WHERE a.status=1 AND b.goods_id=".$goodsId." AND b.user_id = ".$userId);
        if($user_order_goods){
            $this->error('存在未支付的订单，请先支付或取消订单!');
        }

        // 验证地址
        if(empty($addressId)) {
            $this->error('地址尚未选择!');
        }
        $address = M('address')->where(['id' => $addressId, 'user_id' =>$userId])->find();
        // 地址信息验证
        if(!$address) {
            $this->error('地址信息错误!');
        }

        // 验证商品类型 及 购买权限
        $orderGoodsModel  = new OrderGoodsModel(['uid' => $userId,'num' => $num,'goods_id' =>$goodsId]);
        $goodsData = $orderGoodsModel->validata();
        if(!$goodsData) {
            $this->error($orderGoodsModel->getError());
        }

       /* $unitPrice = setCurr($goodsData['goods_price']); // 单价
        $totalExp = setCurr($goodsData['express_fee'] + ($goodsData['up_express_fee']*($nm-1))); // 运费
        $totalMoney = setCurr($unitPrice * $num + $totalExp); // 商品总价*/

        /**
         * 规格增值
         */
        $specStr = '';
        $goodsSpecModel = new GoodsSpecModel;
        

        // 判断规格是否都选中
        $count = count($goods_spec_id);
        $result = $goodsSpecModel->countGoodsSpecNum($goodsId);
        if($count < $result) {
            $this->error('规格未全部选中');
        }

        // 检测库存
        $goodsNum = $goodsSpecModel->checkGoodsNum($goods_spec_id,$goodsId,$num);
        if($goodsNum === false) {
            $this->error('库存不足');
        }

        // 商品总价
        $realMoney = $goodsSpecModel->getRealPrice($goods_spec_id,$goodsId,$num);
        if($realMoney === false) {
            $this->error('该商品或规格不存在');
        }

        // 商品单价
        $unitPrice = setCurr($realMoney/$num);

        // 爆品库商品总价
        $user = D('user')->getOne(['id' => $userId]);
        if($goodsData['acttype'] == 4) {

            $level = $user['level'];
            // 爆品库价格
            $bPrice = M('ExpGoods')->where("goods_id={$goodsId} and find_in_set({$level},level)")->getField('price');
             if($bPrice){
                $unitPrice = $bPrice;
                $realMoney = setCurr($unitPrice*$num);
            }
            $realMoney = setCurr($unitPrice*$num);
        }

        $totalExp = setCurr($goodsData['express_fee'] + ($goodsData['up_express_fee']*($num-1))); // 运费
        $totalMoney = $realMoney + $totalExp;
        // 规格字符串
        $specStr = $goodsSpecModel->getSpecStr($goods_spec_id);


        // 处理纳豆抵扣
        $balance = 0;
        if($deduc == 1){

            $uBalance = M('user')->where(['id' => $userId])->getField('balance');
            $hBalance = M('goods')->where(['id' => $goodsId])->getField('h_balance');
            $hBalance = $hBalance >= $unitPrice ? $unitPrice : $hBalance;
            $balance = setCurr($hBalance*$num) >= setCurr($uBalance) ? $uBalance : $hBalance*$num;
        }

        // 支付金额是否为0
        $status = 1;
        $totalPay = setCurr($totalMoney - $balance);
        $pay_type = 1;
        $pay_at = 0;
        if($totalPay == 0){
            $status = 2;
            $pay_type = 3;
            $pay_at = time();
        }
        $Order = D('Order');
        // 开启事务
        M()->startTrans();

         //订单
        $result = $OrderId = $Order->createData([
            'user_id' => $userId,
            'order_sn' => $Order->createOrderSn(),
            'money' => $totalPay,
            'num' => $num,
            'balance' => setCurr($balance),
            'pay_type' => $pay_type,
            'status' => $status,
            'created_at' => time(),
            'pay_at' => $pay_at,
        ]);

        // 订单地址
        $OrderAddr = D('OrderAddr');
        $result = $result && $OrderAddr->createData([
            'order_id' => $OrderId,
            'user_id' => $userId,
            'status' => 0,
            'name' => $address['realname'],
            'phone' => $address['tel'],
            'pcaaddress' => $address['pcaaddress'],
            'detail' => $address['detail'],
            'created_at' => time(),
        ]);

        $points_times_t = M('goods')->where(['id' => $goodsId])->getField('points_times');
        // 订单商品
        $result = $result && $orderGoodsModel->createData([
            'order_id' => $OrderId,
            'user_id' => $userId,
            'goods_id' => $goodsId,
            'e_order_sn' => $orderGoodsModel->createEOrderSn(), //子订单号
            'acttype' => $goodsData['acttype'],
            'goods_name' => $goodsData['goods_name'],
            'number' => $num,
            'points_times_t' => $points_times_t,
            'goods_spec_ids' => $specData,
            'spec' => $specStr,
            'price' => $unitPrice,
            'total_money' => $totalMoney,
            'express_fee' => $goodsData['express_fee'],
            'created_at' => time(),
            'goods_img' => $goodsData['goods_logo'],
            'total_express_fee' => $totalExp,
        ]);
        if($status == 2) {
            $orderGoods = M('OrderGoods')->where(['order_id'=>$OrderId,'user_id'=>$userId,'goods_id'=>$goodsId])->find();
            if($goodsData['acttype'] == 4) {
                $expGoods = M('ExpGoods')->where("goods_id={$goodsId} and FIND_IN_SET({$user['level']},level)")->find();
                $expModel = D('ExpGoods');
                $result = $result && $expModel->handle($expGoods,$user,$orderGoods);
            }
            if($goodsData['acttype'] == 5) {
                $shareGoods = M('ShareGoods')->where("goods_id={$goodsId}")->find();
                $shareModel = D('ShareGoods');
                $result = $result && $shareModel->handle($shareGoods,$user,$orderGoods);
            }

            $mData = [
                'money' => setCurr($totalMoney),
                'goods_name' => $goodsData['goods_name'],
            ];
            $mJson = json_encode($mData);
            $mUrl = 'http://'.$_SERVER['HTTP_HOST'].'/api/app/templete/send?openid='.$user['wx_openid'].'&data='.$mJson.'&type=4&url=http://'.$_SERVER['HTTP_HOST'].U('user/orderDetail').'?orderid='.$OrderId;
            getJson($mUrl);
        }

        // 纳豆抵扣
        if($deduc == 1 && $balance > 0){
            $result = $result && M('user')->where(['id' => $userId])->
                save(['balance' => ['exp', 'balance' . '-' . $balance], 'updated_at' => time()]);
            
            // 纳豆日志
            $result = $result && D('BalanceDetail')->createData([
                'user_id' => $userId,
                'b_type' => 5,
                'type' => 1,
                'money' => setCurr($balance),
                'balance' => setCurr($uBalance - $balance),
                'title' => '商品抵扣',
                'detail' => "商品抵扣[账单id:{$OrderId}]",
                'created_at' => time(),
            ]);
        }

        // 商品减库存
        $goods = D('goods');
        $result = $result && $goods->updateData("id={$goodsId} and goods_num > 0",[
                'goods_num' => ['exp', 'goods_num' . '-' . $num], 
                'sale_num' => ['exp', 'sale_num' . '+' . $num],
        ]);
        // 规格减库存
        if(!empty($specData)) {
            $goods_spec_id = explode(',',$specData);
            $result = $result && $goodsSpecModel->where(['goods_spec_id'=>['IN',$goods_spec_id]])->setDec('goods_num',$num);
        }
        if($result){
            M()->commit();
            if($totalPay == 0){
                $this->success('下单成功!', U('/user/orderDetail', ['orderid' => $OrderId]));
            } else {
                $this->success('下单成功!', U('goods/ispay', ['id' => $OrderId]));
            }
            
            exit(0);

        } else {
            M()->rollback();
            $this->error('下单失败!');
            exit(0);
        }
    }

    public function orderpay($id)
    { 
        // 订单id
        if(empty($id)) {
            $this->error('参数错误');
        }
        $userId = session('userId');
        $order = D('Order')->where(['id' => $id, 'user_id' => $userId, 'status' => 1])->find();

        
        $this->assign('order', $order);
        $this->display();
    }

    /**
     * LED数据显示
     */
    public function apiordermoney()
    {
        if(IS_POST) {
            $money = M('Order')->sum('money');
            $Tempmoney = M('Temp_money')->sum('temp_money');
            if (empty($Tempmoney)) {
                $data = ['code' => 2000, 'money' => number_format($money, 2, '.', '')];

                $this->ajaxReturn($data, 'json');
            } else {
                $nowmoney = $money + $Tempmoney;
                $data = ['code' => 2000, 'money' => number_format($nowmoney, 2, '.', '')];

                $this->ajaxReturn($data, 'json');
            }
        }
        $this->display();
    }
}