<?php 
namespace Config;

/**
 * 数据库相关配置
 */
class Db
{
	//客服系统数据库  测试环境
    public static $itcrm = array(
		'host' => 'localhost',
		'port' => '3306',
		'user' => 'root',
		'password' => '111111',
		'dbname' => 'itcrm',
		'charset' => 'utf8',
	);
}
