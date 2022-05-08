require 'yaml';

lib_require :Core, 'storable/cacheable';
lib_require :Jobs, 'applicant_asset', 'job_application', 'department_applicant', 'interview', 'availability_type';

module Jobs
	AssetOrganizer = Struct.new("AssetOrganizer", :asset_type, :asset_list);
	ApplicantNote = Struct.new("ApplicantNote", :note, :author, :date_added);
	InterviewList = Struct.new("InterviewList", :job_list, :dep_list);
	PreviousApplication = Struct.new("PreviousApplication", :application, :target);
	
	class Applicant < Cacheable
		init_storable(:jobsdb, 'applicants');
		
		attr_accessor :notes_array, :formatted_phone_number;
		
		relation_multi(:job_applications, :appid, JobApplication);
		relation_multi(:dep_applications, :appid, DepartmentApplicant);
		relation_multi(:assets, :appid, :appid, ApplicantAsset);
		relation_multi(:interviews, :appid, :appid, Interview);
		relation_singular(:availability_status, :availability, AvailabilityType);
		
		def after_load()
			if(self.phonenumber.nil?())
			   phone_string = "";
			else
				phone_string = self.phonenumber.to_s();
			end
			
			if(phone_string.length == 7)
				p1 = phone_string[0,3];
				p2 = phone_string[3,4];
				self.formatted_phone_number = "#{p1}-#{p2}";
			elsif(phone_string.length == 10)
				a1 = phone_string[0,3];
				p1 = phone_string[3,3];
				p2 = phone_string[6,4];
				self.formatted_phone_number = "(#{a1}) #{p1}-#{p2}";
			else
				self.formatted_phone_number = "";
			end
		end
		
		def uri_info(mode = "self")
			case mode
			when "self"
				return [self.name, "/jobs/administration/applicant/#{self.appid}/"];
			end
		end
		
		def structured_assets()
			app_assets = ApplicantAsset.find(:appid, self.appid);
			
			temp_asset_holder = Hash.new();
			
			for asset in app_assets
				holder = temp_asset_holder[asset.assettype];
				if(holder == nil)
					holder = Array.new();
					temp_asset_holder[asset.assettype] = holder;
				end
				holder << asset;
			end
			
			asset_type_list = AssetType.find(*temp_asset_holder.keys());
			
			organized_assets = Array.new();
			
			for asset_type in asset_type_list
				temp = AssetOrganizer.new();
				temp.asset_type = asset_type;
				temp.asset_list = temp_asset_holder[asset_type.assettypeid];
				organized_assets << temp;
			end
			
			return organized_assets;
		end
		
		def job_application(job_id)
			return JobApplication.find(:first, [job_id, self.appid]);
		end
		
		def job_application_date(job_id)
			job_app = self.job_application(job_id);
			if(job_app == nil)
				job = Jobs::JobPosting.find(:first, job_id);
				return dep_application_date(job.depid);
			end
			app_date = self.job_application(job_id).date;
			
			return Time.at(app_date).strftime("%d/%m/%Y");
		end
		
		def dep_application(dep_id)
			return DepartmentApplicant.find(:first, [dep_id, self.appid]);
		end
		
		def dep_application_date(dep_id)
			dep_date = self.dep_application(dep_id).date;
			
			return Time.at(dep_date).strftime("%d/%m/%Y");
		end
		
		def job_interview_date(job_id)
			interview = Interview.find(:first, :jobapp, [self.appid, job_id]);
			
			if(interview.nil?())
				return nil;
			end
			
			interview_date = interview.interviewdate;
			
			if(interview_date > 0)
				return Time.at(interview_date).strftime("%d/%m/%Y ");
			else
				return nil;
			end
		end
		
		def dep_interview_date(dep_id)
			interview = Interview.find(:first, :depapp, [self.appid, dep_id]);
			
			if(interview.nil?())
				return nil;
			end
			
			interview_date = interview.interviewdate;
			
			if(interview_date > 0)
				return Time.at(interview_date).strftime("%d/%m/%Y");
			else
				return nil
			end
		end
		
		def job_interview?(job_id)
			interview = Interview.find(:first, :jobapp, [self.appid, job_id]);
			
			if(interview.nil?())
				return false;
			end
			
			interview_date = interview.interviewdate;
			
			if(interview_date > 0)
				return true;
			else
				return false;
			end
		end
		
		def dep_interview?(dep_id)
			interview = Interview.find(:first, :depapp, [self.appid, dep_id]);
			
			if(interview.nil?())
				return false;
			end
			
			interview_date = interview.interviewdate;
			
			if(interview_date > 0)
				return true;
			else
				return false;
			end
		end
		
		def previous_applications()
			
			job_app_list = JobApplication.find(:appid, self.appid);
			dep_app_list = DepartmentApplicant.find(:appid, self.appid);
			
			job_id_list = Array.new();
			for job_app in job_app_list
				job_id_list << job_app.jobid;
			end
			
			dep_id_list = Array.new();
			for dep_app in dep_app_list
				dep_id_list << dep_app.depid;
			end
			
			job_list = JobPosting.find(*job_id_list);
			dep_list = Department.find(*dep_id_list);
			
			prev_apps = Hash.new();
			prev_apps[:dep] = Array.new();
			prev_apps[:job] = Array.new();
			
			for dep in dep_list
				temp = PreviousApplication.new();
				temp.target = dep;
				temp.application = dep_app_list[[dep.depid, self.appid]];
				
				prev_apps[:dep] << temp;
			end
			
			for job in job_list
				temp = PreviousApplication.new();
				temp.target = job;
				temp.application = job_app_list[[job.jobid, self.appid]];
				
				prev_apps[:job] << temp;
			end
			
			return prev_apps;
		end
		
		def add_note(note, author_id)
			app_note = ApplicantNote.new();
			app_note.note = note;
			app_note.author = author_id;
			app_note.date_added = Time.now.to_i();
			
			self.notes_array << app_note;
			sync_notes();
		end
		
		def remove_note(note_id)
			self.notes_array.delete_at(note_id);
			sync_notes();
		end
		
		def notes_list()
			if(self.notes_array.nil?());
				initialize_notes();
			end
			
			return self.notes_array;
		end
		
		def sync_notes()
			self.notes = self.notes_array.to_yaml();
		end
		
		def initialize_notes()
			if(self.notes != "")
				self.notes_array = YAML.load(self.notes);
			else
				self.notes_array = Array.new();
			end
			
			if(!self.notes_array.kind_of?(Array))
				self.notes_array = Array.new();
			end
		end
		
		def interview_target_list(job_id)
			
			target_list = InterviewList.new();
			target_list.job_list = self.interview_jobs_list(job_id);
			target_list.dep_list = self.interview_dep_list();

			return target_list;
		end
		
		def interview_jobs_list(job_id)
			if(job_id != nil)
				
			end
			job_app_list = JobApplication.find(:appid, self.appid);
			
			open_job_id_list = Array.new();
			for job_app in job_app_list
				if(JobPosting.open?(job_app.jobid))
					open_job_id_list << job_app.jobid;
				end
			end
			
			open_job_id_list << job_id;
			
			job_list = Jobs::JobPosting.find(*open_job_id_list);
			
			return job_list;
		end
		
		def interview_dep_list()
			dep_app_list = DepartmentApplicant.find(:appid, self.appid);
			
			dep_id_list = Array.new();
			for dep_app in dep_app_list
				dep_id_list << dep_app.depid;
			end
			
			dep_list = Department.find(*dep_id_list);
			
			return dep_list;
		end
		
		def interview_list()
			interviews = Interview.find(:appid, self.appid);
			
			return interviews;
		end
		
		def preferred_contact_method()
			if(self.contactmethod == "email")
				return "E-mail";
			else
				return "Phone";
			end
		end
		
		def contact_email()
			if(self.contactmethod == "email")
				return "checked";
			end
			return false;
		end
		
		def contact_phone()
			if(self.contactmethod == "phone")
				return "checked";
			end
			return false;
		end
		
		def cover_letter()
			asset_list = assets_by_type_name("Cover Letter");
			if(asset_list.nil?())
				return nil;
			end
			return asset_list.first;
		end
		
		def resume()
			asset_list = assets_by_type_name("Resume");
			if(asset_list.nil?())
				return nil;
			end
			return asset_list.first;
		end
		
		def assets_by_type_name(type_name)
			asset_type = AssetType.find(:first, :conditions => ["name = ?", type_name]);
			
			if(asset_type.nil?())
			   return nil;
			end
			
			app_assets = ApplicantAsset.find(:appid, self.appid, :conditions => ["assettype = ?", asset_type.assettypeid]);
			
			return app_assets;
		end
	end
end
