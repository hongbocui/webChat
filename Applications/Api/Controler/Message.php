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
            Mmessage::createTable(Mmessage::getTbname());
        }
    }
?>