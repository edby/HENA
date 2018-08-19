<?php
namespace Home\Model;

use Think\Model\RelationModel;

class OrderRelationModel extends RelationModel
{
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