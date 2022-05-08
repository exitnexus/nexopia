CommentsProfileBlock = {
	init: function() {
		var util = YAHOO.util;
		var event = util.Event;
		var dom = util.Dom;
		
		event.on("comment_text", "click", this.clearInput, this);
		event.on("comment_post_submit", "click", this.submitPost, this);
		var post_submit = dom.get("comment_post_submit")
		if (post_submit)
			post_submit.disabled = true;
		
		var delete_submits = dom.getElementsByClassName("delete_submit", "a", "comments_profile_block");
		event.on(delete_submits, "click", this.submitDelete, this);
	},
	
	clearInput: function(event, that) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		// clear the listener off so it doesn't go again
		YAHOO.util.Event.removeListener("comment_text", "click", this.clearInput);
		
		YAHOO.util.Dom.get("comment_text").value = ""
		YAHOO.util.Dom.get("comment_post_submit").disabled = false;
	},
	
	submitPost: function(event, that) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		var form = YAHOO.util.Dom.get("comment_write_form");
		YAHOO.util.Connect.setForm(form);
		// TODO: make this change the url to the ajax one instead of always using the ajax one.
		YAHOO.util.Connect.asyncRequest('POST', form.ajax_post_url.value, new ResponseHandler({
			success: function(o) {
				that.init();
			},
			failure: function(o) {
			},
			scope: this
		}), "ajax=true");
	},
	
	submitDelete: function(ev, that) {
		var util = YAHOO.util;
		var event = util.Event;
		var dom = util.Dom;
		var connect = util.Connect;
		
		if (ev) {
			event.stopEvent(ev);
		}
		
		var target = event.getTarget(ev);
		var form = dom.getAncestorByTagName(target, "form");
		connect.setForm(form);
		connect.asyncRequest('POST', form.ajax_delete_url.value, new ResponseHandler({
			success: function(o) {
				that.init();
			},
			failure: function(o) {
			},
			scope: this
		}), "ajax=true");
	}
};

GlobalRegistry.register_handler("comments_profile_block", CommentsProfileBlock.init, CommentsProfileBlock, true);
