lib_want	:Profile, "profile_block_query_info_module", "profile_block", "profile_display_block", "profilemodule";

module Profile
	class FreeformTextBlock < PageHandler
		declare_handlers("profile_blocks/Profile/freeform/") {
			area :User
			access_level :Any
			page	:GetRequest, :Full, :freeform_block, input(Integer);
			
			area :Self
			access_level :IsUser
			page	:GetRequest, :Full, :freeform_block_new, "new";
			
			handle	:PostRequest, :freeform_block_save, input(Integer), "create";
			handle	:PostRequest, :freeform_block_remove, input(Integer), "remove";
			
			handle	:PostRequest, :visibility_save, input(Integer), "visibility";
			
			access_level :IsUser, CoreModule, :editprofile
			page	:GetRequest, :Full, :freeform_block_edit, input(Integer), "edit";
			
			handle	:PostRequest, :freeform_block_save, input(Integer), "save";
		}
		
		
		def freeform_block(block_id)
			edit_mode = params["profile_edit_mode", Boolean, false];
		
			if(!ProfileDisplayBlock.verify_visibility(block_id, request.user, request.session.user, edit_mode))
				print "<h1>Not visible</h1>";
				return;
			end
			
			#optimization so it will grab all of the user's profile blocks on the first request, then the list will
			#be cached for the requests to the freeform text block which will follow.
			profile_block_list = request.user.freeform_text_blocks;
			profile_block = profile_block_list.detect{|block| block.blockid == block_id }

			# Fix for NEX-788. If the actual profile_block is nil, we double check this by trying to find it from the
			# database, and if it's still not there, we delete the ProfileDisplayBlock reference so that the error doesn't
			# occur again.
			if(profile_block.nil? && ProfileBlock.find(:first, request.user.userid, block_id).nil?)
				display_block = ProfileDisplayBlock.find(:first, request.user.userid, block_id)
				# Make sure the display block is there and that it's one for a freeform block
				if(!display_block.nil? && display_block.moduleid == ProfileModule.typeid && display_block.path == "freeform")
					$log.info "Freeform text block (id: #{block_id}) for '#{request.user.username}' requested but wasn't found. Removing the profile display block reference.", :warning
					display_block.delete()
				end
				
				return
			end

			if(profile_block.blockcontent.length > 0)
				t = Template::instance('profile', 'freeform_text_block_view');
				t.title = profile_block.blocktitle;
				t.content = profile_block.blockcontent;
				t.edit_mode = edit_mode;
				
				print t.display();
			end
		end
		
		
		def self.freeform_block_query(info)
			if(site_module_loaded?(:Profile))
				info.extend(ProfileBlockQueryInfo);
				info.title = "Freeform";
				info.initial_position = 50;
				info.initial_column = 1;
				info.form_factor = :both;
				info.max_number = ProfileBlock;
				info.javascript_init_function = ProfileBlockQueryInfo::JavascriptFunction.new("initialize_enhanced_text_input_for_dialog");
				info.admin_editable = true;
				
				info.content_cache_timeout = 120
			end
			
			return info;
		end
		
		
		def freeform_block_edit(block_id = nil)
			request.reply.headers['Content-Type'] = PageRequest::MimeType::PlainText;
			
			t = Template.instance("profile", "freeform_text_block_edit");
			if(block_id.nil?())
				profile_block = ProfileBlock.new();
			else
				profile_block = ProfileBlock.find(:first, request.user.userid, block_id);
			end
			
			t.title = profile_block.blocktitle;
			t.content = profile_block.blockcontent;
			t.max_length = ProfileBlock.max_length(request.user);
			
			print t.display();
		end
		
		def freeform_block_new()
			request.reply.headers['Content-Type'] = PageRequest::MimeType::PlainText;
			
			profile_block_list = request.user.freeform_text_blocks;
			
			if(profile_block_list.length >= ProfileBlock.max_number(request.session.user))
				#error
				return;
			end
			
			self.freeform_block_edit();
		end
		
		def freeform_block_save(block_id)
			request.reply.headers['Content-Type'] = PageRequest::MimeType::PlainText;
			
			block_title = params["title", String, ""];
			block_content = params["content", String, ""];
			
			profile_block_list = request.user.freeform_text_blocks;
			for block in profile_block_list
				if(block.blockid == block_id)
					profile_block = block;
					break;
				end
			end
			
			if(profile_block_list.length >= ProfileBlock.max_number(request.session.user) && profile_block.nil?())
				#error
				return;
			elsif(block_content.length > ProfileBlock.max_length(request.user))
				#error
				return;
			elsif(profile_block.nil?())
				profile_block = ProfileBlock.new();
				profile_block.blockid = block_id;
				profile_block.userid = request.user.userid;
			end
			
			profile_block.blocktitle = block_title;
			profile_block.blockcontent = block_content;
			
			profile_block.store();
		end
		
		def freeform_block_remove(block_id)
			profile_block = ProfileBlock.find(:first, request.session.user.userid, block_id);
			if(!profile_block.nil?())
				profile_block.delete();
			end
		end
		
		def visibility_save(block_id)
			return;
		end
	end
end