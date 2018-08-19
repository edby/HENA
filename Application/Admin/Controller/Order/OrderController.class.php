<?php
/**
 * User: WJH
 * Date: 2018/5/20
 * Time: 13:20
 */
namespace Admin\Controller\Order;
use Admin\Controller\CommonController;

class OrderController extends CommonController
{
    public function handleFive()
    {
        $orderGoods = M('Order_goods')->field('order_id')->where('acttype=1')->select();//520订单
        $orderIds = [];//所有520的订单ID
        foreach ($orderGoods as $v){
            $orderIds[] = $v['order_id'];
        }
        $orderList = M('Order')->where(['id'=>['IN',$orderIds],'status' =>['IN',[2,3,4]]])->select();//所有已付款的ordersn，用于检索bonus_detail表
        //$orderList = M('Order')->where(['id'=>['IN',$orderIds],'status' => 2])->select();//所有已付款的ordersn，用于检索bonus_detail表
        foreach ($orderList as $info){
            $check = M('Bonus_detail')->where(['order_sn' => $info['order_sn']])->find();
            if (! $check){
                //订单未入明细表，未处理
                $this->handleFiveNext($info['wx_sn']);
            }
        }
    }

    //520大礼包支付成功后处理
    public function handleFiveNext($order_no)
    {
        $orderModel = M('Order');
        $orderInfo = $orderModel->where('wx_sn='.$order_no)->find();
        M()->startTrans();

        //修改普通用户为微咖
        $userInfo = M('User')->where(array('id' => $orderInfo['user_id']))->find();

        if($userInfo['level'] != 1){
            $disRes = $this->distriBonus($userInfo['inv_id'],$orderInfo['order_sn'],$orderInfo['created_at']);
            if($disRes){
                M()->commit();
                return true;
            }
            M()->rollback();
            return false;
        }

        $res = M('User')->where(array('id' => $userInfo['id']))->save(array('level' => 2,'is_pay' => 1));
        if(! $res){
            M()->rollback();
            return false;
        }
        $disRes = $this->distriBonus($userInfo['inv_id'],$orderInfo['order_sn'],$orderInfo['created_at']);
        if($disRes){
            M()->commit();
            return true;
        }
        M()->rollback();
        return false;
    }

    /**
     * 二级分销，购买520礼包给上级的奖金
     * @param $invId  int 推荐人ID
     * @param $orderno  string 订单号
     * @param $ordertime string 订单时间
     * @return bool 是否成功
     */
    public function distriBonus($invId,$orderno,$ordertime)
    {
        $userModel = M('User');
        $firstInv = $userModel->where(array('id' => $invId))->find();
        $secondInv = $userModel->where(array('id' => $firstInv['inv_id']))->find();
        $bonusData = array();
        $money = $bonus = 0;
        //第一级奖金
        if($firstInv){
            $firstData = $secondData = array();
            switch ($firstInv['level']){
                case 2:
                case 3:
                case 4:
                case 5:
                    $firstData['bonus'] = $firstInv['bonus'] + 150;
                    $money = $firstData['bonus'];
                    $bonus = 150;
                    break;
                case 6:
                case 7:
                case 8:
                    $firstData['bonus'] = $firstInv['bonus'] + 160;
                    $money = $firstData['bonus'];
                    $bonus = 160;
                    break;
                case 9:
                case 10:
                case 11:
                    $firstData['bonus'] = $firstInv['bonus'] + 170;
                    $money = $firstData['bonus'];
                    $bonus = 170;
                    break;
                case 12:
                    $firstData['bonus'] = $firstInv['bonus'] + 180;
                    $money = $firstData['bonus'];
                    $bonus = 180;
                    break;
                case 13:
                    $firstData['bonus'] = $firstInv['bonus'] + 200;
                    $money = $firstData['bonus'];
                    $bonus = 200;
                    break;
                default:
                    $firstData['bonus'] = $firstInv['bonus'] + 0;
                    break;
            }
            //上一级推广人数加1
            $firstData['push_num'] = $firstInv['push_num'] + 1;
            //记录上级的奖励金明细
            if ($bonus != 0){
                $bonusData[] = array(
                    'user_id' => $firstInv['id'],
                    'b_type' => 2,//520
                    'type' => 0,//进账
                    'money' => $money,
                    'bonus' => $bonus,
                    'title' => '推荐520',
                    'source_type' => 1,
                    'detail' => '下级购买520大礼包',
                    'order_sn'=>$orderno,
                    'order_time' => $ordertime,
                    'created_at' => time()
                );
            }
            //校验当前人数应该所属的等级
            $newLevel = $this->checkLevel($firstData['push_num']);
            $firstData['level'] = ($newLevel > $firstInv['level']) ? $newLevel: $firstInv['level'];//新等级大于当前等级才更新
            //file_put_contents('./log.txt',"\r\n推广人数\r\n".$firstData['push_num']."\r\n应该所属等级\r\n".$firstData['level']);
            $res1 = $userModel->where(array('id' => $firstInv['id']))->save($firstData);
            if(! $res1){
                file_put_contents('./log.txt',"\r\n出错了\r\n".json_encode($firstData));
                return false;
            }
        }

        $money = $bonus = 0;
        //第二级奖金
        if($secondInv){
            switch ($secondInv['level']){
                case 2:
                    $secondData['bonus'] = $secondInv['bonus'] + 0;
                    break;
                case 3:
                    $secondData['bonus'] = $secondInv['bonus'] + 50;
                    $money = $secondData['bonus'];
                    $bonus = 50;
                    break;
                case 4:
                    $secondData['bonus'] = $secondInv['bonus'] + 60;
                    $money = $secondData['bonus'];
                    $bonus = 60;
                    break;
                case 5:
                    $secondData['bonus'] = $secondInv['bonus'] + 70;
                    $money = $secondData['bonus'];
                    $bonus = 70;
                    break;
                case 6:
                    $secondData['bonus'] = $secondInv['bonus'] + 80;
                    $money = $secondData['bonus'];
                    $bonus = 80;
                    break;
                case 7:
                    $secondData['bonus'] = $secondInv['bonus'] + 90;
                    $money = $secondData['bonus'];
                    $bonus = 90;
                    break;
                case 8:
                    $secondData['bonus'] = $secondInv['bonus'] + 100;
                    $money = $secondData['bonus'];
                    $bonus = 100;
                    break;
                case 9:
                    $secondData['bonus'] = $secondInv['bonus'] + 110;
                    $money = $secondData['bonus'];
                    $bonus = 110;
                    break;
                case 10:
                    $secondData['bonus'] = $secondInv['bonus'] + 120;
                    $money = $secondData['bonus'];
                    $bonus = 120;
                    break;
                case 11:
                    $secondData['bonus'] = $secondInv['bonus'] + 130;
                    $money = $secondData['bonus'];
                    $bonus = 130;
                    break;
                case 12:
                    $secondData['bonus'] = $secondInv['bonus'] + 140;
                    $money = $secondData['bonus'];
                    $bonus = 140;
                    break;
                case 13:
                    $secondData['bonus'] = $secondInv['bonus'] + 150;
                    $money = $secondData['bonus'];
                    $bonus = 150;
                    break;
                default:
                    $secondData['bonus'] = $secondInv['bonus'] + 0;
                    break;
            }
            //记录上级的奖励金明细
            if ($bonus != 0){
                $bonusData[] = array(
                    'user_id' => $secondInv['id'],
                    'b_type' => 2,//520
                    'type' => 0,//进账
                    'money' => $money,
                    'bonus' => $bonus,
                    'title' => '推荐520',
                    'source_type' => 2,
                    'detail' => '下级的下级购买520大礼包',
                    'order_sn'=>$orderno,
                    'order_time' => $ordertime,
                    'created_at' => time()
                );
            }
            $res2 = $userModel->where(array('id' => $secondInv['id']))->save($secondData);
            //file_put_contents('./log.txt',"\r\n暂时没出错\r\n".json_encode($bonusData));
            if($res2 === false){
                file_put_contents('./log.txt',"\r\n出错了\r\n".json_encode($secondData));
                return false;
            }
        }
        //奖金明细入库
        //file_put_contents('./log.txt',"\r\n奖金明细\r\n".json_encode($bonusData));
        if ($bonusData){
            $res = M('Bonus_detail')->addAll($bonusData);
            if (! $res){
                return false;
            }
        }
        return true;
    }

    /**
     * 校验当前直推人数应该所属的等级
     * @param $num  直推人数
     * @return number  等级
     */
    public function checkLevel($num)
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