lib_require :Worker, "kernel_addon"

class FriendFinderModule < SiteModuleBase

	def self.upload_handle(filename, userid, params, original_filename)
	end
	worker_task :upload_handle
	
	def self.remove_contacts_file(file_name)
		$site.mogilefs.delete("#{$site.mogilefs.class::UPLOADS}/#{file_name}")
		File.delete("#{$site.config.pending_dir}/#{file_name}")
	end
	worker_task :remove_contacts_file
end
