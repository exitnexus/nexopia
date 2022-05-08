Friends = {
	init: function() {
		this.reverse = YAHOO.util.Dom.get("reverse");
		var comments = YAHOO.util.Dom.getElementsByClassName("comment");
		for (var i=0;i<comments.length;i++) {
			new Friends.Comment(comments[i]);
		}
		var offline = YAHOO.util.Dom.getElementsByClassName("offline");
		for (var i=0;i<offline.length;i++) {
			new ToolTip(offline[i], {text:"Offline"});
		}
		var online = YAHOO.util.Dom.getElementsByClassName("online");
		for (var i=0;i<online.length;i++) {
			new ToolTip(online[i], {text:"Online"});
		}
		var deleteLinks = YAHOO.util.Dom.getElementsByClassName("delete");
		for (var i=0;i<deleteLinks.length;i++) {
			new Friends.DeleteLink(deleteLinks[i], this.reverse);
		}
		var addLinks = YAHOO.util.Dom.getElementsByClassName("add");
		for (var i=0;i<addLinks.length;i++) {
			new Friends.AddLink(addLinks[i]);
		}
		if (YAHOO.util.Dom.get("user_name")) {
			this.userName = YAHOO.util.Dom.get("user_name").innerHTML;
		}
	}
};

Friends.DeleteLink = function(element, reverse) {
	this.element = element;
	this.reverse = reverse;
	if (this.reverse) {
		this.confirmMessage = "Remove yourself?";
	} else {
		this.confirmMessage = "Remove friend?";
	}
	if (this.reverse) {
		new ToolTip(element, {text:"Remove Yourself"});
	} else {
		new ToolTip(element, {text:"Remove Friend"});
	}
	YAHOO.util.Event.on(element, 'click', this.submit, this, true);
};

Friends.DeleteLink.prototype = {
	submit: function(event) {
		YAHOO.util.Event.preventDefault(event);
		if (confirm(this.confirmMessage)) {
			this.element.innerHTML="<img src=\"" + Site.staticFilesURL + "/Legacy/images/spinner.gif\"/>";
			YAHOO.util.Connect.asyncRequest('post', this.element.href, {
				success: function() {
					var li = YAHOO.util.Dom.getAncestorByClassName(this, "friend_list_entry");
					li.parentNode.removeChild(li);
				},
				scope: this.element
			}, 'form_key=' + SecureForm.getFormKey());
		}
	}
};

Friends.AddLink = function(element) {
	this.element = element;
	new ToolTip(this.element, {text:"Add as Friend"});
	YAHOO.util.Event.on(this.element, 'click', this.submit, this, true);
};

Friends.AddLink.prototype = {
	submit: function(event) {
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		this.element.innerHTML="<img src=\"" + Site.staticFilesURL + "/Legacy/images/spinner.gif\"/>";
		YAHOO.util.Connect.asyncRequest('POST', this.element.href, {
			success: function() {
				this.parentNode.removeChild(this);
			},
			scope: this.element
		}, 'null=null');
	}
};


Friends.Comment = function(element) {
	this.element = element;
	this.textarea = document.createElement("textarea");
	if (element.firstChild.tagName != "SPAN") {
		this.textarea.innerHTML = element.innerHTML;
	}
	YAHOO.util.Event.on(this.element, 'click', this.edit, this, true);
	YAHOO.util.Event.on(this.textarea, 'blur', this.save, this, true);
	new ToolTip(this.element, {text:"Click to edit notes."});
};

Friends.Comment.prototype = {
	edit: function() {
		this.element.innerHTML = "";
		this.element.appendChild(this.textarea);
		this.textarea.focus();
	},
	save: function() {
		this.element.removeChild(this.textarea);
		this.element.innerHTML = "<img src='"+Site.staticFilesURL+"/Legacy/images/spinner.gif'";
		var friendID = this.element.id.match(/comment_(.*)/)[1];
		YAHOO.util.Connect.asyncRequest(
			'POST', 
			'/users/'+escape(Friends.userName)+'/friends/comments/update/' + friendID, 
			new ResponseHandler({
				success: function() {
					new Friends.Comment(document.getElementById(this.element.id));					
				},
				scope: this
			}),
			"form_key=" + SecureForm.getFormKey() + "&comment=" + escape(this.textarea.value)
		);
	}
};

GlobalRegistry.register_handler("friends_page", Friends.init, Friends, true);