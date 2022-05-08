lib_require	:Profile, "profile_display_block";
lib_require :Profile, "profile_block_query_mediator";
lib_require :Profile, "profile_user";
lib_require :Profile, "profile_block_visibility";

module Profile
	class ProfilePageHandler < PageHandler
		declare_handlers("") {
			area :User
			rewrite(:GetRequest) { url/:profile }
			page	:GetRequest, :Full, :view_profile, "profile";
			
			area :Self
			site_redirect(:GetRequest, "profile") { ['/', [:User, PageRequest.current.user]] }
			page	:GetRequest, :Full, :view_edit_profile, "profile", "edit";
			handle	:GetRequest, :refresh_block_view, "profile", "block", input(Integer), "refresh";
			
			handle  :GetRequest, :create_block_view, "profile", "edit", "block", "new";			
			handle  :GetRequest, :edit_block_view, "profile", "edit", "block", input(Integer), "view";
			handle	:PostRequest, :save_block, "profile", "edit", input(Integer), "save";
			handle	:PostRequest, :create_block, "profile", "edit", "create";
			handle	:PostRequest, :save_block_position, "profile", "edit", input(Integer), "position";
			handle	:PostRequest, :save_block_visibility, "profile", "edit", input(Integer), "visibility";
			handle	:PostRequest, :remove_block, "profile", "edit", "block", input(Integer), "remove";
			
		}
		
		def view_profile()
			if(!request.user.visible?(request.session.user))
				if(request.session.user.anonymous?())
					request_login_view();
					return;
				else
					blocked_view();
					return;
				end
			end
			
			if(request.user.userid == request.session.user.userid)
				owner_view = true;
			end
			
			columns = organize_profile_blocks(request.user, request.session.user);
			
			request.reply.headers["X-width"] = 0;
			
			t = Template.instance("profile", "profile_view");
			t.column_data = columns;
			t.owner_view = owner_view;
			
			print t.display();
			
		end
		
		def view_edit_profile()
			t = Template.instance("profile", "edit_profile_view");
			
			columns = organize_profile_blocks(request.session.user, request.session.user);
			
			javascript = "<script>\n";
			javascript = javascript + "PROFILE.init_display_blocks = function(){\n";
			javascript = javascript + "var temp;\n";
			for key in columns.keys
				i=0;
				for block in columns[key]
					javascript = javascript + block.generate_javascript("block#{key}_#{block.blockid}");
					i = i + 1;
				end
			end
			javascript = javascript + "}\n";
			javascript = javascript + "PROFILE.init_block_query_info_list = function(){\n";
			javascript = javascript + "var temp;\n";
			block_query_info_list = ProfileBlockQueryMediator.instance.list_blocks();
			block_list = Array.new();
			
			for block_info_path in block_query_info_list
				block_info = ProfileBlockQueryMediator.instance.query_block(block_info_path[0], block_info_path[1]);
				block_list << block_info;
				javascript = javascript + block_info.generate_javascript();
			end
			javascript = javascript + "}\n";
			javascript = javascript + "PROFILE.init_visibility = function(){\n";
			javascript = javascript + ProfileBlockVisibility.javascript();
			javascript = javascript + "}\n";
			javascript = javascript + "</script>";
			
			request.reply.headers["X-width"] = 0;
			t.column_data = columns;
			t.my_javascript = javascript;
			t.block_info_list = block_list;
			t.profile_form_key = SecureForm.encrypt(request.session.user, Time.now, "/Self/profile/edit");
			t.profile_block_form_key = SecureForm.encrypt(request.session.user, Time.now, "/Self/profile_blocks");
				
			print t.display();
		end
		
		
		def edit_block_view(block_id)
			request.reply.headers['Content-Type'] = PageRequest::MimeType::PlainText;
			
			t = Template.instance("profile", "edit_block_view");
			
			block = ProfileDisplayBlock.find(:first, [session.user.userid, block_id]);
			t.block = block;
			
			print t.display();
		end
		
		
		def create_block_view()
			request.reply.headers['Content-Type'] = PageRequest::MimeType::PlainText;

			module_name = params["module_name", String];
			path = params["path", String];			
			
			t = Template.instance("profile", "create_block_view");
			
			t.module_name = module_name;
			t.path = path;
			
			print t.display();
		end
		
		
		def organize_profile_blocks(profile_user, request_user)
			display_block_list = ProfileDisplayBlock.find(:all, profile_user.userid);
			
			if(display_block_list.empty?())
				#display error
			end
			
			columns = Hash.new();
			
			for display_block in display_block_list
				if(columns[display_block.columnid.to_s()].nil?())
					columns[display_block.columnid.to_s()] = Array.new();
				end
				
				if(display_block.visible?(profile_user, request_user))
					columns[display_block.columnid.to_s()] << display_block;
				end
			end
			
			#sort the columns
			for key in columns.keys
				columns[key] = columns[key].sort_by{|display_block| display_block.position}
			end
			
			#get the rendered content
			for key in columns.keys
				column = columns[key];
				for display_block in column
					out = StringIO.new();
					subrequest(out, :PostRequest, display_block.block_uri(), request.params.to_hash(), [:User, request.user]);
					display_block.rendered_content = out;
				end
            end
			
			return columns;
		end
		
		def refresh_block_view(block_id)
			requested_block = ProfileDisplayBlock.find(:first, [request.session.user.userid, block_id]);
			
			request.reply.headers['Content-Type'] = PageRequest::MimeType::PlainText;
			
			t = Template.instance("profile", "block_refresh_view");
			t.display_block = requested_block;
			
			puts t.display();
		end
		
		def save_block_position(block_id)
			column_id = params["column", Integer];
			position = params["position", Integer];
			
			column_change = false;
			user_id = request.session.user.userid;
			
			moved_block = ProfileDisplayBlock.find(:first, [user_id, block_id]);
			columns = organize_profile_blocks(request.session.user, request.session.user);
			
			original_column = moved_block.columnid;
			moved_block.position = position;
			
			if(moved_block.columnid != column_id)
				moved_block.columnid = column_id;
				column_change = true;
			end
			moved_block.store();
			
			i = 0;
			for display_block in columns[column_id.to_s()]
				if(i == position)
					move_success = true;
					i = i + 1;
				end
				if(display_block.blockid == moved_block.blockid)
					next;
				end
				if(i != display_block.position)
					display_block.position = i;
					display_block.store();
				end
				
				i = i + 1;
			end
			
			if(column_change)
				i=0;
				for display_block in columns[original_column.to_s()]
					if(display_block.blockid == moved_block.blockid)
						next;
					end
					if(i != display_block.position)
						display_block.position = i;
						display_block.store();
					end
					
					i = i + 1;
				end
			end
		end
		
		def save_block_visibility(block_id)
			visibility = params["visibility", Integer];
			
			process_block_visibility(block_id, visibility);
		end
		
		def process_block_visibility(block_id, visibility_setting)
			block = ProfileDisplayBlock.find(:first, [request.session.user.userid, block_id]);
			
			block.visibility = visibility_setting;
			
			block.store();
		end
		
		def save_block(block_id)
			visibility = params["visibility", Integer];
			module_name = params["module_name", String];
			path = params["path", String];
			
			process_block_visibility(block_id, visibility);
			
			rewrite(:Post, "/profile_blocks/#{module_name}/#{path}/#{block_id}/save", params, [:Self, session.user]);
		end
		
		def create_block()
			request.reply.headers["Content-Type"] = PageRequest::MimeType::PlainText;
			
			module_name = params["module_name", String];
			path = params["path", String];
			
			block_info = ProfileBlockQueryMediator.instance.query_block(module_name, path);
			
			visibility = params["visibility", Integer, block_info.default_visibility];
			
			block_id = ProfileDisplayBlock.get_seq_id(request.session.user.userid);
			
			block = ProfileDisplayBlock.new();
			block.blockid = block_id;
			block.userid = request.session.user.userid;
			block.columnid = block_info.initial_column;
			block.moduleid = block_info.module_id;
			block.path = path;
			block.visibility = visibility;
			block.position = block_info.initial_position;
			
			block.store();
			#Find the max position from the DB for the user
			#set block's position to max+1
			#store block.
			
			print block_id;

			#send a create site rewrite to the appropriate handler
			rewrite(:Post, "/profile_blocks/#{module_name}/#{path}/#{block_id}/create", params, [:Self, session.user]);
		end
		
		def remove_block(block_id)
			block = ProfileDisplayBlock.find(:first, [request.session.user.userid, block_id]);
			remove_path = block.remove_uri();
			
			block.delete();
			
			rewrite(:Post, remove_path, params, [:Self, session.user]);
		end

		def blocked_view()
			t = Template.instance("profile", "profile_view_blocked");
			t.user = request.session.user;
			
			print t.display();
		end
		
		def request_login_view()
			t = Template.instance("profile", "profile_view_request_login");
			t.redirect = "/users/#{request.user.username}/profile/";
			t.profile_user = request.user;
			
			print t.display();
		end
	end
end
