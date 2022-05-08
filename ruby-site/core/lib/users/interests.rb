
lib_require :Core, "storable/cacheable"
lib_require :Core, "hierarchy";

class Interests < Storable
	init_storable(:configdb, "interests");
	
	include Hierarchy;
	init_hierarchy("All Interests");
	
	def category
		if (self.parent != 0)
			return Interests.find(:first, self.parent)
		end
	end

	def ==(other)
		if (other.kind_of? UserInterests)
			return self == other.interest;
		else
			return super(other)
		end
	end

end

class UserInterests < Cacheable
	init_storable(:usersdb, "userinterests");
	relation_singular :interest, :interestid, Interests
	
	def to_s
		return Interests.get_by_id(interestid).to_s;
	end
	
	def ==(other)
		if (other.kind_of? Interests)
			return self.interest == other;
		else
			return super(other)
		end
	end
end

