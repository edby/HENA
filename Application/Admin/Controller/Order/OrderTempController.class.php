<?php
namespace Admin\Controller\Order;

use Admin\Controller\CommonController;

class OrderTempController extends CommonController
{
    /**
     * 展示页面
     */
    public function index()
    {
        $Temp_money = M('Temp_money');
        $data = $Temp_money->select();
        $moneys = $Temp_money->sum('temp_money');
        $this->assign('data',$data);
        $this->assign('moneys',$moneys);
        $this->display();
    }

    public function add()
    {
        if(IS_POST){
            $data['temp_money'] = I('post.money');
            $res =  M('Temp_money')->add($data);
            if($res !== false){
                $this->success('新增成功');
            }else{
                $this->error('新增失败');
            }

        }
        $this->display();
    }

}