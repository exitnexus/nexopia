lib_require :groups, 'group', 'groupmember', 'validation_helper'

module Groups
	class GroupsHandler < PageHandler
		
		include GroupsValidationHelper;
		
		declare_handlers("groups") {
			area :Self
			access_level :IsUser, GroupsModule, :editgroups
			
			# Default groups handler
			page :GetRequest, :Full, :edit_groups
			
			page :GetRequest, :Full, :create_group, "create"
			page :GetRequest, :Full, :edit_groups, "edit"
			page :GetRequest, :Full, :edit_groups, "edit", input(Integer)
			page :PostRequest, :Full, :update_groups, "update"
			handle :GetRequest, :remove_group_membership, "remove", input(Integer)

			handle :GetRequest, :query_groups, "query"
		
			area :Public
			handle :GetRequest, :type, "type"
			handle :GetRequest, :type, "type", input(Integer)
			handle :GetRequest, :visibility, "visibility"
			handle :GetRequest, :visibility, "visibility", input(Integer)
						
			area :Admin
			page :GetRequest, :Full, :remove_group, "remove_group", input(Integer), input(String)
		}
		
		
		def create_group()
			t = Template.instance("groups", "edit_groups");
			
			_validate_group_edit(t, params, true);
			
			# t.user_group_types = Groups::GroupMember.grouped_by_type(request.user.userid);
			# t.selected_group = Groups::GroupMember.new;
			
			puts t.display;
		end
		
		
		def edit_groups(group_id=nil)
			t = Template.instance("groups", "edit_groups");
			
			_validate_group_edit(t, params);
			
			if (!group_id.nil?)
				t.selected_group = Groups::GroupMember.find(:first, request.user.userid, group_id);
			end
			
			puts t.display;
		end
		
		
		def update_groups
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
			
			if (visibility.kind_of? TypeSafeHash)
				# If they're just updating privacy settings, we're using slightly different form fields,
				# and we don't have to worry about all the other fields.
				visibility.each { |key|
					group_member_visibility = visibility[key, Integer];
					
					group_id = key.to_i;
					group_member = Groups::GroupMember.find(:first, request.user.userid, group_id);
					
					if (group_member.visibility != group_member_visibility)
						group_member.visibility = group_member_visibility;
						group_member.store;
					end
				};
			end
			
			t = Template.instance("groups", "edit_groups");
			if (!_validate_group_edit(t, params, true))
				puts t.display;
				return;
			end
			
			if (!visibility.kind_of? TypeSafeHash)
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
			end
			
			site_redirect("/groups/edit", :Self);
		end


		def query_groups()
			group_name = params["name", String, ""];
			group_location = params["location", Integer, 0];
			group_type = params["type", Integer, 0];
			
			$log.info "Name: #{group_name} / Location: #{group_location} / Type: #{group_type}", :debug;
			
			conditions = "name LIKE ?";
			order_clause = "type = #{group_type} DESC, location = #{group_location} DESC, type, location";
			values = ["#{group_name}%"];
			
			if (group_location != 0)
				id_path = Locs.get_parent_ids(group_location);
				conditions = conditions + " AND location IN ?";
				values << id_path;
			end
			
			groups = Groups::Group.find(:all, :conditions => [conditions, *values], 
				:order => order_clause,
				:limit => 10);

			request.reply.headers['Content-Type'] = PageRequest::MimeType::XMLText;

			xml_string = "<?xml version = \"1.0\" encoding=\"UTF-8\" standalone=\"yes\" ?>" + "<groups>";
				
			groups.each { |group| 
				xml_string = xml_string +
					"<group>" +
						"<name>#{CGI::escapeHTML(group.name.to_s)}</name>" +
						"<location>#{CGI::escapeHTML(group.location_name)}</location>" +
						"<type>#{CGI::escapeHTML(group.type_name)}</type>" +
						"<location-id>#{group.location}</location-id>" +
						"<type-id>#{group.type}</type-id>" +
					"</group>";
			};

			xml_string = xml_string + "</groups>";

			puts xml_string;
		end
		

		def _validate_group_edit(template, params, creating_new=false)
			params = request.params;

			group_id = params["group_id", Integer, nil];

			template.user_group_types = Groups::GroupMember.grouped_by_type(request.user);
			if (!group_id.nil?)
				group = Groups::GroupMember.find(:first, request.user.userid, group_id);
			elsif (template.user_group_types.nil? || template.user_group_types.empty? || creating_new)
				group = Groups::GroupMember.new;
			end

			location = params['location', Integer, nil];
			name = params['name', String, nil];
			visibility = params["visibility", TypeSafeHash, nil] || params["visibility", Integer, nil];
			type = params['type', Integer, nil];
			from_month = params['from_month', Integer, nil];
			from_year = params['from_year', Integer, nil];
			to_month = params['to_month', Integer, nil];
			to_year = params['to_year', Integer, nil];
			
			present = params["present", String, nil] == "on";

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

			template.selected_group = group;

			return validation.valid?
		end
		
		
		def remove_group_membership(group_id)
			group_member = Groups::GroupMember.find(:first, request.user.userid, group_id);
			group_member.delete;

			if (request.impersonation?)
				$log.info(["groups", "Deleted user #{request.user.username} from group id #{group_id}"], :info, :admin);	
			end
		end


		def remove_group(group_id, username)
			group_members = Groups::GroupMember.find(:all, :conditions => ["groupid = ?", group_id]);
			
			group_members.each { |group_member|
				group_member.delete;
			};
			
			group = Groups::Group.find(:first, group_id);
			group.delete;
			
			$log.info(["groups", "Deleted group id #{group_id}"], :info, :admin);
			
			site_redirect("/self/#{username}/groups/edit", :Admin);
		end
		
		
		def type(value=nil)
			options = option_array(Group::TYPE_OPTIONS);

			t = Template.instance("nexoskel", "selector");
			
			t.ref = "type";
			t.options = options;
			t.value = value;

			t.onchange_handler = params['onchange_handler', String, nil];

			puts t.display;
		end
		
		
		def visibility(value=nil)
			options = option_array(GroupMember::VISIBILITY_OPTIONS);

			t = Template.instance("nexoskel", "selector");
			
			field = params["field", String, nil];
			id = params["id", String, nil];
			if (id.nil?)
				t.ref = field;
			else
				t.ref = "#{field}[#{id}]";
			end
			
			t.options = options;
			t.value = value;

			puts t.display;
		end
		

		Option = Struct.new :value, :text;
		
		
		def option_array(list)
			options = Array.new;

			list.each { |pair| 
				options << Option.new(pair[1], pair[0]);
			};

			return options;
		end
		private :option_array;
	end
end