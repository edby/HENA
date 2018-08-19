<?php

namespace Home\Controller;
use Think\Controller;

class AddressController extends LoginController{

    //获取会员地址
    public function index(){
       
        $userId = session('userId');
        $res = M('address')->where(['user_id' => $userId, 'status' => ['in', [0, 1]]])->order('update_at desc')->select();

        $this->assign('res',$res);
        $this->display();
    }

    // 添加地址页
    public function addaddress(){

        $this->display();

    }

    //设置默认地址
    public function setaddress($id){

        $id = intval($id);
        $userId = session('userId');

        M()->startTrans();

        $model = D('Address');
        
        $result = $model->updateData(['user_id' => $userId, 'status' => 1], ['status' => 0]);
        $result = $result && $model->updateData(['id' => $id, 'user_id' => $userId, 'status' => 0], ['status' => 1]);
       
        if($result) {
            M()->commit();
            $this->success('默认地址设置成功!');
        } else {
            M()->rollback();
            $this->error('默认地址设置失败!'.$id.'-'.$userId);
        }
    }

    //添加会员地址数据提交
    public function addaddress1(){

        $userId = session('userId');
        $address = I('post.pca'); 
        $address = explode('&gt;',$address);
        
        $model = D('Address');

        // 验证地址条数不能超过6条
        if($model->getCount(['user_id' => $userId, 'status' => ['in', [0, 1]]]) >= 6) {
            $this->error('地址数量不能超过6条,您可以修改已添加的地址!');
            exit(0);
        }

        $result = $model->createData([
            'user_id' => $userId,
            'pcaaddress' => implode('',$address),
            'tel' => I('post.tel'),
            'status' => 0,
            'realname' => I('post.realname'),
            'detail' => I('post.detailaddress'),
            'created_at' => time(),
        ]);

        if($result){
            $this->success('地址添加成功！', U('address/index'));
            exit(0);
        }else{
            $this->error('地址添加失败');
            exit(0);
        }

    }

    //获取会员地址
    public function editaddress($id){

        $id = intval($id);
        $userId = session('userId');

        $model = D('Address');
        $data = $model->getOne(['id' => $id, 'user_id' => $userId, 'status' => ['in', [0, 1]]]);
        if(!$data) {
            redirect(U('Address/index'));
        }

        $this->assign('data',$data);
        $this->display('Address/edidaddress');

    }

    //编辑会员地址   where("id=%d,user='%s'",array($id,$user))
    public function editadd(){

        $userId = session('userId');
        $address = I('post.pca1');

        if($address == ''){
            $address = I('post.pca');
            $address = explode('&gt;',$address);
            $pcaaddress = implode('',$address);
        }else{
            $address = explode('&gt;',$address);
            $pcaaddress = implode('',$address);
        }

        $model = D('Address');

        $result = $model->updateData([
            'id' => I('post.id'),
            'user_id' => $userId,
            'status' => ['in', [0, 1]]], [
                'tel' => I('post.tel'),
                'realname' => I('post.realname'),
                'pcaaddress' => $pcaaddress,
                'detail' => I('post.detail'),
                'updated_at' => time(),
            ]);
       
        if($result){
            $this->success('地址修改成功!', U('address/index'));
            exit(0);
        }else{
             $this->error('地址修改失败!');
            exit(0);
        }
    }

    public function del()
    {
        $userId = session('userId');
        $addrId = I('get.id');
        $res = M('Address')->where(['id' => $addrId,'user_id' => $userId])->save(['status' => 9]);
        if($res){
            header('Location:'.U('address/index'));
            exit(0);
        }else{
            header('Location:'.U('address/index'));
            exit(0);
        }
    }


}