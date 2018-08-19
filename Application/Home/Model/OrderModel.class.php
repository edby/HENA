<?php
namespace Home\Model;

class OrderModel extends CommonModel{

    protected $tableName = 'order';

    /**
	 * 生成订单号
	 */
	public function createOrderSn()
	{
		$orderSn = orderSn('sn');
		$isExist = $this->field('id')->where(['order_sn' => $orderSn])->select();
		while ($isExist) {
			$orderSn = orderSn('sn');
			$isExist = $this->field('id')->where(['order_sn' => $orderSn])->select();
		}

		return $orderSn;
	}

	/**
	 * 创建订单
	 */
	public function createOrder($data)
	{
		return $this->data($data)->add();
	}

	/**
	 * 获取当前用户购买改商品的数量
	 * @param $goodsId 商品id
	 * @param $userId int 用户id
	 * @return int 数量
	 */
	public function getOrderGoodsNum($goodsId, $userId)
	{
		$orderGoodsModel = D('order_goods');
		$all = $orderGoodsModel->getAll(['goods_id' => $goodsId, 'user_id' => $userId]);

		// 记录购买数量
		$total = 0;
		foreach ($all as $key => $value) {
			
			$condition['id'] = $value['order_id'];
			$condition['status'] = ['IN', '2,3,4,10'];
			$isExist = $this->isExist($condition);
		
			if($isExist) {
				$total += $value['number'];
			}
		}
		
		return $total;
	}

    /**
     * 520礼包回调处理
     * @param array $order // 订单数组包含order_goods 信息
     */
	public static function handleFive($order)
    {
        // 修改订单状态
        $model = new self;
        $user = D('user')->getUserInfoById($order['user_id']);
        // 分销数据
        $disrArr = self::distribution($user['inv_id'],$order['order_sn'],$order['created_at']);

        try{
            M()->startTrans();
            $result = $model->where(['order_sn' => $order['order_sn'],'status' => 1])->save(['status' => 2,'pay_at' => time()]);
            if($user['level'] == 1) {
                $result = $result&&M('user')->where(['id' => $order['user_id']])->save(['is_pay' => 1,'level' => 2]);
            }

            // 分销
            if($user['inv_id'] != 0) {
                $result = $result && M('user')->where(['id'=>$disrArr['first']['id']])->save($disrArr['first']);
                if(!empty($disrArr['second'])) {
                    $result = $result && M('user')->where(['id'=>$disrArr['second']['id']])->save($disrArr['second']);
                }
                $result = $result && M('Bonus_detail')->addAll($disrArr['bonusData']);

            }
            if($result) {
                M()->commit();
                return true;
            }else{
                M()->rollback();
                return ['msg'=>'未知错误'];
            }
        }catch (Exception $e) {

            M()->rollback();
            return ['msg'=>$e->getMessage()];
        }






    }

    /**
     * @param $invId 上级ID
     * @param $orderSn 订单号
     * @return array first 上级数据 second 上上级数据  bonusData 明细数据
     */
    public static function distribution($invId,$orderSn,$orderTime)
    {
        $firstUser = D('user')->getUserInfoById($invId);
        $secondUser = D('user')->getUserInfoById($firstUser['inv_id']);

        $bonusData = [];
        $time = time();
        if(!empty($firstUser)) {
            $bonus = self::getBonus($firstUser['level'],1);

            $bonusData[] = array(
                'user_id' => $firstUser['id'],
                'b_type' => 2,//520
                'type' => 0,//进账
                'money' => $firstUser['bonus'],
                'bonus' => $bonus,
                'title' => '推荐520',
                'source_type' => 1,
                'detail' => '下级购买520大礼包',
                'order_sn'=>$orderSn,
                'order_time' => $orderTime,
                'created_at' => time()
            );
            // 奖金
            $firstUser['bonus'] += $bonus;
            // 校验推广人数 返回相应等级
            $firstUser['push_num'] += 1;
            $firstUser['updated_at'] = $time;
            $newLevel = self::checkLevel($firstUser['push_num']);
            $firstUser['level'] = ($newLevel > $firstUser['level']) ? $newLevel: $firstUser['level'];


        }
        if(!empty($secondUser)) {
            $bonus = self::getBonus($secondUser['level'],2);
            $bonusData[] = array(
                'user_id' => $secondUser['id'],
                'b_type' => 2,//520
                'type' => 0,//进账
                'money' => $secondUser['bonus'],
                'bonus' => $bonus,
                'title' => '推荐520',
                'source_type' => 1,
                'detail' => '下下级购买520大礼包',
                'order_sn'=>$orderSn,
                'order_time' => $orderTime,
                'created_at' => time()
            );
            $secondUser['bonus'] += $bonus;
            $secondUser['updated_at'] = $time;
        }
        return ['first'=>$firstUser,'second'=>$secondUser,'bonusData' => $bonusData];

    }

    /**
     * 获取分佣奖金
     * @param $level 用户等级
     * @param $type 级别（1,一级分佣  2,二级分佣）
     * @return int 奖金
     */
    protected static function getBonus($level,$type)
    {
        $bonus = 0;
        if($type == 1) {
            switch ($level) {
                case 2:
                case 3:
                case 4:
                case 5:

                    $bonus = 150;
                    break;
                case 6:
                case 7:
                case 8:

                    $bonus = 160;
                    break;
                case 9:
                case 10:
                case 11:

                    $bonus = 170;
                    break;
                case 12:

                    $bonus = 180;
                    break;
                case 13:

                    $bonus = 200;
                    break;
                default:
                    $bonus = 0;
                    break;
            }

            return $bonus;

        }elseif($type = 2){
            switch ($level) {
                case 2:
                    $bonus = 0;
                    break;
                case 3:

                    $bonus = 50;
                    break;
                case 4:

                    $bonus = 60;
                    break;
                case 5:

                    $bonus = 70;
                    break;
                case 6:

                    $bonus = 80;
                    break;
                case 7:

                    $bonus = 90;
                    break;
                case 8:

                    $bonus = 100;
                    break;
                case 9:

                    $bonus = 110;
                    break;
                case 10:

                    $bonus = 120;
                    break;
                case 11:

                    $bonus = 130;
                    break;
                case 12:

                    $bonus = 140;
                    break;
                case 13:

                    $bonus = 150;
                    break;
                default:
                    $bonus = 0;
                    break;
            }
            return $bonus;
        }

    }

    /**
     * 校验等级
     * @param $num 推广人数
     * @return int 等级
     */
    protected static function checkLevel($num)
    {
        if ($num == 0){
            return 1;
        }

        if ($num <= 2 && $num >= 1){
            return 2;
        }

        if ($num <= 5 && $num >= 3){
            return 3;
        }

        if ($num <= 8 && $num >= 6){
            return 4;
        }

        if ($num <= 11 && $num >= 9){
            return 5;
        }

        if ($num <= 14 && $num >= 12){
            return 6;
        }

        if ($num <= 17 && $num >= 15){
            return 7;
        }

        if ($num <= 20 && $num >= 18){
            return 8;
        }

        if ($num <= 23 && $num >= 21){
            return 9;
        }

        if ($num <= 26 && $num >= 24){
            return 10;
        }

        if ($num <= 44 && $num >= 27){
            return 11;
        }

        if ($num <= 80 && $num >= 45){
            return 12;
        }

        if ($num > 80){
            return 13;
        }
    }
}