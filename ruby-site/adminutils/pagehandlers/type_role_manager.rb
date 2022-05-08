lib_require :Core, 'pagehandler', 'privilege'
require 'stringio'

# Handles the admin role account management interface.
class TypeRoleManager < PageHandler
	declare_handlers("typeroles") {
		area :Admin
		access_level :Admin, CoreModule, :adminedit
		page :GetRequest, :Full, :manage
		handle :PostRequest, :add, "add";
		handle :PostRequest, :delete, "delete";
	}
	
	def page_initialize
		@dump = StringIO.new;
	end

	def manage()
		t = Template::instance('adminutils', 'type_role_manager')
		t.roles = Privilege::Storage::TypeRole.find(:scan); # gotta catch'em all!
		t.modules = TypeIDItem.find(:conditions => "typename LIKE '%Module'")
		t.modules.each { |mod|
			mod.typename.chomp!("Module");
		}
		t.privileges = Privilege::Storage::PrivilegeName.find(:scan);
		puts t.display;
	end

	def add()
		role_name = params["role_name", String];
		account_type_name = params["account_type_name", String];
		
		if (role_name && account_type_name)
			role = Privilege::Storage::TypeRole.new();
			role.name = role_name;
			role.typeid = TypeID.get_typeid(account_type_name);
			role.store();
		end
		site_redirect(url/:admin/:typeroles);
	end
	
	def delete()
		roles = params["roles", TypeSafeHash];
		to_be_deleted = [];
		roles.each_pair(String) {|key, value|
			if (value == "on")
				delete_me = Privilege::Storage::TypeRole.new();
				delete_me.roleid = key.to_i;
				to_be_deleted << delete_me;
			end
		}
		to_be_deleted.each {|role|
			role.delete();
		}
		site_redirect(url/:admin/:typeroles);
	end
end
