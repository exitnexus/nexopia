/****** Keep things sane ******/

function $(el)
{
	return YAHOO.util.Dom.get(el);
}

function $$(class_name)
{
	return YAHOO.util.Dom.getElementsByClassName(class_name);
}

/****** Selection and Caret Position Finding ******/

function getSelectionRange(control)
{
	var CaretPos = [0,0];
	
	// IE Support
	if (document.selection) 
	{
		control.focus();
		var range = document.selection.createRange();
		var stored_range = range.duplicate();
		stored_range.moveToElementText(control);
		stored_range.setEndPoint( 'EndToEnd', range );
		
		CaretPos[0] = stored_range.text.length - range.text.length;
		CaretPos[1] = CaretPos[0] + range.text.length;
		
		control.focus();
	}
	
	// Other support
	else if (control.selectionStart || control.selectionStart == '0')
	{
		CaretPos[0] = control.selectionStart;
		CaretPos[1] = control.selectionEnd;
	}
	
	return (CaretPos);
}

function setCaretPosition(ctrl, pos)
{
	if(ctrl.setSelectionRange)
	{
		ctrl.focus();
		ctrl.setSelectionRange(pos,pos);
	}
	else if (ctrl.createTextRange) {
		var range = ctrl.createTextRange();
		range.collapse(true);
		range.moveEnd('character', pos);
		range.moveStart('character', pos);
		range.select();
	}
}

/****** insert markup ******/

function insert(control, open, close)
{
	var current = control.value;
	var sel = getSelectionRange(control);
	
	var beginning = current.slice(0, sel[0]);
	var middle = current.slice(sel[0], sel[1]);
	var end = current.slice(sel[1]);
	
	control.value = beginning + open + middle + close + end;
	
	if(middle.length > 0)
		setCaretPosition(control, (beginning + open + middle + close).length);
	else
		setCaretPosition(control, (beginning + open).length);
}

/****** build  ******/

var preload_images = [];
preload_images[0] = new Image();
preload_images[0].src = "images/icon_left_arrow.gif";
preload_images[1] = new Image();
preload_images[1].src = "images/icon_right_arrow.gif";
preload_images[2] = new Image();
preload_images[2].src = "images/left_arrow_back.png";
preload_images[3] = new Image();
preload_images[3].src = "images/right_arrow_back.png";

function build_insert_button(dest, open, close, caption)
{
	var tmp_button = document.createElement('input');
	tmp_button.type = "button";
	tmp_button.value = caption;
	YAHOO.util.Event.on(tmp_button, 'click', function(e, args)
	{
		insert(args[0], args[1], args[2]);
	}, [dest, open, close]);
	
	YAHOO.util.Dom.addClass(tmp_button, "input_button");
	
	return tmp_button;
}

YAHOO.util.Event.on(window, 'load', function(e)
{
	YAHOO.util.Dom.getElementsByClassName('enhanced_text_input', 'textarea', null, function()
	{
		var text_box = document.createElement('div');
		/* YAHOO.util.Dom.setStyle(text_box, 'display', 'inline'); */
		YAHOO.util.Dom.setStyle(text_box, 'position', 'relative');
		YAHOO.util.Dom.setStyle(text_box, 'width', YAHOO.util.Dom.getRegion(this).right - YAHOO.util.Dom.getRegion(this).left + 'px');
		YAHOO.util.Dom.addClass(text_box, "enhanced_text_input_wrapper");
		
		var tab_view = document.createElement('div');
		YAHOO.util.Dom.addClass(tab_view, "tab_view");
		
		var format_tab = document.createElement('div');
		YAHOO.util.Dom.addClass(format_tab, "tab_handle");
		YAHOO.util.Dom.setStyle(format_tab, 'margin-left', '0px');
		YAHOO.util.Dom.setStyle(format_tab, 'border-left-width', '1px');
		format_tab.innerHTML = "Format";
		
		var align_tab = document.createElement('div');
		YAHOO.util.Dom.addClass(align_tab, "tab_handle");
		YAHOO.util.Dom.setStyle(align_tab, 'left', '60px');
		align_tab.innerHTML = "Align";
		
		var insert_tab = document.createElement('div');
		YAHOO.util.Dom.addClass(insert_tab, "tab_handle");
		YAHOO.util.Dom.setStyle(insert_tab, 'left', '119px');
		insert_tab.innerHTML = "Insert";
		
		var emoticon_tab = document.createElement('div');
		YAHOO.util.Dom.addClass(emoticon_tab, "tab_handle");
		YAHOO.util.Dom.setStyle(emoticon_tab, 'left', '178px');
		emoticon_tab.innerHTML = "Smilies";
		
		var format_body = document.createElement('div');
		YAHOO.util.Dom.addClass(format_body, "tab_body");
		format_body.appendChild(build_insert_button(this, "[b]", "[/b]", " B "));
		format_body.appendChild(build_insert_button(this, "[i]", "[/i]", " I "));
		format_body.appendChild(build_insert_button(this, "[u]", "[/u]", " U "));
		format_body.appendChild(build_insert_button(this, "[strike]", "[/strike]", "Strike"));
		
		var align_body = document.createElement('div');
		YAHOO.util.Dom.addClass(align_body, "tab_body");
		align_body.appendChild(build_insert_button(this, "[left]", "[/left]", "Left"));
		align_body.appendChild(build_insert_button(this, "[center]", "[/center]", "Center"));
		align_body.appendChild(build_insert_button(this, "[right]", "[/right]", "Right"));
		align_body.appendChild(build_insert_button(this, "[justify]", "[/justify]", "Justify"));
		
		var insert_body = document.createElement('div');
		YAHOO.util.Dom.addClass(insert_body, "tab_body");
		insert_body.appendChild(build_insert_button(this, "[quote]", "[/quote]", "Quote"));
		insert_body.appendChild(build_insert_button(this, "[img]", "[/img]", "Image"));
		insert_body.appendChild(build_insert_button(this, "[url]", "[/url]", "Link"));
		insert_body.appendChild(build_insert_button(this, "[user]", "[/user]", "User"));
		insert_body.appendChild(build_insert_button(this, "\n[hr]\n", "", "Line"));
		insert_body.appendChild(build_insert_button(this, "[list]\n[*]", "\n[/list]", "List"));
		
		var emoticon_body = document.createElement('div');
		YAHOO.util.Dom.addClass(emoticon_body, "tab_body");
		YAHOO.util.Dom.setStyle(emoticon_body, 'height', '30px');
		var emoticon_list = document.createElement('div');
		YAHOO.util.Dom.addClass(emoticon_list, "emoticon_list");
		emoticon_body.appendChild(emoticon_list);
		emoticon_table = $('emoticons_list').cloneNode(true);
		emoticon_list.appendChild(emoticon_table);
		emoticon_list_imgs = YAHOO.util.Dom.getChildren(YAHOO.util.Dom.getChildren((YAHOO.util.Dom.getChildren(emoticon_table)[0]))[0]);
		
		for(var i = 0; i < emoticon_list_imgs.length; i++)
		{
			var img = YAHOO.util.Dom.getChildren(emoticon_list_imgs[i])[0];
			
			YAHOO.util.Dom.setStyle(img, 'cursor', 'pointer');
			
			YAHOO.util.Event.on(img, 'click', function(e, args)
			{
				insert(args[0], args[1], args[2]);
			}, [this, img.alt, ""]);
		}
		
		var emoticon_left_back = document.createElement('div');
		YAHOO.util.Dom.addClass(emoticon_left_back, "emoticon_left_back");
		var emoticon_left_back_img = $('emoticon_left_back_img').cloneNode(true);
		emoticon_left_back_img.width = "22";
		emoticon_left_back_img.height = "33";
		YAHOO.util.Dom.addClass(emoticon_left_back_img, "foreground_as_background");
		emoticon_left_back.appendChild(emoticon_left_back_img);
		emoticon_body.appendChild(emoticon_left_back);
		
		var emoticon_right_back = document.createElement('div');
		YAHOO.util.Dom.addClass(emoticon_right_back, "emoticon_right_back");
		var emoticon_right_back_img = $('emoticon_right_back_img').cloneNode(true);
		emoticon_right_back_img.width = "22";
		emoticon_right_back_img.height = "33";
		YAHOO.util.Dom.addClass(emoticon_right_back_img, "foreground_as_background");
		emoticon_right_back.appendChild(emoticon_right_back_img);
		emoticon_body.appendChild(emoticon_right_back);
		
		var emoticon_left_arrow = document.createElement('div');
		var emoticon_left_arrow_img = $('emoticon_left_arrow_img').cloneNode(true);
		emoticon_left_arrow.appendChild(emoticon_left_arrow_img);
		emoticon_body.appendChild(emoticon_left_arrow);
		YAHOO.util.Dom.addClass(emoticon_left_arrow, "emoticon_left_arrow");
		
		var emoticon_right_arrow = document.createElement('div');
		YAHOO.util.Dom.addClass(emoticon_right_arrow, "emoticon_right_arrow");
		var emoticon_right_arrow_img = $('emoticon_right_arrow_img').cloneNode(true);
		emoticon_right_arrow.appendChild(emoticon_right_arrow_img);
		emoticon_body.appendChild(emoticon_right_arrow);
		
		tab_view.appendChild(format_tab);
		tab_view.appendChild(align_tab);
		tab_view.appendChild(insert_tab);
		tab_view.appendChild(emoticon_tab);
		tab_view.appendChild(format_body);
		tab_view.appendChild(align_body);
		tab_view.appendChild(insert_body);
		tab_view.appendChild(emoticon_body);
		text_box.appendChild(tab_view);
		
		var text_box_wrapper = document.createElement('div');
		YAHOO.util.Dom.addClass(text_box_wrapper, "text_box_wrapper");
		YAHOO.util.Dom.addClass(text_box_wrapper, "standard_colors");
		
		this.parentNode.replaceChild(text_box, this);
		text_box.appendChild(text_box_wrapper);
		text_box_wrapper.appendChild(this);
		
		YAHOO.util.Dom.setStyle(this, 'position', 'relative');
		YAHOO.util.Dom.setStyle(this, 'margin', '0px');
		YAHOO.util.Dom.setStyle(this, 'border-width', '0px');
		YAHOO.util.Dom.addClass(this, "standard_colors");
		YAHOO.util.Dom.setStyle(this, 'outline', 'none');
		
		init_custom_color_icons([emoticon_left_arrow_img]);
		init_custom_color_icons([emoticon_right_arrow_img]);
		init_custom_color_icons_alpha([emoticon_left_back_img]);
		init_custom_color_icons_alpha([emoticon_right_back_img]);
	});
	
	init_tabs();
});

/****** emoticon scrolling ******/

var scroller = {};

YAHOO.util.Event.on(window, 'load', function(e)
{
	var leftArrows = $$("emoticon_left_arrow");
	var rightArrows = $$("emoticon_right_arrow");
	
	scroller.emoticonListScrolling = false;
	scroller.emoticonListScrollCycles = 0;
	
	scroller.scrollEmoticonList = function(distance)
	{
		new YAHOO.util.Scroll(this.currentEmoticonList, { scroll: { by: [(distance + this.emoticonListScrollCycles), 0] } }, 0.1).animate();
		
		// adjust this to deal with scroll acceleration
		if(distance > 0)
			this.emoticonListScrollCycles++;
		else
			this.emoticonListScrollCycles--;
		
		if(this.emoticonListScrolling)
			setTimeout("scroller.scrollEmoticonList("+distance+")", 100);
	};
	
	scroller.start_scrollEmoticonList = function(which, distance)
	{
		YAHOO.util.Dom.setStyle(which, "width", YAHOO.util.Dom.getRegion(which.parentNode).right - YAHOO.util.Dom.getRegion(which.parentNode).left - 4);
				
		this.currentEmoticonList = which;
		this.emoticonListScrolling = true;
		this.scrollEmoticonList(distance)
	};
	
	scroller.stop_scrollEmoticonList = function()
	{
		this.emoticonListScrolling = false;
		this.emoticonListScrollCycles = 0;
	};
	
	for(i = 0; i < leftArrows.length; i++)
	{
		var emoticonList = YAHOO.util.Dom.getChildrenBy(leftArrows[i].parentNode, function(el){return YAHOO.util.Dom.hasClass(el, 'emoticon_list')})[0];
		
		var distance = YAHOO.util.Dom.getRegion(emoticonList).right - YAHOO.util.Dom.getRegion(emoticonList).left;
		distance = 20;
		
		YAHOO.util.Event.on(leftArrows[i], 'mousedown', function(e, args)
		{
			args[2].start_scrollEmoticonList(args[0], -args[1]);
		}, [emoticonList, distance, scroller]);
		YAHOO.util.Event.on(leftArrows[i], 'mouseup', function(e, args) { args[2].stop_scrollEmoticonList(); }, [emoticonList, distance, scroller]);
		YAHOO.util.Event.on(leftArrows[i], 'mouseout', function(e, args) { args[2].stop_scrollEmoticonList(); }, [emoticonList, distance, scroller]);
		
		YAHOO.util.Event.on(rightArrows[i], 'mousedown', function(e, args)
		{
			args[2].start_scrollEmoticonList(args[0], args[1]);
		}, [emoticonList, distance, scroller]);
		YAHOO.util.Event.on(rightArrows[i], 'mouseup', function(e, args) { args[2].stop_scrollEmoticonList(); }, [emoticonList, distance, scroller]);
		YAHOO.util.Event.on(rightArrows[i], 'mouseout', function(e, args) { args[2].stop_scrollEmoticonList(); }, [emoticonList, distance, scroller]);
	}
});
