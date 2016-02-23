<?php 
namespace Api\Model;
/**
 * 此model用于判断建立 查询消息时所需要的 merge引擎表
 * 
 * 因为消息表是以月来分表，所以只需每月建立一次即可
 */
class Msqlmerge extends Abstractex{
    /**
     * merge 12个月份的
     */
    private static $tbNum = 12;
    /**
     * 
     */
    private static $mergeTimeFile = 'merge.engine';
    /**
     * 判断本月是否已经创建过merge表
     */
    public static function hasCreateMerge() {
        $apppath = self::getAppPath();
        $fpath = rtrim($apppath, '/').'/Config/St/'.self::$mergeTimeFile;
        if(!file_exists($fpath)) {
            file_put_contents($fpath, date('Ym'));
            return false;
        }else{
            return file_get_contents($fpath) === date('Ym') ? true :false;
        }
    } 
    
}
?>