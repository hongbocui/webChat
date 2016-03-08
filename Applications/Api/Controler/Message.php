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
         *  time       false       string        根据这个时间查找,rangeTm时可以没有
         *  class      true       string         查看的消息类型 nomal/image/attach
         *  type       true        int           0:查以前的，1:查以后的
         *  accountid  true/false string        如果chatid是群组则必须(查询入群时间)。否则非必须
         *  rangeTm    false      string        查询的范围。month/months/year
         *  
         * 返回值 消息列表
         * array();
         */
        public function doList() {
            $chatid    = $this->toStr('chatid');
            $accountid = $this->toStr('accountid');
            
            $keywords  = $this->toStr('keywords');
            $time      = $this->toStr('time');
            $type      = $this->toStr('classType') ? $this->toStr('classType') : 'nomal';
            $selectType= $this->toInt('selectType');
            $rangeMode   = $this->toStr('rangeMode') ? $this->toStr('rangeMode') : '';
            
            //根据rangeMode获取最小时间与最大时间
            $timearr = self::getRangeTimestamp($rangeMode);
            $sTime   = $timearr['stime'];//范围中 最小时间
            $bTime   = $timearr['btime'];//范围中最大时间
            
            if(!$chatid) $this->_error('参数出错');
            $time = false === strpos($time, '-') ? $time : strtotime($time)+24*3600;
            //消息类型处理
            $allowType = array(Storekey::CHAT_MSG_TYPE=>'nomal',Storekey::IMAGE_MSG_TYPE=>'image',Storekey::ATTACH_MSG_TYPE=>'attach');
            if(!in_array($type, $allowType)) $this->_error('参数出错');
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
                'limit'   => 10,     //limit
                'fields'  => array('fromuser','message','time'),//要查询的字段或者以 英文'，'分开
                'time'    => $time,      //时间戳、根据这个向前查询 
                'chatid'  => $chatid,     //要查询的chatid
                'joinTime'=> $jointime,    //用户的入群时间
                'type'    => $type,
                'order'   => 'order by id desc',
                'selectType' => $selectType,
                'stime'   => $sTime,
                'btime'   => $bTime,
                'keywords'=> $keywords,
            ));
            $this->_success($msgList);
        }
        /**
         * 根据file的md5判断这个file是否已经上传过了
         * 请求参数            是否必须            类型(示例)    说明
         * filemd5      true      string      chatid
         * 
         * return
         * 存在：data.data=true 不存在 data.data=false
         */
        public function doMd5Exist() {
            $filemd5 = $this->toStr('filemd5');
            if(!$filemd5) $this->_error(0, false);
            Mmessage::filemd5Exist($filemd5) ? $this->_success(true) : $this->_error(0,false);
        }
        /**
         * 获取redis中存储的最近50条聊天历史记录
         *  请求参数            是否必须            类型(示例)    说明
         * chatid      true      string      chatid
         * 
         * 返回值
         * 历史消息中的历史
         */
        public function doHistory() {
            $chatid = $this->toStr('chatid');
            if(!$chatid) $this->_error('param error');
            $historyList = Mmessage::getHistoryMsg($chatid);
            $this->_success($historyList);
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
         * 某路未读消息数量加 1
         * 请求参数            是否必须            类型(示例)      说明
         * accountid  true      string(cuihb) 账户
         * chatid     true      string        添加的离线消息到chatid上
         * 
         * return
         * bool true/false
         */
        public function doAddUnreadNum() {
            $accoutid = $this->toStr('accountid');
            $chatid   = $this->toStr('chatid');
            if(!$accoutid || !$chatid) $this->_error('param error');
            Mmessage::addUnreadMsg($accoutid, $chatid, Storekey::UNREAD_MSG);
            $this->_success('ok');
        }
        
        private function getRangeTimestamp($mode, $date='') {
            $btime = time();
            switch ($mode) {
                case 'day': //一天
                    $date = date('Y-m-d', strtotime($date));
                    $btime = strtotime($date.' 23:59:59');
                    $stime = strtotime($date.' 00:00:00');
                case 'month': //一个月
                    $stime = $btime - 30*24*3600;
                    break;
                case 'months': //三个月
                    $stime = $btime - 90*24*3600;
                    break;
                case 'year'://一年
                    $stime = $btime - 360*24*3600;
                    break;
                default:return false;
            }
            return array('btime'=>$btime, 'stime'=>$stime);
        }
    }
?>