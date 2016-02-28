function getLocalTime(nS) {
    return new Date(parseInt(nS) * 1000).toLocaleString();
}
function avatar(obj) {
    var member = obj;
    var avatar = $('<div/>').addClass('group-avatar');
    if(!obj.hasClass('no-child')) {
        if(obj.next('.tree-files').length){
            member = obj.next('.tree-files').children();
        }else{
            member = obj.parent().nextAll('.tree-files:last').children();
        }
    }
    member.each(function(i){
        $('<img/>').addClass('avatar').attr({'width':'22px','src':$(this).attr('data-avatar')}).appendTo($(this));
    if(i<4)
        avatar.append($('<img/>').addClass('avatar').attr({'width':'10px','src':$(this).attr('data-avatar')}))
    })
    if(member.length !=1)
        avatar.appendTo(obj);
    if(!obj.parent().hasClass('tree-folders'))
        obj.children('.tree-icon').css('margin-left','20px');
}
function newBroadcast(data) {
	//新增一个通知
	$.get('/chatapi.php?c=broadcast&a=AddUnreadNum&accountid='+wc_loginName);
    $('.broadcast').addClass('rainbow').find('.notice').css('display','block');
    if(data.title)
    	$('.pop-broadcast').attr({"d-time":data.time,"d-fromuser":data.fromuser,"d-touserTitle":data.touserTitle}).show().find('.tit').html(data.title).end().find('.con').html(data.content);
}
function readBroadcast() {
	$('.broadcast').removeClass('rainbow').find('.notice').css('display','none');
	$('.pop-broadcast').hide().find('.tit').html('');//将小提醒框置空
	$.get('/chatapi.php?c=broadcast&a=DelUnreadNum&accountid='+wc_loginName);
}

//写入/设置cookie
function writeCookie(name, value, day) {
    expire = "";
    expire = new Date();
    expire.setTime(expire.getTime() + day * 24 * 3600 * 1000);
    expire = expire.toGMTString();
    document.cookie = name + "=" + escape(value) + ";expires=" + expire;
}
//读取cookie
function readCookie(name) {
    cookieValue = "";
    search1 = name + "=";
    if (document.cookie.length > 0) {
        offset = document.cookie.indexOf(search1);
        if (offset != -1) {
            offset += search1.length;
            end = document.cookie.indexOf(";", offset);
            if (end == -1) end = document.cookie.length;
            cookieValue = unescape(document.cookie.substring(offset, end));
        }
    }
    return cookieValue;
}
//删除指定名称的cookie，可以将其过期时间设定为一个过去的时间
function delCookie(name) {
    var date = new Date();
    date.setTime(date.getTime() - 10000);
    document.cookie = name + "=a; expires=" + date.toGMTString();
}
