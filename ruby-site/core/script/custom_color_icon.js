//require script_manager.js

// icon init
Overlord.assign(
{
	minion: "skin_editor_icon",
	load: function(element)
	{
		init_custom_color_icons([element]);
	}
});

// action here
function init_custom_color_icons(icons)
{
	for(var i = 0; i < icons.length; i++)
	{
		if(!YAHOO.env.ua.ie)
		{
			//if the icon isn't loaded yet come back later and redo this function
			if (icons[i].width == 0)
			{
				YAHOO.util.Event.on(icons[i], 'load', function() {
					init_custom_color_icons([this]);
					this.initialized = true;
				});
				setTimeout(function() {
				 	if (!icons[i].initialized)
				 	{
				 		init_custom_color_icons([icons[i]]);
				 		icons[i].initialized = true;
					}
				}, 2000);
				return;
			}
			icons[i].initialized = true;
			
			if (icons[i].width == 0)
			{
				throw new Exception("Uninitialized image.");
			}
			
			var canvas = document.createElement('canvas');
			
			canvas.width = icons[i].width;
			canvas.height = icons[i].height;
			
			var j;
			icon_class_list = icons[i].className.split(" ");
			for(var j = 0; j < icon_class_list.length; j++)
			{
				YAHOO.util.Dom.addClass(canvas, icon_class_list[j]);
			}
			
			YAHOO.util.Dom.setStyle(canvas, "color", YAHOO.util.Dom.getStyle(icons[i], "color"));
			YAHOO.util.Dom.setStyle(canvas, "margin", YAHOO.util.Dom.getStyle(icons[i], "margin"));
			YAHOO.util.Dom.setStyle(canvas, "padding", YAHOO.util.Dom.getStyle(icons[i], "padding"));
			YAHOO.util.Dom.setStyle(canvas, "border", YAHOO.util.Dom.getStyle(icons[i], "border"));
			
			if(icons[i].parentNode)
			{
				var attached_events = YAHOO.util.Event.getListeners(icons[i]);
				
				if(attached_events)
				{
					for(var j = 0; j < attached_events.length; j++)
					{
						if(attached_events[j])
							YAHOO.util.Event.addListener(canvas, attached_events[j].type, attached_events[j].fn, attached_events[j].obj);
					}
				}
				
				icons[i].parentNode.replaceChild(canvas, icons[i]);
				canvas.maskImage = icons[i];
				canvas.src = canvas.maskImage.src;
				canvas.id = canvas.maskImage.id;
				icons[i] = canvas;
			}
			else
			{
				return;
			}
		}
		
		icons[i].setColor = function(color)
		{
			if(!YAHOO.env.ua.ie)
			{
				if (!this.getContext) return;
				var ctx = this.getContext('2d');
				
				ctx.globalCompositeOperation = "source-over";
				ctx.clearRect(0,0,this.width,this.height);
			
				try
				{	
					ctx.drawImage(this.maskImage, 0, 0, this.width, this.height);
				}
				catch(e){}
				
				ctx.globalCompositeOperation = "source-in";
				
				// For some reason, color can be false here, so we have to check for that before trying
				// to set it.
				if (color)
				{
					ctx.fillStyle = color;
					ctx.fillRect(0,0,this.width,this.height);
				}
			}
			else
			{
				var match = getRGB(color);
				
				if(match)
				{
					match[0] = match[0].toString(16);
					match[1] = match[1].toString(16);
					match[2] = match[2].toString(16);
					
					if(match[0].length == 1)
						match[0] = "0"+match[0];
					if(match[1].length == 1)
						match[1] = "0"+match[1];
					if(match[2].length == 1)
						match[2] = "0"+match[2];
					
					// Use this new color value in the IE filter
					this.style.filter ="filter: progid:DXImageTransform.Microsoft.MaskFilter(color=#000000) progid:DXImageTransform.Microsoft.MaskFilter(color=#"+match.join("")+");";
				}
			}
		}
		
		icons[i].resetColor = function()
		{
			revertColor(icons[i]);
			this.setColor(YAHOO.util.Dom.getStyle(this, "color"));
		}

		icons[i].resetColor();
		YAHOO.util.Dom.addClass(icons[i], 'custom_color_icon');
	}
}

/********** Custom Icons with Alpha in IE **********/

/*
	NOTE:
		Due to a DOCTYPE problem in IE custom icons in IE will be rendered to display as a block element.
		For consistancy I have forced other browsers to do the same.
*/

function init_custom_color_icons_alpha(icons)
{
	for(i = 0; i < icons.length; i++)
	{
		if(!YAHOO.env.ua.ie)
		{
			var canvas = document.createElement('canvas');
			
			canvas.width = icons[i].width;
			canvas.height = icons[i].height;
			
			YAHOO.util.Dom.addClass(canvas, "custom_color_icon_alpha");
			YAHOO.util.Dom.setStyle(canvas, "display", "block");
			
			YAHOO.util.Dom.setStyle(canvas, "color", YAHOO.util.Dom.getStyle(icons[i], "color"));
			YAHOO.util.Dom.setStyle(canvas, "margin", YAHOO.util.Dom.getStyle(icons[i], "margin"));
			YAHOO.util.Dom.setStyle(canvas, "padding", YAHOO.util.Dom.getStyle(icons[i], "padding"));
			YAHOO.util.Dom.setStyle(canvas, "border", YAHOO.util.Dom.getStyle(icons[i], "border"));
			
			icons[i].parentNode.replaceChild(canvas, icons[i]);
			canvas.maskImage = icons[i];
			icons[i] = canvas;
		}
		else
		{
			var wrapper = document.createElement('div');
		
			icons[i].maskImageSrc = icons[i].src;
			icons[i].width = YAHOO.util.Dom.getRegion(icons[i]).right - YAHOO.util.Dom.getRegion(icons[i]).left;
			icons[i].height = YAHOO.util.Dom.getRegion(icons[i]).bottom - YAHOO.util.Dom.getRegion(icons[i]).top;
			
			icons[i].src = Site.staticFilesURL + "/core/custom_color_icon/transparent.gif";
			icons[i].style.filter ="filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" + icons[i].maskImageSrc + "', sizingMethod='scale');";
			
			
			YAHOO.util.Dom.setStyle(wrapper, "width", YAHOO.util.Dom.getStyle(icons[i], "width"));
			YAHOO.util.Dom.setStyle(wrapper, "height", YAHOO.util.Dom.getStyle(icons[i], "height"));
			
			YAHOO.util.Dom.addClass(wrapper, "custom_color_icon_alpha");
			YAHOO.util.Dom.setStyle(wrapper, "color", YAHOO.util.Dom.getStyle(icons[i], "color"));
			
			icons[i].parentNode.replaceChild(wrapper, icons[i]);
			wrapper.appendChild(icons[i]);
			
			icons[i] = wrapper;
		}
		
		icons[i].setColor = function(color)
		{
			if(!YAHOO.env.ua.ie)
			{
				if (!this.getContext) return;
				var ctx = this.getContext('2d');
				
				ctx.globalCompositeOperation = "source-over";
				ctx.clearRect(0,0,this.width,this.height);
				
				ctx.drawImage(this.maskImage, 0, 0, this.width, this.height);
				
				ctx.globalCompositeOperation = "source-in";
				
				ctx.fillStyle = color;
				ctx.fillRect(0,0,this.width,this.height);
			}
			else
			{
				var match = getRGB(color);
				
				match[0] = match[0].toString(16);
				match[1] = match[1].toString(16);
				match[2] = match[2].toString(16);
				
				if(match[0].length == 1)
					match[0] = "0"+match[0];
				if(match[1].length == 1)
					match[1] = "0"+match[1];
				if(match[2].length == 1)
					match[2] = "0"+match[2];
				
				// Use this new color value in the IE filter
				this.style.filter ="filter: progid:DXImageTransform.Microsoft.MaskFilter(color=#000000) progid:DXImageTransform.Microsoft.MaskFilter(color=#"+match.join("")+");";
			}
		}
		revertColor(icons[i]);
		//this makes it so that if you reinitialize with new css rules applying for the color they actually get picked up
		icons[i].setColor(YAHOO.util.Dom.getStyle(icons[i], "color"));
	}
};

//this undoes any color setting we have applied
//it is slightly dangerous in that it will clobber any color values set
//explicitly on the element that look like rgb(<r>,<g>,<b>) but we should
//be able to avoid that
function revertColor(element) {
	if (element && element.style.color && element.style.color.match(/^rgb\((\d*)\D*(\d*)\D*(\d*)\)\D*$/)) {
		element.style.color = "";
	}
}


function getRGB(color)
{
	try
	{
		var match = new Array();
	
		var rgb = /^rgb\((\d*)\D*(\d*)\D*(\d*)\)\D*$/;
		var rgba = /^rgba\((\d*)\D*(\d*)\D*(\d*)\D*(\d*)\)\D*$/;
		var hhh = /^#([a-fA-F0-9])([a-fA-F0-9])([a-fA-F0-9])$/;
		var hhhhhh = /^#([a-fA-F0-9]{2})([a-fA-F0-9]{2})([a-fA-F0-9]{2})$/;
		
		if(match = color.match(rgb))
		{
			match.shift();
			
			for(j = 0; j < match.length; j++)
				match[j] = parseInt(match[j]);
		}
		else if(match = color.match(rgba))
		{
			match.shift();
			match.pop();
			
			for(j = 0; j < match.length; j++)
				match[j] = parseInt(match[j]);
			
			if(!match[3])
				return false;
		}
		else
		{
			if(match = color.match(hhh))
			{
				match.shift();
				for(j = 0; j < match.length; j++)
					match[j] = match[j].concat(match[j]);
			}
			else if(match = color.match(hhhhhh))
			{
				match.shift();
			}
	
			for(j = 0; j < match.length; j++)
				match[j] = parseInt(match[j],16);
		}
			
		return match;
	}
	catch(error)
	{
		return false;
	}
};