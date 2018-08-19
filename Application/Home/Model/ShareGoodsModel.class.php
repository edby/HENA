<?php
/**
 * Created by PhpStorm.
 * User: 杨虎成
 * Date: 2018/6/22
 * Time: 10:50
 */

namespace Home\Model;


class ShareGoodsModel extends CommonModel
{
    protected $tableName = 'share_goods';

    /**
     * @param $model 活动数据
     * @param $userModel 用户数据
     * @param $orderGoods 订单数据
     */
    public function handle($model, $userModel, $orderGoods)
    {
        // 上级
        $firstUser = M('User')->where(['id'=>$userModel['inv_id']])->find();
        if(!$firstUser){
            return true;
        }

        $order = M('Order')->where(['id'=>$orderGoods['order_id']])->find();

        // 上上级
        $secondUser = M('User')->where(['id'=>$firstUser['inv_id']])->find();

        $shareMoney = $model['share_money'] * $orderGoods['number'];

        $firstBonus = setCurr($shareMoney/3);
        $secondBonus = setCurr($shareMoney - $firstBonus);
        $result = true;
        $bonusRebate = D('BonusRebate');

        // 上级分佣
        $firstBonusRebate = clone $bonusRebate;
        $firstData = [
            'user_id' => $firstUser['id'],
            'order_id' => $orderGoods['order_id'],
            'type' => 6,
            'money' => $firstBonus,
            'title' => '共享库分享收益',
            'detail' => '一级奖励：user:[id:'.$firstUser['id'].',nickname:'.$firstUser['nickname'].',level:'.$firstUser['level'].'],
            order:[id:'.$orderGoods['order_id'].',order_sn:'.$order['order_sn'].'],商品价格:['.$orderGoods['price'].'],购买数量:['.$orderGoods['number'].'],奖励金:['.$firstBonus.'],共享库配置金额:['.$model['share_money'].']',
            'source_type' => 1,
            'status' => 1,
            'order_sn' => $order['order_sn'],
            'order_time' => $order['created_at'],
            'order_user_id' => $order['user_id'],
            'order_user_nickname' => $userModel['nickname'],
            'order_total_price' => $orderGoods['total_money'],
            'created_at' => time(),
        ];

        $result = $result && $firstBonusRebate->add($firstData);

        // 上上级分佣
        if($secondUser) {
            $secondBonusRebate = clone $bonusRebate;
            $secondData = [
                'user_id' => $secondUser['id'],
                'order_id' => $orderGoods['order_id'],
                'type' => 6,
                'money' => $secondBonus,
                'title' => '共享库拓展收益',
                'detail' => '二级奖励：user:[id:'.$secondUser['id'].',nickname:'.$secondUser['nickname'].',level:'.$secondUser['level'].'],
            order:[id:'.$orderGoods['order_id'].',order_sn:'.$order['order_sn'].'],商品价格:['.$orderGoods['price'].'],购买数量:['.$orderGoods['number'].'],奖励金:['.$secondBonus.'],共享库配置金额:['.$model['share_money'].']',
                'source_type' => 2,
                'status' => 1,
                'order_sn' => $order['order_sn'],
                'order_time' => $order['created_at'],
                'order_user_id' => $order['user_id'],
                'order_user_nickname' => $userModel['nickname'],
                'order_total_price' => $orderGoods['total_money'],
                'created_at' => time(),
            ];

            $result = $result && $secondBonusRebate->add($secondData);
        }

        M()->startTrans();

        if($result) {
            M()->commit();

            return true;
        }else{
            M()->rollback();
            return false;
        }
    }
}
