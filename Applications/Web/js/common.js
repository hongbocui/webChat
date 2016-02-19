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
}
