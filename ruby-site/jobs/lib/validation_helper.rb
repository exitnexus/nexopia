lib_require :Core, "validation/display", "validation/set", "validation/results", "validation/rules", "validation/chain", "validation/rule", "validation/value_accessor";
lib_require :Jobs, 'validation_rules';

module JobsValidationHelper
	
	def setup_applicant_validation(request)
		params = request.params;
		
		app_name = params['app_name', String, nil];
		app_email = params['app_email', String, nil];
		app_phone = params['app_phone', String, nil];
		app_contact_type = params['app_contact_type', String, nil];
		app_contact_details = params['app_contact_details', String, nil];
		app_resume = params['resume_file_name', String, nil];
		app_cover_letter = params['cover_letter_file_name', String, nil];
		
		val_set = Validation::Set.new();
		val_set.add("app_name", validate_name(app_name));
		val_set.add("app_email", validate_email(app_email));
		val_set.add("app_phone", validate_phone_number(app_phone));
		val_set.add("app_contact_type", validate_contact_type(app_contact_type));
		val_set.add("app_contact_notes", validate_contact_notes(app_contact_notes));
		
		return val_set;
	end
	
	def validate_name(name)
		chain = Chain.new();
		
		chain.add(Rules::CheckNotEmpty.new(ValueAccessor.new("app_name", name)));
		chain.add(Rules::CheckLength.new(ValueAccessor.new("app_name", name), 3, 64));
		
		return chain;
	end
	
	def validate_email(email)
		chain = Chain.new();
		
		chain.add(Rules::CheckNotEmpty.new(ValueAccessor.new("app_email", email)));
		chain.add(Rules::CheckEmailSyntax.new(ValueAccessor.new("app_email", email)));
		chain.add(Rules::CheckEmailSupported.new(ValueAccessor.new("app_email", email)));
		chain.add(Rules::CheckLength.new(ValueAccessor.new("app_email", email), "E-mail", 0, 64));
		chain.add(Jobs::Rules::CheckEmailAvailable(ValueAccessor.new("app_email", email)));
		
		return chain;
	end
	
	def validate_phone_number(phone)
		chain = Chain.new();
		
		chain.add(Rules::CheckNotEmpty.new(ValueAccessor.new("app_phone", phone)));
		chain.add(Rules::CheckLength.new(ValueAccessor.new("app_phone", phone), "Phone Number", 0, 14));
		chain.add(Jobs::Rules::CheckPhoneSyntax(ValueAccessor.new("app_phone", phone)));
		
		return chain;
	end
	
	def validate_contact_type(contact_type)
		chain = Chain.new();
		
		chain.add(Rules::CheckNotEmpty.new(ValueAccessor.new("app_contact_type", contact_type)));
		
		return chain;
	end
	
	def validate_contact_notes(contact_notes)
		chain = Chain.new();
		
		chain.add(Rules::CheckLength.new(ValueAccessor.new("app_contact_notes", contact_notes), 0, 128));
		
		return chain;
	end
end
