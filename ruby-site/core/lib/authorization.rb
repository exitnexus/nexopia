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
		active_email = UserEmail.find(:first, user.userid, true)
		nonactive_email = UserEmail.find(:first, user.userid, false)
		
		if (nonactive_email.nil? && !force)
			return nil
		end

		email = active_email.nil? ? nonactive_email : active_email

		key = makeRandomActivationKey

		new_email = UserEmail.new
		new_email.userid = email.userid
		new_email.email = email.email
		new_email.active = false
		new_email.key = key
		new_email.time = Time.now.to_i

		active_email.delete if !active_email.nil?
		nonactive_email.delete if !nonactive_email.nil?

		new_email.store;

		return key;
	end


	def activate(user, key)
		if (user.nil?)
			return "Invalid username."
		end
		
		email = UserEmail.find(:first, user.userid, :conditions => ["active = 'n'"])
		
		if (email.nil?)
			return "This account has already been activated."
		end
		
		if (email.key == key.strip)
			if (email.time < (Time.now.to_i - WEEK_SECONDS))
				return "That activation key has expired. Have one resent."
			else
				# If there's an active email at this point, we need to remove it.
				old_active_email = UserEmail.find(:first, user.userid, true)
				if (old_active_email)
					old_active_email.delete
				end
				
				new_email = UserEmail.new
				new_email.userid = email.userid
				new_email.email = email.email
				new_email.active = true
				new_email.key = makeRandomActivationKey
				new_email.time = Time.now.to_i

				email.delete
				new_email.store

				user.state = 'active'
				user.account.make_active!
				user.store

				return nil
			end
		else
			$log.info "User #{user.userid} supplied activation key '#{key}', which did not match stored key of '#{email.key}'.", :warning
			return "The activation key did not match."
		end
	end

	def check_key(id, key, myuserid = 0)
		return (key == make_key(id,myuserid));
	end


	def make_key(id, myuserid = 0)
		myuserid = PageRequest.current.session.user.userid if myuserid == 0;
		
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
