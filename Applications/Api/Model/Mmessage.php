<?php 
    namespace Api\Model;
    class Mmessage extends Abstractex{
        //表前缀
        public static $messagetablePre = 'webchat_message';
        
        /**
         * 
         * @param string $chatid
         */
        public static function getChatMessage($chatid) {
            $options = array(
                'page'        => 1,//当前页
                'pageSize'    => 20,//limit
                'fields'      => array(),//要查询的字段或者以 英文'，'分开
                'time'        => 0,//时间戳、根据这个向前查询
                'chatid'      => '', //要查询的chatid
                'order'       => 'order by id desc',
            );
        }
        
        /**
         * 自动建表语句、判断是否有本月聊天表，没有则创建
         */
        public static function createMessageTable() {
            $tableName = self::$messagetablePre.date('Ym');
        }
    }
?>