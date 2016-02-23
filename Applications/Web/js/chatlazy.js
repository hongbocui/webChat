$(function(){
	var loadChatDataTime = setInterval(function(){
    	if(wc_loginName){ 
    		//所有用户列表。可定时一小时刷新、之后再标示在线用户
            $.getJSON('/chatapi.php?c=user&a=allusers', function(r) {
            	flushAllList(r.data);
            	//获取最近联系人列表
                $.getJSON('/chatapi.php?c=user&a=recentcontact&accountid='+wc_loginName, function(r) {
                	loadNearestContact(r.data);//chatid列表
                	//扫描用户列表，更新在线用户
                    $.getJSON('/chatapi.php?c=user&a=onlineusers', function(r) {
                    	addOnlineList(r.data);
                    });
                    
                    //获取未读消息数量
                    $.getJSON('/chatapi.php?c=message&a=unreadmsg&accountid='+wc_loginName, function(r) {
                    	loadUnreadMsg(r.data);
                    });
                });
            });
            //加载未读广播
            $.getJSON('/chatapi.php?c=broadcast&a=UnreadNum&accountid='+wc_loginName, function(r) {
            	if(r.data) newBroadcast(r.data);
            });
            clearInterval(loadChatDataTime);
        }
    },500);
});

//删除一个最近联系人
function delRecentchatMember(chatid) {
	dotChatid = make___ToDot(chatid);
	$.get('/chatapi.php?c=user&a=DelRecentContact&accountid='+wc_loginName+'&chatid='+dotChatid);
}



/*************
 * 用于消息提醒*
 ************/
var NewMsgNoticeflag = false,newMsgNotinceTimer = null;
function newMsgCount() {
    if (NewMsgNoticeflag) {
    	NewMsgNoticeflag = false;
        document.title = '【☏新消息】您有新的即时消息';
    } else {
    	NewMsgNoticeflag = true;
        document.title = '【　　　】您有新的即时消息';
    }
}
var hiddenProperty = 'hidden' in document ? 'hidden' :    
    'webkitHidden' in document ? 'webkitHidden' :    
    'mozHidden' in document ? 'mozHidden' :    
    null;
	var visibilityChangeEvent = hiddenProperty.replace(/hidden/i, 'visibilitychange');
	var onVisibilityChange = function(){
		if (!document[hiddenProperty]) {
			clearInterval(newMsgNotinceTimer);
			newMsgNotinceTimer = null;
			document.title='beta-即时消息系统';//窗口没有消息的时候默认的title内容
		}
	}
document.addEventListener(visibilityChangeEvent, onVisibilityChange);

//声音提示
function playAudio() {
	var audioElement = document.createElement('audio');
    audioElement.setAttribute('src', 'audio/system.wav');
    audioElement.load;
    audioElement.play();
}
//桌面弹窗与消息提示
function palyDeskNotice(theTitle,options) {
    if(Notification.permission !== "granted"){
    	window.Notification.requestPermission(function(permission){
    		if (permission === "granted")
    			showNotice(theTitle,options);
    	});
    }else{
    	showNotice(theTitle,options);
    }
}
function showNotice(theTitle,options){
	var desknotice = new Notification(theTitle, options);
    desknotice.onclick = function() {
    	window.focus();
    	desknotice.close();
    };
    //页面退出时关闭提醒
    window.onbeforeunload = function() {
    	desknotice.close();
    }
    setTimeout(desknotice.close.bind(desknotice), 3000);
}
