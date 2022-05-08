Jobs = {};

Jobs.init = function(){
	YAHOO.util.Event.on('app_cancel_status', "click", function(e){YAHOO.util.Event.preventDefault(e); this.change_display("show_app_update_status", 'update_applicant_status', "inline");}, this, true);
	YAHOO.util.Event.on('show_app_update_status', "click", function(e){YAHOO.util.Event.preventDefault(e); this.change_display('update_applicant_status', "show_app_update_status", "block");}, this, true);
	YAHOO.util.Event.on('app_add_note_link', "click", function(e){YAHOO.util.Event.preventDefault(e); this.change_display("add_note", "app_add_note_link", "block");}, this, true);
	YAHOO.util.Event.on('app_note_cancel', "click", function(e){YAHOO.util.Event.preventDefault(e); this.change_display("app_add_note_link", "add_note", "inline");}, this, true);
	YAHOO.util.Event.on('app_add_interview', "click", function(e){YAHOO.util.Event.preventDefault(e); this.change_display("app_interview_container", "app_add_interview", "block");}, this, true);
	YAHOO.util.Event.on('app_cancel_new_interview', "click", function(e){YAHOO.util.Event.preventDefault(e); this.change_display("app_add_interview", "app_interview_container", "inline");}, this, true);
};

Jobs.change_display = function(node_to_show, node_to_hide, display_type){
	
	if(node_to_show != null && node_to_show != "")
	{
		YAHOO.util.Dom.setStyle(node_to_show, 'display', display_type);
	}
	if(node_to_hide != null && node_to_hide != "")
	{
		YAHOO.util.Dom.setStyle(node_to_hide, 'display', 'none');
	}
};

Jobs.save = function() {
	YAHOO.util.Connect.setForm(this.form);
	YAHOO.util.Connect.asyncRequest('POST', this.form.attributes.action.value, {
		success: function(o) {
			this.root.innerHTML = o.responseText;
			this.reinit();
		},
		failure: function(o) {
			
		},
		scope: this
	}, "ajax=true");
	this.cancelSave();
};

GlobalRegistry.register_handler("view_applicant", Jobs.init, Jobs, true);