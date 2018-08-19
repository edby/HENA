<?php
/**
 * Created by PhpStorm.
 * User: 杨虎成
 * Date: 2018/5/7
 * Time: 9:23
 */

namespace Home\Controller;

use Home\Model\OrderRelationModel;
use Home\Model\OrderModel;
/**
 * 支付控制器
 * @package Home\Controller
 */
class PayController extends LoginController
{
    public function index()
    {
        $orderId = I('get.id');
        $order = M('order')->where(['id' => $orderId])->find();
        $this->assign('order',$order);
        $this->assign('order_id',$orderId);
        $this->display();
    }
    public function pay()
    {
        $orderId = I('get.id');

        $uid = session('userId');
        $user = M('User')->where(['id' => $uid])->find();
        if(empty($user['wx_openid'])) {
            $this->ajaxReturn(['code' => 3000,'msg' => '您尚未绑定微信']);
        }
        $orderGoods = M('OrderGoods')->where(['order_id' => $orderId,'user_id'=>$uid])->find();

        // 520限购
        if($user['is_pay'] >= 1 && $orderGoods['acttype'] == 1) {
            $this->ajaxReturn(['code' => 3000,'msg' => '520限购一次']);
        }
        // 0元购限购
        $order = M('order')->where(['id' => $orderId])->find();
        $goods = M('shoping')->where(['id' => $orderGoods['goods_id']])->find();
        if($orderGoods['acttype'] == 2 && $order['num'] > $goods['x_buy_num']) {
            $this->ajaxReturn(['code' => 3000,'msg' => '超出0元购购买数量']);
        }

        if($orderGoods['acttype'] == 2) {
            // 该用户 所有该0元购商品
            $allZero = M('OrderGoods')->field('order_id')->where(['acttype' => 2,'user_id'=>$uid,'goods_id' => $goods['id']])->select();
            foreach ($allZero as $key => $val) {
                $idArr[] = $val['order_id'];
            }

            $zeroOrder = M('order')->field('SUM(num) as total')->where(['id' => ['IN',$idArr],'user_id' => $uid,'status' => ['IN',[2,3,4]]])->find();
            if($zeroOrder['total'] >= $goods['x_buy_num']) {
                $$this->ajaxReturn(['code' => 3000,'msg' => '已达0元购购买上限']);
            }

        }

        vendor('WeChatPay.WeChatPay');
        $config = C('WECHAT');
        $model = new WeChatPay($config['mchId'],$config['appId'],$config['apiKey']);
        $result = $model->createJsBizPackage($user['wx_openid'],$order['money'],$order['order_sn'],'HNGX',U('pay/notify',[],[],true),time());
        $this->ajaxReturn(['code' => 2000,'result' => $result]);

    }

    public function notify()
    {
        $postStr = file_get_contents("php://input");
        $post = json_decode(json_encode(simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        if($post['result_code'] == 'SUCCESS' && $post['return_code'] == 'SUCCESS') {
            // 支付成功扭转订单状态
            $order_sn = $post['out_trade_no'];
//            $order_sn = 'SN201805121145102321380644';

            $model = new OrderRelationModel();
            $order = $model->where(['order_sn' => $order_sn,'status' => 1])->relation(true)->find();

            // 520处理
            if($order['g']['acttype'] == 1) {
                $result = OrderModel::handleFive($order);
            }

            if($result !== true) {

                file_put_contents('./log.txt',"\r\n出错了\r\n".$result['msg']."\r\n订单号:".$order_sn,FILE_APPEND);
            }
            return '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        }else{
            return ['msg' => '支付失败'];
        }
    }
}