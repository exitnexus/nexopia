lib_require :Worker, "workermodule.rb"

module Kernel
	def worker_task(name)
		if (!@@current_module)
			raise "Not in a site module."
		end
		WorkerModule.add_handler(@@current_module, self, name);
	end
end
