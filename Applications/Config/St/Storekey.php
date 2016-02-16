<?php 
    namespace Config\St;
    class Storekey {
        //在线用户的redis hash
        const USER_ONLINE_LIST = "USER_ONLINE_LIST";
        
        
        //信息类型状态
        const CHAT_MSG_TYPE     = 0; //聊天消息
        const BROADCAST_MSG_TYPE= 1;// 广播消息
        const IMAGE_MSG_TYPE    = 2;
        const ATTACH_MSG_TYPE   = 3; //附件消息
        
        
    }
?>