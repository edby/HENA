<?php
namespace Home\Controller;

use Think\Controller;
use Home\Model\OrderRelationModel;

class OrderlistController extends LoginController {

    // public function index() {
    //     $action = I('get.action','all');
    //     $status = I('get.status');
    //     $orderid = I('get.orderid');
    //     switch ($action){
    //         case 'all':
    //             $this->orderlist($status);
    //             break;
    //         case '1':
    //             $this->getorder(1,$orderid); //待付款
    //             break;
    //         case '2':
    //             $this->getorder(2,$orderid);  //待发货
    //             break;
    //         case '3':
    //             $this->getorder(3,$orderid);  //已发货
    //             break;
    //         case '4':
    //             $this->getorder(4,$orderid);  //已收货
    //             break;
    //         default:
    //             break;
    //     }

    // }


    //全部订单数据
//     public function orderlist($status){

//         $model = D('Orderlist');   //session('userId')
//         $listdata = $model-> orderlist(session('userId'));
//         $status = $status ? $status : 0;
// //        dump($listdata);
//         $this->assign('status',$status);
//         $this->assign('listdata',$listdata);
//         $this->display('Order/order_list');

//     }

    // //订单详情
    // public function getorder($status,$orderid){
    //     $model = D('Orderdetail');
    //     $data = $model->getdata($orderid);
    //     $this->assign('data',$data[0]);
    //     if($data[0]['status'] == 1){
    //         $this->display('Order/dfk');	 //待付款
    //     }elseif($data[0]['status'] == 2){
    //         $this->display('Order/yfk');   //待收货
    //     }elseif($data[0]['status'] == 3){
    //         $this->display('Order/yfh');	 //已付款
    //     }else{
    //         $this->display('Order/ysh');	 //已收货
    //     }

    // }

    /**
     * 确认收货
     * @param $oid
     */
    public function goodsRec($oid)
    {
        $model = new OrderRelationModel;
        $uid = session('userId');
        $condition = [
            'status' => ['eq',3],
            'id' => ['eq',$oid],
            'uid' => $uid
        ];
        $data = $model->where($condition)->relation(true)->find();
        if(empty($data)) {
            $this->error('订单信息有误!');
        }

        M()->startTrans();

        if($data['g']['acttype'] == 2) {

            // 获取返现天数
            $returnCash = M('return_cash');
            $return_day = $returnCash->where(['goods_id' => $data['g']['goods_id']])->getField('day');
            // 添加返现数据
            $backData['order_id'] = $data['id'];
            $backData['user_id'] = $data['user_id'];
            $backData['status'] = 1;
            $backData['money'] = $data['g']['price'];
            $backData['remoney'] = $data['g']['price'];
            $backData['back_money'] = $data['g']['price'];
            $backData['back_time'] = time()+$return_day*3600*24;
            $backData['back_day'] = 1;
            $backData['created_at'] = $backData['update_at'] = time();

            $backModel = M('back_purchase');
            $result = $backModel->data($backData)->add();
        } else if ($data['g']['acttype'] == 4) { // 爆品库
            $result = true;

            // 用户信息
            $userId = $data['user_id'];
            $user = M('user')->where(['id' => $userId])->find();

            $expLog = "user:[id:{$userId},nickname:{$user['nickname']},level:{$user['level']}],
                        order:[id:{$data['id']}:order_sn:{$data['order_sn']}],商品价格:[{$data['g']['price']}]";
            $level = $user['level'];

            // 一级用户数据
            $l_1_user = M('user')->where(['id' => $user['inv_id']])->field('id, level, inv_id, bonus')->find();

            // 二级用户数据
            $l_2_user = M('user')->where(['id' => $l_1_user['inv_id']])->field('id, level, inv_id, bonus')->find();

            // 获取分佣数据
            $expConfig = M('ExpConfig')->where(['goods_id' => $data['g']['goods_id'], "FIND_IN_SET({$user['level']}, `res_level`)"])->find();
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

            $l_1_price = number_format($data['g']['price'] * ($l_1_ratio / 100), 2, '.', '');
            $l_2_price = number_format($data['g']['price'] * ($l_2_ratio / 100), 2, '.', '');

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
                        'order_time' => $data['created_at'],
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
                        'order_time' => $data['created_at'],
                        'source_type' => 2,
                        'created_at' => time()
                    ]);
            }
        }
        
        $result = isset($result) ? $result : true;
        $orderModel = M('order');
        $result = $result&&$orderModel->where($condition)->save(['status' => 4,'receiving_at' => time()]);
        if($result) {
            M()->commit();
            $this->ajaxReturn(['code' => 2000,'msg' => '成功'],'json');
        }else{
            M()->rollback();
            $this->ajaxReturn(['code' => 3000,'msg' => '失败'],'json');
        }

    }

}