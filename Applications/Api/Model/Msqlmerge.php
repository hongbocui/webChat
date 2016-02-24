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
    private static $mergeBdcFile    = 'bdc.merge';//用来查看merge的broadcast表（webchat_broadcast）是否存在
    //private static $msgTbFileName = 'msg.tbtime';
    //private static $brcTbFileName = 'brc.tbtime';
    
    /**
     * 判断并创建merge引擎的  msg表
     */
    public static function mergeMsgTable() {
        if(self::hasCreateMerge(self::$mergeMsgFile, date('Ym')))
            return true;
        $unionTables = self::getTables(self::$msgPrefix, self::$msgTbNum);
        if(!$unionTables) return false;
        self::dropTable(self::$msgPrefix);//先删除表
        $sqlStr = \Api\Plugin\Tableddlget::msgMergeTableDdl(self::$msgPrefix, $unionTables);
        echo $sqlStr;
        return self::dbobj()->query($sqlStr);
    }
    /**
     * 判断并创建merge引擎的  broadcast表
     */
    public static function mergeBdcTable() {
        if(self::hasCreateMerge(self::$mergeBdcFile, date('Y')))
            return true;
        $unionTables = self::getTables(self::$broadcastPrefix, self::$broadcastTbNum);
        if(!$unionTables) return false;
        self::dropTable(self::$broadcastPrefix);//先删除表
        $sqlStr = \Api\Plugin\Tableddlget::broadcastMergeTableDdl(self::$broadcastPrefix, $unionTables);
        return self::dbobj()->query($sqlStr);
    }
    
    /**
     * 创建merge表
     */
    public static function createMergeTable($sqlStr) {
        if(!$sqlStr) return false;
        
    }
    /**
     * 判断是否已经创建过merge表
     */
    public static function hasCreateMerge($fileName, $strInFile) {
        if(!$fileName || !$strInFile) return false;
        $apppath = self::getAppPath();
        $fpath = rtrim($apppath, '/').'/Config/Symbolfile/'.$fileName;
        if(!file_exists($fpath) || file_get_contents($fpath) !== $strInFile) {
            file_put_contents($fpath, $strInFile);
            return false;
        }else{
            return true;
        }
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
    /**
     * 删除现有的过时的 merge 引擎的表
     */
    public static function dropTable($tbname) {
        if(!$tbname) return false;
        if(self::dbobj()->single("SHOW TABLES LIKE '".$tbname."'")) {
            self::dbobj()->query("DROP TABLE ".$tbname);
        }
    }
}
?>