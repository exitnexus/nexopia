lib_require :UserDump, "user_dump_controller", "user_ip_activity"

class UserDump < PageHandler

	declare_handlers("user_dump") {
		area :Admin
		access_level :Admin, UserDumpModule, :userdump
		
		page :GetRequest, :Full, :user_dump_form
		handle :PostRequest, :create_user_dump, "submit"
	}

	def user_dump_form
		t = Template.instance("user_dump", "user_dump")
		
		t.registered_classes = UserDumpController.registered_classes
		t.user_id = params['user_id', Integer]
		
		print t.display()
		
	end

	def create_user_dump

		zip_file_path = UserDumpController.dump(params, request)
		
		# Get the whole zipfile and then make sure it's been deleted.
		begin
			archive_contents = File.open(zip_file_path, "r").gets(nil)
		ensure
			File.delete(zip_file_path) if File.exists?(zip_file_path)
		end

		# Return our zip to the browser.
		reply.headers['Content-Type'] = PageRequest::MimeType::ZIP
		reply.headers['Content-Disposition'] = 'attachment; filename="' + File.basename(zip_file_path) + '"'
		puts archive_contents
		
	end

end