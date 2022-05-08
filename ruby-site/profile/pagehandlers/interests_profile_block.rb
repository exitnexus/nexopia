lib_want	:Profile, "profile_block_query_info_module";
lib_require :Core, "users/interests"

class InterestsBlock < PageHandler
	declare_handlers("profile_blocks/Profile/interests") {
		area :User
		access_level :Any
		page	:GetRequest, :Full, :interests_block, input(Integer);

		area :Self
		page 	:GetRequest, :Full, :interests_block_edit, input(Integer), "edit";
		page 	:GetRequest, :Full, :interests_block_edit, "new";

		handle	:PostRequest, :interests_block_save, input(Integer), "save";
		handle	:PostRequest, :interests_block_save, input(Integer), "create";
		
		handle	:PostRequest, :visibility_save, input(Integer), "visibility";
		
		handle	:PostRequest, :interests_block_remove, input(Integer), "remove";
	}
	
	def interests_block(block_id)
		edit_mode = params["profile_edit_mode", Boolean, false];
		
		if(!Profile::ProfileDisplayBlock.verify_visibility(block_id, request.user, request.session.user, edit_mode))
			print "<h1>Not visible</h1>";
			return;
		end
		
		# If there are no interests to show, don't display the block
		if (request.user.interests.empty?)
			return;
		end

		interest_groups = {};
		request.user.interests.each{|i|
			i.interest
		}
		request.user.interests.each{|i| 
			if (i.interest.category != nil)
				interest_groups[i.interest.category] ||= [];
				interest_groups[i.interest.category] << i.interest.name;
			end
		};
		
		t = Template::instance('profile', 'interests_profile_block');
		t.user = request.user;
		t.interest_groups = interest_groups;

		print t.display();
	end
	
	def self.interests_block_query(info)
		if(site_module_loaded?(:Profile))
			info.extend(ProfileBlockQueryInfo);
			info.title = "Interests";
			info.initial_position = 20;
			info.initial_column = 1;
			info.initial_block = true;
			info.form_factor = :wide;
			info.multiple = false;
			info.removable = true;
			info.max_number = 1;
			info.javascript_init_function = ProfileBlockQueryInfo::JavascriptFunction.new("init_accordions");
			
			# changes on a per user basis because it shows only galleries the reader has a right to read.
			# if we want to make it work well, we could make it only ever show public galleries (if any)
			info.content_cache_timeout = 120 
		end
		
		return info;
	end
	
	def interests_block_edit(block_id=nil)
		reply.headers['Content-Type'] = PageRequest::MimeType::PlainText
		t = Template::instance('profile', 'interests_edit_block')
		t.user = request.user
		puts t.display
	end
	
	def interests_block_save(block_id)
		checked = params['checkbox', TypeSafeHash]

		delete_list = Array.new;
		Interests.all_interest_ids.each { |interest_id|
			if (!checked.nil? && checked.has_key?(interest_id.to_s))
				if (!request.user.interests.include? interest_id)
					i = UserInterests.new();
					i.interestid = interest_id;
					i.userid = request.user.userid;
					i.store;
				end
			else
				if (request.user.interests.include? interest_id)
					delete_list << interest_id;
				end
			end
		};
		
		# This is kind of hacky, but I'm told it's the best way to invalidate the cache for this user's interests
		# and also delete possibly large numbers of interests efficiently. By using the deletion of the first interest
		# to have the cache invalidated, if the way the cache is accessed changes in the future, this code won't
		# break. Then the code simply goes on to check if our list of ids to delete is still empty and just does one
		# large delete for that.
		if (!delete_list.empty?)
			UserInterests.find(:first, request.user.userid, delete_list.pop).delete;
		end
		
		if (!delete_list.empty?)
			UserInterests.db.query("DELETE FROM #{UserInterests.table} WHERE userid = # AND interestid IN ?", request.user.userid, delete_list);
		end
	end
	
	
	def interests_block_remove(block_id)
		return;
	end
	
	def visibility_save(block_id)
		return;
	end
end