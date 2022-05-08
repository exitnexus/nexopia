class WorkerModule < SiteModuleBase
	
	def after_load()
		lib_require :Worker, "worker"
	end
end
