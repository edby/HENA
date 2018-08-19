<?php
namespace Common\Model;

class UserModel extends CommonModel {

	// 数据表名
	protected $tableName = 'user';
	
	/**
	 * 获取下级id
	 * @param $userId int 用户id
	 */
	public function getLowIds($userId)
	{
		return $this->getAllField(['inv_id' => $userId], 'id');
	}

}