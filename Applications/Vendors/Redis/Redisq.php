<?php
namespace Vendors\Redis;
/**
 * Redis队列操作封装
 */
use Vendors\Redis\RedisModel;
class Redisq
{  
    /**
     * 获得队列中现有key的数量
     */
    public static function getSize($paramArr){
        $options = array(
            'serverName'    => '', #服务器名，参照见Redis的定义 ResysQ
            'key'      => false,  #队列名
        );
        if (is_array($paramArr))$options = array_merge($options, $paramArr);
        extract($options);
        if(!$serverName || !$key)return false;
        
        $redis = RedisModel::getLink($serverName);
        return $redis->lSize($key);
    }
    
    /**
     * 尾部插入数据
     */
    public static function rpush($paramArr){
        $options = array(
            'serverName'    => '', #服务器名，参照见Redis的定义 ResysQ
            'key'      => false,  #队列名
            'value'    => false,  #插入队列的数据
            'time'     => 864000, #10天
        );
        if (is_array($paramArr))$options = array_merge($options, $paramArr);
        extract($options);
        if(!$serverName || !$key)return false;
        $redis = RedisModel::getLink($serverName);
        $re = $redis->rpush($key,$value);
        if ($time > 0) {
            $redis->expire($key, $time);
        }
        return $re;
    }
    /**
     * 头部插入数据
     */
    public static function lpush($paramArr){
        $options = array(
            'serverName'    => '', #服务器名，参照见Redis的定义 ResysQ
            'key'      => false,  #队列名
            'value'    => false,  #插入队列的数据
            'time'     => 864000, #10天
        );
        if (is_array($paramArr))$options = array_merge($options, $paramArr);
        extract($options);
        if(!$serverName || !$key)return false;
        $redis = RedisModel::getLink($serverName);
        $re = $redis->lpush($key,$value);
        if ($time > 0) {
            $redis->expire($key, $time);
        }
        return $re;
    }

    /**
     * 获得数据
     */
    public static function pop($paramArr){
        $options = array(
            'serverName'    => '', #服务器名，参照见Redis的定义 ResysQ
            'key'      => false,  #队列名
        );
        if (is_array($paramArr))$options = array_merge($options, $paramArr);
        extract($options);

        $redis = RedisModel::getLink($serverName);
        return $redis->lpop($key);
    }

     /**
     * 获得数据
     */
    public static function pops($paramArr){
        $options = array(
            'serverName'  => '', #服务器名，参照见Redis的定义 ResysQ
            'key'         => false,  #队列名
            'num'         => 2,      #多个数据
        );
        if (is_array($paramArr))$options = array_merge($options, $paramArr);
        extract($options);

        if($key){
            $data = array();
            for($i = 1; $i<=$num; $i++){
                $d = self::pop($paramArr);
                if(!$d)break;
                $tmpK = is_array($d) ? md5(serialize($d)) : md5($d);
                $data[$tmpK] = $d; #md5 为了防止重复，只保留1条
            }
            return $data;
        }
    }
    
    
    /**
     * 获得队列 列表详情，不弹出，只是查看
     * 
     */
    
    public static function range($paramArr){
        $options = array(
            'serverName'  => '',     #服务器名，参照见Redis的定义 ResysQ
            'key'         => false,  #队列名
            'offset'      => 0,      #开始索引值
            'len'         => 2,      #结束索引值
        );
        if (is_array($paramArr))$options = array_merge($options, $paramArr);
        extract($options);
        
        if(!is_numeric($offset)  ||  !is_numeric($len) || empty($key) || empty($serverName)){
            return false;
        }
        
        $redis = RedisModel::getLink($serverName);
        $data  = array();
        $data  = $redis ->lRange($key,$offset,$len);
        return $data;
    }
    /**
     * 要保留的list，与lpush结合使用，保证list中最新的几条
     */
    public static function ltrim($paramArr){
        $options = array(
            'serverName'  => '',     #服务器名，参照见Redis的定义 ResysQ
            'key'         => false,  #队列名
            'offset'      => 0,      #开始索引值
            'len'         => 2,      #结束索引值
        );
        if (is_array($paramArr))$options = array_merge($options, $paramArr);
        extract($options);
        
        if(!is_numeric($offset)  ||  !is_numeric($len) || empty($key) || empty($serverName)){
            return false;
        }
        $redis = RedisModel::getLink($serverName);
        $data  = array();
        $data  = $redis ->ltrim($key,$offset,$len);
        return $data;
    }
    
    /**
     * 获得队列大小
     */
    
    public static function lSize($paramArr){
        $options = array(
            'serverName'  => '',     #服务器名，参照见Redis的定义 ResysQ
            'key'         => false,  #队列名
        );
        if (is_array($paramArr))$options = array_merge($options, $paramArr);
        extract($options);
        
        if(empty($key) || empty($serverName)){
            return false;
        }
        
        $redis = RedisModel::getLink($serverName);
        $data  = 0;
        $data  = $redis ->lSize($key);
        return $data;
    }
}
