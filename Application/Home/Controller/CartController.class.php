<?php
namespace Home\Controller;
use Think\Controller;
class CartController extends Controller{
    public function shopping(){
        $user_id="18590923065";
        $res=M("cart")->where("user_id =".$user_id)->select();
        // dump($res);
        $this->assign('data',$res);
        $this->display();
    }
    public function delsh(){   //删除商品
        $order_id=I('post.shoppingid');
        $res=D('cart')->where('id='.$order_id)->delete();
        if($res){
            $this->ajaxReturn(1);
        }else{
            echo "0";
        }
    }
    public function order(){
        $ddh=I('get.ddh');
        $data=D('order')->where('order_number=%s',$ddh)->find();
        $data['goods_name'] =explode(';',$data['goods_name']);
        array_pop($data['goods_name']);
        $data['spec']       =explode(';',$data['spec']);
        array_pop($data['spec']);
        $data['price']      =explode(';',$data['price'] );
        array_pop($data['price'] );
        $data['img']        =explode(';',$data['img'] );
        array_pop($data['img'] );
        $data['goodsid']    =explode(';',$data['goodsid']);
        array_pop($data['goodsid']);

        $count=count($data['goods_name']);
        $this->assign('count',$count);
        $this->assign('data',$data);
        //dump($data);
        $this->display();
    }

    public function add_order(){
        $userinfo = D("User")->getUserInfo();
        // echo "<pre>";
        // dump($data);die;
        $ti = time();
        $time1 = date('Ymd',$ti);
        $ddh=$time1.rand();//订单号
        $data['goods_name'] =I('post.goods_name');
        $data['spec']       =I('post.spec');
        $data['price']      =I('post.price');
        $data['img']        =I('post.img');
        $data['goodsid']    =I('post.goodsid');
        $data['money']      =I('post.money');
        $data['youfei']     =I('post.youfi');
        $data['user_id']    =D("User")->getUserInfo();
        $data['goods_name'] =explode(';',$data['goods_name']);
        array_pop($data['goods_name']);
        $data['num']        =count($data['goods_name']);
        $data['goods_name'] =I('post.goods_name');
        $data['order_number']=$ddh;
        $data['addtime']    =time();
        $data['status']     ='1';
        $zhifu=I('get.zhifu');
        $goodsname=I('post.goods_name');
        // $find=D('order')->where('goods_name ='.$goodsname)->find();
        // if($find){
        //  echo '2';
        // }else{
        //dump($userinfo );
        //$goodsnum=count($data['goodsid']);
        $res=D('order')->data($data)->add();
        if($res){
            $goodsid = explode(';',I('post.goodsid'));
            // array_pop($goodsid);
            $map['id']  = array('in',$goodsid);
            $del=D('cart')->where($map)->delete();
            echo $ddh;

        }else{
            echo "0";
        }

        // }
    }






    public function add_cart(){   //添加商品
        $goodsid=I('post.goodsid');
        $data=array(
            "goods_name"    =>I('post.goods_name'),
            "goods_pic"     =>I('post.goods_pic'),
            "goods_desc"    =>I('post.goods_desc'),
            "goods_num"     =>I('post.goods_num'),
            "goods_youfei"  =>I('post.goods_youfei'),
            "goods_price"   =>I('post.goods_price'),
            "spec"          =>I('post.spec'),
            "goodsid"       =>I('post.goodsid'),
            "add_time"      =>time()
        ) ;
        $shopping=M('cart')->where('goodsid='.$goodsid)->find();
        if($shopping){
            $data['goods_num']=$data['goods_num'] +$shopping['goods_num'];
            $data['spec']=$data['spec'].';'.$shopping['spec'];
            $res=D('cart')->where('goodsid='.$goodsid)->data($data)->save();
        }else{
            $res=D('cart')->data($data)->add();
        }
        if($res){
            echo '1';
        }else{
            echo '0';
        }
        //echo $goodsid;
    }

}