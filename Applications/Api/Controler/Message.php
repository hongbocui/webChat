<?php 
    namespace Api\Controler;
    use Api\Model\Mmessage;
    
    /**
     * 聊天系统用户表相关
     */
    class Message extends Abstractex {
        /**
         * 获取用户所有聊天对象离线消息的数量
         * 请求参数            是否必须            类型(示例)      说明
         * accountid  true       string(cuihb) 请求用户cuihb的离线消息
         * 
         * 返回值
         * array(
         *     $chatid1=>$num1
         *     $chatid2=>$num2
         * );
         */
        public function doUnreadMsg() {
            $username = $this->toStr('accountid');
            //$num      = $this->toInt('num');
            if(!$username) return false;
            //$num = $num ? $num : 100;
            $unreadMsg = Mmessage::getUnreadMsg($username);
            $this->_success($unreadMsg);
        }
        /**
         * 删除某一路的离线消息
         */
        public function doDelUnreadMsg() {
            Mmessage::delOneItemUnreadMsg($this->toStr('accountid'), $this->toStr('chatid'));
        }
        
        /**
         * 获取用户的离线广播消息
         *  请求参数            是否必须            类型(示例)      说明
         * accountid   true       string(cuihb) 请求用户cuihb的离线广播消息
         * num         false      int           要取的条数
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