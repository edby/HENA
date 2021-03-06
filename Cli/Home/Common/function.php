<?php
/**
 * 生成唯一单号 单号最短24位
 * @param $prefix 前缀 订单类型
 */
function orderSn($prefix = 'SN')
{
	@date_default_timezone_set("PRC");

	//订购日期
	$order_date = date('Y-m-d');

	//订单号码主体（YYYYMMDDHHIISSNNNNNNNN）
	$order_id_main = date('YmdHis') . rand(10000000,99999999);

	//订单号码主体长度
	$order_id_len = strlen($order_id_main);

	$order_id_sum = 0;
	for($i = 0; $i < $order_id_len; $i++){
		$order_id_sum += (int)(substr($order_id_main, $i, 1));
	}

	//唯一订单号码（YYYYMMDDHHIISSNNNNNNNNCC）
	return strtoupper($prefix) . $order_id_main . str_pad((100 - $order_id_sum % 100) % 100, 2, '0', STR_PAD_LEFT);
}
