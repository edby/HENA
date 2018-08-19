<?php
namespace Home\Model;
class UserModel extends CommonModel{
	//定义字段
	protected $fields = array('id','username','nickname','realname','wx_openid','gender','password','tel','belong','level','is_pay','regtime','lasttime','inv_id','renling','order_no','avatar');

	/**
	 * 获取用户信息
	 */
    public function getUserInfo($userId){
       
        $data = $this->getOne(['id' => $userId]);
        $data['levelname'] = D('level')->getOneField(['id' => $data['level']], 'levelname');
        return $data;
    }


    //用户普通浏览器注册
	public function reg($data){
		return $this->add($data);
	}

	public function getList($id){
//		$data = $this->select();
		//获取第一级下级（大盟友）\
        $firsts = $this->where(['inv_id' => $id])->select();
        $secondInvIds = array();
        foreach ($firsts as $k => $v){
            //一级下级昵称解码
            $firsts[$k]['nickname'] = emoji_decode($v['nickname']);
            $secondInvIds[] = $v['id'];
        }
        if(empty($secondInvIds)){
            $seconds = [];
        }else{
            $seconds = $this->where(['inv_id' => ['IN',$secondInvIds]])->select();
            foreach ($seconds as $k => $v){
                $seconds[$k]['nickname'] = emoji_decode($v['nickname']);
            }
        }
		//格式化数据
		/*$list = $this->getTree($data,$id);
		$count = count($list);
		foreach ($list as $key => $value) {
			if($value['lev'] == 1){
				$res['first'][] = $value;
			}else{
				$res['second'][] = $value;
			}
		}
		$first = count($res['first']);
		$second = count($res['second']);*/
		$first = count($firsts);
		$second = count($seconds);
		$count = $first + $second;
		return array('fans_first'=>$firsts,'fans_second'=>$seconds,'sum'=>$count,'big'=>$first,'small'=>$second);
	}

	public function getTree($data,$id,$lev=1){
		static $list = array();
		foreach ($data as $key => $value) {
			if($value['inv_id'] == $id){
				$value['lev'] = $lev;
				$list[] = $value;
				//使用递归方式依次获取数据
				// $this->getTree($data,$value['id'],$lev+1);
				foreach($data as $k => $v){
					if($v['inv_id'] == $value['id']){
						$v['lev'] = $lev+1;
						$list[] = $v;
					}
				}
			}
		}
		return $list;
	}

    public function getUserInfoById($id)
    {
        return $this->where(array('id' => $id))->find();
	}
}