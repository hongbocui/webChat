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
