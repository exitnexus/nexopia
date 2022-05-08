ValidationRules = 
{
	ClearOnNil: function (value_accessors, static_values)
	{
		this.value = value_accessors[0];
		this.nil_state = static_values[0] || "none";
		this.filled_state = static_values[1] || "valid";
		this.className = "ClearOnNil";
	},

	ClearOnNilOrEmpty: function(value_accessors, static_values)
	{
		this.value = value_accessors[0];
		this.nil_or_empty_state = static_values[0] || "none";
		this.filled_state = static_values[1] || "valid";
		this.className = "ClearOnNilOrEmpty";
	},

	CheckLength: function(value_accessors, static_values)
	{
		this.value = value_accessors[0];
		this.field_name = static_values[0];
		this.min_length = static_values[1];
		this.max_length = static_values[2];
		this.className = "CheckLength";
	},

	CheckRetypeValueMatches: function(value_accessors, static_values)
	{
		this.value = value_accessors[0];
		this.retype_value = value_accessors[1];
		this.field_name = static_values[0];
		this.className = "CheckRetypeValueMatches";
	},

	CheckUsernameAvailable: function(username)
	{
		this.username = username;
		this.className = "CheckUsernameAvailable";
	},

	CheckEmailAvailable: function(email)
	{
		this.email = email;
		this.className = "CheckEmailAvailable";
	},

	CheckNotEmpty: function(value_accessors, static_values)
	{
		this.text = value_accessors[0];
		this.if_filled = static_values[0];
		this.identifier = static_values[1];
		this.className = "CheckNotEmpty";
	},


	CheckNotEqualTo: function(value_accessors, static_values)
	{
		this.value = value_accessors[0];
		this.check_value = static_values[0];
		this.error_msg = static_values[1];
		this.className = "CheckNotEqualTo";
	},
	
	
	CheckChecked: function(value_accessors, static_values)
	{
		this.value = value_accessors[0];
		this.className = "CheckChecked";
	},


	// TODO: Fix this... how to get the same character conversion as 127.chr in javascript?
	// Or should this just go into a server-side AJAX call?
	// Checks the given text for any illegal characters
	CheckIllegalCharacters: function(text)
	{
		this.text = text;
		this.className = "CheckIllegalCharacters";
	},

	CheckAlphaCharactersExist: function(value_accessors, static_values)
	{
		this.text = value_accessors[0];
		this.className = "CheckAlphaCharactersExist";
	},

	CheckLegalUsername: function(username)
	{
		this.username = username;
		this.className = "CheckLegalUsername";
	},

	CheckNoBannedWords: function(text)
	{
		this.text = text;
		this.className = "CheckNoBannedWords";
	},

	// Checks that the password is longer than 4 characters		
	CheckPasswordLength: function(value_accessors, static_values)
	{
		this.password = value_accessors[0];
		this.className = "CheckPasswordLength";
	},

	// Makes sure that the email address is of proper syntax
	CheckEmailSyntax: function(value_accessors, static_values)
	{
		this.email = value_accessors[0];
		this.className = "CheckEmailSyntax";
	},

	CheckEmailSupported: function(value_accessors, static_values)
	{
		this.email = value_accessors[0];
		this.className = "CheckEmailSupported";
	},

	// Checks that the email has been deleted for at least a week
	CheckEmailSufficientlyDead: function(email)
	{
		this.email = email;
		this.className = "CheckEmailSufficientlyDead";
	},

	// Checks that the email has not been banned
	CheckEmailNotBanned: function(email)
	{
		this.email = email;
		this.className = "CheckEmailNotBanned";
	},

	// Checks that the ip has not been banned
	CheckIPNotBanned: function(ip)
	{
		this.ip = ip;
		this.className = "CheckIPNotBanned";
	},

	CheckNotBanned: function(identifier, type)
	{
		this.identifier = identifier;
		this.type = type;
		this.className = "CheckNotBanned";
	},

	CheckTerms: function(dob, check_hash)
	{
		this.check_hash = check_hash;
		this.dob = dob;
		this.className = "CheckTerms";
	},

	CheckDateOfBirth: function(value_accessors, static_values)
	{
		this.year = value_accessors[0];
		this.month = value_accessors[1];
		this.day = value_accessors[2];
		this.className = "CheckDateOfBirth";
	},

	CheckLocation: function(value_accessors, static_values)
	{
		this.location = value_accessors[0];
		this.className = "CheckLocation";
	},

	CheckSex: function(value_accessors, static_values)
	{
		this.sex = value_accessors[0];
		this.className = "CheckSex";
	},

	CheckTimezone: function(value_accessors, static_values)
	{
		this.timezone = value_accessors[0];
		this.className = "CheckTimezone";
	},

	CheckPasswordStrength: function(value_accessors, static_values)
	{
		this.password = value_accessors[0];
		this.username = value_accessors[1];
		this.className = "CheckPasswordStrength";
	}
};


ValidationRules.ClearOnNil.prototype = 
{
	validate: function()
	{
		if (this.value.value() == null)
		{
			return new ValidationResults(this.nil_state, "");
		}
		else
		{
			return new ValidationResults(this.filled_state, "");
		}
	},


	isServerSide: function()
	{
		return false;
	}
};


ValidationRules.ClearOnNilOrEmpty.prototype = 
{
	validate: function()
	{
		if (this.value.value() == null || this.value == "")
		{
			return new ValidationResults(this.nil_or_empty_state, "");
		}
		else
		{
			return new ValidationResults(this.filled_state, "");
		}
	},


	isServerSide: function()
	{
		return false;
	}
};


ValidationRules.CheckLength.prototype =
{
	validate: function()
	{				
		if (this.value.value().length < this.min_length)
		{
			return new ValidationResults("error", this.field_name + " must be at least " + this.min_length + " characters long.");
		}
		else if (this.value.value().length > this.max_length)
		{
			return new ValidationResults("error", this.field_name + " must be shorter than " + this.max_length + " characters.");
		}
		else
		{
			return new ValidationResults("valid", "");
		}
	},


	isServerSide: function()
	{
		return false;
	}
};


ValidationRules.CheckRetypeValueMatches.prototype =
{
	validate: function()
	{
		if (this.value.value() != this.retype_value.value())
		{	
			return new ValidationResults("error", this.field_name + " and Retype " + this.field_name + " do not match");
		}
		else
		{
			return new ValidationResults("valid", "");
		}
	},


	isServerSide: function()
	{
		return false;
	}
};

ValidationRules.CheckEmailAvailable.prototype =
{
	validate: function()
	{
		/* Would have to do an AJAX call here. */
	},


	isServerSide: function()
	{
		return true;
	}
};

ValidationRules.CheckUsernameAvailable.prototype =
{
	validate: function()
	{
		/* Would have to do an AJAX call here. */
	},


	isServerSide: function()
	{
		return true;
	}
};

ValidationRules.CheckNotEmpty.prototype =
{
	validate: function()
	{
		if (this.text.value() == "" && (this.if_filled == null || this.if_filled == ""))
		{
			text = "Cannot be blank";
			if (this.identifier != null)
			{
				text = this.identifier + " cannot be blank";
			}
		
			return new ValidationResults("error", text);
		}
		else
		{
			return new ValidationResults("valid", "");
		}
	},


	isServerSide: function()
	{
		return false;
	}	
};


ValidationRules.CheckNotEqualTo.prototype =
{
	validate: function()
	{
		if (this.value.value() == this.check_value)
		{
			return new ValidationResults("error", this.error_msg);
		}
		else
		{
			return new ValidationResults("valid", "");
		}
	},


	isServerSide: function()
	{
		return false;
	}	
};


ValidationRules.CheckChecked.prototype =
{
	validate: function()
	{
		if (this.value.value() == null || !this.value.value())
		{
			return new ValidationResults("error", "");
		}
		else
		{
			return new ValidationResults("valid", "");
		}
	},


	isServerSide: function()
	{
		return false;
	}	
};


ValidationRules.CheckEmailAvailable.prototype =
{
	validate: function()
	{
		/* Would have to do an AJAX call here. */
	},


	isServerSide: function()
	{
		return true;
	}
};

ValidationRules.CheckIllegalCharacters.prototype = 
{
	validate: function()
	{/*
		chars = new Array(' ','<','>','&','%','"',"'",'`','+','=','@',127.chr,129.chr,152.chr,158.chr,160.chr,'/','\\');
		(0...40).each { |i| chars << i.chr };
		(166..223).each { |i| chars << i.chr };
		(240..255).each { |i| chars << i.chr };

		r = Regexp.new("[" + chars.to_s + "]");
		m = this.text.match(r);
		if (m == null)
		{
			return new ValidationResults("valid", "");
		}
		else
		{
			return new ValidationResults("error", "Illegal character: " + m.to_s);
		}
		*/
	},


	isServerSide: function()
	{
		return true;
	}
};

ValidationRules.CheckAlphaCharactersExist.prototype = 
{
	validate: function()
	{
		m = this.text.value().match(/[^\d]/);

		if (m == null)
		{
			return new ValidationResults("error", "Must have letters.");
		}
		else
		{
			return new ValidationResults("valid", "");
		}
	},


	isServerSide: function()
	{
		return false;
	}
};

ValidationRules.CheckLegalUsername.prototype = 
{
	validate: function()
	{
		/* TODO: Do this in an AJAX call. */
	},


	isServerSide: function()
	{
		return true;
	}
};

ValidationRules.CheckNoBannedWords.prototype = 
{
	validate: function()
	{
		/* TODO: Do this in an AJAX call. */
	},


	isServerSide: function()
	{
		return true;
	}
};

ValidationRules.CheckPasswordLength.prototype = 
{	
	validate: function()
	{
		if (this.password.value().length < 4)
		{
			return new ValidationResults("error", "Password must be at least 4 characters long.");
		}
		else if (this.password.value().length > 32)
		{
			return new ValidationResults("error", "Password cannot be longer than 32 characters.");
		}
		else
		{
			return new ValidationResults("valid", "");
		}
	},


	isServerSide: function()
	{
		return false;
	}
};

ValidationRules.CheckEmailSupported.prototype =
{
	validate: function()
	{
		not_supported = /.*@((?:mailinator|dodgeit)\.com)$/;

		if (this.email.value().match(not_supported) != null)
		{
			holder = this.email.value().replace(not_supported, "$1");
			return new ValidationResults("error", holder + " cannot currently be used with Nexopia.");
		}

		return new ValidationResults("valid", "");
	},


	isServerSide: function()
	{
		return false;
	}
};


ValidationRules.CheckEmailSufficientlyDead.prototype = 
{
	validate: function()
	{
		// TODO: Do as AJAX call
	},


	isServerSide: function()
	{
		return true;
	}
};

ValidationRules.CheckEmailNotBanned.prototype =
{
	validate: function()
	{
		// TODO: Do as an AJAX call
		return new CheckNotBanned(this.email.value(), "E-mail").validate();
	},


	isServerSide: function()
	{
		return true;
	}
};

ValidationRules.CheckEmailSyntax.prototype =
{	
	validate: function()
	{
		// TODO: AJAX Call!!!
	},


	isServerSide: function()
	{
		return true;
	}
};

ValidationRules.CheckIPNotBanned.prototype =
{
	validate: function()
	{
		// TODO: Do as an AJAX call
		return new CheckNotBanned(this.ip.value(), "IP").validate();
	},


	isServerSide: function()
	{
		return true;
	}
};

ValidationRules.CheckNotBanned.prototype =
{	
	validate: function()
	{
		// TODO: Do as an AJAX call
	},


	isServerSide: function()
	{
		return true;
	}
};

ValidationRules.CheckTerms.prototype =
{
	validate: function()
	{
		// TODO: Make this an AJAX call
	},


	isServerSide: function()
	{
		return true;
	}
};

ValidationRules.CheckDateOfBirth.prototype =
{
	validate: function()
	{
		if (this.year.value() == null && this.month.value() == null && this.day.value() == null)
		{
			return new ValidationResults("none","");
		}
	
		if (this.year.value() == -1 || this.month.value() == -1 || this.day.value() == -1)
		{
			return new ValidationResults("error", "Invalid Date of Birth");
		}
		else
		{
			current = new Date();
			current_year = current.getFullYear();
			current_month = current.getMonth() + 1;
			current_day = current.getDate();
			age = current_year - this.year.value();

			if (this.month.value() > current_month || (this.month.value() == current_month && this.day.value() > current_day))
			{
				age = age - 1;
			}
		
			if (age < 14)
			{
				return new ValidationResults("error", "Must be 14 or over to join");
			}
			else
			{
				return new ValidationResults("valid", "");
			}
		}
	},


	isServerSide: function()
	{
		return false;
	}
};

ValidationRules.CheckLocation.prototype = 
{
	validate: function()
	{
		if (this.location.value() == null)
		{
			return new ValidationResults("none","");
		}

		if (this.location.value() == 0)
		{
			return new ValidationResults("error", "Must select a valid Location");
		}
		else
		{
			return new ValidationResults("valid", "");
		}			
	},


	isServerSide: function()
	{
		return false;
	}
};

ValidationRules.CheckSex.prototype =
{
	validate: function()
	{
		if (this.sex.value() == null)
		{
			return new ValidationResults("none","");
		}

		if (this.sex.value() == "")
		{
			return new ValidationResults("error", "Must select Male or Female");
		}
		else
		{
			return new ValidationResults("valid", "");
		}
	},


	isServerSide: function()
	{
		return false;
	}
};

ValidationRules.CheckTimezone.prototype =
{
	validate: function()
	{
		if (this.timezone.value() == null)
		{
			return new ValidationResults("none","");
		}

		if (this.timezone.value() == 0)
		{
			return new ValidationResults("error", "Must select a Timezone");
		}
		else
		{
			return new ValidationResults("valid", "");
		}
	},


	isServerSide: function()
	{
		return false;
	}
};

ValidationRules.CheckPasswordStrength.prototype =
{
	validate: function()
	{
		weak = false;
		if ( (!this.username.value() == null && this.username.value() != "" && !this.password.value().index(this.username.value()) == null) ||
			this.password.value() == "secret" ||
			this.password.value().match(/^[a-z]*$/) != null || 				// Warn if all lowercase letters
			this.password.value().match(/^[A-Z]*$/) != null ||				// Warn if all uppercase letters
			this.password.value().match(/^[a-zA-Z][a-z]*$/) != null)		// Warn if only first is uppercase and the rest are lowercase
		{
			weak = true;
		}

		if (weak)
		{
			return new ValidationResults("warning", "Password is not very strong");
		}
		else
		{
			return new ValidationResults("valid", "");
		}
	},


	isServerSide: function()
	{
		return false;
	}
};