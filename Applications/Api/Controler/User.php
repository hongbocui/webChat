<?php 
    namespace Api\Controler;
    use Api\Model\Muser;
    
    /**
     * 聊天系统用户表相关
     */
    class User extends Abstractex {
        public function doTest() {
            $accountid = $this->toStr('accountid');
            if(!$accountid) return false;
            $data = Muser::getUserinfo(array(
                'accountid' => $accountid,
            ));
            var_dump($data);
        }
        /**
         * 获取最近联系人列表
         */
        public function doRecentContact() {
            $username = $this->toStr('username');
            $num      = $this->toInt('num');
            
            if(!$username) return false;
            $num = $num ? $num-1 : 19;
            $recentUsers = Muser::getRecentMembers($username, $num);
            echo json_encode($recentUsers);
        }
        /**
         * 获取一个或者多个用户的信息
         */
        public function doGetOneOrMore(){
            $accountid = $this->toStr('accountid');
            
            if($accountid) {
                $data = Muser::getUserinfo(array(
                    'accountid' => $accountid,
                ));
            } else {
                $data = Muser::getUserinfo(array(
                    'fields' => array('accountid'),
                ));
                foreach($data as $key=>$val){
                    $data[$key] = $val['accountid'];
                }
            }
            echo json_encode($data);
        }
        /**
         * 获取所有在线用户列表
         */
        public function doOnlineUsers() {
            $key = \Config\St\Storekey::USER_ONLINE_LIST;
            $store = \GatewayWorker\Lib\Store::instance("gateway");
            $ret = $store->hGetAll($key);
            if(false === $ret)
            {
                return false;
            }
            sort($ret);
            echo json_encode($ret);
        }
    }
?>