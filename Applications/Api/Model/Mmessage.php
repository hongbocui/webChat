<?php 
    namespace Api\Model;
    class Mmessage extends Abstractex{
        //表前缀
        public static $messagetablePre = 'webchat_message';
        //数据库对象
        public static $db = null;
        
        public static function dbobj(){
            if(null === self::$db)
                self::$db = \GatewayWorker\Lib\Db::instance('webChat');
            return self::$db;
        }
        
        public static function storeMessage($data = array()) {
            $formatData = self::setInsertCondition($data);
            $sql = "insert into ".self::getTbname()."({$formatData['fileds']}) values({$formatData['values']})";
            self::dbobj()->query($sql);
        }
        /**
         * 
         * @param string $chatid
         */
        public static function getChatMessage($chatid) {
            $options = array(
                'page'        => 1,//当前页
                'pageSize'    => 20,//limit
                'fields'      => array(),//要查询的字段或者以 英文'，'分开
                'time'        => 0,//时间戳、根据这个向前查询  必填
                'chatid'      => '', //要查询的chatid
                'order'       => 'order by id desc',
            );
        }
        
        /**
         * 自动建表语句、判断是否有本月聊天表，没有则创建
         */
        public static function createTable($tbname) {
            if(false === self::tbexists($tbname)) 
                return self::createMessageTable($tbname);
        }
        /**
         * 自动建表
         */
        public static function createMessageTable($tbname) {
            $sql = "CREATE TABLE if not exists `{$tbname}` (
                          `id` int(11) NOT NULL AUTO_INCREMENT,
                          `chatid` char(32) NOT NULL COMMENT '俩用户间聊天的唯一标示（组合方法：参与用户名排序后MD5），用来查询历史记录',
                          `fromuser` varchar(30) NOT NULL,
                          `tousers` varchar(500) NOT NULL,
                          `message` varchar(500) NOT NULL,
                          `time` int(11) NOT NULL,
                          PRIMARY KEY (`id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            return self::dbobj()->query($sql);
        }
        /**
         * 获取表名称
         */
        public static function getTbname() {
            return self::$messagetablePre.date('Ym');
        }
        /**
         * 检查表名称是否存在
         */
        public static function tbexists($tbname){
            $sql = "SHOW TABLES LIKE '".$tbname."'";
            return self::dbobj()->single($sql);
        }
    }
?>