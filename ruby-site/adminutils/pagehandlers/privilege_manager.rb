class PrivilegeManager < PageHandler
	declare_handlers("privileges") {
		area :Admin
		access_level :Admin, CoreModule, :editadmins
		page :GetRequest, :Full, :create_permission, 'create'
	}
	
	def create_permission
		module_id = params["site_module", Integer]
		privilege_name = params["name", String]
		privilege = Privilege::Storage::PrivilegeName.new
		privilege.moduleid = module_id
		privilege.name = privilege_name
		privilege.store if (privilege_name && module_id)
		path = request.headers["HTTP_REFERER"].gsub(/http:\/\/.*?\/admin\//, '/')
		site_redirect(path)
	end
end