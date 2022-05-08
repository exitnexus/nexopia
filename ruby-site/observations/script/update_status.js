Status = {
	edit: function(element) {
		var type = element.id.match(/^.*_(\w+)$/)[1];
		input = document.getElementById("text_"+type);
		input.style.display = "inline";
		text = document.getElementById("span_"+type);
		text.style.display = "none";
		document.getElementById("script_submit").style.display = 'inline';
		document.getElementById("script_cancel").style.display = 'inline';
		input.focus();
	},
	focus: function(element) {
		var type = element.id.match(/^.*_(\w+)$/)[1];
		document.getElementById("prefix_"+type).style.fontWeight = 'bold';
	},
	blur: function(element) {
		var type = element.id.match(/^.*_(\w+)$/)[1];
		document.getElementById("prefix_"+type).style.fontWeight = '';
	},
	cancel_all: function() {
		spans = YAHOO.util.Dom.getElementsByClassName("span_message", "span", document.getElementById("status_table"));
		for (var i=0;i<spans.length;i++) {
			spans[i].style.display = 'inline';
			var type = spans[i].id.match(/^.*_(\w+)$/)[1];
			document.getElementById('text_'+type).style.display = 'none';
		}
		document.getElementById("script_submit").style.display = 'none';
		document.getElementById("script_cancel").style.display = 'none';
	},
	delete_status: function(element) {
		var id = element.id;
		var type = id.match(/^.*_(\w+)$/)[1];
		document.getElementById("delete_"+type).style.display = "none";
		document.getElementById("spinner_" + type).style.display = "inline";
		YAHOO.util.Connect.asyncRequest('POST', '/my/status/delete', {
			success: function() {
				document.getElementById("span_" + this.argument).innerHTML = '<span class="minor">Click to update</span>';
				document.getElementById("text_" + this.argument).value = ''; //clear the form input field
				document.getElementById("delete_" + this.argument).innerHTML = 'X';
				document.getElementById("delete_" + this.argument).style.display = 'inline';
				document.getElementById("spinner_" + this.argument).style.display = 'none';
			},
			failure: function() {
				//Do something suitable.
			},
			argument: type
		}, "type=" + type + "&form_key=" + SecureForm.getFormKey());
	},
	update_status: function() {
		
		
		YAHOO.util.Connect.setForm('script_form');
		YAHOO.util.Connect.asyncRequest('POST', '/my/status/update/async', {
			success: function(o) {
				var status = document.getElementById("current_status");
				status.innerHTML = o.responseText;
				this.reinit();
			},
			failure: function() {
				alert("Failure");
			},
			scope: this
		}, '');
	},
	list_name: 'status_list',
	form_name: 'status_form',
	cancel_name: 'cancel_link',
	list: null,
	form: null,
	cancel_link: null,
	init_list: function() {
		this.list = document.getElementById(this.list_name);
		document.getElementById("edit_link").onclick = function(){Status.edit();};
	},
	init_form: function() {
		this.form = document.getElementById(this.form_name);
		this.cancel_link = document.getElementById(this.cancel_name);
		//javascript based submission is currently broken in safari and IE, uncomment below when such can be fixed
		//document.getElementById("submit_button").onclick = function(){return Status.submit();};
		document.getElementById("cancel_link").onclick = function(){return Status.cancel();};
	},
	init: function() {
		YAHOO.util.Event.onAvailable(this.list_name, this.init_list, this, true);
		YAHOO.util.Event.onAvailable(this.form_name, this.init_form, this, true);
		delete_links = YAHOO.util.Dom.getElementsByClassName("delete", "span", document.getElementById("status_table"));
		for (var i=0;i<delete_links.length;i++) {
			link=delete_links[i];
			link.onclick = function() {Status.delete_status(this);return false;};
		}
		statuses = YAHOO.util.Dom.getElementsByClassName("status_text", "td", document.getElementById("status_table"));
		for (var i=0;i<statuses.length;i++) {
			var status = statuses[i];
			var type = status.id.match(/^.*_(\w+)$/)[1];
			status.onclick = function() {Status.edit(this);};
			input = document.getElementById("text_" + type);
			input.onblur = function() {Status.blur(this);};
			input.onfocus = function() {Status.focus(this);};
		}
		cancel = document.getElementById("script_cancel");
		cancel.onclick = function() {Status.cancel_all();return false;};
		document.getElementById("script_submit").onclick = function() {Status.update_status();return false;};
	},
	reinit: function() {
		this.init();
	}
};

GlobalRegistry.register_handler("status_update", Status.init, Status, true);