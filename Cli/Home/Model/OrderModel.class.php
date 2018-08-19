<?php
namespace Home\Model;
/**
 * Created by PhpStorm.
 * User: 杨虎成
 * Date: 2018/5/5
 * Time: 16:15
 */
use Think\Model\RelationModel;

class OrderModel extends RelationModel
{
    // 数据表名
    protected $tableName = 'order';

    protected $_link = [
        'order_goods' => [
            'mapping_type' => self::HAS_ONE,
            'class_name' => 'OrderGoods',
            'mapping_name' => 'g',
            'foreign_key'  =>  'order_id'
        ],
    ];
}