
lib_require :Core, "storable/cacheable"
lib_require :Core, "hierarchy";

lib_want :GoogleProfile, "google_user"


class Interests < Storable
	init_storable(:configdb, "interests");
	
	register_selection :ids, :id;
	
	include Hierarchy;
	init_hierarchy("All Interests");
	
	def category
		if (self.parent != 0)
			return Interests.get_by_id(self.parent)
		end
	end

	def ==(other)
		if (other.kind_of? UserInterests)
			return self.id == other.interestid;
		elsif (other.kind_of? Fixnum)
			return self.id == other;
		else
			return super(other)
		end
	end

	def Interests.all_interest_ids()
		all_interest_ids = $site.memcache.get("ruby-all_interest_ids");
		if (all_interest_ids.nil?)
			all_interest_ids = Interests.find(:scan, :selection => :ids).map { |row| row.id };
			$site.memcache.set("ruby-all_interest_ids", all_interest_ids, 60*60*24);
		end
		
		return all_interest_ids;
	end

end

class UserInterests < Cacheable
	init_storable(:usersdb, "userinterests");

	def interest
		return Interests.get_by_id(interestid);
	end
	
	
	def to_s
		return Interests.get_by_id(interestid).to_s;
	end
	
	def ==(other)
		if (other.kind_of? Interests)
			return self.interestid == other.id;
		elsif (other.kind_of? Fixnum)
			return self.interestid == other;
		else
			return super(other)
		end
	end
	
	def owner
		return User.get_by_id(@userid);
	end
	
	def after_create
		if (site_module_loaded?(:GoogleProfile))
			self.owner.update_hash
		end
		super
	end

	def after_update
		if (site_module_loaded?(:GoogleProfile))
			self.owner.update_hash
		end
		super
	end

	def before_delete
		if (site_module_loaded?(:GoogleProfile))
			self.owner.update_hash
		end
	end			
end

