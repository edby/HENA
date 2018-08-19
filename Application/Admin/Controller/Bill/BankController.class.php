<?php
namespace Admin\Controller\bill;

use Admin\Controller\CommonController;
use Think\Page;
use Vendor\Weixin\WechatPayBank;



class BankController extends CommonController
{

    /**
     * 展示用户提现列表
     */
    public function index()
    {
        $where = '';
        $is_status = I('get.is_status');
        if(empty($is_status)){
            //表示表单提交没有条件
            $where .= "";
        }elseif($is_status == 1){
            // 未审核
            $where .= " AND b.status = 0";
        }elseif($is_status == 2){
            // 已审核
            $where .= " AND b.status = 1";
        }elseif($is_status == 3){
            // 已驳回
            $where .= " AND b.status = 9";
        }

        $tel = I('get.tel');
        if(empty($tel)){
            //表示表单提交没有条件
            $where .= "";
        }else{
            $where .= " AND u.tel like '%$tel%'";
        }

        $where = trim($where,' AND');

        $Bonus = M('Withdraw_bonus');
        $count = $Bonus
            ->alias('b')
            ->field('b.*,u.tel,u.nickname,u.realname,u.level,n.*')
            ->join('LEFT JOIN __USER__ AS u ON b.user_id = u.id')
            ->join('LEFT JOIN __BANK_NAME__ AS n ON b.bank_id = n.id')
            ->where($where)
            ->count();
        $Page = new Page($count, 15);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show = $Page->show();// 分页显示输出
        $list = $Bonus
            ->alias('b')
            ->field('b.*,u.tel,u.nickname,u.realname,u.level,n.*')
            ->join('LEFT JOIN __USER__ AS u ON b.user_id = u.id')
            ->join('LEFT JOIN __BANK_NAME__ AS n ON b.bank_id = n.id')
            ->where($where)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('b.created_at desc')
            ->select();

        // 获取等级列表
        $Level = D('Level')->getField('id,levelname');
        
        $this->assign('level', $Level);// 赋值数据集
        $this->assign('data', $list);// 赋值数据集
        $this->assign('page', $show);// 赋值分页输出
        $this->display(); // 输出模板
    }


    /**
     * 审核驳回处理
     */
    public function dismiss()
    {
        $id = I('get.id'); //提现id
        $reason = I('get.reason'); //失败原因
        $bonusinfo = M('Withdraw_bonus')->where('withdraw_bonus_id=' . $id)->find();
        $missmoney = $bonusinfo['withdraw_money'] + $bonusinfo['service_charge']; // 手续费+提现金额
        M()->startTrans();
            $User = M('User');
            $Userinfo = $User->where('id=' . $bonusinfo['user_id'])->find(); //获取用户信息
            $bonus = $Userinfo['bonus'] + $missmoney; //返回用户奖励余额
            $data['bonus'] = $bonus;
            $res = $User->where('id=' . $Userinfo['id'])->save($data); // 根据条件保存修改的数据
            $info['status'] = 9;
            $info['reason'] = $reason;
            $info['updated_at'] = time();
            $res = $res && M('Withdraw_bonus')->where('withdraw_bonus_id=' . $id)->save($info);
            $logs = array(
                'withdraw_bonus_id' => $id,
                'description' =>  '管理员驳回审核成功,提现金额：'.$bonusinfo['withdraw_money'].'，提现人卡号：'.$bonusinfo['bank_num'].'，提现人姓名：'.$bonusinfo['bank_username'].'，驳回原因：'.$reason,
                'created_at' => time()
            );
            $res = $res && M('Withdraw_bonus_log')->add($logs);
        if ($res === true) {
            // 提交事务
            M()->commit();
            $this->success('已驳回该提现申请');
        } else {
            // 事务回滚
            M()->rollback();
            $this->error('驳回该提现申请失败');
        }
    }

    public function check()
    {
        $config = C('WECHAT');
        vendor('Weixin.WeChatPayBank');
        $model = new WechatPayBank($config);
        $id = $_GET['id'];
        $withdraw = M('Withdraw_bonus')->where(['withdraw_bonus_id' => $id,'status'=>0])->find();
        if(empty($withdraw)) {
            $this->ajaxReturn(['code'=>3000,'msg'=>'提现信息不存在']);
        }

        $result = $model->paybank(trim($withdraw['bank_num']),$withdraw['bank_username'],$withdraw['bank_id'],$withdraw['withdraw_money'],$withdraw['withdraw_sn']);
        if($result['return_code'] === 'SUCCESS' && $result['result_code'] === 'SUCCESS') {
            $log = [
                'withdraw_bonus_id' => $withdraw['withdraw_bonus_id'],
                'description' => '管理员审核提现成功,提现金额：'.$withdraw['withdraw_money'].'提现人卡号：'.$withdraw['bank_num'].'提现人姓名：'.$withdraw['bank_username'].'----'.json_encode($result),
                'created_at' =>time(),

            ];

            $totalMoney = ($withdraw['withdraw_money']+$withdraw['service_charge']);
            $user =  M('user')->where(['id'=>$withdraw['user_id']])->find();
            M()->startTrans();
            $isSave = M('Withdraw_bonus')->where(['withdraw_bonus_id' => $id,'status'=>0])->setField(['status'=>1,'updated_at'=>time()]);
            $isSave = $isSave && M('user')->where(['id'=>$withdraw['user_id']])->setInc('withdraw_bonus',$totalMoney);
            $isSave = $isSave && M('withdraw_bonus_log')->data($log)->add();
            if($isSave) {
                M()->commit();
                $this->ajaxReturn(['code'=>2000,'msg'=>'审核成功，放款成功']);
            }else{
                M()->rollback();
                $this->ajaxReturn(['code'=>3000,'msg'=>'审核失败，放款成功']);
            }

        }else{
            $log = [
                'withdraw_bonus_id' => $withdraw['withdraw_bonus_id'],
                'description' => '管理员审核提现失败,提现金额：'.$withdraw['withdraw_money'].'提现人卡号：'.$withdraw['bank_num'].'提现人姓名：'.$withdraw['bank_username'].'失败原因：'.$result['return_msg'].','.$result['err_code_des'].'----'.json_encode($result),
                'created_at' =>time(),

            ];

            $isSave = M('withdraw_bonus_log')->data($log)->add();
            if($isSave) {
                $this->ajaxReturn(['code'=>2000,'msg'=>$result['return_msg'].$result['err_code_des']]);
            }else{
                $this->ajaxReturn(['code'=>3000,'msg'=>$result['return_msg'].$result['err_code_des']]);
            }

        }

    }

}