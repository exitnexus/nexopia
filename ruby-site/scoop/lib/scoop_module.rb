class ScoopModule < SiteModuleBase
	def after_load
		lib_require :Scoop, 'event'
	end
end