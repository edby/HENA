<?php
/**
 * Created by PhpStorm.
 * User: 杨虎成
 * Date: 2018/5/11
 * Time: 9:43
 */

namespace Admin\Model\Goods;

use Think\Model;
class ActivityModel extends Model
{
    protected $tableName = 'activity';
    protected $_validate = array(
        array('goods_id','','该商品已经存在活动了！',0,'unique',1),

    );
}