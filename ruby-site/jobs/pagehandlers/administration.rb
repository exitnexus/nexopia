lib_require	:Jobs, "job_posting", "department", "job_manager", "applicant", 'job_helper', 'job_mailer', "department_manager", "availability_type", 'supervisor', 'interested_party';
lib_require	:Adminutils, "adminroleaccount";

module Jobs
	class JobsAdministrationHandler < PageHandler
		
		include JobsHelper
		
		declare_handlers("jobs/administration"){
			area :Public
			access_level :Admin, JobsModule, :edit
			
			page	:GetRequest, :Full, :display_admin_nav_page;
			page	:GetRequest, :Full, :new_job_posting, "job", "new";
			
			handle	:PostRequest, :create_job_posting, "job", "new", "submit";
						
			page	:GetRequest, :Full, :new_asset_type, "asset", "type", "new";
			
			handle	:PostRequest, :create_asset_type, "asset", "type", "new", "submit";
			
			page	:GetRequest, :Full, :new_manager, "manager", "new";
			
			handle	:PostRequest, :create_manager, "manager", "new", "submit";
			
			page	:GetRequest, :Full, :view_managers, "managers";
			
			page	:GetRequest, :Full, :add_interested_party, "job", input(Integer), "add", "manager";
			
			handle	:PostRequest, :create_interested_party, "job", input(Integer), "add", "manager", "submit";
			
			page	:GetRequest, :Full, :view_interested_parties, "job", input(Integer), "managers";
			
			page	:GetRequest, :Full, :view_job_applicants, "job", input(Integer), "applicants";
			
			page	:GetRequest, :Full, :edit_job_posting, "job", input(Integer), "edit";
			
			page	:PostRequest, :Full, :update_job_posting, "job", input(Integer), "edit", "submit";
			
			page	:GetRequest, :Full, :display_admin_job_view, "job", input(Integer);
			
			page	:GetRequest, :Full, :admin_error, "error";
			
			page	:GetRequest, :Full, :new_availability_type, "availability", "new";
			page	:GetRequest, :Full, :edit_availability_type, "availability", input(Integer), "edit";
			handle	:PostRequest, :create_availability_type, "availability", "new", "submit";
			handle	:PostRequest, :update_availability_type, "availability", input(Integer), "edit", "submit";
			
			page	:GetRequest, :Full, :new_supervisor, "supervisor", "new";
			handle	:PostRequest, :create_supervisor, "supervisor", "new", "submit";
		}
		
		def display_admin_nav_page()
			t = Template.instance("jobs", "main_admin");
			
			if(request.session.anonymous?() || request.session.user.anonymous?())
				site_redirect("/jobs/");
			end
			
			mod = TypeIDItem.get_by_name(:JobsModule);
			
			$log.info("The type id for the JobsModule is: #{mod.typeid}");
			
			manager = JobManager.find(:first, request.session.user.userid);
			if(manager.nil?() && request.session.has_priv?(JobsModule, :supervise))
				site_redirect("/jobs/administration/supervisor/new/");
			end
			
			request.reply.headers['X-width'] = 0;
			
			
			
			nav_menu = manager.admin_nav_list();
			t.nav_menu = nav_menu;
			
			print t.display();
		end
		
		def new_job_posting()
			t = Template.instance("jobs", "new_job_posting");
			
			job = JobPosting.new();
			
			t.job = job;
			
			dep_list = Department.find(:all);
			
			t.dep_list = dep_list;
			
			t.submit_location="new";
			
			print t.display();
		end
		
		def edit_job_posting(job_id)
			t = Template.instance("jobs", "new_job_posting");
			
			job = JobPosting.find(:first, job_id);
			
			t.job = job;
			
			dep_list = Department.find(:all);
			
			t.dep_list = dep_list;
			t.show_immediate_close = true;	
			t.submit_location = "#{job_id}\edit";
			
			print t.display();
		end
		
		def create_job_posting()
			
			if(request.session.anonymous?() || request.session.user.anonymous?())
				site_redirect("/jobs/");
			end
			
			manager = JobManager.find(:first, request.session.user.userid);
			
			if(manager == nil)
				site_redirect("/jobs/");
			end
			
			job_title = params['job_title', String];
			job_description = params['job_description', String];
			job_responsibilities = params['job_responsibilities', String];
			job_requirements = params['job_requirements', String];
			job_opening_date = params['job_opening_date', String];
			job_closing_date = params['job_closing_date', String];
			job_department = params['job_department', Integer];
			
			job_posting = JobPosting.new();
			
			job_posting.title = job_title;
			job_posting.description = job_description;
			job_posting.responsibilities = job_responsibilities;
			job_posting.requirements = job_requirements;
			job_posting.depid = job_department;
			if(!job_opening_date.nil?() && job_opening_date != "")
				job_posting.openingdate = Time.parse(job_opening_date).to_i();
			else
				job_posting.openingdate = Time.now.to_i();
			end
			if(!job_closing_date.nil?() && job_closing_date != "")
				job_posting.closingdate = Time.parse(job_closing_date).to_i();
			end
			
			job_posting.creator = request.session.user.userid;
			job_posting.entrydate = Time.now.to_i();
			
			job_posting.store();
			
			manager.associate_job(job_posting.jobid);
			
			RequestedAsset.associate_defaults(job_posting.jobid);
			
			site_redirect("/jobs/");
		end

		def update_job_posting(job_id)
			job_title = params['job_title', String];
			job_description = params['job_description', String];
			job_responsibilities = params['job_responsibilities', String];
			job_requirements = params['job_requirements', String];
			job_opening_date = params['job_opening_date', String];
			job_closing_date = params['job_closing_date', String];
			job_department = params['job_department', Integer];
			job_close_immediately = params['job_close_now', String];			
	
			job = JobPosting.find(:first, job_id);
			
			job.title = job_title;
			job.description = job_description;
			job.responsibilities = job_responsibilities;
			job.requirements = job_requirements;
			job.depid = job_department;
			if(!job_opening_date.nil?() && job_opening_date != "")
				job.openingdate = Time.parse(job_opening_date).to_i();
			else
				job.openingdate = Time.now.to_i();
			end
			if(!job_closing_date.nil?() && job_closing_date != "")
				job.closingdate = Time.parse(job_closing_date).to_i();
			end

			if(job_close_immediately.nil?() && job_close_immediately.length > 0)
				job.closingdate = Time.now.to_i();
			end
			
			job.store();
		end
		
		def process_job_posting(job_id = 0)
			if(job_id == 0)
				job = JobPosting.new();
			else
				job = JobPosting.find(:first, job_id);
			end
		end

		def new_manager()
			t = Template.instance("jobs", "new_manager");
			
			t.submit_location = "new";
			
			print t.display();
		end
		
		def new_asset_type()
			t = Template.instance("jobs", "new_asset_type");
			
			t.submit_location = "new";
			
			print t.display();
		end
		
		def create_asset_type()
			at_name = params['asset_type_name', String];
			at_multi = params['asset_type_multistep', String];
			
			asset_type = AssetType.new();
			
			asset_type.name = at_name;
			if(at_multi == "multistep")
				asset_type.multistep = true;
			else
				asset_type.multistep = false;
			end
			
			asset_type.store();
			
			site_redirect("/jobs/");
		end
		
		def new_manager()
			if(request.session.anonymous?() && request.session.user.anonymous?())
			   #needs to redirect to an error page.
			   site_redirect("/jobs/");
			end
			
			t = Template.instance("jobs", "new_manager");
			
			t.dep_list = Department.find(:all);
			
			t.submit_location = "new";
			t.manager = JobManager.new();
			
			print t.display();
		end
		
		def create_manager()
			man_name = params['manager_name', String];
			man_email = params['manager_email', String];
			man_username = params['manager_username', String];
			man_departments = params['manager_departments', Array];
			
			man_username_obj = UserName.by_name(man_username);
			
			if(man_username_obj.nil?())
				#send error
			end
			
			mod = TypeIDItem.get_by_name(:JobsModule);
			
			$log.info("The type id for the JobsModule is: #{mod.typeid}");
			
			manager_role = AdminRoleAccount.find(:first, :conditions => ["rolename = ?", "Department Manager"]);
			
			existing_account_maps = AccountMap.find(man_username_obj.userid, :accountid);
			
			role_exist = false;
			
			for account_map in existing_account_maps
				if(account_map.primaryid == manager_role.id)
					role_exist = true;
				end
			end
			
			if(!role_exist)
				new_map = AccountMap.new();
				new_map.primaryid = manager_role.id;
				new_map.accountid = man_username_obj.userid;
				new_map.visible = true;
				
				new_map.store();
			end
			
			manager = JobManager.new();
			
			manager.userid = man_username_obj.userid;
			manager.name = man_name;
			manager.email = man_email;
			
			manager.store();
			
			for dep_id in man_departments
				manager.associate_department(dep_id);
			end

			site_redirect("/jobs/administration/");
		end
		
		def view_managers()
			t = Template.instance("jobs", "view_managers");
			
			request.reply.headers['X-width'] = 0;
			
			manager_list = JobManager.find(:all);
			
			admin_list = Hash.new();
			admin_list[:manager] = Array.new();
			admin_list[:supervisor] = Array.new();
			
			for manager in manager_list
				if(manager.kind_of?(JobManager))
					admin_list[:manager] << manager;
				else
					admin_list[:supervisor] << manager;
				end
			end
			
			t.manager_list = admin_list;
			
			print t.display();
		end
		
		def view_job_applicants(job_id)
			t = Template.instance("jobs", "admin_view_applicants");
			
			job = JobPosting.find(:first, job_id);
			
			request.reply.headers['X-width'] = 0;
			
			manager = JobManager.find(:first, request.session.user.userid);
			
			nav_menu = manager.admin_nav_list();
			t.nav_menu = nav_menu;
			t.job_id = job_id;
			t.organized_applicant_list = job.applicants();
			
			t.job = job;
			
			print t.display();
		end
		
		def add_interested_party(job_id)
			t = Template.instance("jobs", "add_interested_party");
			
			man_list = JobManager.find(:all);
			ip_list = InterestedParty.find(:jobid, job_id);
			
			t.job_id = job_id;
			t.manager_list = man_list;
			
			t.submit_location = "job/#{job_id}";
			
			print t.display();
		end
		
		def create_interested_party(job_id)
			job = JobPosting.find(:first, job_id);
			
			if(job.nil?())
				site_redirect('/jobs/');
			end
			
			manager_id = params['manager_id', Integer];
			
			interested_party = InterestedParty.new();
			interested_party.jobid = job_id;
			interested_party.userid = manager_id;
			
			interested_party.store();
			
			site_redirect("/jobs/administration/");
		end
		
		def display_admin_job_view(job_id)
			t = Template.instance("jobs", "admin_job_main");
			
			request.reply.headers['X-width'] = 0;
			
			job = JobPosting.find(:first, job_id);
			t.job = job;
			
			t.job_id = job_id;
			
			manager = JobManager.find(:first, request.session.user.userid);
			
			nav_menu = manager.admin_nav_list();
			t.nav_menu = nav_menu;
			
			t.tab_list = self.generate_job_admin_tabs(job_id);
			t.tab_list[:details].selected = "selected";
			
			$log.object t.tab_list;
			
			print t.display();
		end
		
		def admin_error()
			err_message = params['message', String];
			
			request.reply.headers['X-width'] = 0;
			
			t = Template.instance("jobs", "admin_error");
			t.message = err_message;
				
			print t.display;
		end
		
		def generate_job_admin_tabs(job_id)
			tab_list = Hash.new();
			
			tab_list[:details] = AdminTab.new("Job Details", "/jobs/administration/job/#{job_id}/");
			tab_list[:applicants] = AdminTab.new("View Applicants", "/jobs/administration/job/#{job_id}/applicants/");
			tab_list[:interested] = AdminTab.new("Interested Parties", "/jobs/administration/job/#{job_id}/managers/");
			
			return tab_list;
		end
		
		def new_availability_type()
			t = Template.instance("jobs", "new_availability_type");
			t.avail_type = AvailabilityType.new();
			t.submit_location = "new";
			
			print t.display();
		end
		
		def edit_availability_type(avail_id)
			t = Template.instance("jobs", "new_availability_type");
			avail_type = AvailabilityType.find(:first, avail_id);
			t.avail_type = avail_type;
			t.submit_location = "#{avail_id}/edit";
			
			print t.display();
		end
		
		def create_availability_type()
			self.process_availability_type();
			
			site_redirect("/jobs/administration/");
		end
		
		def update_availability_type(avail_id)
			begin
				self.process_availability_type(avail_id);
			rescue JobsException => e
				site_redirect("/jobs/administration/error?message=#{CGI::escape(e.message)}");
			end
			
			site_redirect("/jobs/administration/");
		end
		
		def process_availability_type(avail_id = 0)
			if(avail_id == 0)
				$log.info("I have #{avail_id}");
				avail_type = AvailabilityType.new();
			else
				avail_type = AvailabilityType.find(:first, avail_id);
			end
			
			if(avail_type.nil?())
				raise JobsException.new(), "Availability Type #{avail_id} does not exist";
			end
			
			at_name = params["at_name", String];
			at_available = params["at_available", String];
			
			if(at_available == "available")
				at_avail_enum = true;
			else
				at_avail_enum = false;
			end
			
			avail_type.name = at_name;
			avail_type.available = at_avail_enum;
			
			avail_type.store();
		end
		
		def new_supervisor()
			t = Template.instance("jobs", "new_supervisor");
			
			t.supervisor = Supervisor.new();
			
			print t.display();
		end
		
		def create_supervisor()
			sup_name = params["supervisor_name", String];
			sup_email = params["supervisor_email", String];
			
			sup = Supervisor.new();
			sup.name = sup_name;
			sup.email = sup_email;
			sup.supervisor = true;
			sup.userid = request.session.user.userid;
			
			sup.store();
			
			site_redirect("/jobs/administration/");
		end
		
		def view_interested_parties(job_id)
			t = Template.instance("jobs", "view_interested_parties");
			
			ip_list = InterestedParty.find(job_id);
			
			manager_id_list = Array.new();
			
			for ip in ip_list
				manager_id_list << ip.userid;
			end
			
			manager_list = JobManager.find(*manager_id_list);
			
			admin_list = Hash.new();
			admin_list[:manager] = Array.new();
			admin_list[:supervisor] = Array.new();
			
			for manager in manager_list
				if(manager.kind_of?(JobManager))
					admin_list[:manager] << manager;
				else
					admin_list[:supervisor] << manager;
				end
			end
			
			t.manager_list = admin_list;
			
			manager = JobManager.find(:first, request.session.user.userid);
			
			nav_menu = manager.admin_nav_list();
			t.nav_menu = nav_menu;
			
			t.tab_list = self.generate_job_admin_tabs(job_id);
			t.tab_list[:interested].selected = "selected";
			
			print t.display();
		end
	end
end
