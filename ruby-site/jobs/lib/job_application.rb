lib_require :Core, 'storable/cacheable';

module Jobs
	class JobApplication < Cacheable
		init_storable(:jobsdb, 'jobapplications');
		
		attr_accessor :app_date_string;
		
		def after_load
			@app_date_string = Time.at(self.date).strftime("%B %d, %Y");
		end
	end
end
