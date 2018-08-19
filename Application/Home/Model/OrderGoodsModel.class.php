<?php
namespace Home\Model;

class OrderGoodsModel extends CommonModel{

    protected $tableName = 'order_goods';

    protected $uid; // 用户ID
    protected $num; // 购买数量
    protected $gid;
    protected $acttype;
    protected $_validate = array(
        array('acttype','checkAuth','',1,'callback',520),// 只在登录时候验证
    );

    public function __construct($param)
    {
        parent::__construct();
        $this->uid = $param['uid'];
        $this->num = $param['num'];
        $this->gid = $param['goods_id'];


    }
    public function createEOrderSn()
    {
        $orderSn = orderSn('esn');
        $isExist = $this->field('id')->where(['e_order_sn' => $orderSn])->select();
        while ($isExist) {
            $orderSn = orderSn('esn');
            $isExist = $this->field('id')->where(['e_order_sn' => $orderSn])->select();
        }

        return $orderSn;
    }
    /**
     * @param int $isPay 0 表示下单  1表示付款
     * @return bool|mixed
     */
    public function validata($isPay = 0)
    {
        $goods = M('goods')->where(['id' => $this->gid, 'status' => 1])->find();
        if(empty($goods)) {
            $this->error = '商品不存在或已下架!';
            return false;
        }
        if($isPay == 0) {
            if($goods['goods_num'] < $this->num) {
                $this->error = '商品库存不足或已下架!';
                return false;
            }
        }



        if(!in_array($goods['acttype'], [1, 2, 3, 4, 5])) { // 1、520 2、0元购 4、爆品库
            $this->error = '该商品尚未开启购买!';
            return false;
        }

        $level = D('user')->getOneField(['id' => $this->uid], 'level');
//      520商品购买验证
        if($goods['acttype'] == 1) {
            if($level >= 2) {
                $this->error = '您已是创客，不能重复购买！';
                return false;
            }

            if($this->num > 1 ) {
                $this->error = '520商品只可购买一件!';
                return false;
            }
        }

        $Order = D('Order');
        if($goods['acttype'] == 2) {
            // 读取配置
            $config = M('return_cash')->where(['goods_id'=>$this->gid])->find();
            if(empty($config)) {
                $this->error = '当前商品未配置0元购条件,无法下单/支付';
                return false;
            }
            if(!in_array($level,json_decode($config['res_level'],true))){
                $this->error = '董咖专享，您还没有购买权限！';
                return false;
            }
            if($this->num > $config['res_num']) {
                $this->error = '超过购买上限!';
                return false;
            }
            if(($Order->getOrderGoodsNum($this->gid, $this->uid) + $this->num) > $config['res_num']){
                $this->error = '超过购买上限!';
                return false;
            }

            $time = time();
            if($time < $config['start_time'] || $time > $config['end_time']){
                $this->error = '未在购买时间范围，不可购买!';
                return false;
            }
        }
        return $goods;
    }
}
