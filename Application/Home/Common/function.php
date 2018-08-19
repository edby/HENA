<?php
/**
 * 验证用户是否登录
 * @return true | false 登录 or 未登录
 */
function isLogin()
{
    return session('userId') != null ? true : false;
} 

/**
 * 获取用户id
 * @return int id
 */
function getUserId()
{
    return session('userId');
}


 /**
 * 生成签名
 * @param $checkStr string 要签名的字符串
 * @param $salt string 盐值
 * @param $time int 签名时间戳
 * @return string
 */
function setSign($signStr, $salt, $time)
{
    $str = $signStr.$salt.$time;
    return md5($str);
}

/**
 * 验证签名
 * @param $sign string 签名
 * @param $signStr string 要签名的字符串
 * @param $salt string 盐值
 * @param $time int 签名时间戳
 * @param $expires int 过期时间
 */
function validSign($sign, $signStr, $salt, $time, $expires = 30)
{
    // 验证过期时间
    if(time() - $time > $expires){
        return false;
    }

    if($sign != setSign($signStr, $salt, $time)){
        return false;
    }

    return true;
}