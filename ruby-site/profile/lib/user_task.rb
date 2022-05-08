module Profile
	class UserTask < Cacheable
		init_storable(:usersdb, "usertasks");
		
		def ==(other)
			if(other.kind_of?(NewUserTask))
				if(self.taskid == other.taskid)
					return true;
				else
					return false;
				end
			end

			super(other);
		end
	end
end