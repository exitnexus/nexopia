class StoreHandler < PageHandler
	declare_handlers("webrequest") {
		area :Internal

		access_level :Any
		handle :GetRequest, :store, "store.#{$site.config.base_domain}"
	}
	
	def store
		t = Template.instance("store", "store")
		puts(%Q{<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">})
		puts(t.display)
	end
end
