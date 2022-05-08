StartValidation = {
	
	init: function()
	{
	},

	
	validateUsername: function()
	{
		username_field = document.getElementById("username");
		username = username_field.value;
		if (username == "")
		{
			Validation.displayValidation("username", "error", "Cannot be blank");
		}
		else if (! username.match(/[a-zA-Z]/))
		{
			Validation.displayValidation("username", "error", "Must have letters.");
		}
		else
		{
			Validation.ajaxValidate("username", "/accountcreate/check_username");
		}
	},
	
	
	validateEmail: function()
	{
		email_field = document.getElementById("email");
		email_confirm_field = document.getElementById("email_confirm");
		email = email_field.value;
		email_confirm = email_confirm_field.value;
		
		not_supported_regex = /.*@((?:mailinator|dodgeit)\.com)$/;
		valid_email_regex = /^[a-z0-9]+([a-z0-9_.+&-]+)*@([a-z0-9.-]+)+\.([a-z0-9.-]+)+$/;
		
		if (email == "")
		{
			Validation.displayValidation("email", "error", "Cannot be blank");
		}
		else if (email.match(not_supported_regex))
		{
			holder = email.replace(not_supported_regex, "$1");
			Validation.displayValidation("email", "error", holder + " cannot currently be used with Nexopia.");
		}
		else if (! email.toLowerCase().match(valid_email_regex))
		{
			Validation.displayValidation("email", "error", "Not a valid email address");
		}
		else
		{
			Validation.ajaxValidate("email", "/accountcreate/check_email");
		}		
	},
	
	
	validateEmailConfirm: function()
	{
		results = email_confirm_chain.validate();
		Validation.displayValidation("email_confirm", results.state, results.message);
	},


	validatePassword: function()
	{
		password_field = document.getElementById("password");
		password_confirm_field = document.getElementById("password_confirm");
		password = password_field.value;
		password_confirm = password_confirm_field.value;

		username_field = document.getElementById("username");
		username = username_field.value;

		if (password == "")
		{
			Validation.displayValidation("password", "error", "Cannot be blank");
		}
		else if (password.length < 4)
		{
			Validation.displayValidation("password", "error", "Password must be at least 4 characters long.");
		}
		else if (password.length > 32)
		{
			Validation.displayValidation("password", "error", "Password cannot be longer than 32 characters.");
		}
		else
		{
			if ((username != null && username != "" && password.indexOf(username) >= 0) ||
				password == "secret" ||
				password.match(/^[a-z]*$/) ||
				password.match(/^[A-Z]*$/) ||
				password.match(/^[a-zA-Z][a-z]*$/))
			{
				Validation.displayValidation("password", "warning", "Password is not very strong");
			}
			else
			{
				Validation.displayValidation("password", "valid", "");
			}
		}		
	},


	validatePasswordConfirm: function()
	{
		results = password_confirm_chain.validate();
		Validation.displayValidation("password_confirm", results.state, results.message);
	},
	
	
	validateDOB: function()
	{
		results = dob_chain.validate();
		Validation.displayValidation("dob", results.state, results.message);
	},
	
	
	validateLocation: function()
	{
		results = location_chain.validate();
		Validation.displayValidation("location", results.state, results.message);
	},
	
	
	validateSex: function()
	{	
		results = sex_chain.validate();
		Validation.displayValidation("sex", results.state, results.message);
	},
	
	
	validateTimezone: function()
	{	
		results = timezone_chain.validate();
		Validation.displayValidation("timezone", results.state, results.message);
	},
	
	
	calculateAge: function(year, month, day)
	{
		current = new Date();
		current_year = current.getFullYear();
		current_month = current.getMonth() + 1;
		current_day = current.getDate();
		age = current_year - year;
		
		if (month > current_month || (month == current_month && day > current_day))
		{
			age = age - 1;
		}
		
		return age;
	}
}

StartValidation.init();