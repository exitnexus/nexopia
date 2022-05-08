module Profile
	class NewUserTask < Storable
		init_storable(:db, "newusertasks");
		
		def ==(other)
			if(other.kind_of?(UserTask))
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