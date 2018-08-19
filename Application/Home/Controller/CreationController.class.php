<?php
/**
 * 创客中心
 * User: WJH
 * Date: 2018/5/5
 * Time: 18:06
 */
namespace Home\Controller;
class CreationController extends LoginController
{
    public function index()
    {
        $userinfo = D('User')->getUserInfo(getUserId());
        if ($userinfo['level'] == 1){
           
            header('Location:'.U('/Index/giftbag'));
        }
        $userinfo['nickname'] = emoji_decode($userinfo['nickname']);
        $invInfo = D('User')->where(['id' => $userinfo['inv_id']])->find();
        $invInfo['nickname'] = emoji_decode($invInfo['nickname']);
        $this->assign('info',$userinfo);
        $this->assign('inv_info',$invInfo);
        $this->display();
    }
}