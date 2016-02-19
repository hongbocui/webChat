define(['jquery'],function($){
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
    function tips(obj, param) {
        var _page = $('<div/>'),
        _content = $('<div/>'),
        _target = $('<div/>'),
        _position = param.target||['left',2],
        init = function() {
            createTarget();
            createContent();
            obj.html(_content).append(_target);
            if(param.pages) {
                createPages();
                obj.append(_page)
            }
            obj.hide();
        },
        createTarget = function() {
            _target.addClass('chat-icon tips-target').css(_position[0],_position[1]);
        },
        createContent = function() {
            _content.addClass('tips-box').html(obj.clone().html());
        },
        createPages = function() {
            _content.css('bottom','10px');
            var page_num = 0;
            var content_height = _content.height();
            var current_height = 0;
            var first_top = _content.children().first().offset().top;
            var deviation = 0;
            _content.children().each(function(){
                current_height = $(this).offset().top-first_top;
                if(current_height != 0 && (Math.ceil(current_height/content_height)*content_height-current_height)%content_height < $(this).height()){
page_num=Math.ceil(current_height/content_height);
                }
                $(this).css({'left':page_num*100+'%','top':page_num*(2-content_height)})
            })
            for(var i=0; i<=page_num; i++) {
                _page.append($('<i/>').addClass('page').html('&nbsp;'));
            }
            _page.addClass('tips-page').find('.page:first').addClass('active');
            _page.find('.page').bind('click',function(){
                deviation = $(this).index()-_page.find('.page.active').index();
                $(this).addClass('active').siblings().removeClass('active');
                _content.children().each(function(){
$(this).animate({'left':parseInt($(this).css('left'))-deviation*_content.width()},'fast');
                });
            });
        };
        init();
    }
    function scrollToBottom(obj) {
        var logObj = obj[0];
        setTimeout(function(){logObj.scrollTop = logObj.scrollHeight;}, 50);
    }
    function modal(obj) {
        var _title = $('<div/>'),
        _content = $('<div/>'),
        _modal = $('<div/>');
        _title.addClass('modal-title').html('<h1>'+obj.attr('modal-title')+'</h1><a href="javascript:;" class="modal-close button-close">&times;</a>').appendTo(_modal);
        _content.addClass('modal-content').load(obj.attr('modal-data')).appendTo(_modal);
        _modal.addClass('modal').appendTo($('body'));
        $('<div/>').addClass('modal-border').appendTo($('body'));
        $('<div/>').addClass('modal-bg').appendTo($('body'));
        $('.modal-close').click(function(){
            $('.modal').remove();
            $('.modal-border').remove();
            $('.modal-bg').remove();
        })
    }

    return {
            avatar : avatar,
            tips : tips,
            scrollToBottom : scrollToBottom,
            modal : modal
    }
})
