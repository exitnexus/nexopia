lib_require	:Profile, "profile_display_block";
lib_require :Profile, "profile_block_query_mediator";
lib_require :Profile, "profile_user";
lib_require :Profile, "profile_block_visibility";
lib_require :Profile, "profile_error_messages";
lib_require :Profile, "user_skin";
lib_require :Profile, "profile_view";

lib_require :Core, "admin_log";

module Profile
	class ProfilePageHandler < PageHandler
		include ProfileErrorMessages
		
		declare_handlers("") {
			area :Public
			page	:GetRequest, :Full, :blocked_view, "profile", "blocked";
			page	:GetRequest, :Full, :frozen_view, "profile", "frozen";
			
			area :User
			rewrite(:GetRequest) { url/:profile }
			page	:GetRequest, :Full, :view_profile, "profile";
			page	:GetRequest, :Full, :request_login_view, "profile", "login";
			
			handle	:PostRequest, :update_views, "profile", "views", "update";
			
			area :Self
			handle  :PostRequest, :create_block_view, "profile", "edit", "block", "new";			
			
			handle	:PostRequest, :create_block, "profile", "edit", "create";
			handle	:PostRequest, :save_block_position, "profile", "edit", input(Integer), "position";
			
			access_level :IsUser, CoreModule, :editprofile
			site_redirect(:GetRequest, "profile") { ['/', [:User, PageRequest.current.session.user]] }
			page	:GetRequest, :Full, :view_edit_profile, "profile", "edit";
			
			handle	:PostRequest, :save_block_visibility, "profile", "edit", input(Integer), "visibility";
			handle	:PostRequest, :remove_block, "profile", "edit", "block", input(Integer), "remove";
			
			handle  :PostRequest, :edit_block_view, "profile", "edit", "block", input(Integer), "view";
			handle	:PostRequest, :refresh_block_view, "profile", "block", input(Integer), "refresh";
			
			handle	:PostRequest, :save_block, "profile", "edit", input(Integer), "save";
		}
		
		def view_profile()
			
			if(request.user.userid == request.session.user.userid)
				owner_view = true;
			end
			
			request_profile_view_info = ProfileView.view(request.session.user, request.user);
			
			columns = organize_profile_blocks(request.user, request.session.user, false);
			
			block_query_info_list = ProfileBlockQueryMediator.instance.list_blocks();
			for key in columns.keys()
				for display_block in columns[key]
					block_info = ProfileBlockQueryMediator.instance.query_block(display_block.module_name, display_block.path);
					if(!block_info.nil?())
						display_block.visible_wrapper = block_info.visible_wrapper;
					else
						display_block.visible_wrapper = true;
					end
				end
			end
			
			if(request.user.profileskin > 0 && request.user.plus?())
				user_skin = UserSkin.find(:first, [request.user.userid, request.user.profileskin]);
				if(!user_skin.nil?())
					request.reply.headers['X-user-skin'] = user_skin.header();
				end
			end
			
			request.reply.headers["X-width"] = 0;
			
			t = Template.instance("profile", "profile_view");
			t.user = request.user;
			t.column_data = columns;
			t.owner_view = owner_view;
			t.request_profile_view_info = request_profile_view_info;
			t.add_friend_form_key = SecureForm.encrypt(request.session.user, "/User/friends/add");
			t.remove_friend_form_key = SecureForm.encrypt(request.session.user, "/User/friends/remove");

			print t.display();			
		end
		
		def update_views()
			user_id = params["userid", Integer];
			anon = params["anon", Integer];
			view_time = params["time", Integer];
			key = params["key", String];
			
			if(user_id != request.user.userid)
				return;
			end
			
			cur_time = Time.now.to_i();
			if(view_time <= cur_time && view_time + 60 > cur_time && ProfileView.check_key(key, user_id, anon, view_time))
				ProfileView.increment_views(request.session.user, request.user, anon);
			end
		end
		
		def view_edit_profile()
			t = Template.instance("profile", "edit_profile_view");
			
			columns = organize_profile_blocks(request.user, request.user, true);
			
			block_query_info_list = ProfileBlockQueryMediator.instance.list_blocks();
			block_list = Array.new();
			
			for block_info in block_query_info_list
				PageHandler.top[:modules].push(SiteModuleBase.get(block_info[0].to_sym()));
			end
			
			javascript = "<script>\n";
			javascript = javascript + "PROFILE.init_display_blocks = function(){\n";
			javascript = javascript + "var temp;\n";
			for key in columns.keys
				i=0;
				for block in columns[key]
					block_info = ProfileBlockQueryMediator.instance.query_block(block.module_name, block.path);
					block.visible_wrapper = block_info.visible_wrapper;
					javascript = javascript + block.generate_javascript("block#{key}_#{block.blockid}");
					i = i + 1;
				end
			end
			javascript = javascript + "}\n";
			javascript = javascript + "PROFILE.init_block_query_info_list = function(){\n";
			javascript = javascript + "var temp;\n";
			
			
			for block_info_path in block_query_info_list
				block_info = ProfileBlockQueryMediator.instance.query_block(block_info_path[0], block_info_path[1]);
				block_list << block_info;
				javascript = javascript + block_info.generate_javascript();
			end
			
			javascript = javascript + "}\n";
			javascript = javascript + "PROFILE.init_visibility = function(){\n";
			javascript = javascript + ProfileBlockVisibility.javascript();
			javascript = javascript + "}\n";
			
			javascript = javascript + "PROFILE.init_admin_values = function(){\n";
			javascript = javascript + "YAHOO.profile.admin_user=#{request.impersonation?() ? true : false};\n";
			if(request.impersonation?())
				javascript = javascript + "YAHOO.profile.admin_uri_base=\"#{request.area_base_uri}\";\n";
			end
			javascript = javascript + "}\n";
			javascript = javascript + "</script>";
			
			user_skin = UserSkin.find(:first, [request.user.userid, request.user.profileskin]);
			if(!user_skin.nil?()  && request.user.plus?())
				request.reply.headers['X-user-skin'] = user_skin.header();
			end
			
			if(!request.session.user.user_task_list.empty?())
				request.session.user.user_task_list.each{|task|
					if(task.taskid == 2)
						task.delete();
					end
				};
			end
			
			if(request.impersonation?())
				AdminLog.log(request, "view profile edit", "Profile edit #{request.user.userid} viewed.");
			end
			
			request.reply.headers["X-width"] = 0;
			t.user = request.user;
			t.column_data = columns;
			t.my_javascript = javascript;
			t.block_info_list = block_list.sort{|x,y| x.title <=> y.title};
			t.header_done_link = url/:users/request.user.username/:profile;
			
			t.profile_form_key = SecureForm.encrypt(request.session.user, "/Self/profile");
			t.profile_block_form_key = SecureForm.encrypt(request.session.user, "/Self/profile_blocks");
			
			print t.display();
		end
		
		
		def edit_block_view(block_id)
			request.reply.headers['Content-Type'] = PageRequest::MimeType::PlainText;
			
			block = ProfileDisplayBlock.find(:first, [request.user.userid, block_id]);
			
			if(block.nil?())
				self.error_view(BLOCK_NOT_EXIST);
				return;
			elsif(block.block_info.nil?())
				self.error_view(BLOCK_INFO_NOT_EXIST);
				return;
			elsif(!block.block_info.editable && !block.block_info.in_place_editable || block.block_info.immutable_after_create)
				self.error_view(BLOCK_NOT_EDITABLE);
				return;
			end
			
			t = Template.instance("profile", "edit_block_view");
			t.block = block;
			if (request.impersonation?)
				AdminLog.log(request, "view block contents", "Block #{request.user.userid}-#{block_id} contents viewed. Block info: #{block.module_name}/#{block.path}");
				
				# Check if the same moderator is simply continuing a log that was started within the last 5 minutes
				last_abuse_log = AbuseLog.find(:first, 
					:conditions => ["userid = ? AND modid = ?", request.user.userid, request.session.user.userid], 
					:order => "time DESC");
				if (!last_abuse_log.nil? && (Time.now.to_i - last_abuse_log.time < 5*60))
					t.abuse_log_id = last_abuse_log.id;
					t.abuse_log_entry = last_abuse_log.msg;
					t.abuse_log_subject = last_abuse_log.subject;
					t.abuse_log_reason = last_abuse_log.reason;
				end
			end
			
			print t.display();
		end
		
		
		def create_block_view()
			request.reply.headers['Content-Type'] = PageRequest::MimeType::PlainText;

			module_name = params["module_name", String];
			path = params["path", String];			
			
			block_info = ProfileBlockQueryMediator.instance.query_block(module_name, path);
			
			block_list = ProfileDisplayBlock.find(:conditions => ["userid=# AND moduleid=? AND path=?", request.session.user.userid, block_info.module_id, path]);
			
			if(block_info.nil?())
				self.error_view(BLOCK_INFO_NOT_EXIST);
				return;
			elsif(block_list.length == 1 && !block_info.multiple)
				self.error_view(BLOCK_SINGLETON);
				return;
			elsif(block_info.multiple && block_list.length >= block_info.max_number)
				self.error_view(BLOCK_MAX_NUMBER);
				return;
			end
			
			t = Template.instance("profile", "create_block_view");
			
			t.module_name = module_name;
			t.path = path;
			
			print t.display();
		end
		
		
		def organize_profile_blocks(profile_user, request_user, edit_mode=true)
			display_block_list = ProfileDisplayBlock.find(:all, profile_user.userid);
			
			if(display_block_list.empty?())
				return Hash.new();
			end
			
			columns = Hash.new();
			
			for display_block in display_block_list
				
				#check if the display block's module is loaded. If not, skip the block.
				block_module = TypeID.get_class(display_block.moduleid);
				if(block_module.nil?())
					next;
				end
				
				if(columns[display_block.columnid.to_s()].nil?())
					columns[display_block.columnid.to_s()] = Array.new();
				end
				
				if(display_block.visible?(profile_user, request_user, edit_mode))
					columns[display_block.columnid.to_s()] << display_block;
				end
			end
			
			#sort the columns
			for key in columns.keys
				columns[key] = columns[key].sort_by{|display_block| display_block.position}
			end
			
			new_params = {"profile_edit_mode" => edit_mode};
			
			empty_block_list = Array.new();
			
			#get the rendered content
			for key in columns.keys
				column = columns[key];
				for display_block in column
					out = StringIO.new();
					new_params["column_type"] = display_block.current_column();
					temp_req = subrequest(out, :PostRequest, display_block.block_uri(edit_mode), new_params, display_block.block_area(edit_mode, request.user));
					
					if(temp_req.reply.headers["Status"] != "200 OK")
						display_block.content_error = true;
						temp_sio = StringIO.new();	
						temp_sio << "The block encountered an error";
						temp_sio << "<div style=\"display:none;\">#{display_block.block_uri()}</div>";
						$log.info "organize_profile_blocks: The block for userid #{request.user.userid} encountered an error: #{display_block.block_uri()}", :error;
						display_block.rendered_content = temp_sio;
					else
						if(out.length > 0)
							display_block.content_error = false;
							display_block.rendered_content = out;
						else
							if(edit_mode)
								display_block.content_error = false;
								display_block.rendered_content = StringIO.new(build_placeholder_block(display_block));
							else
								empty_block_list << [key, display_block];
							end
						end
					end
				end
            end
			
			for empty_block in empty_block_list
				columns[empty_block[0]].delete(empty_block[1]);
			end
			
			return columns;
		end
		
		def refresh_block_view(block_id)
			requested_block = ProfileDisplayBlock.find(:first, [request.user.userid, block_id]);
			
			if(requested_block.nil?())
				self.error_view(BLOCK_NOT_EXIST);
				return;
			end
			
			request.reply.headers['Content-Type'] = PageRequest::MimeType::PlainText;
			
			new_params = {"profile_edit_mode" => true };
			out = StringIO.new();
			temp_req = subrequest(out, :PostRequest, requested_block.block_uri(true), new_params, requested_block.block_area(true, request.user));
			
			if(temp_req.reply.headers["Status"] != "200 OK")
				requested_block.content_error = true;
				temp_sio = StringIO.new();	
				temp_sio << "The block encountered an error";
				temp_sio << "<div style=\"display:none;\">#{requested_block.block_uri()}</div>";
				$log.info "refresh_block_view: The block for userid #{request.user.userid} encountered an error: #{requested_block.block_uri()}", :error;
				requested_block.rendered_content = temp_sio;
			else
				if(out.length > 0)
					requested_block.content_error = false;
					requested_block.rendered_content = out;
				else
					requested_block.content_error = false;
					requested_block.rendered_content = StringIO.new(build_placeholder_block(requested_block));
				end
			end
			
			t = Template.instance("profile", "block_refresh_view");
			t.display_block = requested_block;
			
			print t.display();
		end
		
		def save_block_position(block_id)
			column_id = params["column", Integer];
			position = params["position", Integer];
			
			moved_block = ProfileDisplayBlock.find(:first, [request.session.user.userid, block_id]);
			columns = organize_profile_blocks(request.session.user, request.session.user);
			
			request.user.profile.update!();
			
			if(moved_block.nil?())
				error_view(BLOCK_NOT_EXIST);
				return;
			elsif(!moved_block.block_info.moveable)
				self.error_view(BLOCK_NOT_MOVABLE);
				return;
			elsif(!moved_block.valid_column(column_id))
				self.error_view(BLOCK_COLUMN_INVALID);
				return;
			end
			
			for display_block in columns[column_id.to_s()]
				if(display_block.position == position)
					if(!display_block.moveable)
						position = position + 1;
					else
						break;
					end
				end
			end
			
			column_change = false;
			user_id = request.session.user.userid;

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
			if(block.nil?())
				self.error_view(BLOCK_NOT_EXIST);
				return;
			elsif(!block.block_info.editable)
				self.error_view(BLOCK_NOT_EDITABLE);
				return;
			elsif(!block.valid_visibility?(visibility_setting))
				self.error_view(BLOCK_NOT_VALID_VISIBILITY);
				return;
			end
			block.visibility = visibility_setting;
			
			block.store();
			out = StringIO.new();
			subrequest(out, :PostRequest, block.visibility_save_uri(), params.to_hash(), [:Self, request.user]);
		end
		
		def save_block(block_id)
			visibility = params["visibility", Integer];
			module_name = params["module_name", String];
			path = params["path", String];
			
			if(!request.impersonation?())
				process_block_visibility(block_id, visibility);
				request.user.profile.update!();
			else
				AdminLog.log(request, "edit block", "Block #{request.user.userid}-#{block_id} edited. Block info: #{module_name}/#{path}");
				
				abuse_log_entry = params["abuse_log_entry", String];
				abuse_log_subject = params["abuse_log_subject", String];
				abuse_log_reason = params["abuse_log_reason", String];
				abuse_log_id = params["abuse_log_id", Integer, nil];
				
				AbuseLog.make_entry(request.session.user.userid, request.user.userid, 
					AbuseLog::ABUSE_ACTION_PROFILE_EDIT, abuse_log_reason, abuse_log_subject, abuse_log_entry, abuse_log_id);
			end
			
			rewrite(:Post, "/profile_blocks/#{module_name}/#{path}/#{block_id}/save", params, [:Self, request.user]);
		end
		
		def create_block()
			request.reply.headers["Content-Type"] = PageRequest::MimeType::PlainText;
			
			module_name = params["module_name", String];
			path = params["path", String];
			
			block_info = ProfileBlockQueryMediator.instance.query_block(module_name, path);
			
			visibility = params["visibility", Integer, block_info.default_visibility];
			
			if(block_info.nil?())
				self.error(BLOCK_TYPE_NOT_EXIST);
				return;
			elsif(!block_info.valid_visibility?(visibility))
				self.error_view(BLOCK_NOT_VALID_VISIBILITY);
				return;
			end
			
			display_block_list = ProfileDisplayBlock.find(:all, request.session.user.userid);
			max_position = 0;
			display_block_list.each{|display_block|
				if(display_block.position >= max_position && display_block.columnid == block_info.initial_column)
					max_position = display_block.position + 1;
				end
			};
			
			block_id = ProfileDisplayBlock.get_seq_id(request.session.user.userid);
			
			block = ProfileDisplayBlock.new();
			block.blockid = block_id;
			block.userid = request.session.user.userid;
			block.columnid = block_info.initial_column;
			block.moduleid = block_info.module_id;
			block.path = path;
			block.visibility = visibility;
			block.position = max_position;
			
			block.store();
			
			request.user.profile.update!();
			
			#We need to print out the block id to get the id back to the javascript.
			print block_id;

			#send a create site rewrite to the appropriate handler
			rewrite(:Post, "/profile_blocks/#{module_name}/#{path}/#{block_id}/create", params, [:Self, session.user]);
		end
		
		def remove_block(block_id)
			block = ProfileDisplayBlock.find(:first, [request.user.userid, block_id]);
			
			if(block.nil?())
				$log.info("No block")
				self.error_view(BLOCK_NOT_EXIST);
				return;
			elsif(!block.block_info.removable)
				$log.info("Block not removable")
				self.error_view(BLOCK_NOT_REMOVABLE);
				return;
			end
			
			remove_path = block.remove_uri();
			
			block.delete();
			
			request.user.profile.update!();
			
			if(request.impersonation?())
				AdminLog.log(request, "remove block", "Block #{request.user.userid}-#{block_id} removed. Block info: #{block.module_name}/#{block.path}");
			end
			
			rewrite(:Post, remove_path, params, [:Self, request.user]);
		end

		def blocked_view()
			if(request.session.user.anonymous?())
				site_redirect(url / :profile / :login );
			end
			
			request.reply.headers["X-width"] = 0;
			
			t = Template.instance("profile", "profile_view_blocked");
			t.user = request.session.user;
			
			print t.display();
		end
		
		def frozen_view()
			if(request.session.user.anonymous?())
				site_redirect(url / "profile.php");
			end
			
			request.reply.headers["X-width"] = 0;
			
			t = Template.instance("profile", "profile_view_frozen");
			t.profile_user = request.user;
			
			print t.display();
		end
		
		def request_login_view()
			if(request.session.user.logged_in?())
				site_redirect(url / request.user.username);
			end
			request.reply.headers["X-width"] = 0;
			redirect_url = params["redirect_url", String];
			
			if(redirect_url.empty?())
				redirect_url = url / :users / request.user.username;
			end
			
			t = Template.instance("profile", "profile_view_request_login");
			t.redirect = redirect_url;
			t.profile_user = request.user;
			
			print t.display();
		end
		
		def error_view(error_msg)
			t = Template.instance("profile", "profile_error");	
			t.message = error_msg;
			
			print t.display();
		end
		
		def view_illegal_save_test()
			block_list = ProfileDisplayBlock.find(request.session.user.userid);
			
			t = Template.instance("profile", "test_save_view");
			
			t.block_list = block_list;
			temp = params["result", String, nil];
			if(!temp.nil?())
				t.save_results = temp;
			end
			
			print t.display();
		end
		
		def save_test()
			block_id = params["block_id", Integer];
			position = params["position", Integer];
			visibility = params["visibility", Integer];
			column = params["column", Integer];
			
			edit_submit = params["edit_submit", String, nil];
			remove_submit = params["remove_submit", String, nil];
			
			if(!edit_submit.nil?())
				if(!position.nil?() && !column.nil?())
					path = "/profile/edit/#{block_id}/position";
				else
					path = "/profile/edit/#{block_id}/save";
				end
			elsif(!remove_submit.nil?())
				path = "/profile/edit/block/#{block_id}/remove";
			end
			
			out = StringIO.new();
			temp_req = subrequest(out, :PostRequest, path, nil, [:Self, request.user]);
			
			site_redirect("/profile/edit/illegal?result=#{CGI::escape(out.string)}");
		end
		
		def build_placeholder_block(display_block)
			t = Template.instance("profile", "placeholder_block_view");
			
			t.display_block = display_block;
			t.title = display_block.block_info.title;
			
			return t.display();
		end
	end
end
