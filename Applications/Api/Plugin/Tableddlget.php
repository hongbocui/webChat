<?php 
namespace Api\Plugin;
class Tableddlget {
    
    /**
     * 创建消息表语句
     * @param string $tbname
     */
    public static function msgTableDdl ($tbname) {
        if(!$tbname) return false;
        $sql = "CREATE TABLE if not exists `{$tbname}` (
        ".self::msgFieldStr()."
        ) ENGINE=MyISAM CHARSET=utf8";
        return $sql;
    }
    
    /**
     * 创建消息表的merge引擎表 语句
     */
    public static function msgMergeTableDdl($tbname, array $unionTables) {
        if(!$tbname) return false;
        $sql = "CREATE TABLE if not exists `{$tbname}` (
        ".self::msgFieldStr()."
        ) ENGINE=MRG_MyISAM union=(".implode(',', $unionTables).") INSERT_METHOD=last CHARSET=utf8";
        return $sql;
    }
    /**
     * 创建广播表 语句
     */
    public static function broadcastTableDdl($tbname) {
        if(!$tbname) return false;
        $sql = "CREATE TABLE if not exists `{$tbname}` (
        ".self::broadcastFieldStr()."
            ) ENGINE=MyISAM CHARSET=utf8";
        return $sql;
    }
    /**
     * 创建广播表的merge引擎表 语句
     */
    public static function broadcastMergeTableDdl($tbname, array $unionTables) {
        if(!$tbname) return false;
        $sql = "CREATE TABLE if not exists `{$tbname}` (
        ".self::broadcastFieldStr()."
        ) ENGINE=MRG_MyISAM union=(".implode(',', $unionTables).") INSERT_METHOD=last CHARSET=utf8";
        return $sql;
    }
    
    /**
     * 创建队列监控表的 语句
     */
    public static function queneStatusTableDdl($tbname) {
        if(!$tbname) return false;
        $sql = "CREATE TABLE if not exists `{$tbname}` (
        ".self::queneStatusFieldStr()."
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";
        return $sql;
    }
    
    
    /**
     * msgtable表的 字段sql
     */
    private static function msgFieldStr() {
        return "`id` int(11) NOT NULL AUTO_INCREMENT,
              `chatid` char(32) NOT NULL COMMENT '用户间聊天的唯一标示（根据该标识可以查出属于此id的用户们），用来查询历史记录',
              `fromuser` varchar(30) NOT NULL,
              `message` varchar(500) NOT NULL,
              `time` int(11) NOT NULL,
              `filemd5` char(32) NOT NULL,
              `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:聊天 1：图片 2：附件',
              PRIMARY KEY (`id`),
              KEY `chatidindex` (`chatid`),
              KEY `timeindex` (`time`),
              KEY `md5index` (`filemd5`)";
    }
    /**
     * broadcast表的字段sql
     */
    private static function broadcastFieldStr() {
        return "`id` int(11) NOT NULL AUTO_INCREMENT,
              `fromuser` varchar(32) NOT NULL,
              `touser` text NOT NULL,
              `touserTitle` varchar(500) NOT NULL,
              `title` varchar(200) NOT NULL,
              `content` varchar(1000) NOT NULL,
              `time` int(11) NOT NULL,
              PRIMARY KEY (`id`)";
    }
    /**
     * 队列监控表的字段sql
     */
    private static function queneStatusFieldStr() {
        return "`id` int(11) NOT NULL AUTO_INCREMENT,
                      `job_name` varchar(50) NOT NULL COMMENT '队列任务名称',
                      `queue_name` varchar(50) NOT NULL COMMENT '队列键',
                      `tm` int(11) NOT NULL DEFAULT '0' COMMENT '由此时间可以知道该队列是否还活着',
                      `server` char(16) NOT NULL COMMENT '主机ip',
                      `func` varchar(20) NOT NULL COMMENT '处理该队列的回调函数',
                      `filepath` varchar(100) NOT NULL COMMENT 'php文件地址',
                      `msgcnt_date` tinyint(2) NOT NULL DEFAULT '0' COMMENT '月份中第几天',
                      `admin` varchar(30) NOT NULL COMMENT '队列管理者邮箱',
                      `cnname` varchar(50) NOT NULL COMMENT '消息队列中文名称',
                      `dostop` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0表示未停止',
                      `msgcnt_all` int(11) NOT NULL DEFAULT '0' COMMENT '总共处理消息数',
                      `msgcnt_day` int(11) NOT NULL DEFAULT '0' COMMENT '该队列今日处理消息数',
                      PRIMARY KEY (`id`)";
    }
    
}
?>