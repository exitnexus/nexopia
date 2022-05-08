Overlord.assign({
	minion: "login:show_join",
	click: function(event, element) {
		YAHOO.util.Event.preventDefault(event);
		
		YAHOO.util.Dom.setStyle('inline_login_page_container', 'display', 'none');
		YAHOO.util.Dom.setStyle('login_page', 'display', 'none');
		
		YAHOO.util.Dom.setStyle('inline_join_container', 'display', 'block');
		YAHOO.util.Dom.setStyle('join_page', 'display', 'block');

		YAHOO.util.Dom.getElementsByClassName("post_response", "div", null, function(e){ YAHOO.util.Dom.setStyle(e, 'display', 'none') });
	}
});

Overlord.assign({
	minion: "login:lost_toggle",
	click: function(event, element) {
		YAHOO.util.Event.preventDefault(event);

		var display = YAHOO.util.Dom.getStyle('get_password_panel', 'display');
		if(display == "none")
		{
			display = "block";
		}
		else
		{
			display = "none";
		}
		
		YAHOO.util.Dom.setStyle('get_password_panel', 'display', display);
	}
});