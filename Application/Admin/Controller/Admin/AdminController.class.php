<?php
namespace Admin\Controller\Admin;

use Think\Controller;

class AdminController extends Controller{
    public function login()
    {
        if(IS_POST){
            $adminName = I('post.username');
            $passWord = I('post.password');

            $adminInfo = M('Admin')->where(['username'=>$adminName])->find();

            if(!$adminInfo){
                $this->error('账户或密码错误1');
            }

            if(!password_verify($passWord, $adminInfo['password_hash'])) {
                $this->error('账户或密码错误2');
            }

            session('admin',$adminInfo);
            $this->ajaxReturn(array('status' => 1));
        }
        $this->display();
    }

    public function logout()
    {
        session('admin',null);
        $this->success('已成功退出后台!',U('Admin\Admin/Admin/login'));
    }


}