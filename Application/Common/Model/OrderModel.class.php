<?php
namespace Common\Model;

/**
 * 订单模型
 */
class OrderModel extends CommonModel{

	// 数据表名
	protected $tableName = 'order';
	
	// 字段验证
	protected $_validate = [
		['goods_id', 'require', '商品id必须'],
		['address_id', 'require', '商品id必须'],
		//['order_sn','','订单号已存在', 0, 'unique', 1], // 订单号是否存在
     	['pay_type', [1,2],'不支持的支付类型', 2, 'in'] // 支付类型
	];

	
}