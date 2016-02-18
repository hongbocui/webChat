<?php 
    namespace Api\Controler;
    use Api\Model\Mmessage;
    use Api\Model\Mgroup;
    use Config\St\Storekey;
            
    /**
     * 聊天系统用户表相关
     */
    class Message extends Abstractex {
        /**
         * 获取存储于数据库的永久历史聊天记录
         *  请求参数            是否必须            类型(示例)      说明
         *  chatid     true       string        属于该chatid下的聊天记录
         *  time       true       string        根据这个时间向前查找
         *  type       true       string        查看的消息类型 nomal/image/attach
         *  accountid  true/false string        如果chatid是群组则必须(查询入群时间)。否则非必须
         * 
         * 返回值 消息列表
         * array();
         */
        public function doMsgList() {
            $chatid    = $this->toStr('chatid');
            $time      = $this->toStr('time');
            $type      = $this->toStr('type');
            $accountid = $this->toStr('accountid');
            
            if(!$chatid || !$time) $this->_error('参数出错');
            //消息类型处理
            $allowType = array(Storekey::CHAT_MSG_TYPE=>'nomal',Storekey::IMAGE_MSG_TYPE=>'image',Storekey::ATTACH_MSG_TYPE=>'attach');
            if(!in_array($type, $allowType)) $this->_error('参数出错');;
            $type = array_search($type, $allowType);
            
            //如果是群聊天，获取用户进入该群的时间
            $jointime = '';
            if(false === strpos($chatid, '--')) {
                if(!$accountid) $this->_error('参数缺少accountid');
                $group  = explode('-', $chatid);
                $jointime = Mgroup::getJoinTime(array(
                    'master' => $group[0],
                    'uuid'   => $group[1],
                    'accountid' => $accountid
                ));
            }
            
            $msgList = Mmessage::getMsgList(array(
                'limit'  => 20,     //limit
                'fields' => array('fromuser','message','time'),//要查询的字段或者以 英文'，'分开
                'time'   => $time,      //时间戳、根据这个向前查询  必填
                'chatid' => $chatid,     //要查询的chatid
                'joinTime'=> $jointime,    //用户的入群时间
                'type'   => $type,
                'order'  => 'order by id desc',
            ));
            $this->_success($msgList);
        }
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
         * 请求参数            是否必须            类型(示例)      说明
         * accountid  true      string(cuihb) 请求用户cuihb的离线消息
         * chatid     true      string        要删除的某条离线消息
         * 
         * return
         * bool true/false
         */
        public function doDelUnreadMsg() {
            $accoutid = $this->toStr('accountid');
            $chatid   = $this->toStr('chatid');
            Mmessage::delOneItemUnreadMsg($accoutid, $chatid);
            $this->_success('ok');
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
            $this->_success($unreadMsg);
        }
    }
?>