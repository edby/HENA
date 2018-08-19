<?php
namespace Home\Controller;
use Think\Controller;
class LoginController extends CommonController {

    public function _initialize(){
        parent::_initialize();
        if(!isLogin() && !in_array(ACTION_NAME, ['logOut']) &&
            (CONTROLLER_NAME != 'Goods' && ACTION_NAME != 'details')){
            $callBackUrl = urlencode('http://'.$_SERVER['SERVER_NAME'].$_SERVER["REQUEST_URI"]);
            header('Location:'.C('JUMP_DOMAIN').'/html/view/user/login.html?callBack='.$callBackUrl);
            exit(0);
            
        }


        /*// 验证用户是否登录
        if(!isLogin() && !in_array(ACTION_NAME, ['authCallback', 'login', 'testLogin'])) {

            $callBackUrl = urlencode(__SELF__);
            // 判断浏览器类型
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
            //MicroMessenger 是android/iphone版微信所带的
            //Windows Phone 是winphone版微信带的  (这个标识会误伤winphone普通浏览器的访问)
            if(strpos($userAgent, 'MicroMessenger') == false && strpos($userAgent, 'Windows Phone') == false){ //非微信用户

                header('Location:'.U('login/login').'?CallbackUrl='.$callBackUrl);
                exit(0);
            }else{
                header('Location:'.U('user/index'));
            }
            
            // 微信用户
            $wechat = C('WECHAT');
            $appid = $wechat['appid'];
            $secret = $wechat['appKey'];
            $redirect_uri = urlencode ('http://'.$_SERVER['HTTP_HOST'].'/Login/authCallback?mid='.session('mid').'&CallbackUrl='.$callBackUrl);
            $url ="https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$redirect_uri}&response_type=code&scope=snsapi_userinfo&state=1&connect_redirect=1#wechat_redirect";
            header("Location:".$url);
               exit(0);
        } else {
            $userId = getUserId();
            $user = D('user')->getOne(['id' => $userId]);
            if($user['status'] == 9){
                $this->error('改账号被冻结，请联系推荐人', '/');
            }

        }

    }

    // 授权回调地址
    public function authCallback($code)
    {
        $wechat = C('WECHAT');
        $appid = $wechat['appid'];
        $secret = $wechat['appKey'];

        // 获取用户授权信息
        $authInfo = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$appid}&secret={$secret}&code={$code}&grant_type=authorization_code";
        $authInfo = $this->getJson($authInfo);
       
        //第二步:根据全局access_token和openid查询用户信息
        $access_token = $authInfo["access_token"];
        $openid = $authInfo['openid'];
        if(!$openid){
            $this->error('授权失败，稍后请重试!');
        }

        // 根据用户oenid查看用户是否存在
        $user = D('User')->getOne(['wx_openid' => $openid]);
        
        // 存在则直接登录
        if($user){
            $callBackUrl = urldecode(I('get.CallbackUrl'));
            session('userId', $user['id']);
            session('status', $user['status']);
            header("Location:".'http://'.$_SERVER['HTTP_HOST'].$callBackUrl);
            exit(0);
        }

        // 存储openid 进行注册
        session('wx_openid', $openid);

        // 获取用户微信信息
        $getWxInfourl = "https://api.weixin.qq.com/sns/userinfo?access_token={$access_token}&openid={$openid}&lang=zh_CN";
        $wxUserInfo = $this->getJson($getWxInfourl);
        session('userInfo', $wxUserInfo);
        header('Location:'.U('/login/login')); 
           exit(0);*/
    }
 
     // 手机号注册
     public function login()
     {
        if(isLogin()){
            header('Location:'.U('user/index'));
            exit(0);
        }

         if(IS_GET){
             $this->display('/User/tel');
             exit(0);
         }

         $tel = I('post.tel');
        $telCode = I('post.telcode');
        
        //获取当前session中具体的信息
        $telData = session('telcode');
        
        //判断验证码是否过期
        if($telData['time']+$telData['limit'] < time()){
            $this->ajaxReturn(array('errCode'=>1,'errMsg'=>'手机验证码无效'));
        }
        
        if($telData['tel'] != $tel){
            $this->ajaxReturn(array('errCode'=>1,'errMsg'=>'手机号不匹配'));
        }
        
        if($telData['code'] != $telCode){
            $this->ajaxReturn(array('errCode'=>1,'errMsg'=>'手机验证码错误'));
        }

        if($wxUserInfo = session('userInfo')){
            $uData['wx_openid'] = $wxUserInfo['openid'];
            $uData['nickname'] = emoji_encode($wxUserInfo['nickname']);
            $uData['gender'] = $wxUserInfo['sex'];
            $uData['avatar'] = $wxUserInfo['headimgurl'];
            $uData['province'] = $wxUserInfo['province'];
            $uData['city'] = $wxUserInfo['city'];
            $uData['lasttime'] = time();
        }

        // 验证手机号是否存在
        $userInfo =  D('user')->getOne(['tel' => $tel]);
        if($userInfo){
            // 获取微信数据
            if(isset($uData)){
                D('user')->updateData(['id' => $userInfo['id']], $uData);
            }

            session('userId', $userInfo['id']);
            session('status', $userInfo['status']);
            $callBackUrl = urldecode(I('get.CallbackUrl'));
            // 清空验证码
            session('telcode',null);
            $this->ajaxReturn(array('errCode' => 0,'errMsg'=>'登录成功','url' => $callBackUrl));
        }
           
        $uData['inv_id'] = session('mid');
        $uData['level'] = 1;
        $uData['tel'] = $tel;
        $uData['regtime'] = $uData['lasttime'] =time();
        $result = D('user')->createData($uData);
        if($result){
            session('userId', $result);
            session('status', 1);
            // 模板消息
            if(isset($uData['wx_openid'])) {
                $recNickName = '系统推荐';
                if($uData['inv_id'] != 0) {

                    $recUser = D('user')->getOne(['id' => $uData['inv_id']]);
                    $recNickName = $recUser['nickname'];

                    $mData = [
                        'nickname' => $recNickName,
                        'b_nickname' => $uData['nickname'],
                    ];

                    $mJson = json_encode($mData);
                    $mUrl = 'http://'.$_SERVER['HTTP_HOST'].'/api/app/templete/send?openid='.$recUser['wx_openid'].'&data='.$mJson.'&type=1&url=http://'.$_SERVER['HTTP_HOST'].U('User/fans');
                    $this->getJson($mUrl);
                }
                $mData = [
                    'tel' => $tel,
                    'time' => date('Y-m-d H:i:s'),
                    'nickname' => $recNickName, // 推荐人昵称
                ];
                $mJson = json_encode($mData);
                $mUrl = 'http://'.$_SERVER['HTTP_HOST'].'/api/app/templete/send?openid='.$uData['wx_openid'].'&data='.$mJson.'&type=5&url=http://'.$_SERVER['HTTP_HOST'];
                $this->getJson($mUrl);

            }
            $this->ajaxReturn(array('errCode' => 0,'errMsg'=>'登录成功!'));
        } else {
            $this->ajaxReturn(array('errCode' => 1,'errMsg'=>'登陆失败!'));
        }
     }

     // 退出登录
     public function logOut()
    {
        session('userId', null);
        session(null);

        redirect('/');
    }

    protected function getJson($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($output, true);
    }


}