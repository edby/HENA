<?php

namespace Admin\Model;
use Think\Model;
class CategoryModel extends Model{


    /*
     * 获取数据列表
     * return array()
     */
    public function getlist($status=0){

        $data = $this->order('create_time desc')->select();

        return (($status ==1 ) ? $this->getTree($data) : $data);

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
                $this->getTree($list,$v['cat_id'],$level + 1);
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