lib_require :core, 'banned_words', 'banned_users', 'users/deleted_user', 'users/user';
lib_require :core, 'validation/results', 'validation/rule', 'validation/value_accessor';

require 'resolv'

module Validation	
	class Rules

		YEAR = 60 * 60 * 24 * 365.2425;
			

		class ClearOnNil < Rule
			def initialize(value, nil_state=:none, filled_state=:valid)
				super([value], [nil_state, filled_state]);
				
				@value = value.value;
				@nil_state = nil_state;
				@filled_state = filled_state;
			end
		
		
			def validate
				if (@value.nil?)
					return Results.new(@nil_state, "");
				else
					return Results.new(@filled_state, "");
				end
			end
		end
	

		class ClearOnNilOrEmpty < Rule
			def initialize(value, nil_or_empty_state=:none, filled_state=:valid)
				super([value], [nil_or_empty_state, filled_state]);
				
				@value = value.value;
				@nil_or_empty_state = nil_or_empty_state;
				@filled_state = filled_state;
			end
		
		
			def validate
				if (@value.nil? || @value == "")
					return Results.new(@nil_or_empty_state, "");
				else
					return Results.new(@filled_state, "");
				end
			end
		end
	

		class CheckLength < Rule
			def initialize(value, field_name, min_length, max_length)
				super([value], [field_name, min_length, max_length]);
				
				@value = value.value;
				@field_name = field_name;
				@min_length = min_length;
				@max_length = max_length;
			end
		
		
			def validate				
				if (@value.to_s.length < @min_length)
					return Results.new(:error, "#{@field_name} must be at least #{@min_length} characters long.");
				elsif (@value.to_s.length > @max_length)
					return Results.new(:error, "#{@field_name} must be shorter than #{@max_length} characters.");
				else
					return Results.new(:valid, "");
				end
			end
		end


		class CheckRetypeValueMatches < Rule
			def initialize(value, retype_value, field_name)
				super([value, retype_value], [field_name]);
				
				@value = value.value;
				@retype_value = retype_value.value;
				@field_name = field_name;
			end
		
		
			def validate
				if (@value != @retype_value)
					return Results.new(:error, "Did not match the first #{@field_name}");
				else
					return Results.new(:valid, "");
				end
			end
		end

	
		class CheckUsernameAvailable < Rule
			def initialize(username)
				super([username]);
				
				@username = username.value;
			end
		
		
			def validate
				record = UserName.by_name(@username);
			
				if (record.nil?)
					return Results.new(:valid, "Available");
				else
					return Results.new(:error, "Username already in use");
				end
			end
		end


		class CheckEmailAvailable < Rule
			def initialize(email)
				super([email]);
				
				@email = email.value;
			end
		
		
			def validate
				record = UserEmail.by_email(@email);
				if (record.nil?)
					return Results.new(:valid, "");
				else
					return Results.new(:error, "E-mail already in use");
				end
			end
		end


		class CheckNotEmpty < Rule
			def initialize(text, if_filled=nil, identifier=nil)
				super([text], [if_filled, identifier]);
				
				@text = text.value;
				@if_filled = if_filled;
				@identifier = identifier;
			end
		
		
			def validate
				if (@text == "" && (@if_filled.nil? || @if_filled == ""))
					text = "Cannot be blank";
					if (!@identifier.nil?)
						text = "#{@identifier} cannot be blank";
					end
					
					return Results.new(:error, text);
				else
					return Results.new(:valid);
				end
			end
		end
	
	
		class CheckNotEqualTo < Rule
			def initialize(value, check_value, error_msg)
				super([value], [check_value, error_msg]);
				
				@value = value.value;
				@check_value = check_value;
				@error_msg = error_msg;
			end
		
		
			def validate
				if (@value == @check_value)
					return Results.new(:error, @error_msg);
				else
					return Results.new(:valid);
				end
			end
		end
		
		
		class CheckEqualTo < Rule
			def initialize(value, check_value, error_msg)
				super([value], [check_value, error_msg]);
				
				@value = value.value;
				@check_value = check_value;
				@error_msg = error_msg;
			end
		
		
			def validate
				if (@value == @check_value)
					return Results.new(:valid);
				else
					return Results.new(:error, @error_msg);
				end
			end
		end


		class CheckChecked < Rule
			def initialize(checked)
				super([checked], []);
				
				@checked = checked.value;
			end
		
		
			def validate
				if (@checked.nil? || @checked == false)
					return Results.new(:error);
				else
					return Results.new(:valid);
				end
			end
		end	
	
	
		# Checks the given text for any illegal characters
		class CheckIllegalCharacters < Rule
			def initialize(text)
				super([text]);
				
				@text = text.value;
			end
		
			def validate
				r = /[^a-zA-Z0-9~\^\*\-\\|\]\}\[\{\.]/
				# The regular expression above contains all the characters that we allow and will match any text that
				# contains a character that we don't allow. Thus, if the match returns nil, we have found no illegal
				# characters. Take special note of the ^ at the beginning of the character set. This is a special
				# regexp character that negates all the characters that follow.
				m = @text.match(r);
				if (m.nil?)
					return Results.new(:valid, "");
				else
					return Results.new(:error, "Illegal character: #{m.to_s}");
				end
			end
		end
	
	
		class CheckNoSpaces < Rule
			def initialize(text)
				super([text]);
				
				@text = text.value;
			end
		
		
			def validate
				m = @text.match(/\s/)
			
				if (m.nil?)
					return Results.new(:valid, "");
				else
					return Results.new(:error, "No spaces allowed");
				end
			end
		end
		
		
		class CheckAlphaCharactersExist < Rule
			def initialize(text)
				super([text]);
				
				@text = text.value;
			end
		
		
			def validate
				m = @text.match(/[^\d]/)
			
				if (m.nil?)
					return Results.new(:error, "Must have letters.");
				else
					return Results.new(:valid, "");
				end
			end
		end
		
	
		class CheckLegalUsername < Rule
			def initialize(username)
				super([username]);
				
				@username = username.value;
				@username_accessor = username;
			end


			def validate
				chain = Chain.new;
				chain.add(CheckNoSpaces.new(@username_accessor));
				chain.add(CheckIllegalCharacters.new(@username_accessor));
				chain.add(CheckAlphaCharactersExist.new(@username_accessor));
				chain.add(CheckNoBannedWords.new(@username_accessor));
			
				results = chain.validate;
				if (results.state == :error)
					return results;
				end

				if (@username.length < $site.config.min_username_length)
					return Results.new(:error, "Username too short.");
				elsif (@username.length > $site.config.max_username_length)
					return Results.new(:valid, "Username too long.");
				end

				return Results.new(:valid, "");
			end
		end
		
	
		class CheckNoBannedWords < Rule
			def initialize(text)
				super([text]);
				
				@text = text.value;
			end
		
		
			def validate
				banned_words = BannedWords.all_banned_words;
				banned_words.each { |word|
					if (word.type == 'word' || word.type == 'name')
						if (word.word.downcase == @text.downcase)
							return Results.new(:error, "#{word.word} isn't allowed.");
						end
					elsif (word.type == 'part')
						if (! @text.downcase.index(word.word.downcase).nil?)
							return Results.new(:error, "#{word.word} isn't allowed.");
						end
					end
				};

				return Results.new(:valid, "");
			end
		end


		# Checks that the password is longer than 4 characters		
		class CheckPasswordLength < Rule
			def initialize(password)
				super([password]);
				
				@password = password.value;
			end
		
		
			def validate
				if (@password.length < 4)
					return Results.new(:error, "4 characters minimum");
				elsif (@password.length > 32)
					return Results.new(:error, "32 characters maximum");
				else
					return Results.new(:valid, "");
				end
			end
		end
		
	
		# Makes sure that the email address is of proper syntax
		class CheckEmailSyntax < Rule
			def initialize(email)
				super([email]);
				
				@email = email.value;
			end
		
		
			def validate
				email_check_regex = /^[a-z0-9]+([a-z0-9_.+&-]+)*@([a-z0-9.-]+)+\.([a-z0-9.-]+)+$/;
				
				if ( !(@email.downcase =~ email_check_regex) )
					return Results.new(:error, "Not a valid mail address");
				elsif (! domain_exists?(@email))
					host = @email.gsub(email_check_regex, '\2');
				
					return Results.new(:error, "Can't find the host '#{host}'");
				end
			
				return Results.new(:valid, "");			
			end
		
		
			def domain_exists?(email)
				domain = email.match(/\@(.+)/)[1]
				Resolv::DNS.open do |dns|
					@mx = dns.getresources(domain, Resolv::DNS::Resource::IN::MX)
				end
				exists = @mx.size > 0 ? true : false
				
				return exists;
			end
		end

	
		class CheckEmailSupported < Rule
			def initialize(email)
				super([email]);
				
				@email = email.value;
			end
		
		
			def validate
				not_supported = /.*@((?:mailinator|dodgeit)\.com)$/;
			
				if (@email =~ not_supported)
					holder = @email.gsub(not_supported, '\1');
					return Results.new(:error, "#{holder} cannot currently be used with Nexopia.");
				end
			
				return Results.new(:valid, "");
			end
		end
	
	
		# Checks that the email has been deleted for at least a week
		class CheckEmailSufficientlyDead < Rule
			def initialize(email)
				super([email]);
				
				@email = email.value;
			end
		
		
			def validate
				deleted_user = DeletedUser.find(:first, :conditions => ['email = ? AND jointime > ?', @email, 86400*7]);

				if (deleted_user.nil?)
					return Results.new(:valid, "");
				else
					return Results.new(:error, "This email was used to create an account this week, and can't be " + 
						"used again until that period is over.");
				end
			end
		end
		
	
		class CheckNotBanned < Rule
			def initialize(identifier, type)
				super([identifier], [type]);
				
				@identifier = identifier.value;
				@type = type;
			end
		
		
			def validate
				banned = BannedUsers.find(:first, "#{@identifier.to_s}");
				
				if (banned.nil?)
					return Results.new(:valid, "");
				else
					return Results.new(:error, "Your #{@type} has been banned due to abuse. Please " +
						"<a class=\"body\" href=\"/contactus.php\">contact us</a> if you need details.");
				end
			end
		end
	
	
		# Checks that the email has not been banned
		class CheckEmailNotBanned < CheckNotBanned
			def initialize(email)
				super(email, "E-mail");
				
				@email = email.value;
			end
		end
	
		
		# Checks that the ip has not been banned
		class CheckIPNotBanned < CheckNotBanned
			def initialize(ip)
				super(ip, "IP");
				
				@ip = ip.value;
			end
		end
		
	
		class CheckTerms < Rule
			def initialize(dob, check_hash)
				super([dob, check_hash]);
				
				@check_hash = check_hash.value;
				@dob = dob.value;			
			end
		
		
			def validate
				if (@check_hash[:agree])
					valid = false;
					age = _calculate_age(@dob);

					if (@check_hash[:over18] && !@check_hash[:consent] && !@check_hash[:over14])
						valid = true if age >= 18;
					elsif (@check_hash[:over14] && @check_hash[:consent] && !@check_hash[:over18])
						valid = true if age >= 14 && age < 18;
					end
				
					if (valid)
						return Results.new(:valid, "");
					end
				end
			
				return Results.new(:error, "You must read and agree to the Terms and Conditions");
			end
		
		
			def _calculate_age(dob)
				age_in_seconds = Time.now.to_i - dob;
				years = (age_in_seconds / Constants::YEAR).to_i;
			
				return years;
			end
		end
	
	
		class CheckDateOfBirth < Rule
			def initialize(year, month, day)
				super([year, month, day]);
				
				@year = year.value;
				@month = month.value;
				@day = day.value;
			end
		
		
			def validate
								
				if (@year.nil? && @month.nil? && @day.nil?)
					return Results.new(:none,"");
				end

				if (@year == -1 || @month == -1 || @day == -1)
					return Results.new(:error, "Invalid Date of Birth");
				else
					dob = Time.local(@year, @month, @day);
					if (dob.day != @day)
						return Results.new(:error, "Invalid Date of Birth");
					end
					
					current = Time.now;
					current_year = current.year;
					current_month = current.month;
					current_day = current.day;
					age = current_year - @year;

					if (@month > current_month || (@month == current_month && @day > current_day))
						age = age - 1;
					end

					if(age < 13)
						return Results.new(:error, "Must be 13 or over to join");
					else
						return Results.new(:valid, "");
					end
				end
			end
		end
	
	
		class CheckLocation < Rule
			def initialize(location)
				super([location]);
				
				@location = location.value;
			end
		
		
			def validate
				
				if (@location.nil?)
					return Results.new(:none,"");
				end

				if (@location == 0)
					return Results.new(:error, "Must select a valid Location");
				else
					return Results.new(:valid, "");
				end			
			end
		end
	
	
		class CheckSex < Rule
			def initialize(sex)
				super([sex]);
				@sex = sex.value;
			end
		
		
			def validate
				if (@sex.nil?)
					return Results.new(:none,"");
				end

				if (@sex == "")
					return Results.new(:error, "Must select Male or Female");
				else
					return Results.new(:valid, "");
				end			
			end
		end
		

		class CheckTimezone < Rule
			def initialize(timezone)
				super([timezone]);
				@timezone = timezone.value;
			end
		
		
			def validate
				if (@timezone.nil?)
					return Results.new(:none,"");
				end

				if (@timezone == 0)
					return Results.new(:error, "Must select a Timezone");
				else
					return Results.new(:valid, "");
				end			
			end
		end
		



		class CheckPasswordStrength < Rule
			def initialize(password, username)
				super([password,username]);
				@password = password.value;
				@username = username.value;
			end
		
		
			def validate
				weak = false;
				if ( (!@username.nil? && @username != "" && !@password.to_s.index(@username.to_s).nil?) || @password == "secret" || @password == "password")	
					return Results.new(:warning, "Weak");
				elsif(
					@password =~ /^[a-z]*$/ || 				# Warn if all lowercase letters
					@password =~ /^[A-Z]*$/ ||				# Warn if all uppercase letters
					@password =~ /^[a-zA-Z][a-z]*$/ ||
					@password =~ /^\d+$/) 	# Warn if only first is uppercase and the rest are lowercase
					return Results.new(:warning, "Medium");
				else
					return Results.new(:valid, "Strong");
				end
			end
		end


		class CheckCaptcha < Rule
			def initialize(ip, challenge, response)
				super([ip, challenge, response]);
				
				@ip = ip;
				@challenge = challenge;
				@response = response;
			end
			
			
			def validate
				errors = [];
				def errors.add_to_base(str)
					push(str)
				end
				
				valid = $site.captcha.validate(@ip.value, @challenge.value, @response.value, errors);
					
				if (valid)
					return Results.new(:valid, "");
				else
					return Results.new(:error, "You must type in the words shown in the picture below.");
				end
			end
		end


		class PassResults < Rule
			def initialize(results)
				super([],[results]);
				@results = results;
			end
		
		
			def validate
				return @results;
			end
		end

	end
end
