<?php

namespace Admin\Controller\Order;

/**
 * 订单列表 order_goods
 * Class OrderController
 * @package Admin\Controller
 */
use Admin\Controller\CommonController;
use Think\Page;

class OrderGoodsController extends CommonController
{
    public function index()
    {
        $where = '';
        $is_type = I('get.is_type');
        if($is_type==1){
            $where .= " AND d.status =1";
        }elseif ($is_type==2) {
            //表示表单提交2的是待发货
            $where .= " AND d.status =2";
        }elseif($is_type==3){
            //表示表单提交3的是已发货
            $where .= " AND d.status =3";
        }elseif($is_type==4){
            //表示表单提交4的是已收货
            $where .= " AND d.status =4";
        }elseif($is_type==9){
            //表示表单提交9的是已取消
            $where .= " AND d.status =9";
        }elseif($is_type==10){
            //表示表单提交9的是已删除
            $where .= " AND d.status = 10";
        }elseif(empty($is_type)){
            //表示表单提交的是全部
            $where .= "";
        }

        $usertel = I('get.usertel');
        if(empty($usertel)){
            //表示表单提交没有条件
            $where .= "";
        }else{
            $where .= " AND a.phone like '%$usertel%'";
        }

        $ordertel = I('get.ordertel');
        if(empty($ordertel)){
            //表示表单提交没有条件
            $where .= "";
        }else{
            $where .= " AND u.tel like '%$ordertel%'";
        }

        $start_time = I('get.start_time');
        $end_time = I('get.end_time');

        if(!empty($start_time) && !empty($end_time)) {
            $start_unix = strtotime($start_time.' 00:00:00');
            $end_unix = strtotime($end_time.' 23:59:59');
            $where .= ' AND d.created_at BETWEEN '.$start_unix.' AND '.$end_unix;
        }


        $where = trim($where,' AND');
//        if($tel){
            $order_goods = M('order_goods'); // 实例化order_goods对象
            $count = $order_goods->alias('o')
                ->join('LEFT JOIN __ORDER_ADDR__ AS a ON o.order_id = a.order_id')
                ->join('LEFT JOIN __ORDER__ AS d ON o.order_id = d.id')
                ->join('LEFT JOIN __USER__ AS u ON o.user_id = u.id')
                ->where($where)
                ->count();
            $Page  = new Page($count,15);// 实例化分页类 传入总记录数和每页显示的记录数(15)
            $show  = $Page->show();// 分页显示输出
            // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
            $list = $order_goods->alias('o')
                ->distinct(true)
                ->field('o.*,a.name,a.phone,d.status,u.nickname,u.realname,u.tel')
                ->join('LEFT JOIN __ORDER_ADDR__ AS a ON o.order_id = a.order_id')
                ->join('LEFT JOIN __ORDER__ AS d ON o.order_id = d.id')
                ->join('LEFT JOIN __USER__ AS u ON o.user_id = u.id')
                ->order('id desc')
                ->where($where)
                ->limit($Page->firstRow.','.$Page->listRows)
                ->select();
            $this->assign('data',$list);// 赋值数据集
            $this->assign('start_time',$start_time);// 赋值数据集
            $this->assign('end_time',$end_time);// 赋值数据集
            $this->assign('is_type',$is_type);// 赋值数据集
            $this->assign('ordertel',$ordertel);// 赋值数据集
            $this->assign('usertel',$usertel);// 赋值数据集
            $this->assign('page',$show);// 赋值分页输出
            $this->display(); // 输出模板
//        }else{
//            $order_goods = M('order_goods'); // 实例化order_goods对象
//            $count = $order_goods->count();// 查询满足要求的总记录数
//            $Page  = new Page($count,15);// 实例化分页类 传入总记录数和每页显示的记录数(15)
//            $show  = $Page->show();// 分页显示输出
//            // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
//            $list = $order_goods->alias('o')
//                ->distinct(true)
//                ->field('o.*,a.name,a.phone,d.status')
//                ->join('LEFT JOIN __ORDER_ADDR__ AS a ON o.order_id = a.order_id')
//                ->join('LEFT JOIN __ORDER__ AS d ON o.order_id = d.id')
//                ->limit($Page->firstRow.','.$Page->listRows)
//                ->select();
//            $this->assign('data',$list);// 赋值数据集
//            $this->assign('page',$show);// 赋值分页输出
//            $this->display(); // 输出模板
//        }
    }

    //发货详情
    public function delivery()
    {
        if(IS_GET){
            $order_id = I('get.order_id');
            $order_goods = M("Order_goods");
            $delivery = $order_goods->where('order_id=' . $order_id)->find();
            $this->assign('delivery', $delivery);
            $this->display();
        }else{
            $order_id = I('post.order_id');
            $deliName = I('post.delivery_name');
            $deliSn = trim(I('post.delivery_sn'));
            if(empty($deliSn)){
                $this->error('物流单号不能为空！');
            }
            M()->startTrans();
            // 进行相关的业务逻辑操作
            $order_goods = M("Order_goods");
            $data = array(
                'delivery_name' => $deliName,
                'delivery_sn' => $deliSn,
                'delivery_status' => 1,
                'updated_at' =>time()
            );
            $res = $order_goods->where('order_id=' . $order_id)->setField($data);
            $order = M("Order");
            $list = array(
                'status' => 3,
                'delivery_at' =>time()
            );
            $res && $order->where('id=' . $order_id)->setField($list);
            if ($res){
                // 提交事务
                M()->commit();
                $this->success('发货详情填写成功！',U('index'));
            }else{
                // 事务回滚
                M()->rollback();
                $this->error('发货详情填写失败！');
            }
        }


    }

    /**
     * 订单信息导出功能
     */
    public function export()
    {
        $where = '';
        $is_type = I('get.is_type');
        if($is_type==1){
            $where .= " AND o.status = 1";
        }elseif ($is_type==2) {
            //表示表单提交2的是待发货
            $where .= " AND o.status = 2";
        }elseif($is_type==3){
            //表示表单提交3的是已发货
            $where .= " AND o.status = 3";
        }elseif($is_type==4){
            //表示表单提交4的是已收货
            $where .= " AND o.status = 4";
        }elseif($is_type==9){
            //表示表单提交9的是已取消
            $where .= " AND o.status = 9";
        }elseif($is_type==10){
            //表示表单提交9的是已删除
            $where .= " AND o.status = 10";
        }elseif(empty($is_type)){
            //表示表单提交的是全部
            $where .= "";
        }

        $usertel = I('get.usertel');
        if(empty($usertel)){
            //表示表单提交没有条件
            $where .= "";
        }else{
            $where .= " AND a.phone like '%$usertel%'";
        }

        $start_time = I('get.start_time');
        $end_time = I('get.end_time');

        if(!empty($start_time) && !empty($end_time)) {
            $start_unix = strtotime($start_time.' 00:00:00');
            $end_unix = strtotime($end_time.' 23:59:59');
            $where .= ' AND o.created_at BETWEEN '.$start_unix.' AND '.$end_unix;
        }

        $where = trim($where,' AND');

        $order_goods = M('Order_goods');
        $data = $order_goods->alias('g')
            ->field('o.status,o.order_sn,a.name,a.temp_tel,a.phone,CONCAT(a.pcaaddress,a.detail) as address,g.goods_name,g.spec,g.number,g.total_money,g.delivery_name,g.delivery_sn')
            ->join('LEFT JOIN __ORDER_ADDR__ AS a ON g.order_id = a.order_id')
            ->join('LEFT JOIN __ORDER__ AS o ON g.order_id = o.id')
            ->where($where)
            ->select();

        $header='订单状态,账单号,收件人姓名,固话,手机号,地址,商品名称,规格,数量,总价,快递名称,快递单号';
        if(empty($usertel)){
            createCsv($data,$header);
        }else{
            createCsv($data,$header,$filename= $usertel.'用户的订单.csv');
        }


    }




    /**
     * phpexcel的导出
     */
//    public function demo()
//    {
//        $order_goods = M('Order_goods');
//        $orderinfo = $order_goods->alias('g')
//            ->distinct(true)
//            ->field('o.order_sn,g.e_order_sn,g.goods_name,g.number,g.spec,g.total_money,a.name,a.phone,a.pcaaddress,g.delivery_sn,g.delivery_name')
//            ->join('LEFT JOIN __ORDER_ADDR__ AS a ON g.id = a.order_id')
//            ->join('LEFT JOIN __ORDER__ AS o ON g.order_id = o.id')
//            ->select();
//        $header = array(
//            array(NULL, 2010, 2011, 2012),
//        );
//        $data = array_merge($header,$orderinfo);
//        dump($data);die;
//        createXls($data);
//    }



    /**
     * 订单详情
     */
    public function orderdetail()
    {
        $order_id = I('get.order_id');
        $order_goods = M('Order_goods');
        $data = $order_goods->alias('g')
            ->distinct(true)
            ->field('g.*,a.name,a.phone,a.pcaaddress,a.detail,o.order_sn,o.pay_type,o.status,o.wx_sn,o.pay_at,u.nickname')
            ->join('LEFT JOIN __ORDER_ADDR__ AS a ON g.order_id = a.order_id')
            ->join('LEFT JOIN __ORDER__ AS o ON g.order_id = o.id')
            ->join('LEFT JOIN __USER__ AS u ON g.user_id = u.id')
            ->where('g.order_id='.$order_id)
            ->select();
        $this->assign('data', $data);
        $this->display();
    }
}
