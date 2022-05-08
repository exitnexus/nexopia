lib_want	:Profile, "profile_block_query_info_module";

class AdminBlock < PageHandler
	declare_handlers("profile_blocks/Profile/admin/") {
		area :User
		access_level :Any
		page	:GetRequest, :Full, :admin_block, input(Integer);
	}
	
	def admin_block(block_id)
		edit_mode = params["profile_edit_mode", Boolean, false];
		
		if(!Profile::ProfileDisplayBlock.verify_visibility(block_id, request.user, request.session.user, edit_mode))
			print "<h1>Not visible</h1>";
			return;
		end
		
		user_key = Authorization.instance.make_key(request.user.userid);
		
		t = Template.instance('profile', 'admin_block_view');
		
		if(request.user.frozen?)
			t.frozen = true;
		end
		
		general_group = Array.new();
		general_group << ["User Search", url / "adminuser.php" & {:type => "userid", :search => request.user.userid, :k => user_key}];
		
		if(request.session.has_priv?(CoreModule, "showip"))
			general_group << ["IP Search", url / "adminuserips.php" & {:uid => request.user.userid, :type => "userid", :k => user_key}];
		end
		
		if(request.session.has_priv?(CoreModule, "loginlog"))
			general_group << ["Logins", url / "adminloginlog.php" & {:col => "userid", :val => request.user.userid, :k => user_key}];
		end
		
		general_group << ["Abuse: #{request.user.abuses}", url / "adminabuselog.php" & {:uid => request.user.userid}];
		
		if(request.session.has_priv?(CoreModule, "listdeletedusers"))
			general_group << ["Deleted Users", url / "admindeletedusers.php" & {:type => "username", :uid => request.user.username}];
		end
		
		edit_group = Array.new();
		if(request.session.has_priv?(CoreModule, "editprofile"))
			edit_group << ["Edit Profile", url / :admin / :self / request.user.username / :profile / :edit];
		end
		
		if(request.session.has_priv?(CoreModule, "editpreferences"))
			edit_group << ["Edit Preferences", url / "prefs.php" & {:uid => request.user.userid}];
		end
		
		if(request.session.has_priv?(CoreModule, "editpictures"))
			edit_group << ["Edit Pictures", url / :admin / :self/ request.user.username / :pictures];
		end
		
		mod_group = Array.new();
		#INCOMPLETE. These are to only be displayed to those users who are admins, but with no specific
		# privilege associated with it. It could be assumed however that they must have 'listusers'.
		# See the php code in profile.php on lines 827 - 854.
		mod_group << ["Mod Pictures", url / "moderate.php" & {:mode => 1, :uid => request.user.userid}];
		mod_group << ["Mod Questionables", url / "moderate.php" & {:mode => 4, :uid => request.user.userid}];


		menu_groups = Array.new();
		menu_groups << general_group;
		menu_groups << edit_group;
		menu_groups << mod_group;
		
		t.menu_groups = menu_groups;
		
		print t.display();
	end
	
	def self.admin_block_query(info)
		if(site_module_loaded?(:Profile))
			info.extend(ProfileBlockQueryInfo);
			info.title = "Admin";
			info.default_visibility = :admin;
			info.initial_position = 10;
			info.initial_column = 0;
			info.form_factor = :narrow;
			info.multiple = false;
			info.removable = false;
			info.editable = false;
			info.moveable = false;
			info.max_number = 1;
			
			# only shows up for admins anyways, and should always be correct.
			info.content_cache_timeout = 0;
		end
		
		return info;
	end
end
