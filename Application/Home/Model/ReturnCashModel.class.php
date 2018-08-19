<?php
/**
 * Created by PhpStorm.
 * User: 杨虎成
 * Date: 2018/5/10
 * Time: 14:20
 */

namespace Home\Model;


class ReturnCashModel extends CommonModel
{
    protected $tableName = 'return_cash';

    public function getGoodsIds()
    {
        $list = $this->field('goods_id')->select();
        $ids = [];
        foreach ($list as $key => $val) {
            $ids[] = $val['goods_id'];
        }
        return $ids;
    }
}