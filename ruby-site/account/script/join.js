Overlord.assign({
	minion: "join:show_login",
	click: function(event, element) {
		YAHOO.util.Event.preventDefault(event);

		YAHOO.util.Dom.setStyle('inline_join_container', 'display', 'none');
		YAHOO.util.Dom.setStyle('join_page', 'display', 'none');

		YAHOO.util.Dom.setStyle('inline_login_page_container', 'display', 'block');
		YAHOO.util.Dom.setStyle('login_page', 'display', 'block');

 		YAHOO.util.Dom.getElementsByClassName("post_response", "div", null, function(e){ YAHOO.util.Dom.setStyle(e, 'display', 'none') });
	}
});

Overlord.assign({
	minion: "join:main",
	load: function(element) {
		AccountValidation.init(["username","password","email","email_confirm","dob","sex"]);
		AccountValidation.updateValidationStates();
		NexopiaPanel.linkBeforeOpenMap["join_button-button"] = AccountValidation.failIfNotValid;
	}
});
