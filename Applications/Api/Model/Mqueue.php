<?php 
    namespace Api\Model;
    /**
     * @author cuihb
     * // dbobj 中的几个方法 lastInsertId()、single($query = '',$params = null)、
     * row($query)、column($query)、query($query)
     */
    class Mqueue extends Abstractex{
        public static $queuetable = 'queue_deamon_status';
        /**
         * 自动建表
         */
        public static function createQueueTable() {
            $sqlStr = \Api\Plugin\Tableddlget::queneStatusTableDdl(self::$queuetable);
            return self::dbobj()->query($sqlStr);
        }
        /**
         * 获取某个队列的信息信息
         * @param unknown $paramArr
         */
        public static function getQueueInfo($paramArr) {
            $options = array(
                'jobName'   => '',
                'queueName' => '',
                'fields'     => array(),
            );
            if (is_array($paramArr))$options = array_merge($options, $paramArr);
            extract($options);
            $formatData = self::setSelectField($fields);
            
            $where = ' where 1=1 ';
            if($jobName)
                $where .= " and job_name='{$jobName}' ";
            if($queueName)
                $where .= " and queue_name='{$queueName}' ";
            
            $sql = "select {$formatData} from ".self::$queuetable." {$where}";
            return self::dbobj()->query($sql);
        }
    }
?>