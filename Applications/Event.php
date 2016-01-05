<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 */

/**
 * 聊天主逻辑
 * 主要是处理 onMessage onClose
 */
use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\Store;
use \Vendors\Redis\Redisq;

class Event
{

   /**
    * 有消息时
    * @param int $client_id
    * @param string $message
    */
   public static function onMessage($client_id, $message)
   {       
        // debug
        //echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id session:".json_encode($_SESSION)." onMessage:".$message."\n";
        
        // 客户端传递的是json数据
        $message_data = json_decode($message, true);
        if(!$message_data)
        {
            return ;
        }
        
        // 根据类型执行不同的业务
        switch($message_data['type'])
        {
            // 客户端回应服务端的心跳
            case 'pong':
                return;
            // 客户端登录 message格式: {type:login, client_name:xx} ，添加到客户端，广播给所有客户端xx上线
            case 'login':
                // 判断是否有有名字
                if(!isset($message_data['client_name']))
                {
                    throw new \Exception("\$message_data['client_name'] not set. client_ip:{$_SERVER['REMOTE_ADDR']} \$message:$message");
                }
                
                $client_name = htmlspecialchars($message_data['client_name']);
                
                //判断数据库中是否存在用户,不存在则关闭链接
                if(!\Api\Model\Muser::getUserinfo(array('accountid'=>$client_name))){
                    //忽略的消息传给用户
                    Gateway::sendToCurrentClient(json_encode(array('type'=>'error', 'info'=>'erroruser')));
                    Gateway::closeClient($client_id);
                    return;
                }
                
                // 把用户名放到session中
                $_SESSION['client_name'] = $client_name;
                
                //存储用户到在线列表
                self::addUserToOnlineList($client_id, $client_name);
                //转播给在线客户，xx上线 message {type:login, client_id:xx, name:xx}
                $new_message = array(
                    'type' => $message_data['type'],
                    'client_name' => $client_name,
                    'time'        => date('Y-m-d H:i:s')
                );
                Gateway::sendToAll(json_encode($new_message));
                return;
            // 客户端发言 message: {type:say, touser:xx, content:xx}
            case 'say':
                // 非法请求
                if(!isset($_SESSION['client_name']))
                {
                    throw new \Exception("\$_SESSION['client_name'] not set. client_ip:{$_SERVER['REMOTE_ADDR']}");
                }
                $client_name = $_SESSION['client_name'];
                
                //所有消息压入redis队列中，以便存储
                $pushArr = array(
                    'fromuser'    => $client_name,
                    'touser'      => $message_data['touser'],
                    'message' => nl2br(htmlspecialchars($message_data['content'])),
                    'time'    => time(),
                );
                Redisq::rpush(array(
                    'serverName'    => 'webChat', #服务器名，参照见Redisa的定义 ResysQ
                    'key'      => 'chat:msg-list',  #队列名
                    'value'    => serialize($pushArr),  #插入队列的数据
                ));
                
                // 聊天
                if(is_array($message_data['touser']))
                {
                    $new_message = array(
                        'type'=>'say',
                        'fromuser' =>$client_name,
                        'touser' =>$message_data['touser'],
                        'message'=>nl2br(htmlspecialchars($message_data['content'])),
                        'time'=>date('Y-m-d H:i:s'),
                    );
                    $jsonNewMessage = json_encode($new_message);
                    foreach($message_data['touser'] as $username){
                        $to_client_id_arr = self::getClientidFromUser($username);
                        
                        //对方在线
                        if($to_client_id_arr){
                            Gateway::sendToAll($jsonNewMessage, $to_client_id_arr);//对于多客户端来说的
                        //不在线，则，消息压入到离线列表
                        }else{
                            //注意这里是lpush，为了与ltrim一块使用
                            Redisq::lpush(array(
                                'serverName'    => 'webChat', #服务器名，参照见Redisa的定义 ResysQ
                                'key'      => $username.':unread:msg',  #离线消息队列名
                                'value'    => serialize($pushArr),  #插入队列的数据
                            ));
                            //保存最新50条
                            Redisq::ltrim(array(
                                'serverName'  => 'webChat',     #服务器名，参照见Redis的定义 ResysQ
                                'key'         => $username.':unread:msg',  #队列名
                                'offset'      => 0,      #开始索引值
                                'len'         => 50,      #结束索引值
                            ));
                        }
                    }
                    return;
                }
                
                return;
                // 广播（后期需加参数用此功能）
                $client_id_array = array_keys('xxx');
                $new_message = array(
                    'type'     =>'say', 
                    'fromuser' =>$client_name,
                    'touser'   => $message_data['touser'],//all
                    'content'  =>nl2br(htmlspecialchars($message_data['content'])),
                    'time'     =>date('Y-m-d H:i:s'),
                );
                return Gateway::sendToAll(json_encode($new_message), $client_id_array);
                
            //获取存于redis中的历史记录: {type:history, fromuser:xx， touser:xxx} 
            case 'history':
                $chatList = $message_data['touser'];
                $chatid = \Api\Model\Mcommon::setChatId($chatList);
                if(!$chatid) return;
                
                $historyList = Redisq::range(array(
                    'serverName'  => 'webChat',     #
                    'key'         => $chatid.':msg-history',  #队列名
                    'offset'      => 0,      #开始索引值
                    'len'         => -1,      #结束索引值
                ));
                
                if($historyList){
                    $historyList = array_reverse($historyList, false);//反序，并丢弃原键名
                    foreach($historyList as $key=>$val){
                        $historyList[$key] = unserialize($val);
                    }
                    $history_message = array(
                        'type' => 'history',
                        'messageList' => $historyList,
                    );
                    //忽略的消息传给用户
                    Gateway::sendToCurrentClient(json_encode($history_message));
                }
                return;
        }
   }
   
   /**
    * 当客户端断开连接时
    * @param integer $client_id 客户端id
    */
   public static function onClose($client_id)
   {
       // debug
       //echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id onClose:''\n";
       //获取clientname
       $client_name = self::getClientnameFromId($client_id);
       //从在线列表中删除一个用户
       self::delUserFromOnline($client_id);
       // 广播 xxx 退出了
       $new_message = array('type'=>'logout', 'client_name'=>$client_name, 'time'=>date('Y-m-d H:i:s'));
       Gateway::sendToAll(json_encode($new_message));
   }
   /**
    * 从在线用户中删除一个用户
    * @param int $client_id
    */
   public static function delUserFromOnline($client_id){
       $key = \Config\St\Storekey::USER_ONLINE_LIST;
       $store = Store::instance("gateway");
       // 存储驱动是redis
           $try_count = 3;
           while ($try_count--) {
               if ($store->hDel($key, $client_id)) {
                   return true;
               }
           }
       return true;
   }
   /**
    * 根据client_id获取client_name
    */
   public static function getClientnameFromId($client_id) {
       if(!$client_id) return false;
       $store = Store::instance("gateway");
       return $store->hGet(\Config\St\Storekey::USER_ONLINE_LIST, $client_id);
   }
   
   /**
    * 根据username 获取client_id.如果取得client_id为false，说明该user不在线
    * @param string $client_name
    */
   public static function getClientidFromUser($client_name){
       $key = \Config\St\Storekey::USER_ONLINE_LIST;
       $store = Store::instance("gateway");
       $client_id_arr = false;
       // 存储驱动是redis
       $try_count = 3;
       while ($try_count--) {
           $client_list = $store->hGetAll($key);
           if (false === $client_list) {
               $client_list = array();
           }
           if(is_array($client_list)){
               $client_id_arr = array_keys($client_list, $client_name);
               return $client_id_arr;
           }
       }
       return $client_id_arr;
   }
   /**
    * 存储用户到在线列表，并返回所有在线用户
    * @param int $client_id
    * @param string $client_name
    */
   public static function addUserToOnlineList($client_id, $client_name){
       $key = \Config\St\Storekey::USER_ONLINE_LIST;
       $store = Store::instance("gateway");
       // 获取所有所有在线用户--------------
       $all_online_client_id = Gateway::getOnlineStatus();
       // 存储驱动是Redis
       $try_count = 3;
       while ($try_count--) {
           $client_list = $store->hGetAll($key);
           if (false === $client_list) {
               $client_list = array();
           }
           if (!isset($client_list[$client_id])) {
               //是否允许多用户登录,剔除用户的clientid
               if(\Config\St\Status::NOT_ALLOW_CLIENTS)
                   self::notAllowMoreClient($client_list, $client_name);
               // 将存储中不在线用户删除
               self::deleteOfflineUser($client_list, $all_online_client_id);
               // 添加
               if($store->hSet($key, $client_id, $client_name))
                   return true;
           } else {
               return true;
           }
       }
       return false;
   }
   /**
    * 删除存储中的不在线用户
    */
   public static function deleteOfflineUser($clientList, $allClients) {
       if(!$allClients || !$clientList) return;
       $allClients = array_flip($allClients);
       $offlineList = array_diff_key($clientList, $allClients);
       if($offlineList){
           foreach($offlineList as $offlineName){
               self::notAllowMoreClient($clientList, $offlineName);
           }
       }
       return;
   }
   /**
    * 不允许多用户登录
    * 剔除存储用户
    */
   public static function notAllowMoreClient($client_list, $client_name){
       if(is_array($client_list)){
           $unsetKey = array_keys($client_list, $client_name);
           if($unsetKey){
               $store = Store::instance("gateway");
               foreach($unsetKey as $unkey){
                   unset($client_list[$unkey]);
                   $store->hDel(\Config\St\Storekey::USER_ONLINE_LIST, $unkey);
               }
           }
       }
       return $client_list;
   }
   
}
