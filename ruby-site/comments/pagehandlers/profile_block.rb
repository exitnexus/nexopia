require "json";

lib_require :Comments, "comment";
lib_require :Comments, "comment_post_result";
lib_require :Comments, "comment_delete_result";

lib_want	:Profile, "profile_block_query_info_module";

module Comments
	class ProfileBlock < PageHandler
		declare_handlers("profile_blocks/Comments/comments/") {
			area :User
			access_level :Any
			
			# this should become a handle later.
			page :GetRequest, :Full, :comments, input(Integer);
			page :GetRequest, :Full, :refresh, "refresh";
			
			access_level :LoggedIn
			handle :PostRequest, :post_comment, input(Integer), "post";
			handle :PostRequest, :dynamic_comment_post, input(Integer), "post", "dynamic";
			
			handle :GetRequest, :quick_comment_delete, input(Integer), "delete", input(Integer);
			handle :GetRequest, :dynamic_quick_comment_delete, input(Integer), "delete", input(Integer), "dynamic";
			
			area	:Self
			handle	:PostRequest, :comment_block_create, input(Integer), "create";
			handle	:PostRequest, :comment_block_remove, input(Integer), "remove";
		}
		
		def self.comments_query(info)
			if(site_module_loaded?(:Profile))
				info.extend(ProfileBlockQueryInfo);
				info.title = "Comments";
				info.initial_position = 99;
				info.initial_column = 1;
				info.form_factor = :wide;
				info.multiple = false;
				info.editable = false;
				info.initial_block = true;
				info.page_url = ["Comments", url/:comments, 3];
				info.page_per_block = true;

				# Can't be cached because of delete checkboxes that change on a per-user basis.
				info.content_cache_timeout = 0
			end

			return info;
		end

		def comments(block_id)	
			edit_mode = params["profile_edit_mode", Boolean, false];
			
			if(!Profile::ProfileDisplayBlock.verify_visibility(block_id, request.user, request.session.user, edit_mode))
				print "<h1>Not visible</h1>";
				return;
			end
			
			show_editor = Comments.can_post?(request.user, request.session.user)
			if(!show_editor && request.user.comments_count == 0)
			  return;
		  end
			
			t = Template.instance('comments', 'profile_block')			
			
			# This is needed for non-ajax comment post errors.
			post_error = $site.memcache.get("user_comment_post_error-#{request.session.user.userid}/#{request.user.userid}");
			if(!post_error.nil?())
				t.post_error = post_error;
				$site.memcache.delete("user_comment_post_error-#{request.session.user.userid}/#{request.user.userid}")
			end
			
			t.viewing_user = request.session.user;
			t.profile_user = request.user;
			t.admin_viewer = PageRequest.current.session.has_priv?(CoreModule, :deletecomments);
			
			t.show_editor = show_editor
			t.thumbnail_image_type = :landscapemini;
			
			t.comments_list = request.user.first_five_comments
			#temp fix for NEX-875
			t.comments_list.each{|comment| demand(comment)};
			
			t.comments_count = request.user.comments_count;
			t.comments_id_list = request.user.first_five_comments.map{|comment| comment.id};
			
			t.error = params['error', String]
			t.success = params['success', String]

			t.comment_post_url = url/:users/request.user.username/:profile_blocks/:Comments/:comments/block_id/:post;
			t.comments_delete_url = url/:users/request.user.username/:profile_blocks/:Comments/:comments/block_id/:delete;
			t.comments_quick_delete_url = url / :users / request.user.username / :comments / :delete;
						
			t.view_all = request.user.comments_count > 0 && !request.session.user.anonymous?();
			t.current_page = 0;
			
			puts t.display()
		end
		
		def post_comment(block_id)
			display_block = Profile::ProfileDisplayBlock.find(:first, [request.user.userid, block_id]);
			
			result = CommentPostResult.new();
			if(display_block.nil?())
				result.success = false;
				result.error = "The profile block specified (#{block_id}) does not exist";
			elsif(display_block.path != "comments")
				result.success = false;
				result.error = "The profile block specified (#{block_id}) is not a comments block";
			end
			
			if(result.success)
				content = params["comment_text", String, ""];
				result = CommentsPageHandler.process_comment_post(request.session.user, request.user, content, request.get_ip_as_int());
			end
			
			if(!result.success)
				$site.memcache.set("user_comment_post_error-#{request.session.user.userid}/#{request.user.userid}", result.error, 10);
			end
			
			site_redirect(url / request.user.username / :profile);
		end
		
		def dynamic_comment_post(block_id)
			display_block = Profile::ProfileDisplayBlock.find(:first, [request.user.userid, block_id]);
			
			result = CommentPostResult.new();
			if(display_block.nil?())
				result.success = false;
				result.error = "The profile block specified (#{block_id}) does not exist";
			elsif(display_block.path != "comments")
				result.success = false;
				result.error = "The profile block specified (#{block_id}) is not a comments block";
			end
			
			if(result.success)
				content = params["comment_text", String, ""];
				result = CommentsPageHandler.process_comment_post(request.session.user, request.user, content, request.get_ip_as_int());
			end
			
			result.thumbnail_image_type = :landscapemini;
			result.show_delete_controls = false;
			result.comments_delete_url = url/:users/request.user.username/:profile_blocks/:Comments/:comments/block_id/:delete;
			
			print result.to_json();	
		end
		
		def quick_comment_delete(block_id, comment_id)
			user_key = params['key', String, nil];
			
			display_block = Profile::ProfileDisplayBlock.find(:first, [request.user.userid, block_id]);
			
			if(display_block.nil?())
				$site.memcache.set("user_comment_post_error-#{request.session.user.userid}/#{request.user.userid}", "Could not delete comment, block does not exist", 10);
			elsif(display_block.path != "comments")
				$site.memcache.set("user_comment_post_error-#{request.session.user.userid}/#{request.user.userid}", "Could not delete comment, block is of the wrong type", 10);
			elsif(!Authorization.instance.check_key(request.session.user.userid, user_key))
				$site.memcache.set("user_comment_post_error-#{request.session.user.userid}/#{request.user.userid}", "Could not delete comment, key did not verify", 10);
			else
				delete_result = CommentsPageHandler.process_delete_comment(request.user.userid, comment_id, request.session.user);
		
				if(!delete_result.success)
					$site.memcache.set("user_comment_post_error-#{request.session.user.userid}/#{request.user.userid}", delete_result.json_translate_error(), 10);
				end
			end
			
			site_redirect((url / request.user.username / :profile).to_s() + "#comments_top");
		end
		
		def dynamic_quick_comment_delete(block_id, comment_id)
			user_key = params["key", String, nil];
			last_comment_id = params["last_id", Integer, nil];
			
			display_block = Profile::ProfileDisplayBlock.find(:first, [request.user.userid, block_id]);
			
			if(display_block.nil?())
				delete_result = CommentsDeleteResult.new();
				delete_result.success = false;
				delete_result.error = :block_not_exist;
			elsif(!Authorization.instance.check_key(request.session.user.userid, user_key))
				delete_result = CommentsDeleteResult.new();
				delete_result.success = false;
				delete_result.error = :not_allowed;
			else
				delete_result = CommentsPageHandler.process_delete_comment(request.user.userid, comment_id, request.session.user);
			end
			
			if(!delete_result.success)
				print delete_result.to_json();
				return;
			end
			
			page = 0;
			comments_limit = Comment::COMMENTS_PER_PAGE;
			
			num_pages = (request.user.comments_count.to_f/comments_limit).ceil();
			if(page > num_pages || last_comment_id.nil?())
				print delete_result.to_json();
				return;
			end

			next_comment = Comment.find(:first, request.user.userid, :conditions => ["id < ? AND deleted = 'n'", last_comment_id], :limit => 1, :order => "id DESC");
			
			if(next_comment.nil?())
				print delete_result.to_json();
				return;
			end
			
			delete_result.comment_obj = next_comment;
			delete_result.comment_id = next_comment.id;
			delete_result.viewing_user = request.session.user;
			delete_result.profile_user = request.user;
			delete_result.admin_viewer = PageRequest.current.session.has_priv?(CoreModule, :deletecomments);
			delete_result.thumbnail_image_type = :landscapemini;
			delete_result.current_page = page;
			delete_result.show_delete_controls = false;
			delete_result.comments_delete_url = url / :users / request.user.username / :profile_blocks / :Comments / :comments / block_id / :delete;
			
			print delete_result.to_json();
		end
		
		def comment_block_remove(block_id)
			request.session.user.store();
		end
		
		
		def comment_block_create(block_id)
			request.session.user.enablecomments = true;
			request.session.user.commentsmenuaccess = :logged_in;
			request.session.user.store();
		end		
	end
end
