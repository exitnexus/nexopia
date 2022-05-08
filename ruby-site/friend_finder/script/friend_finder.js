FriendFinder = {
	update_checkbox: function(checkbox_value)
	{		
		var checkbox_list = YAHOO.util.Dom.getElementsByClassName("friend_add_result", "input", "find_friends", null);
		
		if(checkbox_list.length < 1)
		{
			checkbox_list = YAHOO.util.Dom.getElementsByClassName("invite_result", "input", "find_friends", null);
		}
		
		var i;
		for(i=0; i<checkbox_list.length; i++)
		{
			checkbox_list[i].checked = checkbox_value;
		}
	},
	
	add_invite_field: function()
	{
		var invite_container = document.getElementById("manual_invite");
		
		var i;
		var temp;
		var max_id = 0;
		for(i=0; i<invite_container.childNodes.length; i++)
		{
			temp = invite_container.childNodes[i];
			if(temp.id.match(/^manual_invite_\d{1,2}$/))
			{
				var temp_parts = temp.id.split("_");
				var temp_id_num = parseInt(temp_parts[2], 10);
				
				if(temp_id_num > max_id)
				{
					max_id = temp_id_num;
				}
			}
		}
		
		if(max_id == 19)
		{
			return;
		}
		
		var new_input = document.createElement("input");
		new_input.type = "text";
		new_input.name = "manual_invite_" + (max_id+1);
		new_input.id = new_input.name;
		
		invite_container.appendChild(new_input);
		
		invite_container.appendChild(document.createElement("br"));
	},
	
	hotmail_override: function()
	{
		var email_input = document.getElementById("import_email");
		var password_input = document.getElementById("import_password");
		var password_input_live = document.getElementById("import_password_live");
		email_parts = email_input.value.split("@");
		if(email_parts.length != 2)
		{
			return;
		}
		
		email_domain = email_parts[1];
		if(email_domain.match(/^hotmail\.(com|fr|co\.uk){1}$/) || email_domain.match(/^live\.(com|ca|co\.uk|fr|com\.au|nl|jp){1}$/))
		{
			YAHOO.util.Dom.setStyle(password_input, 'display', 'none');
			YAHOO.util.Dom.setStyle(password_input_live, 'display', 'block');
		}
		else
		{
			YAHOO.util.Dom.setStyle(password_input, 'display', 'block');
			YAHOO.util.Dom.setStyle(password_input_live, 'display', 'none');
		}
	}
};

Overlord.assign({
	minion: "friend_finder:select_all",
	click: function(event, element){
		YAHOO.util.Event.preventDefault(event);
		FriendFinder.update_checkbox(true);
	}
});

Overlord.assign({
	minion: "friend_finder:select_none",
	click: function(event, element){
		YAHOO.util.Event.preventDefault(event);
		FriendFinder.update_checkbox(false);
	}
});

Overlord.assign({
	minion: "friend_finder:add_invite_field",
	click: function(event, element){
		YAHOO.util.Event.preventDefault(event);
		FriendFinder.add_invite_field();
	}
});

Overlord.assign({
	minion: "friend_finder:hotmail_override",
	load: function(element){
		YAHOO.util.Event.addListener(element, "keyup", FriendFinder.hotmail_override, this);
	}
});