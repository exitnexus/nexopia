require 'digest/md5'

module Jobs
	class ApplicantViewRequest < Storable
		init_storable(:jobsdb, "applicantviewrequests");
		
		ErrorTitle = "Applicant Access Error";
		RequestExpired = "The code provided has expired. Please go <a href=\"/jobs/applicant/request/\">here</a> to obtain a new access code.";
		RequestInvalid = "The code provided to access this page is invalid.";
		
		def active?()
			expiry_time = self.date + (60 * 60 * 4);
			
			if(expiry_time > Time.now.to_i())
				return true;
			end
			return false;
		end
		
		def generate_hash(app_email)
			self.hash = Digest::MD5.new("#{rand(10000)}#{app_email}#{Time.now.to_i}").to_s();
		end
		
		def generate_email_uri()
			return "#{$site.www_url}/jobs/applicant/#{self.hash}/";
		end
	end
end
