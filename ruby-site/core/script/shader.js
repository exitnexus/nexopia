//require script_manager.js
//custom_color_icon.js

Overlord.assign({
	minion: "shade",
	load: function(element)
	{
		initialize_auto_shading(element);
	}
});

function initialize_auto_shading(elements)
{
	if(!elements)
		var elements = YAHOO.util.Dom.getElementsByClassName('shade_auto');
	else if(!YAHOO.lang.isArray(elements))
	{
		elements = [elements];
	}
	
	for(var i = 0; i < elements.length; i++)
	{
		element = elements[i];
		
		var backgroundColorEl = YAHOO.util.Dom.getAncestorBy(element, function(el) {
			var background = YAHOO.util.Dom.getStyle(el, 'background-color');
			return (background != "transparent" && background != 'rgba(0, 0, 0, 0)');
		});
		
		var shade_color = getRGB(YAHOO.util.Dom.getStyle(backgroundColorEl, "background-color"));
		
		if((shade_color[0] + shade_color[1] + shade_color[2])/3 >= 128)
		{
			darken(element, shade_color);
		}
		else
		{
			lighten(element, shade_color);
		}
	}
}

function lighten(element, shade_color)
{
	if(!YAHOO.util.Dom.hasClass(element, "no_shading"));
	{
		if(!YAHOO.util.Dom.hasClass(element, "no_shading_color"))
		{
			do_lighten(element, "color", shade_color);
		}
		if(!YAHOO.util.Dom.hasClass(element, "no_shading_background"))
		{
			do_lighten(element, "background-color", shade_color);
		}
		if(!YAHOO.util.Dom.hasClass(element, "no_shading_border"))
		{
			do_lighten(element, "border-top-color", shade_color);
			do_lighten(element, "border-bottom-color", shade_color);
			do_lighten(element, "border-left-color", shade_color);
			do_lighten(element, "border-right-color", shade_color);
		}
	}
	
	var children = YAHOO.util.Dom.getChildren(element);
	for(var i = 0; i < children.length; i++)
	{
		lighten(children[i], shade_color);
	}
}

function darken(element, shade_color)
{
	if(!YAHOO.util.Dom.hasClass(element, "no_shading"));
	{
		if(!YAHOO.util.Dom.hasClass(element, "no_shading_color"))
		{
			do_darken(element, "color", shade_color);
		}
		if(!YAHOO.util.Dom.hasClass(element, "no_shading_background"))
		{
			do_darken(element, "background-color", shade_color);
		}
		if(!YAHOO.util.Dom.hasClass(element, "no_shading_border"))
		{
			do_darken(element, "border-top-color", shade_color);
			do_darken(element, "border-bottom-color", shade_color);
			do_darken(element, "border-left-color", shade_color);
			do_darken(element, "border-right-color", shade_color);
		}
	}
	
	var children = YAHOO.util.Dom.getChildren(element);
	for(var i = 0; i < children.length; i++)
	{
		darken(children[i], shade_color);
	}
}

function do_lighten(element, property, shade_color)
{
	if(getRGB(YAHOO.util.Dom.getStyle(element, property)) != false)
	{
		var shade_value = 1 - getRGB(YAHOO.util.Dom.getStyle(element, property))[0]/255;
	
		var new_color = [];
		new_color[0] = Math.round(shade_color[0] + (255 - shade_color[0]) * shade_value);
		new_color[1] = Math.round(shade_color[1] + (255 - shade_color[1]) * shade_value);
		new_color[2] = Math.round(shade_color[2] + (255 - shade_color[2]) * shade_value);
		
		colorize(element, property, new_color);
	}
}

function do_darken(element, property, shade_color)
{
	if(getRGB(YAHOO.util.Dom.getStyle(element, property)) != false)
	{
		var shade_value = getRGB(YAHOO.util.Dom.getStyle(element, property))[0]/255;
		
		var new_color = [];
		new_color[0] = Math.round(shade_color[0] * shade_value);
		new_color[1] = Math.round(shade_color[1] * shade_value);
		new_color[2] = Math.round(shade_color[2] * shade_value);
		
		colorize(element, property, new_color);
	}
}

function colorize(element, property, color)
{
	if(YAHOO.util.Dom.hasClass(element, "custom_color_icon") || YAHOO.util.Dom.hasClass(element, "custom_color_icon_alpha"))
		element.setColor('rgb('+color.join(",")+')');
	
	if( !( element.tagName == "IMG" || element.tagName == "CANVAS" ) )
		YAHOO.util.Dom.setStyle(element, property, 'rgb('+color.join(",")+')');
};
