	if (typeof console == "undefined") {    this.console = { log: function (msg) {  } };}
	//几个全局变量
	var wc_loginName='',  //用户登录名称
		wc_allUserArr = [],//用以存储所有用户的id=》name
		wc_ws,
		wc_reConnectTimeid, 
		wc_reconnect=false, 
		wc_errorType=false;
	function init() {
	    // 创建websocket
	 	wc_ws = new WebSocket("ws://"+document.domain+":7272");
	   // 当socket连接打开时，输入用户名
	   wc_ws.onopen = function() {
	 	  wc_reConnectTimeid && window.clearInterval(wc_reConnectTimeid);
	 	  if(!wc_loginName)
	 	  {
			    showLoginPage();
	 	  }
	 	  if(!wc_loginName) {
	 		  return wc_ws.close();
			  }
	 	  if(wc_reconnect == false)
	 	  {
	     	  // 登录
	 		  var login_data = JSON.stringify({"type":"login","clientName":wc_loginName});
	 		  console.log("发送登录数据:"+login_data);
			      wc_ws.send(login_data);
	 		  wc_reconnect = true;
	 	  }
	 	  else
	 	  {
	     	  // 断线重连
	     	  var relogin_data = JSON.stringify({"type":"login","clientName":wc_loginName});
	 		  console.log("发送登录数据:"+relogin_data);
	 		  wc_ws.send(relogin_data);
	 	  }
	   };
	   // 当有消息时根据消息类型显示不同信息
	   wc_ws.onmessage = function(e) {
	 	 console.log(e.data);
	     var data = JSON.parse(e.data);
	     switch(data['type']){
	           // 服务端ping客户端
	           case 'ping':
	         	wc_ws.send(JSON.stringify({"type":"pong"}));
	             break;;
	           // 登录 更新用户列表
	           case 'login':
	               //{"type":"re_login","clientName":"xxx","client_list":"[...]","all_list":"[...]","time":"xxx"}
	        	   lightOnlineUserList(new Array(data['clientName']));
	        	   break;
	           // 发言
	           case 'say':
	         	  //{"type":"say","fromuser":xxx,"touser":xxx,"message":"xxx","time":"xxx"}
	        	   recieveMsg(data['fromuser'], data['touser'], data['message'], data['time']);
	         	  break;
	           // 发言
	           case 'broadcast':
	               //前端发送广播接口
	               //wc_ws.send(JSON.stringify({"type":"broadcast","touser":["技术部"],"content":"qqqdddddddddddddddddddd"}));
	               console.log(data);
	         	  break;
	           // 加载历史消息
	           case 'history':
	         	  //{"type":"history","messageList":"[...]"}
	         	  loadHistoryMessage(data['messageList']);
	         	  break;
	           // 错误处理
	           case 'error':
	         	  switch(data['info']){
	       	          case 'erroruser':
	             	      alert(data['msg']);
	             	      wc_errorType = true;wc_loginName = '';
	             	      break;
	       	          case 'loginconflict':
	             	      alert(data['msg']);
	             	      wc_errorType = true;
	             	      break;
	         	      default:
	             	      break;
	         	  }
	         	  break; 
	          // 用户退出 更新用户列表
	           case 'logout':
	         	  //{"type":"logout","clientName":xxx,"time":"xxx"}
	        	  lightOfflineUserList(new Array(data['clientName']))
	         	  break;
	     }
	   };
	   wc_ws.onclose = function() {
	 	  console.log("连接关闭");
	 	  // 定时重连
	 	  window.clearInterval(wc_reConnectTimeid);
	 	  if(!wc_errorType){
	 		  wc_reConnectTimeid = window.setInterval(init, 3000);
	       }
	   };
	   wc_ws.onerror = function() {
	 	  console.log("出现错误");
	   };
	 }
	init();
	
	// 输入姓名
    function showLoginPage(){  
        wc_loginName = prompt('输入你的名字：', '');
        if(!wc_loginName || wc_loginName=='null'){  
            alert("输入名字为空或者为'null'，请重新输入！");  
            showLoginPage();
        }
    }
    //更新用户列表
    function flushAllList(data) {
    	var userlist_all_window = $("#organization-structure");
    	userlist_all_window.empty();
    	flushAllListFunc(userlist_all_window,data);
    	userlist_all_window.treeViewModify({});
    }
    //更新最近联系人列表
    function loadNearestContact(data) {
    	var nearestContactContainer = $("#nearest-contact");
    	nearestContactContainer.empty();
    	for(var p in data) {
    		loadNearestContactFunc(nearestContactContainer, data[p]);
    	}
    	nearestContactContainer.treeViewModify({});
    }
    //更新在线用户
    function addOnlineList(data) {
    	lightOnlineUserList(data);
    }
    /*************ws****************/
    //发送消息
    function sendToWsMsg(msg) {
    	msg = encMsg(msg);
		
		var nowChatUser = getChatingUsersList();
		var chatList = nowChatUser.split(',');
		if(-1 === nowChatUser.indexOf(',')) {
			chatList.push(wc_loginName);
			chatList.sort();
		}
		wc_ws.send(JSON.stringify({"type":"say","touser":chatList,"content":msg}));
    }
    //接收消息
    function recieveMsg(fromuser, touser, msg, time) {
    	makeHistoryList(fromuser, touser, msg, time);
    	
    	var nowChatUser = getChatingUsersList();
		var nowChatList = nowChatUser.split(',');
		if(-1 === nowChatUser.indexOf(',')) {
			nowChatList.push(wc_loginName);
			nowChatList.sort();
		}
		
		//判断是否在最近联系人中，如没有则显示(个人消息和群消息都判断)
		var nearestContactContainer = $("#nearest-contact");
		if(!$('#nearest-contact span[type=personal][data-id='+nowChatUser+']').length && !$('#nearest-contact span[type=group][data-id='+nowChatUser+']').length){
			loadNearestContactFunc(nearestContactContainer,nowChatList.join(','));
		}
    	
		//判断是否为当前用户，当前用户则append到聊天box里面，否则则将该聊天对话的未读消息+1
		if(nowChatList.toString() === touser.toString()) {
			$('.logs').append(decMsg(msg,fromuser,time));
			$('.logs').scrollToBottom();
		}else{
			
		}
    }
    
    
    
    /*******接口函数********/
    //加载历史消息
    function loadHistoryMessage(messageList){
    	for(var p in messageList){
    		var chatList = messageList[p].touser.sort();
            var userTOuser = chatList.join('_');
        	var chatSomeoneHistory = 'chat'+userTOuser+'History';
        	
        	if(window[chatSomeoneHistory] == undefined){
            	window[chatSomeoneHistory] = [];
            }
        	window[chatSomeoneHistory].push(messageList[p]);
        }
    }
    //encmsg 原始的msg数据加工成像数据库中存储的数据（return str）
    function encMsg(msg) {
    	var face_pattern = /<img\b\ssrc="\.\/images\/smiley\/(\d+)\.gif">/g;
		var br_pattern = /<\/div>/g;
		var clear_tag_pattern = /<\/?(\w+\b)[^>]*>(?:([^<]*)<\/\1[^>]*>)?/g;
		//表情转义
		msg = msg.replace(face_pattern, '[\\face$1]');
		msg = msg.replace(br_pattern, '[\\br]');
		msg = msg.replace(clear_tag_pattern, '$2');
		return msg;
    }
    //decMsg 将从数据库中获取的msg，还原成可以向聊天box append的字符串。
    function decMsg(msg, userid, time) {
    	//消息还原
		msg = msg.replace(/\[\\([a-z]+)(\d+)?\]/g, function(match, p1, p2, offset, string) {
			switch(p1) {
				case 'face':
					return '<img src="./images/smiley/'+p2+'.gif">';
				case 'br':
					return '<br />';
				case 'image':
					//查附件表，id为p2
					return '';
				case 'file':
					//查附件表，id为p2
					return '';
			}
		});
		var selfClass = userid === wc_loginName ? ' self' : ''
	    var pitem     = wc_allUserArr[userid]+' - '+timestampTodate(time);
		return '<div class="row'+selfClass+'"><div class="user-avatar"><img class="avatar" src="./default_34_34.jpg"></div> \
				<div class="message-detail"> \
					<p>'+pitem+'</p> \
					<div class="message-box"> \
						'+msg+'&nbsp; \
						<i class="chat-icon message-box-pike"></i> \
					</div> \
				</div></div>';
			
    }
    //组装本地历史消息数组
    function makeHistoryList(fromuser, touser, message, time){
        touser.sort();
        //俩通信客户端的唯一历史记录
        var userTOuser = touser.join('_');
        var chatSomeoneHistory = 'chat'+userTOuser+'History';
        var nowMessage = [];
        nowMessage.fromuser = fromuser;
        nowMessage.touser = touser;
        nowMessage.message = message;
        nowMessage.time = time;
        if(window[chatSomeoneHistory] == undefined){
            //此时应该从redis中取出最新的数据，防止用户点击标红信息的时候只有一条
        	wc_ws.send(JSON.stringify({"type":"history","fromuser":fromuser,"touser":touser}));
        }

        //等待redis中数据
        var i = 0;
        var waitHistory = function(){
                i++;
            	if(window["chat"+userTOuser+"History"] != undefined){
            		window[chatSomeoneHistory].push(nowMessage);
            		clearInterval(waitTime);
                }
        	    if(i>50)
        	    	clearInterval(waitTime);
            };
    	var waitTime = setInterval(waitHistory, 10);
        
    }
    //给出一个在线或者上线用户组，使用户列表和最近联系人中头像点亮
    function lightOnlineUserList(users) {
    	if(!users) return false;
    	for(var i in users) {
    		//联系人列表处理
    		userItemObjInUserList = $("#organization-structure .no-child[data-id='"+users[i]+"']");
    		userItemObjInUserList.find('img').removeClass('no-login');
    		//userItemObjInUserList.parent().prepend(userItemObjInUserList);
    		userItemObjInUserList.moveTreeTop(userItemObjInUserList.parent());
    		//最近联系人处理
    		$("#nearest-contact .no-child[data-id='"+users[i]+"']").find('img').removeClass('no-login');
    	}
    }
    //给出一个下线用户组，使用户列表和最近联系人中头像变灰
    function lightOfflineUserList(users) {
    	if(!users) return false;
    	for(var i in users) {
    		//联系人列表处理
    		userItemObjInUserList = $("#organization-structure .no-child[data-id='"+users[i]+"']");
    		userItemObjInUserList.find('img').addClass('no-login');
    		//userItemObjInUserList.parent().append(userItemObjInUserList);
    		
    		//最近联系人处理
    		$("#nearest-contact .no-child[data-id='"+users[i]+"']").find('img').addClass('no-login');
    	}
    }
    //获取当前聊天人员 a,b,c
    function getChatingUsersList() {
    	return $('.contact-msg').attr('chatuser');
    }
    //给出一路对话，更新到最近联系人列表 chatUsersStr = a,b,c
    function loadNearestContactFunc(parentObj,chatUsersStr) {
    	var innerStr = '';
    	var childfileStr = '';
    	
    	var contactArr = chatUsersStr.split(',');
		//单用户聊天
		if(contactArr.length === 2) {
			for(var q in contactArr) {
				if(contactArr[q] === wc_loginName) continue;
				innerStr += '<span class="no-child" type="personal" data-id="'+contactArr[q]+'"><img class="avatar no-login" src="./default_34_34.jpg" width="22px">'+wc_allUserArr[contactArr[q]]+'<b class="unread">0</b></span>';
			}
		//群用户聊天
		} else if(contactArr.length > 2) {
			var groupNames = [];
			innerStr += '<span type="group" data-id="'+chatUsersStr+'">';
			innerStr += '<div class="group-avatar">';
			
			childfileStr += '<div class="tree-files">';
			var kk = 0;
			for(var r in contactArr) {
				if(kk<4) {//限制4个头像
					innerStr += '<img class="avatar" src="./default_34_34.jpg" width="10px">';
				}
				//限制3个人长度
				if(kk<3)groupNames.push(wc_allUserArr[contactArr[r]]);
				
				childfileStr += '<span class="no-child" type="member" data-id="'+contactArr[r]+'"><img class="avatar no-login" src="./default_34_34.jpg" width="22px">'+wc_allUserArr[contactArr[r]]+'</span>';
				kk++;
			}
			childfileStr += '</div>';
			
			innerStr += '</div>'+groupNames.join(',');
			innerStr += '...<b class="unread">0</b></span>';
		}
		if(parentObj.find('.tree-folders').length <= 0){
			$("<div/>").addClass('tree-folders').appendTo(parentObj);
		}
		parentObj.find('.tree-folders').append(innerStr);
		parentObj.append(childfileStr);
    }
    //递归更新所有用户列表
    function flushAllListFunc(parentObj, allList){
    	var innerStr = '';
    	var isFolder = false;
    	for(var p in allList) {
    		if(typeof(allList[p]) === 'object') {
    			isFolder = true;
    			innerStr += '<span>'+p+'</span>';
    			
    			var filesObj = document.createElement('div');
    				filesObj.className = "tree-files";
    			var filesObj = $(filesObj);
    			//递归调用
    			filesObj = flushAllListFunc(filesObj, allList[p]);
    			parentObj.append(filesObj);
    		} else {
    			wc_allUserArr[p] = allList[p];
    			innerStr = '<span class="no-child" type="member" data-id="'+p+'"><img class="avatar no-login" src="./default_34_34.jpg" width="22px">'+allList[p]+'</span>'+innerStr;
    		}
    	}
    	if(false === isFolder) {
    		parentObj.append(innerStr);
    	} else {
    		parentObj.prepend('<div class="tree-folders">'+innerStr+'</div>');
    	}
    	return parentObj;
    }
    //前端获取用户在线状态
    function getUserStatus(accountid){
    	return $("#organization-structure .no-child[data-id='"+accountid+"']").find('img').hasClass('no-login') ? false : true;
    }
    //js 将php时间戳转为时间
    function timestampTodate(timestamp) {
    	var d = new Date(parseInt(timestamp) * 1000);
    	return d.getFullYear()+'-'+(d.getMonth()+1)+'-'+d.getDate()+' '+d.getHours()+':'+d.getMinutes()+':'+d.getSeconds();
    }