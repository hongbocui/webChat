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
    }
?>