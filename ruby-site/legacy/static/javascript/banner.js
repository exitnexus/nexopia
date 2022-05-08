var MM_FlashCanPlay = false;
var MM_contentVersion = 7;
if (navigator.userAgent && navigator.userAgent.indexOf("MSIE")>=0
   && (navigator.appVersion.indexOf("Win") != -1)) {

	document.write('<SCR' + 'IPT LANGUAGE=VBScript\> \n'); //FS hide this from IE4.5 Mac by splitting the tag
	document.write('on error resume next \n');
	document.write('MM_FlashCanPlay = ( IsObject(CreateObject("ShockwaveFlash.ShockwaveFlash.' + MM_contentVersion + '")))\n');
	document.write('</SCR' + 'IPT\> \n');
}

function flashbanner(flashfile, image, link, width, height, bgcolor){
	var plugin = (navigator.mimeTypes && navigator.mimeTypes["application/x-shockwave-flash"]) ? navigator.mimeTypes["application/x-shockwave-flash"].enabledPlugin : 0;
	if ( plugin ) {
		var words = navigator.plugins["Shockwave Flash"].description.split(" ");

	    for (var i = 0; i < words.length; ++i)    {
			if (!isNaN(parseInt(words[i])))
				var MM_PluginVersion = words[i];
	    }
		MM_FlashCanPlay = MM_PluginVersion >= MM_contentVersion;
	}

	if ( MM_FlashCanPlay ) {
		document.write('<OBJECT classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"');
		document.write(' codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" ');
		document.write(' ID="script" WIDTH="' + width + '" HEIGHT="' + height + '" ALIGN="">');
		document.write(' <PARAM NAME=movie VALUE="' + flashfile + '"> <PARAM NAME=quality VALUE=high> <PARAM NAME=bgcolor VALUE=' + bgcolor + '>  ');
		document.write(' <EMBED src="' + flashfile + '" quality=high bgcolor=' + bgcolor + '  ');
		document.write(' swLiveConnect=FALSE WIDTH="' + width + '" HEIGHT="' + height + '" NAME="banner" ALIGN=""');
		document.write(' TYPE="application/x-shockwave-flash" PLUGINSPAGE="http://www.macromedia.com/go/getflashplayer">');
		document.write(' </EMBED>');
		document.write(' </OBJECT>');
	} else{
		if(link != "")
			document.write('<a href="' + link + '">');
		if(image != "")
			document.write('<img src="' + image + '" width="' + width + '" height="' + height + '" border=0>');
		if(link != "")
			document.write('</a>');
	}
}
