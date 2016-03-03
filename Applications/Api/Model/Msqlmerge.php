<?php 
namespace Api\Model;
/**
 * 此model用于判断建立 查询消息时所需要的 merge引擎表
 * 
 * 因为消息表是以月来分表，所以只需每月建立一次即可
 */
class Msqlmerge extends Abstractex{
    /**
     * merge msg表12个表（msg每月分表）
     */
    private static $msgTbNum = 12;
    /**
     * msgtable 前缀
     */
    private static $msgPrefix = 'webchat_message';
    /**
     * merge broadcast表2个表（broadcast每年分表）
     */
    private static $broadcastTbNum = 2;
    /**
     * broadcast 表前缀
     */
    private static $broadcastPrefix = "webchat_broadcast";
    
    /**
     * 几个用来标示的文件
     */
    private static $mergeMsgFile = 'msg.merge';//用来查看merge的msg表（webchat_message）是否存在
    private static $mergeBdcFile = 'bdc.merge';//用来查看merge的broadcast表（webchat_broadcast）是否存在
    
    /**
     * 判断并创建merge引擎的  msg表
     */
    public static function mergeMsgTable() {
        //首先判断是否有要联合的表
        $unionTables = self::getTables(self::$msgPrefix, self::$msgTbNum);
        if(!$unionTables) return false;
        //第二才能判断需不需要建立表
        if(Mcommon::isStrInFile(self::$mergeMsgFile, date('Ym')))
            return true;
        //获取修改或者创建merge表的 ddl语句
        if(self::dbobj()->single("SHOW TABLES LIKE '".self::$msgPrefix."'"))
            $sqlStr = \Api\Plugin\Tableddlget::alterMergeDdl(self::$msgPrefix, $unionTables);
        else
            $sqlStr = \Api\Plugin\Tableddlget::msgMergeTableDdl(self::$msgPrefix, $unionTables);
        return self::dbobj()->query($sqlStr);
    }
    /**
     * 判断并创建merge引擎的  broadcast表
     */
    public static function mergeBdcTable() {
        //首先判断是否有要联合的表
        $unionTables = self::getTables(self::$broadcastPrefix, self::$broadcastTbNum);
        if(!$unionTables) return false;
        //第二才能判断需不需要建立表
        if(Mcommon::isStrInFile(self::$mergeBdcFile, date('Y')))
            return true;
        //获取修改或者创建merge表的 ddl语句
        if(self::dbobj()->single("SHOW TABLES LIKE '".self::$broadcastPrefix."'"))
            $sqlStr = \Api\Plugin\Tableddlget::alterMergeDdl(self::$broadcastPrefix, $unionTables);
        else
            $sqlStr = \Api\Plugin\Tableddlget::broadcastMergeTableDdl(self::$broadcastPrefix, $unionTables);
        return self::dbobj()->query($sqlStr);
    }
    /**
     * 如果没有创建过merge 引擎的msg表。则选取最近12个月的进行merge。
     * @param string $tablePrefix 要查询的表的前缀
     * @param int $num  要获取几个表
     */
    public static function getTables($tablePrefix, $num=12) {
        $sql = "show tables like '{$tablePrefix}2%'";
        $tables = self::dbobj()->column($sql);
        if(!$tables || $num<1) return false;
        rsort($tables); //反序排列
        $tables = array_slice($tables, 0, $num);
        return $tables;
    }
}
?>