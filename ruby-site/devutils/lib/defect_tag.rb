require 'set'
lib_require :Core, 'users/user', 'storable/storable'


# DefectTag (Storable)
#
# Properties:
#		id: Automatically assigned upon DefectTag.new.
#		tag: The text that represents the tag.
#		userid: Represents the user who should be notified when Defects are given this tag.
#
# Virtual Properties:
#		username: (read-only) The name of the user identified by DefectTag.userid
class DefectTag < Storable
	
	init_storable(:taskdb, 'defecttag');

	relation_singular :user, :userid, User;
	
	
	class << self
		# Returns an Array of DefectTags
		def find_by_tag(tag)
			defect_tags = DefectTag.find(:all, :conditions => ["tag = ?", tag]);
			
			return defect_tags;
		end
		
		def users_for_tags(*tags)
			defect_tags = []
			defect_tags = DefectTag.find(:tag, *tags.flatten) unless tags.flatten.empty?
			users = {}
			defect_tags.each {|tag|
				users[tag.userid] = tag.user
			}
			return users
		end
	end
	
	
	def username()
		if (user.nil?)
			return "";
		end
		
		return user.username;
	end
	
end