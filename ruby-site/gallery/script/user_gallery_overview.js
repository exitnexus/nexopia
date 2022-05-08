Overlord.assign({
	minion: "ugo:delete_link",
	click: function(event, element) {
		YAHOO.util.Event.preventDefault(event);
		if( confirm("Delete album?") ) {
			YAHOO.util.Connect.asyncRequest('POST', element.href, new ResponseHandler({}));	
		}
	}
	
});

Overlord.assign({
	minion: "ugo:description",
	load: function(element) {
		new Truncator(element);
	}
});