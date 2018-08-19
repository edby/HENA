<?php
namespace Home\Model;
use Think\Model\RelationModel;

class OrderdetailModel extends RelationModel{

    protected $tableName = 'order';
    protected $tablePrefix = 'hn_';
    protected $_link = array(
        'order_goods'=>array(
            'mapping_type'=>self::HAS_MANY,
            'class_name' =>  'order_goods',
            'mapping_name '=> 'b',
            'foreign_key'  =>  'order_id'
        ),
        'order_addr'=>array(
            'mapping_type'=>self::HAS_ONE,
            'class_name' =>  'order_addr',
            'mapping_name '=> 'c',
            'foreign_key'  =>  'order_id'
        )

    );


    /*
     * 获取订单详情数据
     * param  订单ID
     * return  array
     **/
    public function getdata($orderid){

        header('content-type:text/html;charset=utf-8');
        $data = $this->where('id='.$orderid)->order('created_at DESC')->relation(array('order_goods','order_addr'))->select();
        return $data;

    }







}