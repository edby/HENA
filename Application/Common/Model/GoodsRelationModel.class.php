<?php
/**
 * Created by PhpStorm.
 * User: 杨虎成
 * Date: 2018/5/10
 * Time: 15:50
 */

namespace Common\Model;

use Think\Model\RelationModel;

class GoodsRelationModel extends RelationModel
{
    protected $tableName = 'goods';
    protected $_link = [
        'return_cash' => [
            'mapping_type' => self::HAS_ONE,
            'class_name' => 'ReturnCash',
            'mapping_name' => 'return_cash',
            'foreign_key'  =>  'goods_id',
        ],

        'goods_type' => [
            'mapping_type' => self::HAS_MANY,
            'class_name' => 'GoodsType',
            'mapping_name' => 'goods_type',
            'foreign_key'  =>  'goods_id',
        ],
        // 商品规格
        'goods_spec' => [
            'mapping_type' => self::HAS_MANY,
            'class_name' => 'GoodsSpec',
            'mapping_name' => 'goods_spec',
            'foreign_key'  =>  'goods_id',
        ],

        'category' => [
            'mapping_type' => self::BELONGS_TO,
            'class_name' => 'Category',
            'foreign_key'   => 'cate_id',
            'relation_foreign_key'  => 'cat_id',
        ],

        'activity' => [
            'mapping_type' => self::HAS_ONE,
            'class_name' => 'Activity',
            'mapping_name' => 'activity',
            'foreign_key'  =>  'goods_id',
        ],
        //爆品库
        'exp_config' => [
            'mapping_type' => self::HAS_MANY,
            'class_name' => 'ExpConfig',
            'mapping_name' => 'exp_config',
            'foreign_key' => 'goods_id'
        ],

    ];
}