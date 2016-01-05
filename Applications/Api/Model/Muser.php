<?php 
    namespace Api\Model;
    /**
     * @author cuihb
     * // dbobj 中的几个方法 lastInsertId()、single($query = '',$params = null)、
     * row($query)、column($query)、query($query)
     */
    class Muser extends Abstractex{
        public static $usertable = 'webchat_user';
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
         * 获取所有用户或者某个用户信息
         * @param unknown $paramArr
         */
        public static function getUserinfo($paramArr) {
            $options = array(
                'fields'      => array(),//要查询的字段或者以 英文'，'分开
                'accountid'   => 0, //若有，则查该用户
                'isCount'     => 0, //是否是查询总数
            );
            if (is_array($paramArr))$options = array_merge($options, $paramArr);
            extract($options);
            $formatData = self::setSelectField($fields);
            
            $where = '';
            if($accountid)
                $where = " where accountid='{$accountid}' ";
            if($isCount) {
                $sql = " select count(*) from ".self::$usertable;
                return self::dbobj()->single($sql);
            }
            
            $sql = "select {$formatData} from ".self::$usertable." {$where}";
            return self::dbobj()->query($sql);
        }
        /****************************************
         *                redis操作                                   *
         ****************************************/
        /**
         * 获取最近的n个联系人
         */
        public static function getRecentMembers ($username, $num = 19) {
            return \Vendors\Redis\RedisModel::zrevrange(self::$redisServer, $username.':recentchat:members', 0, $num);
        }
    }
?>