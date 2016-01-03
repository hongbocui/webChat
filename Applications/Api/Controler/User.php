<?php 
    namespace Api\Controler;
    use Api\Model\Muser;
    
    /**
     * 聊天系统用户表相关
     */
    class User extends Abstractex {
        /**
         * 获取一个或者多个用户的信息
         */
        public function doInfo(){
            $accountid = $this->toStr('accountid');
            return $data = Muser::getUserinfo(array(
                'accountid' => $accountid,
            ));
        }
    }
?>