/**
 * Skin Editor JS
 * 
 * Sean Healy
**/

var imagesPath = Site.staticFilesURL + "/profile/skin_edit/images/";

var oColorPicker = new YAHOO.widget.ColorPicker("yui-picker-panel", {
	showcontrols: false, animate: true,
	images: {
		PICKER_THUMB: imagesPath + "picker_thumb_invis.png",
		HUE_THUMB: imagesPath + "hue_thumb_invis.png"
	} });

var current = {};
//  	current.swatch_group;
//  	current.hex_box;
//  	current.swatch;
//  	current.selector;

function switchColorListener(new_swatch_group)
{
	var new_one = {};
	new_one.swatch_group = new_swatch_group;
	new_one.hex_box = YAHOO.util.Dom.getElementsByClassName("color_hex", "input", new_one.swatch_group)[0];
	new_one.swatch = YAHOO.util.Dom.getElementsByClassName("color_swatch", "div", new_one.swatch_group)[0];
	new_one.selector = YAHOO.profile.UserSkin.skin_selectors[new_one.swatch_group.id]; // A selector rule
	
	switchSwatchVisuals(current, new_one);
	
	current = new_one;
	
	oColorPicker.on("rgbChange", function (p_oEvent) {
		current.hex_box.value = "#" + this.get("hex");
		setColor(false);
	});
}

function showPreviewFor(selector)
{
	element = getElements(selector)[0];
	
	if(YAHOO.lang.isObject(element[0]))
		element = element[0];
	
	position = YAHOO.util.Dom.getXY(element);
	
	if(!YAHOO.lang.isArray(position) && position.length != 2)
		position = [0,0];
	
	position[0] = position[0] - 25;
	position[1] = position[1] - 25;
	
	scrollPreviewTo(position);
}

function jumpPreviewTo(position)
{
	YAHOO.util.Dom.get('preview').contentWindow.scrollTo(position[0], position[1]);
}
function scrollPreviewTo(position)
{
	var scrollThing = YAHOO.util.Dom.get('preview').contentWindow.document.body;
	var anim = new SmoothScroll(scrollThing, { scroll: { to: position } }, 0.25);
	anim.animate();
}
function previewScrollPosition()
{
	return [YAHOO.util.Dom.get('preview').contentWindow.YAHOO.util.Dom.getDocumentScrollLeft(),
					YAHOO.util.Dom.get('preview').contentWindow.YAHOO.util.Dom.getDocumentScrollTop()];
}

function parseColor(color)
{
	var rgb = /^rgb\((\d*)\D*(\d*)\D*(\d*)\D*$/;
	var hhh = /^#([a-fA-F0-9])([a-fA-F0-9])([a-fA-F0-9])$/;
	var hhhhhh = /^#([a-fA-F0-9]{2})([a-fA-F0-9]{2})([a-fA-F0-9]{2})$/;
	
	if(match = color.match(rgb))
	{
		match.shift();
		
		for(j = 0; j < match.length; j++)
			match[j] = parseInt(match[j]);
	}
	else
	{
		if(match = color.match(hhh))
		{
			match.shift();
			for(i = 0; i < match.length; i++)
				match[i] = match[i].concat(match[i]);
		}
		else if(match = color.match(hhhhhh))
			match.shift();
		
		for(i = 0; i < match.length; i++)
			match[i] = parseInt(match[i],16);
	}
	
	return match;
}

function switchSwatchVisuals(old_one, new_one)
{
	var new_color = YAHOO.util.Dom.getStyle(new_one.swatch, "background-color");
	oColorPicker.setValue(parseColor(new_color),true);
	
	YAHOO.util.Dom.setStyle(old_one.swatch, "border-color", "#bebebe");
	YAHOO.util.Dom.setStyle(old_one.hex_box, "border-color", "#dfdfdf");
	YAHOO.util.Dom.setStyle(old_one.hex_box, "background-color", "transparent");
	
	YAHOO.util.Dom.setStyle(new_one.swatch, "border-color", "#555");
	YAHOO.util.Dom.setStyle(new_one.hex_box, "border-color", "#929292");
	YAHOO.util.Dom.setStyle(new_one.hex_box, "background-color", "#fff");
}

function getElements(selector)
{
	var preview = selector.match(/^(([a-zA-Z0-9_-]*):([a-zA-Z0-9_-]*)\/)?(([a-zA-Z0-9_-]*):([a-zA-Z0-9_-]*),([a-zA-Z0-9_-]*))?$/);
	
	var parent = {};
	parent.type = preview[2];
	parent.name = preview[3];
	
	var element = {};
	element.type = preview[5];
	element.name = preview[6];
	element.property = preview[7];
	
	var result_parent = [];
	var result = [];
	
	if(parent.type && element.type)
	{
		if(parent.type == "id")
			result_parent[0] = YAHOO.util.Dom.get('preview').contentWindow.YAHOO.util.Dom.get(parent.name);
		else if(parent.type == "tag")
			result_parent = YAHOO.util.Dom.get('preview').contentWindow.document.getElementsByTagName(parent.name);
		else if(parent.type == "class")
			result_parent = YAHOO.util.Dom.get('preview').contentWindow.YAHOO.util.Dom.getElementsByClassName(parent.name);
		
		for(var i = 0; i < result_parent.lenght; i++)
		{
			if(element.type == "id")
				result.concat(YAHOO.util.Dom.get('preview').contentWindow.YAHOO.util.Dom.getElementsBy(function(el) {el.id == element.name}, null, result_parent[i]));
			else if(element.type == "tag")
				result.concat(YAHOO.util.Dom.get('preview').contentWindow.YAHOO.util.Dom.getElementsBy(function(el) {true}, element.name, result_parent[i]));
			else if(element.type == "class")
				result.concat(YAHOO.util.Dom.get('preview').contentWindow.YAHOO.util.Dom.getElementsByClassName(element.name, null, result.parent[i]));
		}
		
		alert(result);
		
		return [result, element.property];
	}
	else if(element.type)
	{
		if(element.type == "id")
			result = YAHOO.util.Dom.get('preview').contentWindow.YAHOO.util.Dom.get(element.name);
		else if(element.type == "tag")
			result = YAHOO.util.Dom.get('preview').contentWindow.document.getElementsByTagName(element.name);
		else if(element.type == "class")
			result = YAHOO.util.Dom.get('preview').contentWindow.YAHOO.util.Dom.getElementsByClassName(element.name);
		
		return [result, element.property];
	}
	else
	{
		if(parent.type == "id")
			result = YAHOO.util.Dom.get('preview').contentWindow.YAHOO.util.Dom.get(parent.name);
		else if(parent.type == "tag")
			result = YAHOO.util.Dom.get('preview').contentWindow.document.getElementsByTagName(parent.name);
		else if(parent.type == "class")
			result = YAHOO.util.Dom.get('preview').contentWindow.YAHOO.util.Dom.getElementsByClassName(parent.name);
		
		return [result, null];
	}
}

function setColor(updateColorPicker)
{
	// set default value
	if(updateColorPicker == null)
		updateColorPicker = true;
	
	YAHOO.util.Dom.setStyle(current.swatch, "background-color", current.hex_box.value);
	
	result = getElements(current.selector);
	if(result == null)
		return;
	property = result[1];
	result = result[0];
	if(YAHOO.lang.isArray(result))
		for(i=0; i < result.length; i++)
		{
			if(YAHOO.util.Dom.hasClass(result[i], "custom_color_icon") || YAHOO.util.Dom.hasClass(result[i], "custom_color_icon_alpha"))
				result[i].setColor(current.hex_box.value);
			
			YAHOO.util.Dom.setStyle(result[i], property, current.hex_box.value);
		}
	else
		YAHOO.util.Dom.get('preview').contentWindow.YAHOO.util.Dom.setStyle(result, property, current.hex_box.value);
	
	if(updateColorPicker)
	{
		var new_color = YAHOO.util.Dom.getStyle(current.swatch, "background-color");
		oColorPicker.setValue(parseColor(new_color),true);
	}
}

YAHOO.util.Event.on(window, 'load', function()
{
	var attributes = {
		points: { to: [YAHOO.util.Dom.getRegion("right").left + 6, YAHOO.util.Dom.getRegion("preview_wrapper").top] }
	};
	var anim = new YAHOO.util.Motion('preview_wrapper', attributes, 0);
	anim.animate();
	
	var skin_handles = YAHOO.util.Dom.getChildrenBy(YAHOO.util.Dom.get('skin_panes'), function(el){return YAHOO.util.Dom.hasClass(el, 'accordion_handle')});
	var skin_bodies = YAHOO.util.Dom.getChildrenBy(YAHOO.util.Dom.get('skin_panes'), function(el){return YAHOO.util.Dom.hasClass(el, 'accordion_body')});
	
	for(i = 0; i < skin_handles.length; i++)
	{
		// This is confusing but seems to be nessiary with YUI either that or I missed something
		YAHOO.util.Event.on(skin_handles[i], 'click', function(e, args)
		{
			args[0](args[1]);
		}, [showPreviewFor, YAHOO.profile.UserSkin.group_selectors[skin_handles[i].id]]); // A Selector rule
	}
	showPreviewFor(YAHOO.profile.UserSkin.group_selectors[skin_handles[0].id]); // A Selector rule
	
	for(i = 0; i < skin_bodies.length; i++)
	{
		var swatch_groups = YAHOO.util.Dom.getChildren(skin_bodies[i]);
		
		for(j = 0; j < swatch_groups.length; j++)
		{
			// This is confusing but seems to be nessiary with YUI either that or I missed something
			YAHOO.util.Event.on(swatch_groups[j], 'click', function(e, args)
			{
				args[0](args[1]);
			}, [switchColorListener, swatch_groups[j]]);
		}
	}
	
	var edit_headers = YAHOO.util.Dom.getElementsByClassName("input_title", null, null, function()
	{
		YAHOO.util.Event.on(this, 'focus', function(e, el)
		{
			YAHOO.util.Dom.addClass(el, "focus");
		}, this);
		
		YAHOO.util.Event.on(this, 'blur', function(e, el)
		{
			YAHOO.util.Dom.removeClass(el, "focus");
		}, this);
	});
});
