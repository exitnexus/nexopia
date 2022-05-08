lib_require :Groups, 'group';
lib_require :Core, 'visibility', 'time_format';
lib_want :Friends, 'friend'

module Groups
	class GroupMember < Cacheable
		set_enums(
			:visibility => Visibility.list(GroupsModule)
		);
		

		set_db(:usersdb);
		set_table("groupmembers");
		init_storable();

		VISIBILITY_OPTIONS = Visibility.options(GroupsModule);


		def initialize(*args)
			super(*args);
			
			# Just to provide a dummy value for method calls below.
			@group = Groups::Group.new;
		end		
		
		
		def GroupMember.grouped_by_type(user, filter_visibility_for=nil, logged_in=true, admin_viewer=false)
			categories = Hash.new;
			user.group_memberships.each { |member|
				if (logged_in && (admin_viewer || (filter_visibility_for.nil? || member.visible_to?(filter_visibility_for))))
					category = categories[member.group_type];
					if (category.nil?)
						category = Array.new;
						categories[member.group_type] = category;
					end
					
					category << member;
				end
			};
			
			return categories.values;
		end
		
		def visible_to?(viewerid)
			user = User.find(:first, self.userid);

			if ((self.visibility != :none && viewerid == self.userid) ||
				(self.visibility == :friends && user.friend?(viewerid)) ||
				(self.visibility == :friends_of_friends && (user.friend_of_friend?(viewerid) || user.friend?(viewerid))) ||
				self.visibility == :all)

				return true;
			else
				return false;
			end
		end
		
		def owner
			return User.get_by_id(@userid);
		end
		
		def after_create
			@group = Groups::Group.find(:first, :promise, self.groupid);
			
			refresh_cache;

			if (site_module_loaded?(:GoogleProfile))
				self.owner.update_hash
			end
			
			super
		end
		
		
		def after_load
			@group = Groups::Group.find(:first, :promise, self.groupid);
		end
		
		
		def after_update
			refresh_cache;
			
			if (site_module_loaded?(:GoogleProfile))
				self.owner.update_hash
			end
			
			super
		end
		
		
		def after_delete
			refresh_cache;
		end
		
		
		def before_delete
			if (site_module_loaded?(:GoogleProfile))
				self.owner.update_hash
			end
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
			if (self.fromyear.nil? || self.frommonth.nil?)
				from_string = ""
			else
				from_string = TimeFormat.month_and_year(Time.local(self.fromyear, self.frommonth, 1));
			end
			if (self.tomonth == -1 || toyear == -1)
				to_string = "Present";
			elsif (self.tomonth.nil? || self.toyear.nil?)
				to_string = ""
			else
				to_string = TimeFormat.month_and_year(Time.local(self.toyear, self.tomonth, 1));
			end
			
			return from_string + " - " + to_string;
		end
			
	end
end

class User < Cacheable
	relation :multi, :group_memberships, :userid, Groups::GroupMember
end