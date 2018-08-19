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

/**
 * 生成随机字符串
 * @param int  $length  要生成的随机字符串长度
 * @param string  $type    随机码类型：0，数字+大小写字母；1，数字；2，小写字母；3，大写字母；4，特殊字符；-1，数字+大小写字母+特殊字符
 * @return string
 */
function randCode($length = 5, $type = 0) {
    $arr = [
    	1 => "0123456789",
    	2 => "abcdefghijklmnopqrstuvwxyz", 
    	3 => "ABCDEFGHIJKLMNOPQRSTUVWXYZ", 
    	4 => "~@#$%^&*(){}[]|"];

    if ($type == 0) {
        array_pop($arr);
        $string = implode("", $arr);
    } elseif ($type == "-1") {
        $string = implode("", $arr);
    } else {
        $string = $arr[$type];
    }

    $count = strlen($string) - 1;
    
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $string[rand(0, $count)];
    }

    return $code;
}

 /**
 * 数组转xls格式的excel文件
 * @param  array  $data      需要生成excel文件的数组
 * @param  string $filename  生成的excel文件名
 *      示例数据：
        $data = array(
            array(NULL, 2010, 2011, 2012),
            array('Q1',   12,   15,   21),
            array('Q2',   56,   73,   86),
            array('Q3',   52,   61,   69),
            array('Q4',   30,   32,    0),
           );
 */
function createXls($data, $filename='simple.xls'){
    ini_set('max_execution_time', '0');
    Vendor('PHPExcel.PHPExcel');

    $filename=str_replace('.xls', '', $filename).'.xls';
    
    $phpexcel = new PHPExcel();
    $phpexcel->getProperties()
//      右键属性所显示的信息
        ->setCreator("By LeChuang") //作者
        ->setLastModifiedBy("By LeChuang") //最后一次保存者
        ->setTitle("excel")//标题
        ->setSubject("excel")//主题
        ->setDescription("excel")//描述
        ->setKeywords("excel php") //标记
        ->setCategory("result file"); //类别
    $phpexcel->getActiveSheet()->fromArray($data);
    $phpexcel->getActiveSheet()->setTitle('Sheet1');
    $phpexcel->setActiveSheetIndex(0);
    
    header('Content-Type: application/vnd.ms-excel');
    header("Content-Disposition: attachment;filename=$filename");
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
    header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
    header ('Pragma: public'); // HTTP/1.0
    
    $objwriter = PHPExcel_IOFactory::createWriter($phpexcel, 'Excel5');
    $objwriter->save('php://output');
    
    exit;
}

/**
 * 数据转csv格式的excel
 * @param  array $data      需要转的数组
 * @param  string $header   要生成的excel表头
 * @param  string $filename 生成的excel文件名
 *      示例数组：
$data = array(
'1,2,3,4,5',
'6,7,8,9,0',
'1,3,5,7,9'
);
$header='用户名,密码,头像,性别,手机号';
 */
function createCsv($data,$header=null,$filename='orderlist.csv'){
    // 如果手动设置表头；则放在第一行
    if (!is_null($header)) {
        array_unshift($data, $header);
    }
    // 防止没有添加文件后缀
    $filename=str_replace('.csv', '', $filename).'.csv';
    ob_clean();
    Header( "Content-type:  application/octet-stream ");
    Header( "Accept-Ranges:  bytes ");
    Header( "Content-Disposition:  attachment;  filename=".$filename);
    foreach( $data as $k => $v){


        // 如果是二维数组；转成一维
        if (is_array($v)) {
            $v['order_sn'] = substr($v['order_sn'],6);
            $v=implode(',', $v);
        }
        // 替换掉换行
        $v=preg_replace('/\s*/', '', $v);
        // 解决导出的数字会显示成科学计数法的问题
        $v=str_replace(',', "\t,", $v);
        // 转成gbk以兼容office乱码的问题
        echo iconv('UTF-8','GBK',$v)."\t\r\n";
    }
}

/**
 * 设置金额 
 */
function setCurr($money)
{
    return number_format($money, 2, '.', '');
}

/**
 * 对emoji表情转义
 * @param $str
 * @return string
 */
function emoji_encode($str){
    $strEncode = '';

    $length = mb_strlen($str,'utf-8');

    for ($i=0; $i < $length; $i++) {
        $_tmpStr = mb_substr($str,$i,1,'utf-8');
        if(strlen($_tmpStr) >= 4){
            $strEncode .= '[[EMOJI:'.rawurlencode($_tmpStr).']]';
        }else{
            $strEncode .= $_tmpStr;
        }
    }
    return $strEncode;
}

/**
 * 对emoji表情转反义
 * @param $str
 * @return null|string|string[]
 */
function emoji_decode($str){
    $strDecode = preg_replace_callback('|\[\[EMOJI:(.*?)\]\]|', function($matches){
        return rawurldecode($matches[1]);
    }, $str);

    return $strDecode;
}

/**
 * curl get 请求
 * @param $url
 * @return mixed
 */
function getJson($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    curl_close($ch);

    return json_decode($output, true);
}