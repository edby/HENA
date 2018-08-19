<?php
/**
 * Created by PhpStorm.
 * User: dingjuru
 * Date: 2018/5/9
 * Time: 14:25
 * 商品规格控制器 
 */
namespace Admin\Controller\Goods;
use Think\Controller;

class GoodsSpecController extends Controller
{
    /*
     *商品列表
     */
    function index()
    {
        // 分页获取商品列表
        $this->display();
    }

    /*
     *商品添加
     */
    function add()
    {
        //获取商品信息
        $goodsId = intval($_GET['id']);
        $goods = D('Goods')->getOne(['id' => $goodsId]);

        // 根据商品分类获取规格数据
        $spec = D('Spec')->getAll(['c_id' => $goods['cate_id']]);

        // 获取商品属性规格
        $goodsSpec = D('GoodsSpec')->getAll(['goods_id' => $goodsId]);
        $this->assign('goodsSpec',$goodsSpec);
        $this->assign('goods',$goods);
        $this->assign('spec',$spec);
        
        $this->display();
    }

    /**
     * 保存数据
     */
    public function save()
    {

        $condition['goods_spec_id'] = intval($_POST['goods_spec_id']);
        $data['goods_id'] = intval($_POST['goods_id']);
        $data['spec_id'] = intval($_POST['spec_id']);
        $data['spec_title'] = $_POST['spec_name'];
        $data['addr_id'] = intval($_POST['attr_id']);
        $data['addr_title'] = $_POST['attr_name'];
        $data['incre_price'] = $_POST['incre_price'];
        $data['goods_num'] = intval($_POST['goods_num']);

        // 验证数据
        if(empty($data['goods_id']) || empty($data['spec_id']) || empty($data['addr_id'])){
            return $this->ajaxReturn(['code' => 0, 'msg' => '参数错误!'], 'json');
        }

        
        if($condition['goods_spec_id'] == 0){ // 添加
            $data['create_time'] = $data['update_time'] = time();
            $result = D('GoodsSpec')->createData($data);
            $opt = '添加';
            $goods_spec_id = $result;
        } else {
            $data['update_time'] = time();
            $result = D('GoodsSpec')->updateData($condition, $data);
            $opt = '编辑';
            $goods_spec_id = $condition['goods_spec_id'];
        }

        if($result){
            return $this->ajaxReturn(['code' => 1, 'msg' => $opt.'成功!', 'goods_spec_id' => $goods_spec_id], 'json');
        } else {
            return $this->ajaxReturn(['code' => 0, 'msg' => $opt.'失败!'], 'json');
        }
    }

    /**
     * 获取规格的属性
     */
    public function getSpecAttr($id)
    {       
       $data = D('Addr')->getAll(['s_id' => $id]);  

       return $this->ajaxReturn($data, 'json');
    }

    /**
     * 设置商品规格属性
     */
    function setSataus()
    {
        $goods_spec_id = intval($_POST['goods_spec_id']);
        $status = D('GoodsSpec')->getOneField(['goods_spec_id' => $goods_spec_id],'status');
        $data['status'] = 1 - $status;
        $result = D('GoodsSpec')->updateData(['goods_spec_id' => $goods_spec_id], $data);
        if($result) {
            return $this->ajaxReturn(['code' => 1, 'msg' => '操作成功!', 'status' => $data['status']], 'json');
        } else {
            return $this->ajaxReturn(['code' => 0, 'msg' => '操作成功!', 'status' => $status], 'json');
        }
    }

   


}