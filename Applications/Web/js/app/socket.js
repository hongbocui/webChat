define([''],function(){
    var wc_loginName='',  //用户登录名称
    wc_allUserArr = [],//用以存储所有用户的id=》name
    wc_ws = null,
    wc_reConnectTimeid,
    wc_reconnect=false,
    wc_errorType=false;
    function init(){
        var wc_ws = new WebSocket("ws://"+document.domain+":7272");
        wc_ws.onopen = function() {
            wc_reConnectTimeid && window.clearInterval(wc_reConnectTimeid);
            if(!wc_loginName) {
                showLoginPage();
                return wc_ws.close();
            }
            if(wc_reconnect == false) {
                var login_data = JSON.stringify({"type":"login","clientName":wc_loginName});
                console.log("发送登录数据:"+login_data);
                wc_ws.send(login_data);
                wc_reconnect = true;
            }else{
                var relogin_data = JSON.stringify({"type":"login","clientName":wc_loginName});
                console.log("发送登录数据:"+relogin_data);
                wc_ws.send(relogin_data);
            }
        };
        wc_ws.onmessage = function(e) {
            console.log(e.data);
            var data = JSON.parse(e.data);
            switch(data['type']){
                case 'ping':
                    wc_ws.send(JSON.stringify({"type":"pong"}));
                    break;
                case 'login':
                    lightOnlineUserList(new Array(data['clientName']));
                    break;
                case 'say':
                    console.log(document[hiddenProperty]);
                    console.log(newMsgNotinceTimer);
                    if(document[hiddenProperty] && !newMsgNotinceTimer)
                        newMsgNotinceTimer = setInterval("newMsgCount()", 200);
                    recieveMsg(data['fromuser'], data['chatid'], data['message'], data['time']);
                    break;
                case 'broadcast':
                    console.log(data);
                    break;
                case 'history':
                    loadHistoryMessage(data['messageList']);
                    break;
                case 'groupset':
                    groupUpdate(data);
                    break;
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
                case 'logout':
                    lightOfflineUserList(new Array(data['clientName']))
                    break;
            };
            wc_ws.onclose = function() {
                console.log("连接关闭");
                window.clearInterval(wc_reConnectTimeid);
                if(!wc_errorType){
                      wc_reConnectTimeid = window.setInterval(init, 3000);
            };
            wc_ws.onerror = function() {
                console.log("出现错误");
            };
    }
})
