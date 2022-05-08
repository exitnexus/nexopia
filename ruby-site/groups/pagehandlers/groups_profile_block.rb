lib_require :Core, 'users/user_name', 'secure_form'
lib_require :Groups, 'group', 'groupmember', 'validation_helper'
lib_want	:Profile, "profile_block_query_info_module";

class GroupsProfileBlock < PageHandler
	include GroupsValidationHelper;
	
	declare_handlers("profile_blocks/Groups") {
		area :User
		access_level :Any
		handle :GetRequest, :groups, "list", input(Integer)
		
		area :Self
		access_level :IsUser, CoreModule, :editprofile
		handle 	:GetRequest, :edit, "list", input(Integer), "edit"
		
		handle :GetRequest, :refresh_list, "refresh_list"
		handle :GetRequest, :edit_group, "edit_group", input(Integer)
		handle :GetRequest, :create_group, "create_group"
		
		handle	:PostRequest, :visibility_save, "list", input(Integer), "visibility";
		
		handle :PostRequest, :update_group, "update";
		
		handle	:GetRequest, :edit, "list", "new";
		handle	:PostRequest, :groups_block_create, "list", input(Integer), "create";
		handle	:PostRequest, :groups_block_remove, "list", input(Integer), "remove";
		handle	:PostRequest, :groups_block_save, "list", input(Integer), "save";
	}
	
	def groups(block_id)
		edit_mode = params["profile_edit_mode", Boolean, false];
		
		if(!Profile::ProfileDisplayBlock.verify_visibility(block_id, request.user, request.session.user, edit_mode))
			print "<h1>Not visible</h1>";
			return;
		end
		
		t = Template::instance('groups', 'groups_profile_block');
		t.user_group_types = Groups::GroupMember.grouped_by_type(request.user, request.session.user.userid, 
			!request.session.anonymous?, request.session.has_priv?(CoreModule, "editprofile"));

		# if there are no groups to display, don't display the block
		if (t.user_group_types.empty?)
			return;
		end

		puts t.display
	end


	def edit(block_id=nil)
		request.reply.headers['Content-Type'] = PageRequest::MimeType::PlainText;

		if (!request.impersonation?)
			t = Template.instance("groups", "groups_profile_block_edit");	
			t.selected_group = Groups::GroupMember.new;
		else
			t = Template.instance("groups", "admin_list_groups");
		end
		
		t.user_group_types = Groups::GroupMember.grouped_by_type(request.user);
		
		puts t.display;
	end


	def _validate_group_edit(template, params, creating_new=false)
		params = request.params;

		group_id = params['group_id', Integer, nil];
		location = params['location', Integer, nil];
		name = params['name', String, nil];
		visibility = params["visibility", TypeSafeHash, nil] || params["visibility", Integer, nil];
		type = params['type', Integer, nil];
		from_month = params['from_month', Integer, nil];
		from_year = params['from_year', Integer, nil];
		to_month = params['to_month', Integer, nil];
		to_year = params['to_year', Integer, nil];
		
		present = params["present", String, nil];
		present = present.nil? ? nil : present == "on";

		validation = Validation::Set.new;
		validation.add("location", _validate_location(location));
		validation.add("name", _validate_name(name));
		validation.add("from", _validate_from(from_month, from_year));
		validation.add("to", _validate_to(to_month, to_year, present));

		validation.bind(template);

		template.location = location;
		template.name = name;
		template.from_month = from_month;
		template.from_year = from_year;
		template.to_month = to_month;
		template.to_year = to_year;
		template.visibility = visibility;
		template.type = type;
		template.group_id = group_id;
		template.present = present;
		template.user = request.user;

		return validation.valid?
	end
		
		
	def refresh_list()
		request.reply.headers['Content-Type'] = PageRequest::MimeType::PlainText;
		
		t = Template::instance('groups', 'list_groups');
		t.user_group_types = Groups::GroupMember.grouped_by_type(request.user);

		puts t.display
	end
		
		
	def edit_group(id)
		request.reply.headers['Content-Type'] = PageRequest::MimeType::PlainText;
		
		t = Template.instance("groups", "edit_group");
		t.selected_group = Groups::GroupMember.find(:first, request.session.userid, id);
		t.edit_form_key = SecureForm.encrypt(request.session.user, "/Self/groups/update");
	 	_validate_group_edit(t, params);
		puts t.display;
	end
	
	
	def create_group()
		request.reply.headers['Content-Type'] = PageRequest::MimeType::PlainText;
		
		t = Template.instance("groups", "edit_group");
		t.selected_group = Groups::GroupMember.new;
		t.edit_form_key = SecureForm.encrypt(request.session.user, "/Self/groups/update");
		_validate_group_edit(t, params);
		puts t.display;
	end
	
	
	def update_group
		request.reply.headers['Content-Type'] = PageRequest::MimeType::PlainText;
		
		name = params["name", String, nil];
		type = params["type", Integer, nil];
		location = params["location", Integer, nil];
		visibility = params["visibility", TypeSafeHash, nil] || params["visibility", Integer, nil];
		from_month = params["from_month", Integer, nil];
		from_year = params["from_year", Integer, nil];
		to_month = params["to_month", Integer, nil];
		to_year = params["to_year", Integer, nil];
		group_id = params["group_id", Integer, nil];
		present = params["present", String, nil] == "on";
		
		t = Template.instance("groups", "edit_group");
		t.selected_group = Groups::GroupMember.find(:first, request.session.userid, group_id) || Groups::GroupMember.new;
		t.edit_form_key = SecureForm.encrypt(request.session.user, "/Self/groups/update");
		if (!_validate_group_edit(t, params, true))
			t.present = present;
			puts t.display;
			return;
		end
		
		# Need to search via the name, type, and location entered because the user might select 
		# an existing group and then edit the name. If they choose a name of another existing 
		# group, we don't want to create that other group. Instead, we want to find it and add 
		# the user to it.
		begin
			group = Groups::Group.new;
			group.name = name;
			group.type = type;
			group.location = location;
			group.store;
		rescue SqlBase::QueryError => sql_error
			# We're not logging this because we assume that it means that the group already exists
			group = Groups::Group.by_name_type_location(name, type, location);
			
			# But if the group's still not found, it was some other haneous error
			if (group.nil?)
				raise sql_error;
			end
		end
		
		# If the user selected a group, and then changed the name, thus causing this post to create
		# a brand new group, we assume they wanted to be removed from the group they originally
		# selected, so we do this here.
		original_group = Groups::Group.find(:first, group_id);
		if (!original_group.nil? && original_group.id != group.id)
			original_group_member = Groups::GroupMember.find(:first, request.user.userid, original_group.id);
			if (!original_group_member.nil?)
				original_group_member.delete;
			end
		end
		
		# Add the person to the group. It will either be a new group or an existing group,
		# depending on what happens above.
		group_member = Groups::GroupMember.find(:first, request.user.userid, group.id) || Groups::GroupMember.new;
		group_member.userid = request.user.userid;
		group_member.groupid = group.id;
		group_member.visibility = visibility;
		group_member.fromyear = from_year;
		group_member.frommonth = from_month;

		if (present)
			group_member.toyear = -1;
			group_member.tomonth = -1;
		else
			group_member.toyear = to_year;
			group_member.tomonth = to_month;
		end
		
		group_member.store;
		return;
	end
	
			
	def self.groups_query(info)
		if(site_module_loaded?(:Profile))
			info.extend(ProfileBlockQueryInfo);
			info.title = "Connections";
			info.initial_position = 20;
			info.initial_column = 1;
			info.form_factor = :both;
			info.explicit_save = false;
			info.max_number = 1;
			info.javascript_init_function = ProfileBlockQueryInfo::JavascriptFunction.new("GroupsProfileBlock.init");
			info.admin_editable = true;
			info.add_visibility_exclude(:all);
			info.default_visibility = :logged_in;

			# changes on a per user basis because of complex access rights
			info.content_cache_timeout = 0 
		end
		
		return info;
	end
	
	
	def groups_block_create(block_id)
		$log.info("groups_block_create");
	end
	
	
	def groups_block_remove(block_id)
		$log.info("groups_block_remove");
	end
	
	
	def groups_block_save(block_id)
		log_details = "";
		groups_removed = Array.new;
		membership_removed = Array.new;
		
		admin_actions = params["admin_action", TypeSafeHash, nil];
		admin_actions.each { | id |
			action = admin_actions[id, String];
			if (action == "remove_group")
				members = Groups::GroupMember.find(:scan, :all, :conditions => ["groupid = ?", id]);
				group = Groups::Group.find(:first, id);

				groups_removed << "#{id} (#{group.name})";

				members.each { | member | member.delete; };
				group.delete;
			elsif (action == "remove_member")
				member = Groups::GroupMember.find(:first, request.user.userid, id);

				membership_removed << "#{id} (#{member.group_name})";

				member.delete;
			end
		};
		
		if (request.impersonation?)
			log_details += "Removed entire group: " + (groups_removed * ", ").to_s if !groups_removed.empty?;
			log_details += " / " if !membership_removed.empty? && !groups_removed.empty?;
			log_details += "Removed group membership: " + (membership_removed * ", ").to_s if !membership_removed.empty?;
		
			$log.info(["edit groups", log_details], :info, :admin);
		end
	end
	
	def visibility_save(block_id)
		return;
	end
end