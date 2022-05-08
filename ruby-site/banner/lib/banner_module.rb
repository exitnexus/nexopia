class BannerModule < SiteModuleBase
	def after_load()
		lib_require :Banner, "banner"
	end
end