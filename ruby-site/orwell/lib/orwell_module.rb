class OrwellModule < SiteModuleBase
	def after_load
		lib_require :Orwell, 'process_users'
	end
end