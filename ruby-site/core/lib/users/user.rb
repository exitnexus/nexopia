require 'iconv'
lib_require :Core,  'authorization'
lib_require :Core,  'storable/storable'
lib_require :Core,  'accounts'
lib_require :Core,  'constants'
lib_require :Core,  'users/user_name'
lib_require :Core,  'users/interests'
lib_require :Core,  'users/locs'
lib_require :Core,  'users/useremails'
lib_require :Core,  'users/userpassword'
lib_require :Core,  'sql'
lib_require :Core,  'storable/cacheable'
lib_require :Core,	'users/user_ignore'
lib_require :Core,  'user_error'
lib_require :Core,  'skin_mediator'
lib_require :Core, 	'visibility'
lib_require :Core,	'time_format'
lib_require :Core, 'users/deleted_user'
lib_want :UserDump, "dumpable"

class UserActiveTime < Storable
	init_storable(:usersdb, "useractivetime");
end

class User < Cacheable
	set_enums(:gallerymenuaccess => Visibility.instance.visibility_list,
		:blogsmenuaccess => Visibility.instance.visibility_list,
		:commentsmenuaccess => Visibility.instance.visibility_list
	);

	init_storable(:usersdb, "users");
	attr_reader :interests, :profile, :password, :galleries, :account;
	attr_accessor :primed_activetime, :email;
	set_prefix("ruby_userinfo")
	
	relation :singular, :useractivetime, :userid, UserActiveTime
	relation :multi, :interests, :userid, UserInterests
	relation :multi, :ignored_user_list, :userid, UserIgnore

	relation :singular, :username_obj, :userid, UserName
	relation :singular, :password, :userid, Password
	relation :singular, :account, :userid, Account

	cache_extra_column(:username, lambda{ self.username_obj;promise {self.username_obj.nil?() ? nil : self.username_obj.username} }, lambda{|name| @username = name})
	register_selection(:minimal, :userid, :firstpic, :age, :sex, :loc, :activetime, :premiumexpiry)

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
	
	def ==(obj)
		return obj.kind_of?(User) && obj.userid == self.userid
	end
	
	# Does a memcache test.
	# Not equivalent to !anonymous.
	#TODO: fix this to take "remember me" into consideration and use constants not magic numbers
	def logged_in?
		curr_time = Time.now.to_i();
		
		if(@primed_activetime.nil?())
			active_time = $site.memcache.get("useractive-#{self.userid}");
		else
			active_time = @primed_activetime;
		end
		
		if(active_time.nil?())
			return false;
		end

		@primed_activetime = active_time;
		active_time = active_time.to_i();
		
		if(active_time > curr_time - $site.config.session_active_timeout)
			return true;
		else
			return false;
		end
	end
	
	def logout!
		time = Time.now.to_i

		self.useractivetime.online = false
		self.useractivetime.store;
		
		self.online = false
		self.timeonline = self.timeonline + (time - self.activetime)
		self.activetime = time
		
		self.db.query("UPDATE usersearch SET active = 1 WHERE userid = #", self.userid);

		$site.memcache.store("useractive", userid, time - 600, 86400*7);
	end
	
	alias original_activetime activetime
	#If you want the most accurate activetime for a user use this call
	def activetime(force_non_zero = false)
		if(!@primed_activetime.nil?())
			t = @primed_activetime.to_i();
			if((!force_non_zero && t == 0) || (force_non_zero && t > 0))
				return t;
			end
		end
		active_time_miss = false;
		
		time = $site.memcache.load("useractive", userid, 86400*7) { |missing_keys|
			active_time_miss = true;
			missing_keys.each_pair{|key, value|
				if (!self.useractivetime.nil?)
					missing_keys[key] = self.useractivetime.activetime.to_i
				else
					missing_keys[key] = self.original_activetime.to_i || 0
				end
			}
		}
		
		if(force_non_zero && time.to_i() == 0 && !active_time_miss && !self.useractivetime.nil?())
			time = self.useractivetime.activetime.to_i();
			$site.memcache.set("useractive-#{self.userid}", time, 86400*7);
		end
		
		@primed_activetime = time.to_i();
		return @primed_activetime;
	end
	
	# This function is used by pages to grab the activetime for groups of
	#  users. This turns getting activetime from n memcache queries to 1.
	def self.prime_user_activetime(user_list)
		user_list.delete_if{|user| user.anonymous?}
		user_id_list = user_list.map{|user| 
			[user.userid]
		};
		
		if(user_list.kind_of?(StorableResult))
			user_hash = user_list.to_hash();
		elsif(user_list.kind_of?(Array))
			user_hash = Hash.new();
			user_list.each{|user| 
				user_hash[[user.get_primary_key()]] = user;
			};
		end	

		active_time_list = $site.memcache.load("useractive", user_id_list, 86400*7) { |missing_keys|
			missing_keys.each_pair{|key, value|
				missing_keys[key] = 0;
			}
		}

		user_hash.each_pair{|user_id, user|
			user.primed_activetime = active_time_list["useractive-#{user_id}"];
		};
	end
	
	def self.get_all_activetime(user_id_list)
		active_time_list =  $site.memcache.load("useractive", user_id_list, 86400*7) { |missing_keys|
			missing_keys.each_pair{|key, value|
				missing_keys[key] = 0;
			}
		}
		
		return active_time_list;
	end
	
	def refresh_active_status
		time = Time.now.to_i();

		user_active_time = UserActiveTime.new();
		
		user_active_time.userid = self.userid;
		user_active_time.online = true;

		user_active_time.ip = PageRequest.current.get_ip_as_int();
		user_active_time.activetime = time;
		user_active_time.store(:duplicate);

		self.db.query("UPDATE usersearch SET active = 2 WHERE userid = #", self.userid);

		$site.memcache.set("useractive-#{self.userid}", time, 86400*7);
	end
	
	def self.get_by_ids(userids)
		return User.find(:all, :conditions => ["userid IN #",userids]);
	end

	def self.get_by_id(userid)
		if(!userid.kind_of?(Integer))
			userid = userid.to_i();
		end
		
		return User.find(:first, :promise, userid);
	end

	# Get a user by username.
	# If we set handle_encoding to true, we take care of UTF-8 to
	# WINDOWS-1252 encoding issues, dealing with Ruby to MySQL.
	# Perhaps one day, we'll move our databases to unicode and then
	# we wouldn't need to care about this.
	def self.get_by_name(username, handle_encoding = false)
		user_name = UserName.by_name(username);
		if (handle_encoding && user_name.nil?)
			# Try changing encoding 
			begin
				encoded = Iconv.new('WINDOWS-1252', 'UTF-8').iconv(username)
				user_name = UserName.by_name(encoded)
			rescue
				# Reencoding failed, nothing more we can do
			end
		end

		if (user_name)
			return User.find(:first, user_name.userid);
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

			user = User.new()
			user.userid = userid
			user.dob = dob.to_i

			user.age = user.calculate_age(user.dob)
			user.loc = location
			user.sex = sex.to_s
			user.jointime = Time.now.to_i
			user.activetime = Time.now.to_i
			user.ip = ip;
			user.defaultsex = case sex.to_s
				when "Male" then "Female"
				when "Female" then "Male"
			end.to_s
			user.defaultminage = (user.age/2+7).floor
			user.defaultmaxage = (3*user.age/2-5).ceil
			user.state = "active" if !needs_activation
			user.termsversion = 1 if !needs_terms
			user.store()
			
			# This needs to come after the user is stored so that the account property has been loaded.
			needs_activation ? user.account.make_new! : user.account.make_active!
			
			self.db.squery(user.userid, "UPDATE stats SET userstotal = userstotal + 1")
		rescue SqlBase::QueryError => sql_error # make more specific
			$log.info sql_error.to_s, :error;
			$log.info sql_error.backtrace, :error;
			
			name.delete() if !name.nil?;
			emailobj.delete() if !emailobj.nil?;
			pass.delete() if !pass.nil?;
			split_name.delete() if !split_name.nil?;
			user.delete() if !user.nil?;
			
			raise UserError, "Unexpected error while creating user"
		
			return nil;
		end

		return user
	end

	
	def deleted?
		return self.state == 'deleted'
	end


	def activated?
		if (self.state != 'new' && 
			!account.nil? && (account.active? || account.frozen?))

			# Note: We don't check if the useremail record has active set to 'y' because anyone can request
			# a reactivation and we don't want the request itself to affect a user who has already activated
			
			return true;
		else
			return false;
		end
	end

	def after_load()
		# This will ensure that if someone has done a reactivation at some point, but not confirmed, that
		# we'll at least get an email to send a message to, but that we'll attempt to get one marked active
		# before we try to get another.
		@email     = UserEmail.find(:first, :promise, :conditions => ["userid = #", userid], :order => "active = 'y' DESC")
		orig_skin = @skin
		@skin = promise {
			mediator = SkinMediator.instance
			skin_list = mediator.get_skin_list($site.config.page_skeleton)
			if (skin_list.index(orig_skin))
				orig_skin
			else
				"newblack"
			end
		}
		@privileges = promise{ Privilege::Privilege.new(self) }
	end

	def after_create()
		@email     = UserEmail.find(:first, :promise, :conditions => ["userid = # AND active = 'y'", userid])
		if (!SkinMediator.request_skin_list($site.config.page_skeleton).index(@skin))
			@skin = "newblack"
		end
		@privileges = promise{ Privilege::Privilege.new(self) }
	end

	def pronoun
		if (@sex.to_s === "Male")
			"he"
		else
			"she"
		end
	end

	
	def delete()
		update_username = username_obj || UserName.find(:first, self.userid);
		delete_password = password || Password.find(:first, self.userid);
		delete_email = email || UserEmail.find(:first, self.userid);
		delete_splitname = SplitUserName.find(:first, self.userid);
		delete_profile = profile || Profile::Profile.find(:first, self.userid);
		delete_account_maps = AccountMap.find(:all, :conditions => ["accountid = ?", self.userid]);
		
		update_username.live = nil;
		update_username.store;
		
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
		@username ||= username_obj.username;
		return @username
	end

	def username_escaped()
		return CGI::escape(self.username.to_s)
	end

	def email()
		if (@email.nil?)
			return nil;
		end
		
		return @email.email;
	end

	def plus?
		return Time.now < Time.at(self.premiumexpiry);
	end

	def verified?
		return self.signpic;
	end
		
	# Generate an authorization key for an operation associated with this user.
	def gen_auth_key
		return Authorization.instance.make_key(self.userid);
	end
	

	# Returns a list of Account objects identifying what accounts this user is a
	# member of.
	def account_membership()
		mappings = AccountMap.find(:all, :accountid, userid);
		mappings = mappings.collect {|map| map.primaryid; }
		return Account.find(:all, *mappings) if mappings.length > 0
		return []
	end

	def link
		return %Q|<a href="#{uri_info('php')[1]}">#{uri_info('php')[0]}</a>|	
	end
	
	def uri_info(mode = '')
		case mode
		when "", "php"
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
		if(self.frozen? || self.state == "deleted")
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
	
	def ignore(user_or_id)
		if(user_or_id.kind_of?(AnonymousUser) || user_or_id.kind_of?(DeletedUser))
			return;
		elsif(user_or_id.kind_of?(User))
			user_target = user_or_id;
		elsif(user_or_id.kind_of?(Integer))
			user_target = User.find(:first, user_or_id);
		else
			raise ArgumentError.new("Wrong type of argument: #{user_or_id.class}");
		end
		
		_ignore(user_target)
	end
	
	def _ignore(user)
		user_ignore = UserIgnore.new
		user_ignore.userid = self.userid
		user_ignore.ignoreid = user.userid
		user_ignore.store(:ignore)
		self.ignored_user_list << user
	end
	
	def unignore(user_or_id)
		if(user_or_id.kind_of?(AnonymousUser) || user_or_id.kind_of?(DeletedUser))
			return;
		elsif(user_or_id.kind_of?(User))
			user_target = user_or_id;
		elsif(user_or_id.kind_of?(Integer))
			user_target = User.find(:first, user_or_id);
		else
			raise ArgumentError.new("Wrong type of argument: #{user_or_id.class}");
		end

		_unignore(user_target)
	end
	
	def _unignore(user)
		ignore_proxy = UserIgnore::StorableProxy.new(
			{
				:userid=>self.userid,
				:ignoreid=>user.userid
			})
		ignore_proxy.delete
		
		self.ignored_user_list.delete(user)
	end
	
	def ignored?(user_or_id)
		if(user_or_id.kind_of?(AnonymousUser) || user_or_id.kind_of?(DeletedUser))
			return false;
		elsif(user_or_id.kind_of?(User))
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
	
	def calculate_age(dob_msec)		
		dob = Time.at(dob_msec)		
		current_date = Time.now
		
		age = current_date.year - dob.year
		
		if (dob.month > current_date.month || (dob.month == current_date.month && dob.day > current_date.day))
			age = age - 1
		end
		
		return age
	end
	
	def remaining_plus_days()
		rem_plus = self.premiumexpiry - Time.now.to_i();
		
		if(rem_plus <= 0)
			return "0";
		end
		
		return sprintf("%.2f", rem_plus/86400.to_f());
	end
	
	def plus_expiry_date()
		return TimeFormat.short_date(self.premiumexpiry);
	end
	
	def frozen?()
		# We should stop checking state == "frozen" pretty much everywhere other than here. Instead,
		# use this method, which will correct state == "frozen" if the user's freeze has expired.
		if (self.state == "frozen" && self.frozentime != 0 && self.frozentime < Time.now.to_i)
			self.state = "active"
			self.store
		end
		
		return self.state == "frozen";
	end
	
	def ignore_section_by_age?(section)
		mod_section = section.to_s().downcase();
		
		if(mod_section == "messages")
			mod_section = "msgs";
		end
		
		if(self.ignorebyage == "both" || self.ignorebyage == mod_section)
			return true;
		end
		
		return false;		
	end
	
	def friends_only?(section)
		mod_section = section.to_s().downcase();
		
		if(mod_section == "messages")
			mod_section = "msgs";
		end
		
		if(self.onlyfriends == "both" || self.onlyfriends == mod_section)
			return true;
		end
		
		return false;
	end

	if (site_module_loaded?(:UserDump))
	  extend Dumpable
	  
		def self.user_dump(user_id, start_time = 0, end_time = Time.now)
		  user = self.get_by_id(user_id)
		  out = "User ##{user_id}\n"
		  out += "User name: #{user.username}\n"
		  out += "Account state: #{user.state}\n"
		  out += "Joined: #{Time.at(user.jointime).gmtime.to_s}\n"
		  out += "Last Active: #{Time.at(user.activetime).gmtime.to_s}\n"
		  out += "Frozen: #{Time.at(user.frozentime).gmtime.to_s}\n" if user.frozentime != 0
		  daysonline = user.timeonline / (24 * 60 * 60)
		  hoursonline = user.timeonline / (60 * 60)- (24 * daysonline)
		  minsonline = user.timeonline / 60 - (60 * hoursonline + 24 * 60 * daysonline)
		  out += "Total time spent online: #{daysonline} days #{hoursonline} hours #{minsonline} minutes\n"
		  out += "IP: #{Session.int_to_ip_addr(user.ip)}\n"
		  out += "Birthdate: #{Time.at(user.dob).gmtime.strftime('%b %d %Y')}\n"
		  out += "Age: #{user.age}\n"
		  out += "Sex: #{user.sex}\n"
		  out += "Location: #{user.location}\n"
		  # out += "First name: #{user.firstname}\n"
		  # out += "Last name: #{user.lastname}\n"
	  
		  return Dumpable.str_to_file("#{user_id}-user.txt", out)
	  end
  end
end
