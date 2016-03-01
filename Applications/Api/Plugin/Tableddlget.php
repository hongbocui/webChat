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
     * 创建的merge引擎 消息表的sql
     */
    public static function msgMergeTableDdl($tbname, array $unionTables) {
        if(!$tbname) return false;
        $sql = "CREATE TABLE if not exists `{$tbname}` (
        ".self::msgFieldStr()."
        ) ENGINE=MRG_MyISAM union=(".implode(',', $unionTables).") INSERT_METHOD=last CHARSET=utf8";
        return $sql;
    }
    /**
     * 创建广播表语句
     */
    public static function broadcastTableDdl($tbname) {
        if(!$tbname) return false;
        $sql = "CREATE TABLE if not exists `{$tbname}` (
        ".self::broadcastFieldStr()."
            ) ENGINE=MyISAM CHARSET=utf8";
        return $sql;
    }
    /**
     * 创建merge引擎  广播表的sql
     */
    public static function broadcastMergeTableDdl($tbname, array $unionTables) {
        if(!$tbname) return false;
        $sql = "CREATE TABLE if not exists `{$tbname}` (
        ".self::broadcastFieldStr()."
        ) ENGINE=MRG_MyISAM union=(".implode(',', $unionTables).") INSERT_METHOD=last CHARSET=utf8";
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
    
}
?>