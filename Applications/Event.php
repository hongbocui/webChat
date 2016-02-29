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
use \Vendors\Redis\RedisModel;
use \Vendors\Redis\Redisq;
use \Api\Model\Muser;
use \Api\Model\Mmessage;
use \Config\St\Storekey;
use Api\Model\Mbroadcast;

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
                
                //判断消息类型
                $msgType = Storekey::CHAT_MSG_TYPE;
                if(isset($messageData['msgType'])) {
                    if($messageData['msgType']==='file'){
                        $msgType = Storekey::ATTACH_MSG_TYPE;
                    } elseif ($messageData['msgType'] === 'image'){
                        $msgType = Storekey::IMAGE_MSG_TYPE;
                    }
                }
                //所有单人聊天、群组聊天消息都压入redis队列中，以便存储
                $pushArr = self::makeMsg($chatid, $clientName, $messageData['content'], $msgType);
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
                        Mmessage::addUnreadMsg($offname, $chatid, Storekey::UNREAD_MSG);
                    }
                }
                return;
            case 'broadcast':
                if(!isset($messageData['touser']['member'])) return;
                
                $toUsersList = $messageData['touser']['member'];
                if(!$toUsersList || !is_array($toUsersList)) return;
                $toUsersList = array_unique($toUsersList);
                // 非法请求
                if(!isset($_SESSION['clientName'])) {
                    throw new \Exception("\$_SESSION['clientName'] not set. client_ip:{$_SERVER['REMOTE_ADDR']}");
                }
                $clientName = $_SESSION['clientName'];
                
                //makeMsg($chatid, $from, $content='', $type=0)
                //所有广播消息压入redis队列中，以便存储
                $messageData['title'] = isset($messageData['title']) ? $messageData['title'] : '无标题';
                $pushArr = array(
                    'fromuser'   => $clientName,
                    'touser'     => '-'.implode('-',$messageData['touser']['member']).'-',//便于查询
                    'touserTitle'=> $messageData['touser']['title'],
                    'title'      => addslashes($messageData['title']),
                    'content'    => addslashes($messageData['content']),
                    'time'       => time(),
                    'type'       => Storekey::BROADCAST_MSG_TYPE,
                );
                self::msgIntoQueue($pushArr);
                
                // 聊天内容.修改type，前端发送不必发送所有用户
                $pushArr['type'] = 'broadcast';
                unset($pushArr['touser']);
                $jsonNewMessage = json_encode($pushArr);
                
                //获取所有存储的在线用户
                $clientLists = Muser::getOnlineUsers();
                //获取该组用户在线的clientid
                $onlineClientIds = self::getClientidsFromUsers($clientLists, $toUsersList);
                if($onlineClientIds){
                    Gateway::sendToAll($jsonNewMessage, $onlineClientIds);
                }
                //获取该组用户所有不在线的用户
                $offlineUsers = self::getOfflineUsers($clientLists, $toUsersList);
                if($offlineUsers) {
                    foreach($offlineUsers as $offname) {
                        Mbroadcast::addUnreadBroadcastNum($offname, Storekey::UNREAD_BROADCAST);
                    }
                }
                return;
            case 'groupset':
                // 非法请求
                if(!isset($_SESSION['clientName'])){
                    throw new \Exception("\$_SESSION['clientName'] not set. client_ip:{$_SERVER['REMOTE_ADDR']}");
                }
                $clientName = $_SESSION['clientName'];
                
                //修改群组成员
                $chatInfo = explode('-', $messageData['chatid']);//master=$chatInfo[0],uuid=$chatInfo[1]
                
                //获取已有群成员信息
                $originalMembers = \Api\Model\Mgroup::getGroupMembers(array(
                    'master' => $chatInfo[0],
                    'uuid'   => $chatInfo[1]
                ));
                
                $setRes = \Api\Model\Mgroup::setGroup(array(
                    'master' => $chatInfo[0],
                    'uuid'   => $chatInfo[1],
                    'title'  => $messageData['title'],
                ));
                if(!$setRes) return;
                $messageData['members'] = array_unique($messageData['members']);
                //根据  $originalMembers 和 $messageData['members'] 获取分别要添加和减少的成员
                $addMembers = array_diff($messageData['members'], $originalMembers);
                $delMembers = array_diff($originalMembers, $messageData['members']);
                if(!is_array($addMembers) || !is_array($delMembers) || !is_array($originalMembers))
                    return;
                //删除用户
                \Api\Model\Mgroup::setGroupMembers(array(
                    'master'  => $chatInfo[0],//群主账号
                    'uuid'    => $chatInfo[1],//唯一的id标示,可以是时间戳
                    'type'    => 'del', //add、del 向群众添加或者删除用户
                    'userList'=> $delMembers,//群中删除的用户
                ));
                //把删除用户的该chatid的最近联系人删除
                \Api\Model\Mgroup::remRecentMembers($messageData['chatid'], $delMembers);
                //添加用户
                \Api\Model\Mgroup::setGroupMembers(array(
                    'master'  => $chatInfo[0],//群主账号
                    'uuid'    => $chatInfo[1],//唯一的id标示,可以是时间戳
                    'type'    => 'add', //add、del 向群众添加或者删除用户
                    'userList'=> $addMembers,//要向群中添加的用户
                ));
                //要广播的信息
                $broadMsg = array(
                    'type'     => $messageData['type'],
                    'chatid'   => $messageData['chatid'],
                    'fromuser' => $clientName,
                    'delMember'=> $delMembers,
                    'addMember'=> $addMembers,
                );
                
                //获取所有存储的在线的用户
                $clientLists = Muser::getOnlineUsers();
                //获取该组原本用户在线的clientid,并广播
                if($originalMembers)
                    $onlineClientIds = self::getClientidsFromUsers($clientLists, $originalMembers);
                if(isset($onlineClientIds) && $onlineClientIds){
                    Gateway::sendToAll(json_encode($broadMsg), $onlineClientIds);
                }
                return;
            case 'systemNotice':
                // 非法请求
                if(!isset($_SESSION['clientName'])){
                    throw new \Exception("\$_SESSION['clientName'] not set. client_ip:{$_SERVER['REMOTE_ADDR']}");
                }
                $clientName = $_SESSION['clientName'];
                //群组info
                $chatInfo = explode('-', $messageData['chatid']);//master=$chatInfo[0],uuid=$chatInfo[1]
                
                //获取已有群成员信息
                $originalMembers = \Api\Model\Mgroup::getGroupMembers(array(
                    'master' => $chatInfo[0],
                    'uuid'   => $chatInfo[1]
                ));
                //如果本身不在群里则禁止操作
                if(!in_array($clientName, $originalMembers))
                    return;
                
                //要广播的信息
                $broadMsg = array(
                    'fromuser'=> $clientName,
                    'type'    => $messageData['type'],
                    'chatid'  => $messageData['chatid'],
                    'action'  => $messageData['action'],
                );
                
                switch ($messageData['action']) {
                    case "grouptitle"://修改群title
                        $setRes = \Api\Model\Mgroup::setGroup(array(
                            'master' => $chatInfo[0],
                            'uuid'   => $chatInfo[1],
                            'title'  => $messageData['title'],
                        ));
                        $broadMsg['title'] = $messageData['title'];
                        if(!$setRes) return;
                        break;
                    case "opennotice"://打开群消息提醒 
                        break;
                    case "grouptitle"://屏蔽群消息提醒
                        break;
                }
                
                //获取所有存储的在线的用户
                $clientLists = Muser::getOnlineUsers();
                //获取该组原本用户在线的clientid,并广播
                if($originalMembers)
                    $onlineClientIds = self::getClientidsFromUsers($clientLists, $originalMembers);
                if(isset($onlineClientIds) && $onlineClientIds){
                    Gateway::sendToAll(json_encode($broadMsg), $onlineClientIds);
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
       $key = Storekey::USER_ONLINE_LIST;
       // 存储驱动是redis
       $try_count = 3;
       while ($try_count--) {
           if (RedisModel::hashDel('webChat', $key, $client_id)) {
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
       return RedisModel::hashGet('webChat', Storekey::USER_ONLINE_LIST, $clientId);
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
    * 存储用户到在线列表
    * @param int $client_id
    * @param string $clientName
    */
   public static function addUserToOnlineList($clientId, $clientName){
       $key = Storekey::USER_ONLINE_LIST;
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
       // 添加   时间默认是一天
       if(RedisModel::hashSet('webChat', $key, $clientId, $clientName))
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
               foreach($unsetKey as $unkey){
                   unset($clientList[$unkey]);
                   //下线用户
                   Gateway::closeClient($unkey);
                   //删除存储用户
                   RedisModel::hashDel('webChat', Storekey::USER_ONLINE_LIST, $unkey);
               }
           }
       }
       return;
   }
   /**
    * 所有聊天消息和广播消息都压入到redis队列中
    */
   public static function msgIntoQueue($msgData) {
       Redisq::rpush(array(
           'serverName'    => 'webChat', #服务器名，参照见Redisa的定义 ResysQ
           'key'      => Storekey::MSG_CHAT_LIST,  #队列名
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
}
