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
use \Api\Model\Muser;

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
        $messageData = json_decode($message, true);
        if(!$messageData)
        {
            return;
        }
        
        // 根据类型执行不同的业务
        switch($messageData['type'])
        {
            // 客户端回应服务端的心跳
            case 'pong':
                return;
            // 客户端登录 message格式: {type:login, clientName:xx} ，添加到客户端，广播给所有客户端xx上线
            case 'login':
                // 判断是否有有名字
                if(!isset($messageData['clientName']))
                {
                    throw new \Exception("\$messageData['clientName'] not set. client_ip:{$_SERVER['REMOTE_ADDR']} \$message:$message");
                }
                
                $clientName = htmlspecialchars($messageData['clientName']);
                
                //判断数据库中是否存在用户,不存在则关闭链接
                if(!Muser::getUserinfo(array('accountid'=>$clientName))){
                    //忽略的消息传给用户
                    Gateway::sendToCurrentClient(json_encode(array('type'=>'error', 'info'=>'erroruser', 'msg'=>'用户名不存在')));
                    Gateway::closeClient($client_id);
                    return;
                }
                
                // 把用户名放到session中
                $_SESSION['clientName'] = $clientName;
                
                //存储用户到在线列表
                self::addUserToOnlineList($client_id, $clientName);
                //转播给在线客户，xx上线 message {type:login, client_id:xx, name:xx}
                $new_message = array(
                    'type' => $messageData['type'],
                    'clientName' => $clientName,
                    'time'        => time(),
                );
                Gateway::sendToAll(json_encode($new_message));
                return;
            case 'say':
                // 非法请求
                if(!isset($_SESSION['clientName'])){
                    throw new \Exception("\$_SESSION['clientName'] not set. client_ip:{$_SERVER['REMOTE_ADDR']}");
                }
                $clientName = $_SESSION['clientName'];
                $chatid = $messageData['chatid'];
                //获取群成员
                $chatList = Muser::getChatListFromChatid($messageData['chatid']);
                
                if(!is_array($chatList)) return;
                //所有单人聊天、群组聊天消息都压入redis队列中，以便存储
                $pushArr = self::makeMsg($chatid, $clientName, $messageData['content']);
                self::msgIntoQueue($pushArr);
                
                // 聊天内容
                $new_message = self::makeMsg($chatid, $clientName, $messageData['content'], 'say');
                $jsonNewMessage = json_encode($new_message);
                //获取所有存储的在线用户
                $clientLists = Muser::getOnlineUsers();
                //获取该组用户在线的clientid,并广播
                $onlineClientIds = self::getClientidsFromUsers($clientLists, $chatList);
                if($onlineClientIds){
                    Gateway::sendToAll($jsonNewMessage, $onlineClientIds);
                }
                //获取该组用户所有不在线的用户,并生成离线消息队列
                $offlineUsers = self::getOfflineUsers($clientLists, $chatList);
                if($offlineUsers) {
                    foreach($offlineUsers as $offname) {
                       self::addOfflineMsgQueue($offname, $chatid, ':unread:msg');
                    }
                }
                return;
            case 'broadcast':
//                 $chatDept = $messageData['touser'];
//                 if(!$chatDept) return;
//                 // 非法请求
//                 if(!isset($_SESSION['clientName'])) {
//                     throw new \Exception("\$_SESSION['clientName'] not set. client_ip:{$_SERVER['REMOTE_ADDR']}");
//                 }
//                 $clientName = $_SESSION['clientName'];
//                 if(!is_array($chatDept)) return;
                
//                 //所有消息压入redis队列中，以便存储
//                 $pushArr = self::makeMsg($clientName, $chatDept, $messageData['content'], \Config\St\Storekey::BROADCAST_MSG_TYPE);
//                 self::msgIntoQueue($pushArr);
                
//                 // 聊天内容
//                 $new_message = self::makeMsg($clientName, $chatDept, $messageData['content'],'broadcast');
//                 $jsonNewMessage = json_encode($new_message);
                
//                 //获取部门下的用户列表
//                 $toUsersList = self::getUsersByDept($chatDept);
//                 //获取所有存储的在线用户
//                 $clientLists = Muser::getOnlineUsers();
//                 //获取该组用户在线的clientid
//                 $onlineClientIds = self::getClientidsFromUsers($clientLists, $toUsersList);
//                 if($onlineClientIds){
//                     Gateway::sendToAll($jsonNewMessage, $onlineClientIds);
//                 }
//                 //获取该组用户所有不在线的用户
//                 $offlineUsers = self::getOfflineUsers($clientLists, $toUsersList);
//                 if($offlineUsers) {
//                     foreach($offlineUsers as $offname) {
//                         self::addOfflineBroadcastQueue($offname, $pushArr, ':unread:broadcast');
//                     }
//                 }
//                 return;
            case 'history':
                if(!isset($messageData['chatid'])) return;
                
                $historyList = \Api\Model\Mmessage::getHistoryMsg($messageData['chatid']);
                if($historyList){
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
       $clientName = self::getClientnameFromId($client_id);
       //从在线列表中删除一个用户
       self::delUserFromOnline($client_id);
       // 广播 xxx 退出了
       $new_message = array('type'=>'logout', 'clientName'=>$clientName, 'time'=>date('Y-m-d H:i:s'));
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
    * 根据clientId获取clientName
    */
   public static function getClientnameFromId($clientId) {
       if(!$clientId) return false;
       $store = Store::instance("gateway");
       return $store->hGet(\Config\St\Storekey::USER_ONLINE_LIST, $clientId);
   }
   
   /**
    * 根据所给用户列表，获取在线的clientid列表
    * @param string $clientName
    */
   public static function getClientidsFromUsers($clientsList=array(),$clientNameArr = array()){
       if(!is_array($clientNameArr) || !is_array($clientsList)) return false;
       $clientIds = array_intersect($clientsList, $clientNameArr);
       return array_keys($clientIds);
   }
   /**
    * 根据用户列表，获取所给用户中不在线的用户
    */
   public static function getOfflineUsers($clientsList=array(),$clientNameArr=array()) {
       if(!is_array($clientNameArr) || !is_array($clientsList)) return false;
       return array_diff($clientNameArr, $clientsList);
   }
   /**
    * 存储用户到在线列表，并返回所有在线用户
    * @param int $client_id
    * @param string $clientName
    */
   public static function addUserToOnlineList($clientId, $clientName){
       $key = \Config\St\Storekey::USER_ONLINE_LIST;
       $store = Store::instance("gateway");
       // 获取所有所有在线用户clientid--------------
       $allOnlineClientId = Gateway::getOnlineStatus();
       //获取存储中在线用户列表       
       $clientList = Muser::getOnlineUsers();
       if(isset($clientList[$clientId])) return true;
       //是否允许多用户登录,剔除用户的clientid
       if(\Config\St\Status::NOT_ALLOW_CLIENTS)
           self::notAllowMoreClient($clientList, $clientName);
       // 将存储中不在线用户删除
       self::deleteOfflineUser($clientList, $allOnlineClientId);
       // 添加
       if($store->hSet($key, $clientId, $clientName))
           return true;
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
   public static function notAllowMoreClient($clientList, $clientName){
       if(is_array($clientList)){
           $unsetKey = array_keys($clientList, $clientName);
           if($unsetKey){
               Gateway::sendToAll(json_encode(array('type'=>'error', 'info'=>'loginconflict', 'msg'=>'您已在另一客户端登陆')),$unsetKey);
               $store = Store::instance("gateway");
               foreach($unsetKey as $unkey){
                   unset($clientList[$unkey]);
                   //下线用户
                   Gateway::closeClient($unkey);
                   //删除存储用户
                   $store->hDel(\Config\St\Storekey::USER_ONLINE_LIST, $unkey);
               }
           }
       }
       return;
   }
   /**
    * 离线聊天数据或广播数据 压入用户离线聊天消息队列
    * 每个用户有一个离线hash，hash中的键值分别是本路聊天对应的消息数量
    */
   public static function addOfflineMsgQueue($username, $chatid, $partkey='') {
       $store = Store::instance("gateway");
       $store->hIncrBy($username.$partkey, $chatid, 1);
   }
   /**
    * 点击某路对话时清除该对话的离线数量 
    */
   public static function delOfflineMsgQueue($username, $touserstr, $partkey=''){
       $store = Store::instance("gateway");
       $store->hDel($username.$partkey, $touserstr);
   }
   /**
    * 离线广播数据压入队列
    */
   public static function addOfflineBroadcastQueue($username, $msgData, $partkey='') {
       //注意这里是lpush，为了与ltrim一块使用
       Redisq::lpush(array(
           'serverName'    => 'webChat', #服务器名，参照见Redisa的定义 ResysQ
           'key'      => $username.$partkey,  #离线消息队列名
           'value'    => serialize($msgData),  #插入队列的数据
       ));
       //保存最新100条
       Redisq::ltrim(array(
           'serverName'  => 'webChat',     #服务器名，参照见Redis的定义 ResysQ
           'key'         => $username.$partkey,  #队列名
           'offset'      => 0,      #开始索引值
           'len'         => 100,      #结束索引值
       ));
   }
   /**
    * 所有聊天消息和广播消息都压入到redis队列中
    */
   public static function msgIntoQueue($msgData) {
       Redisq::rpush(array(
           'serverName'    => 'webChat', #服务器名，参照见Redisa的定义 ResysQ
           'key'      => 'chat:msg-list',  #队列名
           'value'    => serialize($msgData),  #插入队列的数据
       ));
   }
   /**
    * 格式化消息数据
    */
   private static function makeMsg($chatid, $from, $content='', $type=0) {
       $msg = array(
           'chatid'  => $chatid,
           'fromuser'=> $from,
           'message' => $content,
           'time'    => time(),
           'type'    => $type,
       );
       return $msg;
   }
   /**
    * 根据部门获取部门下所有用户，部门之间用,号分割
    * 用于广播
    */
   public static function getUsersByDept($chatDept) {
       if(in_array('公司全体员工', $chatDept)) {
           $toUserList = Muser::getUserinfo(array(
               'fields' => array('accountid'),
           ));
       } else {
           $toUserList = array();
           foreach($chatDept as $key=>$dept) {
               $userList = Muser::getUserinfo(array(
                   'fields' => array('accountid'),
                   'dept'   => $dept,
               ));
               $toUserList = array_merge($toUserList, $userList);
           }
       }
       if(!$toUserList) return;
       foreach((array)$toUserList as $key=>$userval) {
           $toUserList[$key] = $userval['accountid'];
       }
       return $toUserList;
   }
   
}
