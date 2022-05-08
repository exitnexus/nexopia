lib_require :Core, "storable/storable";

module Blogs
	class BlogComment < Cacheable
		extend TypeID;
		attr_reader :username

		init_storable(:usersdb, "blogcomments");
		
		relation :singular, :db_author, [:userid], User;
		relation :singular, :user, [:bloguserid], User;
		
		user_content(:msg);
		
		COMMENTS_PER_PAGE = 50;
		
		attr_accessor :child_nodes, :descendant_node_count, :displayed, :parent_node, :descendant_displayed;
		
		def initialize(*args)
			super(*args);
			self.child_nodes = Array.new();
			self.descendant_node_count = 0;
			self.displayed = false;
			self.descendant_displayed = false;
		end
		
		def <=>(anOther)
			if(!anOther.kind_of?(BlogComment))
				raise(ArgumentError.new("#{anOther.class} is not compatible with BlogComment"));
			end
			
			#$log.info("Looking at #{anOther.id} against #{self.id}")
			
			if(anOther.rootid < self.rootid)
				return 1;
			elsif(anOther.rootid > self.rootid)
				return -1;
			else	
				if(anOther.parentid < self.parentid)
					return 1;
				elsif(anOther.parentid > self.parentid)
					return -1;
				else
					if(anOther.time < self.time)
						return 1;
					else
						return -1;
					end
				end
			end
		end
		
		# We need to wrap the author relation because the author of a comment might be a deleted user. If so,
		#  the relation will return nil, at that point we will try to find the deleted user. If we can't we'll
		#  just use a placeholder.
		def author
			if (db_author.nil?)
				return DeletedUser.find(:first, self.userid) || DeletedUser.new();
			end
			return db_author;
		end
		
		def increment_descendant_count()
			self.descendant_node_count += 1;
			if(!parent_node.nil?())
				parent_node.increment_descendant_count();
			end
		end
		
		def show_wrapper()
			if(self.displayed())
				return true;
			end
			
			return self.descendant_displayed;
		end
		
			# A comment can be quick deleted (without abuse report) if the comment hasn't already been deleted and
			#  the user viewing the comments is either the author of the post (and currently has plus) or the
			#  owning user of the comments page.
			def quick_delete?(viewing_user)
				if(self.deleted)
					return false;
				elsif((self.userid == viewing_user.userid && viewing_user.plus?()) || self.bloguserid == viewing_user.userid)
					return true;
				else
					return false;
				end
			end
	end
end