###############################################################
# Discussion notes from 2/11/08 discussion regarding the need
# for form_key.
# 
# Participants: Nathan, Graham, Timo
# 
# Problem: Adding form_key to every post request adds non-trivial
# complexity to submitting posts (particulary via ajax).
#
# Solution proposals:
# 1. Eliminate form_key on post requests.
#    Pros: Simple to implement, simple to use.
#    Cons: Theoretical attack where someone sets up a page
#          that performs posts back to nexopia.  No way to
#          validate that they came from us.  Referrer checking
#          considered as a way of doing such validation but
#          belief is that ajax requests come with no referrer.
# 2. Eliminate path checking on form_keys.
#    Pros: Most of the complexity of using form keys comes from
#          crossing page handlers, this would remove that issue.
#    Cons: If you get the key for one page, you could submit to any
#          page.  This concern stems largely from social engineering
#          related attacks.
#    Extra Notes:  If this solution is chosen, a mechanism for
#          automatically inserting the form key in every page and
#          providing access to it via javascript would be desirable.
#          Much of the complexity from ajaxing posts is figuring 
#          out how to access the form_key and which form_key to use.
# 3. Suck it up and keep things as they are.
#    Pros: Most secure.
#    Cons: Annoying.

require 'digest/md5'
lib_require :Core, 'url'

class SecureForm

	KEY = '582f4a3f597466723838496a3e472374'
	KEY_DURATION = Constants::DAY_IN_SECONDS;

	# The standard key will be vaild for 24 hours.
	def self.encrypt(user, path = "/", time = Time.now + KEY_DURATION)
		return self.encrypt_string("#{user.userid}:#{time.to_i}:#{path}")
	end

	def self.encrypt_session(session, time=Time.now)
		data = "#{session.sessionid}:#{session.userid}"
		return self.encrypt_string(data)
	end

	def self.decrypt_session(data)
		$log.info data, :debug
		begin
			sessionkey = self.decrypt_string(data)
			session = Session.get(sessionkey)
			return session
		rescue
			$log.object $!, :error
			$log.info "SecureForm failed to load session data from cypher text.", :warning
			return nil
		end
	end

	def self.encrypt_string(string)
		$log.info("Encrypting #{string}", :debug)

		return Digest::MD5.hexdigest(KEY + string) + urlencode(string);
	end

	def self.decrypt_string(string)
		return "invalid:invalid" if(string.length < 33) #must be 32 bytes of hash, and at least 1 byte of other stuff

		hash = string[0...32];
		string = urldecode(string[32..-1]);

		$log.info("Decrypting #{string}", :debug)

		if(Digest::MD5.hexdigest(KEY + string) != hash)
			$log.info "Attempted to decrypt an invalid string.", :warning
			return "invalid:invalid"
		end

		return string
	end

	def self.decrypt(data)
		decrypted = self.decrypt_string(data).split(":")
		decrypted[0] = decrypted[0].to_i
		decrypted[1] = decrypted[1].to_i
		return decrypted
	end

	def self.validate_key(uid, key, path=nil)
		key_uid, key_time, key_path = self.decrypt(key);
		
		# key is valid if the given uid matches the uid in the key
		# AND if the key hasn't expired.
		result = (uid == key_uid && (Time.now.to_i <= key_time))
		
		# AND if the path in the key matches part of the given path 
		if (!key_path.nil? && !path.nil?)
			result &&= path.index(key_path) == 0
		end
		
		if (!result)
#			 $log.info("SECURE FORM: KEY DOESN'T MATCH: #{uid} == #{key_uid} && (#{Time.now.to_i} <= #{key_time}) && #{key_path} prefix of #{path}", :warning);
		else
#			 $log.info("SECURE FORM: MATCH FOUND #{uid} == #{key_uid} && (#{Time.now.to_i} <= #{key_time}) && #{key_path} prefix of #{path}", :debug);
		end
		
		return result
	end
end
