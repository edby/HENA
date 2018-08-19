<?php
namespace Home\Controller;
use Think\Controller;
class CommonController extends Controller {

	public function _initialize(){

        // 记录用户第一次访问链接的mid
        $mid = isset($_GET['mid']) ? intval($_GET['mid']) :0;
        if(empty(session('mid')) && $mid != 0){
            session('_mid', $mid);
        }
	}


    public function _empty()
    {
        redirect('/');
    }
}