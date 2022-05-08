AccountValidation = {
	
	init: function(fields)
	{
		Validation.init(fields);
	},

	
	validateUsername: function(silent)
	{
		var username_field = document.getElementById("username");
		var username = username_field.value;

		if (username == null || username == "")
		{
			Validation.displayValidation("username", "error", "Cannot be blank", silent);
		}
		else if (username.match(/\s/))
		{
			Validation.displayValidation("username", "error", "No spaces allowed", silent);
		}
		else if (! username.match(/[a-zA-Z]/))
		{
			Validation.displayValidation("username", "error", "Must have letters.", silent);
		}
		else if (m = username.match(/[^a-zA-Z0-9~\^\*\-\\|\]\}\[\{\.]/))
		{
			Validation.displayValidation("username", "error", "Illegal character: " + Nexopia.Utilities.escapeHTML(m.toString()), silent);
		}
		else
		{
			Validation.ajaxValidate("username", "/account/check_username", silent);
		}
	},
	
	
	validateEmail: function(silent)
	{
		var email_field = document.getElementById("email");
		var email_confirm_field = document.getElementById("email_confirm");
		var email = email_field.value;
		
		var not_supported_regex = /.*@((?:mailinator|dodgeit)\.com)$/;
		var valid_email_regex = /^[a-z0-9]+[a-z0-9_.+&-]*@[a-z0-9.-]+\.[a-z0-9.-]+$/;
		
		if (email == null || email == "")
		{
			Validation.displayValidation("email", "error", "Cannot be blank", silent);
		}
		else if (email.match(not_supported_regex))
		{
			var holder = email.replace(not_supported_regex, "$1");
			Validation.displayValidation("email", "error", holder + " cannot currently be used with Nexopia.", silent);
		}
		else if (! email.toLowerCase().match(valid_email_regex))
		{
			Validation.displayValidation("email", "error", "Not a valid email address", silent);
		}
		else
		{
			Validation.ajaxValidate("email", "/account/check_email", silent);
		}		
	},
	
	
	validateEmailConfirm: function(silent)
	{
		var results = email_confirm_chain.validate();
		Validation.displayValidation("email_confirm", results.state, results.message, silent);
	},


	validatePassword: function(silent)
	{
		var password_field = document.getElementById("password");
		var password = password_field.value;

		var username_field = document.getElementById("username");
		var username = username_field.value;

		if (password == null || password == "")
		{
			Validation.displayValidation("password", "error", "Cannot be blank", silent);
		}
		else if (password.length < 4)
		{
			Validation.displayValidation("password", "error", "4 characters minimum", silent);
		}
		else if (password.length > 32)
		{
			Validation.displayValidation("password", "error", "32 characters maximum", silent);
		}
		else
		{
			if ((username != null && username != "" && password.indexOf(username) >= 0) ||
				password == "secret" || password == "password")
			{
				Validation.displayValidation("password", "warning", "Weak", silent);
			}
			else if(password.match(/^[A-Z]?[a-z]+$/) || password.match(/^[A-Z]+$/) || password.match(/^\d+$/))
			{
				Validation.displayValidation("password", "warning", "Medium", silent);
			}
			else
			{
				Validation.displayValidation("password", "valid", "Strong", silent);
			}
		}
	},

	passwordFocus: function()
	{
		var password_field = document.getElementById("password");
		var password = password_field.value;
		if (password.length < 4)
		{
			Validation.displayValidation("password", "error", "4 characters minimum");
		}
	},
	
	validatePasswordConfirm: function(silent)
	{
		var results = password_confirm_chain.validate();
		Validation.displayValidation("password_confirm", results.state, results.message, silent);
	},
	
	
	validateDOB: function(silent)
	{
		var results = dob_chain.validate();
		Validation.displayValidation("dob", results.state, results.message, silent);
	},
	
	
	validateLocation: function(silent)
	{
		var results = location_chain.validate();
		Validation.displayValidation("location", results.state, results.message, silent);
	},
	
	
	validateSex: function(silent)
	{	
		var results = sex_chain.validate();
		Validation.displayValidation("sex", results.state, results.message, silent);
	},
	
	
	validateTimezone: function(silent)
	{	
		var results = timezone_chain.validate();
		Validation.displayValidation("timezone", results.state, results.message, silent);
	},

	
	calculateAge: function(year, month, day)
	{
		var current = new Date();
		var current_year = current.getFullYear();
		var current_month = current.getMonth() + 1;
		var current_day = current.getDate();
		var age = current_year - year;
		
		if (month > current_month || (month == current_month && day > current_day))
		{
			age = age - 1;
		}
		
		return age;
	},
	
	
	failIfNotValid: function(element)
	{
		if(!Validation.allStatesAreValid())
		{
			var form = YAHOO.util.Dom.getAncestorByTagName(element, "form");
			form.submit();

			return false;
		}
		else
		{
			return true;
		}
	},
	
	
	updateValidationStates: function()
	{
		AccountValidation.validateUsername(true);
		AccountValidation.validatePassword(true);
		AccountValidation.validateEmail(true);
		AccountValidation.validateEmailConfirm(true);
		AccountValidation.validateDOB(true);
		AccountValidation.validateSex(true);
	}
}