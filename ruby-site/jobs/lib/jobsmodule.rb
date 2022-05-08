lib_require :Worker, "kernel_addon"

class JobsModule < SiteModuleBase
	worker_task :upload_handle;
	worker_task :remove_asset;
	
	def self.upload_handle(filename, userid, params, original_filename)
		
		#if(params["new_job_app_submit"] != nil)
			#create_application(params);
		#elsif(params["new_dep_app_submit"] != nil)
			#create_dep_application(params);
		#end
	end
	
	def self.remove_asset(mogile_filename)
		begin
			$site.mogilefs.delete(mogile_filename, "uploads");
		rescue Exception => e
			$log.error("Unable to delete file #{mogile_filename}");
			$log.error(e.message);
		end
	end
	

end