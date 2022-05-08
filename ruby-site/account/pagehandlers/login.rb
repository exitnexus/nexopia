lib_require :Account, 'validation_helper', 'post_response'
lib_require :Core, 'login_log'

module AccountManagement
	class LoginHandler < PageHandler
		include AccountValidationHelper;
		
		declare_handlers("account") {
			area :Public
			access_level :NotLoggedIn
			page :GetRequest, :Full, :show_login, "login"
			handle :OpenPostRequest, :post_login, "login", "post"
			page :PostRequest, :Full, :reset_password, "reset_password"
		
			page :GetRequest, :Full, :new_password, "new_password"
			page :PostRequest, :Full, :change_password, "change_password"
		
			# page :PostRequest, :Full, :do_login, "post"
		}
	
	
		def show_login(error=nil, show_lost_password=false)
			request.reply.headers['X-width'] = 0;
		
			t = Template.instance("account", "login")
			t.inline = params.to_hash['inline']
			t.referer = params.to_hash['referer']
			t.username = params.to_hash['login_username']

			if (error_ref = params["error", String, nil])
				error = $site.memcache.get("login-error-#{error_ref}")
			end

			if (error)
				t.post_response = error
				t.show_lost_password = show_lost_password
			end
		
			puts t.display
		end
	
	
		def post_login()
			username = params['login_username', String, nil]
			password = params['login_password', String, nil]
			user = User.get_by_name(username)

			error_msg = nil
			error_msg_secondary = nil
			error_status = nil
			if (user)
				if (Password.check_password(password, user.userid))
					if (!user.activated?)
						error_msg = "You must activate to continue"
						email = UserEmail.find(:first, user.userid).email
						error_msg_secondary = "Click <a href='/account/reactivate?email=#{urlencode(email)}'>here</a> to reactivate"
						error_status = 'unactivated'
					elsif (user.frozen?)
						if (user.frozentime == 0)
							error_msg = "Your account is frozen."
						else
							frozen_days = (user.frozentime - Time.now.to_i) / (60*60*24).to_f
							frozen_days_str = "%.3f" % frozen_days
							error_msg = "Your account is frozen for another #{frozen_days_str} days."
						end
						error_msg_secondary = "<a class=body href='/contactus.php'>Contact an admin if you've got questions.</a>"
						error_status = 'frozen'
					elsif (user.deleted?)
						error_msg = "That account is deleted"
						error_status = 'deleted'
					end
				else
					error_msg = "Bad username or password"
					error_status = "badpass"
				end
			else
				error_msg = "Bad username or password"
				# We don't have a userid at this point, so we can't log the login attempt
			end
			
			if (error_msg)
				error = PostResponse.new(:error, error_msg, error_msg_secondary)
				md5_token = Digest::MD5.new.update(username + ":" + Time.now.to_i.to_s).to_s;
				$site.memcache.set("login-error-#{md5_token}", error, 60)
				
				# Store the login log entry
				LoginLog.log(user.userid, request.get_ip_as_int, error_status) if (user && error_status)
				
				site_redirect( url / :account / :login & { :error => md5_token, :login_username => username })
			else
				# TODO: Sheesh... I started doing this to fix an issue with not displaying login error messages and
				# at this point, we probably should just be doing all the logging in via Ruby. There isn't much that
				# the login.php page is doing now that couldn't just be done in this method.
				# TODO: When this is completely moved to Ruby, remember to store a LoginLog for logins that are
				# successful (status: success).
				rewrite(:PostRequest, "/login.php:Page", params, :Public)
			end
		end
	
	
		def reset_password()
			request.reply.headers['X-width'] = 0;

			email = params['reset_email', String, nil]

			if (!email.nil? && email != "")
				user = User.get_by_email(email)
			else
			  show_login(PostResponse.new(:error, "Please enter an email address to reset your password."), true)
				return
			end
		
			if(!user.nil?)
				# Check for attempts at hackery
				user_frequency_cap = $site.memcache.incr("reset-password-uid-#{user.userid}")
				if(user_frequency_cap.nil?)
					$site.memcache.set("reset-password-uid-#{user.userid}", 1, 1800)
					user_frequency_cap = 1
				end

				ip_frequency_cap = $site.memcache.incr("reset-password-ip-#{request.get_ip_as_int}")
				if(ip_frequency_cap.nil?)
					$site.memcache.set("reset-password-ip-#{request.get_ip_as_int}", 1, 1800)
					ip_frequency_cap = 1
				end

				if(user_frequency_cap > 10 || ip_frequency_cap > 10)
					show_login(PostResponse.new(:error, "Please try again later."))
					return
				end

				# Force a re-activation
	 			key = Authorization.instance.reset(user,true);

				# key should never be nil when authorization is forced (throw site error if it is?)
				begin
					# The user object doesn't retrieve the email of a user unless they've already been activated.
					# So we need to retrieve the user email directly and set it on the user object,
					# since the emailer code uses the user object.
					email = UserEmail::find(:first, user.userid)
					user.email = email

					send_lost_password_email(user,key)
			
				# As of Ruby 1.9.x, SMTPError becomes a class, which all of the following SMTP____ exceptions
				# would presumably extend. Right now, they just include it. Because of this, I'm looking for
				# each possible error instead of SMTPError.
				rescue Net::SMTPServerBusy, Net::SMTPSyntaxError, Net::SMTPFatalError, Net::SMTPUnknownError => error
					# Email would normally bounce. Just silence the error.
					$log.info "Error sending lost password email to user with ID: #{user.userid}."
					$log.error
	 				token = Time.now.strftime("%H:%M:%S:%Y-%m-%d") + ":" + PageRequest.top.token
	 				show_login(PostResponse.new(:error, "Error sending email.  When reporting this error, please include the following token: #{token}"), true)
					return
				end
			
				show_login(PostResponse.new(:valid, "", "An email to finish resetting your password has been sent to #{user.email}."))
			else
				show_login(PostResponse.new(:error, "Username not found."), true)
		  end
		end
	
	
		def new_password(error=nil)
			request.reply.headers['X-width'] = 0;
		
			t = Template.instance("account", "new_password")		
			_validate_new_password(t)
		
			puts t.display
		end
	
	
		def change_password()
			request.reply.headers['X-width'] = 0;
		
			key = params["key", String, nil]
			username = params["username", String, nil]
			password = params["password", String, nil]
		
			t = Template.instance("account", "new_password")
		
			user = User.get_by_name(username)
		
			if (_validate_new_password(t))
				# Change the password
				pass = Password.find :first, user.userid
				pass.change_password(password)
				pass.store
			
				Authorization.instance.activate(user, key)
				
				# Log the user in
				session = Session.build(request.get_ip_as_int, user.userid, false, false)
				PageRequest.top.reply.set_cookie(session.cookie)
				PageRequest.top.reply.send_cookies()

				site_redirect("/")
			end
		
			# We'll only ever get here if the change password attempt has failed.
			puts t.display
		end
	

		def send_lost_password_email(user, key)
			if (site_module_loaded?(:Orwell))
				msg = Orwell::SendEmail.new
				msg.subject = "Change your password at #{$site.config.site_name}"
				msg.send(user, 'lost_password_plain', :html_template => 'lost_password', :template_module => 'account', 
					:username => user.username, :key => key)
			end
		end
	

		def _validate_new_password(template)
			key = params["key", String, nil]		
			username = params['username', String]
			password = params['password', String, nil]
			password_confirm = params['password_confirm', String, nil]		
		
			template.key = key
			template.username = username
		
			user = User.get_by_name(username)
			if(user.nil?)
				template.post_response = PostResponse.new(:error, "User #{username} does not exist.", 
					"Please <a target='_top' href='/account/join'>click here</a> to create a new account.")
				return false
			end
			
			# Check whether or not the key has expired
			email = UserEmail::find(:first, user.userid, :conditions => ["`key` = ?", key])
			if(email.nil?) # Do we have a valid activation?
				template.post_response = PostResponse.new(:error, "Invalid request.", 
					"Please <a target='_top' href='/account/login'>click here</a> to login or reset your password.")
				return false
			end
		
			if(email.time <= (Time.now.to_i - 86400)) # Only allow a day for the activation key to be valid. (This used to be a week.)
				template.post_response = PostResponse.new(:error, "Password reset time period has expired.",
					"Please <a target='_top' href='/account/login'>click here</a> to login or request another password reset.")
				return false
			end

			validation = Validation::Set.new;
			validation.add("password", _validate_password(password, username));
			validation.add("password_confirm", _validate_password_confirm(password, password_confirm))

			validation.bind(template);

			valid = validation.valid?
			template.post_response = PostResponse.new(:error, "Please make sure you have filled in the fields correctly.") if !valid && !validation.no_data?

			return valid
		end	
	end
end