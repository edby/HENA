<?php

namespace Common\Model;
use Think\Model;
class CategoryModel extends CommonModel {

    // 数据表名
    protected $tableName = 'category';
    
    /**
     * 获取直接下级数据
     */
    public function getLow($pid)
    {
        return $this->getAll(['cat_pid' =>$pid]);
    }

     /**
      * 递归获取数据 
      */
     public function getTreeRec($list, $pid = 0)
     {

        $tree = [];
        foreach ($list as $key => $value) {
            if($value['cat_pid'] == $pid){
                $value['low'] = $this->getTreeRec($list, $value['cat_id']);
                $tree[] = $value;
            }
        }
        return $tree;
     }
    
    /*
     * 递归无线级分类处理
     * return array()
     */
    public function getTree($list,$pid = 0,$level = 0)
    {
        static $tree = array();
        foreach ($list as $v){
            if ($v['cat_pid'] == $pid){
                $v['level'] = $level;
                $tree[] = $v;
                $tree[] = $this->getTree($list,$v['cat_id'],$level + 1);
            }
        }
        return $tree;
    }

    /*
     * 添加商品分类
     * param array()
     * return int
     */
    public function addcategory($data){

        return $this->data($data)->add();

    }

    /*
     * 获取商品分类一条数据
     * param cat_id
     * return array
     */
    public function getdata($id){

        return $this->where('cat_id='.$id)->find();

    }


    /*
     * 编辑商品分类
     * param array() id
     * return int
     */
    public function editcategory($data,$id){

        return $this->where('cat_id='.$id)->data($data)->save();

    }








}