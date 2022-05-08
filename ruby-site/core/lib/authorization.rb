lib_require :Core,  'users/user', 'users/useremails'

require 'singleton'
require 'cgi'

class Authorization
	include Singleton;

	WEEK_SECONDS = 86400*7;

	def initialize()

	end


	def key_matches(user, key)
		email = UserEmail.find(:first, user.userid);

		return email.key == key;
	end


	def reset(user, force=false)
		email = UserEmail.find(:first, user.userid);

		if (email.active && !force)
			return nil;
		end

		key = makeRandomActivationKey;

		new_email = UserEmail.new;
		new_email.userid = email.userid;
		new_email.email = email.email;
		new_email.active = false;
		new_email.key = key;
		new_email.time = Time.now.to_i;

		email.delete;
		new_email.store;

		return key;
	end


	def activate(user, key)
		if (user.nil?)
			return "Invalid username.";
		end
		
		email = UserEmail.find(:first, user.userid, :conditions => ["active = 'n'"]);
		
		if (email.nil?)
			return "This account has already been activated.";
		end
		
		if (email.key == key)
			if (email.time < (Time.now.to_i - WEEK_SECONDS))
				return "That activation key has expired. Have one resent.";
			else
				new_email = UserEmail.new;
				new_email.userid = email.userid;
				new_email.email = email.email;
				new_email.active = true;
				new_email.key = email.key;
				new_email.time = Time.now.to_i;

				email.delete;
				new_email.store;

				user.state = 'active';
				user.account.make_active!;
				user.store;

				return nil;
			end
		else
			return "The activation key did not match.";
		end
	end


	def auto_login(user=nil, session_ip=nil)
		userid = user.userid;
		
		# The domain needs to match the domain from which the cookie is being set. It also specifies
		# the domains the cookie will operate under. For example, if we set the cookie under "php.name",
		# the cookie will properly set when the page is accessed via "www.php.name" and will work under
		# "php.name" and all of its sub-domains. However, it would not work under a sub-domain of "name"
		# that is not really a sub-domain of "php.name". As well,
		cookiedomain = "." + $site.config.cookie_domain;

		# An expiration time of 1 month (in seconds)
		expire = Time.now + (60 * 60 * 24 * 30);

		key = makeRandomActivationKey();
		
		# Create the cookies needed by the PHP site.
		userid_cookie = CGI::Cookie.new(
			'name' => "userid",
			'value' => "#{userid}" ,
			'expires' => expire,
			'path' => '/',
			'domain' => cookiedomain);
		key_cookie = CGI::Cookie.new(
			'name' => "key",
			'value' => "#{key}" ,
			'expires' => expire,
			'path' => '/',
			'domain' => cookiedomain);
		
		# Create a new session. Along with the key contained in the cookie, this will authenticate the user 
		# in the PHP site.
		session = Session.new();
		session.ip = session_ip;
		session.userid = userid;
		session.activetime = Time.now.to_i;
		session.sessionid = key;
		session.lockip = false;
		session.jstimezone = -360;
		session.store();

		# We need to get the top PageRequest object to set the cookies on because the
		# sub-requests will not process headers in the same way (you can still set
		# direct header information, but the cookies will not get mixed in).
		PageRequest.top.reply.set_cookie(userid_cookie);
		PageRequest.top.reply.set_cookie(key_cookie);

		PageRequest.top.reply.send_cookies();
	end


	def check_key(id, key, myuserid = 0)
		return (key == make_key(id,myuserid));
	end


	def make_key(id, myuserid = 0)
		result = Digest::MD5.hexdigest("#{myuserid}:blah:#{id}")[0,10].upcase;

		return result;
	end


	def makeRandomActivationKey()
		# TODO: the original PHP code for generating this value is: md5(uniqid(rand(),1));
		# I cannot find an equivalent for "uniqid" in Ruby. Until then, the hash is just
		# based on a random number.

		return Digest::MD5.hexdigest("#{rand()}");
	end

end
