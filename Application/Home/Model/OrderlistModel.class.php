<?php
namespace Home\Model;
use Think\Model\RelationModel;

class OrderlistModel extends RelationModel{

    protected $tableName = 'order';
    protected $tablePrefix = 'hn_';
    protected $_link = array(
        'order_goods'=>array(
            'mapping_type'=>self::HAS_ONE,
            'class_name' =>  'order_goods',
            'mapping_name '=> 'b',
            'foreign_key'  =>  'order_id'
        )

    );


    /*
     * 获取订单列表
     * param  用户ID
     * return  array
     **/
    public function orderlist($userId){

        header('content-type:text/html;charset=utf-8');
        $data = $this->where('user_id='.$userId)->order('created_at DESC')->relation('order_goods')->select();
        $waitpaymoney = array();//待付款
        $waitsendgoods = array();//待发货
        $readysendgoods = array();//已发货
        $readygetgoods = array();//已收货
        for($i=0;$i<$len=count($data);$i++){
            if($data[$i]['status'] == 1){
                $waitpaymoney[] = $data[$i];
            }elseif($data[$i]['status'] == 2){
                $waitsendgoods[] = $data[$i];
            }elseif($data[$i]['status'] == 3){
                $readysendgoods[] = $data[$i];
            }else{
                $readygetgoods[] = $data[$i];
            }
        }
        $listdata = array($data,$waitpaymoney,$waitsendgoods,$readysendgoods,$readygetgoods);
        return $listdata;
    }







}