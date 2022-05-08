lib_require :Jobs, 'department', 'department_manager', 'interested_party', 'supervisor';

module Jobs
	
	class JobManager < Supervisor
		
		def JobManager.find(*args)
			list = super(*args);
			
			if(list.kind_of?(OrderedMap))
				JobManager.seperate_manager_types!(list);
			elsif(list.kind_of?(JobManager))
				list = JobManager.determine_manager_type(list);
			end
			
			return list;
		end
		
		def departments()
			return self.my_departments();
		end
		
		def jobs()
			return self.my_jobs();
		end
		
		def admin_nav_list()
			dep_list = self.departments();
			job_list = self.jobs();
			
			admin_nav = Array.new();
			
			for dep in dep_list
				temp_nav = self.generate_nav_group(dep, job_list);
				
				admin_nav << temp_nav;
			end
			
			if(!job_list.empty?())
				temp_dep = Department.new();
				temp_dep.name = "Unassociated Jobs";
				temp_nav = self.generate_nav_group(temp_dep, job_list);
				
				admin_nav << temp_nav;
			end
			
			return admin_nav;
		end
		
		def JobManager.seperate_manager_types!(manager_list)
			key_list = manager_list.keys();
			for key in key_list
				manager = manager_list[key];
				temp = JobManager.determine_manager_type(manager);
				manager_list[key] = temp;
			end
		end
		
		def JobManager.determine_manager_type(manager)
			if(!manager.supervisor)
				return manager;
			end
			
			sup = Supervisor.new();
			sup.userid = manager.userid;
			sup.email = manager.email;
			sup.name = manager.name;
			sup.supervisor = true;
			
			sup.update_method = :update;
			
			return sup;
		end
	end
end
