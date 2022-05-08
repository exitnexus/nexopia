lib_require :Core,  'storable/storable', 'privilege'

module Forum
	class Post < Storable
		extend TypeID
		init_storable(:forumdb, "forumposts");
		include Observations::Observable

		relation_singular :author, :authorid, User

		def owner
			return User.get_by_id(@authorid);
		end
		
		def thread
			return Thread.find(:first, @threadid);
		end
		
		observable_event :create, proc{"#{owner.link} posted in the thread '#{thread.link}'"}

    	def author_location
    		return self.author.location
    	end
    	
    	def formatted_time
    		return Time.at(self.time).strftime("%b %d, %y %I:%M %p")
    	end
    	
    	def ticket_notes
    		return "#{self.msg}";
    	end
	end
end
