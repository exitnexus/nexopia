Overlord.assign({
	minion: "button_link",
	click: function(event, element) {
		path = element.getAttribute("path");
		if (path) {
			YAHOO.util.Event.preventDefault(event);
			document.location = path;
		}
	}
});