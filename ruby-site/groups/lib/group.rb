lib_require :core, 'users/locs'

module Groups
	class Group < Cacheable
		set_db(:groupsdb);
		set_table("groups");
		init_storable();


		TYPE_OPTIONS = {
			1 => "High School",
			2 => "College",
			3 => "University",
			4 => "Job",
			5 => "Club",
			6 => "Team"
		}
		
		
		def Group.by_name(group_name=nil)
			return find(:first, :conditions => ["name = ?", group_name]);
		end
		
		
		def Group.by_name_type_location(group_name=nil, group_type=nil, group_location=nil)
			return find(:first, :conditions => ["name = ? AND type = ? AND location = ?", group_name, group_type, group_location]);
		end
		
		
		def after_load
			@loc = Locs.get_by_id(self.location);
		end
		
		
		def after_create
			@loc = Locs.get_by_id(self.location);
		end
		
		
		def type_name
			return TYPE_OPTIONS[self.type];
		end
		
		
		def location_name
			if (!@loc.nil?)
				return @loc.name;
			elsif (self.location == 0)
				return "";
			end
		end
		
		
		def location_path
			if (!@loc.nil?)
				return @loc.name_path;
			elsif (self.location == 0)
				return "";
			end
		end
	end
end