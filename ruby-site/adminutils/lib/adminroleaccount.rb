lib_require :Core, "accounts", 'url', 'privilege';

# Defines the admin role account type. Even though any data about this account
# will be split into the usersdb, the actual role object remains in the moddb
# for easy lookup.
# Simplistic conversion from old system to new system in terms of the role object:
# 1) Change the size of the type field of newmaster.account
# 2) UPDATE newmods.adminroles SET id = id + (SELECT max(id) FROM newmaster.accounts);
# 3) INSERT INTO newmaster.accounts (id, type, serverid) SELECT id, #, ^&* FROM newmods.adminroles; replace # with the typeid of AdminRoleAccount, replace ^&* with the serverid for AdminRoleAccount
# 4) INSERT INTO newmaster.accountmap (primaryid, accountid) SELECT id, id FROM newmods.adminroles;
# 5) INSERT INTO newmaster.accountmap (primaryid, accountid) SELECT roleid + (max id used in first query), userid FROM newmods.admins;
# Conversion of actual permissions assigned to the roles will be done later.
class AdminRoleAccount < Storable
	init_storable(:rolesdb, 'adminroles');
	include AccountType;

	relation_multi(:privileges, "id", Privilege::Storage::GlobalGrant);
	relation_multi(:admins_map, "id", AccountMap, :primaryid);

	def AdminRoleAccount.admin_title_dump
		return @admin_dump if @admin_dump
		@admin_dump = $site.memcache.get("ruby-admintitles");
		if (@admin_dump.nil?)
			@admin_dump = Hash.new;
			
			roles = AdminRoleAccount.find(:scan, :all);
			roles.each { |role|
				role.admins_map
			}
			roles.each { |role|
				role.admins_map.each { |member| 
					@admin_dump[member.accountid] = Array.new if @admin_dump[member.accountid].nil?;
					@admin_dump[member.accountid] << role.title;
				};
			};
			
			$site.memcache.set("ruby-admintitles", @admin_dump, 60*60*24);
			
			return @admin_dump;
		end
		
		return @admin_dump;
	end


	def self.create_role(name)
		if (accountid = create_account())
			adminrole = AdminRoleAccount.new();
			adminrole.id = accountid;
			adminrole.rolename = name;
			adminrole.store();
			return adminrole;
		end
		return nil;
	end

	def before_delete
		self.privileges.each {|priv|
			priv.delete
		}
	end
	
	def uri_info(type = nil)
		return [rolename, url / :admin / :roles / id];
	end
end

class User < Cacheable
	def admin_tags
		tags = AdminRoleAccount.admin_title_dump[self.userid] || Array.new;
		
		return tags;
	end
	
	
	def admin?(mod=CoreModule, type="")
		if (type != "")
			return @privileges.has?(mod, type);
		end

		admin_roles = AdminRoleAccount.admin_title_dump[self.userid];

		return !admin_roles.nil? && !admin_roles.empty?;
	end
end