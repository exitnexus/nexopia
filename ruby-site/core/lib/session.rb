lib_require :Core, "storable/storable", "users/user", "users/anonymous_user", "secure_form"
#require 'md5'
require 'cgi'

#Obviously needs to be improved.
def makeRandKey
	@@chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	str = "."*32;
	for i in (0...32)
		str[i] = @@chars[(rand()*62).to_i];
	end
	return str;
end

#Represents a session.  Backed by cookies and the DB.
#To check for an existing session, run Session.get().
class Session < Cacheable
	attr :user, true;
	attr :cookie, true;
	
	set_db(:usersdb);
	set_table("sessions");
	init_storable();

	def to_s
		return "Session: " + userid.to_s + ":" + sessionid.to_s;
	end

	def anonymous?
		return self.user.anonymous?
	end

	#Get the existing session (if any) from the user's cookie. The sessionkey is
	#expected to be a string, not a CGI::Cookie.
	def Session.get(sessionkey)
		if (!sessionkey)
			return nil;
		end

		begin
			time = Time.now.to_i();
			request_ip = PageRequest.current.get_ip_as_int();
			memcache_store_session = false;
			
			sessions = $site.cache.get(:sessions, :page) { Hash.new };
	
			key, user_id = sessionkey.scan(/[^:]+/);
			user_id = user_id.to_i();
			
			session = sessions[sessionkey];
			
			if(session.nil?())
				session = $site.memcache.get("ruby_session-#{user_id}/#{key}")
			else
				return session;
			end
			
			if (session.nil?() && user_id > 0 && !key.nil?())
				session = Session.find(:first, :userid, user_id, key);
				if(!session.nil?())
					memcache_store_session = true;
				end
			end
			
			#bad session
			if (session.nil?())
				temp = Session.new();
				temp.cookie = sessionkey;
				expired_cookie = temp.destroy(request_ip);
				if(!expired_cookie.nil?())
					PageRequest.current.reply.set_cookie(expired_cookie);
				end
				
				return nil;
			end
			
			session.user = User.get_by_id(user_id);
			session.cookie = PageRequest.current.cookies["sessionkey"];
			
			if(session.user.frozen?())
				expired_cookie = session.destroy(request_ip);
				if(!expired_cookie.nil?())
					PageRequest.current.reply.set_cookie(expired_cookie);
				end
			end
			
			#bad or expired session
			if( (!session.cachedlogin && (session.activetime < (time - $site.config.session_timeout))) ||
						(session.lockip && (request_ip & 0xFFFFFF00) != (session.ip & 0xFFFFFF00) ) )
				expired_cookie = session.destroy(request_ip);
				if(!expired_cookie.nil?())
					PageRequest.current.reply.set_cookie(expired_cookie);
				end
				
				return nil;
			end
	
			if(session.ip != request_ip || session.activetime < time - $site.config.session_active_timeout)
				db.query("UPDATE sessions SET activetime = ?, ip = ? WHERE userid = # AND sessionid = ?", time, request_ip, user_id, key)
				session.activetime = time;
				session.ip = request_ip;
				memcache_store_session = true;
			end
	
			if(session.activetime < time - 120 || memcache_store_session)
				session.activetime = time;
				$site.memcache.set("ruby_session-#{user_id}/#{key}", session, $site.config.session_timeout);
			end
	
			if(session.user.nil?())
				$log.info("Session was found with nil user(#{session.userid})", :warning);
				return nil;
			end
			
			session.user.refresh_active_status();
			sessions[sessionkey] = session;
			return session;
		rescue
			$log.error;
			return nil;
		end
	end

	#Create a new session
	def Session.build(remote_addr, userid, cachedlogin, lockip = false)
		if(!userid.kind_of?(Integer))
			userid = userid.to_i();
		end
		
		cookiedomain = $site.config.cookie_domain;

		expire = (cachedlogin ?
		Time.now + $site.config.long_session_timeout : Time.now + (60 * 60 * 24 * 30));  #cache for 1 month
		key = makeRandKey();
		cookie = CGI::Cookie.new('name' => "sessionkey",
								 'value' => "#{key}:#{userid}" ,
								 'expires' => expire,
								 'path' => '/',
								 'domain' => cookiedomain);

		session = Session.new();
		session.ip = remote_addr.to_s;
		session.userid = userid;
		session.user = User.get_by_id(userid);
		session.activetime = Time.now.to_i;
		session.sessionid = key;
		session.cachedlogin = cachedlogin;
		session.lockip = lockip;
		session.jstimezone = -360;
		session.cookie = cookie;
		session.store();
		
		return session;
	end

	def self.create_anonymous_session(ip)
		int_ip = PageRequest.current.get_ip_as_int();
		#This is needed for guest counts
		anon_session = $site.memcache.get("anon-session-#{int_ip}");
		
		if(!anon_session)
			anon_count_id = (Time.now.to_i()/($site.config.session_active_timeout.to_f()/$site.config.guest_buckets.to_f())).floor();
			$site.memcache.set("anon-session-#{int_ip}", 1, $site.config.session_active_timeout);
			guest_incr = $site.memcache.incr("guest-count-#{anon_count_id}");
			if(guest_incr.nil?())
				$site.memcache.set("guest-count-#{anon_count_id}", 1, $site.config.session_active_timeout)
			end
		end
		
		user = AnonymousUser.new(ip)
		session = Session.new();
		session.ip = ip
		session.userid = user.userid
		session.user = user
		session.activetime = Time.now.to_i;
		session.sessionid = "anonymous"
		session.lockip = false;
		session.jstimezone = -360;
		return session;
	end

	def encrypt(time=Time.now)
		return SecureForm.encrypt_session(self, time)
	end

	def self.decrypt(data)
		return SecureForm.decrypt_session(data)
	end

	#Delete existing session.
	def destroy(remote_addr)
		if (!cookie)
			return nil;
		end

		# We sometimes set the cookie as just the value string of the actual cookie
		# because we have the value string and don't have the CGI::Cookie object. 
		# Constructing a new CGI::Cookie is unnecessary since the destroy function
		# is going to construct a new CGI::Cookie object anyways. 
		if(cookie.kind_of?(String))
			key, cookie_userid = cookie.scan(/[^:]+/);
		else
			key, cookie_userid = cookie.value.first.scan(/[^:]+/);
		end
		
		cookiedomain = $site.config.cookie_domain;

		@ip = remote_addr.to_s;
		@userid = cookie_userid.to_i;
		@sessionid = key;
		
		if(!@userid.nil?() && @userid > 0)
			$site.memcache.delete("session-#{@userid}-#{@sessionid}");
			$site.memcache.delete("ruby_session-#{@userid}/#{@cookie_userid}");
			
			self.db.query("DELETE FROM `sessions` WHERE `userid` = # AND `sessionid` = ?", @userid, @sessionid);
		end

		real_cookie = CGI::Cookie.new('name' => "sessionkey",
								 'value' => "0:0",
								 'expires' => Time.at(0),
								 'path' => '/',
								 'domain' => cookiedomain);

		return real_cookie;
	end

	def has_priv?(mod, bit)
		return false if anonymous?

		priv = @priv_obj || @priv_obj = Privilege::Privilege.new(user);
		return priv.has?(mod, bit);
	end
	
	
	def admin?(mod=CoreModule,type="")
		 return !self.anonymous? && self.lockip && user.admin?(mod,type);
	end

  # Utility function to convert an integer IP to a printable IP address
  def Session.int_to_ip_addr(ip_int)
	  return [24, 16, 8, 0].collect {|b| (ip_int >> b) & 255}.join('.')
  end
end
