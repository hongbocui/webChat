<?php 
	use \Workerman\Autoloader;
    /**
	 * 消息队列，即时从消息队列中取得数据
	 * 该聊天系统共3个队列，一个有序集合 redis中设置所有数据有效期都是10天
	 * name:message:quene  离线消息队列，只留最新n条，并且用户登录时弹出n条,该队列在re_login方法生成
	 * md5(name1_name2_...):message:quene  聊天消息队列，一路聊天只留最新n条
	 * chat:message:quene  所有消息队列，即时弹出,并存入数据库
	 * 
	 * name::recentchat:members  有序集合，保存每个用户最近10天的联系人
	 */
	require_once  '../../../Workerman/Autoloader.php';
	Autoloader::setRootPath("../../");
	use Vendors\RedisQuene\Redisq;
	use Vendors\RedisQuene\RedisModel;
	
	//消息队列回调函数
	function doQuene($data){
		if(!$data) return false;
		$data = unserialize($data);
	    //数据库中插入消息
	    insertData($data);
	    storeMessageList($data);
	    storeRecentMembers($data);
	}
	/**
	 * 所有对话message数据,到mysql中
	 */
	function insertData($data){
	    if(!$data) return false;
	    $db = \GatewayWorker\Lib\Db::instance('webChat');
	    
	    $chatList = $data['touser'];
	    if(is_array($chatList) && $chatList)
	        sort($chatList);
	    else
	        return;
	    $chatid = implode('_', $chatList);
	    $chatid = md5($chatid);
	    
	    //线上要可以做自动分表处理
	    $touser = implode(',', $chatList);
	    $sql = "insert into webchat_message(chatid, fromuser, tousers, message, time) values('{$chatid}', '{$data['fromuser']}','{$touser}','{$data['message']}','{$data['time']}')";
	    $db->query($sql);
	}
	
	/**
	 * 保留每个用户最新的n个最近联系人，到redis中
	 */
	function storeRecentMembers($data){
	    if(!is_array($data['touser']))
	        return false;
	    $chatList = $data['touser'];
	    $chatStr = implode(',', $chatList);
	    foreach($chatList as $username){
	        RedisModel::zAdd('Default', $username.':recentchat:members', $data['time'], $chatStr, 864000);
	        //删除十天前的最近联系人
	        RedisModel::zRemRangeByScore('Default', $username.':recentchat:members', 0,  $data['time']-864000);
	    }
	}
	
	/**
	 * 保留每路最新的n条message(历史消息),到redis中
	 */
	function storeMessageList($data){
	    if(!$data) return false;
	    
	    $chatList = $data['touser'];
	    if(is_array($chatList) && $chatList)
	        sort($chatList);
	    else
	        return;
	    $chatid = implode('_', $chatList);
	    $chatid = md5($chatid);
	    
	    Redisq::lpush(array(
            'serverName'    => 'Default', #服务器名，参照见Redisa的定义 ResysQ
            'key'      => $chatid.':message-history',  #队列名
            'value'    => serialize($data),  #插入队列的数据
        ));
	    //保存最新50条
	    Redisq::ltrim(array(
            'serverName'  => 'Default',     #服务器名，参照见Redis的定义 ResysQ
            'key'         => $chatid.':message-history',  #队列名
            'offset'      => 0,      #开始索引值
            'len'         => 50,      #结束索引值
        ));
	}
	 
	deamonStart(array(
            'queueType'   => 'RedisQ',      #消息队列名称 默认是MQ RedisQ
            'serverName'  => 'Default',      #ResysQ
            'queueName'   => 'chat:message-list',      #要监听的消息队列名
            'jobName'     => 'chat:message-list',      #当前处理的job名称
            'cnName'      => 'itcrm聊天队列',      #中文名称
            'function'    => 'doQuene',   #要运行的函数名
            'msgNumAtm'   => 2,       #每次处理的消息数，如果是多个会有合并处理
            'maxSleep'    => 30,      #没有消息的时候，deamon将sleep，如果队列消息不多，尽量设置大点，减少处理压力20+
            'adminMail'   => 'cuihb@ifeng.com',      #接受监控报警的邮件地址，多个地址逗号分割
            'msgServer'   => 'Default',      #要监听的消息队列服务器名
            'phpFile'     =>  __FILE__,      #php文件地址
            'life'        => 0,       #程序的生命周期，如果0表示是一直循环的Deamon处理，如果设置了时间，必须采用crontab的形式
		));
	
	 /**
     *  消息队列Client Deamon程序启动
     */
    function deamonStart($paramArr) {
		$options = array(
            'queueType'   => 'RedisQ',      #消息队列名称 默认是MQ RedisQ
            'serverName'  => 'Default',      #ResysQ
            'queueName'   => 'cui:first:quene',      #要监听的消息队列名
            'jobName'     => 'cui:first:quene',      #当前处理的job名称
            'cnName'      => '',      #中文名称
            'function'    => 'doQuene',   #要运行的函数名
            'msgNumAtm'   => 2,      #每次处理的消息数，如果是多个会有合并处理
            'maxSleep'    => 30,      #没有消息的时候，deamon将sleep，如果队列消息不多，尽量设置大点，减少处理压力
            'adminMail'   => '',      #接受监控报警的邮件地址，多个地址逗号分割
            'msgServer'   => 'Default',      #要监听的消息队列服务器名
            'phpFile'     => '',      #php文件地址
            'life'        => 3,       #程序的生命周期，如果0表示是一直循环的Deamon处理，如果设置了时间，必须采用crontab的形式
		);
		if (is_array($paramArr))$options = array_merge($options, $paramArr);
        foreach ($options as $k => $v){#下面有sql操作，放注入处理
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
             $serverIp = ''; #当前服务器的IP
        }
        $runCnt   = 0;
        $lifeStartTm  = time(); #计算生命期用
        #采用始终循环的方式进行处理
        while(true){
            #从队列中获得数据
            if('RedisQ' == $queueType){#判断队列的类型
                $dataArr = Redisq::pops(array('key'=>$queueName,'serverName'=>$serverName,'num'=>$msgNumAtm));
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
               $db      = \GatewayWorker\Lib\Db::instance('webChat'); #便于记录运行状态用
               $startTm = $tm;
               $nowD    = date("d");
               $sql     = "select * from redisq_deamon_status where job_name = '{$jobName}' and queue_name = '{$queueName}' ";
               $info    = $db->query($sql);
               if($info && is_array($info)){
                   $info = $info[0];#因为我用的是workerman自带的mysql操作
                   #获得当天处理的消息数量
                   $todaycnt = $info['msgcnt_date'] != $nowD ? $runCnt : 'msgcnt_day + '.$runCnt;
                   $sql = "update redisq_deamon_status 
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
                   $sql = "insert into redisq_deamon_status(job_name,queue_name,tm,server,func,filepath,admin,msgcnt_date,cnname)
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
?>