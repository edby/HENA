<?php
namespace Home\Controller;
use Aliyun\Api\Sms\Request\V20170525\SendSmsRequest;
use Aliyun\Core\Config;
use Aliyun\Core\DefaultAcsClient;
use Aliyun\Core\Profile\DefaultProfile;
use Think\Controller;
use Home\Model\ExpGoodsModel;
class ApiController extends Controller {
    //生成二维码
    public function qrcode() {
        $id = I('get.id');
        $model = D('User');
        $res = $model->findOneById($id);
        if (!$res || $res['is_pay'] == 0) {
            $this->error('不好意思，您不是本平台的会员用户，没有权限邀请他人!');
        }
        $url = "http://m.hena360.com/home/user/index/id/" . $id;
        $level = 3;
        $size = 5;
        Vendor('phpqrcode.phpqrcode');
        $errorCorrectionLevel = intval($level); //容错级别
        $matrixPointSize = intval($size); //生成图片大小
        //生成二维码图片
        $object = new \QRcode();
        $object->png($url, false, $errorCorrectionLevel, $matrixPointSize, 2);
    }

    //发送短信
    public function sms() {
        $mobile = I('post.tel');
        $smscode = 'SMS_133270394';
        $code = rand(1000, 9999);
        $param = array('code' => $code);
        $data = array(
            'tel' => $mobile,
            'code' => $code,
            'time' => time(),
            'limit' => 600,
        );
        session('telcode', $data);
        $res = $this->sendSms($mobile, $smscode, $param);
        if ($res['Message'] == 'OK') {
            $this->ajaxReturn(array('status' => 1, 'msg' => '发送成功'));
        } else {
            $this->ajaxReturn(array('status' => 0, 'msg' => $res['Message']));
        }

    }

    /**
     * 微信支付
     */
    public function wxpay()
    {
        $outTradeNo = I('get.order_no');
        $data =  M('Order')->where('wx_sn='.$outTradeNo)->find();
        $payAmount = $data['money'];
//        if(strpos($outTradeNo,'520') === 0){
//            //以520开头，是520大礼包，金额为520元
//            $payAmount = 520;
//            $payAmount = 0.02;
//        }else{
//            $payAmount = 10000;
//        }
        
        include_once "./Public/Wxpay/jsapi.php";
    }

    /**
     * 微信回调
     */
    public function wxnotify() {

        include_once "./Public/Wxpay/notify.php";

        $wxPay = new \WxpayService($mchid, $appid, $apiKey);
        $log = "[微信支付回调开始]:".date('Y-m-d H:i:s')."\r\n";
        $log .= "wxPay:".json_encode($wxPay)."\r\n";

        $result = $wxPay->notify();
        $log .= "result:".json_encode($result)."\r\n";


        if ($result) {
            
            //完成你的逻辑
            //例如连接数据库，获取付款金额$result['cash_fee']，获取订单号$result['out_trade_no']，修改数据库中的订单状态等;
            $order_no = $result['out_trade_no'];
            $time = strtotime($result['time_end']);
            $res =  M('Order')->where(['wx_sn' => $order_no])->find();
            $r = M('Order_goods')->where('order_id='.$res['id'])->find();

            $user = D('user')->getOne(['id' => $res['user_id']]);

            if(empty($res) || empty($r)) {
                $log .= "出错了未查找到订单\r\n";
                exit();
            }

            //判断订单是否是520订单 等于1是520
            if($r['acttype'] == 1){
                $result = $this->handleFive($order_no,$time);
                if(!$result) {
                    $log .= "520处理错误";
                    echo '520处理错误';
                }

            }elseif ($r['acttype'] == 2){
                //2 是0元购
                $data = array(
                    'status' => 2,
                    'pay_at' => $time
                );
                M('Order')->where('wx_sn='.$order_no)->save($data);
            }elseif ($r['acttype'] == 4){
                //4 是爆品库商品
                $result = $this->expProduct($order_no,$time);

                $expGoods = M('ExpGoods')->where("goods_id={$r['goods_id']} and FIND_IN_SET({$user['level']},level)")->find();
                $expModel = D('ExpGoods');
                $result = $result && $expModel->handle($expGoods,$user,$r);

                if(!$result) {
                    $log .= "爆品库处理错误";
                    echo '爆品库处理错误';
                }




            } elseif($r['acttype'] == 5) {
                $result = $this->OrdinGodos($order_no,$time);
                $shareGoods = M('ShareGoods')->where("goods_id={$r['goods_id']}")->find();
                $shareModel = D('ShareGoods');
                $result = $result && $shareModel->handle($shareGoods,$user,$r);
                if(!$result) {
                    $log .= "共享库处理错误";
                    echo '共享库处理错误';
                }
            } else {
                $result = $this->OrdinGodos($order_no,$time);
            }

            $mData = [
                'money' => setCurr($res['money']),
                'goods_name' => $r['goods_name'],
            ];
            $mJson = json_encode($mData);
            $mUrl = 'http://'.$_SERVER['HTTP_HOST'].'/api/app/templete/send?openid='.$user['wx_openid'].'&data='.$mJson.'&type=4&url=http://'.$_SERVER['HTTP_HOST'].U('user/orderDetail').'?orderid='.$res['id'];
            getJson($mUrl);

        } else {
            $log .= "pay error\r\n";
            echo 'pay error';
        }

        $log .= "[回调结束]".date('Y-m-d')."\r\n";

        file_put_contents('./Application/Runtime/Logs/Home/wx_pay_'.date('Y-m-d').'.log', $log, FILE_APPEND);
    }

    /**
     * 爆品库商品支付后的处理
     */
    public function expProduct($order_no,$time)
    {
        $orderModel = M('Order');
        $orderInfo = $orderModel->where('wx_sn='.$order_no)->find();

        $voList = $orderModel->alias("o")
            ->where('o.wx_sn='.$order_no)
            ->join('__ORDER_GOODS__ g ON g.order_id= o.id')
            ->join('__USER__ u ON u.id = o.user_id')
            ->field('o.wx_sn,o.id,o.user_id,g.goods_id,g.total_express_fee,o.created_at,o.money,o.num,u.inv_id')
            ->select();
        
        $data = array(
            'user_id' => $voList[0]['user_id'],
            'order_id' => $voList[0]['id'],
            'num'=> $voList[0]['num'],
            'order_goods_id' => $voList[0]['goods_id'],
            'total_money' => $voList[0]['money'],
            'total_express_fee' => $voList[0]['total_express_fee'],
            'created_at' =>$voList[0]['created_at']
        );

        $orderData = array(
            'status' => 2,
            'pay_at' => $time
        );
        M()->startTrans();

        $res = $orderModel->where('wx_sn='.$order_no)->save($orderData);
       
        if($res) {
            M()->commit();
            
            return true;
        }else{
            M()->rollback();
            return false;
        }
    }

    // 处理普通商品
    public function OrdinGodos($order_no,$time)
    {
        $orderModel = M('Order');
        $orderInfo = $orderModel->where('wx_sn='.$order_no)->find();
        $orderData = array(
            'status' => 2,
            'pay_at' => $time
        );

        return $orderModel->where(array('id' => $orderInfo['id']))->save($orderData);
    }


    //520大礼包支付成功后处理
    public function handleFive($order_no,$time)
    {
        $orderModel = M('Order');
        $orderInfo = $orderModel->where('wx_sn='.$order_no)->find();
        $orderData = array(
            'status' => 2,
            'pay_at' => $time
        );

        M()->startTrans();

        $res = $orderModel->where(array('id' => $orderInfo['id']))->save($orderData);
        if($res){
            //修改普通用户为微咖
            $userInfo = M('User')->where(array('id' => $orderInfo['user_id']))->find();

            if($userInfo['level'] != 1){
                $disRes = $this->distriBonus($userInfo['inv_id'],$orderInfo['order_sn'],$orderInfo['created_at']);
                if($disRes){
                    M()->commit();
                    return true;
                }
                M()->rollback();
                return false;
            }

            $res = M('User')->where(array('id' => $userInfo['id']))->save(array('level' => 2,'is_pay' => 1));
            if(! $res){
                M()->rollback();
                return false;
            }
            $disRes = $this->distriBonus($userInfo['inv_id'],$orderInfo['order_sn'],$orderInfo['created_at']);
            if($disRes){
                M()->commit();
                return true;
            }
            M()->rollback();
            return false;
        }
        M()->rollback();
        return false;
    }

    /**
     * 二级分销，购买520礼包给上级的奖金
     * @param $invId  int 推荐人ID
     * @param $orderno  string 订单号
     * @param $ordertime string 订单时间
     * @return bool 是否成功
     */
    public function distriBonus($invId,$orderno,$ordertime)
    {
        $userModel = M('User');
        $firstInv = $userModel->where(array('id' => $invId))->find();
        $secondInv = $userModel->where(array('id' => $firstInv['inv_id']))->find();
        $bonusData = array();
        $money = $bonus = 0;
        //第一级奖金
        if($firstInv){
            $firstData = $secondData = array();
            switch ($firstInv['level']){
                case 2:
                case 3:
                case 4:
                case 5:
                    $firstData['bonus'] = $firstInv['bonus'] + 150;
                    $money = $firstData['bonus'];
                    $bonus = 150;
                    break;
                case 6:
                case 7:
                case 8:
                    $firstData['bonus'] = $firstInv['bonus'] + 160;
                    $money = $firstData['bonus'];
                    $bonus = 160;
                    break;
                case 9:
                case 10:
                case 11:
                    $firstData['bonus'] = $firstInv['bonus'] + 170;
                    $money = $firstData['bonus'];
                    $bonus = 170;
                    break;
                case 12:
                    $firstData['bonus'] = $firstInv['bonus'] + 180;
                    $money = $firstData['bonus'];
                    $bonus = 180;
                    break;
                case 13:
                    $firstData['bonus'] = $firstInv['bonus'] + 200;
                    $money = $firstData['bonus'];
                    $bonus = 200;
                    break;
                default:
                    $firstData['bonus'] = $firstInv['bonus'] + 0;
                    break;
            }
            //上一级推广人数加1
            $firstData['push_num'] = $firstInv['push_num'] + 1;
            //记录上级的奖励金明细
            if ($bonus != 0){
                $bonusData[] = array(
                    'user_id' => $firstInv['id'],
                    'b_type' => 2,//520
                    'type' => 0,//进账
                    'money' => $money,
                    'bonus' => $bonus,
                    'title' => '推荐520',
                    'source_type' => 1,
                    'detail' => '下级购买520大礼包',
                    'order_sn'=>$orderno,
                    'order_time' => $ordertime,
                    'created_at' => time()
                );
                $mJson = json_encode([
                    'money' => $bonus,
                ]);
                $mUrl = 'http://'.$_SERVER['HTTP_HOST'].'/api/app/templete/send?openid='.$firstInv['wx_openid'].'&data='.$mJson.'&type=2&url=http://'.$_SERVER['HTTP_HOST'].U('Creation/index');
                getJson($mUrl);
                file_put_contents('./log.txt',"\r\n出错了\r\n".$mJson.$mUrl);
            }
            //校验当前人数应该所属的等级
            $newLevel = $this->checkLevel($firstData['push_num']);
            $firstData['level'] = ($newLevel > $firstInv['level']) ? $newLevel: $firstInv['level'];//新等级大于当前等级才更新
            //file_put_contents('./log.txt',"\r\n推广人数\r\n".$firstData['push_num']."\r\n应该所属等级\r\n".$firstData['level']);
            $res1 = $userModel->where(array('id' => $firstInv['id']))->save($firstData);
            if(! $res1){
                file_put_contents('./log.txt',"\r\n出错了\r\n".json_encode($firstData));
                return false;
            }
        }

        $money = $bonus = 0;
        //第二级奖金
        if($secondInv){
            switch ($secondInv['level']){
                case 2:
                    $secondData['bonus'] = $secondInv['bonus'] + 0;
                    break;
                case 3:
                    $secondData['bonus'] = $secondInv['bonus'] + 50;
                    $money = $secondData['bonus'];
                    $bonus = 50;
                    break;
                case 4:
                    $secondData['bonus'] = $secondInv['bonus'] + 60;
                    $money = $secondData['bonus'];
                    $bonus = 60;
                    break;
                case 5:
                    $secondData['bonus'] = $secondInv['bonus'] + 70;
                    $money = $secondData['bonus'];
                    $bonus = 70;
                    break;
                case 6:
                    $secondData['bonus'] = $secondInv['bonus'] + 80;
                    $money = $secondData['bonus'];
                    $bonus = 80;
                    break;
                case 7:
                    $secondData['bonus'] = $secondInv['bonus'] + 90;
                    $money = $secondData['bonus'];
                    $bonus = 90;
                    break;
                case 8:
                    $secondData['bonus'] = $secondInv['bonus'] + 100;
                    $money = $secondData['bonus'];
                    $bonus = 100;
                    break;
                case 9:
                    $secondData['bonus'] = $secondInv['bonus'] + 110;
                    $money = $secondData['bonus'];
                    $bonus = 110;
                    break;
                case 10:
                    $secondData['bonus'] = $secondInv['bonus'] + 120;
                    $money = $secondData['bonus'];
                    $bonus = 120;
                    break;
                case 11:
                    $secondData['bonus'] = $secondInv['bonus'] + 130;
                    $money = $secondData['bonus'];
                    $bonus = 130;
                    break;
                case 12:
                    $secondData['bonus'] = $secondInv['bonus'] + 140;
                    $money = $secondData['bonus'];
                    $bonus = 140;
                    break;
                case 13:
                    $secondData['bonus'] = $secondInv['bonus'] + 150;
                    $money = $secondData['bonus'];
                    $bonus = 150;
                    break;
                default:
                    $secondData['bonus'] = $secondInv['bonus'] + 0;
                    break;
            }
            //记录上级的奖励金明细
            if ($bonus != 0){
                $bonusData[] = array(
                    'user_id' => $secondInv['id'],
                    'b_type' => 2,//520
                    'type' => 0,//进账
                    'money' => $money,
                    'bonus' => $bonus,
                    'title' => '推荐520',
                    'source_type' => 2,
                    'detail' => '下级的下级购买520大礼包',
                    'order_sn'=>$orderno,
                    'order_time' => $ordertime,
                    'created_at' => time()
                );
                $mJson = json_encode([
                    'money' => $bonus,
                ]);
                $mUrl = 'http://'.$_SERVER['HTTP_HOST'].'/api/app/templete/send?openid='.$secondInv['wx_openid'].'&data='.$mJson.'&type=2&url=http://'.$_SERVER['HTTP_HOST'].U('Creation/index');
                getJson($mUrl);
                file_put_contents('./log.txt',"\r\n出错了\r\n".$mJson.$mUrl);
            }
            $res2 = $userModel->where(array('id' => $secondInv['id']))->save($secondData);
            //file_put_contents('./log.txt',"\r\n暂时没出错\r\n".json_encode($bonusData));
            if($res2 === false){
                file_put_contents('./log.txt',"\r\n出错了\r\n".json_encode($secondData));
                return false;
            }
        }
        //奖金明细入库
        //file_put_contents('./log.txt',"\r\n奖金明细\r\n".json_encode($bonusData));
        if ($bonusData){
            $res = M('Bonus_detail')->addAll($bonusData);
            if (! $res){
                return false;
            }
        }
        return true;
    }

    /**
     * 校验当前直推人数应该所属的等级
     * @param $num  直推人数
     * @return number  等级
     */
    public function checkLevel($num)
    {
        if ($num == 0){
            return 1;
        }

        if ($num <= 2 && $num >= 1){
            return 2;
        }

        if ($num <= 5 && $num >= 3){
            return 3;
        }

        if ($num <= 8 && $num >= 6){
            return 4;
        }

        if ($num <= 11 && $num >= 9){
            return 5;
        }

        if ($num <= 14 && $num >= 12){
            return 6;
        }

        if ($num <= 17 && $num >= 15){
            return 7;
        }

        if ($num <= 20 && $num >= 18){
            return 8;
        }

        if ($num <= 23 && $num >= 21){
            return 9;
        }

        if ($num <= 26 && $num >= 24){
            return 10;
        }

        if ($num <= 44 && $num >= 27){
            return 11;
        }

        if ($num <= 80 && $num >= 45){
            return 12;
        }

        if ($num > 80){
            return 13;
        }
    }

    /**
     * 发送短信接口
     * 文档地址 https://help.aliyun.com/document_detail/55491.html?spm=5176.doc55491.6.560.0KUKny
     * @param $mobile   手机号
     * @param $smscode  短信模板ID
     * @param $params   模板替换参数
     * @return mixed    code = OK 表示完成
     */
    public function sendSms($mobile, $smscode, $params) {
        require_once VENDOR_PATH . '/aliyunsms/vendor/autoload.php';
        Config::load();
        $sms_config = C("SMS_CONFIG");
        $templateParam = $params;
        $signName = $sms_config['sign'];
        $templateCode = $smscode;
        $product = "Dysmsapi";
        $domain = "dysmsapi.aliyuncs.com";
        $region = "cn-hangzhou";

        $profile = DefaultProfile::getProfile($region, $sms_config['key'], $sms_config['secret']);
        DefaultProfile::addEndpoint("cn-hangzhou", "cn-hangzhou", $product, $domain);
        $acsClient = new DefaultAcsClient($profile);
        $request = new SendSmsRequest();
        $request->setPhoneNumbers($mobile);
        $request->setSignName($signName);
        $request->setTemplateCode($templateCode);
        if ($templateParam) {
            $request->setTemplateParam(json_encode($templateParam));
        }
        $acsResponse = $acsClient->getAcsResponse($request);
        $result = json_decode(json_encode($acsResponse), true);
        return $result;
    }


}