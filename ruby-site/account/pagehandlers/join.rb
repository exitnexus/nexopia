lib_require :Core, 'users/user', 'users/user_name', 'users/useremails', 'authorization'
lib_require :Friends, 'friend'
lib_require :Profile, 'profile', 'profile_block'
lib_require :Account, 'terms', 'invite_email', 'invite_optout', 'post_response'
lib_require :Messages, 'message'
lib_require :Core, 'validation/display', 'validation/set', 'validation/results', 'validation/rules', 'validation/chain'
lib_require :Core, 'validation/rule', 'validation/value_accessor'
lib_require :Core,  'user_error'
lib_require :Account, 'validation_helper'
lib_require :Wiki, "wiki"
lib_require :Account, "newest_user"
lib_want :Orwell, "send_email"
lib_want :Metrics, "category_user_sign_up", "incremental_metric_data"
lib_want :FriendFinder, "email_invite"

require 'net/http'
require 'uri'
require 'digest/md5'

module AccountManagement
	class CreateAccount < PageHandler

		include Profile;
		include AccountValidationHelper;

		declare_handlers("account") {
			# The three steps in account creation:
			# 1. Start: 	Minimal information is entered to create an account
			# 2. Accept: 	The user must accept the legal terms.
			# 3. Create: 	The terms are set on the account and the user is logged in and
			# 						sent an activation email.
			area :Public
			access_level :NotLoggedIn

			page :GetRequest, :Full, :join # Do the same as if we called /account/start
			page :GetRequest, :Full, :join, "join"
			page :PostRequest, :Full, :create_account, "create"
			page :GetRequest, :Full, :confirm_email, "confirm"

			# For getting fullscreen versions of terms and privacy as in the PHP site.
			page :GetRequest, :Full, :show_terms, "terms"
			page :GetRequest, :Full, :show_privacy, "privacy"

			# Opt out of invites
			page :GetRequest, :Full, :optout, "optout"
			page :GetRequest, :Full, :optout, "optout", input(String), input(String)
			page :PostRequest, :Full, :optout, "optout", "update"

			# Server-side AJAX handles
			handle :PostRequest, :check_username, "check_username"
			handle :PostRequest, :check_email, "check_email"

			access_level :Any

			# Activation and re-activation handlers
			page :GetRequest, :Full, :activate, "activate"
			page :GetRequest, :Full, :reactivate, "reactivate"
			page :GetRequest, :Full, :activation_success, "activation_success"
			page :GetRequest, :Full, :activation_failure, "activation_failure", input(String)

			area :Self
			access_level :LoggedIn

			# Confirmation: The user is told that the account has been created
			page :GetRequest, :Full, :create_confirm, "confirm"

			page	:GetRequest, :Full, :view_activate_step, "activate";
		
		}


		# For backwards compatibility with any messages sent using the old /accountcreate prefix.
		# This should be able to be eliminated a month or so after the changes go live.
		declare_handlers("accountcreate") {
			area :Public
			access_level :NotLoggedIn

			page :GetRequest, :Full, :join # Do the same as if we called /account/start
			page :GetRequest, :Full, :join, "join"
			page :PostRequest, :Full, :create_account, "create"

			page :GetRequest, :Full, :show_terms, "terms"
			page :GetRequest, :Full, :show_privacy, "privacy"

			page :GetRequest, :Full, :optout, "optout"
			page :GetRequest, :Full, :optout, "optout", input(String), input(String)
			page :PostRequest, :Full, :optout, "optout", "update"

			handle :PostRequest, :check_username, "check_username"
			handle :PostRequest, :check_email, "check_email"

			access_level :Any

			page :GetRequest, :Full, :activate, "activate"
			page :GetRequest, :Full, :reactivate, "reactivate"
			page :GetRequest, :Full, :activation_success, "activation_success"
			page :GetRequest, :Full, :activation_failure, "activation_failure", input(String)

			area :Self
			access_level :LoggedIn

			page :GetRequest, :Full, :create_confirm, "confirm"

			page	:GetRequest, :Full, :view_activate_step, "activate";		
		}


		def join(general_error=nil)
			request.reply.headers['X-width'] = 0;

			t = Template.instance("account", "create_start");
			t.inline = params.to_hash['inline']
			provided_email = params['email_addr', String, nil]
			t.provided_email = provided_email
			invite_user_id = params['friend_id', Integer, nil]
			t.invite_user_id = invite_user_id
			
			referer = params['referer', String, nil]
			t.complete_action = !referer.nil?();
			t.referer = referer
			
			if(!referer.nil?())
				t.join_type = 2
			elsif(!provided_email.nil?() && !invite_user_id.nil?())
				t.join_type = 1
			else
				t.join_type = 0
			end
			_validate_start_template(t, request, general_error);

			puts t.display;
		end


		def show_terms()
			request.reply.headers['X-width'] = 0;
			t = Template.instance("account", "show_terms");
			puts t.display;
		end


		def show_privacy()
			request.reply.headers['X-width'] = 0;
			t = Template.instance("account", "show_privacy");

			puts t.display;
		end

		def create_account()
			request.reply.headers['X-width'] = 0;

			begin
				start_template = Template.instance("account", "create_start");
			
				valid = _validate_start_template(start_template,request);
				if (valid)
					username = params["username", String, nil]
					password = params["password", String, nil]
					email = params["email", String, nil]
					year = params["year", Integer, nil]
					month = params["month", Integer, nil]
					day = params["day", Integer, nil]
					sex = params["sex", String, nil]
					location = params["location", Integer, 75]
					termsversion = params["termsversion", Integer]

					dob = Time.utc(year,month,day).to_i
					
					user = User.create(username, password, email, dob, sex, location, request.get_ip_as_int)
					user.termsversion = termsversion
					
					user.firstname = params["first_name", String, nil]
					user.lastname = params["last_name", String, nil]
					user.store
					
					join_type = params['join_type', Integer, 0];
					
					invite_user_id = params['invite_user_id', Integer, nil]
					if(site_module_loaded?(:FriendFinder))
						if(!invite_user_id.nil?())
							ei = FriendFinder::EmailInvite.find(:first, [invite_user_id, email]);
						
							if(!ei.nil?())
								ei.accepted = true;
								ei.store();
							end
						end
					end
					
					if(site_module_loaded?(:Metrics))
						join_m = Metrics::IncrementalMetricData.new()
						join_m.categoryid = Metrics::CategoryUserSignUp.typeid
						join_m.metric = 7
						join_m.usertype = 'na'
						join_m.col = join_type
						join_m.date = Metrics::CategoryUserSignUp.get_start_of_day(Time.now)
						join_m.value = 1
						join_m.store(:duplicate, :increment => [:value, 1])
						
						m = Metrics::IncrementalMetricData.new()
						m.categoryid = Metrics::CategoryUserSignUp.typeid
						m.metric = 6
						m.usertype = 'na'
						m.col = 0
						m.date = Metrics::CategoryUserSignUp.get_start_of_day(Time.now)
						m.value = 1
						m.store(:duplicate, :increment => [:value, 1])
					end
					
					if((!user.firstname.nil?() && user.firstname.length > 0) || (!user.lastname.nil?() && user.lastname.length > 0))
						if(site_module_loaded?(:Metrics))
							m = Metrics::IncrementalMetricData.new()
							m.categoryid = Metrics::CategoryUserSignUp.typeid
							m.metric = 6
							m.usertype = 'na'
							m.col = 1
							m.date = Metrics::CategoryUserSignUp.get_start_of_day(Time.now)
							m.value = 1
							m.store(:duplicate, :increment => [:value, 1])
						end
					end
					# The user object doesn't retrieve the email of a user unless they've already been activated.
					# So we use the email the user just gave us and set it on the user object,
					# since the emailer code uses the user object.
					emailobj = UserEmail.new()
					emailobj.userid = user.userid
					emailobj.active = false
					emailobj.email = email
					emailobj.key = "";
					emailobj.time = Time.now.to_i

					user.email = emailobj
				
					key = Authorization.instance.reset(user)

					track_join_from_ip(request)

					send_activation_email(user, key)

					_send_welcome_message(user);
					
					AccountModule::warn_admins_defer(request.get_ip, request.get_ip_as_int);
					
					site_redirect(url / :account / :confirm & {:email => user.email})
				else
					puts start_template.display
				end
			rescue UserError => error
				$log.error
				join(PostResponse.new(:error, error.to_s))
			# As of Ruby 1.9.x, SMTPError becomes a class, which all of the following SMTP____ exceptions
			# would presumably extend. Right now, they just include it. Because of this, I'm looking for
			# each possible error instead of SMTPError.
			rescue Net::SMTPServerBusy, Net::SMTPSyntaxError, Net::SMTPFatalError, Net::SMTPUnknownError => error
				$log.error
				join(PostResponse.new(:error, "Error sending activation email to the account given"))
			# For some reason, some people are still seeing places where this blows up royally, so I'm
			# adding an extra catch for any other error that could occur that brings us back to the first
			# page.
			rescue Exception => error
				$log.error
				join(PostResponse.new(:error, "Unknown error. Please <a href='#{$site.www_url}/contactus.php'>contact site admin</a>."))
			end
		end

	
		KnownMailHost = Struct.new :name, :url
		KNOWN_MAIL_HOSTS = {
			"hotmail.com" => KnownMailHost.new("Hotmail", "http://www.hotmail.com"),
			"live.com" => KnownMailHost.new("Windows Live Mail", "http://mail.live.com"),
			"gmail.com" => KnownMailHost.new("Gmail", "http://www.gmail.com"),
			"yahoo.com" => KnownMailHost.new("Yahoo Mail", "http://mail.yahoo.com"),
			"aol.com" => KnownMailHost.new("AOL Mail", "http://webmail.aol.com"),
			"shaw.ca" => KnownMailHost.new("Shaw Mail", "http://webmail.shaw.ca"),
			"telus.net" => KnownMailHost.new("Telus Mail", "http://webmail.telus.net"),
			"msn.com" => KnownMailHost.new("MSN Mail", "http://mail.live.com")
		}

		def confirm_email()
			request.reply.headers['X-width'] = 0

			email = params["email", String]
			provider = email.gsub(/^.*?@/,'')
			known_host = nil
			KNOWN_MAIL_HOSTS.each { |k,v|
				known_host = v if provider == k
			}

			t = Template.instance("account", "create_confirm");
	
			t.email = email
			t.known_host = known_host
			t.reactivate = false

			puts t.display
		end


		def activate()
			username = params['username', String, nil];
			key = params['key', String, nil];
		
			if (username.nil? || username == "" || key.nil? || key == "")
				view_activate();
			else
				process_activation();
			end
		end
	
	
		def view_activate()
			username = params['username', String, nil];
		
			request.reply.headers['X-width'] = 0;
		
			t = Template.instance("account", "activation_entry");
			t.username = username;
			t.reactivate = false;

			print t.display();
		end


		def process_activation()
			username = params['username', String];
			key = params['key', String];
		
			request.reply.headers['X-width'] = 0;
	
			md5_token = Digest::MD5.new.update(key + ":" + Time.now.to_i.to_s).to_s;
			user = User.get_by_name(username);
			if (user.nil?)
				message = "Username #{htmlencode(username)} not found."
				$site.memcache.set("activation-error-#{md5_token}", message , 60)
				site_redirect(url/:account/:activation_failure/md5_token)
			end
		
			new_user = false;
			if(user.state == "new")
				new_user = true;
			end
		
			return_val = Authorization.instance.activate(user,key);
		
			if(return_val.nil?() && new_user)
				newest_user_obj = NewestUser.new();
				newest_user_obj.userid = user.userid;
				newest_user_obj.username = user.username;
				newest_user_obj.sex = user.sex;
				newest_user_obj.age = user.age;
				newest_user_obj.time = Time.now.to_i();
			
				newest_user_obj.store();
			end
		
			if (return_val.nil?)
				session = Session.build(request.get_ip_as_int, user.userid, false, false)
				PageRequest.top.reply.set_cookie(session.cookie);
				PageRequest.top.reply.send_cookies();
				
				# Automatic friending of Nexopia.com user
				nexopia_user = User.get_by_name("Nexopia.com")
				if(!nexopia_user.nil?())
					user.add_friend(nexopia_user)
				end
				
				if(site_module_loaded?(:FriendFinder))
					invite_list = FriendFinder::EmailInvite.find(user.email, :email)
					if(!invite_list.empty?())
						user_id_list = []
						invite_list.each{|invite|
							if(invite.accepted)
								user.add_friend(invite.userid)
							end
							user_id_list << [invite.userid];
						
							invite.delete();
						}
						
						user_list = User.find(*user_id_list);
						user_list.each{|existing_user|
							_send_friend_invite_join_message(existing_user, user)
						}
					end
				end
				if(site_module_loaded?(:Metrics))
					m = Metrics::IncrementalMetricData.new()
					m.categoryid = Metrics::CategoryUserSignUp.typeid
					m.metric = 6
					m.usertype = 'na'
					m.col = 2
					m.date = Metrics::CategoryUserSignUp.get_start_of_day(Time.now)
					m.value = 1
					m.store(:duplicate, :increment => [:value, 1])
				end
				
				site_redirect(url / :friends / :find / :fresh, :Self)
			else
				message = "Activation failed. " + return_val;
				$site.memcache.set("activation-error-#{md5_token}", message , 60)
				site_redirect(url / :account / :activation_failure / md5_token)
			end

			print t.display();
		end


		def activation_success()
			request.reply.headers['X-width'] = 0;
				
			t = Template.instance("account", "activation_success")
				
			print t.display()	
		end


		def activation_failure(md5_token)
			request.reply.headers['X-width'] = 0;
				
			t = Template.instance("account", "activation_failure")
			t.message = $site.memcache.get("activation-error-#{md5_token}") || "Activation failed."
		
			$site.memcache.delete("activation-error-#{md5_token}")

			print t.display()
		end


		def reactivate()
			request.reply.headers['X-width'] = 0;

			username = params['username', String, nil];
			email = params['email', String, nil]

			if (!username.nil? && username != "")
				user = User.get_by_name(username)
			elsif (!email.nil? && email != "")
				user = User.get_by_email(email)
			else
			  view_reactivate();
				return;
			end
		
			if(!user.nil?)
	 			key = Authorization.instance.reset(user);

	 			if (key.nil?)
	 				t = Template.instance("account", "activation_failure");
	 				t.username = user.username;
	 				t.message = "Your account has already been activated. Please login.";
	 			else
	 				begin

						# The user object doesn't retrieve the email of a user unless they've already been activated.
						# So we need to retrieve the user email directly and set it on the user object,
						# since the emailer code uses the user object.
						email = UserEmail::find(:first, user.userid)
						user.email = email
	
						send_activation_email(user,key)
					
					# As of Ruby 1.9.x, SMTPError becomes a class, which all of the following SMTP____ exceptions
	 				# would presumably extend. Right now, they just include it. Because of this, I'm looking for
	 				# each possible error instead of SMTPError.
	 				rescue Net::SMTPServerBusy, Net::SMTPSyntaxError, Net::SMTPFatalError, Net::SMTPUnknownError => error
	 					# Email would normally bounce. Just silence the error.
	 					$log.info "Error sending activation email during reactivation to user with ID: #{user.userid}."
	 					$log.error
	   				t = Template.instance("account", "resend_activation")
	   				t.username = user.username
	   				token = Time.now.strftime("%H:%M:%S:%Y-%m-%d") + ":" + PageRequest.top.token
	   				t.message = "Error sending email.  When reporting this error, please include the following token: #{token}"
	 				end

					unless(t)
					  site_redirect(url / :account / :confirm & {:email => user.email});
						return
				  end
	 			end
			else
				t = Template.instance("account", "resend_activation");
				t.username = username;
				t.message = "Username not found.";
		  end

			puts t.display;
		end
	
	
		def view_reactivate()
			username = params['username', String, nil];
		
			request.reply.headers['X-width'] = 0;
		
			t = Template.instance("account", "resend_activation");
			t.username = username;
			t.reactivate = true;

			print t.display();
		end
	

		def view_activate_step()
			request.reply.headers['X-width'] = 0;
		
			t = Template.instance("account", "view_activate_step");
		
			t.step2 = true;
			t.step3 = true;
		
			print t.display();
		end
	
		#to be removed.
		FriendRow = Struct.new :name, :email, :index, :profile_link, :thumbnail_tag, :username;

		#To be moved to another module
		def optout(email=nil, key=nil)
			request.reply.headers['X-width'] = 0;

			form_email = params['email', String, nil];
			if (form_email.nil? || form_email == "")
				if (!Authorization.instance.check_key(email,key,-1))
					email = "";
				end
			else
				email = form_email;
			end

			if (email != "")
				optout = InviteOptout.find(:first, email) || InviteOptout.new;
				optout.email = email;
				optout.store();

				t = Template.instance("account", "invite_optout_confirm");

				puts t.display;
			else
				t = Template.instance("account", "invite_optout");
				t.email_addr = params['email_addr', String, nil];
				
				puts t.display;
			end
		end


		#
		# AJAX pagehandlers for validation
		#
		def check_username()
			username = params["value", String, nil];
		
			request.reply.headers['Content-Type'] = PageRequest::MimeType::XMLText;

			results = _validate_username(username).validate;

			puts results.to_xml;
		end


		def check_email()
			email = params["value", String, nil];

			request.reply.headers['Content-Type'] = PageRequest::MimeType::XMLText;

			# Presumably, we've already checked for matching in the Javascript side, so there's not much sense
			# going through the trouble of passing in the extra variable and checking it again.
			results = _validate_email(email, email).validate;

			puts results.to_xml;
		end


		def send_activation_email(user, key)
			if (site_module_loaded?(:Orwell))
				msg = Orwell::SendEmail.new
				msg.subject = "#{$site.config.site_name} Activation Link"
				msg.send(user, 'activation_email_plain', :html_template => 'activation_email_html', :template_module => 'account', :key => key)
			end
		end


		def _send_welcome_message(user)
		
			t = Template.instance("account", "welcome_message")
			t.user = user
		
			message = Message.new;
			message.sender_name = "Nexopia";
			message.receiver = user;
			message.subject = "Welcome To Nexopia";
			message.text = t.display
			message.send();
		end
		
		def _send_friend_invite_join_message(existing_user, new_user)
		
			t = Template.instance("account", "invitee_join_message")
			t.user = existing_user
			t.new_user = new_user
		
			message = Message.new;
			message.sender_name = "Nexopia";
			message.receiver = existing_user;
			message.subject = "A Friend You Invited Has Joined";
			message.text = t.display
			message.send();
		end


		# Increments the memcache tracker variable for account creations from a particular ip
		# The memcache entry only lasts for half an hour, so theoretically, someone could make
		# 48 x $site.config.join_ip_frequency_cap accounts per day.
		def track_join_from_ip(request)
			# Increment the memcache entry for this IP so we can guard against spammers
			ip_frequency = $site.memcache.incr("account-join-ip-#{request.get_ip_as_int}")
			if(ip_frequency.nil?)
				$site.memcache.set("account-join-ip-#{request.get_ip_as_int}", 1, 60*30)
			end
		end


		# Returns true if the frequency cap (as set in the 'join_ip_frequency_cap') has been exceeded
		# Returns false if we're still under the frequency cap
		def cap_join_from_ip?(request)
			ip_frequency = $site.memcache.get("account-join-ip-#{request.get_ip_as_int}") || 1
			return (ip_frequency > $site.config.join_ip_frequency_cap)
		end


		#
		# The Validation Setup functions for each step reside here.
		#
		def _validate_start_template(template, request, general_error=nil)
			params = request.params;

			username = params['username', String, nil];
			email = params['email', String, nil];
			email_confirm = params['email_confirm', String, nil];
			password = params['password', String, nil];
			dob = params['dob', Integer, nil];
			year = params['year', Integer, dob.nil? ? nil : Time.at(dob).year];
			month = params['month', Integer, dob.nil? ? nil : Time.at(dob).month];
			day = params['day', Integer, dob.nil? ? nil : Time.at(dob).day];
			location = params['location', Integer, 75];
			location_path = params['location_path', String, nil]
			sex = params['sex', String, nil];
			referer = params['referer', String, nil]
			
			validation = Validation::Set.new;
			validation.add("username", _validate_username(username));
			validation.add("email", _validate_email(email, email_confirm));
			validation.add("email_confirm", _validate_email_confirm(email, email_confirm));
			validation.add("password", _validate_password(password, username));
			validation.add("dob", _validate_dob(year, month, day));
			validation.add("sex", _validate_sex(sex));

			# For general validation that does not fit into any one of the fields.
			validation.add("general", _validate_general(request, general_error), false);

			validation.bind(template);

			template.username = username;
			template.email = email;
			template.email_confirm = email_confirm;
			template.password = password;
			template.year = year;
			template.month = month;
			template.day = day;
			template.location = location;
			template.sex = sex;
			template.referer = referer

			first_name = params['first_name', String, nil]
			last_name = params['last_name', String, nil]

			template.first_name = first_name
			template.last_name = last_name
			
			join_type = params['join_type', Integer, nil]
			
			if(!join_type.nil?())
				template.join_type = join_type
			end
			
			invite_user_id = params['invite_user_id', Integer, nil]
			if(!invite_user_id.nil?())
				template.invite_user_id = invite_user_id
			end
			
			valid = validation.valid?
			template.post_response = PostResponse.new(:error, "Please make sure you have filled in the fields correctly.") if !valid && !validation.no_data?
			
			interstitial = Wiki::from_address("/SiteText/whyjoin")
			template.why_join_text = interstitial.get_revision().content

			if(cap_join_from_ip?(request))
				template.post_response = PostResponse.new(:error, "Please try again later.")
				valid = false
			end
			
			return valid
		end
	end
end