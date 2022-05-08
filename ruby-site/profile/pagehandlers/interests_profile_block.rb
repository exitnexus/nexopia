lib_want	:Profile, "profile_block_query_info_module";
lib_require :Core, "users/interests"

class InterestsBlock < PageHandler
	declare_handlers("profile_blocks/Profile/interests") {
		area :User
		access_level :Any
		page	:GetRequest, :Full, :interests_block, input(Integer);

		area :Self
		page 	:GetRequest, :Full, :interests_block_edit, input(Integer), "edit";
		handle	:PostRequest, :interests_block_save, input(Integer), "save";
	}
	
	def interests_block(block_id)
		t = Template::instance('profile', 'interests_profile_block');
		t.user = request.user
		t.interest_groups = {}
		request.user.interests.select{|i| 
			if (i.interest.category != nil)
				t.interest_groups[i.interest.category] ||= [];
				t.interest_groups[i.interest.category] << i;
			end
		}
		t.interest_nodes = 
		print t.display();
	end
	
	def self.interests_block_query(info)
		if(site_module_loaded?(:Profile))
			info.extend(ProfileBlockQueryInfo);
			info.title = "Interests";
			info.initial_position = 10;
			info.initial_column = 1;
			info.form_factor = :wide;
			info.multiple = false;
			info.removable = false;
			info.javascript_init_function = ProfileBlockQueryInfo::JavascriptFunction.new("init_accordions");
		end
		
		return info;
	end
	
	def interests_block_edit(block_id)
		reply.headers['X-width'] = 420;
		reply.headers['Content-Type'] = PageRequest::MimeType::PlainText
		t = Template::instance('profile', 'interests_edit_block')
		t.user = request.user
		puts t.display
	end
	
	def interests_block_save(block_id)
		checked = params['checkbox', TypeSafeHash]
		lib_require :core, "users/interests"
		checked.each_key{|id|
			interest = Interests.get_by_id(id.to_i)
			if (!request.user.interests.include? interest)
				i = UserInterests.new();
				i.interestid = id.to_i;
				i.userid = request.user.userid;
				i.store;
			end
		}
		throw :page_done;
		#do save like things!
	end
end