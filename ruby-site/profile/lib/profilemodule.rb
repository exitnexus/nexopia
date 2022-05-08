class ProfileModule < SiteModuleBase
	def after_load()
		lib_require :Profile, "user_queues"
	end
end