(function($){
	$.fn.treeView = function(param){
		var _this = $(this),
		openTree = function(obj) {
				if(!obj.hasClass('tree-folders')) {
					var childIndex = obj.prevAll('span:not(.no-child)').length;
					obj.parent().siblings('.tree-files:eq('+childIndex+')').clone().show().insertAfter(obj);
					//保留最外层tree-files以备其他地方使用
					if(obj.parent().parent().hasClass('tree-files'))
						obj.parent().siblings('.tree-files:eq('+childIndex+')').html('');
					if(obj.nextAll('span').length)
						obj.next().addClass('tree-main-line');
					if(obj.next().children('.tree-folders').length)
						obj = obj.next().children('.tree-folders');
					else
						obj = obj.next();
					obj.prepend($('<div/>').addClass('tree-icon tree-main-line-usable'));
				}
				var indent = parseInt(obj.closest('.tree-files').prev().children('.tree-icon').css('margin-left'))+20 || 0;
				obj.find('.tree-main-line-usable').css({'left':indent-13+'px'})
				obj.children('span').each(function(i){
					var _treeIcon = $('<div/>').addClass('tree-icon tree-button-bg').css({'margin-left':indent+'px'});
					if($(this).next().length == 0)
						_treeIcon.addClass('tree-end-line')
					else
						_treeIcon.addClass('tree-middle-line')
					if(!$(this).hasClass('no-child'))
						$('<i/>').addClass('tree-icon tree-button tree-button-close').appendTo(_treeIcon)
					_treeIcon.prependTo($(this));
				});
				if(param.defaultOpen){
					obj.children('span:not(.no-child)').click();
				}
			},
			//只是隐藏, 里边的状态不管
			closeTree = function(obj) {
				obj.hide()
			};
			
			_this.on('click', 'span:not(.no-child)', function(){
					if($(this).next('.tree-files').length) {
						if($(this).next().css('display') == 'none'){
							$(this).next().show();
							$(this).find('.tree-button').addClass('tree-button-open').removeClass('tree-button-close');
						}else{
							$(this).find('.tree-button').addClass('tree-button-close').removeClass('tree-button-open');
							closeTree($(this).next());
						}
					}else{
						$(this).find('.tree-button').addClass('tree-button-open').removeClass('tree-button-close');
						openTree($(this));
					}
			});
			openTree(_this.children('.tree-folders'));
			_this.find('.tree-icon:first').removeClass('tree-middle-line').addClass('tree-start-line')
	};
    /**
     * @param int target 移动到的位置
     *
     **/
	$.fn.moveTree = function(target) {
			var _this = $(this);
			var _parent = _this.parent();
			var currentPos = _this.prevAll('span').length;
			var targetPos = _parent.children('span:eq('+target+')');
			if(target == currentPos) return false;
			//目标位置是通过目标位置所在元素来定位的
            //目标位置在当前元素之后，当前元素移动之后目标位置上移，所以目标位置加1
            if(target > currentPos) target++;
			if(!_this.hasClass('no-child'))
                _parent.nextAll('.tree-files:eq('+_this.prevAll('span:not(.no-child)').length+')').insertBefore(_parent.nextAll('.tree-files:eq('+target+')'))
 			if(_this.next('.tree-files').length){
				_this.next('.tree-files').insertBefore(targetPos);
				_this.insertBefore(targetPos.prev('.tree-files'));
			}else{	
				_this.insertBefore(targetPos);
			}
			_parent.iconCorrect();
		};
	$.fn.addTree = function(data) {
		var _this = $(this),
			_title = $('<div/>'),
			_html = $('<div/>'),
			_member = $('<div/>'),
			title_attr = {},
			member_attr = {};
		for(var x in data.attr) {
			title_attr[x] = data.attr[x];
		}
		$('<span/>').attr(title_attr).html(data.title).appendTo(_title);
		_title.addClass('tree-folders').appendTo(_html);
		if(data.member && data.member.length >= 2) {
			for(var i=0; i<data.member.length; i++) {
				member_attr = {};
				for(var x in data.member[i].attr) {
					member_attr[x] = data.member[i].attr[x];
				}
				$('<span/>').attr(member_attr).addClass('no-child').html(data.member[i].username).appendTo(_member);
			}
			_member.addClass('tree-files').appendTo(_html);
		}else{
			_title.children('span').addClass('no-child');
		}
        if(_this.hasClass('tree-folders') || _this.prev('span').length)
		    _html.treeView({});
		_title.children().appendTo(_this);
		if(data.member && data.member.length >= 2)
			_member.appendTo(_this.parent());
		_this.iconCorrect();
		data.callback && data.callback(_this.children('span:last'));
		return _this.children('span:last');
	};
	$.fn.removeTree = function() {
		var _this=$(this);
		var _parent=_this.parent();
		if(!_this.hasClass('no-child')) {
			_this.parent().siblings('.tree-files:eq('+_this.prevAll('span:not(.no-child)').length+')').remove();
			_this.next('.tree-files').remove();
		}
		_this.remove();
		_parent.iconCorrect();
	}
	//Tree图标校正
	$.fn.iconCorrect = function(){
		var _this = $(this);
		_this.children('span').children('.tree-start-line').removeClass('tree-start-line').addClass('tree-middle-line');
		_this.children('span').children('.tree-end-line').removeClass('tree-end-line').addClass('tree-middle-line');
		_this.children('span:first').children('.tree-icon').removeClass('tree-middle-line').addClass('tree-start-line');
		_this.children('span:last').next('.tree-files').removeClass('tree-main-line');
		if(_this.children('span').length == 1) return false;
		_this.children('span:last').children('.tree-icon').removeClass('tree-middle-line').addClass('tree-end-line');
		_this.children('span:first').next('.tree-files').addClass('tree-main-line');
	};
    $.fn.treeMember = function(){
        if($(this).next('.tree-files').length)
            return $(this).next()
        return $(this).parent().nextAll('.tree-files').eq($(this).prevAll('span:not(.no-child)').length);
    }
})(jQuery);
