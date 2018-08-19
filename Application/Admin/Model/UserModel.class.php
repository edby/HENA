<?php
	namespace Admin\Model;
	class UserModel extends CommonModel{
		//定义字段
		protected $fields = array('id','username','nickname','password','tel','belong','level','is_pay','time','inv_id','renling','order_id','avatar');

		//用户列表
		public function getList(){
            return $this->query("select u.*,l.levelname,nu.realname as inv_name,nu.nickname as inv_nick,nu.tel as inv_tel from hn_user as u left join hn_level as l on u.level = l.id left join hn_user as nu on nu.id=u.inv_id");
            //return $this->alias('u')->field('u.*,l.levelname')->join('__LEVEL__ as l on u.level = l.id')->where('level > 1')->select();
		}
		//删除用户
		public function del($id){
			return $this->where('id='.$id)->delete();
		}
	}