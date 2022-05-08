lib_require :devtopia, 'task', 'log'

module Devtopia
	class Sprint < Storable
		set_db(:devtaskdb);
		set_table("sprint");
		init_storable();

		def edit_link
			return "/my/projectmanager/sprints/#{projectid}/list/#{id}";
		end
		
		
		def after_load()
			@tasks = Devtopia::Task.find(:all, :scan, :promise, :conditions => ["projectid = ? AND sprintid = ?", projectid, id]);
			@project = Devtopia::Project.find(:first, :promise, self.projectid);
		end
		
		
		def after_create()
			@tasks = Devtopia::Task.find(:all, :scan, :promise, :conditions => ["projectid = ? AND sprintid = ?", projectid, id]);
			@project = Devtopia::Project.find(:first, :promise, self.projectid);
		end
		
		
		def parent_id=(value)
			self.projectid = value;
		end
		
		
		def parent_id
			return self.projectid;
		end


		def display_columns
			return ["name"];
		end
		
		
		def project_name
			return @project.name;
		end
		
		
		def start_day
			return Time.at(self.startdate).day;
		end
		
		
		def start_month
			return Time.at(self.startdate).month;
		end
		
		
		def start_year
			return Time.at(self.startdate).year;
		end
		
		
		def end_day
			return Time.at(self.enddate).day;
		end
		
		
		def end_month
			return Time.at(self.enddate).month;
		end
		
		
		def end_year
			return Time.at(self.enddate).year;
		end
		
		
		def tasks(programmer=nil)
			@tasks = demand(@tasks) || Array.new;
			
			if (programmer.nil?)
				return @tasks;
			else
				return @tasks.select { |task| task.assigned_to?(programmer) };
			end
		end

		include Devtopia::Loggable;	

	end
end