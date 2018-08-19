<?php
return array(
   //'配置项'=>'配置值'
   'URL_MODEL' => 2, //设置URL模式重写模式
   'DEFAULT_MODULE' => 'Home', //设置默认模块
   'MODULE_ALLOW_LIST' => array('Home', 'Admin'),
   //增加自定义的模板替换配置信息
   'TMPL_PARSE_STRING' => array(
      '__PUBLIC_ADMIN__' => '/Public/Admin',
      '__PUBLIC_HOME__' => '/Public/Home',
   ),
    
   //'SHOW_PAGE_TRACE' => false,
   /* 数据库设置 */
   'DB_TYPE' => 'mysql', // 数据库类型
   'DB_HOST' => '127.0.0.1', // 服务器地址
   'DB_NAME' => 'test', // 数据库名
   'DB_USER' => 'root', // 用户名
   'DB_PWD' => 'k0TfrKuyw6Qthv7h', // 密码
   'DB_PORT' => '3306', // 端口
   'DB_PREFIX' => 'hn_', // 数据库表前缀
   'SMS_CONFIG' => array(
      "sign" => '合纳共享', //审核通过的签名
      "key" => 'LTAI3TKBYETo7UT1', //阿里云生成的 accessKeyId
      "secret" => 'HmjzlssKnPBDLBkVMivYtwfWQd770d', // 阿里云生成的 accessKeySecret
   ),
   'WECHAT' => [
        'appid' => 'wx9de1809e83f53cce',
        'appKey' => '6293cc51a9e828c3b73b630a862b0671',
        'apiKey' => 'GnhK15jmHJnghkN5nnmjlkKJL58njklC',
        'mchid' => '1492083672',
        'certificate_path' => dirname(dirname(__FILE__)).'/Credential',
    ],
);
