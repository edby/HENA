<?php
namespace Common\Model;

class UserAgentLevelModel extends CommonModel{

	// 数据表名
	protected $tableName = 'user_agent_level';
	
	/**
	 *  关联用户表查询信息
	 */
	public function getRelationInfos($where,  $offset = 0, $limit = 25, $field = '*', $order = 'id')
	{
		$list = $this->getList($where, $offset, $limit, $field, $order);

		$user = new \C_Model\C_UserModel; 
		$det = new \C_Model\C_UserDetDetailModel; 
		foreach ($list as $key => &$value) {
			// 用户数据
			$userData = $user->getDataById($value['user_id'], ['id', 'nickname', 'username', 'tel'] );	
			$value['username'] = $userData['username'];
			$value['nickname'] = $userData['nickname'];
			$value['tel'] = $userData['tel'];
			$value['level_name'] = $value['level_id'] == 14 ? 'AT1' : ($value['level_id'] == 15 ? 'AT2' : '');

			// 下级用户id
			$low_users = $user->getLowIds($value['user_id']);

			// 汇总数据
			$data = $det->getOne(['user_id' => ['in', $low_users]], 'sum(num) as nums, sum(total_money) as total_moneys, sum(total_express_fee) as total_express_fees');
			$value['goods_num'] = empty($data['nums']) ? 0 : $data['nums'];
			$value['goods_money'] = empty($data['total_moneys']) ? 0 : $data['total_moneys'];
			$value['goods_express_fee'] = empty($data['total_express_fees']) ? 0 : $data['total_express_fees'];
			$value['total_money'] = $value['goods_money'] + $value['goods_express_fee'];
		}

		return $list;
	}
}