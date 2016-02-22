<?php 
    namespace Api\Controler;
    use Api\Model\Mbroadcast;
    class Broadcast extends Abstractex {
        /**
         * 获取用户的广播消息列表
         * 请求参数             是否必须            类型(示例)      说明
         * accountid   true      string        用户账号
         * time        false     string        根据这个时间来向前查询消息记录，默认为当前时间
         * 
         * 返回值json
         * data.data = 广播消息列表
         */
        public function doList() {
            $accountid = $this->toStr('accountid');
            $time      = $this->toStr('time');
            if(!$time) $time = time();
            if(!$accountid) $this->_error('param error');
            $list = Mbroadcast::getList(array(
                'accountid' => $accountid,
                'time'      => $time,
                'fields'    => array('fromuser','touserTitle', 'title', 'content','time'),
            ));
            $this->_success($list);
        }
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