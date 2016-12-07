<?php 
namespace Config;

/**
 * 数据库相关配置
 */
class Db
{
	//客服系统数据库  测试环境
    public static $webChat = array(
		'host' => '127.0.0.1',
		'port' => '3306',
		'user' => 'puser', 
		'password' => '',
		'dbname' => 'webChat',
		'charset' => 'utf8',
	);
}
