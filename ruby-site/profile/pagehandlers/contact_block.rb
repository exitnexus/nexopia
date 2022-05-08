lib_want	:Profile, "profile_block_query_info_module";

class ContactBlock < PageHandler
	declare_handlers("profile_blocks/Profile/contact/") {
		area :User
		access_level :Any
		page	:GetRequest, :Full, :contact_block, input(Integer);
		
		area :Self
		page 	:GetRequest, :Full, :contact_block_edit, input(Integer), "edit";
		page	:GetRequest, :Full, :contact_block_edit, "new";
		
		handle	:PostRequest, :contact_block_save, input(Integer), "save";
		handle	:PostRequest, :contact_block_save, input(Integer), "create";
		
		handle	:PostRequest, :visibility_save, input(Integer), "visibility";
		
		handle	:PostRequest, :remove_contact_block, input(Integer), "remove";
	}
	
	Contact = Struct.new(:name, :value, :param)
	
	def contact_block(block_id)
		edit_mode = params["profile_edit_mode", Boolean, false];
		
		if(!Profile::ProfileDisplayBlock.verify_visibility(block_id, request.user, request.session.user, edit_mode))
			print "<h1>Not visible</h1>";
			return;
		end
		
		t = Template::instance('profile', 'contact_block_view')
		t.user = request.user
		
		contacts = []
		contacts << Contact.new("MSN:", request.user.profile.msn) unless (request.user.profile.msn.empty?)
		contacts << Contact.new("AIM:", request.user.profile.aim) unless (request.user.profile.aim.empty?)
		contacts << Contact.new("Yahoo:", request.user.profile.yahoo) unless (request.user.profile.yahoo.empty?)
		contacts << Contact.new("ICQ:", request.user.profile.icq) unless (request.user.profile.icq.zero?)
		
		if(!contacts.empty?())
		  t.contacts = contacts
		
		  print t.display()
	  end
	end
	
	def self.contact_block_query(info)
		if(site_module_loaded?(:Profile))
			info.extend(ProfileBlockQueryInfo);
			info.title = "Contact";
			info.initial_position = 15;
			info.default_visibility = :friends;
			info.initial_column = 1;
			info.form_factor = :both;
			info.multiple = false;
			info.removable = true;
			info.initial_block = true;
			info.max_number = 1;
			info.add_visibility_exclude(:all);
			info.add_visibility_exclude(:logged_in);
			
			info.content_cache_timeout = 120
		end
		
		return info;
	end
	
	def contact_block_edit(block_id=nil)
		request.reply.headers["Content-Type"] = PageRequest::MimeType::PlainText
		
		t = Template::instance('profile', 'contact_edit_block')
		
		contacts = []
		contacts << Contact.new("MSN:", request.user.profile.msn, 'msn')
		contacts << Contact.new("AIM:", request.user.profile.aim, 'aim')
		contacts << Contact.new("Yahoo:", request.user.profile.yahoo, 'yahoo')
		icq_val = (request.user.profile.icq > 0) ? request.user.profile.icq : "";
		contacts << Contact.new("ICQ:", icq_val, 'icq')
		
		t.contacts = contacts
		t.user = request.user
		
		puts t.display
	end
	
	def contact_block_save(block_id)
		request.user.profile.msn = params['msn', String, ""]
		request.user.profile.yahoo = params['yahoo', String, ""]
		request.user.profile.aim = params['aim', String, ""]
		request.user.profile.icq = params['icq', Integer, 0]
		request.user.profile.profileupdatetime = Time.now.to_i
		request.user.profile.store
	end
	
	def remove_contact_block(block_id)
		return;
	end
	
	def visibility_save(block_id)
		return;
	end
end
