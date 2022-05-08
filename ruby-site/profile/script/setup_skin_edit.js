if(YAHOO.profile == undefined){
	YAHOO.namespace ("profile");
}

/*
	Setup aspects of the skin editor
*/
YAHOO.profile.UserSkin = {
	init: function() {
		PROFILE.init_user_skin_model();
		PROFILE.init_user_skin_selectors();
		PROFILE.init_user_skin_display_group_selectors();
		PROFILE.init_user_skin_areas();
		
		YAHOO.profile.UserSkin.init_skin_editor();
		YAHOO.profile.UserSkin.init_fancy_scroll();
		
		YAHOO.util.Event.addListener("load_user_skin", "click", this.change_skin);
		YAHOO.util.Event.addListener("duplicate_submit", "click", this.duplicate_skin);
		YAHOO.util.Event.addListener("remove_submit", "click", this.remove_skin);
		
		for(var i=0; i<YAHOO.profile.UserSkin.skinable_areas.length; i++)
		{
			YAHOO.util.Event.addListener("skin_select_" + YAHOO.profile.UserSkin.skinable_areas[i], "change", this.update_skin_form);
		}
					
		YAHOO.util.Event.addListener("skin_apply_to_all", "click", this.apply_to_all);
		
		//YAHOO.util.Event.addListener("apply_skin_save", "click", this.save_skin_choices);
		YAHOO.util.Event.addListener("skin_edit_done", "click", this.save_skin);
		
		var self = this;
		function handleUserEntry(e, field)
		{
			if (self.validateColorField(field))
			{
				// Make sure the color picker and the swatch match up with the new color.
				// Note that "setColor" is a function of skin_editor.js.
				// TODO: Untangle some of the coupling between this file and skin_editor.js.
				// by restructuring everything into proper objects.
				setColor();
			}
		}
		
		var colorValueFields = YAHOO.util.Dom.getElementsByClassName("color_hex", "input", null);
		for (var i=0; i < colorValueFields.length; i++)
		{
			YAHOO.util.Event.addListener(colorValueFields[i], "keyup", handleUserEntry, colorValueFields[i]);
			YAHOO.util.Event.addListener(colorValueFields[i], "keydown", handleUserEntry, colorValueFields[i]);
		}
		
		self.validate();
		
		function validateBeforeSave(e)
		{
			var msgs = self.validate();
			if (msgs.length != 0)
			{
				alert(msgs.join('\n'));
				
				YAHOO.util.Event.stopEvent(e);
			}
		}
		YAHOO.util.Event.addListener("save_skin_form", "submit", validateBeforeSave);
		YAHOO.nexopia.Select.list["skin_menu"].setSelected(document.getElementById("skin_id").value);
	},
	
	
	change_skin: function(e)
	{
		var selected_skin = YAHOO.nexopia.Select.list["skin_menu"].getSelected();
		var redirect_location = "/my/profile/edit/skin/"+selected_skin.value+"/";
		
		window.location = redirect_location;
	},
	
	duplicate_skin: function(e)
	{
		YAHOO.util.Event.preventDefault(e);
		
		var selected_skin = YAHOO.nexopia.Select.list["skin_menu"].getSelected();
		var action_location = "/my/profile/edit/skin/"+selected_skin.value+"/duplicate";
		var duplicate_form = document.getElementById("duplicate_form");
		
		duplicate_form.action = action_location;
		
		duplicate_form.submit();
	},
	
	remove_skin: function(e)
	{
		YAHOO.util.Event.preventDefault(e);
		
		var selected_skin = YAHOO.nexopia.Select.list["skin_menu"].getSelected();
		var action_location = "/my/profile/edit/skin/"+selected_skin.value+"/remove";
		var remove_form = document.getElementById("remove_form");
		
		remove_form.action = action_location;
		
		remove_form.submit();
	},
	
	save_skin_choices: function(e)
	{
		var skin_chooser_form = document.getElementById("skin_chooser_form");
		var save_button = document.getElementById("apply_skin_save_bar");
		YAHOO.util.Dom.setStyle(save_button, "display", "none");
		YAHOO.util.Dom.setStyle("apply_skin_spinner", "display", "block");
		
		YAHOO.util.Connect.setForm(skin_chooser_form);
		YAHOO.util.Connect.asyncRequest(skin_chooser_form.method, skin_chooser_form.action, {
			success: function(o) {
				YAHOO.util.Dom.setStyle("apply_skin_spinner", "display", "none");
				YAHOO.util.Dom.setStyle(save_button, "display", "block");
			},
			failure: function(o) {
				alert("failure");
			},
			scope: this
		}, "");
	},
	
	
	validateColorField: function(field)
	{
		var value = field.value;
		
		// Check that the value is either 3 or 6 digits (not counting the # sign), that it begins with a # sign, 
		// and that it only contains valid hex digits
		if ((value.length-1 != 6 && value.length-1 != 3) || value.substring(0,1) != "#" || value.substring(1).match(/[^0-9a-fA-F]/))
		{			
			YAHOO.util.Dom.addClass(field, "skin_data_error");

			return false;
		}
		else
		{
			YAHOO.util.Dom.removeClass(field, "skin_data_error");
			
			return true;
		}
	},
	
	
	validate: function()
	{
		var colorValueFieldError = false;
		var colorValueFields = YAHOO.util.Dom.getElementsByClassName("color_hex", "input", null);
		for (var i = 0; i < colorValueFields.length; i++)
		{
			var field = colorValueFields[i];
			colorValueFieldError = !this.validateColorField(field) || colorValueFieldError;
		}
		
		var errors = [];
		
		if (colorValueFieldError)
		{
			errors.push("One or more of the color values you entered is invalid.");
		}
		
		var nameEntry = YAHOO.util.Dom.getElementsByClassName("input_title", "input", null)[0];
		if (nameEntry.value == "")
		{
			errors.push("The skin name cannot be empty.");
		}
		
		return errors;
	},
	
	
	apply_to_all: function(e)
	{
		
		var apply_to_all = YAHOO.util.Event.getTarget(e);
		var skin_selector = document.getElementById("skin_select_profile");
		
		for(var i=0; i<YAHOO.profile.UserSkin.skinable_areas.length; i++)
		{
			if(YAHOO.profile.UserSkin.skinable_areas[i] == "profile")
			{
				continue;
			}
			
			var temp_selector = document.getElementById("skin_select_"+YAHOO.profile.UserSkin.skinable_areas[i]);
			var temp_hidden = document.getElementById(YAHOO.profile.UserSkin.skinable_areas[i]+"skin");
				
			if(apply_to_all.checked)
			{	
				temp_hidden.value = skin_selector.options[skin_selector.selectedIndex].value;
				temp_selector.selectedIndex = skin_selector.selectedIndex;
				temp_selector.disabled = true;
			}
			else
			{
				temp_selector.disabled = false;
			}
		}
		
		YAHOO.profile.UserSkin.update_skin_form(null);
	},
	
	update_skin_form: function(e)
	{
		var skin_selector;
		if(e)
		{
			skin_selector = YAHOO.util.Event.getTarget(e);
		}
		
		if(!skin_selector)
		{
			skin_selector = document.getElementById("skin_select_profile");
		}

		var name_parts = skin_selector.attributes['id'].value.split("_");
		var area_target = name_parts[2];
	
		var target_input = document.getElementById(area_target + "skin");
		target_input.value = skin_selector.options[skin_selector.selectedIndex].value;
		
		var apply_to_all = document.getElementById("skin_apply_to_all");
		if(area_target == "profile" && apply_to_all.checked)
		{
			for(var i=0; i<YAHOO.profile.UserSkin.skinable_areas.length; i++)
			{
				if(YAHOO.profile.UserSkin.skinable_areas[i] == "profile")
				{
					continue;
				}
				var temp_selector = document.getElementById("skin_select_"+YAHOO.profile.UserSkin.skinable_areas[i]);
				var temp_hidden = document.getElementById(YAHOO.profile.UserSkin.skinable_areas[i] + "skin");
				
				temp_hidden.value = skin_selector.options[skin_selector.selectedIndex].value;
				temp_selector.disabled = false;
				temp_selector.selectedIndex = skin_selector.selectedIndex;
				temp_selector.disabled = true;
			}
		}
		
		YAHOO.profile.UserSkin.save_skin_choices();
	},
	
	save_skin: function(e)
	{
		var skin_form = document.getElementById("save_skin_form");
		skin_form.submit();
	}
};
Overlord.assign({
	minion: "skin_edit_wrapper",
	load: function(element) {
		YAHOO.profile.UserSkin.init();
	}
});