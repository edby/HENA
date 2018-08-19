<?php

/**
 * Created by PhpStorm.
 * User: 杨虎成
 * Date: 2018/5/10
 * Time: 19:39
 */
namespace Admin\Model\Goods;

use Think\Model;
class ReturnCashModel extends Model
{
    protected $tableName = 'return_cash';
    protected $_validate = array(
        array('goods_id','','该商品已经存在活动了！',0,'unique',1),

    );
}