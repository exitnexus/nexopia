lib_require :Core, 'validation/display', 'validation/set', 'validation/results', 'validation/rules', 'validation/chain'
lib_require :Core, 'validation/rule', 'validation/value_accessor'

module AccountcreateValidationHelper
	def _validate_username(username)
		chain = Validation::Chain.new;
		chain.add(Validation::Rules::CheckNotEmpty.new(Validation::ValueAccessor.new("username", username)));
		chain.add(Validation::Rules::CheckLegalUsername.new(Validation::ValueAccessor.new("username", username)));
		chain.add(Validation::Rules::CheckUsernameAvailable.new(Validation::ValueAccessor.new("username", username)));

		chain.set_none_override(Validation::Rules::ClearOnNil.new(Validation::ValueAccessor.new("username", username)));

		return chain;
	end


	def _validate_email(email, email_confirm)
		chain = Validation::Chain.new;
		chain.add(Validation::Rules::CheckNotEmpty.new(Validation::ValueAccessor.new("email", email)));
		chain.add(Validation::Rules::CheckEmailSyntax.new(Validation::ValueAccessor.new("email", email)));
		chain.add(Validation::Rules::CheckEmailSupported.new(Validation::ValueAccessor.new("email", email)));
		chain.add(Validation::Rules::CheckLength.new(Validation::ValueAccessor.new("email", email), "E-mail", 0, 100));
		chain.add(Validation::Rules::CheckEmailAvailable.new(Validation::ValueAccessor.new("email", email)));

		chain.set_none_override(Validation::Rules::ClearOnNil.new(Validation::ValueAccessor.new("email", email)));

		return chain;
	end


	def _validate_email_confirm(email, email_confirm)
		chain = Validation::Chain.new;

		chain.add(Validation::Rules::CheckNotEmpty.new(Validation::ValueAccessor.new("email", email)));
		chain.add(Validation::Rules::CheckLength.new(Validation::ValueAccessor.new("email", email), "Retype E-mail", 0, 100));
		chain.add(Validation::Rules::CheckRetypeValueMatches.new(
			Validation::ValueAccessor.new("email", email),
			Validation::ValueAccessor.new("email_confirm", email_confirm), "E-mail"));

		chain.set_none_override(Validation::Rules::ClearOnNil.new(Validation::ValueAccessor.new("email_confirm", email_confirm)));

		return chain;
	end


	def _validate_password(password, password_confirm, username)
		chain = Validation::Chain.new;

		chain.add(Validation::Rules::CheckNotEmpty.new(Validation::ValueAccessor.new("password", password)));
		chain.add(Validation::Rules::CheckLength.new(Validation::ValueAccessor.new("password", password), "Password", 4, 32));
		chain.add(Validation::Rules::CheckPasswordStrength.new(
			Validation::ValueAccessor.new("password", password),
			Validation::ValueAccessor.new("username", username)));

		chain.set_none_override(Validation::Rules::ClearOnNil.new(Validation::ValueAccessor.new("password", password)));

		return chain;
	end


	def _validate_password_confirm(password, password_confirm)
		chain = Validation::Chain.new;

		chain.add(Validation::Rules::CheckNotEmpty.new(Validation::ValueAccessor.new("password_confirm", password_confirm)));
		chain.add(Validation::Rules::CheckRetypeValueMatches.new(
			Validation::ValueAccessor.new("password", password),
			Validation::ValueAccessor.new("password_confirm", password_confirm), "Password"));
		chain.add(Validation::Rules::CheckLength.new(Validation::ValueAccessor.new("password_confirm", password_confirm), "Retype Password", 0, 32));

		chain.set_none_override(Validation::Rules::ClearOnNil.new(Validation::ValueAccessor.new("password", password)));

		return chain;
	end


	def _validate_dob(year, month, day)
		chain = Validation::Chain.new;

		chain.add(Validation::Rules::CheckDateOfBirth.new(
			Validation::ValueAccessor.new("year", year),
			Validation::ValueAccessor.new("month", month),
			Validation::ValueAccessor.new("day", day)));

		return chain;
	end


	def _validate_location(location)
		chain = Validation::Chain.new;

		chain.add(Validation::Rules::CheckLocation.new(Validation::ValueAccessor.new("location", location)));

		return chain;
	end


	def _validate_sex(sex)
		chain = Validation::Chain.new;

		chain.add(Validation::Rules::CheckSex.new(Validation::ValueAccessor.new("sex", sex)));

		return chain;
	end


	def _validate_timezone(timezone)
		chain = Validation::Chain.new;

		chain.add(Validation::Rules::CheckTimezone.new(Validation::ValueAccessor.new("timezone", timezone)));

		return chain;
	end


	def _validate_general(request, general_error)
		chain = Validation::Chain.new;

		if (general_error.nil?)
			chain.add(Validation::Rules::CheckIPNotBanned.new(Validation::ValueAccessor.new("ip", request.get_ip_as_int)));
			chain.add(Validation::Rules::CheckEmailNotBanned.new(
				Validation::ValueAccessor.new("email", request.params["email", String, nil])));

			return chain;
		else
			chain.add(Validation::Rules::PassResults.new(Validation::Results.new(:error, general_error)));

			return chain;
		end
	end
	
	def _validate_my_name(name)
		chain = Validation::Chain.new;

		chain.add(Validation::Rules::CheckNotEmpty.new(Validation::ValueAccessor.new("name", name)));

		chain.set_none_override(Validation::Rules::ClearOnNil.new(Validation::ValueAccessor.new("name", name)));

		return chain;
	end

	#This will be removed as it is being replaced in friend of friends
	def _validate_friend(name, email)
		chain = Validation::Chain.new;
		chain.add(Validation::Rules::CheckNotEmpty.new(
			Validation::ValueAccessor.new("email", email),
			Validation::ValueAccessor.new("name", name), "Email"));
		chain.add(Validation::Rules::CheckEmailSyntax.new(
			Validation::ValueAccessor.new("email", email)));
		chain.add(Validation::Rules::CheckLength.new(
			Validation::ValueAccessor.new("email", email), "E-mail", 0, 100));
		chain.add(Validation::Rules::CheckNotEmpty.new(
			Validation::ValueAccessor.new("name", name),
			Validation::ValueAccessor.new("email", email), "Name"));

		chain.set_valid_override(Validation::Rules::ClearOnNilOrEmpty.new(
			Validation::ValueAccessor.new("email + name", email + name), :valid, :unknown));

		return chain;
	end
end