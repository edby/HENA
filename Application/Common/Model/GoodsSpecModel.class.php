<?php
namespace Common\Model;

/**
 * 商品规格Model
 */
class GoodsSpecModel extends CommonModel{

	// 数据表名
	protected $tableName = 'goods_spec';

    /**
     * 详情页面 规格格式化
     * @param $goods_id
     * @return array
     */
    public function getFormatGoodsSpec($goods_id)
    {
        $list = $this->where(['goods_id'=>$goods_id,'status' => 0])->select();

        $data = [];
        foreach ($list as $key => $val) {
            $data[$val['spec_title']][intval($val['goods_spec_id'])] = $val['addr_title'];
//            $data[$val['spec_title']]['goods_spec_id'][] = $val['goods_spec_id'];
            $data[$val['spec_title']]['spec_id'] = $val['spec_id'];
            $data[$val['spec_title']]['goods_num'][$val['goods_spec_id']] = $val['goods_num'];
        }
//        var_dump($list);
//        var_dump($data);
        return $data;
    }

    /**
     * 统计当前商品共多少个规格
     * @param $goods_id
     * @return int 规格个数
     */
    public function countGoodsSpecNum($goods_id)
    {
        $arr = $this->getAll(['goods_id'=>$goods_id],'spec_id');
        $data = [];
        foreach ($arr as $key => $val) {
            $data[] = $val['spec_id'];
        }
        return count(array_unique($data));
    }

    /**
     * 检测库存是否充足
     * @param array $goods_spec_id
     * @param $goods_id
     * @param $num
     * @return bool
     */
    public function checkGoodsNum($goods_spec_id = [],$goods_id,$num)
    {
        if(!empty($goods_spec_id)) {
            $arr = $this->getAll(['goods_spec_id'=>['IN',$goods_spec_id],'status'=>0,'goods_id'=>$goods_id],'goods_num');

            foreach ($arr as $key =>$val) {
                if(($val['goods_num']-$num) < 0) {
                    return false;
                }
            }
        }
        $goods = D('goods');
        $goodsData = $goods->getOne(['id'=>$goods_id,'status'=>1],'goods_num');
        if(($goodsData['goods_num'] - $num) < 0){
            return false;
        }

        return true;

    }

    /**
     * 根据选择规格计算总价格
     * @param array $goods_spec_id
     * @param $goods_id
     * @param $num
     * @return array
     */
    public function getRealPrice($goods_spec_id = [],$goods_id,$num)
    {
        $totalMoney = 0;
        $goods = D('goods');
        if(!empty($goods_spec_id)) {
            $totalMoney += $this->getSingIncrePrice($goods_spec_id);
        }
        $goodsData = $goods->getOne(['id'=>$goods_id,'status'=>1],'goods_price,goods_num');
        if(empty($goodsData)) {
            return false;
        }else{
            $totalMoney += $goodsData['goods_price'];
            $totalMoney *= $num;
        }

        return $totalMoney;
    }

    /**
     * 爆品库商品总价格
     */
    public function getExpPrice($goods_spec_id = [],$goods_id,$num,$level)
    {
        $totalMoney = 0;
        if(!empty($goods_spec_id)) {
            $totalMoney += $this->getSingIncrePrice($goods_spec_id);
        }

        $expGoods = D('exp_config')->getAll(['goods_id'=>$goods_id],'goods_price,res_level');

        if(empty($expGoods)) {
            return false;
        }else{
            foreach ($expGoods as $key => $value) {
                if(in_array($level,explode(',',$value['res_level']))) {
                    $expGoods = $value;
                }
            }
            $totalMoney += $expGoods['goods_price'];
            $totalMoney *= $num;
        }
        return $totalMoney;
    }

    /**
     * 获取单商品增值金额
     */
    public function getSingIncrePrice($goods_spec_id)
    {
        return $this->sum(['goods_spec_id' => ['IN', $goods_spec_id], 'status' => 0], 'incre_price');
    }


    public function getSpecStr($goods_spec_id = [])
    {

        $arr = $this->getAll(['goods_spec_id'=>['IN',$goods_spec_id],'status'=>0],'spec_title,addr_title');
        $str = '';
        foreach ($arr as $key => $val) {
            $str .= $val['spec_title'] .':'.$val['addr_title'].';';
        }
        return $str;
    }
     /*
     * 获取商品规格数据
     * status 9  软删除
     */
    public function getGoodsSpec($goodsId)
    {
        $data = D('GoodsSpec')->getAll(['goods_id' => $goodsId]);
        $goodsSpec = [];
        foreach ($data as $key => $value) {
            if(isset($goodsSpec[$value['spec_id']])){
                if(!isset($goodsSpec[$value['spec_id']]['attr'][$value['addr_id']])){
                    $goodsSpec[$value['spec_id']]['attr'][$value['addr_id']] = $value['addr_title'];
                }
            } else {
                $goodsSpec[$value['spec_id']]['spec'] = $value['spec_title'];
                if(!isset($goodsSpec[$value['spec_id']]['attr'][$value['addr_id']])){
                    $goodsSpec[$value['spec_id']]['attr'][$value['addr_id']] = $value['addr_title'];
                }

            }
        }
        
        return $goodsSpec;
    }
}