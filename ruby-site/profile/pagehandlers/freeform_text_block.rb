lib_want	:Profile, "profile_block_query_info_module", "profile_block";

module Profile
	class FreeformTextBlock < PageHandler
		declare_handlers("profile_blocks/Profile/freeform/") {
			area :User
			access_level :Any
			page	:GetRequest, :Full, :freeform_block, input(Integer);
			
			area :Self
			page	:GetRequest, :Full, :freeform_block_edit, input(Integer), "edit";
			page	:GetRequest, :Full, :freeform_block_edit, "new";
			
			handle	:PostRequest, :freeform_block_save, input(Integer), "save";
			handle	:PostRequest, :freeform_block_save, input(Integer), "create";
			handle	:PostRequest, :freeform_block_remove, input(Integer), "remove";
		}
		
		
		def freeform_block(block_id)
			t = Template::instance('profile', 'freeform_text_block_view');
			
			profile_block = ProfileBlock.find(:first, request.user.userid, block_id);
			
			t.title = profile_block.blocktitle;
			t.content = profile_block.blockcontent;
			
			print t.display();
		end
		
		
		def self.freeform_block_query(info)
			if(site_module_loaded?(:Profile))
				info.extend(ProfileBlockQueryInfo);
				info.title = "Freeform Text";
				info.initial_position = 50;
				info.initial_column = 1;
				info.form_factor = :both;
				info.max_number = 5;
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
			
			print t.display();
		end
		
		def freeform_block_save(block_id)
			request.reply.headers['Content-Type'] = PageRequest::MimeType::PlainText;
			
			profile_block = ProfileBlock.find(:first, request.session.user.userid, block_id);
			if (profile_block.nil?)
				profile_block = ProfileBlock.new;
				profile_block.blockid = block_id;
				profile_block.userid = request.session.user.userid;
			end
			
			profile_block.blocktitle = params["title", String, ""];
			profile_block.blockcontent = params["content", String, ""];
			profile_block.store();
		end
		
		def freeform_block_remove(block_id)
			profile_block = ProfileBlock.find(:first, request.session.user.userid, block_id);
			
			profile_block.delete();
		end
	end
end
