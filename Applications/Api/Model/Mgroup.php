<?php 
    namespace Api\Model;
    use \Vendors\Redis\RedisModel;
        
    class Mgroup extends Abstractex {
        //群生命周期 一个月
        public static $groupLife = 2592000;
        /**
         * 建群，设置/修改 群基本信息
         */
        public static function setGroup($paramArr){
            $options = array(
                'master'  => '',//群主账号
                'uuid'    => '',//唯一的id标示,可以是时间戳
                'title'   => '',//群名称
            );
            if (is_array($paramArr))$options = array_merge($options, $paramArr);
            extract($options);
            //群基本信息key
            $key = self::groupInfoKey($master, $uuid);
            if(!$key) return false;
            RedisModel::hashSet(self::$redisServer, $key, 'master', $master, 0);
            RedisModel::hashSet(self::$redisServer, $key, 'uuid', $uuid, 0);
            if($title)
                RedisModel::hashSet(self::$redisServer, $key, 'title', $title, 0);
            if(!RedisModel::hashExists(self::$redisServer, $key, 'ctime'))
                RedisModel::hashSet(self::$redisServer, $key, 'ctime', time(), 0);//创建时间
            RedisModel::hashSet(self::$redisServer, $key, 'mtime', time(), self::$groupLife); //修改时间
            
            return RedisModel::exists(self::$redisServer, $key);
        }
        
        /**
         * 向群中添加用户、删除用户
         */
        public static function setGroupMembers($paramArr) {
            $options = array(
                'master'  => '',//群主账号
                'uuid'    => '',//唯一的id标示,可以是时间戳
                'type'    => 'add', //add、del 向群众添加或者删除用户 
                'userList'=> array(),//要向群中添加的用户 
                'joinTime'=> time(),//用户入群时间
            );
            if (is_array($paramArr))$options = array_merge($options, $paramArr);
            extract($options);
            if(!is_array($userList)) return false;
            $key = self::groupMembersKey($master, $uuid);
            if(!$key) return false;
                       
            foreach($userList as $user) {
                if($type === 'add'){
                    RedisModel::hashSet(self::$redisServer, $key, $user, $joinTime, 0);
                }elseif ($type === 'del') {
                    RedisModel::hashDel(self::$redisServer, $key, $user);
                }
            }
            return RedisModel::exists(self::$redisServer, $key);
        }
        /**
         * 设置群的生存时间
         */
        public static function setGroupExpire($paramArr) {
            $options = array(
                'master' => '',//群主账号
                'uuid'   => '',//唯一的id标示,可以是时间戳
            );
            if (is_array($paramArr))$options = array_merge($options, $paramArr);
            extract($options);
            if(!$time) return false;
            $key1 = self::groupInfoKey($master, $uuid);
            $key2 = self::groupMembersKey($master, $uuid);
            RedisModel::expire(self::$redisServer, $key1, self::$groupLife);
            RedisModel::expire(self::$redisServer, $key2, self::$groupLife);
        }
        /**
         * 获取群的基本信息
         */
        public static function getGroupInfo($paramArr) {
            $options = array(
                'master' => '',//群主账号
                'uuid'   => '',//唯一的id标示,可以是时间戳
            );
            if (is_array($paramArr))$options = array_merge($options, $paramArr);
            extract($options);
            $key = self::groupInfoKey($master, $uuid);
            return RedisModel::hashGet(self::$redisServer, $key);
        }
        /**
         * 获取群成员信息
         */
        public static function getGroupMembers($paramArr) {
            $options = array(
                'master' => '',//群主账号
                'uuid'   => '',//唯一的id标示,可以是时间戳
            );
            if (is_array($paramArr))$options = array_merge($options, $paramArr);
            extract($options);
            $key = self::groupMembersKey($master, $uuid);
            return array_keys(RedisModel::hashGet(self::$redisServer, $key));
        }
        /**
         * 获取群中某个成员的入群时间
         */
        public static function getJoinTime($paramArr) {
            $options = array(
                'master'    => '',
                'uuid'      => '',
                'accountid' => ''
            );
            if (is_array($paramArr))$options = array_merge($options, $paramArr);
            extract($options);
            if(!$accountid || !$master || !$uuid) return false;
            $key = self::groupMembersKey($master, $uuid);
            return RedisModel::hashGet(self::$redisServer, $key, $accountid);
        }
        /**
         * 删除最近联系人
         * 给出一个用户名（用户名组）和一个chatid，将属于该用户名下最近联系人为chatid的都删除掉（踢出群）
         */
        public static function remRecentMembers($chatid, $userList) {
            if(!is_array($userList)) return false;
            foreach($userList as $userid) {
                RedisModel::zRem(self::$redisServer, $userid.\Config\St\Storekey::RECENT_MEMBERS, $chatid);
            }
            return true;
        }
        
        /**
         * 生成群基本信息存储的redis键值
         * @return boolean|string
         */
        public static function groupInfoKey($master, $uuid) {
            if(!$master || !$uuid) return false;
            return $master.$uuid.\Config\St\Storekey::GROUP_BASEINFO;
        }
        /**
         * 生成群成员信息存储的redis键值
         */
        public static function groupMembersKey($master, $uuid) {
            if(!$master || !$uuid) return false;
            return $master.$uuid.\Config\St\Storekey::GROUP_MEMBERS;
        }
    }
?>