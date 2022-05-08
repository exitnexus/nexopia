require 'digest/md5';

class Password < Storable;
	init_storable(:usersdb, "userpasswords");

	@@salt = "<removed>"; #random string from random.org

	def Password.check_password(passwd, userid)		
		hash      = Password.find(:first, userid).password;
		calc_hash = Digest::MD5.new.update( @@salt + passwd).to_s;

		return (hash == calc_hash);
	end
	
	def change_password(passwd)
        self.password = Digest::MD5.new.update( @@salt + passwd).to_s
        store
	end
end
