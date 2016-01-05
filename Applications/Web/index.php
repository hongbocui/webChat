<html><head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>webChat</title>
  <script type="text/javascript">
  //WebSocket = null;
  </script>
  <link href="./css/bootstrap.min.css" rel="stylesheet">
  <link href="./css/style.css" rel="stylesheet">
  <!-- Include these three JS files: -->
  <script type="text/javascript" src="./js/swfobject.js"></script>
  <script type="text/javascript" src="./js/web_socket.js"></script>
  <script type="text/javascript" src="./js/json.js"></script>
  <script type="text/javascript" src="./js/jquery.min.js"></script>
  <script type="text/javascript">
    if (typeof console == "undefined") {    this.console = { log: function (msg) {  } };}
    WEB_SOCKET_SWF_LOCATION = "/swf/WebSocketMain.swf";
    WEB_SOCKET_DEBUG = true;
    var ws, name, client_list={},timeid, reconnect=false, errorType=false, isGetSomeone = '';

    function init() {
       // 创建websocket
    	ws = new WebSocket("ws://"+document.domain+":7272");
      // 当socket连接打开时，输入用户名
      ws.onopen = function() {
    	  timeid && window.clearInterval(timeid);
    	  if(!name)
    	  {
  		    show_prompt();
    	  }
    	  if(!name) {
    		  return ws.close();
   		  }
    	  if(reconnect == false)
    	  {
        	  // 登录
    		  var login_data = JSON.stringify({"type":"login","client_name":name});
    		  console.log("发送登录数据:"+login_data);
  		      ws.send(login_data);
    		  reconnect = true;
    	  }
    	  else
    	  {
        	  // 断线重连
        	  var relogin_data = JSON.stringify({"type":"login","client_name":name});
    		  console.log("发送登录数据:"+relogin_data);
    		  ws.send(relogin_data);
    	  }
      };
      // 当有消息时根据消息类型显示不同信息
      ws.onmessage = function(e) {
    	console.log(e.data);
        var data = JSON.parse(e.data);
        switch(data['type']){
              // 服务端ping客户端
              case 'ping':
            	ws.send(JSON.stringify({"type":"pong"}));
                break;;
              // 登录 更新用户列表
              case 'login':
                  //{"type":"re_login","client_name":"xxx","client_list":"[...]","all_list":"[...]","time":"xxx"}
            	  add_online_client(data['client_name']);
                  console.log(data['client_name']+"登录成功");
                  break;
              // 断线重连，只更新用户列表
              case 're_login':
              	  //{"type":"re_login","client_name":"xxx","client_list":"[...]","all_list":"[...]","time":"xxx"}
            	  flush_client_list(data['client_list']);
            	  flush_all_list(data['all_list']);
            	  console.log(data['client_name']+"重连成功");
                  break;
              // 发言
              case 'say':
            	  //{"type":"say","fromuser":xxx,"touser":xxx,"message":"xxx","time":"xxx"}
            	  say(data['fromuser'], data['touser'], data['message'], data['time']);
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
                	      alert('用户名不存在');
                	      errorType = true;name = '';
                	      break;
            	      default:
                	      break;
            	  }
            	  break; 
             // 用户退出 更新用户列表
              case 'logout':
            	  //{"type":"logout","client_name":xxx,"time":"xxx"}
            	 delete_offline_client(data['client_name']);
        }
      };
      ws.onclose = function() {
    	  console.log("连接关闭，定时重连");
    	  // 定时重连
    	  window.clearInterval(timeid);
    	  if(!errorType){
    		  timeid = window.setInterval(init, 3000);
          }
      };
      ws.onerror = function() {
    	  console.log("出现错误");
      };
    }
    function say(fromuser, touser, message, time){
        var nowChatUser = $("#nowChatTo").html();
        var userlist_online_window = $("#userlist-online");
        makeHistoryList(fromuser, touser, message, time);
        
        var chatList = nowChatUser.split(',');
            chatList.push(name);
            chatList.sort();
            touser.sort();

        //显示为最近联系人
        var chatusertmp = '';
		if(touser.join(',').indexOf(','+name) > -1){
			chatusertmp = touser.join(',').replace(','+name, '');
        }else{
        	chatusertmp = touser.join(',').replace(name+',', '');
        }
    	var userlist_nearly_window = $("#userlist-nearly");
        if(userlist_nearly_window.html().indexOf('>'+chatusertmp+'<') < 0){//如果不加><,单用户如果存在群聊中，则单用户到不了最近联系人
        	userlist_nearly_window.prepend('<li><a>'+chatusertmp+'</a></li>');
        }
        
        if(chatList.toString() == touser.toString() || fromuser==name){
        	$("#dialog").append('<div class="speech_item">'+fromuser+' <br> '+time+'<div style="clear:both;"></div><p class="triangle-isosceles top">'+message+'</p> </div>');
        	$("#dialog").scrollTop(100000);
        }else{
            //最近联系人列表标红，在线用户没必要标红
        	userlist_nearly_window.find("li").each(function(){
                if($(this).find("a").html() == chatusertmp){//
                	$(this).find("a").css("color", "red");
                }
            });
        }
    }
    // 提交对话
    function onSubmit() {
      var input = document.getElementById("textarea");
      var toChatUser = $("#nowChatTo").html();
      var chatList = toChatUser.split(',');
          chatList.push(name);
          chatList.sort();
      if(!toChatUser){
  	      alert("请选择聊天对象"); return false;
      }
      if($("#groupChat").html() == '确认'){
          //模拟点击确认按钮
          var btn = document.getElementById("groupChat");
          var event = document.createEvent('MouseEvents');
          event.initMouseEvent('click', true, true, document.defaultView, 0,0,0,0,0, false, false, false, false, 0, null);
          btn.dispatchEvent(event);
      }
      ws.send(JSON.stringify({"type":"say","touser":chatList,"content":input.value}));
      input.value = "";
      input.focus();
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
        	ws.send(JSON.stringify({"type":"history","fromuser":fromuser,"touser":touser}));
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
    //刷新用户列表
    function flush_all_list(allList){
    	var userlist_all_window = $("#userlist-all");
    	userlist_all_window.empty();
    	for(var p in allList){
    		userlist_all_window.append('<li><a>'+allList[p]+'</a></li>');
        }
    }
    //刷新在线用户列表
    function flush_client_list(client_list){
    	var userlist_online_window = $("#userlist-online");
    	userlist_online_window.empty();
    	for(var p in client_list){
    		userlist_online_window.append('<li id="'+client_list[p]+'"><a>'+client_list[p]+'</a></li>');
        }
    }
    //删除下线用户
    function delete_offline_client(client_name){
		$("#"+client_name).remove();
    }
    //增加一个在线用户
    function add_online_client(client_name) {
    	$("#userlist-online").append('<li id="'+client_name+'"><a>'+client_name+'</a></li>');
    }
    //登陆时加载用户最近的联系人
    function loadRecentMembers(membersList){
    	var userlist_nearly_window = $("#userlist-nearly");
    	for(var p in membersList){

    		//显示为最近联系人
            var chatusertmp = '';
    		if(membersList[p].indexOf(','+name) > -1){
    			chatusertmp = membersList[p].replace(','+name, '');
            }else{
            	chatusertmp = membersList[p].replace(name+',', '');
            }
            
    		userlist_nearly_window.append('<li><a>'+chatusertmp+'</a></li>');
        }
    }
    //如果有离线消息，则在最近联系人中标红
    function loadIgnoreMessage(messageList){
    	var userlist_nearly_window = $("#userlist-nearly");
        for(var p in messageList){
            messageList[p].touser.sort();
        	//显示为最近联系人,并且标红
            var chatusertmp = '';
    		if(messageList[p].touser.join(',').indexOf(','+name) > -1){
    			chatusertmp = messageList[p].touser.join(',').replace(','+name, '');
            }else{
            	chatusertmp = messageList[p].touser.join(',').replace(name+',', '');
            }
            if(userlist_nearly_window.html().indexOf('>'+chatusertmp+'<') < 0){//如果不加><,单用户如果存在群聊中，则单用户到不了最近联系人
            	userlist_nearly_window.prepend('<li><a style="color:red">'+chatusertmp+'</a></li>');
            }else{
            	//最近联系人列表标红
            	userlist_nearly_window.find("li").each(function(){
                    if($(this).find("a").html() == chatusertmp){//
                    	$(this).find("a").css("color", "red");
                    }
                });
            }
        }
    }
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
    //向聊天容器中走数据的时候都走这个
    function chatInDialogContainer(messageList, gotoBott){
        var dialogContainer = $("#dialog");
        for(var p in messageList){
    		dialogContainer.append('<div class="speech_item">'+messageList[p].fromuser+' <br> '+messageList[p].time+'<div style="clear:both;"></div><p class="triangle-isosceles top">'+messageList[p].message+'</p> </div>');
        }
        if(gotoBott){
        	dialogContainer.scrollTop(100000);
        }
    }
    // 输入姓名
    function show_prompt(){  
        name = prompt('输入你的名字：', '');
        if(!name || name=='null'){  
            alert("输入名字为空或者为'null'，请重新输入！");  
            show_prompt();
        }
    }
    </script>
</head>
<body onload="init();">
    <div class="container">
	    <div class="row clearfix">
	        <div class="col-md-1 column">
	        </div>
	        <div class="col-md-6 column">
	           <div class="thumbnail">
	               <div class="caption" id="dialog" style="height:100px; overflow:auto;" >
	               </div>
	           </div>
	           <form onsubmit="onSubmit(); return false;">
	                <p>我是<b id="chat_myname" ></b>正在与<b id="nowChatTo" ></b>聊天中<button id="groupChat">拉人</button></p>
                    <textarea class="textarea thumbnail" id="textarea"></textarea>
                    <div class="say-btn"><input type="submit" class="btn btn-default" value="发表" /></div>
               </form>
	        </div>
	        <div class="col-md-3 column">
	           <div class="thumbnail">
                   <div class="caption">
                   <h4>最近聊天</h4>  
                   <ul id="userlist-nearly">
                   </ul>
                   </div>
               </div>
	           <div class="thumbnail">
                   <div class="caption">
                   <h4>在线用户</h4>
                   <ul id="userlist-online">
                   </ul>
                   </div>
               </div>
	           <div class="thumbnail">
                   <div class="caption">
                   <h4>全部用户</h4>
                   <ul id="userlist-all">
                   </ul>
                   </div>
               </div>
	        </div>
	    </div>
    </div>
    <script src="js/webchat.js"></script>
</body>
</html>
