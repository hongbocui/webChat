<?php 
    namespace Api\Model;
    class Muser extends Abstractex{
        public static $usertable = 'webchat_user';
        
        public static function getUserinfo($paramArr) {
            $options = array(
                'fields'      => array(),//要查询的字段或者以 英文'，'分开
                'accountid'   => 0,
                'isCount'     => 0, //是否是查询总数
            );
            if (is_array($paramArr))$options = array_merge($options, $paramArr);
            extract($options);
            $formatData = self::setSelectField($fields);
            
            $db = \GatewayWorker\Lib\Db::instance('webChat');
            $where = '';
            if($accountid)
                $where = " where accountid='{$accountid}' ";
            if($isCount) {
                $sql = " select count(*) from ".self::$usertable;
                return $db->single($sql);
            }
            
            $sql = "select {$formatData} from ".self::$usertable." {$where}";
            return $db->query($sql);
        }
    }
?>