lib_require :Core, "storable/storable"

#This class is used to determine whether or not a blog post should be collapsed for a viewer.
#If an object is found for the userid, bloguserid, postid, then the post should be collapsed.
module Blogs
	class BlogNavigation < Cacheable
		init_storable(:usersdb, "blognavigation");
		
		class << self
			def find_for_user(userid, *keys)
				navigation_key_list = []
				keys.each { |key|
					navigation_key_list << [userid, key[0], key[1]]
				};
				
				return BlogNavigation.find(*navigation_key_list)
			end
		end	
	end
end