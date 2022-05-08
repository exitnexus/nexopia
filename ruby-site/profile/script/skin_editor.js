// require setup_skin_edit.js

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
//		current.property;

function switchColorListener(new_swatch_group)
{
	var skin_sel;
	
	if(current != null && current.revert_to != null)
	{
		var temp = {};
		temp.swatch_group = document.getElementById(current.revert_to);
		temp.hex_box = YAHOO.util.Dom.getElementsByClassName("color_hex", "input", temp.swatch_group)[0];
		temp.swatch = YAHOO.util.Dom.getElementsByClassName("color_swatch", "div", temp.swatch_group)[0];
		
		skin_sel = YAHOO.profile.UserSkin.skin_selectors[current.revert_to];
		
		temp.selector = skin_sel.selector;
		temp.property = skin_sel.property;
		temp.apply_on_focus = skin_sel.apply_on_focus;
		temp.revert_to = skin_sel.revert_to;
		temp.exclude_selectors = skin_sel.exclude_selectors;
		temp.conditional_selectors = skin_sel.conditional_selectors;
		
		current = temp;
		
		setColor(false);
	}
	
	var new_one = {};
	new_one.swatch_group = new_swatch_group;
	new_one.hex_box = YAHOO.util.Dom.getElementsByClassName("color_hex", "input", new_one.swatch_group)[0];
	new_one.swatch = YAHOO.util.Dom.getElementsByClassName("color_swatch", "div", new_one.swatch_group)[0];
	
	skin_sel = YAHOO.profile.UserSkin.skin_selectors[new_one.swatch_group.id];
	
	new_one.selector = skin_sel.selector;
	new_one.property = skin_sel.property;
	new_one.apply_on_focus = skin_sel.apply_on_focus;
	new_one.revert_to = skin_sel.revert_to;
	new_one.exclude_selectors = skin_sel.exclude_selectors;
	new_one.conditional_selectors = skin_sel.conditional_selectors;
	
	switchSwatchVisuals(current, new_one);
	
	current = new_one;
	
	if(current.apply_on_focus)
	{
		setColor();
	}
	
	oColorPicker.on("rgbChange", function (p_oEvent) {
		current.hex_box.value = "#" + this.get("hex");
		setColor(false);
		YAHOO.profile.UserSkin.validateColorField(current.hex_box);
	});
}

function showPreviewFor(selector)
{
	var temp_obj = {};
	temp_obj.selector = selector;
	element = getElements(temp_obj);
	
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
	
	hex_sel = document.getElementById("hex_selected");
	hex_unsel = document.getElementById("hex_unselected");
	
	YAHOO.util.Dom.setStyle(old_one.hex_box, "background-color", YAHOO.util.Dom.getStyle(hex_unsel, "background-color"));
	YAHOO.util.Dom.setStyle(old_one.hex_box, "color", YAHOO.util.Dom.getStyle(hex_unsel, "color"));
	
	YAHOO.util.Dom.setStyle(new_one.hex_box, "background-color", YAHOO.util.Dom.getStyle(hex_sel, "background-color"));
	YAHOO.util.Dom.setStyle(new_one.hex_box, "color", YAHOO.util.Dom.getStyle(hex_sel, "color"));
}

function getElements(selector_obj)
{
	var all_elements = [];
	
	// 
	if(selector_obj.conditional_selectors && selector_obj.conditional_selectors.length > 0)
	{
		var temp;
		var cond_result;
		var linked_elements;
		for(var k = 0; k < selector_obj.conditional_selectors.length; k++)
		{
			temp = selector_obj.conditional_selectors[k];
			cond_result = eval(temp[1])();
			if(cond_result)
			{
				linked_elements = getElements(YAHOO.profile.UserSkin.skin_selectors[temp[0]]);
				all_elements = all_elements.concat(linked_elements);
			}	
		}
	}
	
	var temp_list;
	for(var i = 0; i < selector_obj.selector.length; i++)
	{
		temp_list = YAHOO.util.Dom.get('preview').contentWindow.YAHOO.util.Selector.query(selector_obj.selector[i])
		all_elements = all_elements.concat(temp_list);
	}
	var excluded_list = [];
	var filtered_list;
	var temp_sel_obj;
	var i, j;
	
	if(selector_obj.exclude_selectors != null)
	{
		
		for(i = 0; i < selector_obj.exclude_selectors.length; i++)
		{
			temp_sel_obj = YAHOO.profile.UserSkin.skin_selectors[selector_obj.exclude_selectors[i]];
			var temp = getElements(temp_sel_obj);
			for(j = 0; j < temp.length; j++)
			{
				excluded_list.push(temp[j]);
			}
		}
		
		filtered_list = [];
		var include_element = true;
		for(i = 0; i < all_elements.length; i++)
		{
			include_element = true;
			for(j = 0; j < excluded_list.length; j++)
			{
				if(all_elements[i] == excluded_list[j])
				{
					include_element = false;
					break;
				}
			}
			if(include_element)
			{
				filtered_list.push(all_elements[i]);
			}
		}
	}
	else
	{
		filtered_list = all_elements;
	}
	
	return filtered_list;
}

function setColor(updateColorPicker)
{
	// set default value
	if(updateColorPicker == null)
		updateColorPicker = true;
	
	YAHOO.util.Dom.setStyle(current.swatch, "background-color", current.hex_box.value);
	
	result = getElements(current);
	if(result == null)
		return;
	
	if(YAHOO.lang.isArray(result))
		for(i=0; i < result.length; i++)
		{
			if(YAHOO.util.Dom.hasClass(result[i], "custom_color_icon") || YAHOO.util.Dom.hasClass(result[i], "custom_color_icon_alpha"))
				result[i].setColor(current.hex_box.value);
			
			YAHOO.util.Dom.setStyle(result[i], current.property, current.hex_box.value);
		}
	else
		YAHOO.util.Dom.get('preview').contentWindow.YAHOO.util.Dom.setStyle(result, current.property, current.hex_box.value);
	
	if(updateColorPicker)
	{
		var new_color = YAHOO.util.Dom.getStyle(current.swatch, "background-color");
		oColorPicker.setValue(parseColor(new_color),true);
	}
}

section_gutter_enabled = function()
{
	gutter_checkbox_list = YAHOO.util.Dom.getElementsByClassName("skin_edit_checkbox", "input", "section_gutter_color", null);
	if(gutter_checkbox_list.length == 0)
	{
		return false;
	}
	
	gutter_checkbox = gutter_checkbox_list[0];
	if(gutter_checkbox.checked)
	{
		return true;
	}
	return false;
}

section_gutter_disabled = function()
{
	return !this.section_gutter_enabled();
}

is_checkbox = function(el)
{
	if(el.tagName.downcase() == "input" && el.attributes["type"].downcase() == "checkbox")
	{
		return true;
	}
	return false;
}

YAHOO.profile.UserSkin.init_skin_editor = function()
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
		// This is confusing but seems to be necessary with YUI either that or I missed something
		YAHOO.util.Event.on(skin_handles[i], 'click', function(e, args)
		{
			args[0](args[1]);
		}, [showPreviewFor, YAHOO.profile.UserSkin.group_selectors[skin_handles[i].id]]); // A Selector rule
	}
	showPreviewFor(YAHOO.profile.UserSkin.group_selectors[skin_handles[0].id]); // A Selector rule
	
	for(i = 0; i < skin_bodies.length; i++)
	{
		var swatch_groups = YAHOO.util.Dom.getChildren(skin_bodies[i]);
		var skin_sel_obj;
		for(j = 0; j < swatch_groups.length; j++)
		{
			skin_sel_obj = YAHOO.profile.UserSkin.skin_selectors[swatch_groups[j].id];
			if(skin_sel_obj.type && skin_sel_obj.type == "checkbox")
			{
				checkbox_list = YAHOO.util.Dom.getElementsByClassName("skin_edit_checkbox", "input", swatch_groups[j].id, null);
				if(checkbox_list.length == 0)
				{
					continue;
				}

				checkbox = checkbox_list[0];
				
				YAHOO.util.Event.on(checkbox, 'click', function(e)
				{
					YAHOO.profile.UserSkin.handle_checkbox_click(e);
				})
			}
			else
			{
				// This is confusing but seems to be necessary with YUI either that or I missed something
				YAHOO.util.Event.on(swatch_groups[j], 'click', function(e, args)
				{
					args[0](args[1]);
				}, [switchColorListener, swatch_groups[j]]);
			}
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
}

YAHOO.profile.UserSkin.handle_checkbox_click = function(e)
{
	var target = YAHOO.util.Event.getTarget(e);
	
	var skin_sel_obj = YAHOO.profile.UserSkin.skin_selectors[target.name];
	var target_swatch, target_skin_sel, target_color;
	var results;
	
	if(!target.checked)
	{
		target_skin_sel = skin_sel_obj.onchange.disabled;
	}
	else
	{
		target_skin_sel = skin_sel_obj.onchange.enabled;
	}
	
	target_swatch = YAHOO.util.Dom.getElementsByClassName("color_hex", "input", target_skin_sel, null);
	if(target_swatch.length < 1)
	{
		return;
	}

	target_color = target_swatch[0].value;
	results = getElements(skin_sel_obj);
	
	for(var i=0; i< results.length; i++)
	{
		YAHOO.util.Dom.setStyle(results[i], skin_sel_obj.property, target_color)
	}
	
};
