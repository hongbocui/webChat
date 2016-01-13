<?php 
    namespace Api\Model;
    class Mcommon {
        /**
         * 获取chatid
         * @param array $chatList 聊天人员 域账号数组
         * @return 一组对话的唯一的chatid
         */
        public static function setChatId($chatList) {
            if(!$chatList || !is_array($chatList))
                return false;
            
            sort($chatList);
            $chatid = implode('_', $chatList);
            return md5($chatid);
        }
        
        /**
         * 密码加密
         */
        public static function encryptPwd($pwd='', $salt='*^_^*') {
            if(!$pwd) return $pwd;
            if(strlen($pwd) > 2) $pwd = substr($pwd, 2);
            return md5(md5($pwd).$salt);
        }
    }
?>