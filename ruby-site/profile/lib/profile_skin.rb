require 'set'
require 'uri'

lib_require :Core, 'users/user'

module Profile
	class ProfileSkin < Cacheable
		init_storable(:usersdb, 'profileskins')
		set_prefix("ruby_profileskin")
		#these are columns that do not include name=>value pairs for substitution in a user skin
		@@meta_columns = Set.new([:userid, :id, :name])
		
		relation_singular :user, :userid, User
		
		def before_update
			self.revision += 1
		end
		
		def header
			return "#{URI.escape(self.user.username)}/#{self.revision}/#{URI.escape(self.name)}"
		end
		
		def skin_variables
			hash = {}
			self.class.columns.each_key {|column_name|
				column_name = column_name.to_sym
				unless (@@meta_columns.member?(column_name))
					hash[column_name] = "##{self.send(column_name)}"
				end
			}
			return hash
		end
	end
end