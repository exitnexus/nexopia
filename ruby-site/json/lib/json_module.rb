class JsonModule < SiteModuleBase
	def after_load()
		lib_require :json, "exported"
	end
end