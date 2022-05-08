lib_require :Core, 'users/user', 'users/user_name', 'users/useremails', 'static_pages', 'authorization'
lib_require :Friends, 'friend'
lib_require :Profile, 'profile', 'profile_block'
lib_require :Accountcreate, 'terms', 'activation_email', 'invite_email', 'invite_optout', 'invite'
lib_require :Messages, 'message'
lib_require :Core, 'validation/display', 'validation/set', 'validation/results', 'validation/rules', 'validation/chain'
lib_require :Core, 'validation/rule', 'validation/value_accessor'
lib_require :Core,  'user_error'
lib_require :Accountcreate, 'validation_helper';

require 'net/http'
require 'uri'

class CreateAccount < PageHandler

	include Profile;
	include AccountcreateValidationHelper;

	declare_handlers("accountcreate") {
		access_level :NotLoggedIn

		# The three steps in account creation:
		# 1. Start: 	Minimal information is entered to create an account
		# 2. Accept: 	The user must accept the legal terms.
		# 3. Create: 	The terms are set on the account and the user is logged in and
		# 						sent an activation email.
		page :GetRequest, :Full, :create_start # Do the same as if we called /accountcreate/start
		page :GetRequest, :Full, :create_start, "start"
		page :PostRequest, :Full, :create_terms, "accept"
		page :PostRequest, :Full, :create_account, "create"

		area :Public

#		page :GetRequest, :Full, :param_test, "ptest", remain

		# For getting fullscreen versions of terms and privacy as in the PHP site.
		page :GetRequest, :Full, :show_terms, "terms"
		page :GetRequest, :Full, :show_privacy, "privacy"

		# Opt out of invites
		page :GetRequest, :Full, :optout, "optout"
		page :GetRequest, :Full, :optout, "optout", input(String), input(String)
		page :PostRequest, :Full, :optout, "optout", "update"

		# Server-side AJAX handles
		handle :PostRequest, :check_username, "check_username", input(String)
		handle :PostRequest, :check_email, "check_email", input(String)

		# Activation and re-activation handlers
		page :GetRequest, :Full, :activate, "activate"
		page :GetRequest, :Full, :reactivate, "reactivate"

#		page :GetRequest, :Full, :test_cookies, "testcookies"

		area :Self
		access_level :LoggedIn

		# Confirmation: The user is told that the account has been created
		page :GetRequest, :Full, :create_confirm, "confirm"

		# Initial profile creation.
		page :GetRequest, :Full, :create_profile, "profile"
		page :PostRequest, :Full, :update_profile, "profile", "update"

		# Initial picture upload.
		page :GetRequest, :Full, :create_picture, "picture"
		page :GetRequest, :Full, :update_picture, "picture", "update"
#		page :PostRequest, :Full, :update_picture, "picture", "update"
		
		page	:GetRequest, :Full, :view_activate_step, "activate";
		
		# Initial friend find/invites.
		#page :GetRequest, :Full, :view_find_friends, "find";
		#page :GetRequest, :Full, :view_member_results, "find", "members";
		#page :GetRequest, :Full, :view_invite_results, "find", "invite";
	}
=begin
	def param_test(remain)
		remain.each { | part |
			puts "#{part.to_s}<br/>";
			puts "<ul>"
			arguments = params[part, TypeSafeHash, nil];
			arguments.keys(true).each { | key |
				argument = arguments[key, String];
				puts "<li>#{argument}</li>";
			};
			puts "</ul><br/>";
		};
	end
=end

	def create_start(general_error=nil)
		request.reply.headers['X-width'] = 0;

		t = Template.instance("accountcreate", "create_start");

		_validate_start_template(t, request, general_error);

		puts t.display;
	end


	def create_terms()
		request.reply.headers['X-width'] = 0;

		start_template = Template.instance("accountcreate", "create_start");
		terms_template = Template.instance("accountcreate", "create_terms");

		valid = _validate_start_template(start_template,request);
		if (valid)
			username = params['username', String];
			email = params['email', String];
			password = params['password', String];
			year = params['year', Integer];
			month = params['month', Integer];
			day = params['day', Integer];
			location = params['location', Integer];
			sex = params['sex', String];
			timezone = params['timezone', Integer, nil];

			terms_template.username = username;
			terms_template.email = email;
			terms_template.password = password;
			terms_template.dob = Time.local(year,month,day).to_i;
			terms_template.location = location;
			terms_template.sex = sex;
			terms_template.timezone = timezone;
			
			terms_template.captcha = $site.captcha.get_challenge

			puts terms_template.display;
		else
			puts start_template.display;
		end
	end







	def show_terms()
		request.reply.headers['X-width'] = 0;
		t = Template.instance("accountcreate", "show_terms");
		puts t.display;
	end


	def show_privacy()
		request.reply.headers['X-width'] = 0;
		t = Template.instance("accountcreate", "show_privacy");

		puts t.display;
	end


# Security leak when this is turned on. Don't turn on unless there are cookie/login
# problems that need to be tested in a development environment.
=begin
	def test_cookies()
		user = User.find(:first, 6638);
		Authorization.instance.auto_login(user, request.headers["REMOTE_ADDR"].to_s);
		site_redirect("/");
	end
=end

	def create_account()
		request.reply.headers['X-width'] = 0;

		begin
			terms_template = Template.instance("accountcreate", "create_terms");
			
			valid = _validate_terms_template(terms_template, params);
			if (valid)
				username = params["username", String, nil];
				password = params["password", String, nil];
				email = params["email", String, nil];
				dob = params["dob", Integer, nil];
				sex = params["sex", String, nil];
				location = params["location", Integer, nil];
				termsversion = params["termsversion", Integer];
				timezone = params['timezone', Integer, nil];

				user = User.create(username, password, email, dob, sex, location, request.get_ip_as_int);
				user.termsversion = termsversion;
				user.timeoffset = timezone;
				user.store;

				key = Authorization.instance.reset(user);

				activation_email = ActivationEmail.new(user, key);
				activation_email.send();

				_send_welcome_message(user);
				_process_invites(user);

				Authorization.instance.auto_login(user, request.headers["REMOTE_ADDR"].to_s);

				site_redirect("/my/accountcreate/profile");
			else
				terms_template.captcha = $site.captcha.get_challenge
				puts terms_template.display;
			end
		rescue UserError => error
			create_start(error.to_s);
		# As of Ruby 1.9.x, SMTPError becomes a class, which all of the following SMTP____ exceptions
		# would presumably extend. Right now, they just include it. Because of this, I'm looking for
		# each possible error instead of SMTPError.
		rescue Net::SMTPServerBusy, Net::SMTPSyntaxError, Net::SMTPFatalError, Net::SMTPUnknownError => error
			create_start("Error sending activation email to the account given");
		end
	end





	def create_confirm()
		request.reply.headers['X-width'] = 0;

		user = request.session.user;

		t = Template.instance("accountcreate", "create_confirm");
		t.username = user.username;
		t.reactivate = false;

		puts t.display;
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
		
		t = Template.instance("accountcreate", "activation_entry");
		t.username = username;
		t.reactivate = false;

		print t.display();
	end

	def process_activation()
		username = params['username', String];
		key = params['key', String];
		
		request.reply.headers['X-width'] = 0;
	
		user = User.get_by_name(username);

		return_val = Authorization.instance.activate(user,key);
		
		if (return_val.nil?)
			t = Template.instance("accountcreate", "activation_success");
			Authorization.instance.auto_login(user, request.headers["REMOTE_ADDR"].to_s);
		else
			t = Template.instance("accountcreate", "activation_failure");
			t.message = "Activation failed. " + return_val;
		end

		print t.display();
	end

	def reactivate()
		request.reply.headers['X-width'] = 0;

		username = params['username', String, nil];

		if (!username.nil? && username != "")
			user = User.get_by_name(username);
			key = Authorization.instance.reset(user);

			if (key.nil?)
				t = Template.instance("accountcreate", "activation_failure");
				t.username = user.username;
				t.message = "Your account has already been activated. Please login.";
			else
				begin
					activation_email = ActivationEmail.new(user, key);
					activation_email.send();					
				# As of Ruby 1.9.x, SMTPError becomes a class, which all of the following SMTP____ exceptions
				# would presumably extend. Right now, they just include it. Because of this, I'm looking for
				# each possible error instead of SMTPError.
				rescue Net::SMTPServerBusy, Net::SMTPSyntaxError, Net::SMTPFatalError, Net::SMTPUnknownError => error
					# Email would normally bounce. Just silence the error.
					$log.info "Error sending activation email during reactivation to user with ID: #{user.userid}.";
				end
				
				t = Template.instance("accountcreate", "create_confirm");
				t.username = user.username;
				t.reactivate = true;
			end

			puts t.display;
		end
	end


	def create_profile()
		request.reply.headers['X-width'] = 0;

		session = request.session;
		user = session.user;
		profile = Profile.find(:first, user.userid);

		t = Template.instance("accountcreate", "create_profile");
		t.weight = profile.weight;
		t.height = profile.height;
		t.orientation = profile.orientation;
		t.living = profile.living;
		t.dating = profile.dating;
		
		profile_block_1 = ProfileBlock.find(:first, user.userid);
		if (profile_block_1.nil?)
			profile_block_1 = ProfileBlock.new;
			t.about_title = "About Me";
		else
			t.block_id = profile_block_1.blockid;
			t.about_title = profile_block_1.blocktitle;
		end
		
		t.about = profile_block_1.blockcontent;
		t.timezone = user.timeoffset;
		t.step2 = false;
		t.step3 = false;

		puts t.display;
	end


	def update_profile()
		request.reply.headers['X-width'] = 0;

		action = params['action', String, nil];
		t = Template.instance("accountcreate", "create_profile");
		if(_validate_profile_template(t, params))
			session = request.session;
			user = session.user;
			profile = Profile.find(:first, user.userid);

			profile.weight = params['weight', String, nil];
			profile.height = params['height', String, nil];
			profile.orientation = params['orientation', String, nil];
			profile.living = params['living', String, nil];
			profile.dating = params['dating', String, nil];
			profile.profileupdatetime = Time.now.to_i;
			profile.store;

			block_id = params['block_id', Integer, nil];
			if (block_id.nil? || block_id == "" || ProfileBlock.find(:first, user.userid, block_id).nil?)
				block_id = ProfileBlock.get_seq_id(user.userid);
				profile_block_1 = ProfileBlock.new;
				profile_block_1.blockid = block_id;
				profile_block_1.blocktitle = "About Me";
				profile_block_1.userid = user.userid;
				profile_block_1.blockorder = 1;
				profile_block_1.permission = "anyone";
			else
				profile_block_1 = ProfileBlock.find(:first, user.userid, block_id);
			end
			
			profile_block_1.blockcontent = params['about', String, nil];
			profile_block_1.store;
			
			if (action == "Finish")
				site_redirect("/accountcreate/activate");
			elsif (action == "Continue")
				site_redirect("/accountcreate/picture");
			end
		else
			puts t.display;
		end
	end


	def create_picture()
		request.reply.headers['X-width'] = 0;

		t = Template.instance("accountcreate", "create_picture");
		t.step2 = true;

		upload_error = params["error", String, nil];
				
		if (!upload_error.nil?)
			chain = Validation::Chain.new;
			
			uid = PageRequest.current.session.user.userid
						
			upload_error = $site.memcache.get("error-#{uid}-#{upload_error}");			
			if (upload_error.kind_of? UserError)
				upload_error_text = upload_error.to_s;
			else
				upload_error_text = "Unknown Error: Please make sure you're choosing an image file."
				$log.info upload_error, :error
				$log.info upload_error.backtrace.join("\n"), :error
			end
			
			chain.add(Validation::Rules::PassResults.new(Validation::Results.new(:error, upload_error_text)));
			validation = Validation::Set.new;
			validation.add("upload_error", chain);

			validation.bind(t);
		end
		
		puts t.display;
	end


	def update_picture()
		if (params.to_hash["Errors"] && !(params.to_hash["Errors"].kind_of? Uploads::FileSizeException))
			exception = params.to_hash["Errors"];
			$log.info "Picture upload error", :error;
			$log.object exception, :error;
			$log.info exception.backtrace, :error;
			uid = PageRequest.current.session.user.userid
			time = MD5.new(Time.now.to_s).to_s
			$site.memcache.set("error-#{uid}-#{time}", exception, 86400)
			site_redirect("/accountcreate/picture?error=#{time}")
			return;
		end
				

		# The real work here is done by the uploader on Pics.
		# See the form in the create_picture template to see how it's getting called.
		action = params['action', String, nil];
		if (action == "Finish")
			site_redirect("/accountcreate/activate");
		else
			site_redirect("/accountcreate/activate");
		end
	end

	def view_activate_step()
		request.reply.headers['X-width'] = 0;
		
		t = Template.instance("accountcreate", "view_activate_step");
		
		t.step2 = true;
		t.step3 = true;
		
		print t.display();
	end
	
	#to be removed.
	FriendRow = Struct.new :name, :email, :index, :profile_link, :thumbnail_tag, :username;

	#to be moved.
=begin
	def view_find_friends()
		t = Template.instance("accountcreate", "find_friends");
		
		request.reply.headers["X-width"] = 0;
		
		t.redirect_location = "/accountcreate/find/members/";
		t.finish_location = "/my/accountcreate/confirm/";
		t.step2 = true;
		t.step3 = true;
		
		
		print t.display();

		request.reply.headers['X-width'] = 0;
		
		friends = Array.new;
		(0...6).each {
			friends << FriendRow.new("","",0,"");
		}

		t = Template.instance("accountcreate", "find_friends");
		t.friends = friends;
		t.my_email = UserEmail.find(:first, session.user.userid).email;
		t.my_name = request.session.user.username;

		t.friend_vi = ["","","","","",""];
		t.friend_vm = ["","","","","",""];

		t.step2 = true;
		t.step3 = true;

		_validate_find_template(t, nil, nil, nil);

		puts t.display;

	end

	def view_member_results()
		t = Template.instance("accountcreate", "find_results");
		
		request.reply.headers['X-width'] = 0;
		
		t.redirect_location = "/accountcreate/find/invite/";
		t.finish_location = "/my/accountcreate/confirm/";
		t.step2 = true;
		t.step3 = true;
		
		print t.display();
	end
	
	def view_invite_results()
		t = Template.instance("accountcreate", "find_results_invite");
		
		request.reply.headers['X-width'] = 0;
		
		t.redirect_location = "/my/accountcreate/confirm/";
		t.finish_location = "/my/accountcreate/confirm/";
		t.step2 = true;
		t.step3 = true;
		
		print t.display();
	end
=end
	#also to be moved.
=begin
	def find_update()
		request.reply.headers['X-width'] = 0;

		action = params['action', String, nil];

		friend_names = params['friend_name', TypeSafeHash, nil];
		friend_emails = params['friend_email', TypeSafeHash, nil];

		empty = true;
		if (!friend_names.nil?)
			friend_names.each { | key, value|
				if ((!friend_names[key,String].nil? && friend_names[key,String] != "") ||
						(!friend_emails[key,String].nil? && friend_emails[key,String] != ""))
					empty = false;
				end
			};
		end

		if (empty)
			site_redirect("/accountcreate/confirm");
		else
			my_name = params['my_name', String, nil];

			exists_list = Array.new;
			invite_list = Array.new;

			find_template = Template.instance("accountcreate", "find_friends");
			find_template.step2 = true;
			find_template.step3 = true;

			valid = _validate_find_template(find_template, friend_names, friend_emails, my_name);
			if (!valid)
				puts find_template.display;
			else
				friend_names.each { | key, value |
					friend = FriendRow.new(friend_names[key, String], friend_emails[key, String], key.to_i, "");

					if (friend.email == "")
						next;
					end

					existing_user = User.get_by_email(friend.email);
					if (existing_user.nil?)
						invite_list << friend;
					else
						friend.profile_link = "<a href='/profile.php?uid=#{existing_user.userid}' target='_blank'>View Profile</a>";
						friend.thumbnail_tag = "<img src=#{existing_user.thumb.link} />";
						friend.username = existing_user.username;
						exists_list << friend;
					end
				};

				t = Template.instance("accountcreate", "find_friends_confirm");
				t.existing_friends = exists_list;
				t.invite_friends = invite_list;
				t.my_name = my_name;

				t.step2 = true;
				t.step3 = true;

				puts t.display;
			end
		end
	end

	#This is to be moved to friend of friends.
	def find_invite()
		request.reply.headers['X-width'] = 0;

		action = params['action', String, nil];

		my_name = params['my_name', String, nil];
		personal_message = params['personal_message', String, nil];

		invite_friend_names = params['invite_friend_name', TypeSafeHash, nil];
		invite_friend_emails = params['invite_friend_email', TypeSafeHash, nil];
		invite_friend_sends = params['invite_friend_send', TypeSafeHash, nil];

		if (!invite_friend_names.nil?)
			invite_email = InviteEmail.new(request.session.user, my_name, personal_message);
			optouts = InviteOptout.find(:all, :conditions => ["email IN ?", invite_friend_emails.values]);

			invite_friend_names.each { | key, value |
				friend = FriendRow.new(invite_friend_names[key, String], invite_friend_emails[key, String], key.to_i);
				invite = !invite_friend_sends.nil? && (invite_friend_sends[key, String] == "on" ? true:false);
				if (friend.email == "")
					next;
				end

				if (invite && !optouts.include?(friend.email))
					begin
						invite_email.send(friend.name, friend.email);
					# As of Ruby 1.9.x, SMTPError becomes a class, which all of the following SMTP____ exceptions
					# would presumably extend. Right now, they just include it. Because of this, I'm looking for
					# each possible error instead of SMTPError.
					rescue Net::SMTPServerBusy, Net::SMTPSyntaxError, Net::SMTPFatalError, Net::SMTPUnknownError => error
						# Email would normally bounce. Just silence the error.
						$log.info "Error sending invite email to #{friend.email}.";
					end

					invite = Invite.new;
					invite.name = friend.name;
					invite.email = friend.email;
					invite.userid = request.session.user.userid;
					invite.time = Time.now.to_i;
					invite.store;
				end
			};
		end

		existing_friend_names = params['existing_friend_name', TypeSafeHash, nil];
		existing_friend_emails = params['existing_friend_email', TypeSafeHash, nil];
		existing_friend_adds = params['existing_friend_add', TypeSafeHash, nil];

		if (!existing_friend_names.nil?)
			existing_friend_names.each { | key, value |
				friend = FriendRow.new(existing_friend_names[key, String], existing_friend_emails[key, String], key.to_i);
				add = !existing_friend_adds.nil? && (existing_friend_adds[key, String] == "on" ? true:false);

				if (friend.email == "")
					next;
				end

				if (add)
					user = request.session.user;
					existing_user = User.get_by_email(friend.email);

					f = Friend.find(:first, user.userid, existing_user.userid);
					if (f.nil?)
						user.add_friend(existing_user);
					end
				end
			};
		end

		site_redirect("/accountcreate/confirm");
	end
=end
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

			t = Template.instance("accountcreate", "invite_optout_confirm");

			puts t.display;
		else
			t = Template.instance("accountcreate", "invite_optout");

			puts t.display;
		end
	end


	#
	# AJAX pagehandlers for validation
	#
	def check_username(username)
		user_name = UserName.by_name(username);
		request.reply.headers['Content-Type'] = PageRequest::MimeType::XMLText;

		results = _validate_username(username).validate;

		puts results.to_xml;
	end

	def check_email(email)
		email = CGI::unescape(email);
		user_email = UserEmail.by_email(email);

		request.reply.headers['Content-Type'] = PageRequest::MimeType::XMLText;

		# Presumably, we've already checked for matching in the Javascript side, so there's not much sense
		# going through the trouble of passing in the extra variable and checking it again.
		results = _validate_email(email, email).validate;

		puts results.to_xml;
	end


	def _send_welcome_message(user)
		message = Message.new;
		message.sender_name = "Nexopia";
		message.receiver = user;
		message.subject = "Welcome To Nexopia";
		message.text = StaticPage.by_name("welcomemsg").content;
		message.send();
	end

	#Will be moved to friends of friends.
	def _process_invites(user)
		email = UserEmail.find(:first, user.userid);
		Invite.find(:all, :conditions => ["email = ?", email.email]).each { |invite|
			existing_user = User.find(:first, invite.userid);
			if (!existing_user.nil?)
				existing_user.add_friend(user);
				user.add_friend(existing_user);
				
				message = Message.new;
				message.sender_name = "Nexopia";
				message.receiver = existing_user;
				message.subject = "Friend Joined";
				message.text = "Your friend #{invite.name} has joined Nexopia.com, and has been added to your friends list. " +
					"Click [url=/profile.php?uid=#{user.userid}]here[/url] to see your friend's profile.";
				message.send();
			end

			invite.delete();
		};
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
		password_confirm = params['password_confirm', String, nil];
		dob = params['dob', Integer, nil];
		year = params['year', Integer, dob.nil? ? nil : Time.at(dob).year];
		month = params['month', Integer, dob.nil? ? nil : Time.at(dob).month];
		day = params['day', Integer, dob.nil? ? nil : Time.at(dob).day];
		location = params['location', Integer, nil];
		sex = params['sex', String, nil];
		timezone = params['timezone', Integer, nil];

		validation = Validation::Set.new;
		validation.add("username", _validate_username(username));
		validation.add("email", _validate_email(email, email_confirm));
		validation.add("email_confirm", _validate_email_confirm(email, email_confirm));
		validation.add("password", _validate_password(password, password_confirm, username));
		validation.add("password_confirm", _validate_password_confirm(password, password_confirm));
		validation.add("dob", _validate_dob(year, month, day));
		validation.add("location", _validate_location(location));
		validation.add("sex", _validate_sex(sex));
		validation.add("timezone", _validate_timezone(timezone));

		# For general validation that does not fit into any one of the fields.
		validation.add("general", _validate_general(request, general_error), false);

		validation.bind(template);

		template.username = username;
		template.email = email;
		template.email_confirm = email_confirm;
		template.password = password;
		template.password_confirm = password_confirm;
		template.year = year;
		template.month = month;
		template.day = day;
		template.location = location;
		template.sex = sex;
		template.timezone = timezone;

		return validation.valid?
	end
	
	def _validate_terms_template(template, params)
		over18 = params["over18", String, nil] == "on" ? true:false;
		over14 = params["over14", String, nil] == "on" ? true:false;
		consent = params["consent", String, nil] == "on" ? true:false;
		agree = params["agree", String, nil] == "on" ? true:false;

		dob = params["dob", Integer, nil];

		chain = Validation::Chain.new;

		terms_rule = Validation::Rules::CheckTerms.new(
			Validation::ValueAccessor.new("dob", dob),
			Validation::ValueAccessor.new("check_hash", { :over18 => over18, :over14 => over14, :consent => consent, :agree => agree }));
		chain.add(terms_rule);

		captcha_rule = Validation::Rules::CheckCaptcha.new(
			Validation::ValueAccessor.new("ip", request.get_ip),
			Validation::ValueAccessor.new("challenge", params['recaptcha_challenge_field', String]),
			Validation::ValueAccessor.new("response", params['recaptcha_response_field', String]));
		captcha_chain = Validation::Chain.new;
		captcha_chain.add(captcha_rule);

		validation = Validation::Set.new;
		validation.add("general", chain);		
		validation.add("captcha", captcha_chain);
		validation.bind(template);
		
		template.username = params["username", String, nil];
		template.email = params["email", String, nil];
		template.password = params["password", String, nil];
		template.dob = dob;
		template.location = params["location", Integer, nil];
		template.sex = params["sex", String, nil];

		return validation.valid?;
	end
	
	def _validate_profile_template(template, params)
		weight = params['weight', String, nil];
		height = params['height', String, nil];
		orientation = params['orientation', String, nil];
		living = params['living', String, nil];
		dating = params['dating', String, nil];
		about = params['about', String, nil];

		# TODO: Actually do some validation here.

		template.weight = weight;
		template.height = height;
		template.orientation = orientation;
		template.living = living;
		template.dating = dating;
		template.about = about;

		return true;
	end
	
	def _validate_find_template(template, friend_names, friend_emails, my_name)
		validation = Validation::Set.new;
		index = 0;

		if (friend_emails.nil?)
			(0...6).each { |index|
				chain = Validation::Chain.new;
				chain.add(Validation::Rules::PassResults.new(Validation::Results::new(:none, "")));
				validation.add("friend[#{index}]",chain);
			}
		else
			friend_emails.keys(true).each { | key |
				name = friend_names[key, String];
				email = friend_emails[key, String];

				display_valid = (!name.nil? && name != "") && (!email.nil? && email != "");

				validation.add("friend[#{index}]", _validate_friend(name, email), display_valid);
				index = index + 1;
			};

			friends = Array.new;
			friend_emails.keys(true).each { | key |
				friend = FriendRow.new(friend_names[key, String], friend_emails[key, String], key.to_i);
				friends << friend;
			};

			template.my_name = my_name;
			template.friends = friends;
		end

		validation.add("my_name", _validate_my_name(my_name));
		validation.bind(template);

		return validation.valid?;
	end
end
