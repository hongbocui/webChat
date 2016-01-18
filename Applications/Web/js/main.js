$(function(){
	$('.pop-smiley').tips({'target':['left',5]})
	$('.pop-keys').tips({'target':['right',3]});
	//表情、快捷发送浮动层
	$('.smile').click(function(event){
		$('.pop-smiley').show();
		event.stopPropagation(event);
	});
	$('.more').click(function(event){
		$('.pop-keys').show();
		event.stopPropagation();
	});
	$('.pop-smiley').click(function(event){
		$(this).show();
		event.stopPropagation();
	});
	$('.pop-keys').click(function(event){
		$(this).show();
		event.stopPropagation();
	});
	//表情插入输入框
	$('.pop-smiley a').click(function(){
		fn('chat-input','<img src="./images/smiley/'+$(this).attr('data-face').substr(4)+'.gif" />')
	}).mouseover(function(){
		$(this).css('background','url("./images/smiley/'+$(this).attr('data-face').substr(4)+'.gif") no-repeat 1px 2px #FFFFFF')
	}).mouseout(function(){
		$(this).removeAttr('style');
	});
	$('.button').click(function(){
		var msg = $('.chat-input').html();
		var face_pattern = /<img\b\ssrc="\.\/images\/smiley\/(\d+)\.gif">/g;
		var br_pattern = /<\/div>/g;
		var clear_tag_pattern = /<\/?(\w+\b)[^>]*>(?:([^<]*)<\/\1[^>]*>)?/g;
		var compile_pattern = /\[\\([a-z]+)(\d+)?\]/g;
		console.log(msg);
		//转义
		msg = msg.replace(face_pattern, '[\\face$1]');
		msg = msg.replace(br_pattern, '[\\br]');
		msg = msg.replace(clear_tag_pattern, '$2');
		console.log(msg);
		//还原
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
		console.log(msg);
		$('<div/>').addClass('row self').html(
			'<div class="user-avatar"><img class="avatar" src="./default_34_34.jpg"></div> \
			<div class="message-detail"> \
				<p>&nbsp;</p> \
				<div class="message-box"> \
					'+msg+'&nbsp; \
					<i class="chat-icon message-box-pike"></i> \
				</div> \
			</div>'
		).appendTo($('.logs'));
		//情况输入框
		$('.chat-input').html('');
		$('.logs').scrollToBottom();
	})
	//搜索框
	$('.search').click(function(event){
		if($(this).find('.empty').length)
			return false;
		$(this).animate({'margin':0,'width':'188px','height':'38px','border-radius':'15px','border-radius':0,'line-height':'38px','padding':'0 5px'},'fast').css({'background':'#fff','text-align':'left'}).append($('<div/>').addClass('chat-icon empty')).find('.search-contact').animate({'width':'150px'}).focus();
		$('.empty').on('click',function(){
			$(this).siblings('input').val('').focus().parent().siblings('.search-result').hide();
		});
		event.stopPropagation();
	});
	$('.search-contact').bind('input propertychange',function(){
		if($(this).val() != '') {
			//从组织架构中筛选
			$('.search-result').show();
		}else{
			$('.search-result').hide();
		}
	});


	$('.tab').click(function(){
		$(this).addClass('active').siblings('.tab').removeClass('active');
		$(this).parent().siblings('.'+$(this).attr('tab-name')).addClass('active').siblings('.tab-detail').removeClass('active');	
	});
	$('.tab-detail.structure').treeViewModify({});
	$('.tab-detail.recent').treeViewModify({});
	//新消息，若最近联系人中存在，则将其移至最近联系人中顶部
	//$('待移动元素').moveTreeTop($('.recent'));
	$(".tab-detail.structure,.chat-box .member").on('dblclick','span[type=member]',function(){
		//添加之前先判断是否有这个联系人
		if($('.tab-detail.recent span[type=personal][data-id='+$(this).attr('data-id')+']').length) {
			$('.tab-detail.recent span[type=personal][data-id='+$(this).attr('data-id')+']').dblclick();
			return false;
		}
		$('.tab-detail.recent').addTree({
			'title'  : $(this).html().replace(/<(\w+\b)[^>]+>(.*<\/\1>)*/g,''),
			'member' : [{'username':$(this).find('.username').html(),'avatar':$(this).find('.avatar').attr('src'),'attr':{'data-id':$(this).attr('data-id')}}]
		});
		//$('.tab[tab-name=recent]').click();
		//双击recent中的这个联系人
		$('.tab-detail.recent span:first').dblclick();
	})
	$("body").on('dblclick','.tab-detail.recent span',function(){
		//$(this).moveTreeTop($('.tab-detail.recent'));
		//ajax 获取最近聊天记录，如果是群获取创建时间，否则获取联系人基本资料
		var	_title = $(this).html().replace(/<(\w+\b)[^>]+>(.*<\/\1>)*/g,'');
		var _member = $(this).attr('data-id');
		$('.home').hide();
		$('.message').css('margin-right','0px');
		$('.chat-box .member').hide();
		//如果是群组，更新群组成员
		if($(this).attr('type')=='group') {
			$('.message').css('margin-right','180px');
			$('<div/>').addClass('tree-folders').append($(this).clone().find('.unread').remove().end()).append($(this).next('.tree-files').clone().show()).appendTo($('.chat-box .member').show().html(''))
			
		}
		//联系人信息更新
		$('.contact-msg').attr('chatuser',_member);
		$('.contact-msg h1').html(_title);
		$('.chat-input').focus();
		
	})
	$("body").on('mouseover','.tab-detail.active span',function(event){
			var _this = $('.tab-detail.active')
			if(_this.children('.tree-item-bg').length==0)
			$('<div/>').addClass('tree-item-bg').appendTo(_this);
			_this.children('.tree-item-bg').css({'top':$(this).offset().top-_this.offset().top+_this.scrollTop()})
		}
	)/*.on('mousedown','.tab-detail.active span',function(event){
			var _this = $('.tab-detail.active');
			if(event.which == 3)
			_this.rightMouse({'data':{'发送消息':'#','从最近联系人中删除':'javascript:$(".tab-detail.recent span:eq('+$(this).prevAll('span').length+')").removeTree();','查看消息记录':'#','查看资料':'#','成员列表':'#'},'limit':{'group':['发送消息','从最近联系人中删除','查看消息记录','成员列表'],'personal':['发送消息','从最近联系人中删除','查看消息记录','查看资料'],'member':['发送消息','查看消息记录','查看资料'],'dept':['成员列表']},'type':$(this).attr('type'),'top':event.pageY-_this.offset().top,'left':event.pageX-_this.offset().left,'event':event});
	})*/
	$('.tab-detail,.member').rightMouse({
		'data':{
			'发送消息':'#',
			'从最近联系人中删除':'javascript:$(".tab-detail.recent span:eq(eval##$(this).prevAll(\'span\').length##)").removeTree();',
			'查看消息记录':'#',
			'查看资料':'#',
			'成员列表':'#'
		},
		'limit':{
			'group':['发送消息','从最近联系人中删除','查看消息记录','成员列表'],
			'personal':['发送消息','从最近联系人中删除','查看消息记录','查看资料'],
			'member':['发送消息','查看消息记录','查看资料'],
			'dept':['成员列表']
		},
		'elem':'span'
	});
	$(document).click(function(){
		$('.pop-smiley').hide();
		$('.pop-keys').hide();
		if($('.search .search-contact').val() != "")
			return false;
		$('.search').removeAttr('style').find('.search-contact').val('').css({'width':'26px'}).end().find('.empty').remove();
	})
})
