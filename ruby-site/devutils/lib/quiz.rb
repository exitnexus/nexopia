=begin
require 'test/unit'
lib_require :Devutils, 'defect_tag'
class Quiz < Test::Unit::TestCase
	DEFAULT_TAGS = []
	
	class << self
		def tags
			return DEFAULT_TAGS
		end
		
		def tags=(new_tags)
			class_attr(:tags, true)
			self.tags = new_tags
		end
		
		def emails
			users = DefectTag.users_for_tags(self.tags)
			emails = [];
			users.each_pair {|uid, user|
				emails << user.email
			}
			return emails
		end
		
		def inherited(subclass)
			subclass.tags = DEFAULT_TAGS
		end
	end
end
=end