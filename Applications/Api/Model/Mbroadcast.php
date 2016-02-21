<?php 
    namespace Api\Model;
    use Vendors\Redis\RedisModel;
    class Mbroadcast extends Abstractex {
        //表前缀
        public static $messagetablePre = 'webchat_broadcast';
        //数据库对象
        public static $db = null;
        //redis服务器
        public static $redisServer = 'webChat';
        
        public static function dbobj(){
            if(null === self::$db)
                self::$db = \GatewayWorker\Lib\Db::instance('webChat');
            return self::$db;
        }
        
        /**
         * 消息入库
         */
        public static function storeBroadcast($data = array()) {
            $formatData = self::setInsertCondition($data);
            $sql = "insert into ".self::getTbname($data['time'])."({$formatData['fileds']}) values({$formatData['values']})";
            return self::dbobj()->query($sql);
        }
        
        /**
         * 自动建表语句、判断是否有本月聊天表，没有则创建
         */
        public static function createTable($tbname) {
            if(false === self::tbexists($tbname))
                return self::createBroadcastTable($tbname);
        }
        /**
         * 自动建表
         */
        public static function createBroadcastTable($tbname) {
            $sql = "CREATE TABLE if not exists `{$tbname}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `fromuser` varchar(32) NOT NULL,
                `touser` text NOT NULL,
                `title` varchar(200) NOT NULL,
                `content` varchar(1000) NOT NULL,
                `time` int(11) NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            return self::dbobj()->query($sql);
        }
        /**
         * 检查表名称是否存在
         */
        public static function tbexists($tbname){
            $sql = "SHOW TABLES LIKE '".$tbname."'";
            return self::dbobj()->single($sql);
        }
        /**
         * 获取表名称
         */
        public static function getTbname($timestamp = '') {
            $time = $timestamp ? $timestamp : time();
            return self::$messagetablePre.date('Y', $time);
        }
        /****************************************
         *                redis操作                                   *
         ****************************************/
        /**
         * 获取用户离线广播数量
         */
        public static function getUnreadBroadcast($username){
            if(!$username) return false;
            return RedisModel::get(self::$redisServer, $username.\Config\St\Storekey::UNREAD_BROADCAST);
        }
        /**
         * 删除用户离线广播消息数量
         */
        public function delUnreadBroadcast($username) {
            if(!$username) return false;
            return RedisModel::delete(self::$redisServer, $username.\Config\St\Storekey::UNREAD_BROADCAST);
        }
    }
?>