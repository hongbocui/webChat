(function(){
        // 滚动条
        // param = {
        //      'x'    : 300,
        //      'y'    : 200,
        //      'h'    : 0,    // 仅限于下拉菜单滚动条
        //      'type' : 1     // 1 只显示Y轴滚动条，2 只显示X轴滚动条，3 显示X、Y轴滚动条
        // }
        $.fn.easyScroll    = function(param){
                var _this      = this,
                scrollX        = param.x||300,
                scrollY        = param.y||200,
                type           = param.type||4,
                paddingHeight  = parseInt($(_this).css('padding-top')) + parseInt($(_this).css('padding-bottom')),
                paddingWidth   = parseInt($(_this).css('padding-left')) + parseInt($(_this).css('padding-right')),
                scrollContent  = $(_this).clone(),
                scrollCss      = {
                                'position':'relative',
                                'overflow':'hidden',
								'top':0
                },
                scrollHtml     = '<div class="scroll-box">'+scrollContent.html()+'</div>',
                onlyScrollY    = function (){
                        scrollCss.width        = parseInt($(_this).css('width'))-8;
                        scrollCss.paddingRight = parseInt($(_this).css('padding-right'))+8;
                        scrollCss.height       = scrollY;
                        scrollHtml += '<div class="scroll-y-box"><div class="scroll-y"></div></div>';
                        $(_this).css(scrollCss);
                        $(_this).html('').append(scrollHtml);
                        var originalHeight = param.h||$(_this).find('.scroll-box').height();
                        var scrollYHeight  = (scrollY + paddingHeight)/originalHeight*(scrollY + paddingHeight);
                        $(_this).find('.scroll-y').css({'height':scrollYHeight});
                        $(_this).find('.scroll-y').easyScrollY({'maxTop':scrollY + paddingHeight - scrollYHeight,'rate':(originalHeight - scrollY)/(scrollY + paddingHeight - scrollYHeight)});
                },
                onlyScrollX    = function(){
                        scrollCss.height        = parseInt($(_this).css('height'))-8;
                        scrollCss.paddingBottom = parseInt($(_this).css('padding-bottom'))+8;
                        scrollCss.width         = scrollX;
                        scrollHtml += '<div class="scroll-x-box"><div class="scroll-x"></div></div>';
                        $(_this).css(scrollCss);
                        $(_this).html('').append(scrollHtml);
                        var originalWidth = $(_this).find('.scroll-box').height()*$(_this).find('.scroll-box').width()/$(_this).height() - paddingWidth;
                        var scrollXWidth  = (scrollX + paddingWidth)/originalWidth*(scrollX + paddingWidth);
                        if(originalWidth > $(_this).width())
                            $(_this).find('.scroll-box').width(originalWidth);
                        $(_this).find('.scroll-x').css({'width':scrollXWidth});
                        $(_this).find('.scroll-x').easyScrollX({'maxLeft':scrollX + paddingWidth - scrollXWidth,'rate':(originalWidth - scrollX)/(scrollX + paddingWidth - scrollXWidth)});
                },
                scrollXY      = function(){
                        scrollCss.height        = scrollY-8;
                        scrollCss.paddingRight  = parseInt($(_this).css('padding-right'))+8;
                        scrollCss.paddingBottom = parseInt($(_this).css('padding-bottom'))+8;
                        scrollCss.width         = scrollX-8;
                        scrollHtml += '<div class="scroll-y-box"><div class="scroll-y"></div></div><div class="scroll-x-box"><div class="scroll-x"></div></div>';
                        $(_this).css(scrollCss);
                        $(_this).html('').append(scrollHtml);
                        //换算比率
                        var radio          = Math.sqrt($(_this).find('.scroll-box').height()/scrollY);
                        var originalWidth  = radio*(scrollX-8);
                        var scrollXWidth   = (scrollX + paddingWidth-10)/(originalWidth + paddingWidth -10)*(scrollX + paddingWidth-10);
                        $(_this).find('.scroll-box').width(originalWidth);
                        var originalHeight = $(_this).find('.scroll-box').height(); //防止撑破
                        var scrollYHeight  = (scrollY + paddingHeight-10)/(originalHeight + paddingHeight - 10)*(scrollY + paddingHeight-10);
                        if(radio < 1){
                            $(_this).find('.scroll-box').width('');
                            scrollXWidth = scrollX + paddingWidth - 10;
                            scrollYHeight = scrollY + paddingHeight - 10;
                        }
                        $(_this).find('.scroll-x').css({'width':scrollXWidth});
                        $(_this).find('.scroll-y').css({'height':scrollYHeight});
                        $(_this).find('.scroll-x').easyScrollX({'maxLeft':scrollX + paddingWidth - scrollXWidth-10,'rate':(originalWidth - scrollX+3)/(scrollX + paddingWidth - scrollXWidth-10)});
                        $(_this).find('.scroll-y').easyScrollY({'maxTop':scrollY + paddingHeight - scrollYHeight-10,'rate':(originalHeight - scrollY+5)/(scrollY +paddingHeight - scrollYHeight-10)});
                };
                switch(type){
                    case 1:
                        onlyScrollY();
                        break;
                    case 2:
                        onlyScrollX();
                        break;
                    case 3:
                        scrollXY();
                        break;
                    case 4:

                        break;
                }
        }
        // Y轴滚动条
        $.fn.easyScrollY   = function(param){
            var _this   = this;
            $(_this).mousedown(function(e){
                var offsetY = $(_this).offset();
                var offsetYBox = $(_this).parent().offset();
                var originalTop = offsetYBox.top;
                var y =e.pageY;
                $(_this).css({'background':'#888888','cursor':'default'});
                $(document).bind('mousemove',function(ev){
                    $('body').attr('unselectable','on').css({'-moz-user-select':'-moz-none','-moz-user-select':'none','-o-user-select':'none','-khtml-user-select':'none','-webkit-user-select':'none','-ms-user-select':'none','user-select':'none'}).bind('selectstart',function(){return false;});
                    var _y = ev.pageY - y + offsetY.top - originalTop;
                    if(_y < 0)
                        _y = 0;
                    if(_y > param.maxTop)
                        _y = param.maxTop;
                    $(_this).css({'top':_y+'px','left':0});
                    $(_this).parent().siblings('.scroll-box').css('top','-'+_y*param.rate+'px');
                });
                $(document).mouseup(function()  {  
                    $('body').attr('unselectable','off').css({'-moz-user-select':'','-moz-user-select':'','-o-user-select':'','-khtml-user-select':'','-webkit-user-select':'','-ms-user-select':'','user-select':''}).unbind('selectstart');
                    $(this).unbind("mousemove");
                    $(_this).css('background','#D8D8D8');
                });
            });
            $(_this).parent().click(function(e){
                if($(e.target).closest('.scroll-y').length > 0)
                    return false;
                var offsetY = $(_this).offset();
                var offsetYBox = $(_this).parent().offset();
                var scrollYHeight = $(_this).height();
                var scrollYBoxHeight = $(_this).parent().height();
                var y = e.pageY;
                var _y;

                _y = y - offsetYBox.top - scrollYHeight/2;
                if(offsetYBox.top + scrollYBoxHeight < y + scrollYHeight/2)
                    _y = param.maxTop;
                if(offsetYBox.top > y - scrollYHeight/2)
                    _y = 0;
                $(_this).animate({'top':_y+'px'},'fast');
                $(_this).parent().siblings('.scroll-box').animate({'top':'-'+_y*param.rate+'px'},'fast');
            });
        }
        // X轴滚动条
        $.fn.easyScrollX   = function(param){
            var _this    = this;
            $(_this).mousedown(function(e){
                var offsetX = $(_this).offset();
                var offsetXBox = $(_this).parent().offset();
                var originalLeft = offsetXBox.left;
                var x = e.pageX;
                $(_this).css({'background':'#888888','cursor':'default'});
                $(document).bind('mousemove',function(ev){
                    $('body').attr('unselectable','on').css({'-moz-user-select':'-moz-none','-moz-user-select':'none','-o-user-select':'none','-khtml-user-select':'none','-webkit-user-select':'none','-ms-user-select':'none','user-select':'none'}).bind('selectstart',function(){return false;});
                    var _x = ev.pageX - x + offsetX.left - originalLeft;
                    if(_x < 0)
                        _x = 0;
                    if(_x > param.maxLeft)
                        _x = param.maxLeft;
                    $(_this).css({'top':0,'left':_x+'px'});
                    $(_this).parent().siblings('.scroll-box').css('left','-'+_x*param.rate+'px');
                });
                $(document).mouseup(function(){
                    $('body').attr('unselectable','off').css({'-moz-user-select':'','-moz-user-select':'','-o-user-select':'','-khtml-user-select':'','-webkit-user-select':'','-ms-user-select':'','user-select':''}).unbind('selectstart');
                    $(this).unbind("mousemove");
                    $(_this).css('background','#D8D8D8');
                });
            });
            $(_this).parent().click(function(e){
                if($(e.target).closest('.scroll-x').length > 0)
                    return false;
                var offsetX = $(_this).offset();
                var offsetXBox = $(_this).parent().offset();
                var x = e.pageX;
                var _x;

                _x = x - offsetXBox.left - $(_this).width()/2;
                if(offsetXBox.left + $(_this).parent().width() < x + $(_this).width()/2)
                    _x = param.maxLeft;
                if(offsetXBox.left > x - $(_this).width()/2)
                    _x = 0;
                $(_this).animate({'left':_x+'px'},'fast');
                $(_this).parent().siblings('.scroll-box').animate({'left':'-'+_x*param.rate+'px'},'fast');
            });
        }
        // 文件树
        // param = {
        //         'defaultOpen' : 1 // 默认打开项
        //         'openTpye'    : 1 // 打开类型: 1 下滑 ……
        //         'openNum'     : 1 // 1 只打开一个，2 打开多个
        // }
        $.fn.treeView      = function(param){
            var _this    = this,
            defaultOpen  = param.defaultOpen||1,
            openTpye     = param.openTpye||1,
            openNum      = param.openNum||1,
            treeHeight   = 0,
            folderHeight = $(_this).find('.folders span').height(),
            initTree     = function(){
                $(_this).css({'position':'relative'});
                $(_this).find('.files div').addClass('file');
                $(_this).find('.file').each(function(){
                    $(this).find('span:last').css('background-image','url(./images/tree_linebottom.gif)');
                });
                $(_this).find('.file').hide();
                animateSettings(defaultOpen-1);
            },
            animateSettings = function(num){
                treeHeight = 0;
                $(_this).find('.folders span').each(function(i){
                    if(i == num){
                        $(this).animate({top:treeHeight+'px'},'200');
                        treeHeight += folderHeight;
                        //slideDown导致获取height为1
                        var tmp = $(_this).find('.file:eq('+num+')').height();
                        $(_this).find('.file:eq('+num+')').css('top',treeHeight).slideDown('200');
                        treeHeight += tmp;
                    }else{
                        $(this).animate({top:treeHeight+'px'},'200');
                        treeHeight += folderHeight;
                    }
                });
                $(_this).find('.folders span:eq('+num+')').css({'background-image':'url(./images/tree_folderopen.gif)','font-weight':'bold'}).siblings('span').css({'background-image':'url(./images/tree_folder.gif)','font-weight':'normal'});
                $(_this).animate({height:treeHeight+'px'},'200');
            };
            initTree();
            $(_this).find('.folders span').hover(function(){
                $(this).css('cursor','pointer');
            },function(){
                $(this).css('cursor','default');
            });
            $(_this).find('.folders span').unbind('click').bind('click',function(){
                var _folderIndex = $(this).index();
                if($('.folder-'+(_folderIndex+1)).css('display') == 'block')
                    return false;
                $(_this).find('.file:visible').slideUp('fast',animateSettings(_folderIndex));
            });
        }
		$.fn.treeViewModify = function(param) {
			var _this=this,
				_open = param.defaultOpen||0,
				//找到孩子, 并将其置于当前元素后
				openTree = function(obj, init) {
					var init = init||false;
					if(init){
						obj = obj.children('.tree-folders');
					}else{
						var _rootIndex = obj.prevAll('span:not(.no-child)').length;
						$('<div>').append(obj.parent().siblings('.tree-files:eq('+_rootIndex+')').clone()).children().show().insertAfter(obj);
						if(obj.parent().parent().hasClass('tree-files'))
							obj.parent().siblings('.tree-files:eq('+_rootIndex+')').html('');
						if(obj.nextAll().length > 1)
							obj.next().addClass('tree-icon tree-main-line');
						if(obj.next().children('.tree-folders').length > 0)
							obj = obj.next().children('.tree-folders');
						else
							obj = obj.next();
					}
					obj.children('span').each(function(i){
						var _treeTool = $('<div/>').addClass('tree-icon tree-button-bg');
						if(init && i==0)
							_treeTool.addClass('tree-start-line')
						else if($(this).next().length == 0)
							_treeTool.addClass('tree-end-line')
						else
							_treeTool.addClass('tree-middle-line')
						if(!$(this).hasClass('no-child'))
							$('<i/>').addClass('tree-icon tree-button tree-button-close').appendTo(_treeTool).bind('click',function(){
								$(this).parent().next().click();
								$(_this).children('.tree-item-bg').css({'top':$(this).parent().offset().top-$(_this).offset().top+$(_this).scrollTop()})
							});
						_treeTool.insertBefore($(this));
					});
					obj.children('span:not(.no-child)').unbind('click').bind('click', function(){
						if($(this).next().find('span').length > 0) {
							if($(this).next().css('display') == 'none'){
								$(this).next().show();
								$(this).prev().children('i').addClass('tree-button-open').removeClass('tree-button-close');
							}else{
								$(this).prev().children('i').addClass('tree-button-close').removeClass('tree-button-open');
								closeTree($(this).next());
							}
						}else{
							$(this).prev().children('i').addClass('tree-button-open').removeClass('tree-button-close');
							openTree($(this));
						}
					});
					if(init && _open){
						obj.children('span:not(.no-child)').click();
					}
				},
				//只是隐藏, 里边的状态不管
				closeTree = function(obj) {
					obj.hide()
				};
				openTree($(_this), true);	
		}
        // input美化
        // 
        // 获取自身
        // 原理：创建一个匿名Object，然后将自身加入其中，再取出匿名Object的html()便可得到自身HTML。
        // $("<p>").append($(selecter).clone()).html()
        $.fn.colorInput    = function(config){
            var status = '';
            if($(this).attr('readonly') || $(this).attr('disabled'))
                status = ' disabled';
            $(this).parent().append('<div class="color-input-text'+status+'" style="width:'+($(this).width()+22)+'px">'+$("<p>").append($(this).clone()).html()+'<div class="form-icon empty"></div></div>').end().remove();
            $('.color-input-text').hover(function(){
                if($(this).hasClass('disabled'))
                    return false;
                $(this).css('cursor','text');
            },function(){
                $(this).css('cursor','default');
            });
            $('.empty').hover(function(){
                $(this).css('cursor','pointer');
            },function(){
                $(this).css('cursor','default');
            });
            $('.color-input-text').click(function(){
                if($(this).hasClass('disabled'))
                    return false;
                $('.color-input-text').removeClass('focus');
                $('.color-input-text .empty').hide()
                $(this).addClass('focus');
                $(this).find('input').focus().end().find('.empty').show();
            });
            $('.empty').click(function(){
                $(this).siblings('input').val('');
            });
            $(document).click(function(e){
                if($(e.target).closest('.color-input-text').length == 0){
                    $('.color-input-text').removeClass('focus');
                    $('.empty').hide();
                }
            })
            
        }
        // selecte美化
        $.fn.colorSelect  = function(){
            var _this;
            var param;
            var option = '';
            var status = '';
            var rate = 1;
            var getRate = function(){
                var originalHeight = param.h;
                var scrollYHeight = param.y / originalHeight * param.y;
                rate = (param.y - scrollYHeight)/(originalHeight - param.y);
            }
            $(this).find('option').each(function(){
                option += '<span>'+$(this).text()+'</span>';
            });
            if($(this).attr('disabled'))
                status = ' disabled';
            $(this).parent().append('<div class="color-select'+status+'" style="width:'+$(this).width()+'px">'+$("<p>").append($(this).clone()).html()+'<div class="form-icon indicate"></div><h3>'+$(this).val()+'</h3><div class="color-option">'+option+'</div>').end().remove();
            $('.color-select select').attr('disabled',true);
            $('.color-select').hover(function(){
                $(this).find('select').css({'display':'none'});
            },function(){
                $(this).find('select').css({'display':''});
            });
            $('.color-select h3,.indicate').unbind('click').click(function(){
                _this = $(this).parent().get(0);
                if($(_this).hasClass('disabled'))
                    return false;
                if($(_this).find('.color-option:visible').length > 0)
                    return false;
                var itemSum = $(_this).find('.color-option span').length;
                var itemHeight = 25;
                param = {'type':1,'x':200,'y':200,'h':itemSum*itemHeight};
                if($(_this).find('.scroll-y-box').length == 0 && itemSum > 8)
                    $(_this).find('.color-option').easyScroll(param);
                if(itemSum <= 8){
                    $(_this).find('.color-option').css('top','35px');
                }else{
                    getRate();
                    $(_this).find('.scroll-box').css('top',0);
                    $(_this).find('.scroll-y').css('top',0);
                }
                if($(_this).find('.color-option span').hasClass('hover')){
                    var index = $(_this).find('.color-option span.hover').index();
                    if(index <= 3)
                        index = 0;
                    if(index > 3 && index <= itemSum - 5)
                        index = index-3;
                    if(index > itemSum - 5)
                        index = itemSum - 8;
                    $(_this).find('.scroll-box').css('top','-'+index*itemHeight+'px');
                    $(_this).find('.scroll-y').css('top',index*itemHeight*rate+'px');
                }else{
                    $(_this).find('h3').html($(_this).find('select').val());
                    $(_this).find('.color-option span:first').addClass('hover');
                }
                $('.color-select').css('border-left','5px solid #e9e7e3').find('.indicate').css('background-position','0 -205px').end().find('.color-option').slideUp('fast');
                $(this).parent().css('border-left','5px solid #2489c5').find('.indicate').css('background-position','-12px -205px').end().find('.color-option').slideDown('fast');
                $(_this).find('.color-option span').optionSettings({'sum':itemSum,'rate':rate,'height':itemHeight});
            })
            $(document).click(function(e){
                if($(e.target).closest('.color-select').length == 0){
                    $('.color-select .color-option').slideUp('fast',function(){
                        $(_this).css('border-left','5px solid #e9e7e3').find('.indicate').css('background-position','0 -205px').end().find('select').css({'display':''});
                    });
                    $(document).unbind('keydown');
                }
            })
        }
        // 重新加载option
        $.fn.reloadOptions = function(){
            var option = '';
            var sum = $(this).find('option').length;
            $(this).find('option').each(function(){
                option += '<span>'+$(this).html()+'</span>';
            });
            if(sum > 8)
                $(this).parent().find('.color-option').css({'width':$(this).parent().width()+'px','padding-right':0,'top':0})
            $(this).parent().find('.color-option').html(option);
            $(this).parent().find('.color-option span').optionSettings();
        }
        $.fn.optionSettings = function(param){
			param = param||{};
            var _this = this;
            var obj = $(this).closest('.color-select');
            var index = 0;
            var optionHeight = param.height||25;
            var offsetOption = obj.find('.color-option').offset();
            var optionSelect = function(key){
                switch(key){
                        case 40: //下键
                            index++;
                            if(index > param.sum-1) index = param.sum-1;
                            break;
                        case 38: //上键
                            index--;
                            if(index < 0) index = 0;
                            break;
                        case 13: //回车键
                            obj.find('.color-option span:eq('+index+')').click();
                            index = 0;
                            break;
                }
            }
            $(_this).unbind('mousemover').mouseover(function(){
                index = $(this).index();
                $(this).addClass('hover').siblings().removeAttr('class');
            });
            
            $(document).unbind('keydown').on('keydown',function(e){
                    $(_this).unbind('mouseover')
                    var _key=e.keyCode;
                    optionSelect(_key);
                    obj.find('.color-option span:eq('+index+')').addClass('hover').siblings().removeAttr('class');
                    var offsetSpanHover = obj.find('.color-option span:eq('+index+')').offset();
                    var relativeHeight  = offsetSpanHover.top - offsetOption.top;
                    if(relativeHeight > 7*optionHeight){
                        obj.find('.scroll-box').css('top','-'+(index-7)*optionHeight+'px');
                        obj.find('.scroll-y').css('top',(index-7)*optionHeight*param.rate+'px');
                    }
                    if(relativeHeight < 0){
                        obj.find('.scroll-box').css('top','-'+(index)*optionHeight+'px');
                        obj.find('.scroll-y').css('top',(index)*optionHeight*param.rate+'px');
                    }
                    $(this).unbind('mousemove').mousemove(function(){
                        $(_this).mouseover(function(){
                            index = $(this).index();
                            $(this).addClass('hover').siblings().removeAttr('class');
                        });
                        $(this).unbind('mousemove');
                    })
                    return false;
            });
            $(_this).click(function(){
                    $(this).closest('.color-select').find('h3').html($(this).html());
                    $(this).closest('.color-select').find('select option:eq('+$(this).index()+')').attr('selected','true').siblings('option').removeAttr('selected');
                    $(this).closest('.color-option').slideUp('fast',function(){
                        $(this).closest('.color-select').css('border-left','5px solid #e9e7e3').find('.indicate').css('background-position','0 -205px').end().find('select').css({'display':''});
                    });
            });
        }
        // checkbox美化
        $.fn.colorCheckbox = function(){
            var _this = this;
            $(_this).each(function(){
                var status = '';
                if($(this).attr('checked'))
                    status = ' checked';
                if($(this).attr('disabled'))
                    status = ' disabled';
                if($(this).attr('disabled') && $(this).attr('checked'))
                    status = ' checked-disabled';
                $(this).parent().append('<div class="color-checkbox'+status+'"><div class="form-icon checkbox-check'+status+'"></div>'+$("<p>").append($(this).clone()).html()+'<span>'+$(this).next('label').html()+'</span>');
                $(this).next('label').remove().end().remove();
            });
            $('.color-checkbox').hover(function(){
                if($(this).hasClass('disabled') || $(this).hasClass('.checked-disabled'))
                    return false;
                $(this).find('.checkbox-check').addClass('hover');
                $(this).css('cursor','pointer');
            },function(){
                $(this).find('.checkbox-check').removeClass('hover');
                $(this).css('cursor','default');
            });
            $('.color-checkbox').click(function(){
                if($(this).hasClass('disabled') || $(this).hasClass('checked-disabled'))
                    return false;
                if($(this).find('.checkbox-check').hasClass('checked'))
                    $(this).find('.checkbox-check').removeClass('checked').next('input').removeAttr('checked');
                else
                    $(this).find('.checkbox-check').addClass('checked').next('input').attr('checked',true);
            });
        }
        // radio美化
        $.fn.colorRadio    = function(){
            var _this = this;
            $(_this).each(function(){
                var status = '';
                if($(this).attr('checked'))
                    status = ' checked';
                if($(this).attr('disabled'))
                    status = ' disabled';
                if($(this).attr('disabled') && $(this).attr('checked'))
                    status = ' checked-disabled';
                $(this).parent().append('<div name="'+$(_this).attr('name')+'" class="color-radio'+status+'"><div class="form-icon radio-check'+status+'"></div>'+$("<p>").append($(this).clone()).html()+'<span>'+$(this).next('label').html()+'</span>');
                $(this).next('label').remove().end().remove();
            });
            $('.color-radio').hover(function(){
                if($(this).hasClass('disabled') || $(this).hasClass('checked-disabled'))
                    return false;
                $(this).find('.radio-check').addClass('hover');
                $(this).css('cursor','pointer');
            },function(){
                $(this).find('.radio-check').removeClass('hover');
                $(this).css('cursor','default');
            });
            $('.color-radio').click(function(){
                if($(this).hasClass('disabled') || $(this).hasClass('checked-disabled'))
                    return false;
                var name = $(this).attr('name');
                $('[name='+name+']:has(.checked-disabled)').removeClass('checked-disabled').addClass('disabled').find('.radio-check').removeClass('checked-disabled').addClass('disabled');
                $('[name='+name+']').find('.radio-check').removeClass('checked').next('input').removeAttr('checked');
                $(this).find('.radio-check').addClass('checked').next('input').attr('checked',true);
            });
        }
        // 按钮美化
        $.fn.colorButton   = function(){
            
        }
        // 右键
        $.fn.rightMouse    = function(param){
			var _this = this,
				_limit = null,
				_html = null,
				_split = null,
				num=0;
			$(_this).on('mousedown',param.elem,function(e){
				if(e.which != 3)
					return false;
            	$('.right-mouse').remove();
				$('.right-mouse-base').removeClass('right-mouse-base');
				$(this).addClass('right-mouse-base');
				_limit = param.limit[$(this).attr('type')];
				_html = $('<div/>');
				for(var x in _limit) {
					_split = param.data[_limit[x]].split(/eval##(.*?)\##/im);
					if(_split[1] != null)
					$('<a/>').attr('href',_split[0]+eval(_split[1])+_split[2]).html(_limit[x]).appendTo(_html);
					else
					$('<a/>').attr('href',_split[0]).html(_limit[x]).appendTo(_html);
					num++;
				}
				_html.addClass('right-mouse').css({'top':e.pageY+5,'left':e.pageX+5}).appendTo($('body'));
			});
            $(_this).bind("contextmenu",function(e){
                return false;
            });
			$('.right-mouse').click(function(event){
				event.stopPropagation();
			});
			$('.right-mouse a').click(function(){
				$('.right-mouse').remove();
			})
			$(document).click(function(){
				$('.right-mouse').remove();
			})
        }
		$.fn.moveTreeTop = function() {
			var _this = this,
			obj = $(_this).parent(),
			_prev_icon = 'tree-middle-line',
			_this_icon = 'tree-middle-line';
			if(obj.children('.tree-icon').length == 0) {
				$(_this).prependTo(obj);
				return false;
			}
			obj.children('.tree-icon:first').addClass('tree-middle-line');
			if($(_this).nextAll('span').length == 0) {
				_prev_icon = 'tree-end-line';
				_this_icon = 'tree-end-line';
				if($(_this).prevAll('span:first').next('.tree-files').length)
					$(_this).prevAll('span:first').next('.tree-files').removeClass('tree-icon tree-main-line');
			}
			$(_this).prevAll('span:first').prev().removeClass('tree-start-line').removeClass('tree-middle-line').addClass(_prev_icon);
			
			if($(_this).next('.tree-files').length)
				$(_this).next().addClass('tree-icon tree-main-line').prependTo(obj);
			else {
				obj.nextAll('.tree-files').eq($(_this).prevAll('span:not(.no-child)').length).insertAfter(obj);
			}
			$(_this).prev().addClass('tree-start-line').removeClass(_this_icon).prependTo(obj);
			$(_this).insertAfter(obj.children('.tree-icon:first'))
		}
		$.fn.moveTree = function(obj, pos, target) {
			var _this = this,
				_target = target||0,
				_prev_icon = 'tree-middle-line',
				_this_icon = 'tree-middle-line';
			//确认目标位以及当前位
			//当前位为首位则下一成首位，否则首位不动
			//当前位为目标位则不动
			//目标位为末位则当前位变末位，否则末位不动
			/*obj.children('.tree-icon:first').addClass('tree-middle-line');
			if($(_this).nextAll('span').length == 0) {
				_prev_icon = 'tree-end-line';
				_this_icon = 'tree-end-line';
				if($(_this).prevAll('span:first').next('.tree-files').length)
					$(_this).prevAll('span:first').next('.tree-files').removeClass('tree-icon tree-main-line');
			}
			$(_this).prevAll('span:first').prev().removeClass('tree-start-line').removeClass('tree-middle-line').addClass(_prev_icon);
			
			if($(_this).next('.tree-files').length)
				$(_this).next().addClass('tree-icon tree-main-line').prependTo(obj);
			$(_this).prev().addClass('tree-start-line').removeClass(_this_icon).prependTo(obj);
			$(_this).insertAfter(obj.children('.tree-icon:first'))*/
		}
		/**
		 * $('.recent').addTree({
		 * 		'title'  : '项目进程如何了',
		 * 		'member' : [{'username':'谢衍鑫','avatar':'default_34_34.jpg','attr':{'data-id':'xieyx','type':'member'}},{'username':'崔洪波','avatar':'default_34_34.jpg','attr':{'data-id':'xieyx','type':'member'}}],
		 * 		'attr':{'data-id':'xieyx1234234534534','type':'group'}
		 * });
		 *
		 * 
		 **/
		$.fn.addTree = function(data) {
			var _this = this,
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
			if(data.member.length >= 2) {
				var _avatar = $('<div/>');
				for(var i=0; i<data.member.length; i++) {
					member_attr = {};
					for(var x in data.member[i].attr) {
						member_attr[x] = data.member[i].attr[x];
					}
					if(i<4)
					$('<img/>').attr({'src':data.member[i].avatar,'width':'10px'}).addClass('avatar').appendTo(_avatar);
					$('<span/>').attr(member_attr).addClass('no-child').html(data.member[i].username).prepend($('<img/>').attr({'src':data.member[i].avatar,'width':'22px'}).addClass('avatar')).appendTo(_member);
				}
				_avatar.addClass('group-avatar').prependTo(_title.children('span'));
				_member.addClass('tree-files').appendTo(_html);
			}else{
				_title.children('span').addClass('no-child');
				$('<img/>').addClass('avatar').attr({'src':data.member[0].avatar,'width':'22px'}).prependTo(_title.children('span'));
			}
			_html.treeViewModify({});
			_title.children().appendTo($(_this));
			if($(_this).children('.tree-icon').length >= 2) {
				$(_this).children('.tree-icon:eq(-2)').addClass('tree-middle-line').removeClass('tree-end-line');
				$(_this).children('.tree-icon:eq(-1)').addClass('tree-end-line').removeClass('tree-start-line');
			}
			if(data.member.length >= 2)
				_member.appendTo($(_this).parent());
			return $(_this).children('span:last');
		}
		$.fn.removeTree = function() {
			var _this=this;
			$('.tree-item-bg').remove();
			$(_this).prev().remove();
			if(!$(_this).hasClass('no-child')) {
				$(_this).parent().siblings('.tree-files:eq('+$(_this).prevAll('span:not(.no-child)').length+')').remove();
				$(_this).next('.tree-files').remove();
			}
			$(_this).remove();
		}
		$.fn.tips = function(param) {
			var _this = this,
				_page = $('<div/>'),
				_content = $('<div/>'),
				_target = $('<div/>'),
				_position = param.target||['left',2],
				init = function() {
					createTarget();
					createContent();
					$(_this).html(_content).append(_target);
					if(param.pages) {
						createPages();
						$(_this).append(_page)
					}
					$(_this).hide();
				},
				createTarget = function() {
					_target.addClass('chat-icon tips-target').css(_position[0],_position[1]);
				},
				createContent = function() {
					_content.addClass('tips-box').html($(_this).clone().html());
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
						console.log((Math.ceil(current_height/content_height)*content_height-current_height)+','+content_height)
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
		$.fn.scrollToBottom = function(){
			var logObj = $(this)[0];
			setTimeout(function(){logObj.scrollTop = logObj.scrollHeight;}, 50);
			//$(this).animate({'scrollTop':$(this)[0].scrollHeight});
		}
		$.fn.previewImg = function(file,callback){
			var _this = this,
			    filters = {'jpeg':'/9j/4','gif':'R0lGOD','png':'iVBORw'},
			checkFileType = function(){
				if(window.FileReader) {
					for (var i=0, f; f = file.files[i]; i++) {
						var fr = new FileReader();
						fr.onload = function(e) {
							var src = e.target.result;
							if (validateImg(src)) {
								callback(src);
							}
						}
						fr.readAsDataURL(f);
					}
				} else { // 降级处理
					if (/\.jpg$|\.png$|\.gif$/i.test(file.value) ) {
						callback(file.value);
					}
				}
			},
			validateImg = function(data) {
				var pos = data.indexOf(",") + 1;
				for (var e in filters) {
					if (data.indexOf(filters[e]) === pos) {
						return e;
					}
				}
				return null;
			}/*,
			showPrvImg = function(src) {
				if($(_this).children().length >= 8) return false;
				var img = $('<img />');
				var box = $('<div />');
				var _t = new Image();
				img.attr('src',src);
				_t.src = src;
				if(_t.width > _t.height)
					img.attr('width','100%');
				else
					img.attr('height','100%');
				box.addClass('image-msg').append(img).appendTo($(_this));
			}*/;
			checkFileType();
		}
		$.fn.modal = function() {
			var _this = this,
			_title = $('<div/>'),
			_content = $('<div/>'),
			_modal = $('<div/>');
			_title.addClass('modal-title').html('<h1>'+$(_this).attr('modal-title')+'</h1><a href="javascript:;" class="modal-close button-close">&times;</a>').appendTo(_modal);
			_content.addClass('modal-content').load($(_this).attr('modal-data')).appendTo(_modal);
			_modal.addClass('modal').appendTo($('body'));
			$('<div/>').addClass('modal-border').appendTo($('body'));
			$('<div/>').addClass('modal-bg').appendTo($('body'));
			$('.modal-close').click(function(){
				$('.modal').remove();
				$('.modal-border').remove();
				$('.modal-bg').remove();
			})
		}
})(jQuery);
