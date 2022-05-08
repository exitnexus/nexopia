lib_require :Core, 'storable/cacheable';
lib_require :Jobs, 'requested_asset', 'asset_type', 'applicant', 'job_manager', 'supervisor', 'job_application', 'department_applicant', 'department';

module Jobs
	ApplicantDisplayList = Struct.new("ApplicantDisplayList", :job_list, :dep_list);
	
	class JobPosting < Cacheable
		init_storable(:jobsdb, 'jobpostings');

		attr_accessor :selected, :opening_date_string, :closing_date_string, :jd_opening_date_string, :jd_closing_date_string;
		
		relation_singular(:department, :depid, Department);
		relation_singular(:author, :creator, JobManager);
		
		def create()
			@selected = false;
		end
		
		def after_load
			if(self.openingdate > 0)
				@opening_date_string = Time.at(self.openingdate).strftime("%d/%m/%Y");
				@jd_opening_date_string = Time.at(self.openingdate).strftime("%B %d, %Y");
			else
				@opening_date_string = "";
			end
			if(self.closingdate > 0)
				@closing_date_string = Time.at(self.closingdate).strftime("%d/%m/%Y");
				@jd_closing_date_string = Time.at(self.closingdate).strftime("%B %d, %Y");
			else
				@closing_date_string = "";
			end
		end
		
		def uri_info(mode = 'self')
			case mode
			when 'self'
				return [self.title, "/jobs/job/#{self.jobid}#job_details"];
			when 'apply'
				return ['', "/jobs/job/#{self.jobid}/apply/"];
			when 'admin_view'
				return [self.title, "/jobs/administration/job/#{self.jobid}/"];
			end
		end

		def requested_assets()
			req_asset_list = RequestedAsset.find(:jobid, self.jobid);
			
			asset_type_id_list = Array.new();
			
			for asset in req_asset_list
				asset_type_id_list << asset.assettypeid;
			end
			
			asset_type_list = AssetType.find(*asset_type_id_list);
			
			return asset_type_list;
		end
		
		def applicants()
			job_apps = JobApplication.find(self.jobid, :order => "date ASC");
			
			job_app_id_list = Array.new();
			dep_app_id_list = Array.new();
			
			for job_app in job_apps
				job_app_id_list << job_app.appid;
			end
			
			if(!job_app_id_list.empty?())
				dep_apps = DepartmentApplicant.find(self.depid, :conditions => ["appid NOT IN ?", job_app_id_list], :order => "date DESC");
			else
				dep_apps = DepartmentApplicant.find(self.depid);
			end
			
			
			for dep_app in dep_apps
				dep_app_id_list << dep_app.appid;
			end
			
			job_app_list = Applicant.find(*job_app_id_list);
			dep_app_list = Applicant.find(*dep_app_id_list);
			
			organized_applicants = ApplicantDisplayList.new();
			organized_applicants.dep_list = sort_applicant_list(dep_app_list, dep_apps);
			organized_applicants.job_list = sort_applicant_list(job_app_list, job_apps);
			
			$log.object organized_applicants.dep_list;
			
			return organized_applicants;
		end
		
		def sort_applicant_list(applicant_list, sorted_pair_list)
			sorted_applicant_list = Array.new();
			for temp_pair in sorted_pair_list
				temp_app = applicant_list[temp_pair.appid];
				if(temp_app == nil)
					next;
				end
				sorted_applicant_list << temp_app;
			end
			
			return sorted_applicant_list;
		end
		
		def dep_match(dep_id)
			if(self.depid != nil && self.depid == dep_id)
				return "selected";
			end
			return false;
		end
		
		def no_dep()
			if(self.depid == nil || self.depid <= 0)
				return "selected";
			end
			return false;
		end
		
		def JobPosting.open?(job_id)
			job = JobPosting.find(:first, job_id);
			
			return job.open;
		end
		
		def JobPosting.open_jobs()
			return JobPosting.find(:conditions => ["(openingdate < ? AND (closingdate > ? OR closingdate = 0))", Time.now.to_i(), Time.now.to_i()], :order => "openingdate ASC");
		end
		
		def JobPosting.open_job_count(exception_id_list = nil)
			if(exception_id_list == nil || exception_id_list.empty?())
				sql_query = "Select count(jobid) AS jobcount From jobpostings WHERE (openingdate < ? AND (closingdate > ? OR closingdate = 0))";
				sql_query = self.db.prepate(sql_query, Time.now.to_i(), Time.now.to_i());
			else
				sql_query = "Select count(jobid) AS jobcount From jobpostings WHERE jobid NOT IN ? AND (openingdate < ? AND (closingdate > ? OR closingdate = 0))";
				sql_query = self.db.prepare(sql_query, exception_id_list, Time.now.to_i(), Time.now.to_i());
			end
			
			result = self.db.query(sql_query);
			
			for row in result
				job_count = row["jobcount"];
			end
			
			return job_count;
		end
		
		def JobPosting.available_jobs(app)
			job_app_list = JobApplication.find(:appid, app.appid);
			
			job_app_id_list = Array.new();
			
			for job_app in job_app_list
				job_app_id_list << job_app.jobid;
			end
			
			if(!job_app_id_list.empty?())
				job_list = JobPosting.find(:conditions => ["jobid NOT IN ? AND (openingdate < ? AND (closingdate > ? OR closingdate = 0))", job_app_id_list, Time.now.to_i(), Time.now.to_i()]);
			else
				#this should never occur since the function is only used for reapplicants, but this is here just in case.
				job_list = JobPosting.open_jobs();
			end
			
			return job_list;
		end
	end
end
