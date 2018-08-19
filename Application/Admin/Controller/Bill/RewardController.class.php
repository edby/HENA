<?php
namespace Admin\Controller\Bill;
use Think\Controller;
use Think\Page;

class RewardController extends Controller
{
    /**
     * 用户余额明细
     */
    public function userindex()
    {
        $where = '';
        $is_type = I('get.is_type');
        if($is_type==1){
            //表单提交的1表示充值
            $where .= " AND b_type =1";
        }elseif ($is_type==2) {
            //表示表单提交的是0元购
            $where .= " AND b_type =2";
        }elseif(empty($is_type)){
            //表示表单提交的是全部
            $where .= "";
        }

        $is_status = I('get.is_status');
        if($is_status == 1){
            //表单提交的0表示进账
            $where .= " AND type =0";
        }elseif ($is_status == 2) {
            //表示表单提交的是出账
            $where .= " AND type =1";
        }elseif(empty($is_status)){
            //表示表单提交的是全部
            $where .= "";
        }

        $tel = I('get.tel');
        if(empty($tel)){
            //表示表单提交没有条件
            $where .= "";
        }else{
            $where .= " AND u.tel like '%$tel%'";
        }

        $where = trim($where,' AND');
//        if($tel){
            $User = M('Balance_detail'); // 实例化User对象
            $count      = $User
                    ->alias('b')
                    ->field('b.*,u.tel')
                    ->join('LEFT JOIN __USER__ AS u ON b.user_id = u.id')
//                    ->where("u.tel like '%$tel%'")\
                    ->where($where)
                    ->count();// 查询满足要求的总记录数
            $Page       = new Page($count,15);// 实例化分页类 传入总记录数和每页显示的记录数(25)
            $show       = $Page->show();// 分页显示输出
            // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
            $list = $User
                ->alias('b')
                ->field('b.*,u.tel')
                ->join('LEFT JOIN __USER__ AS u ON b.user_id = u.id')
                ->order('id desc')
                ->where($where)
                ->limit($Page->firstRow.','.$Page->listRows)
                ->select();
            $this->assign('data',$list);// 赋值数据集
            $this->assign('page',$show);// 赋值分页输出
            $this->display(); // 输出模板
//        }else{
//            $User = M('Balance_detail'); // 实例化User对象
//            $count      = $User->count();// 查询满足要求的总记录数
//            $Page       = new Page($count,15);// 实例化分页类 传入总记录数和每页显示的记录数(25)
//            $show       = $Page->show();// 分页显示输出
//            // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
//            $list = $User
//                ->alias('b')
//                ->field('b.*,u.tel')
//                ->join('LEFT JOIN __USER__ AS u ON b.user_id = u.id')
//                ->limit($Page->firstRow.','.$Page->listRows)
//                ->select();
//            $this->assign('data',$list);// 赋值数据集
//            $this->assign('page',$show);// 赋值分页输出
//            $this->display(); // 输出模板
//        }
//
//        $data = M('Balance_detail')->alias('b')
//            ->field('b.*,u.tel')
//            ->join('LEFT JOIN __USER__ AS u ON b.user_id = u.id')
//            ->select();
//        $this->assign('data',$data);
//        $this->display();
    }

    /**
     * 用户推广奖励明细
     */
    public function cpsindex()
    {
        $where = '';
        $is_type = I('get.is_type');
        if($is_type==1){
            //表单提交的1表示分佣
            $where .= " AND b_type =1";
        }elseif ($is_type==2) {
            //表示表单提交的是520
            $where .= " AND b_type =2";
        }elseif($is_type==3){
            //表示表单提交的是0元购
            $where .= " AND b_type =3";
        }elseif(empty($is_type)){
            //表示表单提交的是全部
            $where .= "";
        }

        $is_status = I('get.is_status');
        if($is_status == 1){
            //表单提交的0表示进账
            $where .= " AND type =0";
        }elseif ($is_status == 2) {
            //表示表单提交的是出账
            $where .= " AND type =1";
        }elseif(empty($is_status)){
            //表示表单提交的是全部
            $where .= "";
        }

        $tel = I('get.tel');
        if(empty($tel)){
            //表示表单提交没有条件
            $where .= "";
        }else{
            $where .= " AND u.tel like '%$tel%'";
        }

        $where = trim($where,' AND');
//        if(!empty($tel && $is_type && $is_status)){
        $User = M('Bonus_detail'); // 实例化User对象
        $count      = $User
                ->alias('b')
                ->field('b.*,u.tel')
                ->join('LEFT JOIN __USER__ AS u ON b.user_id = u.id')
                ->where($where)
                ->count();// 查询满足要求的总记录数
        $Page       = new Page($count,15);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();// 分页显示输出
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $list = $User
            ->alias('b')
            ->field('b.*,u.tel')
            ->join('LEFT JOIN __USER__ AS u ON b.user_id = u.id')
            ->order('id desc')
            ->where($where)
            ->limit($Page->firstRow.','.$Page->listRows)
            ->select();
        $this->assign('data',$list);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        $this->display(); // 输出模板
//        }else{
//            $User = M('Bonus_detail'); // 实例化User对象
//            $count      = $User->count();// 查询满足要求的总记录数
//            $Page       = new Page($count,15);// 实例化分页类 传入总记录数和每页显示的记录数(25)
//            $show       = $Page->show();// 分页显示输出
//            // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
//            $list = $User
//                ->alias('b')
//                ->field('b.*,u.tel')
//                ->join('LEFT JOIN __USER__ AS u ON b.user_id = u.id')
//                ->limit($Page->firstRow.','.$Page->listRows)
//                ->select();
//            $this->assign('data',$list);// 赋值数据集
//            $this->assign('page',$show);// 赋值分页输出
//            $this->display(); // 输出模板
//        }
//        $data = M('Bonus_detail')->alias('b')
//            ->field('b.*,u.tel')
//            ->join('LEFT JOIN __USER__ AS u ON b.user_id = u.id')
//            ->select();
//        $this->assign('data',$data);
//        $this->display();
    }

}