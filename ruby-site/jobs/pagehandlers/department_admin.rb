lib_require :Jobs, "job_posting", "department", "job_manager", "applicant", "job_helper", 'job_mailer', "department_manager", 'jobs_exception';

module Jobs
	class JobsDepartmentAdministrationHandler < PageHandler
		
		include JobsHelper
		
		declare_handlers("jobs/administration/department"){
			area :Public
			access_level :Admin, JobsModule, :supervise
			
			#Views for creating a department
			page	:GetRequest, :Full, :new_department, "new";
			
			#Controllers for creating a department
			handle	:PostRequest, :create_department, "new", "submit";
			
			#View and controller for adding department managers.
			page	:GetRequest, :Full, :add_department_manager, input(Integer), "add", "manager";
			handle	:PostRequest, :create_department_manager, input(Integer), "add", "manager", "submit";
		
			access_level :Admin, JobsModule, :edit
			
			page 	:GetRequest, :Full, :edit_department, input(Integer), "edit";
			handle	:PostRequest, :update_department, input(Integer), "edit", "submit";
			
			page	:GetRequest, :Full, :view_dep_applicants, input(Integer), "applicants";
			page	:GetRequest, :Full, :admin_department_view, input(Integer);
			
			page	:GetRequest, :Full, :view_closed_jobs, input(Integer), "jobs", "closed";
		}
		
		#Views for department creation and editing
		def new_department()
			t = Template.instance("jobs", "new_department");
			
			t.dep = Department.new();
			t.submit_location = "new";
			
			print t.display();
		end
		
		def edit_department(dep_id)
			t = Template.instance("jobs", "new_department");
			
			dep = Department.find(:first, dep_id);
			
			t.dep = dep;
			t.submit_location = "#{dep_id}/edit";
			
			print t.display();
		end
		
		#Controllers for creating and updating a department.
		def create_department()
			self.process_department();
			
			site_redirect("/jobs/administration/");
		end
		
		def update_department(dep_id)
			begin
				self.process_department(dep_id);
			rescue JobsException => e
				site_redirect("/jobs/administration/error?message=#{CGI::escape(e.message)}");
			end
			
			site_redirect("/jobs/administration/");
		end
		
		def process_department(dep_id = 0)
			if(dep_id == 0)
				dep = Department.new();
			else
				dep = Department.find(:first, dep_id);
				if(dep.nil?)
					raise JobsException.new(), "Department #{dep_id} does not exist";
				end
			end
			
			department_name = params["dep_name", String];
			department_desc = params["dep_desc", String];
			
			dep.name = department_name;
			dep.description = department_desc;
			
			dep.store();
		end
		
		def view_dep_applicants(dep_id)
			t = Template.instance("jobs", "view_applicants");
			
			dep = Department.find(:first, dep_id);
			
			request.reply.headers['X-width'] = 0;
			
			dep_apps = dep.applicants();
			
			t.dep = dep;
			t.applicants = dep_apps;
			
			t.tab_list = self.generate_dep_admin_tabs(dep_id);
			t.tab_list[:applicants].selected = "selected";
			
			print t.display();
		end
		
		def add_department_manager(dep_id)
			t = Template.instance("jobs", "add_interested_party");
			
			man_list = JobManager.find(:all);
			ip_list = DepartmentManager.find(dep_id);
			
			t.dep_id = dep_id;
			t.manager_list = man_list;
			
			t.submit_location = "department/#{dep_id}";
			
			print t.display();
		end
		
		def create_department_manager(dep_id)
			dep = Department.find(:first, dep_id);
			
			if(dep.nil?())
				site_redirect("/jobs/");
			end
			
			manager_id = params['manager_id', Integer];
			
			dep_man = DepartmentManager.new();
			dep_man.depid = dep_id;
			dep_man.userid = manager_id;
			
			dep_man.store();
			
			site_redirect("/jobs/administration/");
		end
		
		def admin_department_view(dep_id)
			t = Template.instance("jobs", "admin_department_main");
			
			request.reply.headers['X-width'] = 0;
			
			dep = Department.find(:first, dep_id);
			t.dep = dep;
			t.dep_id = dep.depid;
			
			manager = JobManager.find(:first, request.session.user.userid);
			
			nav_menu = manager.admin_nav_list();
			t.nav_menu = nav_menu;
			
			t.tab_list = self.generate_dep_admin_tabs(dep_id);
			t.tab_list[:details].selected = "selected";
			
			print t.display();
		end
		
		def generate_dep_admin_tabs(dep_id)
			tab_list = Hash.new();
			
			tab_list[:details] = AdminTab.new("Department Details", "/jobs/administration/department/#{dep_id}/");
			tab_list[:applicants] = AdminTab.new("View Applicants", "/jobs/administration/department/#{dep_id}/applicants/");
			tab_list[:closed_jobs] = AdminTab.new("Closed Jobs", "/jobs/administration/department/#{dep_id}/jobs/closed/");
			
			return tab_list;
		end
		
		def view_closed_jobs(dep_id)
			t = Template.instance("jobs", "admin_department_view_jobs");
			
			dep = Department.find(:first, dep_id);
			t.dep = dep;
			
			manager = JobManager.find(:first, request.session.user.userid);
			nav_menu = manager.admin_nav_list();
			t.nav_menu = nav_menu;
			
			job_list = JobPosting.find(:conditions => ["closingdate < ?", Time.now.to_i()]);
			t.job_list = job_list;
			
			t.tab_list = self.generate_dep_admin_tabs(dep_id);
			t.tab_list[:closed_jobs].selected = "selected";
			
			print t.display();
		end
	end
end
