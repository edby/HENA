<?php
/**
 * Created by PhpStorm.
 * User: xsh
 * Date: 2018/5/9
 * Time: 14:25
 */
namespace Admin\Controller\Goods;

use function GuzzleHttp\Psr7\str;
use Think\Controller;
use Common\Model\GoodsRelationModel;
use Admin\Model\Goods\ReturnCashModel;
use Admin\Model\Goods\ActivityModel;
class GoodsController extends Controller
{
    /*
     *商品列表
     */
    function index()
    {
        $model = new GoodsRelationModel();

        $condition['status'] = ['NEQ', 9];

        $count = $model->where($condition)->count();
        $Page = new \Think\Page($count, 25);
        $show = $Page->show();// 分页显示输出
       
        $list = $model->relation('category')->where($condition)->order('create_time')->limit($Page->firstRow.','.$Page->listRows)->select();

        $this->assign('show',$show);
        $this->assign('list',$list);
        $this->display();
    }

    /*
     *商品添加
     */
    function add()
    {
        if(IS_GET){
            //获取分类数据，显示页面
            $model = D('category');
            $category = $model->getlist(1);
            $this->assign('category',$category);
            $this->display();
        }else{
            //添加商品
            $upload = new \Think\Upload();// 实例化上传类
            $upload->maxSize   = 3145728 ;// 设置附件上传大小
            $upload->exts      = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
            $upload->rootPath  = './Uploads/'; // 设置附件上传根目录
            $upload->savePath  = 'img/'; // 设置附件上传（子）目录
            $upload->autoSub = false;
            $logos[] = $_FILES['img'];
            $info   =   $upload->upload($logos);   //上传商品图
            if(!$info){
                $this->error($upload->getError());
            }

            $detailimg = [];  //存放上传的详情图片
            $len = count($info);
            for($i = 0;$i<$len;$i++){
                $detailimg[] = '/Uploads/img/'.$info[$i]['savename'];
            }


            $bgImgs[] = $_FILES['bg_img'];
            $bg_img   =   $upload->upload($bgImgs);   //上传商品背景
            if(!$bg_img){
                $this->error($upload->getError());
            }

            $model = M('goods');    //商品id调用函数
            $data['goods_name'] = $_POST['goods_name'];
            $data['f_title'] = $_POST['f_title'];
            $data['cate_id'] = $_POST['title'];
            $data['goods_price'] = $_POST['goods_price'];
            $data['original_price'] = $_POST['original_price'];
            $data['express_fee'] = $_POST['express_fee'];
            $data['goods_logo'] = $detailimg[0];
            $data['bg_img'] = '/Uploads/img/'.$bg_img[0]['savename'];
            $data['goods_detail_img'] = implode(',',$detailimg);
            $data['goods_num'] = $_POST['goods_num'];
            $data['sale_num'] = $_POST['sale_num'];
            $data['goods_unit'] = $_POST['goods_unit'];
            $data['acttype'] = $_POST['acttype'];
            $data['up_express_fee'] = $_POST['up_express_fee'];
            $data['detailimgs'] = $_POST['info'];
            $data['create_time'] = time();
            $data2 = $_POST['goods_attribute'];

            $res = $model->data()->add($data);
            if($res){
                for($i=0;$i<$len=count($data2);$i++){
                    $res2 = M('goods_type')->data(array('goods_id'=>$res,'goods_type'=>$data2[$i]))->add();
                    if($res2){
                        $this->success('商品添加成功!',U('Admin/Goods/Goods/edit',['id'=>$res]));
                    }
                }
            }
        }
    }

    /*
     *编辑商品
     * param id
     */
    function edit()
    {
        if(IS_GET){
            $id = I('get.id');
            $model = new GoodsRelationModel();
            $data = $model->relation(['category','activity','return_cash','goods_spec','exp_config'])->where('id='.$id)->find();
            $level = M('level')->select();
            $img = explode(',',$data['goods_detail_img']);
            $model = D('category');
            $category = $model->getlist(1);
            $spec = D('Spec')->getAll(['c_id' => $data['cate_id']]);

            // 获取商品属性规格
            $goodsSpec = D('GoodsSpec')->getAll(['goods_id' => $data['id']]);

            // 确定选项卡选中页面
            $select = 0;
            if(($data['acttype'] == 1 && empty($data['activity'])) || ($data['acttype'] == 2 && empty($data['return_cash'])) || ($data['acttype'] == 4 && empty($data['exp_config']))) {
                $select = 1;
            }elseif ((!empty($data['activity']) || !empty($data['return_cash']) || !empty($data['exp_config'])) && empty($data['goods_spec'])) {
                $select = 2;
            }

            if($data['return_cash'] != null) {
                $data['return_cash']['res_level'] = json_decode($data['return_cash']['res_level'],true);
            }

            if($data['exp_price']) {
                $data['exp_price']['exp_level_conf'] = json_decode($data['exp_price']['exp_level_conf'],true);
            }
            //商品属性 推荐 新品 热卖 start
            $attr_model = D("goods_type");
            $attr_data = $attr_model->where(['goods_id'=>$id])->select();
            if($attr_data){
                foreach ($attr_data as $row){
                    $attr_data_value[] = $row['goods_type'];
                    $this->assign('attr_data_value',$attr_data_value);
                }
            }
            // 爆品库
            if(!empty($data['exp_config'][0])) {
                $normalUser = $data['exp_config'][0];
                $normalUser['exp_json'] = json_decode($normalUser['exp_json'],true);

                $this->assign('normalUser',$normalUser);
            }
            if(!empty($data['exp_config'][1])) {
                $member = $data['exp_config'][1];
                $member['exp_json'] = json_decode($member['exp_json'],true);

                $this->assign('member',$member);
            }

            //商品属性 end
            $this->assign('goodsSpec',$goodsSpec);
            $this->assign('level',$level);//爆品库分销比例设置
            $this->assign('spec',$spec);
            $this->assign('category',$category);  //商品分类树形结构数据
            $this->assign('select',$select);  //商品分类树形结构数据
            $this->assign('img',$img); //商品图片
            $this->assign('data',$data); //商品数据
            $this->display();
        }else{

            // 上传背景图片
            if($_FILES['bg_img']['tmp_name'][0] != ''){
                $bgImg[] = $_FILES['bg_img'];
                $upload = new \Think\Upload();// 实例化上传类
                $upload->maxSize   = 3145728 ;// 设置附件上传大小
                $upload->exts      = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
                $upload->rootPath  = './Uploads/'; // 设置附件上传根目录
                $upload->savePath  = 'img/'; // 设置附件上传（子）目录
                $upload->autoSub = false;
                $bg_img   =   $upload->upload($bgImg);   //打印上传信息
                if(!$bg_img){
                    $this->error($upload->getError());
                }

                $data['bg_img'] = '/Uploads/img/'.$bg_img[0]['savename'];
            }

            //判断是否上传图片
            if($_FILES['img']['tmp_name'][0] != '') {
                $id = $_POST['id'];
                $model = M('goods');
                $onedata = M('goods')->where('id='.$id)->find();

                $upload = new \Think\Upload();// 实例化上传类
                $upload->maxSize   = 3145728 ;// 设置附件上传大小
                $upload->exts      = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
                $upload->rootPath  = './Uploads/'; // 设置附件上传根目录
                $upload->savePath  = 'img/'; // 设置附件上传（子）目录
                $upload->autoSub = false;
                $imgs[] = $_FILES['img'];
                $info   =   $upload->upload($imgs);   //打印上传信息

                if(!$info){
                    $this->error($upload->getError());
                }

                $detailimg = [];  //存放上传的详情图片
                $len = count($info);
                for($i = 0;$i<$len;$i++){
                    $detailimg[] = '/Uploads/img/'.$info[$i]['savename'];
                }
                $data['goods_name'] = $_POST['goods_name'];
                $data['f_title'] = $_POST['f_title'];
                $data['cate_id'] = $_POST['title'];
                $data['goods_price'] = $_POST['goods_price'];
                $data['original_price'] = $_POST['original_price'];
                $data['express_fee'] = $_POST['express_fee'];
                $data['goods_num'] = $_POST['goods_num'];
                $data['sale_num'] = $_POST['sale_num'];
                $data['goods_unit'] = $_POST['goods_unit'];
                $data['acttype'] = $_POST['acttype'];
                $data['up_express_fee'] = $_POST['up_express_fee'];
                $data['detailimgs'] = $_POST['info'];
                $data['create_time'] = time();
                $data2 = $_POST['goods_attribute'];  //商品属性
                if($onedata['goods_logo'] == '' || $onedata['goods_logo'] == null){
                    $data['goods_logo'] = $detailimg[0];
                }

                if($onedata['goods_detail_img'] == '' || $onedata['goods_detail_img'] == null){
                    $data['goods_detail_img'] = implode(',',$detailimg);
                }else{
                    $data['goods_detail_img'] = $onedata['goods_detail_img'].','.implode(',',$detailimg);
                }

                $res = $model->where('id='.$id)->save($data);
                if($res){
                    $attr_model = D("goods_type");
                    $attr_data = $attr_model->where(['goods_id'=>$id])->select();
                    if($attr_data){
                        $del = $attr_model->where(['goods_id'=>$id])->delete();
                        if($del){
                            for($i=0;$i<$len=count($data2);$i++){
                                $res2 = $attr_model->data(array('goods_id'=>$id,'goods_type'=>$data2[$i]))->add();
                            }
                        }
                    }else{
                        for($i=0;$i<$len=count($data2);$i++){
                            $res2 = $attr_model->data(array('goods_id'=>$id,'goods_type'=>$data2[$i]))->add();
                        }
                    }
                    if($res2){
                        $this->success('商品编辑成功!',U('Admin/Goods/Goods/edit',['id'=>$id]));
                        return;
                    }
                }
            }else{


                $id = $_POST['id'];
                $model = M('goods');
                $data['goods_name'] = $_POST['goods_name'];
                $data['f_title'] = $_POST['f_title'];
                $data['cate_id'] = $_POST['title'];
                $data['goods_price'] = $_POST['goods_price'];
                $data['original_price'] = $_POST['original_price'];
                $data['express_fee'] = $_POST['express_fee'];
                $data['goods_num'] = $_POST['goods_num'];
                $data['sale_num'] = $_POST['sale_num'];
                $data['goods_unit'] = $_POST['goods_unit'];
                $data['acttype'] = $_POST['acttype'];
                $data['up_express_fee'] = $_POST['up_express_fee'];
                $data['detailimgs'] = $_POST['info'];
                $data['update_time'] = time();
                $res = $model->where('id='.$id)->save($data);
                $data2 = $_POST['goods_attribute'];//商品属性

                if($res){
                    $attr_model = D("goods_type");
                    $attr_data = $attr_model->where(['goods_id'=>$id])->select();
                    if($attr_data){
                        $del = $attr_model->where(['goods_id'=>$id])->delete();
                        if($del){
                            for($i=0;$i<$len=count($data2);$i++){
                                $res2 = $attr_model->data(array('goods_id'=>$id,'goods_type'=>$data2[$i]))->add();
                            }
                        }
                    }else{
                        for($i=0;$i<$len=count($data2);$i++){
                            $res2 = $attr_model->data(array('goods_id'=>$id,'goods_type'=>$data2[$i]))->add();
                        }
                    }

                    if($res2){
                        $this->success('商品编辑成功!',U('Admin/Goods/Goods/edit',['id'=>$id]));
                        return;
                    }
                }
            }
            $this->error('商品编辑失败!');
        }

    }

    /*
     *删除商品图片
     * param id
     */
    public function del_img()
    {
        $id = I('get.id');
        $url_img = I('get.img_src');
        $img_name = end(explode('/',$url_img)); //要删除的图片名称  goods_logo goods_detail_img
        $data = M('goods')->where('id='.$id)->find();
        $goods_logo = end(explode('/',$data['goods_logo']));
        $goods_detail_img = explode(',',$data['goods_detail_img']);
        if($img_name == $goods_logo){
            $res = M('goods')->where('id='.$id)->save(array('goods_logo'=>'','goods_detail_img'=>''));
            if($res){
                //unlink($_SERVER["DOCUMENT_ROOT"].'/Uploads/img/'.$img_name);
                $this->ajaxReturn(['status'=>1]);
            }
        }else{
            for($i=0;$i<$len=count($goods_detail_img);$i++){
                if($img_name == end(explode('/',$goods_detail_img[$i]))){
                    unlink($_SERVER["DOCUMENT_ROOT"].$goods_detail_img[$i]);
                    unset($goods_detail_img[$i]);
                    $res = M('goods')->where('id='.$id)->setField('goods_detail_img',implode(',',$goods_detail_img));
                    if($res){
                        $this->ajaxReturn(['status'=>1]);
                    }
                }
            }
        }


    }

    /*
     *删除商品
     * param id
     * status 9  软删除
     */
    function del()
    {
        $id = I('get.id');
        $res = M('goods')->where('id='.$id)->setField('status',9);
        if($res){
            $this->success('删除成功!');
            return;
        }
        $this->error('删除失败!');
    }

    /**
     * 活动页面
     */
    public function activity()
    {

        $id = $_GET['id'];
        $acttype = $_GET['acttype'];
        $goods = M('goods')->field('goods_name')->where(['id'=>$id])->find();
        if($acttype == 1) {
            $data = M('activity')->where(['goods_id'=>$id])->find();
            if(!empty($data)) {
                $this->assign('data',$data);
            }
            $this->assign('activity_name','520活动');
            $this->assign('acttype',$acttype);
            $this->assign('goods_name',$goods['goods_name']);
            $this->assign('goods_id',$id);

            $this->display('Goods/Goods/activity');
        }elseif($acttype == 2){
            $level = M('level')->select();
            $data = M('return_cash')->where(['goods_id' => $id])->find();
            if(!empty($data)) {
                $data['res_level'] = json_decode($data['res_level'],true);
                $this->assign('data',$data);
            }
            $goods = M('goods')->field('goods_name')->where(['id'=>$id])->find();
            $this->assign('activity_name','0元购活动');
            $this->assign('level',$level);
            $this->assign('acttype',$acttype);
            $this->assign('goods_name',$goods['goods_name']);
            $this->assign('goods_id',$id);
            $this->display('Goods/Goods/return_cash');
        }elseif($acttype == 4){
            //爆品库
            $level = M('level')->select();
            $data = M('exp_config')->where(['goods_id' => $id])->select();
//            dump($data);
            $member = $normalUser = [];
            if(!empty($data[0])) {
                $normalUser = $data[0];
                $normalUser['exp_json'] = json_decode($normalUser['exp_json'],true);
                $this->assign('normalUser',$normalUser);
            }
            if(!empty($data[1])) {
                $member = $data[1];
                $member['exp_json'] = json_decode($member['exp_json'],true);
                $this->assign('member',$member);
            }
            $goods = M('goods')->where(['id'=>$id])->find();
            $this->assign('activity_name','爆品库');
            $this->assign('level',$level);
            $this->assign('acttype',$acttype);
            $this->assign('goods',$goods);
            $this->assign('goods_id',$id);
            $this->display('Goods/Goods/exp_goods');
        }

    }

    public function addActivity()
    {
        $acttype = $_POST['acttype'];

        if($acttype == 1) {
            $model = new ActivityModel;
            $flag = false;
            if(isset($_POST['id'])) {
                $data['id'] = $_POST['id'];
                $flag = true;

            }
            $data['goods_id'] = $_POST['goods_id'];
            $data['type'] = $_POST['type'];
            $data['description'] = $_POST['description'];

            if($model->create($data)) {
                $flag ? $result = $model->save($data) : $result = $model->add($data);
                if($result) {
                    $this->ajaxReturn(['code' => 2000,'msg' => '添加/编辑成功']);
                }else{
                    $this->ajaxReturn(['code' => 3000,'msg' => '添加/编辑失败']);
                }
            }else{
                $this->ajaxReturn(['code' => 3000,'msg' => $model->getError()]);
            }
        } elseif($acttype == 2) {

            $model = new ReturnCashModel;
            $flag = false;
            if(!empty($_POST['id'])) {
                $data['id'] = $_POST['id'];
                $flag = true;
            }
            $data['goods_id'] = $_POST['goods_id'];
            $data['res_level'] = json_encode($_POST['res_level']);
            $data['day'] = $_POST['day'];
            $data['res_num'] = $_POST['res_num'];
            $data['start_time'] = strtotime($_POST['start_time']);
            $data['end_time'] = strtotime($_POST['end_time']);
            $data['description'] = $_POST['description'];
            $data['created_at'] = time();


            if($model->create($data)) {
                $flag ? $result = $model->save($data) : $result = $model->add($data);
                if($result) {
                    $this->ajaxReturn(['code' => 2000,'msg' => '添加/编辑成功']);
                }else{
                    $this->ajaxReturn(['code' => 3000,'msg' => '添加/编辑失败']);
                }

            }else{
                $this->ajaxReturn(['code' => 3000,'msg' => $model->getError()]);
            }
        }

    }

    /*
     * 商品上下架
     * param id
     */
    public function upgoods(){

        $id = I('get.id');
        $status = I('get.status');
        if($status ==1 ){
            $data = M('goods_spec')->where('goods_id='.$id)->select();
            if(!$data){
                $this->error("请先添加相关规格!");
                return;
            }
        }
        $model = M('goods');
        $res = $model->where('id='.$id)->setField('status',$status);
        if($res){
            $this->success('操作成功！');
            return;
        }
        $this->success('操作失败！');

    }

    /*
     * 商品详情信息
     * param id
     */
    public function getgoodsinfo(){

        $id = I('post.id');
        $acttype = I('post.acttype');
        if($acttype == 1 ){
            $relationmodel = 'activity'; //520
        }else{
            $relationmodel = 'return_cash';  //0元购
        }
        $model = new GoodsRelationModel();
        $data = $model->relation($relationmodel)->where('id='.$id)->find();
        $data['create_time'] = date('Y-m-d H:i:s ',$data['create_time']);
        if($data){
            $this->ajaxReturn(['data' => $data,'status' => 1]);
        }else{
            $this->ajaxReturn(['status' => 2]);
        }

    }

    //爆品库会员价格
    public function addexpgoods()
    {
        $goods_data = M("exp_goods")->where(['id'=>$_POST['goods_id']])->find();
        if($goods_data){
            $res = M("exp_goods")->where(['id'=>$_POST['goods_id']])->save(array('exp_conf'=>$_POST['price'],'description'=>$_POST['description']));
        }else{
            $data['goods_id'] = $_POST['id'];
            $data['description'] = $_POST['description'];
            $data['exp_conf'] = $_POST['price'];
            $data['created_at'] = time();
            $model = M("exp_goods");
            $res = $model->data($data)->add();
        }
        if($res){
            $this->success("操作成功！",U('Admin/Goods/Goods/index'));
            return;
        }

        $this->error('操作失败！');

    }

    //设置不同级别会员分佣比例
    public function setlevelconf()
    {
       $model = M('exp_price');
       $price_data = M("exp_price")->where(['id'=>$_POST['goods_id']])->find();
       if($price_data){
           $data['exp_level_conf'] = json_encode(array('res_level10'=>$_POST['res_level10'],'res_level11'=>$_POST['res_level11']));
           $res = $model->where(['id'=>$_POST['goods_id']])->save($data);
       }else{
           $data['goods_id'] = $_POST['id'];
           $data['exp_level_conf'] = json_encode(array('res_level10'=>$_POST['res_level10'],'res_level11'=>$_POST['res_level11']));
           $data['create_time'] = time();
           $res = $model->data($data)->add();
       }
       if($res){
           $this->success('操作成功！',U('Admin/Goods/Goods/index'));
           return;
       }
       $this->error('操作失败！');
    }

    public function addExp()
    {
        if(IS_POST) {
            $flag = false;
            if(!empty($_POST['exp_id'])) {
                $data['id'] = $_POST['exp_id'];
                $flag = true;
            }
            if($_POST['exp_type'] == 0) {
                $data['goods_id'] =  $_POST['goods_id'];
                $data['goods_price'] = $_POST['goods_price'];
                $data['res_level'] = '1,2,3,4,5,6,7,8,9,10,11'; // 当前购买人等级
                $data['exp_json'] = json_encode([
                    [
                        'level' => [1,2,3,4,5,6,7,8,9,10,11],  // 上级
                        'ratio' => $_POST['ratio'][0],
                        'sup' => ['level' => [1,2,3,4,5,6,7,8,9,10,11,12,13], 'ratio' => $_POST['ratio'][1]] // 上上级
                    ],


                    [
                        'level' => [12,13],
                        'ratio' => $_POST['ratio'][2],
                        'sup' =>['level' => [1,2,3,4,5,6,7,8,9,10,11,12,13], 'ratio' => $_POST['ratio'][3],]]

                ]);


            }
            if($_POST['exp_type'] == 1) {

                $data['goods_id'] =  $_POST['goods_id'];
                $data['goods_price'] = $_POST['member_price'];
                $data['res_level'] = '12,13'; // 当前购买人等级
                $data['exp_json'] = json_encode([
                    [
                        'level' => [1,2,3,4,5,6,7,8,9,10,11,12,13],  // 上级
                        'ratio' => $_POST['member_ratio'][0],
                        'sup' =>['level' => [1,2,3,4,5,6,7,8,9,10,11,12,13], 'ratio' => $_POST['member_ratio'][1]] // 上上级
                    ],
                ]);

            }

            $model = M('exp_config');
            $flag ? $result = $model->save($data) : $result = $model->add($data);
            if($result === false) {

                $this->error('添加/编辑失败！');
            }else{
                $this->success('添加/编辑成功！');
            }
        }


    }



}