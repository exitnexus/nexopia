lib_require :Groups, 'group';
lib_require :Core, 'visibility';
lib_want :Friends, 'friend'

module Groups
	class GroupMember < Storable
		set_enums(
			:visibility => Visibility.list
		);
		

		set_db(:usersdb);
		set_table("groupmembers");
		init_storable();

		VISIBILITY_OPTIONS = Visibility::options;


		def initialize()
			super();
			
			# Just to provide a dummy value for method calls below.
			@group = Groups::Group.new;
		end		
		
		
		def GroupMember.grouped_by_type(userid, filter_visibility_for=nil, logged_in=true)
			categories = Hash.new;
			members = find(:all, userid);
			members.each { |member|
				category = categories[member.group_type];
				if (category.nil?)
					category = Array.new;
					categories[member.group_type] = category;
				end
				
				if (logged_in && (filter_visibility_for.nil? || member.visible_to?(filter_visibility_for)))
					category << member;
				end
			};
			
			return categories.values;
		end
		
		def visible_to?(viewerid)
			user = User.find(:first, self.userid);

			if ((visibility != :none && viewerid == self.userid) ||
				(visibility == :friends && user.friend?(viewerid)) ||
				(visibility == :friends_of_friends && (user.friend_of_friend?(viewerid) || user.friend?(viewerid))) ||
				visibility == :all)

				return true;
			else
				return false;
			end
		end
		
				
		def after_create
			@group = Groups::Group.find(:first, :promise, self.groupid);
			
			refresh_cache;
		end
		
		
		def after_load
			@group = Groups::Group.find(:first, :promise, self.groupid);
		end
		
		
		def after_update
			refresh_cache;
		end
		
		
		def after_delete
			refresh_cache;
		end
		
		
		def refresh_cache
			# The PHP side caches the whole list of group members for each user
			$site.memcache.delete("groupmembers-#{self.userid}");
		end
		
		
		def group_name
			return @group.name;
		end
		
		
		def group_type_name
			return @group.type_name;
		end
		
		
		def group_location_name
			return @group.location_name;
		end


		def group_location_path
			return @group.location_path;
		end
		
		
		def group_location
			return @group.location;
		end
				
	
		def group_type
			return @group.type;
		end
		
		
		def duration
			from_string = Time.local(self.fromyear, self.frommonth, 1).strftime("%B, %Y");
			if (self.tomonth == -1 || toyear == -1)
				to_string = "Present";
			else
				to_string = Time.local(self.toyear, self.tomonth, 1).strftime("%B, %Y");
			end
			
			return from_string + " - " + to_string;
		end
			
	end
end