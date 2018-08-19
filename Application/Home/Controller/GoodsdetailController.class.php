<?php
namespace Home\Controller;
use Think\Controller;
class GoodsdetailController extends LoginController {

    public function index(){
        $action = I('get.action');
        switch ($action){
            case '1':
                $this->getgoods('1');
                break;
            case '2':
                $this->getgoods('2');
                break;
            case '3':
                $this->getgoods('4');
                break;
            default:
                break;
        }
    }

    public function getgoods($action){

        $model = D('shoping');
        $count = $model->where(array('acttype'=>$action))->count();
        $Page       = new \Think\Page($count,6);
        $show      = $Page->show();
        $data = $model->where(array('acttype'=>$action))->order('add_time desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('data',$data);
        $this->assign('show',$show);
        if($action == '1'){
            $this->display('Goodsdetail/Package1');
        }else{
            $this->display('Goodsdetail/Package');
        }

    }



}