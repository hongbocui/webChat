<?php 
    namespace Api\Model;
    use Vendors\Redis\RedisModel;
    class Mmessage extends Abstractex{
        //表前缀
        public static $messagetablePre = 'webchat_message';
        /**
         * 获取某路聊天的历史记录
         * time/chatid 必须
         * 
         * 注意，如果是群聊天，则需要查到该用户的入群时间，早于该入群时间的消息时不能被查询到的。
         */
        public static function getMsgList($paramArr) {
            $options = array(
                'limit'     => 10,     //limit
                'time'      => 0,      //时间戳、根据这个向前查询  必填
                'stime'     => '',     //最小时间
                'btime'     => '',      //最大时间
                'chatid'    => '',     //要查询的chatid
                'joinTime'  => '',    //用户的入群时间
                'type'      => 0,     //消息类型  Storekey::CHAT_MSG_TYPE
                'selectType'=> 1, //向前查还是向后查
                'keywords'  => '',
                'fields'    => array(),//要查询的字段或者以 英文'，'分开
                'order'     => 'order by id desc',
            );
            if (is_array($paramArr))$options = array_merge($options, $paramArr);
            extract($options);
            
            if(!$chatid) return false;
            $where = " where chatid='{$chatid}' ";
            $limit = $limit < 1 ? 'limit 10' : 'limit '.$limit;
            $formatData = self::setSelectField($fields);
            
            if($stime && $btime && !$time) {
                $where .=  " and time>{$stime} and time<{$btime} ";
            } 
            //并非分页逻辑，都是以一个时间为查询的mode点，分为向前和向后两种type
            if($time){
                if($selectType){
                    $mode = '<';
                }else {
                    $mode = '>';
                    $order = " order by time asc ";
                }
                $where .= " and time{$mode}{$time} ";
            }
            if($joinTime)//如果是群聊则限制消息记录的时间
                $where .= " and time > {$joinTime} ";
            if($type)
                $where .= " and type = {$type} ";
            if($keywords)
                $where .= " and message like binary '%{$keywords}%' ";
            $tbname = self::$messagetablePre;
            \Api\Model\Msqlmerge::mergeMsgTable();
            if(!self::tbexists($tbname)) return false;
            $sql = "select {$formatData} from {$tbname} {$where} {$order} {$limit}";
            $res = self::dbobj()->query($sql);
            if(0 === $selectType)
                $res = array_reverse((array)$res);
            return $res;
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
         * 根据file的md5查看文件是否已经存在,用于文件秒传
         */
        public static function filemd5Exist($filemd5) {
            $tbname = self::$messagetablePre;
            \Api\Model\Msqlmerge::mergeMsgTable();
            if(!self::tbexists($tbname)) return false;
            $where = " where filemd5='{$filemd5}' ";
            $sql = "select id from {$tbname} {$where}";
            return self::dbobj()->single($sql);
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
            $sql = \Api\Plugin\Tableddlget::msgTableDdl($tbname);
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
         * 用户某路对话的离线消息数加 num 个
         * 每个用户有一个离线hash，hash中的键值分别是每路聊天对应的消息数量
         */
        public static function addUnreadMsg($username, $chatid, $partkey='', $num=1) {
            return RedisModel::hashIncrBy(self::$redisServer, $username.$partkey, $chatid, $num);
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