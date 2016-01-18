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
	         	  //say(data['fromuser'], data['touser'], data['message'], data['time']);
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
	         	  //loadHistoryMessage(data['messageList']);
	         	  break;
	           // 错误处理
	           case 'error':
	         	  switch(data['info']){
	       	          case 'erroruser':
	             	      alert(data['msg']);
	             	      webChat_errorType = true;wc_loginName = '';
	             	      break;
	       	          case 'loginconflict':
	             	      alert(data['msg']);
	             	      webChat_errorType = true;
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
	 	  if(!webChat_errorType){
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
    	loadNearestContactFunc(nearestContactContainer, data);
    	nearestContactContainer.treeViewModify({});
    }
    //更新在线用户
    function addOnlineList(data) {
    	lightOnlineUserList(data);
    }
    
    
    
    /*******接口函数********/
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
    //更新最近联系人列表
    function loadNearestContactFunc(parentObj, recentList) {
    	var innerStr = '';
    	var childfileStr = '';
    	for(var p in recentList) {
    		var contactArr = recentList[p].split(',');
    		//单用户聊天
    		if(contactArr.length === 2) {
    			for(var q in contactArr) {
    				if(contactArr[q] === wc_loginName) continue;
    				innerStr += '<span class="no-child" type="personal" data-id="'+contactArr[q]+'"><img class="avatar no-login" src="./default_34_34.jpg" width="22px">'+wc_allUserArr[contactArr[q]]+'<b class="unread">0</b></span>';
    			}
    		//群用户聊天
    		} else if(contactArr.length > 2) {
    			var groupNames = [];
    			innerStr += '<span type="group" data-id="'+recentList[p]+'">';
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
    	}
    	innerStr = '<div class="tree-folders">'+innerStr+'</div>';
    	parentObj.append(innerStr+childfileStr);
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