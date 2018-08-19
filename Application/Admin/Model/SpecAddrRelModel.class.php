<?php
/**
 * User: WJH
 * Date: 2018/5/9
 * Time: 16:58
 */
namespace Admin\Model;
use Think\Model\RelationModel;

class SpecAddrRelModel extends RelationModel{

    protected $tableName = 'spec';
    protected $tablePrefix = 'hn_';
    protected $_link = array(
        'spec_addr'=>array(
            'mapping_type'=>self::HAS_MANY,
            'class_name' =>  'addr',
            'mapping_name '=> 'b',
            'foreign_key'  =>  's_id'
        ),
    );


    /**
     * 获取分类的规格列表
     * @param $cId
     * @return mixed
     */
    public function getdata($cId)
    {
        $data = $this->order('create_time DESC')->relation(array('spec_addr'))->where(['c_id' => $cId])->select();
        return $data;
    }







}