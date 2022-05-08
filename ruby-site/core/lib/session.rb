lib_require :Core, "storable/storable", "users/user", "users/anonymous_user", "secure_form"
#require 'md5'
require 'cgi'

$cookies = Array[]

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
class Session < Storable;
	set_db(:usersdb);
	set_table("sessions");
	init_storable();

	attr :ip, true;
	attr :userid, true;
	attr :activetime, true;
	attr :sessionid, true;
	attr :cachedlogin, true;
	attr :lockip, true;
	attr :jstimezone, true;
	attr :user, true;
	attr :cookie, true;

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

		sessions = $site.cache.get(:sessions, :page) { Hash.new };
		return sessions[sessionkey] if sessions[sessionkey]

		key, userid = sessionkey.scan(/[^:]+/);

		session = Session.find(:first, :userid, userid.to_i, key);#:conditions => ['userid = ? AND sessionid = ?', userid, key]);
		if (session == nil)
			return nil;
		else
			key, userid = sessionkey.scan(/[^:]+/);
			session.user = User.find(:first, userid.to_i);
			session.cookie = sessionkey;
			sessions[sessionkey] = session
			return session;
		end
	end

	#Create a new session
	def Session.build(remote_addr, userid, cachedlogin)
		cookiedomain = $site.config.cookie_domain;

		expire = (cachedlogin ?
		Time.now + $site.config.long_session_timeout : Time.now + (60 * 60 * 24 * 30));  #cache for 1 month

		key = makeRandKey();
		cookie = CGI::Cookie.new('name' => "sessionkey",
								 'value' => "#{key}:#{userid}" ,
								 'expire' => expire,
								 'path' => '/',
								 'domain' => cookiedomain);

		session = Session.new();
		session.ip = remote_addr.to_s;
		session.userid = userid;
		session.user = User.get_by_id(userid);
		session.activetime = Time.now.to_i;
		session.sessionid = key;
		session.lockip = false;
		session.jstimezone = -360;
		session.cookie = cookie;
		session.store();
		return session;
	end

	def self.create_anonymous_session(ip)
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
		key, userid = cookie.scan(/[^:]+/);

		cookiedomain = $site.config.cookie_domain;

		ip = remote_addr.to_s;
		userid = userid.to_i;
		activetime = (Time.now - 1).to_i;
		sessionid = key;
		lockip = false;
		jstimezone = -360;

		delete();

		real_cookie = CGI::Cookie.new('name' => "sessionkey",
								 'value' => "#{sessionid}:#{userid}" ,
								 'expire' => Time.now - 1,
								 'path' => '/',
								 'domain' => cookiedomain);
		return real_cookie;
	end

	def has_priv?(mod, bit)
		return false if anonymous?

		priv = @priv_obj || @priv_obj = Privilege::Privilege.new(user);
		return priv.has?(mod, bit);
	end

end
