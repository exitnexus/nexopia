InviteFriends = 
{
	addRow: function ()
	{
		// Get the table and form from the page
		form = document.getElementById("invite_form");
		table = document.getElementById("invite_table");
	
		index = table.rows.length - 5;
	
		// Create a new input field for the tag
		name_input = document.createElement("input");
		name_input.setAttribute("type","text");
		name_input.setAttribute("class", "text_small");
		name_input.setAttribute("name","friend_name[" + index + "]");
		name_input.setAttribute("id","friend_name[" + index + "]");
		name_input.setAttribute("value","");
	
		// Create a new input field for the username
		email_input = document.createElement("input");
		email_input.setAttribute("type","text");
		email_input.setAttribute("class","text");
		email_input.setAttribute("name","friend_email[" + index + "]");
		email_input.setAttribute("id","friend_email[" + index + "]");
		email_input.setAttribute("value","");
	
		// Get the table body
		table_body = table.getElementsByTagName("tbody")[0];
	
		// Create the new tr/td elements for the table.
		tr = document.createElement("tr");
		td_name = document.createElement("td");
		td_email = document.createElement("td");
	
		// Put the input fields in the appropriate td elements.
		td_name.appendChild(name_input);
		td_email.appendChild(email_input);
	
		// Attach the entire row to the table body.
		tr = table_body.insertRow(index + 4);
		
		// Attach the td elements to their tr parent
		tr.appendChild(td_name);
		tr.appendChild(td_email);
	},
	
	
	/////////////// Validation stuff ///////////////////////	
	validateMyName: function()
	{
		my_name_field = document.getElementById("my_name");
		my_name = my_name_field.value;
		if (my_name == "")
		{
			Validation.displayValidation("my_name", "error", "Cannot be blank");
		}
		else
		{
			Validation.displayValidation("my_name", "valid", "");
		}
	},
	
	
	validateFriend: function(row_number)
	{
		friend_name_field = document.getElementById("friend_name[" + row_number + "]");
		friend_email_field = document.getElementById("friend_email[" + row_number + "]");
		friend_name = friend_name_field.value;
		friend_email = friend_email_field.value;
		
		valid_email_regex = /^[a-z0-9]+[a-z0-9_.+&-]*@[a-z0-9.-]+\.[a-z0-9.-]+$/;
							
		if (friend_email == "" && friend_name == "")
		{
			Validation.displayValidation("friend[" + row_number + "]", "none", "");
			return;
		}
		
		if (friend_email == "" && friend_name != "")
		{
			Validation.displayValidation("friend[" + row_number + "]", "error", "Email cannot be blank");
		}
		else if (! friend_email.toLowerCase().match(valid_email_regex))
		{
			Validation.displayValidation("friend[" + row_number + "]", "error", "Not a valid mail address");
		}
		else if (friend_email != "" && friend_name == "")
		{
			Validation.displayValidation("friend[" + row_number + "]", "error", "Name cannot be blank");
		}
		else
		{
			Validation.displayValidation("friend[" + row_number + "]", "valid", "");
		}
	}
};