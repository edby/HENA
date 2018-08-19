<?php
/**
 * User: WJH
 * Date: 2018/5/17
 * Time: 14:33
 */
namespace Home\Model;
class ExpGoodsModel extends CommonModel
{
    protected $tableName = 'exp_goods';

    /**
     * @param $model 活动数据
     * @param $userModel 用户数据
     * @param $orderGoods 订单数据
     */
    public function handle($model,$userModel,$orderGoods)
    {

        // 上级
        $firstUser = M('User')->where(['id'=>$userModel['inv_id']])->find();
        if(!$firstUser){
            return true;
        }

        $order = M('Order')->where(['id'=>$orderGoods['order_id']])->find();

        // 上上级
        $secondUser = M('User')->where(['id'=>$firstUser['inv_id']])->find();

        $firstRatio = M('ExpFirst')->where("e_g_id={$model['id']} and FIND_IN_SET({$firstUser['level']},level)")->find();
        $result = true;
        $bonusRebate = D('BonusRebate');
        // 上级分佣
        if($firstRatio) {
            if($firstRatio['ratio'] < 1) {
                $firstBonus = $model['price'] * $firstRatio['ratio'];
            }else{
                $firstBonus = $model['price'] - $firstRatio['ratio'];
            }

            $firstBonus *= $orderGoods['number'];

            $firstBonusRebate = clone $bonusRebate;
            $firstData = [
                'user_id' => $firstUser['id'],
                'order_id' => $orderGoods['order_id'],
                'type' => 3,
                'money' => $firstBonus,
                'title' => '爆品库分享收益',
                'detail' => '一级奖励：user:[id:'.$firstUser['id'].',nickname:'.$firstUser['nickname'].',level:'.$firstUser['level'].'],order:[id:'.$orderGoods['order_id'].',order_sn:'.$order['order_sn'].'],商品价格:['.$orderGoods['price'].'],购买数量:['.$orderGoods['number'].'],奖励值:['.$firstRatio['ratio'].'],奖励金:['.$firstBonus.']',
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
        }

        // 上上级分佣

        if($secondUser) {
            $secondRatio = M('ExpSecond')->where("e_f_id={$firstRatio['id']} and FIND_IN_SET({$secondUser['level']},level)")->find();
            if($secondRatio) {
                if($secondRatio['ratio'] < 1) {
                    $secondBonus = $model['price'] * $secondRatio['ratio'];
                }else{
                    $secondBonus = $model['price'] - $secondRatio['ratio'];
                }

                $secondBonus *= $orderGoods['number'];

                $secondBonusRebate = clone $bonusRebate;

                $secondData = [
                    'user_id' => $secondUser['id'],
                    'order_id' => $orderGoods['order_id'],
                    'type' => 3,
                    'money' => $secondBonus,
                    'title' => '爆品库拓展收益',
                    'detail' => '二级奖励：user:[id:'.$secondUser['id'].',nickname:'.$secondUser['nickname'].',level:'.$secondUser['level'].'],order:[id:'.$orderGoods['order_id'].',order_sn:'.$order['order_sn'].'],商品价格:['.$orderGoods['price'].'],购买数量:['.$orderGoods['number'].'],奖励值:['.$secondRatio['ratio'].'],奖励金:['.$secondBonus.']',
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