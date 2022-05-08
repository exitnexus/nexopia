class ModeratorModule < SiteModuleBase
	def after_load()
		lib_require :Moderator, "modqueue", "moderator", "unplaced_queues"
	end
end