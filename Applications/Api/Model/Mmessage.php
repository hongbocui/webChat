<?php 
    namespace Api\Model;
    use Vendors\Redis\RedisModel;
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
         * 
         * 注意，如果是群聊天，则需要查到该用户的入群时间，早于该入群时间的消息时不能被查询到的。
         */
        public static function getMsgList($paramArr) {
            $options = array(
                'limit'  => 20,     //limit
                'time'   => 0,      //时间戳、根据这个向前查询  必填
                'chatid' => '',     //要查询的chatid
                'joinTime'=> '',    //用户的入群时间
                'type'    => 0,     //消息类型  Storekey::CHAT_MSG_TYPE
                'fields' => array(),//要查询的字段或者以 英文'，'分开
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
            if($joinTime)//如果是群聊则限制消息记录的时间
                $where .= " and time > {$joinTime} ";
            if($type)
                $where .= " and type = {$type} ";
            
            $sql = "select {$formatData} from {$tbname} {$where} {$order} {$limit}";
            return self::dbobj()->query($sql);
        }
        
        /**
         * 消息入库
         */
        public static function storeMessage($data = array()) {
            $formatData = self::setInsertCondition($data);
            $sql = "insert into ".self::getTbname($data['time'])."({$formatData['fileds']}) values({$formatData['values']})";
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
                          `message` varchar(500) NOT NULL,
                          `time` int(11) NOT NULL,
                          `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:聊天 1：广播 2：图片 3：附件',
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
         * 获取用户离线消息
         */
        public static function getUnreadMsg($username) {
            if(!$username) return false;
            return RedisModel::hashGet(self::$redisServer, $username.\Config\St\Storekey::UNREAD_MSG);
        }
        /**
         * 用户点击对话时删除该对话的离线消息
         */
        public static function delOneItemUnreadMsg($username, $chatid) {
            if(!$username || !$chatid) return false;
            return RedisModel::hashDel(self::$redisServer, $username.\Config\St\Storekey::UNREAD_MSG, $chatid);
        }
        /**
         * 获取某路聊天的最近的历史消息 
         */
        public static function getHistoryMsg($chatid){
            $historyList = \Vendors\Redis\Redisq::range(array(
                'serverName'  => 'webChat',     #
                'key'         => $chatid.\Config\St\Storekey::MSG_HISTORY,  #队列名
                'offset'      => 0,      #开始索引值
                'len'         => -1,      #结束索引值
            ));
            if(!$historyList) return false;
            
            $historyList = array_reverse($historyList, false);//反序，并丢弃原键名
            foreach($historyList as $key=>$val){
                $historyList[$key] = unserialize($val);
            }
            return $historyList;
        }
    }
?>