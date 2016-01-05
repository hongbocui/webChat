<?php
namespace Vendors\Redis;
use Config\Redis;
/**
 * Redis类
 */
class RedisModel{
    protected static $redisArr  = array();#创建的redis对象的集合
    protected static $redis     = false;  #当前方法操作的redis对象
    protected static $dalCfg    = false;

    public static function init($server = 'Default'){
        if (!isset(self::$redisArr[$server])){
            if (class_exists("\Redis")) {
                #获得链接信息
                $hostInfo        = Redis::$server[$server];
                if($hostInfo){
                    #连接redis
                    self::$redis    = new \Redis();
                    self::$redis->connect($hostInfo['host'],$hostInfo['port']);
                    self::$redisArr[$server] = self::$redis;
                }
            } else {
                die("Redis接口模块不可用");
            }
        }else{
            self::$redis = self::$redisArr[$server];
        }

    }
    /**
     * 获得Redis链接，可以直接用这个链接进行数据操作
     */
    public static function getLink($server){
        if(!Redis::$server[$server])return false;
        self::init($server);
        return self::$redis;
        
    }
    /*--------------------------------------------------------------------
                            key-value类型
    ---------------------------------------------------------------------*/
    /**
     * key-value 写
     */
    public static function set($server,$key,$value,$time=86400){
        
        if(!Redis::$server[$server] || !$key)return false;
        self::init($server);

        if ($time <> 0) {
            return self::$redis->setex($key,$time,$value);
        } else {
            return self::$redis->set($key,$value);
        }
    }

    /**
     * key-value 读
     */
    public static function get($server,$key){

        if(!Redis::$server[$server] || !$key)return false;
        self::init($server);
        return self::$redis->get($key);
    }
    /**
     * 自增
     */
    public static function increment($server, $key)
    {
        if(!Redis::$server[$server] || !$key)return false;
        self::init($server);
        return self::$redis->incr($key);
    }


    /**
     * 设置多值
     */
    public function setMulti($server,$key){

        if(!Redis::$server[$server] || !$key)return false;
        self::init($server);

        $keyArr = array();
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $keyArr[$k]   = $v;
            }
        }
        return self::$redis->mset($keyArr);
    }

    /**
     * 获取多值
     * @param subKey 传入的是数组
     */
    public function getMulti($server,$keyArr){
        if(!Redis::$server[$server])return false;
        self::init($server);


        $arr = self::$redis->mget($keyArr);
        if (is_array($arr)) {
            foreach ($arr as $key=>$row) {
                $arr[$key]  = $row;
            }
        }
        return $arr;
    }

    /**
     * 删除元素
     */
    public static function delete($server,$key){

        if(!Redis::$server[$server] || !$key)return false;
        self::init($server);        
        return self::$redis->delete($key);
    }


    /*--------------------------------------------------------------------
                                   hash类型
    ---------------------------------------------------------------------*/
    /**
     * 存单键值
     */
    public static function hashSet($server,$key,$subKey,$value,$time=86400)
    {
        if(!Redis::$server[$server] || !$key)return false;
        self::init($server);

        $re = self::$redis->hSet($key, $subKey, $value);
        if ($time > 0) {
            self::$redis->expire($key, $time);
        }
        return $re;
    }

    /**
     * 取值
     */
    public static function hashGet($server,$key, $subKey=false){
        if(!Redis::$server[$server] || !$key)return false;
        self::init($server);
        if(false === $subKey)
            return self::$redis->hGetAll($key);
        return self::$redis->hGet($key, $subKey);
    }

    /*--------------------------------------------------------------------
                                   set类型
    ---------------------------------------------------------------------*/
    /**
     * 增加集合元素
     */
    public static function sAdd($server,$key,$value,$time=86400){
        if(!Redis::$server[$server] || !$key)return false;
        self::init($server);

        $re     = self::$redis->sAdd($key, $value);#认为set的处理是不压缩的
        if ($time > 0) {
            self::$redis->expire($key, $time);
        }
        return $re;
    }

    /**
     * 删除一个指定的元素
     */
    public static function sDelete($server,$key , $value){
        if(!Redis::$server[$server] || !$key)return false;
        self::init($server);
        return self::$redis->sRemove($key, $value);
    }

    /**
     * 移动元素
     *
     * @param 要移动涉及的key $fromKey
     * @param 移动到的key $toKey
     * @param 元素 $value
     */
    public static function sMove($server ,$fromKey, $toKey, $value){

        if(!Redis::$server[$server])return false;
        self::init($server);

        return self::$redis->sMove($fromKey, $toKey, $value);
    }

    /**
     * 统计元素个数
     */
    public static function sSize($server,$key){
        
        if(!Redis::$server[$server] || !$key)return false;
        self::init($server);

        return self::$redis->sSize($key);
    }

    /**
     * 判断元素是否属于某个key
     */
    public static function sIsMember($server,$key, $value){

        if(!Redis::$server[$server] || !$key)return false;
        self::init($server);
        
        return self::$redis->sIsMember($key, $value);
    }

    /**
     * 求交集
     *
     * @param key集合 $keyArr
     */
    public static function sInter($server,$keyArr = array()){
        if(!Redis::$server[$server])return false;
        self::init($server);
       
       return self::$redis->sInter($keyArr);
    }

    /**
     * 求交集并存储到另外的key中
     *
     * @param key集合 $keyArr 'output', 'key1', 'key2', 'key3'
     */
    public static function sInterStore($server,$key,$ouput,$keyArr){

        if(!Redis::$server[$server] || !$key)return false;
        self::init($server);

        array_unshift($keyArr,$ouput);  #插入到数组的开头
        return call_user_func_array(array(self::$redis, "sInterStore"), $keyArr);
    }

    /**
     * 求并集
     *
     * @param key集合 $keyArr
     */
    public static function sUnion($server,$key,$keyArr = array()){

        if(!Redis::$server[$server] || !$key)return false;
        self::init($server);

        if($keyArr){
            return self::$redis->sUnion($keyArr);
        }
    }

    /**
     * 求差集 A-B的操作
     *
     * @param key集合 $keyArr
     */
    public static function sDiff($server,$key,$keyArr = array()){

        if(!Redis::$server[$server] || !$key)return false;
        self::init($server);


        if($keyArr){
            return self::$redis->sDiff($keyArr);
        }
    }
    /**
     * 获取当前key下的所有元素
     *
     * @param key集合 $key
     */
    public static function sMembers($server,$key){
        if(!Redis::$server[$server] || !$key)return false;
        self::init($server);

        return self::$redis->sMembers($key);
    }
    /*--------------------------------------------------------------------
     zset有序集合类型
     ---------------------------------------------------------------------*/
    /**
     * 增加有序集合元素
     * @param key 
     * @param score 用于对value排序
     */
    public static function zAdd($server, $key, $score, $value, $time=86400){
        if(!Redis::$server[$server] || !$key)return false;
        self::init($server);
    
        $re = self::$redis->zAdd($key, $score, $value);
        if ($time > 0) {
            self::$redis->expire($key, $time);
        }
        return $re;
    }
    /**
     * 删除名称为key的zset中score >= star且score <= end的所有元素，返回删除个数
     */
    public static function zRemRangeByScore($server, $key, $start, $end){
        if(!Redis::$server[$server] || !$key)return false;
        self::init($server);
        
        return self::$redis->zRemRangeByScore($key, $start, $end);
    }
    /**
     * 获取有序集合的数据
     * index 从$begin到$offset的值
     */
    public static function zrevrange($server, $key, $begin, $offset, $withscores = false){
        if(!Redis::$server[$server] || !$key)return false;
        self::init($server);
    
        return self::$redis->zrevrange($key, $begin, $offset, $withscores);
    }
}