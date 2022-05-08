lib_require :Core, 'sitemodule'

class ModqueueModule < SiteModuleBase
	lib_require :Core, 'typeid'
	extend TypeID
	
	def moderate_queue_name
		return "Modqueue"
	end
end
