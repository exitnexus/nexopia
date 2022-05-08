Overlord.assign({
	minion: "blogs:submit_new",
	click: function(event) {
		YAHOO.util.Event.preventDefault(event);
		YAHOO.util.Dom.get('new_blog').submit();
	}
});

Overlord.assign({
	minion: "blogs:tip",
	click: function(event, element) {
		YAHOO.util.Event.preventDefault(event);
		var tip = element.nextSibling;
		if (tip) {
			if (YAHOO.util.Dom.getStyle(tip, 'display') == "none") {
				YAHOO.util.Dom.setStyle(tip, 'display', 'block');
			} else {
				YAHOO.util.Dom.setStyle(tip, 'display', 'none');
			}
		}
	}
});

Overlord.assign({
	minion: "blogs:options",
	click: function(event, element) {
		YAHOO.util.Event.preventDefault(event);
		var tip = element.nextSibling;
		if (tip) {
			if (YAHOO.util.Dom.getStyle(tip, 'display') == "none") {
				YAHOO.util.Dom.setStyle(tip, 'display', 'block');
				element.innerHTML = "Fewer Options";
				element.className = "collapse"
			} else {
				YAHOO.util.Dom.setStyle(tip, 'display', 'none');
				element.innerHTML = "More Options";
				element.className = "expand"
			}
		}
	}
});