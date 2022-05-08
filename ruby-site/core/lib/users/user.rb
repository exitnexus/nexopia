lib_require :Core,  'authorization'
lib_require :Core,  'storable/storable'
lib_require :Core,  'accounts'
lib_require :Core,  'constants'
lib_require :Core,  'users/user_name'
lib_require :Core,  'users/interests'
lib_require :Core,  'users/locs'
lib_require :Core,  'users/useremails'
lib_require :Core,  'users/userpassword'
lib_require :Core,  'acts_as_uri'
lib_require :Core,  'sql'
lib_require :Core,  'storable/cacheable'
lib_require :Core,	'users/user_ignore'
lib_require :Core,  'user_error'
lib_require :Core,  'skin_mediator'
lib_require :Core, 	'visibility'

class UserActiveTime < Storable
	init_storable(:usersdb, "useractivetime");
end
class UserSearch < Storable
	init_storable(:usersdb, "usersearch")
end

class User < Cacheable

	init_storable(:usersdb, "users");
	attr_reader :interests, :profile, :password, :email, :galleries, :account
	set_prefix("ruby_userinfo")
	
	relation_singular :useractivetime, :userid, UserActiveTime
	relation_multi_cached :interests, :userid, UserInterests, "ruby_interests"
	relation_multi_cached :ignored_user_list, :userid, UserIgnore, "ruby_ignore";

	relation_singular :username_obj, :userid, UserName, true
	relation_singular :password, :userid, Password, true
	relation_singular :account, :userid, Account, true
	relation_singular :profile, :userid, Profile, true


	#acts_as_uri(:description => :username, :uri_spec => ['users',:username])

	include AccountType;

	def id
		return self.userid
	end

	def location
		return Locs.get_by_id(loc);
	end

	def anonymous?
		return false
	end
	
	# Does a memcache test.
	# Not equivalent to !anonymous.
	#TODO: fix this to take "remember me" into consideration and use constants not magic numbers
	def logged_in?
		return self.activetime > (Time.now.to_i - 600);  
	end
	
	def logout!
		time = Time.now.to_i

		self.useractivetime.online = false
		self.useractivetime.store;
		
		self.online = false
		self.timeonline = self.timeonline + (time - self.activetime)
		self.activetime = time
		
		self.usersearch.active = 1
		self.store;

		$site.memcache.store("useractive", userid, time - 600, 86400*7);
	end
	
	alias original_activetime activetime
	#If you want the most accurate activetime for a user use this call
	def activetime
		time = $site.memcache.load("useractive", userid, 86400*7) { |missing_keys|
			missing_keys.each_pair{|key, value|
				if (!self.useractivetime.nil?)
					missing_keys[key] = self.useractivetime.activetime
				else
					missing_keys[key] = self.original_activetime
				end
			}
		}
		return time.to_i
	end
	
	def refresh_active_status
		time = Time.now.to_i

		if (self.useractivetime.online)
			self.timeonline = self.timeonline + (time - self.activetime)
		end
		self.online = true
		self.usersearch.active = 0
		self.activetime = time;
		self.store;

		self.useractivetime.online = true
		self.useractivetime.time = time
		self.useractivetime.store;


		$site.memcache.store("useractive", userid, time, 86400*7);
	end
	
	def self.get_by_ids(userids)
		return User.find(:all, :conditions => ["userid IN #",userids]);
	end

	def self.get_by_id(userid)
		return User.find(:first, :promise, userid);
	end

	def self.get_by_name(username)
		user_name = UserName.by_name(username);
		if (user_name)
			return User.find(user_name.userid).first;
		else
			return nil;
		end
	end

	def self.get_by_email(email)
		user_email = UserEmail.by_email(email);
		if (user_email)
			return User.find(:first, user_email.userid);
		else
			return nil;
		end
	end

	def User.create(username, password, email, dob, sex, location, ip, needs_activation = true, needs_terms=true)
		userid = create_account()

		name = nil;
		emailobj = nil;
		
		# set up the username
		begin
			name = UserName.new()
			name.userid = userid
			name.username = username
			name.live = true

			name.store()
		rescue SqlBase::QueryError # make this more specific.
			name.delete() if !name.nil?;
			
			raise UserError, "That username already exists"
			
			return nil;
		end
		
		# set up the email
		begin
			emailobj = UserEmail.new()
			emailobj.userid = userid
			emailobj.active = !needs_activation;
			emailobj.email = email
			emailobj.key = "";
			emailobj.time = Time.now.to_i

			emailobj.store()
		rescue SqlBase::QueryError # make more specific
			raise UserError, "That email address already registered"
		
			return nil;
		end

		pass = nil;
		split_name = nil;
		profile = nil;
		user = nil;
		
		begin
			pass = Password.new()
			pass.userid = userid
			pass.change_password(password)
			pass.store()

			split_name = SplitUserName.new()
			split_name.userid = userid
			split_name.username = username
			split_name.store()

			profile = Profile::Profile.new()
			profile.userid = userid
			profile.store()

			user = User.new()
			user.userid = userid
			user.dob = dob.to_i

			age_in_seconds = Time.now.to_i - dob.to_i;
			age = (age_in_seconds / Constants::YEAR).to_i;
			user.age = age;
			user.loc = location
			user.sex = sex.to_s
			user.jointime = Time.now.to_i
			user.activetime = Time.now.to_i
			user.ip = ip;
			user.defaultsex = case sex.to_s
				when "Male" then "Female"
				when "Female" then "Male"
			end.to_s
			user.defaultminage = (age/2+7).floor
			user.defaultmaxage = (3*age/2-5).ceil
			user.state = "active" if !needs_activation
			user.termsversion = 1 if !needs_terms
			user.store()
			
			# This needs to come after the user is stored so that the account property has been loaded.
			needs_activation ? user.account.make_new! : user.account.make_active!;
			
			self.db.squery(user.userid, "UPDATE stats SET userstotal = userstotal + 1");
		rescue SqlBase::QueryError => sql_error # make more specific
			$log.info sql_error.to_s, :error;
			$log.info sql_error.backtrace, :error;
			
			name.delete() if !name.nil?;
			emailobj.delete() if !emailobj.nil?;
			pass.delete() if !pass.nil?;
			split_name.delete() if !split_name.nil?;
			profile.delete() if !profile.nil?;
			user.delete() if !user.nil?;
			
			raise UserError, "Unexpected error while creating user"
		
			return nil;
		end

		return user
	end


	def activated?
		if (!@email.nil? && @email.active && 
			self.state == 'active' && 
			!account.nil? && account.active?)

			# Note: Technically, checking only one of these conditions (email.active, user.state, account.active)
			# would allow us to tell whether the user is activated or not, but since all of them are supposed to
			# be set upon activation, the user would not be properly "activated" if only one condition was met.
			# Thus, activated? does a strict check for all of these.
			
			return true;
		else
			return false;
		end
	end

	def after_load()
		@email     = UserEmail.find(:first, :promise, :conditions => ["userid = # AND active = 'y'", userid])
		mediator = SkinMediator.instance
		skin_list = mediator.get_skin_list($site.config.page_skeleton)
		if (!skin_list.index(@skin))
			@skin = "newblue"
		end
	end

	def after_create()
		@email     = UserEmail.find(:first, :promise, :conditions => ["userid = # AND active = 'y'", userid])
		if (!SkinMediator.request_skin_list($site.config.page_skeleton).index(@skin))
			@skin = "newblue"
		end
	end

	def pronoun
		if (@sex.to_s === "Male")
			"he"
		else
			"she"
		end
	end

	
	def delete()
		delete_username = username_obj || UserName.find(:first, self.userid);
		delete_password = password || Password.find(:first, self.userid);
		delete_email = email || UserEmail.find(:first, self.userid);
		delete_splitname = SplitUserName.find(:first, self.userid);
		delete_profile = profile || Profile::Profile.find(:first, self.userid);
		delete_account_maps = AccountMap.find(:all, :conditions => ["accountid = ?", self.userid]);
		
		delete_username.delete() if !delete_username.nil?
		delete_password.delete() if !delete_password.nil?
		delete_email.delete() if !delete_email.nil?
		delete_splitname.delete() if !delete_splitname.nil?
		delete_profile.delete() if !delete_profile.nil?
		delete_account_maps.each { |map| map.delete() };
		
		super;
	end
	

	def possessive_pronoun
		if (@sex.to_s === "Male")
			"his"
		else
			"her"
		end
	end


	def objective_pronoun
		if (@sex.to_s === "Male")
			"him"
		else
			"her"
		end
	end

	def username()
		return username_obj.username;
	end

	def email()
		if (@email.nil?)
			return nil;
		end
		
		return @email.email;
	end

	def to_s
		return "User: " + userid.to_s;
	end

	def plus?
		return Time.now < Time.at(premiumexpiry);
	end

	# Returns a list of Account objects identifying what accounts this user is a
	# member of.
	def account_membership()
		mappings = AccountMap.find(:all, :accountid, userid);
		mappings = mappings.collect {|map| map.primaryid; }
		return Account.find(:all, *mappings);
	end

	def link
		return %Q|<a href="#{uri_info('php')[1]}">#{uri_info('php')[0]}</a>|	
	end
	
	def uri_info(mode = '')
		case mode
		when "php"
			return [self.username, "/profile.php?uid=#{self.userid}"];
		when ""
			return [self.username, $site.user_url/self.username]
		when "abuse"
			return ["Report Abuse", "/reportabuse.php?type=31&id=#{self.userid}"];
		end
		super(mode);
	end

	def visible?(user_or_id)
		if(user_or_id.kind_of?(User) || user_or_id.kind_of?(AnonymousUser))
			user_target = user_or_id;
		elsif(user_or_id.kind_of?(Integer))
			user_target = User.find(:first, user_or_id);
		else
			raise ArgumentError.new("Wrong type of argument: #{user_or_id.class}");
		end
		
		return _visible?(user_target);
	end
	
	def _visible?(user_target)
		#add ability for admin to always see user
		if(self.state == "frozen" || self.state == "deleted")
			return false;
		elsif(!self.hideprofile)
			return true;
		end
		
		if(self.hideprofile && (user_target.anonymous?() || self.ignored?(user_target)))
			return false;
		else
			return true;
		end
	end
	
	def ignored?(user_or_id)
		if(user_or_id.kind_of?(User) || user_or_id.kind_of?(AnonymousUser))
			user_target = user_or_id;
		elsif(user_or_id.kind_of?(Integer))
			user_target = User.find(:first, user_or_id);
		else
			raise ArgumentError.new("Wrong type of argument: #{user_or_id.class}");
		end
		
		return _ignored?(user_target);
	end
	
	def _ignored?(user_target)
		user_ignore = ignored_user_list.find {|row| row.userid == self.userid && row.ignoreid == user_target.userid }
		
		return !user_ignore.nil?();
	end
end
