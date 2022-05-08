lib_require :Jobs, 'job_posting', 'applicant', 'department', 'department_applicant', 'job_application', 'applicant_asset', 'asset_type', 'applicant_view_request', 'validation_helper';



module Jobs
	class JobsHandler < PageHandler
		
		include JobsValidationHelper;
		
		declare_handlers("jobs"){
			area :Public
			access_level :Any
			
			page	:GetRequest, :Full, :jobs_front;
			
			page	:GetRequest, :Full, :jobs_front, "job", input(Integer);
			
			page	:GetRequest, :Full, :new_application, "job", input(Integer), "apply";
			page	:GetRequest, :Full, :create_application, "job", input(Integer), "apply", "submit";
			page	:GetRequest, :Full, :create_dep_application, "department", input(Integer), "apply", "submit";
			
			page	:GetRequest, :Full, :complete_application, "job", input(Integer), "apply", "complete";
			page	:GetRequest, :Full, :complete_dep_application, "department", input(Integer), "apply", "complete";
			
			
			page	:GetRequest, :Full, :choose_department, "departments";
			page	:GetRequest, :Full, :new_dep_application, "department", input(Integer), "apply";
		};

		def jobs_front(job_id = nil)
			t = Template.instance("jobs", "front_page");
			
			request.reply.headers['X-width'] = 0;
			
			job_list = JobPosting.open_jobs();
			
			if(job_id != nil)
				for temp_job in job_list
					if(temp_job.jobid == job_id)
						job = temp_job;
						temp_job.selected = true;
						break;
					end
				end
			end
			
			t.job_list = job_list;
			
			if(job != nil)
				t.job = job;
			end
			
			print t.display();
		end
		
		def new_application(job_id)
			t = Template.instance("jobs", "new_application");
			
			request.reply.headers['X-width'] = 0;
			
			t.job_id = job_id;
			
			t.submit_location = "job/#{job_id}/apply";
			t.submit_button = "new_job_app_submit";
			print t.display();
		end
		
		def complete_application(job_id)
			t = Template.instance("jobs", "confirm_application");
			
			print t.display();
		end
		
		def new_dep_application(dep_id)
			t = Template.instance("jobs", "new_application");
			
			request.reply.headers['X-width'] = 0;
			
			t.dep_id = dep_id;
			
			t.submit_location = "department/#{dep_id}/apply";
			
			t.submit_button = "new_dep_app_submit";
			
			print t.display();
		end
		
		def complete_dep_application(dep_id)
			t = Template.instance("jobs", "confirm_application");
			
			request.reply.headers['X-width'] = 0;
			
			print t.display();
		end
		
		def choose_department()
			t = Template.instance("jobs", "choose_department");
			
			request.reply.headers['X-width'] = 0;
			
			dep_list = Department.find(:all);
			t.dep_list = dep_list;
			
			print t.display();
		end
		
		

		
		def edit_applicant_asset(app_hash, asset_type_id)
			app_request = ApplicantViewRequest.find(:first, :conditions => ["hash = ?", app_hash]);
			
			request.reply.headers['X-width'] = 0;
			
			if(app_request.nil?() || !app_request.active?())
				error_title = ApplicantViewRequest.ErrorTitle;
				if(app_request.nil?())
					error_message = ApplicantViewRequest.RequestInvalid;
				else
					error_message = ApplicantViewRequest.RequestExpired;
				end
				
				self.show_error(error_title, error_message);
				
				return;
			end
			
			asset_type = AssetType.find(:first, asset_type_id);
			
			if(asset_type.nil?())
				error_title = AssetType.ErrorTitle;
				error_message = AssetType.InvalidType;
				
				self.show_error(error_title, error_message);
				return;
			end
			
			t = Template.instance("jobs", "applicant_asset_update");
			
			t.asset_type = asset_type;
			t.app_hash = app_hash;
			
			print t.display();
		end
		
		
		def show_error(error_title, error_message)
			t = Template.instance("jobs", "error_view");
			
			t.error_title = error_title;
			t.error_message = error_message;
			
			print t.display();
		end
		

		
		
		
		def create_application(job_id)
			job_id = params['app_job_id', Integer];
			
			if(job_id == nil)
				return;
			end
			
			job = JobPosting.find(:first, job_id);
			
			app_name = params['app_name', String];
			app_email = params['app_email', String];
			app_phone = params['app_phone', String];
			app_contact_type = params['app_contact_type', String];
			app_contact_details = params['app_contact_details', String];
			
			app = Applicant.new();
			
			app.name = app_name;
			app.email = app_email;
			app.phonenumber = app_phone;
			app.contactmethod = app_contact_type;
			app.contactnotes = app_contact_details;
			
			app.store();
			
			job_app = Jobs::JobApplication.new();
			
			job_app.jobid = job.jobid;
			job_app.appid = app.appid;
			job_app.date = Time.now.to_i();
			
			job_app.store();
			
			dep_app = DepartmentApplicant.new();
			
			dep_app.depid = job.depid;
			dep_app.appid = app.appid;
			dep_app.date = Time.now.to_i();
			
			dep_app.store();
			
			resume_type = AssetType.find(:first, :conditions => ["name = ?", "Resume"]);
			
			if(resume_type == nil)
				AssetType.create_default_entries();
				resume_type = AssetType.find(:first, :conditions => ["name = ?", "Resume"]);
			end
			
			cover_letter_type = AssetType.find(:first, :conditions => ["name = ?", "Cover Letter"]);
			
			cover_letter_file = params['cover_letter_file_name', String];
			cover_letter_mogile = params['cover_letter_file_mogile', String];
			resume_file = params['resume_file_name', String];
			resume_mogile = params['resume_file_mogile', String];
			
			aa_cover_letter = ApplicantAsset.new();
			
			aa_cover_letter.appid = app.appid;
			aa_cover_letter.userfilename = cover_letter_file;
			aa_cover_letter.mogilefilename = cover_letter_mogile;
			aa_cover_letter.description = "Cover Letter";
			aa_cover_letter.assettype = cover_letter_type.assettypeid;
			
			aa_cover_letter.store();
			
			aa_resume = ApplicantAsset.new();
			
			aa_resume.appid = app.appid;
			aa_resume.userfilename = resume_file;
			aa_resume.mogilefilename = resume_mogile;
			aa_resume.description = "Resume";
			aa_resume.assettype = resume_type.assettypeid;
			
			aa_resume.store();
			
			mail_message = StringIO.new();
			mail_message.print("<html><body>");
			mail_message.print("A new application for the <b>#{job.title}</b> position has been recieved from <b>#{app.name}</b>.<br/><br/>");
			mail_message.print("You can access the submitted information and assets at the ");
			mail_message.print("<a href=\"#{$site.www_url}/jobs/administration/applicant/#{app.appid}/\">Applicant Profile</a>.<br/><br/>");
			mail_message.print("You can view all applications for the #{job.title} position at the ");
			mail_message.print("<a href=\"#{$site.www_url}/jobs/administration/job/#{job.jobid}/applicants/\">Job Summary</a> page.<br/><br/><br/><br/>");
			mail_message.print("<span style=\"font-size:8pt;\">This is an automated message from the Nexopia Jobs System. Please do not respond to this email.</span>");
			mail_message.print("</body></html>");
			mail_message_text = mail_message.string;
			
			mail_subject = "New Application for #{job.title}";
			mail_author = "jobs-system@nexopia.com";
			
			ip_list = InterestedParty.find(:jobid, job_id);
			
			manager_id_list = Array.new();
			
			for ip in ip_list
				manager_id_list << ip.userid;
			end
			
			manager_list = JobManager.find(:userid, *manager_id_list);
			
			for manager in manager_list
				JobMailer.send_notification(manager.email, mail_subject, mail_message_text, mail_author);
			end
			
			site_redirect("/jobs/job/#{job_id}/apply/complete/");
		end
		
		def create_dep_application(dep_id)
			
			app_dep_id = params['app_dep_id', Integer];
			if(app_dep_id == nil)
				return;
			end
			
			dep = Department.find(:first, app_dep_id);
			if(dep == nil)
				return;
			end
			
			app_name = params['app_name', String];
			app_email = params['app_email', String];
			app_phone = params['app_phone', String];
			app_contact_type = params['app_contact_type', String];
			app_contact_details = params['app_contact_details', String];
			
			app = Applicant.new();
			
			app.name = app_name;
			app.email = app_email;
			app.phonenumber = app_phone;
			app.contactmethod = app_contact_type;
			app.contactnotes = app_contact_details;
			
			app.store();
			
			dep_app = DepartmentApplicant.new();
			
			dep_app.depid = app_dep_id;
			dep_app.appid = app.appid;
			dep_app.date = Time.now.to_i();
			
			dep_app.store();
			
			resume_type = AssetType.find(:first, :conditions => ["name = ?", "Resume"]);
			
			if(resume_type == nil)
				AssetType.create_default_entries();
				resume_type = AssetType.find(:first, :conditions => ["name = ?", "Resume"]);
			end
			
			cover_letter_type = AssetType.find(:first, :conditions => ["name = ?", "Cover Letter"]);
			
			cover_letter_file = params['cover_letter_file_name', String];
			cover_letter_mogile = params['cover_letter_file_mogile', String];
			resume_file = params['resume_file_name', String];
			resume_mogile = params['resume_file_mogile', String];
			
			aa_cover_letter = ApplicantAsset.new();
			
			aa_cover_letter.appid = app.appid;
			aa_cover_letter.userfilename = cover_letter_file;
			aa_cover_letter.mogilefilename = cover_letter_mogile;
			aa_cover_letter.description = "Cover Letter";
			aa_cover_letter.assettype = cover_letter_type.assettypeid;
			
			aa_cover_letter.store();
			
			aa_resume = Jobs::ApplicantAsset.new();
			
			aa_resume.appid = app.appid;
			aa_resume.userfilename = resume_file;
			aa_resume.mogilefilename = resume_mogile;
			aa_resume.description = "Resume";
			aa_resume.assettype = resume_type.assettypeid;
			
			aa_resume.store();
			
			mail_message = StringIO.new();
			mail_message.print("<html><body>");
			mail_message.print("A new application for <b>#{dep.name}</b> has been recieved from <b>#{app.name}</b>.<br/><br/>");
			mail_message.print("You can access the submitted information and assets at the ");
			mail_message.print("<a href=\"#{$site.www_url}/jobs/administration/applicant/#{app.appid}/\">Applicant Profile</a>.<br/><br/>");
			mail_message.print("You can view all applications for the #{dep.name} department at the ");
			mail_message.print("<a href=\"#{$site.www_url}/jobs/administration/department/#{dep.depid}/applicants/\">Department Application Summary</a> page.<br/><br/><br/><br/>");
			mail_message.print("<span style=\"font-size: 8pt;\">This is an automated message from the Nexopia Jobs System. Please do not respond to this email.</span>");
			mail_message.print("</body></html>");
			mail_message_text = mail_message.string;
			
			mail_subject = "New Application for #{dep.name}";
			mail_author = "jobs-system@nexopia.com";
			
			dm_list = DepartmentManager.find(:depid, app_dep_id);
			
			manager_id_list = Array.new();
			
			for dm in dm_list
				manager_id_list << dm.userid;
			end
			
			manager_list = JobManager.find(:userid, *manager_id_list);
			
			for manager in manager_list
				JobMailer.send_notification(manager.email, mail_subject, mail_message_text, mail_author);
			end
			
			site_redirect("/jobs/department/#{dep_id}/apply/complete/");
		end
	end
end
