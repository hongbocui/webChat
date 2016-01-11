<?php 
    namespace Api\Controler;
    use Api\Model\Mmessage;
    
    /**
     * 聊天系统用户表相关
     */
    class Message extends Abstractex {
        /**
         * 获取一个或者多个用户的信息
         */
        public function doTest(){
            var_dump(Mmessage::getRecentMembers('cuihb'));die;
            var_dump(Mmessage::getChatMessage(array(
                'time'   => 1451932905, //时间戳、根据这个向前查询  必填
                'chatid' => '994157c8b4188b6f6d2920d0bbb2f28c', //要查询的chatid
            ))); 
        }
        
        /**
         * 获取用户的离线消息
         */
        public function doGetUnreadMsg() {
            $username = $this->toStr('username');
            $num      = $this->toInt('num');
            if(!$username) return false;
            $num = $num ? $num : 100;
            
            $unreadMsg = Mmessage::getUnreadMsg($username, $num);
            echo json_encode($unreadMsg);
        }
    }
?>