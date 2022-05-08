lib_require :Core, 'pagehandler'
lib_require :Adminutils, 'adminroleaccount';

# Handles the admin role account management interface.
class AdminRoleManager < PageHandler
	declare_handlers("roles") {
		area :Admin
		access_level :Admin, CoreModule, :editadmins

		page :GetRequest, :Full, :list_roles
		page :GetRequest, :Full, :edit_role, input(Integer);
		page :GetRequest, :Full, :edit_role, "create";

		handle :PostRequest, :apply, "apply";
		handle :PostRequest, :delete_role, "delete", input(Integer)
	}

	#display a page that shows a role and lets you add/remove permissions from it
	def edit_role(roleid=nil)
		t = Template::instance("adminutils", "role_manager");
		role = nil
		if (roleid)
			role = AdminRoleAccount.find(roleid, :first);
			if (role)
				t.rolename = role.rolename
				t.roleid = role.id
				t.title = role.title
			end
		end

		existing_privileges = Hash.new
		if (role)
			Privilege::Storage::GlobalGrant.find(role.id).each {|grant|
				existing_privileges[grant.privilegeid] = true;
			}
		end

		privileges = Privilege::Storage::PrivilegeName.find(:scan);
		privileges = privileges.sort_by {|privilege| [privilege.moduleid, privilege.name]}
		modules = Hash.new
		module_struct = Struct.new("ModulePermission", :name, :permissions);
		privileges.each {|privilege|
			unless (modules[privilege.module_name])
				modules[privilege.module_name] = module_struct.new
				modules[privilege.module_name].permissions = []
				modules[privilege.module_name].name = privilege.module_name
			end
			if (existing_privileges[privilege.privilegeid])
				privilege.meta.checked = "checked"
			else
				privilege.meta.checked = false;
			end
			modules[privilege.module_name].permissions << privilege
		}
		modules = modules.values.sort_by {|value|
			value.name
		}

		t.modules = modules
		t.nex_site_modules = TypeIDItem.find(:conditions => "typename LIKE '%Module'").sort_by {|site_module| site_module.typename.upcase}

		puts t.display
	end

	#add/delete permissions for a role, also used to create roles if the role doesn't exist
	def apply()
		#We need a name or an ID to do anything
		if (!params["rolename", String, nil] && !params["roleid", Integer, nil])
			return
		end
		name = params["rolename", String, nil];
		title = params["title", String, nil];
		id = params["roleid", Integer, nil];
		new_privileges = params["permission", TypeSafeHash, TypeSafeHash.new(Hash.new)];
		existing_privileges_hash = Hash.new;
		role = nil;

		if (id)
			role = AdminRoleAccount.find(id, :first)
			return unless (role);
			existing_privileges = Privilege::Storage::GlobalGrant.find(id);
			existing_privileges.each { |privilege| #remove any privileges that shouldn't be there
				if (!new_privileges[privilege.privilegeid.to_s, String, nil])
					grant = Privilege::Storage::GlobalGrant.new();
					grant.accountid = id;
					grant.privilegeid = privilege.privilegeid;
					grant.delete();
				end
			}
		else
			existing_privileges = StorableResult.new
			role = AdminRoleAccount.create_role(name);
			id = role.id;
		end

		existing_privileges_hash = existing_privileges.to_hash
		#add the new privileges
		new_privileges.each{|priv_id|
			unless (existing_privileges_hash[[id, 0, priv_id.to_i]])
				grant = Privilege::Storage::GlobalGrant.new();
				grant.accountid = id;
				grant.privilegeid = priv_id;
				grant.store();
			end
		}

		role.rolename = name if (name && name != role.rolename)
		role.title = title if (title && title != role.title)
		role.store();
		
		site_redirect(url/:roles);
	end

	#roles overview page
	def list_roles()
		roles = AdminRoleAccount.find(:scan);
		roles.each {|role|
			privilege_names = role.privileges.map {|privilege|
				privilege.privilege_name
			}
			privilege_names = privilege_names.map {|privilege_name|
				privilege_name.name
			}
			privilege_names = privilege_names.sort

			role.privileges.meta.names = privilege_names.join(', ');
			role.privileges.meta.names
		}
		t = Template::instance("adminutils", "role_list");
		t.form_key = SecureForm.encrypt(request.session.user)
		t.roles = roles.sort_by {|role| role.rolename.upcase}
		puts t.display
	end
	
	def delete_role(id)
		role = AdminRoleAccount.find(id, :first)
		role.delete
		site_redirect(url/:roles)
	end
end
