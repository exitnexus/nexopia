lib_require :Jobs, 'job_posting', 'applicant', 'department', 'department_applicant', 'job_application', 'applicant_asset', 'asset_type', 'applicant_view_request', 'validation_rules';
lib_require :Core, "validation/display", "validation/set", "validation/results", "validation/rules", "validation/chain", "validation/rule", "validation/value_accessor";


module Jobs
	class ApplicantHandler < PageHandler
		declare_handlers("jobs/applicant"){
			area :Public
			access_level :Any
			
			page	:GetRequest, :Full, :applicant_view_job_dep_postings, input(String), "list";
			page	:GetRequest, :Full, :applicant_view_job_details, input(String), "job", input(Integer);
			
			page	:GetRequest, :Full, :applicant_confirm_job_application, input(String), "job", input(Integer), "apply";
			handle	:PostRequest, :applicant_create_job_application, input(String), "job", input(Integer), "apply", "submit";
			
			page	:GetRequest, :Full, :applicant_view_department_details, input(String), "department", input(Integer);
			page	:GetRequest, :Full, :applicant_confirm_department_application, input(String), "department", input(Integer), "apply";
			
			handle	:PostRequest, :applicant_create_department_application, input(String), "department", input(Integer), "apply", "submit";
			
			page	:GetRequest, :Full, :edit_applicant, input(String), "edit";
			page	:GetRequest, :Full, :update_applicant, input(String), "edit", "submit";
			
			page	:GetRequest, :Full, :view_applicant, input(String);
			
			page	:GetRequest, :Full, :new_applicant_view_request, "request";
			handle	:PostRequest, :generate_applicant_view_request, "request", "submit";
			page	:GetRequest, :Full, :complete_applicant_view_request, "request", "complete";
			
			page	:GetRequest, :Full, :edit_applicant_asset, input(String), "asset", "type", input(Integer), "edit";
			page	:GetRequest, :Full, :update_applicant_asset, input(String), "asset", "type", input(Integer), "edit", "submit";
		};
		
		def view_applicant(app_hash)
			app_request = ApplicantViewRequest.find(:first, :conditions => ["hash = ?", app_hash]);
			
			request.reply.headers['X-width'] = 0;
			
			if(app_request.nil?() || !app_request.active?())
				error_title = ApplicantViewRequest::ErrorTitle;
				if(app_request.nil?())
					error_message = ApplicantViewRequest::RequestInvalid;
				else
					error_message = ApplicantViewRequest::RequestExpired;
				end
				
				self.show_error(error_title, error_message);
				
				return;
			end
			
			t = Template.instance("jobs", "applicant_view");
			
			app = Applicant.find(:first, app_request.appid);
			
			t.app_list = app.previous_applications();
			
			prev_job_id_list = Array.new();
			
			for job_app in t.app_list[:job]
				prev_job_id_list << job_app.target.jobid;
			end
			
			t.job_count = JobPosting.open_job_count(prev_job_id_list);
			
			t.app = app;
			t.app_hash = app_hash;
			
			print t.display();
		end
		
		def new_applicant_view_request()
			t = Template.instance("jobs", "request_applicant_view");
			
			request.reply.headers['X-width'] = 0;
			
			print t.display();
		end
		
		def generate_applicant_view_request()
			req_email = params["app_request_email", String];
			
			request.reply.headers['X-width'] = 0;
			
			app = Applicant.find(:first, :conditions => ["email = ?", req_email]);
			
			if(app.nil?())
				#error page
			end
			
			app_request = ApplicantViewRequest.find(:first, app.appid);
			
			if(app_request.nil?())
				app_request = ApplicantViewRequest.new();
			end
			
			app_request.appid = app.appid;
			app_request.generate_hash(app.email);
			app_request.date = Time.now.to_i();
			
			app_request.store();
			
			mail_message = StringIO.new();
			mail_message.print("<html><body>");
			mail_message.print("Your applicant profile for Nexopia.com is available <a href=\"#{app_request.generate_email_uri()}\">here</a>.<br/><br/>");
			mail_message.print("<span style=\"font-size: 8pt;\">This is an automated message from the Nexopia Jobs System. Please do not respond to this email.</span>");
			mail_message.print("</body></html>");
			mail_message_text = mail_message.string;
			
			mail_subject ="Nexopia.com Application Profile Link";
			mail_author = "jobs-system@nexopia.com";
			
			Jobs::JobMailer.send_notification(app.email, mail_subject, mail_message_text, mail_author);
			
			site_redirect("/jobs/applicant/request/complete");
			
		end
		
		def complete_applicant_view_request()
			t = Template.instance("jobs", "applicant_request_complete");
			
			request.reply.headers['X-width'] = 0;
			
			print t.display();
		end
		
		def edit_applicant(app_hash)
			app_request = ApplicantViewRequest.find(:first, :conditions => ["hash = ?", app_hash]);
			
			request.reply.headers['X-width'] = 0;
			
			if(app_request.nil?() || !app_request.active?())
				error_title = ApplicantViewRequest::ErrorTitle;
				if(app_request.nil?())
					error_message = ApplicantViewRequest::RequestInvalid;
				else
					error_message = ApplicantViewRequest::RequestExpired;
				end
				
				self.show_error(error_title, error_message);
				
				return;
			end
			
			t = Template.instance("jobs", "edit_applicant");
			
			app = Applicant.find(:first, app_request.appid);
			
			t.app = app;
			t.app_hash = app_hash;
			
			print t.display();
		end
		
		def update_applicant(app_hash)
			app_name = params['app_name', String];
			app_email = params['app_email', String];
			app_phone = params['app_phone', String];
			app_contact_type = params['app_contact_type', String];
			app_contact_details = params['app_contact_details', String];
			
			app_request = ApplicantViewRequest.find(:first, :conditions => ["hash = ?", app_hash]);
			if(app_request.nil?() || !app_request.active?())
				#display error
			end
			
			app = Applicant.find(:first, app_request.appid);
			
			app.name = app_name;
			app.email = app_email;
			app.phonenumber = app_phone;
			app.contactmethod = app_contact_type;
			app.contactnotes = app_contact_details;
			
			app.store();
			
			site_redirect("/jobs/applicant/#{app_hash}/");
		end
		
		
		def update_applicant_asset(app_hash, asset_type_id)
			app_request = ApplicantViewRequest.find(:first, :conditions => ["hash = ?", app_hash]);
			
			if(app_request.nil?() || !app_request.active?())
				#display error
			end
			
			asset_type = AssetType.find(:first, asset_type_id);
			
			if(asset_type.nil?())
				#display error
			end
			
			asset_filename = params['asset_file_name', String];
			asset_mogilefilename = params['asset_file_mogile', String];
			
			new_asset = ApplicantAsset.new();
			new_asset.appid = app_request.appid;
			new_asset.userfilename = asset_filename;
			new_asset.mogilefilename = asset_mogilefilename;
			new_asset.assettype = asset_type_id;
			
			new_asset.store();
			
			old_asset = ApplicantAsset.find(:first, :conditions => ["appid = ? AND assettype = ?", app_request.appid, asset_type_id]);
			
			WorkerModule.do_task(SiteModuleBase.get("Jobs"), "remove_asset", [old_asset.mogilefilename]);
			
			old_asset.delete();
			
			site_redirect("/jobs/applicant/#{app_hash}/");
		end
		
		
				def applicant_view_department_details(app_hash, dep_id)
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
			
			dep = Department.find(:first, dep_id);
			if(dep.nil?())
			   #spit out error
			end
			
			app = Applicant.find(:first, app_request.appid);
			
			t = Template.instance("jobs", "applicant_reapply_view");
			
			t.dep = dep;
			
			job_list = JobPosting.available_jobs(app);
			dep_list = Department.available_departments(app);
			
			
			for temp_dep in dep_list
				if(temp_dep.depid == dep_id)
					dep = temp_dep;
					temp_dep.selected = true;
					break;
				end
			end
			
			
			t.job_list = job_list;
			t.dep_list = dep_list;
			
			t.app_hash = app_hash;
			
			print t.display();
		end
		
		def applicant_confirm_department_application(app_hash, dep_id)
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
			
			app = Applicant.find(:first, app_request.appid);
			
			t = Template.instance("jobs", "applicant_confirm_application");
			
			submit_location = "applicant/#{app_hash}/department/#{dep_id}/apply";
			
			t.app = app;
			t.app_hash = app_hash;
			t.submit_location = submit_location;
			
			print t.display();
		end
		
		def applicant_create_department_application(app_hash, dep_id)
			app_request = ApplicantViewRequest.find(:first, :conditions => ["hash = ?", app_hash]);
			
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
			
			app = Applicant.find(:first, app_request.appid);
			
			dep = Department.find(:first, dep_id);
			
			dep_app = DepartmentApplicant.new();
			
			dep_app.depid = dep_id;
			dep_app.appid = app.appid;
			dep_app.date = Time.now.to_i();
			
			dep_app.store();
			
			site_redirect("/jobs/applicant/#{app_hash}/");
		end
		
		def applicant_create_job_application(app_hash, job_id)
			$log.info("NARG!!!");
			app_request = ApplicantViewRequest.find(:first, :conditions => ["hash = ?", app_hash]);
			
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
			
			app = Applicant.find(:first, app_request.appid);
			
			job = JobPosting.find(:first, job_id);
			
			job_app = JobApplication.new();
			
			job_app.jobid = job_id;
			job_app.appid = app.appid;
			job_app.date = Time.now.to_i();
			
			job_app.store();
			
			site_redirect("/jobs/applicant/#{app_hash}/");
		end
		
		def applicant_confirm_job_application(app_hash, job_id)
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
			
			app = Applicant.find(:first, app_request.appid);
			
			t = Template.instance("jobs", "applicant_confirm_application");
			
			submit_location = "applicant/#{app_hash}/job/#{job_id}/apply";
			
			t.app = app;
			t.app_hash = app_hash;
			t.submit_location = submit_location;
			
			print t.display();
		end
		
		def applicant_view_job_details(app_hash, job_id)
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
			
			job = JobPosting.find(:first, job_id);
			if(job.nil?())
			   #spit out error
			end
			
			app = Applicant.find(:first, app_request.appid);
			
			t = Template.instance("jobs", "applicant_reapply_view");
			
			t.job = job;
			
			job_list = JobPosting.available_jobs(app);
			dep_list = Department.available_departments(app);
			
			
			for temp_job in job_list
				if(temp_job.jobid == job_id)
					job = temp_job;
					temp_job.selected = true;
					break;
				end
			end
			
			
			t.job_list = job_list;
			t.dep_list = dep_list;
			
			t.app_hash = app_hash;
			
			print t.display();
		end
		
		def applicant_view_job_dep_postings(app_hash)
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
			
			app = Applicant.find(:first, app_request.appid);
			
			t = Template.instance("jobs", "applicant_reapply_view");
			
			job_list = JobPosting.available_jobs(app);
			dep_list = Department.available_departments(app);
			
			t.job_list = job_list;
			t.dep_list = dep_list;
			
			t.app_hash = app_hash;
			
			print t.display();
		end
	end
end

