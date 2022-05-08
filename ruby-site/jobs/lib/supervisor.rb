lib_require :Jobs, 'department', 'department_manager', 'interested_party';

module Jobs
	
	ManagerNavGroup = Struct.new("ManagerNavGroup", :department, :job_list);
	
	class Supervisor < Storable
		init_storable(:jobsdb, 'jobmanagers');
		
		def departments()
			dep_mans = DepartmentManager.find(:userid, self.userid);
			
			dep_id_list = Array.new();
			
			for temp in dep_mans
				dep_id_list << temp.depid;
			end
			
			deps = Department.find(*dep_id_list);
			
			list = Hash.new();
			list[:mine] = deps;
			
			if(dep_id_list.empty?())
				other_deps = Department.find(:all);
			else
				other_deps = Department.find(:conditions => ["depid NOT IN ?", dep_id_list]);
			end
			
			list[:other] = other_deps;
			
			return list;
		end
		
		def my_departments()
			dep_mans = DepartmentManager.find(:userid, self.userid);
			
			dep_id_list = Array.new();
			
			for temp in dep_mans
				dep_id_list << temp.depid;
			end
			
			deps = Department.find(*dep_id_list);
			
			return deps;
		end
		
		def jobs()
			ip_list = InterestedParty.find(:userid, self.userid);
			
			job_id_list = Array.new();
			
			for ip in ip_list
				job_id_list << ip.jobid;
			end
			
			job_list = JobPosting.find(*job_id_list);
			
			list = Hash.new();
			list[:mine] = job_list;
			
			if(job_id_list.empty?())
				other_jobs = JobPosting.find(:all);
			else
				other_jobs = JobPosting.find(:conditions => ["jobid NOT IN ?", job_id_list]);
			end
			list[:other] = other_jobs;
			
			return list;
		end
		
		def my_jobs()
			ip_list = InterestedParty.find(:userid, self.userid);
			
			job_id_list = Array.new();
			
			for ip in ip_list
				job_id_list << ip.jobid;
			end
			
			job_list = JobPosting.find(*job_id_list);
			
			return job_list;
		end
		
		def admin_nav_list()
			dep_list = self.departments();
			job_list = self.jobs();
			
			admin_nav = Array.new();
			
			for dep in dep_list[:mine]
				temp_nav = self.generate_nav_group(dep, job_list[:mine]);
				
				admin_nav << temp_nav;
			end
			
			if(!job_list[:mine].empty?())
				temp_dep = Department.new();
				temp_dep.name = "My Unassociated Jobs";
				temp_nav = self.generate_nav_group(temp_dep, job_list[:mine]);
				
				admin_nav << temp_nav;
			end
			
			for dep in dep_list[:other]
				temp_nav = self.generate_nav_group(dep, job_list[:other]);
				
				admin_nav << temp_nav;
			end
			
			if(!job_list[:other].empty?())
				temp_dep = Department.new();
				temp_dep.name = "Unassociated Jobs";
				temp_nav = self.generate_nav_group(temp_dep, job_list[:other]);
				
				admin_nav << temp_nav;
			end
			
			return admin_nav;
		end
		
		def generate_nav_group(dep, job_list)
			
			temp_nav = ManagerNavGroup.new();
			temp_nav.department = dep;
			temp_nav.job_list = Array.new();
			for job in job_list
				if(job.depid == dep.depid)
					temp_nav.job_list << job;
				end
			end
			
			for job in temp_nav.job_list
				job_list.delete(job);
			end
			
			return temp_nav;
		end
		
		def associate_job(job_id)
			ip = InterestedParty.new();
			
			ip.jobid = job_id;
			ip.userid = self.userid;
			
			ip.store();
		end
		
		def associate_department(dep_id)
			dm = DepartmentManager.new();
			
			dm.userid = self.userid;
			dm.depid = dep_id;
			
			dm.store();
		end
		
		def username()
			user_obj = User.find(:first, self.userid);
			
			return user_obj.username;
		end
	end
end
