<?php
namespace Admin\Controller;
use Think\Controller;

class CommonController extends Controller {
    public function __construct(){
    	parent::__construct();
    }
    /*
    * 自动执行
    */
    public function _initialize(){
        $adminInfo = session('admin');
        if(! $adminInfo){
            $this->error('账号未登录或已失效!',U('Admin/Admin/Admin/login'));
        }
    }
}