<?php 
    namespace Api\Model;
    class Mmessage extends Abstractex{
        //表前缀
        public static $messagetablePre = 'webchat_message';
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
         * 获取某路聊天的历史记录
         * time/chatid 必须
         */
        public static function getChatMessage($paramArr) {
            $options = array(
                'limit'  => 20,     //limit
                'fields' => array(),//要查询的字段或者以 英文'，'分开
                'time'   => 0,      //时间戳、根据这个向前查询  必填
                'chatid' => '',     //要查询的chatid
                'order'  => 'order by id desc',
            );
            if (is_array($paramArr))$options = array_merge($options, $paramArr);
            extract($options);
            
            if(!$chatid || !$time) return false;
            $where = " where chatid='{$chatid}' ";
            $limit = $limit < 1 ? 'limit 20' : 'limit '.$limit;
            $tbname = self::getTbname($time);
            $formatData = self::setSelectField($fields);
            $where .= " and time<{$time} ";
            
            $sql = "select {$formatData} from {$tbname} {$where} {$order} {$limit}";
            return self::dbobj()->query($sql);
        }
        
        /**
         * 消息入库
         */
        public static function storeMessage($data = array()) {
            $formatData = self::setInsertCondition($data);
            $sql = "insert into ".self::getTbname()."({$formatData['fileds']}) values({$formatData['values']})";
            return self::dbobj()->query($sql);
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
                          PRIMARY KEY (`id`),
                          KEY `chatidindex` (`chatid`),
                          KEY `timeindex` (`time`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            return self::dbobj()->query($sql);
        }
        /**
         * 获取表名称
         */
        public static function getTbname($timestamp = '') {
            $time = $timestamp ? $timestamp : time();
            return self::$messagetablePre.date('Ym', $time);
        }
        /**
         * 检查表名称是否存在
         */
        public static function tbexists($tbname){
            $sql = "SHOW TABLES LIKE '".$tbname."'";
            return self::dbobj()->single($sql);
        }
        /****************************************
         *                redis操作                                   *
         ****************************************/
        /**
         * 用户离线消息队列中获取离线消息
         */
        public static function getUnreadMsg($usernanme,$num=50){
            if(!$usernanme || !$num) return false;
            $msgList = \Vendors\Redis\Redisq::pops(array(
                'serverName'  => self::$redisServer, #服务器名，参照见Redis的定义 ResysQ
                'key'         => $usernanme.':unread:msg',  #队列名
                'num'         => $num,      #多个数据
            ));
            if($msgList){
                $msgList = array_reverse($msgList, false);//反序，并丢弃原键名
                foreach($msgList as $key=>$val){
                    $msgList[$key] = unserialize($val);
                }
            }
            return $msgList;
        }
    }
?>