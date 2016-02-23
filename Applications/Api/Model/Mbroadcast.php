<?php 
    namespace Api\Model;
    use Vendors\Redis\RedisModel;
    class Mbroadcast extends Abstractex {
        //表前缀
        public static $messagetablePre = 'webchat_broadcast';
        
        /**
         * 消息入库
         */
        public static function storeBroadcast($data = array()) {
            $formatData = self::setInsertCondition($data);
            $sql = "insert into ".self::getTbname($data['time'])."({$formatData['fileds']}) values({$formatData['values']})";
            return self::dbobj()->query($sql);
        }
        /**
         * 获取广播消息列表
         */
        public static function getList($paramArr) {
            $options = array(
                'accountid' => '',//用户账号
                'time'      => '',//根据这个时间向前查询
                'limit'     => 20, //默认每次查询20条
                'type'      => 1, //向前查还是向后查
                'fields'    => array(),//要查询的字段
                'order'     => 'order by id desc',
            );
            if (is_array($paramArr))$options = array_merge($options, $paramArr);
            extract($options);
            if(!$accountid || !$time) return false;
            $where = " where touser like '%-".$accountid."-%' ";
            
            $mode = $type ? '<' : '>';
            $where .= " and time{$mode}{$time} ";
            $limit = $limit < 1 ? 'limit 20' : 'limit '.$limit;
            $formatData = self::setSelectField($fields);
            $tbname = self::getTbname($time);
            if(false === self::tbexists($tbname))
                return false;
            $sql = "select {$formatData} from {$tbname} {$where} {$order} {$limit}";
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
            $sql = \Api\Plugin\Tableddlget::broadcastTableDdl($tbname);
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
        public static function delUnreadBroadcast($username) {
            if(!$username) return false;
            return RedisModel::delete(self::$redisServer, $username.\Config\St\Storekey::UNREAD_BROADCAST);
        }
        /**
         * 新增离线广播数据
         * 每个用户都有一个string类型的  键值 用来保存离线广播数量
         */
        public static function addUnreadBroadcastNum($username, $partkey='') {
            return RedisModel::increment(self::$redisServer, $username.$partkey);
        }
    }
?>