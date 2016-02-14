<?php 
    namespace Api\Model;
    class Mcommon {
        /**
         * 单人聊天获取chatid
         * @param array $chatList 聊天人员 域账号数组
         * @return 一组对话的唯一的chatid
         */
        public static function singleChatId($chatList) {
            if(!$chatList || !is_array($chatList))
                return false;
            
            sort($chatList);
            return implode('--', $chatList);
        }
        /**
         * 群组聊天获取chatid
         */
        public static function groupChatId($master, $uuid) {
            if(!$master || !$uuid) return false;
            return $master.'-'.$uuid;
        }
        
        /**
         * 密码加密
         */
        public static function encryptPwd($pwd='', $salt='*^_^||') {
            if(!$pwd) return $pwd;
            $pwd = strrev($pwd);
            $code = floor(substr(ord(substr($pwd, -1)), -1)/2);
            if(strlen($pwd) > 2*$code) $pwd = substr($pwd, -$code).substr($pwd, $code, -$code).substr($pwd, 0, $code);
            return md5(md5($pwd).$salt);
        }
    }
?>