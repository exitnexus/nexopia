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

require 'crypt/rijndael'

class SecureForm

	AES_KEY = '582f4a3f597466723838496a3e472374'
	KEY_DURATION = 86400;
	
	@@aes = Crypt::Rijndael.new(AES_KEY)
	
	def self.encrypt(user, time=Time.now, path="/")
		return self.encrypt_string("#{user.userid}:#{time.to_i}:#{path}")
	end

	def self.encrypt_session(session, time=Time.now)
		data = Marshal.dump([session, time.to_i])
		return self.encrypt_string(data)
	end
	
	def self.decrypt_session(data)
		begin
			marshaled = self.decrypt_string(data)
			session, time_stamp = Marshal.load(marshaled)
			if (time_stamp + KEY_DURATION >= Time.now.to_i)
				return session
			else
				return nil
			end
		rescue
			$log.info "SecureForm failed to load session data from cypher text.", :warning
			return nil
		end
	end

	def self.encrypt_string(string)
		encrypted_blocks = (0...(string.length.to_f/16).ceil).map {|i|
			self.encrypt_block(string.slice(i*16,16))
		}
		return encrypted_blocks.join('')
	end

	def self.decrypt_string(string)
		#check that we can even try to decrypt it
		if (string.length%32 != 0)
			return "invalid:invalid"
		end
		decrypted_blocks = (0...(string.length.to_f/32).ceil).map {|i|
			self.decrypt_block(string.slice(i*32,32))
		}
		result = decrypted_blocks.join('')
		#remove null characters from the end (used as padding)
		while (result.chomp!("\000"))
			#keep chomping
		end
		return result
	end
	
	
	def self.encrypt_block(block)
		while (block.length < 16)
			block << "\000"
		end
		return @@aes.encrypt_block(block).unpack("H*").first
	end
	
	def self.decrypt(data)
		decrypted = self.decrypt_string(data).split(":")
		decrypted[0] = decrypted[0].to_i
		decrypted[1] = decrypted[1].to_i
		return decrypted
	end

	def self.decrypt_block(data)
		data = [*data].pack("H*")
		begin 
			buffer = @@aes.decrypt_block(data)
		rescue
			$log.object "SecureForm: " + $!, :warning
			buffer = "invalid:invalid"
		end
		return buffer
	end
	
	def self.validate_key(uid, key, path=nil)
		key_uid, key_time, key_path = self.decrypt(key);
		result = (uid == key_uid && (Time.now.to_i - key_time) < KEY_DURATION)
		if (!key_path.nil? && !path.nil?)
			result &&= path.index(key_path) == 0
		end
		#$log.info("#{uid} == #{key_uid} && #{(Time.now.to_i - key_time)} < #{KEY_DURATION}")
		return result
	end
end