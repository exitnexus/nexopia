YAHOO.util.Event.on(window, 'load', init_custom_color_icons_on_event);

function init_custom_color_icons_on_event()
{
	init_custom_color_icons()
}

function init_custom_color_icons(icons)
{
	if(!icons)
		icons = YAHOO.util.Dom.getElementsByClassName("custom_color_icon");
	
	for(i = 0; i < icons.length; i++)
	{
		if(!YAHOO.env.ua.ie)
		{
			var canvas = document.createElement('canvas');
			
			canvas.width = icons[i].width;
			canvas.height = icons[i].height;
			
			YAHOO.util.Dom.addClass(canvas, "custom_color_icon");
			YAHOO.util.Dom.setStyle(canvas, "color", YAHOO.util.Dom.getStyle(icons[i], "color"));
			icons[i].parentNode.replaceChild(canvas, icons[i]);
			canvas.maskImage = icons[i];
			icons[i] = canvas;
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
				// Convert color to a 6 digit hex value
				var rgb = /^rgb\((\d*)\D*(\d*)\D*(\d*)\)\D*$/;
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
		
		icons[i].setColor(YAHOO.util.Dom.getStyle(icons[i], "color"));
	}
}


/********** Custom Icons with Alpha in IE **********/

/*
	NOTE:
		Due to a DOCTYPE problem in IE custom icons in IE will be rendered to display as a block element.
		For consistancy I have forced other browsers to do the same.
*/

YAHOO.util.Event.on(window, 'load', init_custom_color_icons_alpha_event);

function init_custom_color_icons_alpha_event()
{
	init_custom_color_icons_alpha();
}

function init_custom_color_icons_alpha(icons)
{
	if(!icons)
		icons = YAHOO.util.Dom.getElementsByClassName("custom_color_icon_alpha");
	
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
			icons[i].parentNode.replaceChild(canvas, icons[i]);
			canvas.maskImage = icons[i];
			icons[i] = canvas;
		}
		else
		{
			var wrapper = document.createElement('div');
		
			icons[i].maskImageSrc = icons[i].src;
			icons[i].width = icons[i].width;
			icons[i].height = icons[i].height;
			
			icons[i].src = "/~shealy/Playground/custom_icon/images/transparent.gif";
			icons[i].style.filter ="filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" + icons[i].maskImageSrc + "', sizingMethod='scale');";
			
			YAHOO.util.Dom.setStyle(wrapper, "width", icons[i].width);
			YAHOO.util.Dom.setStyle(wrapper, "height", icons[i].height);
			
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
				// Convert color to a 6 digit hex value
				var rgb = /^rgb\((\d*)\D*(\d*)\D*(\d*)\)\D*$/;
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
		
		icons[i].setColor(YAHOO.util.Dom.getStyle(icons[i], "color"));
	}
}
