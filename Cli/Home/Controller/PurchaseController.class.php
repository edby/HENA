<?php
namespace Home\Controller;
use Think\Controller;

/**
 * 零元购订单
 */
class PurchaseController extends CommonController {
    
    // 脚本处理0元购返奖励金
    public function index(){

        $Purchase = M('back_purchase');
		
        $condition['status'] = 1;
        $condition['back_time'] = ['ELT', time()];
		$data = $Purchase->where($condition)->field('*')->select();
		
        if(empty($data)) {
            return '';
        }
		foreach ($data as $key => $value) {
            $log = '';
			// 开启事务
        	M()->startTrans();
        	$log .= '['.date('Y-m-d H:i:s', time()).'] 开始执行0元购返奖余额'."\r\n";


			// 更新返单数据
			$backP = M("back_purchase"); 
			
			$backPData['remoney'] = $value['money'];
			$backPData['status'] = 2;
			$backPData['updated_at'] = time();
			$result = $backP->where(['id' => $value['id']])->save($backPData); // 根据条件更新记录

			// 获取用户余额
			$user = M('user');
			$result =  $result && $balance = $user->where(['id' => $value['user_id']])->getField('balance');

			// 写入返单日志
			$backPDetail = M('back_purchase_detail');
			$backPDetailData['back_purchase_id'] = $value['id'];
			$backPDetailData['user_id'] = $value['user_id'];
			$backPDetailData['money'] = $value['money'];
			$backPDetailData['created_at'] = time();
			$result =  $result && $backPDetail->data($backPDetailData)->add();

			// 更新用户余额
			$userBalance['balance'] = $balance + $value['money'];
			$result = $result && $user->where(['id' => $value['user_id']])->save($userBalance);

			// 写入用户余额日志
			$balanceDetail = M('balance_detail');
			$balanceDetailDate['user_id'] = $value['user_id'];
			$balanceDetailDate['b_type'] = 2;
			$balanceDetailDate['type'] = 0;
			$balanceDetailDate['money'] = $value['money'];
			$balanceDetailDate['balance'] = $userBalance['balance'];
			$balanceDetailDate['title'] = '0元购返余额';
			$balanceDetailDate['detail'] = '0元购返余额:'.$value['money'].'元'.'，当前余额'.$userBalance['balance'].'元';
			$balanceDetailDate['created_at'] = time();
			$result = $result && $balanceDetail->data($balanceDetailDate)->add();

			$log .= "user_id: {$value['user_id']}\r\n";
			$log .= "order_id: {$value['order_id']}\r\n";
			$log .= "back_purchase_id: {$value['id']}\r\n";
	        $log .= "返余额{$value['money']},  当前余额{$userBalance['balance']}\r\n";

			if($result){
	            M()->commit();
	            $log .= '['.date('Y-m-d H:i:s', time()).'] 执行成功!'."\r\n\r\n";
	        } else {
	            M()->rollback();

	            $log .= '['.date('Y-m-d H:i:s', time()).'] 执行失败!'."\r\n\r\n";
	        }

	        $test = file_put_contents(dirname(dirname(dirname(__FILE__))).'/Runtime/Logs/purchase.log', $log, FILE_APPEND);
		}


    }
}