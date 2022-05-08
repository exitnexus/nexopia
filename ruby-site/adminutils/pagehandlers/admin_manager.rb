lib_require :Core, 'pagehandler'
lib_require :Adminutils, 'adminroleaccount';



# Handles the admin role account management interface.
class AdminManager < PageHandler
	declare_handlers("") {
		area :Admin
		access_level :Admin, CoreModule, :editadmins
		page :GetRequest, :Full, :list_admins
		page :GetRequest, :Full, :edit_admin, "admins", input(Integer)
		
		handle :PostRequest, :update_admin, "admins", "update"
		handle :PostRequest, :create_admin, "admins", "create"
	}
	
	def list_admins
		roles = AdminRoleAccount.find(:scan)
		
		#lets grab all the users without actually looking at them so the queries can be aggregated
		users = Hash.new;
		roles.each {|role|
			role.admins_map.each { |admin_map|
				if (!users.key?(admin_map.accountid))
					users[admin_map.accountid] = [];
				end
				users[admin_map.accountid].push(role)
			}
		}
		users.each {|uid, roles|
			roles.meta.uid = uid;
			roles.meta.roles_list = roles;
			roles.meta.user_name = UserName.find(uid, :first, :promise)
		}
		#remove any accounts that aren't users
		users.delete_if {|uid, roles|
			roles.meta.user_name.nil?
		}

		#calculate permissions for each user
		users.each {|uid, roles|
			permissions = Set.new
			roles.each {|role|
				role.privileges.each{|privilege| permissions.add(privilege.privilege_name.name)}
			}
			roles.meta.permissions = [*permissions].sort.join(', ')
		}
		
		users = users.values.sort_by {|roles|
			roles.meta.user_name = roles.meta.user_name.username
			roles.meta.user_name.upcase
		}
		
		t = Template::instance('adminutils', 'admin_list')
		t.users = users;
		puts t.display();
	end
	
	def edit_admin(id)
		@dump = StringIO.new
		user = User.find(id, :first);
		roles = AdminRoleAccount.find(:scan);
		roles_hash = roles.to_hash
		account_maps = AccountMap.find(id, :accountid);
		account_maps.each {|account|
			if (roles_hash[[account.primaryid]])
				roles_hash[[account.primaryid]].meta.checked = true;
				account.html_dump(@dump);
				if (account.visible)
					roles_hash[[account.primaryid]].meta.visible = true;
				end
			end
		}
		
		t = Template::instance('adminutils', 'edit_admin');
		t.dump = @dump.string;
		t.user = user;
		t.roles = roles;
		puts t.display
	end
	
	def update_admin
		return site_redirect(url/:admins) unless id = params['id', Integer, nil]
		roles = params['role', TypeSafeHash, TypeSafeHash.new(Hash.new)]
		titles = params['title', TypeSafeHash, TypeSafeHash.new(Hash.new)]
		user = User.find(id, :first);
		account_maps = AccountMap.find(id, :accountid);
		existing_roles = Set.new();
		#first delete any permissions we no longer should have
		account_maps.each {|account_map|
			unless (roles[account_map.primaryid.to_s, String] == "on")
				account_map.delete();
			else #update visible on permissions that already exist
				existing_roles.add(account_map.primaryid)
				if (account_map.visible != (titles[account_map.primaryid.to_s, String] == "on"))
					account_map.visible = titles[account_map.primaryid.to_s, String] == "on"
					account_map.store();
				end
			end
		} unless account_maps.nil?
		
		roles.each_pair(String, nil) {|role_id, set|
			if (set == "on" && !existing_roles.member?(role_id.to_i))
				new_map = AccountMap.new();
				new_map.primaryid = role_id;
				new_map.accountid = id;
				new_map.visible = (titles[role_id.to_s, String] == "on")
				new_map.store();
			end
		}
		site_redirect(url/:admins)
	end
	
	def create_admin
		name = params['name', String, nil]
		return site_redirect(url/:admins) unless user = User.get_by_name(name);
		site_redirect(url/:admins/user.userid)
	end
end
