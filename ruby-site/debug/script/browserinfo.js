function initialize_flashplugin_version(dialog) {
	var field = document.getElementById("flash_version");
	
	var plugin = (navigator.mimeTypes && navigator.mimeTypes["application/x-shockwave-flash"] ? navigator.mimeTypes["application/x-shockwave-flash"].enabledPlugin : 0);
	if (plugin) {
		field.innerHTML = plugin.description;
	} else {
		field.innerHTML = "n/a";
	}
}

