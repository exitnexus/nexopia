lib_require :Forums, 'type_role_member'
class ForumRoleAdmin < PageHandler
	declare_handlers("forums/roles") {
		area :Admin
		page :GetRequest, :Full, :manage
		page :GetRequest, :Full, :show_user, "user", input(Integer)
		handle :PostRequest, :add, "add";
		handle :PostRequest, :delete, "delete";
	}
	
	def page_initialize
		@dump = StringIO.new;
	end

	def manage()
		t = Template::instance("forums", "role_admin")
		
		t.forums = Forum::Forum.find(:all);
		t.roles = Privilege::Storage::TypeRole.find(:all);
		puts t.display;
	end
	
	def add()
		username = params["username", String];
		user_id = UserName.by_name(username).userid;
		forum_id = params["forum", Integer];
		role_id = params["role", Integer];
		
		membership = Forum::TypeRoleMember.new();
		membership.objectaccountid = forum_id;
		membership.accountid = user_id;
		membership.roleid = role_id;
		membership.store();
		site_redirect(url/:forums/:roles)
	end
	
	def show_user(userid)
		t = Template::instance("forums", "user_role_list");
		t.memberships = Forum::TypeRoleMember.find(:conditions => ["accountid = ?", userid]);
		puts t.display();
	end
end