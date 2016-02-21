<?php 
namespace Config\St;
class Storekey {
    //在线用户的redis hash
    const USER_ONLINE_LIST = "USER_ONLINE_LIST";
    
    
    //信息类型
    const CHAT_MSG_TYPE     = 0; //聊天消息
    const IMAGE_MSG_TYPE    = 1; //图片消息
    const ATTACH_MSG_TYPE   = 2; //附件消息
    const BROADCAST_MSG_TYPE= 3; //广播消息
    
    #chat系统 redis键值相关 
    const MSG_CHAT_LIST    = 'chat:msg-list';       //消息队列     list
    const GROUP_BASEINFO   = ':group:info';         //群基本信息  hash
    const GROUP_MEMBERS    = ':group:members';      //群成员信息  hash
    const UNREAD_MSG       = ':unread:msgnum';      //离线消息数量 hash
    const UNREAD_BROADCAST = ':unread:broadcast';   //离线广播消息  string
    const MSG_HISTORY      = ':msg-history';        //历史消息记录  list
    const RECENT_MEMBERS   = ':recentchat:members'; //最近联系人列表 order set
    
}
?>