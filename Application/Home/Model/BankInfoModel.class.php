<?php
/**
 * Created by PhpStorm.
 * User: 杨虎成
 * Date: 2018/5/14
 * Time: 10:46
 */

namespace Home\Model;


class BankInfoModel extends CommonModel{

    protected $tableName = 'bank_info';

    protected $_validate = [
        ['bank_num','require','请输入银行卡号！'],
        ['bank_id','require','请选择银行！'],
        ['bank_num','16,19', '银行卡号长度错误！', 0, 'length'],
        ['bank_username','require','请输入持卡人姓名！'],
        ['bank_username','checkName','持卡人姓名必须是中文！',0,'callback'],
        ['bank_username','2,5','持卡人姓名长度2-5位！',0,'length'],

    ];
    protected function checkName($str)
    {
        if(preg_match("/[\x{4e00}-\x{9fa5}]+/u", $str, $ch)) {
            if(strlen($str) == strlen($ch[0])) {
                return true;
            }
        }
        return false;
    }
    public function getAccount($uid)
    {
        return $this->getOne(['user_id'=>$uid]);
    }
}
