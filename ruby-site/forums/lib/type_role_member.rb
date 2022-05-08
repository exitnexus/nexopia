lib_require :Core, "storable/storable"
lib_require :Forums, "forum"

module Forum
	class TypeRoleMember < Storable
		init_storable(:forumdb, 'typerolemembers');
		relation_singular(:role, "roleid", Privilege::Storage::TypeRole);
		relation_singular(:forum, "objectaccountid", Forum);

		def role_name
			return self.role.name;
		end
		
		def forum_name
			return self.forum.name;
		end
	end
end