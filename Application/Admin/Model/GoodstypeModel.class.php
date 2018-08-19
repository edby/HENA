<?php
namespace Admin\Model;
use Think\Model;
/**
 * 分类模型
 */
class GoodstypeModel extends Model
{
    //获取商品类型
    public function gettypedata()
    {
       $data = $this->select();
       return $data;
    }




}