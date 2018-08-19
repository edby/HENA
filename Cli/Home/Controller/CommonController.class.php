<?php
/**
 * Created by PhpStorm.
 * User: 杨虎成
 * Date: 2018/5/7
 * Time: 11:53
 */

namespace Home\Controller;

use Think\Controller;

class CommonController extends Controller
{
    public function _initialize(){
        $key = $_GET['secret_Key'];
        if($key != C('secret_Key')) {
            file_put_contents(dirname(dirname(dirname(__FILE__))).'/Runtime/logs/error.log', '['.date('Y-m-d H:i:s', time()).'] 非法访问!'."\r\n\r\n", FILE_APPEND);
            exit();
        }
    }
}