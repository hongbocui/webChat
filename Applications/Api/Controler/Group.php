<?php 
    namespace Api\Controler;
    use Api\Model\Mgroup;
    /**
     * 群相关
     */
    class Group extends Abstractex {
        /**
         * 建群  'group:'.$master.$uuid.':info'
         * 请求参数  是否必须  类型(示例)  说明
         * chatid  true  string(cuihb-165726534)  唯一的groupid,可以是群主-时间戳
         * title   false string    为群取的名字
         * 
         * 返回结果
         * res.code=1 成功 ；res.code=0失败
         */
        public function doSet() {
            $chatid = $this->toStr('chatid');
            $group  = explode('-', $chatid);
            if(strpos($chatid, '--') > -1)
                $this->_success('-1','error:群chatid不能包含--', '0');
            $data = Mgroup::setGroup(array(
                        'master' => $group[0],
                        'uuid'   => $group[1],
                        'title'  => $this->toStr('title')
                    ));
            return $data ? $this->_success('1', 'success', '1') : $this->_success('-1','error', '0');
        }
        
        
        /**
         * 向群中添加或者删除成员  'group:'.$master.$uuid.':members'
         * 请求参数     是否必须   类型(示例)    说明
         * chatid  true  string(cuihb-165726534)  唯一的groupid,可以是群主-时间戳
         * type    true   string      add/del  添加/删除 成员(成员存在的形式 cuihb=》时间戳)
         * users   true  string      向群中添加或者删除的成员 用‘-’分割，cuihb-xieyx-huocc
         * 
         * 返回结果
         * res.code=1 成功 ；res.code=0失败
         */
        public function doMembers() {
            $userList = explode('-', $this->toStr('users'));
            $chatid = $this->toStr('chatid');
            $group  = explode('-', $chatid);
            $data = Mgroup::setGroupMembers(array(
                'master' => $group[0],
                'uuid'   => $group[1],
                'type'   => $this->toStr('type'),
                'userList' => $userList,
            ));
            return $data ? $this->_success('1', 'success', '1') : $this->_success('-1','error', '0');
        }
        
        /**
         * 获取群的基本信息以及成员信息
         * 
         * 请求参数     是否必须   类型(示例)    说明
         * chatid  true  string(cuihb-165726534)  唯一的groupid,可以是群主-时间戳
         * 
         * 返回结果
         * data.info 群基本信息
         * data.members 群成员
         */
        public function doGetInfo() {
            $chatid = $this->toStr('chatid');
            $group  = explode('-', $chatid);
            
            $master = $group[0];
            $uuid   = $group[1];
            if(!$master || !$uuid) return false;
            $outArr = array();
            $outArr['info'] = Mgroup::getGroupInfo(array(
                'master' => $master,
                'uuid'   => $uuid,
            ));
            $outArr['members']  = Mgroup::getGroupMembers(array(
                'master' => $master,
                'uuid'   => $uuid,
            ));
            $this->_success($outArr);
        }
        
        /**
         * 设置群的生存时间
         *
         * 请求参数     是否必须   类型(示例)    说明
         * chatid  true  string(cuihb-165726534)  唯一的groupid,可以是群主-时间戳
         *
         * 返回值 ：无返回值
         */
        public function doExpires() {
            $chatid = $this->toStr('chatid');
            $group  = explode('-', $chatid);
            Mgroup::setGroupExpire(array(
                'master' => $group[0],
                'uuid'   => $group[1],
            ));
        }
    }
?>