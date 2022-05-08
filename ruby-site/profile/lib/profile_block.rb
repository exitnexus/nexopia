lib_require :Profile, 'profile'
lib_require :Core, "storable/cacheable"
lib_want :GoogleProfile, "google_user"
lib_want :UserDump, "dumpable"

module Profile
	class ProfileBlock < Cacheable
		
		init_storable(:usersdb, "profileblocks")

		relation :singular, :profile, [:userid], Profile
		
		user_content :blockcontent;#, :wrap => false;
		monitor_content :blockcontent, "body"
	
		def owner
			return User.get_by_id(@userid);
		end

		def after_create
			self.profile.update!
			if (site_module_loaded?(:GoogleProfile))
				self.owner.update_hash
			end
			super
		end

		def after_update
			self.profile.update!
			if (site_module_loaded?(:GoogleProfile))
				self.owner.update_hash
			end
			super
		end

		def before_delete
			self.profile.update!
			if (site_module_loaded?(:GoogleProfile))
				self.owner.update_hash
			end
		end
		
		def ProfileBlock.max_length(user)
			if(user.plus?())
				return 20000;
			end
			return 10000;
		end
		
		def ProfileBlock.max_number(user)
			if(user.plus?())
				return 6;
			end
			
			return 3;
		end
		
		if (site_module_loaded?(:UserDump))
		  extend Dumpable
		  
  		def self.user_dump(user_id, start_time = 0, end_time = Time.now())
  		  out = ""
  		  profile_blocks = ProfileBlock.find(:all, user_id)
  		  profile_blocks.each { |block|
			out += "--------------------------------------------------------------------------------\n"
  		    out += "Title: #{block.blocktitle}\n"
			out += "--------------------------------------------------------------------------------\n"
  		    out += "#{block.blockcontent}\n"
			out += "--------------------------------------------------------------------------------\n"
  		  }
		  
  		  return Dumpable.str_to_file("#{user_id}-profile_blocks.txt", out)
  	  end
    end
	end

	class Profile < Cacheable
		relation :multi, :blocks, [:userid], ProfileBlock
	end
end