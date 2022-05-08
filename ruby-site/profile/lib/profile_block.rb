lib_require :Core, "storable/storable"
lib_want :Observations, "observable"

module Profile
	class ProfileBlock < Storable
		init_storable(:usersdb, "profileblocks")
	
		if (site_module_loaded? :Observations)
			include Observations::Observable
			OBSERVABLE_NAME = "Profile Block"
	
			observable_event :create, proc{"#{owner.link} added a new section to #{owner.possessive_pronoun} profile entitled #{@blocktitle}"}
			observable_event :edit, proc{"#{owner.link} changed #{owner.possessive_pronoun} profile block entitled #{@blocktitle}"}
		end
		
		user_content :blockcontent;
		monitor_content :blockcontent, "body"
	
		def owner
			return User.get_by_id(@userid);
		end
	
	end
end