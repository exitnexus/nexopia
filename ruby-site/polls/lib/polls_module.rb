class PollsModule < SiteModuleBase
	def after_load()
		lib_require :Polls, "polls_queue"
	end
end