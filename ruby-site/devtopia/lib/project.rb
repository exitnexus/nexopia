lib_require :devtopia, 'log';

module Devtopia
	class Project < Storable
		set_db(:devtaskdb);
		set_table("project");
		init_storable();

		def sprints
			return Sprint.find(:all, :scan, id);
		end
		
		
		def add_link(record_type)
			return "/my/projectmanager/add/#{self.type}/#{self.get_primary_key}/#{record_type}";
		end
		
		
		def parent_id
			return 0;
		end
		
		
		def parent_id=(id)
			# Do nothing
		end
		
		def display_columns
			return ["name"];
		end
		
		def edit_link
			return "/my/projectmanager/projects/list/#{id}";
		end

		include Devtopia::Loggable;
		
	end
end