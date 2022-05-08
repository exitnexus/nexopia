lib_require :Core, 'storable/storable'

class DeletedUser < Storable
	init_storable(:db, "deletedusers");
	
	def uri_info(*args)
		return [username, nil]
	end
	
	def img_info(type = 'landscapethumb')
		return [username, $site.static_files_url/:Userpics/:images/"no_profile_image_#{type}.gif"]
	end
end