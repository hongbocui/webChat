$(function(){
    $('.recent').treeView({})
	$('.pop-smiley').tips({'target':['left',5]})
	$('.pop-keys').tips({'target':['right',3]});
	$('.pop-broadcast').tips({'target':['left',5]});
	$('.pop-groupName').tips({'target':['left',5]});
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
		$(this).hide();
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
	$('.send').click(function(){
		var msg = $('.chat-input').html();
		sendToWsMsg(msg);
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

	//点击联系人
	$('.tab').click(function(){
		$(this).addClass('active').siblings('.tab').removeClass('active');
		$(this).parent().siblings('.'+$(this).attr('tab-name')).addClass('active').siblings('.tab-detail').removeClass('active');	
	});
	//新消息，若最近联系人中存在，则将其移至最近联系人中顶部
	//$('待移动元素').moveTreeTop($('.recent'));
//	$(".tab-detail.structure,.chat-box .member").on('dblclick','span[type=member]',function(){
//		//添加之前先判断是否有这个联系人
//		if($('.tab-detail.recent span[type=personal][data-id="'+$(this).attr('data-id')+'"]').length) {
//			$('.tab-detail.recent span[type=personal][data-id="'+$(this).attr('data-id')+'"]').dblclick();
//			return false;
//		}
//		$('.tab-detail.recent').addTree({
//			'title'  : $(this).html().replace(/<(\w+\b)[^>]+>(.*<\/\1>)*/g,''),
//			'member' : [{'username':$(this).find('.username').html(),'avatar':$(this).find('.avatar').attr('src'),'attr':{'data-id':$(this).attr('data-id'),'type':'member','class':$(this).find('.avatar').hasClass('no-login') ? 'no-login' : ''}}],
//			'attr'   : {'data-id':$(this).attr('data-id'),'type':'personal','class':$(this).find('.avatar').hasClass('no-login') ? 'no-login' : ''}
//		});
//		//$('.tab[tab-name=recent]').click();
//		//双击recent中的这个联系人
//		$('.tab-detail.recent span:first').dblclick();
//	})
	$("body").on('dblclick','.tab-detail.active span:not([type=dept])',function(){
		//$(this).moveTreeTop($('.tab-detail.recent'));
		//ajax 获取最近聊天记录，如果是群获取创建时间，否则获取联系人基本资料
		var	_title = $(this).html().replace(/<(\w+\b)[^>]+>(?:.*?<\/\1>)?/g,'');
		var _member = $(this).attr('data-id');
		$('.home').hide();
		$('.message').css('margin-right','0px');
		$('.chat-box .member').hide();
		$('.contact-msg').attr('chatid',_member);
		//如果是群组
		if($(this).attr('type')=='group') {
			$('.contact-msg p').html(getAdminByChatid(_member)+' 创建于'+timestampTodate($(this).attr('ctime')));
			//更新一下群组的生存时间
			$.get('/chatapi.php?c=group&a=expires&chatid='+_member);
			//将屏蔽消息按钮显示
			$(".remind").css('display', '');
			
			if(readCookie(getNowChatId())){
				$(".remind").css({"filter":"alpha(opacity=50)",opacity:0.4});
			} else {
				$(".remind").css({"filter":"alpha(opacity=50)",opacity:1});
			}
			
		}else{
			var personalData = getPersonalData(_member);
			$('.contact-msg p').html('部门：'+personalData.deptDetail+'&nbsp;&nbsp;&nbsp;&nbsp;邮箱：'+personalData.email+'&nbsp;&nbsp;&nbsp;&nbsp;电话：'+personalData.tel);
			//将屏蔽按钮去掉
			$(".remind").css('display', 'none');
		}
		//未读消息变为0
		if($(this).find('b').length){
			loadUnreadMsgFun(_member, 0); //前端
			//服务端
			$.get('/chatapi.php?c=message&a=delunreadmsg&chatid='+_member+'&accountid='+wc_loginName);
		}
		//加载本地消息
		historyInDialog(_member);
        //加载传输文件
        if(uploader.running != null && uploader.running.file.chatid == getNowChatId() && uploader.persentages[uploader.running.file.id].total != uploader.persentages[uploader.running.file.id].loaded) {
            var percent = Math.round(uploader.persentages[uploader.running.file.id].loaded/uploader.persentages[uploader.running.file.id].total*100);
            $('.logs').append('<div class="row self '+uploader.running.file.id+'"><div class="user-avatar"><img class="avatar" src="./default_34_34.jpg"></div><div class="message-detail"><p>&nbsp;</p><div class="message-box"><div class="files"><div class="file-msg" style="background-image:url(./images/filetype/icon_'+fileIcon(uploader.running.file.ext.toLowerCase())+'_256.png)"><p class="text-cut">'+uploader.running.file.name+'</p><p>'+fileSizeFormat(uploader.running.file.size)+'</p></div><div class="attach-upload"><div class="progress"></div><div class="loaded" style="width:'+percent/100*200+'px"></div><div class="attach-upload-control"><span class="loaded-persent">'+percent+'%</span></div></div><div class="file-tool" style="display:none">等待上传</div></div><i class="chat-icon message-box-pike"></i></div></div></div>').scrollToBottom();
        }
        if(uploader.pending.length) {
            for(var x in uploader.pending) {
                if(uploader.pending[x].file.chatid != getNowChatId()) continue;
                $('.logs').append('<div class="row self '+uploader.pending[x].file.id+'"><div class="user-avatar"><img class="avatar" src="./default_34_34.jpg"></div><div class="message-detail"><p>&nbsp;</p><div class="message-box"><div class="files"><div class="file-msg" style="background-image:url(./images/filetype/icon_'+fileIcon(uploader.pending[x].file.ext.toLowerCase())+'_256.png)"><p class="text-cut">'+uploader.pending[x].file.name+'</p><p>'+fileSizeFormat(uploader.pending[x].file.size)+'</p></div><div class="attach-upload" style="display:none"><div class="progress"></div><div class="loaded"></div><div class="attach-upload-control"><span class="loaded-persent">0%</span></div></div><div class="file-tool">等待上传</div></div><i class="chat-icon message-box-pike"></i></div></div></div>').scrollToBottom();
            }
        }
		//联系人信息更新
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
			'发送消息':'javascript:$(".right-mouse-base").dblclick();',
			'从最近联系人中删除':'javascript:delRecentchatMember($(".right-mouse-base").attr("data-id"));$(".right-mouse-base").removeTree();',
			'查看消息记录':'#',
			'查看资料':'#',
			'成员列表':'#',
			'更改群名称':'javascript:editGroupName();',
            '发送广播消息':'javascript:$(this).attr({"modal-title":"发送广播消息","modal-data":"broadcast.html","class":"show-modal"}).modal();'
		},
		'limit':{
			'group':['发送消息','从最近联系人中删除','查看消息记录','成员列表','更改群名称'],
			'personal':['发送消息','从最近联系人中删除','查看消息记录','查看资料'],
			'member':['发送消息','查看消息记录','查看资料'],
			'dept':['成员列表','发送广播消息']
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
	//修改群名称
	$(".pop-groupName input").keyup(function(e){
		if(e.which === 13){
			$(".pop-groupName").hide();
			var chatid = $(".pop-groupName").attr("data-id");
			wc_ws.send(JSON.stringify({"type":"systemNotice","action":"grouptitle","chatid":chatid,"title":$(this).val()}));
		}
	});
    /*$('.recent').on('click','span[type=group]',function(){
        if($('.pop-groupName:visible').length)
            $('.pop-groupName').css({'top':$('.recent span[data-id="'+$('.pop-groupName').attr('data-id')+'"]').offset().top-47+'px'});
    })*/
	//屏蔽&取消屏蔽 消息提醒
	$(".remind").click(function(){
		var nowChatid = getNowChatId();
		if(nowChatid.indexOf('--') > -1) return;//单人聊天没有屏蔽消息的功能
		var cookieKey = nowChatid;
		if(readCookie(cookieKey)){
			delCookie(cookieKey);
			wc_ws.send(JSON.stringify({"type":"systemNotice","chatid":nowChatid,"action":"opennotice"}));
			$(".remind").css({"filter":"alpha(opacity=50)",opacity:1});
		}else{
			writeCookie(cookieKey, '1', 30);
			wc_ws.send(JSON.stringify({"type":"systemNotice","chatid":nowChatid,"action":"closenotice"}));
			$(".remind").css({"filter":"alpha(opacity=50)",opacity:0.4});
		}
	});
})
function editGroupName() {
	//console.log('wait')
    /*$('.pop-groupName').remove();
    var groupName = $('<div/>');
    groupName.addClass('pop-groupName').css({'left':$('.right-mouse-base').offset().left+20+'px','top':$('.right-mouse-base').offset().top-47+'px'}).appendTo($('body'));
    groupName.append('<a href="javascript:;" class="tips-close button-close">&times;</a> \
                        <input type="text" name="groupName">')
               .tips({'left':5});
    groupName.show();*/
    $('.pop-groupName').attr('data-id',$('.right-mouse-base').attr('data-id')).css({'left':$('.right-mouse-base').offset().left+20+'px','top':$('.right-mouse-base').offset().top-47+'px'}).show();
}
