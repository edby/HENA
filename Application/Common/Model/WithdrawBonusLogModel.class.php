<?php
namespace Common\Model;

/**
 * 奖励金提现日志
 */
class WithdrawBonusLogModel extends CommonModel{

	// 数据表名
	protected $tableName = 'withdraw_bonus_log';

	/**
	 *获取奖励金提现日志
	 */
	public function getAllLog($withdraw_bonus_id)
	{
		return $this->getAll(['withdraw_bonus_id' => $withdraw_bonus_id]);
	}

	
}