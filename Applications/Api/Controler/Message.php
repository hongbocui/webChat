<?php 
    namespace Api\Controler;
    use Api\Model\Mmessage;
    
    /**
     * 聊天系统用户表相关
     */
    class Message extends Abstractex {
        /**
         * 
         */
        public function doTest(){
            var_dump(Mmessage::getUnreadMsg('wangjx')); 
        }
        /**
         * 获取用户每路离线消息的数量
         */
        public function doUnreadMsg() {
            $username = $this->toStr('accountid');
            $num      = $this->toInt('num');
            if(!$username) return false;
            $num = $num ? $num : 100;
            $unreadMsg = Mmessage::getUnreadMsg($username, $num);
            $this->_success($unreadMsg);
        }
        
        /**
         * 获取用户的离线广播消息
         */
        public function doGetUnreadBroadcast() {
            $username = $this->toStr('accountid');
            $num      = $this->toInt('num');
            if(!$username) return false;
            $num = $num ? $num : 100;
            
            $unreadMsg = Mmessage::getUnreadBroadcast($username, $num);
            echo json_encode($unreadMsg);
        }
    }
?>