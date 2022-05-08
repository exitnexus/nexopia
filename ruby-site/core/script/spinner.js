/*
	cfg properties:
		lazyload:
			- if true, the spinner will be initialized upon calling "on". The "off" function will destroy it.
			- if false, the spinner will be initialized during construction. The "off" function will then simply hide it
		xy: [{x}, {y}]
			- {x} is the absolute horizontal position (from the left)
			- {y} is the absolute vertical position (from the top)
		context: [{element}, {corner}]
			- {element} is the DOM element you want the spinner to align to
			- {corner} is the corner of the element you want the spinner to align to:
				- tr: the top right corner of the spinner will align to the top right corner of the element
				- tl: the top left corner of the spinner will align to the top left corner of the element
				- br: the bottom right corner of the spinner will align to the bottom right corner of the element
				- bl: the bottom left corner of the spinner will align to the bottom left corner of the element
		offset: [{x}, {y}]
			- {x} is the number of pixels horizontally in from the corner
			- {y} is the number of pixels vertically in from the corner
	
		Note that "offset" only works with a "context", and if you use a "context", you shouldn't use an "xy" setting.
		
	Functions:
		on: Make the spinner visible.
		off: Destroy the spinner.
*/
function Spinner(cfg)
{
	this.cfg = cfg;
	
	if(!cfg.lazyload)
	{
		this.init(cfg);
	}
};

Spinner.prototype =
{
	init: function(cfg)
	{
		var positionCfg = null;
		if (cfg.xy)
		{
			positionCfg = { x: cfg.xy[0], y: cfg.xy[1] };
		}
		else if (cfg.context)
		{
			positionCfg = { context: [cfg.context[0], cfg.context[1], cfg.context[1]] };
		}

		var overlayCfg = YAHOO.lang.merge(
			{ visible: false, width: "16px", height: "16px" },
			positionCfg);

		this.overlay = new YAHOO.widget.Overlay("spinner", overlayCfg );
		this.overlay.setBody("<img src=\"" + Site.staticFilesURL + "/Legacy/images/spinner.gif\"/>");
		this.overlay.render(document.body);

		if (cfg.offset && cfg.context)
		{
			var xMod = cfg.offset[0];
			var yMod = cfg.offset[1];
			var directions = cfg.context[1].split("");

			if (directions[0] == "b")
			{
				yMod = yMod * -1;
			}

			if (directions[1] == "r")
			{
				xMod = xMod * -1;
			}

			this.overlay.cfg.setProperty("x", this.overlay.cfg.getProperty("x") + xMod);
			this.overlay.cfg.setProperty("y", this.overlay.cfg.getProperty("y") + yMod);
		}		
	},
	
	on: function()
	{
		if (this.cfg.lazyload)
		{
			this.init(this.cfg);
		}
		
		this.overlay.show();
	},
	
	off: function()
	{
		if (this.cfg.lazyload)
		{
			this.overlay.destroy();
		}
		else
		{
			this.overlay.hide();
		}
	}
};