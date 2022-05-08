module Jobs
	class Interview < Cacheable
		init_storable(:jobsdb, "interviews");
		
		def uri_info(mode = 'self')
			case mode
			when "self"
				if(jobid != nil && jobid != 0)
					self.generate_job_link();
				elsif(depid != nil && depid != 0)
					self.generate_dep_link();
				end
			end
		end
		
		def generate_job_link()
			job = JobPosting.find(:first, self.jobid);
			
			return [job.title, "/jobs/administration/job/#{self.jobid}/preview/"];
		end
		
		def generate_dep_link()
			dep = Department.find(:first, self.depid);
			
			return [dep.name, "/jobs/administration/department/#{self.depid}/preview/"];
		end
	end
end