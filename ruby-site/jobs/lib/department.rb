lib_require :Core, 'storable/cacheable';
lib_require :Jobs, 'department_applicant', 'applicant', 'department_manager';

module Jobs
	class Department < Cacheable
		init_storable(:jobsdb, 'departments');
		
		attr_accessor :selected;
		
		def applicants()
			dep_apps = DepartmentApplicant.find(self.depid);
			
			app_id_list = Array.new();
			
			for dep_app in dep_apps
				app_id_list << dep_app.appid;
			end
			
			app_list = Applicant.find(*app_id_list);
			
			return app_list;
		end
		
		def uri_info(mode = 'self')
			case mode
			when 'admin_view'
				return [self.name, "/jobs/administration/department/#{self.depid}/"];
			when 'apply'
				return ["", "/jobs/department/{self.depid}/apply/"];
			end
		end
		
		def manager_list()
			man_dep_list = DepartmentManager.find(:depid, self.depid);
			
			man_id_list = Array.new();
			for man_dep in man_dep_list
				man_id_list << man_dep.userid;
			end
			
			man_list = JobManager.find(*man_id_list);
			
			return man_list;
		end
		
		def Department.available_departments(app)
			dep_app_list = DepartmentApplicant.find(:app, app.appid);
			
			dep_id_list = Array.new();
			
			for dep_app in dep_app_list
				dep_id_list << dep_app.depid;
			end
			
			if(!dep_id_list.empty?())
			   dep_list = Department.find(:conditions => ["depid NOT IN ?", dep_id_list]);
			else
				dep_list = Department.find(:all);
			end
			
			return dep_list;
		end
	end
end
