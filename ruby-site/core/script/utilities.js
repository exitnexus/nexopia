//require nexopia.js
Nexopia.Utilities = {
	/*
		@img: id or element of the image to base actions on
		@options: {
			load: function that gets called when the img is ready (either immediately or after load)
			scope: scope the function gets called in, defaults to the image
			args: an array containing arguments to be passed to the function, defaults to [the image]
		}
	*/
	withImage: function(img, options) {
		var imgElement = YAHOO.util.Dom.get(img);
		if (!options.scope) {
			options.scope = imgElement;
		}
		if (!options.args) {
			options.args = [imgElement];
		}
		if (imgElement.complete) {
			options.load.apply(options.scope, options.args);
		} else {
			YAHOO.util.Event.on(imgElement, 'load', function() {options.load.apply(options.scope, options.args);});
		}
	},
	escapeHTML: function(string) {
		s = string.replace(/&nbsp;/g,' ').replace(/>/g,'&gt;').replace(/</g,'&lt;').replace(/"/g,'&quot;');
		for (var i = 0; i <= 32; i++){
			s = s.replace('&#' + i + ';', "");
		}
		return s;
	},
	escapeURI: function(string) {
		return(escape(string).replace(/\+/g, '%2B'));
	},
	getHexValue: function(color)
	{
		rgb = color.replace(/rgb\((.*?),\s*(.*?),\s*(.*?)\s*\)/,'$1,$2,$3').split(',');
		return Nexopia.Utilities.getHex(rgb);
	},
	getHex: function(rgb)
	{
		return Nexopia.Utilities.toHex(rgb[0]) + Nexopia.Utilities.toHex(rgb[1]) + Nexopia.Utilities.toHex(rgb[2]);
	},
	toHex: function(n) 
	{
		if (n==null) {
			return "00";
		}
		n=parseInt(n, 10); 
		if (n==0 || isNaN(n)) {
			return "00";
		}
		n=Math.max(0,n); 
		n=Math.min(n,255); 
		n=Math.round(n);

		return "0123456789ABCDEF".charAt((n-n%16)/16) + "0123456789ABCDEF".charAt(n%16);
	},
	deduceImgColor: function(element)
	{
		var img = document.createElement("img");
		img.className = "color_icon";
		element.appendChild(img);
		var color = YAHOO.util.Dom.getStyle(img, 'color');
		element.removeChild(img);
		return color;
	},
	trim: function(str) {
		return str.replace(/^\s+|\s+$/g, '') ;
	}
};