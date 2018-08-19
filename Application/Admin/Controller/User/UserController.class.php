<?php
namespace Admin\Controller\User;

use Admin\Controller\CommonController;
use Think\Page;

class UserController extends CommonController {
    public function index(){
        $levelList = M('Level')->select();
        foreach ($levelList as $k => $level){
            $levelList[$k]['levelname'] = $level['levelname'].'V'.($level['id'] - 1);
        }
        $this->assign('level_list',$levelList);
        /*检索条件*/
        $condition = [];
        $realname = I('get.realname','');
        $tel = I('get.tel','');
        $level = I('get.level','');
        if ($realname){
            $condition['u.realname'] = ['LIKE',$realname.'%'];
        }
        if ($tel){
            $condition['u.tel'] = ['LIKE',$tel.'%'];
        }
        if ($level != 0){
            $condition['u.level'] = $level;
        }
        /*检索条件*/
        $User = M('User'); // 实例化User对象
        $count      = $User->alias('u')->where($condition)->count();// 查询满足要求的总记录数
        $Page       = new Page($count,25);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();// 分页显示输出
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $list = $User
            ->alias('u')->field('u.*,l.levelname,nu.realname as inv_name,nu.nickname as inv_nick,nu.tel as inv_tel')
            ->join('LEFT JOIN hn_level AS l ON u.level = l.id')
            ->join('LEFT JOIN hn_user AS nu ON nu.id=u.inv_id')
            ->where($condition)
            ->order('id')->limit($Page->firstRow.','.$Page->listRows)->select();
        foreach ($list as $k => $v){
            $list[$k]['nickname'] = emoji_decode($v['nickname']);
            $list[$k]['inv_nick'] = emoji_decode($v['inv_nick']);
        }
        foreach ($list as $k => $user){
            $list[$k]['levelname'] = $user['levelname'].'V'.($user['level'] - 1);
        }
        $this->assign('data',$list);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        $this->display(); // 输出模板
    }

    /**
     * 用户信息搜索
     */
    public function searchUser()
    {
        $realname = trim(I('post.realname'));
        $tel = trim(I('post.tel'));
        $User = M('User'); // 实例化User对象
        $list = $User
            ->alias('u')->field('u.*,l.levelname,nu.realname as inv_name,nu.nickname as inv_nick,nu.tel as inv_tel')
            ->join('LEFT JOIN hn_level AS l ON u.level = l.id')
            ->join('LEFT JOIN hn_user AS nu ON nu.id=u.inv_id')
            ->where(['u.realname' => ['LIKE',$realname.'%'],'u.tel' => ['LIKE',$tel.'%']])
            ->order('u.id')->limit('0,25')->select();
        foreach ($list as $k => $v){
            $list[$k]['nickname'] = emoji_decode($v['nickname']);
            $list[$k]['inv_nick'] = emoji_decode($v['inv_nick']);
        }
        $this->ajaxReturn(['status' => 1,'data' => $list]);
    }
    public function level(){
    	$data = M('Level')->where('id>1')->select();
    	$this->assign('data',$data);
    	$this->display();
    }
    public function credit(){
    	$this->display();
    }

    public function update(){
    	if(IS_GET){
    		$id = I('get.id');
    		$info = M('Level')->where('id='.$id)->find();
    		$this->assign('info',$info);
    		$this->display();
    	}else{
    		$data = array(
    			'levelname' => I('post.levelname'),
    			'upgrading' => I('post.upgrading')
    		);
    		$id = I('post.id');
    		$res = M('Level')->where('id='.$id)->save($data);
    		$this->ajaxReturn(array('status'=>1,'msg'=>'修改成功'));
    	}
    }

    public function del(){
    	$id = I('get.id');
    	$res = M('user')->where('id='.$id)->setField('status',9);
    	if($res){
            $this->success('删除成功！',U('index'));
    		return ;
    	}
        $this->error('操作失败!',U('index'));
    }

    public function add(){
        if(IS_GET){
            $levelList = M('Level')->where("`lev_code` = 'LV'")->select();
            $this->assign('level_list',$levelList);
            $this->display();
        }else{
            $level = I('post.level');
            if($level == 0){
                $this->error('请选择用户等级!');
            }
            $nickname = I('post.nickname');
            $tel = trim(I('post.tel'));
            $tel = preg_replace("/\D/",'',$tel);
            if(!preg_match("/^[1][3,4,5,6,7,8,9][0-9]{9}$/",$tel)){
                $this->error('手机号码格式错误,请重新输入!'.$tel);
            }
            $data = array();
            $check = M('User')->where(['tel' => $tel])->find();
            if($check){
                $this->error('该手机号用户已存在，用户昵称为:'.$check['nickname']);
            }
            $inv_tel = I('post.inv_tel');
            if($inv_tel == 0){
                //没有推荐人
                $inv_id = 0;
            }else{
                $invInfo = M('User')->where('tel = '.$inv_tel)->find();
                if (!$invInfo){
                    $this->error('推荐人手机号用户不存在!');
                }
                $inv_id = $invInfo['id'];
            }
            $data['realname'] = $nickname;
            $data['tel'] = $tel;
            $data['inv_id'] = $inv_id;
            $data['level'] = $level;
            $data['regtime'] = time();
            $data['is_pay'] = 1;
            $res = M('User')->add($data);
            if ($res){
                $this->success('用户添加成功!');
            }
        }
    }

    public function getinfo()
    {
        $tel = I('post.user_tel');
        $data = array();
        if (! $tel){
            $data['status'] = 0;
            $data['message'] = '未获取到用户信息!';
            $this->ajaxReturn($data);
        }
        $userInfo = M('User')->where('tel = '.$tel)->find();
        if ($userInfo){
            $data['status'] = 1;
            $data['message'] = $userInfo;
            $this->ajaxReturn($data);
        }else{
            $data['status'] = 0;
            $data['message'] = '未获取到用户信息!';
            $this->ajaxReturn($data);
        }
    }

    //用户信息展示
    public function detail()
    {
        $id = I('get.id');
        $userInfo = M('User')->find($id);
        $invPhone = M('User')->where(['id' => $userInfo['inv_id']])->getField('tel');//推荐人手机号
        $levelList = M('Level')->where(['lev_code' => 'LV'])->select();
        $this->assign('level_list',$levelList);
        $this->assign('inv_phone',$invPhone);
        $this->assign('info',$userInfo);
        $this->display();
    }

    //用户信息修改
    public function editPost()
    {
        $id = I('post.userid');
        $realName = trim(I('post.realname'));
        $tel = trim(I('post.tel'));
        $nickName = trim(I('post.nickname'));

        $level = I('post.level');
        if($level == 0){
            $this->error('请选择用户等级!');
        }
        $inv_tel = I('post.inv_tel');
        if($inv_tel == 0){
            //没有推荐人
            $inv_id = 0;
        }else{
            $invInfo = M('User')->where('tel = '.$inv_tel)->find();
            if (!$invInfo){
                $this->error('推荐人手机号用户不存在!');
            }
            $inv_id = $invInfo['id'];
        }

        $tel = preg_replace("/\D/",'',$tel);
        if(!preg_match("/^[1][3,4,5,6,7,8,9][0-9]{9}$/",$tel)){
            $this->error('手机号码格式错误,请重新输入!'.$tel);
        }
        $data = array(
            'realname' => $realName,
            'tel' => $tel,
            'inv_id' => $inv_id,
            'level' => $level,
            'nickname' => $nickName
        );
        $res = M('User')->where(['id' => $id])->save($data);
        if ($res !== false){
            $this->ajaxReturn(['msg' => '用户信息修改成功','status' => 1]);
        }
        $this->ajaxReturn(['msg' => '用户信息修改失败','status' => 0]);
    }

    /*
     * 获取用户下级信息
     * param user_id
     */
    public function getuserinfo(){

        $id = I('get.id');
        $model = M('user');
        $user = $model->field('realname')->where('id='.$id)->find(); //邀请人
        $lower_level = $model->field('id,realname,tel')->where('inv_id='.$id)->select(); //被邀请人
        foreach($lower_level as $k => $row){
            $lower_level[$k]['lower_user'] = $model->field('id,realname,tel')->where($row['id'].'=inv_id')->select();
        }
        $this->assign('user',$user['realname']);
        $this->assign('lower_level',$lower_level);
        $this->display();
    }

    /**
     * 扣除奖金
     */
    public function delBonus()
    {
        if(IS_POST){
            $id = I('post.userid');
            $money = I('post.money');
            $reason = I('post.reason');
            if(empty($reason)){
                $this->ajaxReturn(['msg' => '请填写原因','status' => 0]);
            }
            $userBonus = M('User')->where('id='.$id)->getField('bonus');
            $nowBonus = $userBonus - $money;
            //启动事务
            M()->startTrans();
            $res = M('User')->where('id='.$id)->setField('bonus',$nowBonus);
            $data = array(
                'user_id' => $id,
                'b_type' => 5,
                'type' => 1,
                'bonus' => $money,
                'money' => $nowBonus,
                'title' => '扣除奖励金',
                'detail' => $reason,
                'created_at' => time()
            );
            $res = $res && M('Bonus_detail')->add($data);
            if ($res){
                // 提交事务
                M()->commit();
                $this->ajaxReturn(['msg' => '奖金扣除成功','status' => 1]);
            }else{
                // 事务回滚
                M()->rollback();
                $this->ajaxReturn(['msg' => '奖金扣除失败','status' => 0]);
            }
        }else{
            $id = I('get.id');
            $userInfo = M('User')->find($id);
            $invPhone = M('User')->where(['id' => $userInfo['inv_id']])->getField('tel');//推荐人手机号
            $levelList = M('Level')->where(['lev_code' => 'LV'])->select();
            $this->assign('level_list',$levelList);
            $this->assign('inv_phone',$invPhone);
            $this->assign('info',$userInfo);
            $this->display();
        }
    }
}