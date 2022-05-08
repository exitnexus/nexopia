FriendFinderResults = {
	init: function(){
		//booleans for completion status of the two page parts.
		this.email_invite_complete = false;
		this.member_invite_complete = false;
		
		//check if there are results for the parts. If not, they are completed.
		var member_input_list = YAHOO.util.Dom.getElementsBy(this.is_checkbox, "input", "member_results", function(){});
		if(member_input_list.length == 0){
			this.member_invite_complete = true;
		}
		var invite_input_list = YAHOO.util.Dom.getElementsBy(this.is_checkbox, "input", "invite_results", function(){});
		if(invite_input_list.length == 0){
			this.email_invite_complete = true;
		}
		
		//Set the actions for the All and None links below the the result lists. 
		var member_selectors = YAHOO.util.Dom.getElementsByClassName("result_selectors", "div", "member_results", function(element){});
		var invite_selectors = YAHOO.util.Dom.getElementsByClassName("result_selectors", "div", "invite_results", function(element){});
		var root;
		if(member_selectors.length > 0){
			root = member_selectors.pop();
			YAHOO.util.Event.addListener(root.firstChild, 'click', this.select_all, this, true);
			YAHOO.util.Event.addListener(root.lastChild, 'click', this.select_none, this, true);
		}
		if(invite_selectors.length > 0){
			root = invite_selectors.pop();
			YAHOO.util.Event.addListener(root.firstChild, 'click', this.select_all, this, true);
			YAHOO.util.Event.addListener(root.lastChild, 'click', this.select_none, this, true);
		}
		
		//Setting up the submit buttons for AJAX'ing
		YAHOO.util.Event.addListener("member_submit", 'click', this.add_friends, this, true);
		YAHOO.util.Event.addListener("invite_submit", 'click', this.send_invites, this, true);
		
		//Setting the result panes to be scrollable if they exceed 300px for members and 180px for email invites.
		var member_results = YAHOO.util.Dom.getElementsByClassName("result_container", "div", "member_results", function(){});
		var invite_results = YAHOO.util.Dom.getElementsByClassName("result_container", "div", "invite_results", function(){});
		
		member_results_height = parseInt(YAHOO.util.Dom.getStyle(member_results, "height"));
		invite_results_height = parseInt(YAHOO.util.Dom.getStyle(invite_results, "height"));
		if(member_results_height > 300){
			YAHOO.util.Dom.setStyle(member_results, "height", "300px");
		}
		
		if(invite_results_height > 180){
			YAHOO.util.Dom.setStyle(invite_results, "height", "180px");
		}
	},
	
	add_friends: function(event){
		if (event) {
			YAHOO.util.Event.preventDefault(event);
			YAHOO.util.Event.stopPropagation(event);
		}
		var target = YAHOO.util.Event.getTarget(event);
		this.save(target.form);
		this.member_invite_complete = true;
		this.form_complete();
	},
	
	send_invites: function(event){
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		var target = YAHOO.util.Event.getTarget(event);
		this.save(target.form);
		this.email_invite_complete = true;
		this.form_complete();
	},
	
	save: function(form){
		YAHOO.util.Connect.setForm(form);
		
		YAHOO.util.Connect.asyncRequest('POST', form.attributes['action'].value.concat("ajax/"), {
			success: function(o){
				this.update_display(form);
			},
			failure: function(o){
				
			},
			scope: this
		}, "ajax=true");
	},
	
	form_complete: function(){
		if(this.member_invite_complete && this.email_invite_complete){
			var finish_input_list = YAHOO.util.Dom.getElementsBy(function(){return true;}, 'input', "finish_form", function(){});
			var i;
			var finish_location;
			for(i=0; i<finish_input_list.length; i++){
				if(finish_input_list[i].attributes['name'].value == "finish_location"){
					finish_location = finish_input_list[i].attributes['value'].value;
					break;
				}
			}
			setTimeout(function(){window.location = finish_location;}, 4000);
		}
	},
	
	update_display: function(form){
		var input_list = YAHOO.util.Dom.getElementsBy(function(){return true;}, 'input', form, function(){});
		
		var i;
		for(i=0; i< input_list.length; i++){
			YAHOO.util.Dom.setStyle(input_list[i], "display", "none");
		}
		
		var selector_controls = YAHOO.util.Dom.getElementsByClassName("result_selectors", "div", form, function(){});
		
		for(i=0; i< selector_controls.length; i++){
			YAHOO.util.Dom.setStyle(selector_controls[i], "display", "none");
		}
		
		var personal_message_input_list = YAHOO.util.Dom.getElementsByClassName("personalized_message_input", "div", form, function(){});
		
		for(i=0; i<personal_message_input_list.length; i++){
			YAHOO.util.Dom.setStyle(personal_message_input_list[i], "display", "none");
		}
	},
	 
	select_all: function(event){
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		var target = YAHOO.util.Event.getTarget(event);
		var root = target.parentNode.parentNode;
		if(typeof(form) == "String"){
			form = YAHOO.util.Dom.get(form);
		}
		var input_list = YAHOO.util.Dom.getElementsBy(this.is_checkbox, 'input', root, function(){});
		
		var i;
		for(i=0; i< input_list.length; i++){
			input_list[i].checked = true;
		}
	},
	
	select_none: function(event){
		if (event) {
			YAHOO.util.Event.preventDefault(event);
		}
		var target = YAHOO.util.Event.getTarget(event);
		var root = target.parentNode.parentNode;
		var input_list = YAHOO.util.Dom.getElementsBy(this.is_checkbox, 'input', root, function(){});
		
		var i;
		for(i=0; i< input_list.length; i++){
			input_list[i].checked = false;
		}
	},
	
	is_checkbox: function(el) {
		if(el.tagName.toLowerCase() == 'input' && el.attributes['type'].value == 'checkbox'.toLowerCase()){
			return true;
		}
		return false;
	}
};

GlobalRegistry.register_handler('contact_search_results', FriendFinderResults.init, FriendFinderResults, true);