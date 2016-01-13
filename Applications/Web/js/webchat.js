$(function(){
    	//点击用户
        $("#userlist-online,#userlist-all,#userlist-nearly").on("click", 'li', function(){
            var toChatUser = $(this).find("a").html();
            if(toChatUser == name){
                alert("不可以与自己聊天");return false;
            }
            $("#chat_myname").html(name);
            var groupChatMembers = $("#nowChatTo").html();
            //拉人事件
            if($("#groupChat").html() == '确认'){
                if(groupChatMembers.indexOf(toChatUser) < 0){//防止重复
                    if(groupChatMembers){
                    	$("#nowChatTo").html(groupChatMembers+','+toChatUser);
                    }else{
                    	$("#nowChatTo").html(toChatUser);
                    }
                }
                	
            }else{
            	$("#dialog").empty();
            	$("#nowChatTo").html(toChatUser);
            	//加载本地历史
            	var chatList = toChatUser.split(',');
            	chatList.push(name);
            	chatList.sort();
                var userTOuser = chatList.join("_");
                if(window["chat"+userTOuser+"History"] != undefined){
                    var historyLog = window["chat"+userTOuser+"History"];
                    chatInDialogContainer(historyLog, true);
                //redis中取历史记录
                }else{
                	ws.send(JSON.stringify({"type":"history","fromuser":name,"touser":chatList}));

                	//等待redis中数据
                    var i = 0;
                    var waitHistory = function(){
                            i++;
                        	if(window["chat"+userTOuser+"History"] != undefined){
                        		chatInDialogContainer(window["chat"+userTOuser+"History"], true);
                        		clearInterval(waitTime);
                            }
                    	    if(i>50)
                    	    	clearInterval(waitTime);
                        };
                	var waitTime = setInterval(waitHistory, 10);
                	
                }
                $(this).find("a").css("color", "#428bca");
            }
        });
        //建群成功
        $("#groupChat").click(function(){
        	var groupChatMembers = $("#nowChatTo").html();
            var chatList = groupChatMembers.split(',');
            chatList.push(name);
            chatList.sort();
            var userTOuser = chatList.join("_");
            
            if($("#groupChat").html() == '拉人'){
            	isGetSomeone = userTOuser;
            	$("#groupChat").html('确认');
            }else{
                if(isGetSomeone != userTOuser){//如果聊天对象改变了，则去加载记录
                    if(window["chat"+userTOuser+"History"] != undefined){
                        var historyLog = window["chat"+userTOuser+"History"];
                        chatInDialogContainer(historyLog, true);
                    //redis中取历史记录
                    }else{
                    	ws.send(JSON.stringify({"type":"history","fromuser":name,"touser":chatList}));
    
                    	//等待redis中数据
                        var i = 0;
                        var waitHistory = function(){
                                i++;
                            	if(window["chat"+userTOuser+"History"] != undefined){
                            		chatInDialogContainer(window["chat"+userTOuser+"History"], true);
                            		clearInterval(waitTime);
                                }
                        	    if(i>50)
                        	    	clearInterval(waitTime);
                            };
                    	var waitTime = setInterval(waitHistory, 10);
                    }
                }
            	$("#groupChat").html('拉人');
            }
        	return false;
        });
        //所有用户列表。可定时一小时刷新、之后再标示在线用户
        $.getJSON('/chatapi.php?c=user&a=allusers', function(r) {
        	flush_all_list(r.data);
        });
        //在线用户列表
        $.getJSON('/chatapi.php?c=user&a=onlineusers', function(r) {
        	flush_client_list(r);
        });
        //直到用户登录后才有
        var loadRecentAndMsgTime = setInterval(function(){
        	if(name){
            	//最近联系人
                $.getJSON('/chatapi.php?c=user&a=recentcontact&accountid='+name, function(r) {
                	loadRecentMembers(r);
                });
                //离线消息列表
                $.getJSON('/chatapi.php?c=message&a=GetUnreadMsg&accountid='+name, function(r) {
                	loadIgnoreMessage(r);
                });
                clearInterval(loadRecentAndMsgTime);
            }
        },500);
    }); 