class UserFilesModule < SiteModuleBase
	def after_load()
		lib_require :UserFiles, "user_file_type"
	end
end