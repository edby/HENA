<?php
namespace Admin\Model;
use Think\Model;
/**
 * 分类模型
 */
class ShopingModel extends Model
{

    protected $trueTableName = 'hn_shoping';
    protected $tablePrefix = 'hn_';
    //获取数据列表
    public function listData($status)
    {
        $pagesize = "9999999999999999";
        //$where =  array('status'=>1);
        if($status == 6){
            $list = $this->page($p,$pagesize)->where('goods_num=0')->order('add_time DESC')->select();
        }else{
            $list = $this->page($p,$pagesize)->where('status='.$status)->order('add_time DESC')->select();
        }
        return array('pageStr'=>$show,'list'=>$list);
    }




}