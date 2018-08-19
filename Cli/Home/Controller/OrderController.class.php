<?php
namespace Home\Controller;

use Home\Model\OrderModel;

class OrderController extends CommonController {
    /**
     * 未支付取消订单
     */
    public function closeOrder()
    {
        $order = new OrderModel;
        $time = time()-1800;
        $condition = [
            'status' => ['eq',1],
            'created_at' => ['ELT',$time],

        ];
        $data = $order->where($condition)->relation(true)->select();
        if(!empty($data)) {
            foreach ($data as $key => $order) {
                M()->startTrans();
                $log = '';
                $log .= '['.date('Y-m-d H:i:s', time()).'] 开始执行未支付取消订单'."\r\n";
                $orderModel = M('order');
                $result =$orderModel->where(['id' => $order['id']])->save(['status' => 9]);
                $result =$result && M('goods')->where(['id'=>$order['g']['goods_id']])->setInc('goods_num',$order['num']);
                $result =$result && M('goods')->where(['id'=>$order['g']['goods_id']])->setDec('sale_num',$order['num']);
                $goods_spec_ids = M('OrderGoods')->where(['order_id' => $order['id']])->getField('goods_spec_ids');
                $result = $result && M('goodsSpec')->where(['goods_spec_id' => ['IN', $goods_spec_ids]])->setInc('goods_num', $order['num']);

                $log .= '['.date('Y-m-d H:i:s', time()).'] 订单ID======'.$order['id'].''."\r\n\r\n";
                if($result) {
                    M()->commit();
                    $log .= '['.date('Y-m-d H:i:s', time()).'] 执行成功!'."\r\n\r\n";
                }else{
                    M()->rollback();
                    $log .= '['.date('Y-m-d H:i:s', time()).'] 执行失败!'."\r\n\r\n";
                }

                file_put_contents(dirname(dirname(dirname(__FILE__))).'/Runtime/Logs/closeOrder.log', $log, FILE_APPEND);
            }
        }
    }
    /**
     * 7天后自动收货
     */
    public function goodsRec()
    {
        $order = new OrderModel;
        // 获取已发货的订单
        $condition = [
            'status' => ['eq',3],
            'delivery_at' => ['ELT',strtotime('-7 days')],

        ];

        $data = $order->where($condition)->relation(true)->select();
        if(!empty($data)){
            foreach ($data as $key => $order) {
                M()->startTrans();
                $log = '';
                $log .= '['.date('Y-m-d H:i:s', time()).'] 开始执行自动收货'."\r\n";

                if($order['g']['acttype'] == 2) { // 0元购
                    // 获取返现天数

                    $returnCash = M('return_cash');
                    $return_day = $returnCash->where(['goods_id' => $data['g']['goods_id']])->getField('day');
                    // 添加返现数据
                    $backData['order_id'] = $order['id'];
                    $backData['user_id'] = $order['user_id'];
                    $backData['status'] = 1;
                    $backData['money'] = $order['g']['price'];
                    $backData['remoney'] = $order['g']['price'];
                    $backData['back_money'] = $order['g']['price'];
                    $backData['back_time'] = time()+$return_day*3600*24;
                    $backData['back_day'] = 1;
                    $backData['created_at'] = $backData['update_at'] = time();

                    $backModel = M('back_purchase');
                    $result = $backModel->data($backData)->add();

                    $log .= '返还余额为：'.$backData['money'].',返还时间为：'.date('Y-m-d H:i:s',$backData['back_time'])."\r\n";
                } else if($order['g']['acttype'] == 4){ // 爆品库
                    $result = true;

                    // 用户信息
                    $userId = $order['user_id'];
                    $user = M('user')->where(['id' => $userId])->find();

                    $expLog = "user:[id:{$userId},nickname:{$user['nickname']},level:{$user['level']}],
                        order:[id:{$order['id']}:order_sn:{$order['order_sn']}],商品价格:[{$order['g']['price']}]";
                    $level = $user['level'];

                    // 一级用户数据
                    $l_1_user = M('user')->where(['id' => $user['inv_id']])->field('id, level, inv_id, bonus')->find();

                    // 二级用户数据
                    $l_2_user = M('user')->where(['id' => $l_1_user['inv_id']])->field('id, level, inv_id, bonus')->find();

                    // 获取分佣数据
                    $expConfig = M('ExpConfig')->where(['goods_id' => $order['g']['goods_id'], "FIND_IN_SET({$user['level']}, `res_level`)"])->find();
                    $expJson = json_decode($expConfig['exp_json'], true);

                    // 分佣比例
                    $l_1_ratio = 0;
                    $l_2_ratio = 0;
                    foreach($expJson as $key => $value) {
                        // 一级分佣
                        if(in_array($l_1_user['level'], $value['level'])) {
                            $l_1_ratio = $value['ratio'];

                            if(in_array($l_2_user['level'], $value['sup']['level'])){
                                $l_2_ratio = $value['sup']['ratio'];
                            }
                        }
                    }

                    $l_1_price = number_format($order['g']['price'] * ($l_1_ratio / 100), 2, '.', '');
                    $l_2_price = number_format($order['g']['price'] * ($l_2_ratio / 100), 2, '.', '');

                    // 一级分佣
                    if($l_1_price > 0 ) {
                        $result = $result && M('user')->where(['id' => $l_1_user['id']])->setInc('bonus', $l_1_price);
                        $result = $result && M('bonusDetail')->add([
                            'user_id' => $l_1_user['id'],
                            'b_type' => 3,
                            'type' => 0,
                            'money' => $l_1_user['bonus'] + $l_1_price,
                            'bonus' => $l_1_price,
                            'title' => '爆品库奖励',
                            'detail' => '一级奖励:'.$expLog.",比例:[{$l_1_ratio}%],奖励金:[$l_1_price]",
                            'order_sn' => orderSn('BN'),
                            'order_time' => $order['created_at'],
                            'source_type' => 1,
                            'created_at' => time()
                            ]);
                    }

                    // 二级分佣
                    if($l_2_price > 0 ) {
                        $result = $result && M('user')->where(['id' => $l_2_user['id']])->setInc('bonus', $l_2_price);
                        $result = $result && M('bonusDetail')->add([
                            'user_id' => $l_2_user['id'],
                            'b_type' => 3,
                            'type' => 0,
                            'money' => $l_2_user['bonus'] + $l_2_price,
                            'bonus' => $l_2_price,
                            'title' => '爆品库奖励',
                            'detail' => '二级奖励:'.$expLog.",比例:[{$l_2_ratio}%],奖励金:[$l_2_price]",
                            'order_sn' => orderSn('BN'),
                            'order_time' => $order['created_at'],
                            'source_type' => 2,
                            'created_at' => time()
                            ]);
                    }

                }
                $result = isset($result) ? $result : true;
                // 修改订单状态
                $orderModel = M('order');
                $result = $result&&$orderModel->where(['id' => $order['id']])->save(['status' => 4,'receiving_at' => time()]);

                $log .= '['.date('Y-m-d H:i:s', time()).'] 订单ID======'.$order['id'].''."\r\n\r\n";
                if($result) {
                    M()->commit();
                    $log .= '['.date('Y-m-d H:i:s', time()).'] 执行成功!'."\r\n\r\n";
                }else{
                    M()->rollback();
                    $log .= '['.date('Y-m-d H:i:s', time()).'] 执行失败!'."\r\n\r\n";
                }

                file_put_contents(dirname(dirname(dirname(__FILE__))).'/Runtime/Logs/goodsRec.log', $log, FILE_APPEND);
            }
        }
    }

   
}