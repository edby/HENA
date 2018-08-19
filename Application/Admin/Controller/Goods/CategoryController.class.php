<?php
namespace Admin\Controller\Goods;
use Think\Controller;
class CategoryController extends Controller{

    /*
     * 添加商品分类数据
     * request get
     * return  array()
     */
    public function index(){
        $model = D('category');
        $category = $model->getlist(0);
        $this->assign('category',$category);
        $this->display();

    }

    /*
     * 获取分类数据,显示添加商品分类页
     * request get
     */
    public function addshow(){

        $model = D('category');
        $category = $model->getlist(1);
        $this->assign('category',$category);
        $this->display();

    }

    /*
     * 添加商品分类
     * request post
     */
    public function add(){

        $data['cat_title'] = I('post.type');
        $data['cat_pid'] = I('post.catid');
        $data['create_time'] = time();
        $model = D('category');
        $res = $model->addcategory($data);
        if($res){
            $this->ajaxReturn(['msg'=>'添加成功!','status'=>1]);
        }

    }

    /*
     * 显示编辑商品分类页
     * request get
     */
    public function editshow(){

        $cat_id = I('get.id');
        $model = D('category');
        $title = $model->getdata($cat_id);
        $category = $model->getlist(1);
        $specList = D('SpecAddrRel')->getdata($cat_id);
        $this->assign('title',$title);
        $this->assign('category',$category);
        $this->assign('spec_list',$specList);
        $this->display();

    }

    /*
     * 编辑商品分类
     * request post
     */
    public function edit(){
        $id = I('post.id');
        $data['cat_title'] = I('post.type');
        $data['cat_pid'] = I('post.catid');
        $data['update_time'] = time();
        $model = D('category');
        $res = $model->editcategory($data,$id);
        if($res){
            $this->ajaxReturn(['msg'=>'修改成功!','status'=>1]);
        }

    }

    /*
     * 添加商品规格
     */
    public function addspec(){
        if(IS_GET){
            $cat_id = I('get.id');  //分类ID
            $cat_title = I('get.title');   //类型名称
            $specList = D('SpecAddrRel')->getdata($cat_id);
//            dump($specList);die;
            $this->assign('cat_id',$cat_id);
            $this->assign('cat_title',$cat_title);
            $this->assign('spec_list',$specList);
            $this->display();
        }else{
            $spec = M('spec');
            $addr = M('addr');
            $num = $_POST['specnum'];
            $spec_name = [];       //规格名称
            $spec_val = [];       //规格属性
            for($i=1;$i<=$num;$i++){
                if ($_POST['specc'.$i]){
                    $spec_name[] = $_POST['specc'.$i][0];
                    $spec_val[] = $_POST['spec'.$i];
                }
            }
            $data['c_id'] = $_POST['cat_id']; //分类ID
            $data['spec_title'] = $_POST['cat_id']; //分类ID
            $data['create_time'] = time();
            for($i=0;$i<$len=count($spec_name);$i++){
                $data['spec_title'] = $spec_name[$i];
                $res = $spec->data($data)->add();
                if($res){
                    $insertArr = $spec_val[$i];
                    foreach ($insertArr as $v){
                        $res1 = $addr->data(array('addr_title'=>$v,'s_id'=>$res,'create_time'=>time()))->add();
                    }
                }
            }

            if($res1){
                $this->success('添加成功!');
            }

        }
    }

    //标签管理
    public function signmanager(){

    }

    /**
     * 删除规格
     */
    public function deleteSpec()
    {
        $specId = I('post.spec_id');
        $flag = true;
        M()->startTrans();
        $res1 = M('Spec')->where(['spec_id' => $specId])->delete();
        $res2 = M('Addr')->where(['s_id' => $specId])->delete();
        if ($res1 === false){
            $flag = false;
        }
        if ($res2 === false){
            $flag = false;
        }
        if ($flag){
            M()->commit();
            $this->ajaxReturn(['status' => 1,'msg'=>'规格删除成功!']);
        }
        M()->rollback();
        $this->ajaxReturn(['status' => 0,'msg'=>'规格删除失败!']);
    }

    /**
     * 编辑规格
     */
    public function editSpec()
    {
        //显示规格编辑页面
        $specId = I('get.id');
        $specInfo = M('Spec')->find($specId);
        $addrList = M('Addr')->where(['s_id' => $specId])->select();
        $this->assign('spec_info',$specInfo);
        $this->assign('addr_list',$addrList);
        $this->display();
    }

    /**
     * 删除规格的值
     */
    public function deleteAddr()
    {
        $addrId = I('post.addr_id');
        $flag = M('Addr')->delete($addrId);
        if ($flag !== false){
            $this->ajaxReturn(['status' => 1,'msg'=>'规格删除成功!']);
        }
        $this->ajaxReturn(['status' => 0,'msg'=>'规格删除失败!']);
    }

    /**
     * 修改规格的值
     */
    public function editAddr()
    {
        $addrId = I('post.addr_id');
        $addrTitle = I('post.addr_title');
        $data = array(
            'addr_title' => $addrTitle,
            'update_time' => time()
        );
        $res = M('Addr')->where(['addr_id' => $addrId])->save($data);
        if ($res !== false){
            $this->ajaxReturn(['status' => 1,'msg'=>'规格修改成功!']);
        }
        $this->ajaxReturn(['status' => 0,'msg'=>'规格修改失败!']);
    }

    /**
     * 添加规格的值
     */
    public function addAddr()
    {
        $specId = I('post.spec_id');
        $addrTitle = I('post.addr_title');
        $data = array(
            'addr_title' => $addrTitle,
            's_id' => $specId,
            'create_time' => time()
        );
        $res = M('Addr')->add($data);
        if ($res !== false){
            $this->ajaxReturn(['status' => 1,'msg'=>'规格添加成功!']);
        }
        $this->ajaxReturn(['status' => 0,'msg'=>'规格添加失败!']);
    }

}