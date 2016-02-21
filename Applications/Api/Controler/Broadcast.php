<?php 
    namespace Api\Controler;
    use Api\Model\Mbroadcast;
    class Broadcast extends Abstractex {
        /**
         * 用户获取未读广播消息数量
         * 请求参数             是否必须            类型(示例)      说明
         * accountid   true      string        用户账号
         * 
         * 返回值json
         * data.data = num
         */
        public function doUnreadNum() {
            $accountid = $this->toStr('accountid');
            if(!$accountid) $this->_error('param error');
            $num = Mbroadcast::getUnreadBroadcast($accountid);
            $this->_success($num);
        }
        /**
         * 删除用户未读广播消息数量
         * 请求参数             是否必须            类型(示例)      说明
         * accountid   true      string        用户账号
         */
        public function doDelUnreadNum() {
            $accountid = $this->toStr('accountid');
            if(!$accountid) $this->_error('param error');
            Mbroadcast::delUnreadBroadcast($accountid);
            $this->_success('ok');
        }
    }
?>