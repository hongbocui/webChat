<?php 
    namespace Vendors\Queue;
    use Vendors\Redis\Redisq;
    class Util {
        //消息队列启动处理程序
        public static function mqHander($paramArr) {
            $options = array(
                'queueType'   => 'RedisQ',      #消息队列名称 默认是RedisQ MQ
                'serverName'  => 'ResysQ',      #ResysQ
                'queueName'   => '',      #要监听的消息队列名(key)
                'jobName'     => '',      #当前处理的job名称
                'cnName'      => '',      #中文名称
                'function'    => false,   #要运行的函数名
                'msgNumAtm'   => 10,      #每次处理的消息数，如果是多个会有合并处理
                'maxSleep'    => 30,      #没有消息的时候，deamon将sleep，如果队列消息不多，尽量设置大点，减少处理压力
                'adminMail'   => '',      #接受监控报警的邮件地址，多个地址逗号分割
                'eagleeyeDb'   => 'Eagleeye', #消息队列监控状态所在库
                'phpFile'     => '',      #php文件地址
                'life'        => 0,       #程序的生命周期，如果0表示是一直循环的Deamon处理，如果设置了时间，必须采用crontab的形式
            );
            
            if (is_array($paramArr))$options = array_merge($options, $paramArr);
            foreach ($options as $k => $v){#下面有sql操作，防注入处理
                $options[$k] = addslashes($v);
            }
            extract($options);
            
            if(!$function || !function_exists($function)){
                echo "[ERROR]数据处理函数function有误！";
                return false;
            }
            if(!$queueName){
                echo "[ERROR]消息队列名称不可为空！";
                return false;
            }
            if(!$jobName) $jobName = $queueName;
            
            $sleepSec = 0;
            $startTm  = 0;
            $life     = (int)$life;
            $maxSleep = (int)$maxSleep > 10 ? (int)$maxSleep : 10;
            
            if(isset($_SERVER['SERVER_ADDR'])){
                $serverIp = $_SERVER['SERVER_ADDR']; #当前服务器的IP
            }else{
                if(isset($_SERVER['SSH_CONNECTION']))
                    $serverIp = explode(" ", $_SERVER['SSH_CONNECTION']);
                $serverIp = isset($serverIp) ? $serverIp[2] : ''; #当前服务器的IP
            }
            $runCnt   = 0;
            $lifeStartTm  = time(); #计算生命期用
            #采用始终循环的方式进行处理
            while(true){
                #从队列中获得数据
                if('RedisQ' == $queueType){#判断队列的类型
                    $dataArr = Redisq::pops(array('serverName'=>$serverName, 'key'=>$queueName, 'num'=>$msgNumAtm));
                }
                if($dataArr){
                    foreach($dataArr as $d){
                        #执行要调用的函数
                        $function($d);
                        $runCnt++;    #记录处理消息的个数
                    }
                    $sleepSec = 0;
                }else{
                    $sleepSec = intval( ($sleepSec + 2) % $maxSleep); #递增，但限制最大sleep数字，不可等待过长
                    if($sleepSec == 0)$sleepSec = 1;
                    sleep($sleepSec);
                }
            
                #定时将自动运行的存活状态记录到数据库主中，便于监控和统计
                #用这种方法模拟心跳，让监控程序知道这循环还活着
                $tm = time();
                $mustDie = $life && ($tm - $lifeStartTm) > $life ? true : false; #判断生命期,是否该结束了
                if($tm - $startTm > 240 || $mustDie){
                    $db      = \GatewayWorker\Lib\Db::instance($eagleeyeDb); #便于记录运行状态用
                    $startTm = $tm;
                    $nowD    = date("d");
                    $sql     = "select * from queue_deamon_status where job_name = '{$jobName}' and queue_name = '{$queueName}' ";
                    $info    = $db->query($sql);
                    if($info && is_array($info)){
                        $info = $info[0];#因为我用的是workerman自带的mysql操作
                        #获得当天处理的消息数量
                        $todaycnt = $info['msgcnt_date'] != $nowD ? $runCnt : 'msgcnt_day + '.$runCnt;
                        $sql = "update queue_deamon_status
                        set tm = '$tm',server = '{$serverIp}',func='{$function}',filepath='{$phpFile}',admin='{$adminMail}',dostop=0,
                        msgcnt_all = msgcnt_all + {$runCnt} , msgcnt_day = {$todaycnt},msgcnt_date='$nowD',cnname='{$cnName}'
                        where job_name = '{$jobName}' and queue_name = '{$queueName}' ";
            
                        $db->query($sql);
                        #判断是否要停止,这个标志在后台中设置
                        if((int)$info["dostop"]){
                            echo "手动设置了停止";
                            exit;
                        }
                        $runCnt = 0;
                    }else{
                        $sql = "insert into queue_deamon_status(job_name,queue_name,tm,server,func,filepath,admin,msgcnt_date,cnname)
                        values('{$jobName}','{$queueName}',$tm,'{$serverIp}','{$function}','{$phpFile}','{$adminMail}','{$nowD}','{$cnName}') ";
                        $db->query($sql);
                         
                    }
                }
            
                #判断生命期
                if($mustDie){
                    exit; #生命结束，期待crontab
                }
            
            }
        }
    }
?>