<?php 
    namespace Api\Model;
    /**
     * @author cuihb
     * // dbobj 中的几个方法 lastInsertId()、single($query = '',$params = null)、
     * row($query)、column($query)、query($query)
     */
    use \GatewayWorker\Lib\Db;
    class Mqueue extends Abstractex{
        public static $queuetable = 'queue_deamon_status';
        //数据库对象
        public static $db = null;
        
        public static function dbobj(){
            if(null === self::$db)
                self::$db = Db::instance('webChat');
            return self::$db;
        }
        /**
         * 自动建表
         */
        public static function createQueueTable() {
            $sql = "CREATE TABLE if not exists `".self::$queuetable."` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `job_name` varchar(50) NOT NULL COMMENT '队列任务名称',
                      `queue_name` varchar(50) NOT NULL COMMENT '队列键',
                      `tm` int(11) NOT NULL DEFAULT '0' COMMENT '由此时间可以知道该队列是否还活着',
                      `server` char(16) NOT NULL COMMENT '主机ip',
                      `func` varchar(20) NOT NULL COMMENT '处理该队列的回调函数',
                      `filepath` varchar(100) NOT NULL COMMENT 'php文件地址',
                      `msgcnt_date` tinyint(2) NOT NULL DEFAULT '0' COMMENT '月份中第几天',
                      `admin` varchar(30) NOT NULL COMMENT '队列管理者邮箱',
                      `cnname` varchar(50) NOT NULL COMMENT '消息队列中文名称',
                      `dostop` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0表示未停止',
                      `msgcnt_all` int(11) NOT NULL DEFAULT '0' COMMENT '总共处理消息数',
                      `msgcnt_day` int(11) NOT NULL DEFAULT '0' COMMENT '该队列今日处理消息数',
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";
            return self::dbobj()->query($sql);
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