<?php

namespace Admin\Controller\Bill;
use Admin\Controller\CommonController;
use Think\Page;

/**
 * 账单列表 order
 * Class OrderController
 * @package Admin\Controller
 */
class OrderController extends CommonController {

    public function index()
    {
        $where = '';
        $tel = I('get.tel');
        if(empty($tel)){
            //表示表单提交没有条件
            $where .= "";
        }else{
            $where .= " AND a.phone like '%$tel%'";
        }

        $ordernum = I('get.ordernum');
        if(empty($ordernum)){
            //表示表单提交没有条件
            $where .= "";
        }else {
            $where .= " AND o.order_sn like '%$ordernum%'";
        }

        $where = trim($where,' AND');

            $Order = M('Order');
            $count = $Order->alias('o')
                ->join('LEFT JOIN __ORDER_ADDR__ AS a ON o.id = a.order_id')
                ->join('LEFT JOIN __USER__ AS u ON o.user_id = u.id')
                ->where($where)
                ->count();
            $Page  = new Page($count,15);// 实例化分页类 传入总记录数和每页显示的记录数(25)
            $show  = $Page->show();// 分页显示输出
            // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
            $list = $Order->alias('o')
                ->field('o.*,a.name,a.phone,g.goods_name,u.nickname,u.realname,u.tel')
                ->join('LEFT JOIN __ORDER_ADDR__ AS a ON o.id = a.order_id')
                ->join('LEFT JOIN __ORDER_GOODS__ AS g ON o.id = g.order_id')
                ->join('LEFT JOIN __USER__ AS u ON o.user_id = u.id')
                ->where($where)
                ->order('id desc')
                ->limit($Page->firstRow.','.$Page->listRows)
                ->select();
            $this->assign('data',$list);// 赋值数据集
            $this->assign('page',$show);// 赋值分页输出
            $this->display(); // 输出模板
    }
}
