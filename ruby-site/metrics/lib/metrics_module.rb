class MetricsModule < SiteModuleBase
	def after_load
		lib_require :Metrics, 'category_all'
	end
end