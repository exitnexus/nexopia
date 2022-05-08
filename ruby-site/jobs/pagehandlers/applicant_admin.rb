module Jobs
	class JobsApplicantAdminHandler < PageHandler
		declare_handlers("jobs/administration/applicant"){
			area :Public
			access_level :Admin, JobsModule, :edit
			
			page	:GetRequest, :Full, :display_job_applicant, input(Integer), "job", input(Integer);
			page	:GetRequest, :Full, :display_dep_applicant, input(Integer), "dep", input(Integer);
			page	:GetRequest, :Full, :display_applicant, input(Integer);
			
			page	:PostRequest, :add_applicant_note, input(Integer), "note", "add";
			
		}
		
		def display_applicant(app_id)
			t = Template.instance("jobs", "view_job_applicant");
			
			request.reply.headers['X-width'] = 0;
			
			applicant = Applicant.find(:first, app_id);
			
			if(applicant.nil?())
				site_redirect("/jobs/");
			end
			
			t.app = applicant;
			
			print t.display();
		end
		
		def display_job_applicant(app_id, job_id)
			t = Template.instance("jobs", "view_job_applicant");
			
			request.reply.headers['X-width'] = 0;
			
			applicant = Applicant.find(:first, app_id);
			
			if(applicant.nil?())
				site_redirect("/jobs/");
			end
			
			t.app = applicant;
			t.admin_view = true;
			
			print t.display();
		end
		
		def display_dep_applicant(app_id, dep_id)
			
		end
		
		def add_applicant_note(app_id)
			app_note = params['applicant_note', String];
			
			app = Applicant.find(:first, app_id);
			
			user_id = request.session.user.userid;
			
			app.add_note(app_note, user_id);
		end
	end
end
