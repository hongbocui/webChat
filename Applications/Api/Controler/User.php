<?php 
    namespace Api\Controler;
    use Api\Model\Muser;
    
    /**
     * 聊天系统用户相关
     */
    class User extends Abstractex {
        /**
         * 登录
         * 
         * 请求参数              是否必须             类型(示例)   说明
         * accountid   true       string(cuihb)  登录的用户名
         * pwd         true       string     用户密码
         * 
         * @return number 1/0
         */
        public function doLogin(){
            $username = $this->toStr('accounid');
            $password = $this->toStr('pwd');
            return Muser::userAuth($username, $password);
        }
        /**
         * 获取某个用户信息
         * 
         * 请求参数              是否必须             类型(示例)   说明
         * accountid   true       string(cuihb)  登录的用户名
         * 
         * 返回值 json
         * array(0=>array(
         *  用户信息
         * ))
         */
        public  function doOneUser(){
            $accountid = $this->toStr('accountid');
            $data = Muser::getUserinfo(array(
                'accountid' => $accountid,
            ));
            if($data) $this->_success($data);
            $this->_error('无用户信息');
        }
        /**
         * 获取某个部门用户账号信息
         * 
         * 请求参数              是否必须             类型(示例)   说明
         * dept        true      string(cuihb)  部门名称
         * 
         * 返回值 json
         * 部门所有用户信息 
         */
        public function doDeptUser() {
            $dept = $this->toStr('dept');
            $data = Muser::getUserinfo(array(
                'dept'   => $dept,
                'fields' => array('accountid'),
            ));
            if($data) $this->_success($data);
            $this->_error('部门信息有误');
        }
        /**
         * 获取所有用户信息
         * 仅支持最高6级部门
         * 无参数
         * 
         * 返回所有用户
         */
        public function doAllUsers(){
            $data = Muser::getUserinfo(array());
            $outArr = array();
            foreach((array)$data as $key=>$val) {
                $deptArr = explode(',', trim($val['deptDetail'], ','));
                $deep = count($deptArr);
                switch($deep) {
                    case 1: $outArr[$deptArr[0]][$val['accountid']] = $val['username']; 
                        break;
                    case 2:
                        $outArr[$deptArr[0]][$deptArr[1]][$val['accountid']] = $val['username'];
                        break;
                    case 3:
                        $outArr[$deptArr[0]][$deptArr[1]][$deptArr[2]][$val['accountid']] = $val['username'];
                        break;
                    case 4:
                        $outArr[$deptArr[0]][$deptArr[1]][$deptArr[2]][$deptArr[3]][$val['accountid']] = $val['username'];
                        break;
                    case 5:
                        $outArr[$deptArr[0]][$deptArr[1]][$deptArr[2]][$deptArr[3]][$deptArr[4]][$val['accountid']] = $val['username'];
                        break;
                    case 6:
                        $outArr[$deptArr[0]][$deptArr[1]][$deptArr[2]][$deptArr[3]][$deptArr[4]][$deptArr[5]][$val['accountid']] = $val['username'];
                        break;
                }
            }
            $this->_success($outArr);
        }
        /**
         * 获取最近联系人列表
         * 请求参数           是否必须          类型(示例)   说明
         * accountid  true     string(cuihb)  用户账号
         * num        false    int         要获取最近的num个联系人
         * 
         * return json
         * data.data = array(
         *  chatid1
         *  chatid2
         * );
         * 
         */
        public function doRecentContact() {
            $username = $this->toStr('accountid');
            $num      = $this->toInt('num');
        
            if(!$username) return false;
            $num = $num ? $num-1 : 19;
            $recentUsers = Muser::getRecentMembers($username, $num);
            $this->_success($recentUsers);
        }
        /**
         * 获取所有在线用户 账号列表
         * 
         * 无参数
         * 
         * 返回所有在线用户列表
         */
        public function doOnlineUsers() {
            $clientList = Muser::getOnlineUsers();
            if(false === $clientList)
                return false;
            $this->_success($clientList);
        }
    }
?>