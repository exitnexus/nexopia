Overlord.assign({
	minion: "gallery_abuse_section",
	load: function(element) {
	
		YAHOO.util.Event.on("submit_abuse_log", "click", function(event){
			if (event) {
				YAHOO.util.Event.preventDefault(event);
			}
	
			var form = YAHOO.util.Dom.get("abuse_log_form");
			YAHOO.util.Connect.setForm(form);
			YAHOO.util.Connect.asyncRequest('POST', form.gallery_abuse_url.value, new ResponseHandler({
				success: function(o) {
					YAHOO.util.Dom.get("abuse_log_entry").value = "";
					YAHOO.util.Dom.get("abuse_log_subject").value = "";
				},
				failure: function(o) {
				}
			}));
		});
	}	
});