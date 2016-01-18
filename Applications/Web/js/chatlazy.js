$(function(){
	var loadChatDataTime = setInterval(function(){
    	if(wc_loginName){
    		//所有用户列表。可定时一小时刷新、之后再标示在线用户
            $.getJSON('/chatapi.php?c=user&a=allusers', function(r) {
            	flushAllList(r.data);
            	
            	//获取最近联系人列表
                $.getJSON('/chatapi.php?c=user&a=recentcontact&accountid='+wc_loginName, function(r) {
                	loadNearestContact(r.data);
                	
                	//扫描用户列表，更新在线用户
                    $.getJSON('/chatapi.php?c=user&a=onlineusers', function(r) {
                    	addOnlineList(r.data);
                    });
                });
            });
            clearInterval(loadChatDataTime);
        }
    },500);
});
