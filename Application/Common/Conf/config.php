<?php
return array(
	//'配置项'=>'配置值'
	'URL_MODEL' => 2, //设置URL模式重写模式
	'DEFAULT_MODULE' => 'Home', //设置默认模块
	'MODULE_ALLOW_LIST' => array('Home', 'Admin'),

    'LOAD_EXT_CONFIG' => 'wechat,db',

    //增加自定义的模板替换配置信息
	'TMPL_PARSE_STRING' => array(
		'__PUBLIC_ADMIN__' => '/Public/Admin',
		'__PUBLIC_HOME__' => '/Public/Home',
	),
	'SHOW_PAGE_TRACE' => false,

	'SMS_CONFIG' => array(
		"sign" => '合纳共享', //审核通过的签名
		"key" => 'LTAI3TKBYETo7UT1', //阿里云生成的 accessKeyId
		"secret" => 'HmjzlssKnPBDLBkVMivYtwfWQd770d', // 阿里云生成的 accessKeySecret
	),

	// 配置图片域名
	'RESOURCES_DOMAIN' => 'http://img.htxnww.com'
);
