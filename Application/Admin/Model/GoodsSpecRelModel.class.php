<?php
namespace Admin\Model\Goods;
use Think\Model\RelationModel;

class GoodsSpecRelModel extends RelationModel{

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
     * param  用户ID
     * return  array
     **/
    public function getdata()
    {
        $count = $this->count();
        $Page       = new \Think\Page($count,12);
        $show      = $Page->show();
        $data = $this->order('created_at DESC')->relation(array('order_goods','order_addr'))->limit($Page->firstRow.','.$Page->listRows)->select();
        $listdata['show'] = $show;
        $listdata['data'] = $data;
        return $listdata;

    }







}