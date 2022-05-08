require "json";

lib_require :Core, "admin_log"
lib_require :Core, "text_manip";
lib_require :Comments, "comment";
lib_require :Comments, "comment_post_result";
lib_require :Comments, "comment_delete_result";
lib_require :Profile, "user_skin";
lib_require :Paginator, "paginator";

module Comments
	class CommentsPageHandler < PageHandler
		declare_handlers("comments") {
			area :Self
			site_redirect(:GetRequest, remain) {|remain| [url/:comments/remain, [:User, PageRequest.current.session.user]] }

			area :User
			access_level :LoggedIn
			
			page :GetRequest, :Full, :view_comments;
			
			handle :PostRequest, :post_comment, "post";
			handle :PostRequest, :dynamic_comment_post, "post", "dynamic";
			
			handle :PostRequest, :delete_comments, "delete";
			handle :GetRequest, :quick_comment_delete, "delete", input(Integer);
			handle :GetRequest, :dynamic_quick_comment_delete, "delete", input(Integer), "dynamic";
		}
		
		def view_comments()
			# If the profile's owner has disabled comments then we should send anyone who (except the owner)
			# tries to get to the comments page directly back to the profile page.			
			if (!Profile::ProfileBlockVisibility.visible?(request.user.send("commentsmenuaccess".to_sym()), request.user, request.session.user)  &&
						PageRequest.current.user.userid != PageRequest.current.session.user.userid)
				site_redirect(url / request.user.username)
			end
			
			page = params['page', Integer, 0];
			
			# If, for some bizarre reason, the page number comes through as a negative number, redirect the viewer to the first comment page
			if(page < 0)
				site_redirect(url / request.user.username / :comments);
			end
			
			comments_limit = Comment::COMMENTS_PER_PAGE;
			page_count = (request.user.comments_count.to_f/comments_limit).ceil();
			
			# If the page requested doesn't exist, we will redirect the user to the last page of comments.
			if(page >= page_count && page_count > 0)
				site_redirect(url / request.user.username / :comments & {:page => page_count-1});
			end
			
			# If the viewing user is the owner and they have new comments, clean that up.
			if (request.session.user == request.user && request.user.newcomments > 0)
				request.user.newcomments = 0;
				request.user.store();
			end
			
			request.reply.headers['X-width'] = "0";
			
			if(request.user.commentsskin > 0 && request.user.plus?())
				user_skin = Profile::UserSkin.find(:first, [request.user.userid, request.user.commentsskin]);
				#user_skin = request.user.comments_skin;
				if(!user_skin.nil?())
					request.reply.headers['X-user-skin'] = user_skin.header();
				end
			end
			
			t = Template.instance("comments", "comments_view");
			
			# Error in posting/deleting from non javascript users get put in memcache so we can easily display them on page reload.
			post_error = $site.memcache.get("user_comment_post_error-#{request.session.user.userid}/#{request.user.userid}");
			if(!post_error.nil?())
				t.post_error = post_error;
				$site.memcache.delete("user_comment_post_error-#{request.session.user.userid}/#{request.user.userid}")
			end
			
			# Any errors in abuse actions get stored in memcache. We need to pull them out to display them.
			if(PageRequest.current.session.has_priv?(CoreModule, :deletecomments))
				abuse_error = $site.memcache.get("comments_abuse_error-#{request.session.userid}/#{request.user.userid}");
				if(!abuse_error.nil?())
					t.comments_abuse_error = abuse_error;
					$site.memcache.delete("comments_abuse_error-#{request.session.userid}/#{request.user.userid}")
				end
			end
			
			comments_list = Comment.find(request.user.userid, :total_rows, :conditions => ["deleted = 'n'"], :page => page+1, :limit => comments_limit, :order => "time DESC");
			t.comments_list = comments_list;
			
			t.viewing_user = request.session.user;
			t.profile_user = request.user;
			t.admin_viewer = PageRequest.current.session.has_priv?(CoreModule, :deletecomments);
			t.admin_view = :normal;
			
			t.comments_count = request.user.comments_count;
			t.num_pages = page_count;
			t.current_page = page;
			t.comments_id_list = comments_list.map{|comment| comment.id}; 
			t.number_of_comments = comments_list.total_rows;
			
			t.show_editor = Comments.can_post?(request.user, request.session.user);
			#$log.info("Show editor: #{t.show_editor}")
			t.comment_post_url = url / :users / request.user.username / :comments / :post;
			t.editor_text = "Post a comment...";
			
			t.thumbnail_image_type = :landscapethumb;	
			t.show_delete_controls = ((t.admin_viewer || request.user.userid == request.session.user.userid) && request.user.comments_count > 0);
			if(t.admin_viewer && request.user.userid != request.session.user.userid)
				t.comments_delete_url = url / :users / request.user.username / :comments / :admin / :delete;
			else
				t.comments_delete_url = url / :users / request.user.username / :comments / :delete;
			end
			t.page_url = url / :users / request.user.username / :comments;
			t.comments_quick_delete_url = url / :users / request.user.username / :comments / :delete;
			
			if(page_count == 0)
				page_count = 1;
			end
			
			if(page_count > 1)
				page_list = Paginator::Paginator.generate_page_list(page, page_count, url / :users / request.user.username / :comments, request.user.comments_skin[:primary_block_icon_color]);
				t.paging_string = page_list.display();
			end
			
			print t.display();
		end

		# This is the non javascript comment posting method.
		def post_comment()
			content = params['comment_text', String, ""];
			
			result = CommentsPageHandler.process_comment_post(request.session.user, request.user, content, request.get_ip_as_int());
			
			if(!result.success)
				$site.memcache.set("user_comment_post_error-#{request.session.user.userid}/#{request.user.userid}", result.error, 10);
			end
			
			site_redirect(url / request.user.username / :comments);
		end
		
		def dynamic_comment_post()
			content = params['comment_text', String, ""];
			conversation_page = params['conversation_page', Boolean, false];
			
			result = CommentsPageHandler.process_comment_post(request.session.user, request.user, content, request.get_ip_as_int());
			
			result.current_page = 0;
			result.thumbnail_image_type = :landscapethumb;
			if(conversation_page)
				result.show_delete_controls = false;
				#result.conversation_page = true;
				#result.comment_id = result.comment_obj.time;
			else
				result.comments_delete_url = url / :users / request.user.username / :comments / :delete;
			end
			
			print result.to_json();
		end
		
		# This needs to be a static method so the profile block can also leverage it.
		def self.process_comment_post(posting_user, comments_user, content, posting_user_ip)
			comment_post_result = CommentPostResult.new();
		
			if(content.length <= 0)
				comment_post_result.success = false;
				comment_post_result.error = "Comment post had no content";
			end
			
			if(comment_post_result.success)
				begin
					content.spam_filter();
				rescue SpamFilterException
					comment_post_result.success = false;
					comment_post_result.error = $!.to_s();
				end
			end

			if(comment_post_result.success && CommentsPageHandler.hit_post_rate_limit(posting_user.userid))
				comment_post_result.success = false;
				comment_post_result.error = "You've sent too many comments too quickly";
			end
			
			if(comment_post_result.success && posting_user.anonymous?())
				comment_post_result.success = false;
				comment_post_result.error = "Comment posts by anonymous users are not allowed";
			end
			
			if(comment_post_result.success && comments_user.ignored?(posting_user))
				comment_post_result.success = false;
				comment_post_result.error = "You are ignored by #{comments_user.username} and are not allowed to post comments";
			end
			
			if(comment_post_result.success && comments_user.friends_only?(:comments) && (!comments_user.friend?(posting_user) && comments_user.userid != posting_user.userid))
				comment_post_result.success = false;
				comment_post_result.error = "#{comments_user.username} only accepts comments from friends";
			end
			
			if(comment_post_result.success && comments_user.ignore_section_by_age?(:comments) && (!comments_user.friend?(posting_user) && comments_user.userid != posting_user.userid) && 
					(posting_user.age < comments_user.defaultminage || posting_user.age > comments_user.defaultmaxage))	
					comment_post_result.success = false;
					comment_post_result.error = "#{comments_user.username} does not accept comments from users outside #{comments_user.possessive_pronoun} age range";
			end
			
			if(!comment_post_result.success)
				return comment_post_result;
			end
			
			comment = Comment.new();
			comment.userid = comments_user.userid;
			comment.id = Comment.get_seq_id(comments_user.userid);
			comment.authorid = posting_user.userid;
			comment.authorip = posting_user_ip;
			comment.time = Time.now.to_i();
			comment.nmsg = content;

			comment.store();

			comment_post_result.comment_id = comment.id;
			comment_post_result.comment_obj = comment;
			comment_post_result.profile_user = comments_user;
			comment_post_result.viewing_user = posting_user;
			comment_post_result.admin_viewer = PageRequest.current.session.has_priv?(CoreModule, :deletecomments);

			return comment_post_result;
		end

		# Returns true if the user hasn't hit the comments post rate, false if he/she has. The time limit is defined
		#  in seconds. If the user trips the rate limit the timeout becomes longer to better prevent spamming.
		def self.hit_post_rate_limit(user_id)
			initial_time_limit = 5;
			repeat_time_limit = 15;
			key = "user_comments_post_rate_limit-#{user_id}";
			
			limit = $site.memcache.get(key);
			if(limit)
				$site.memcache.set(key, true, repeat_time_limit);
				return true;
			else
				$site.memcache.set(key, true, initial_time_limit);
				return false;
			end
		end
	
		def quick_comment_delete(comment_id)
			user_key = params['key', String, nil];
			
			if(!Authorization.instance.check_key(request.session.user.userid, user_key))
				$site.memcache.set("user_comment_post_error-#{request.session.user.userid}/#{request.user.userid}", "Could not delete comment, key did not verify", 10);
			else
				delete_result = CommentsPageHandler.process_delete_comment(request.user.userid, comment_id, request.session.user);
			
				page = params["page", Integer, 0];
		
				if(!delete_result.success)
					$site.memcache.set("user_comment_post_error-#{request.session.user.userid}/#{request.user.userid}", delete_result.json_translate_error(), 10);
				end
			end
			
			site_redirect(url / request.user.username / :comments & {:page => page});
		end
		
		def dynamic_quick_comment_delete(comment_id)
			user_key = params["key", String, nil];
			last_comment_id = params["last_id", Integer, nil];
			
			if(!Authorization.instance.check_key(request.session.user.userid, user_key))
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
			
			page = params["page", Integer, 0];
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
			delete_result.thumbnail_image_type = :landscapethumb;
			delete_result.current_page = page;
			delete_result.comments_delete_url = url / :users / request.user.username / :comments / :delete;
			
			print delete_result.to_json();
		end
		
		def delete_comments()
			valid_keys = Array.new();
			
			page = params["page", Integer, 0];
			comments_limit = Comment::COMMENTS_PER_PAGE;

			params.keys.each{|key|
				if(key =~ /^comment_delete_\d*\:\d*$/)
					valid_keys << key;
				end
			};
			
			error_list = Hash.new();
			
			valid_keys.each{|key|
				comment_key = key.gsub(/^comment_delete_/, "");
				comment_key_parts = comment_key.split(":");
				if(comment_key_parts.length != 2)
					next;
				end
				
				delete_result = CommentsPageHandler.process_delete_comment(comment_key_parts[0], comment_key_parts[1], request.session.user);
				
				if(!delete_result.success && error_list[delete_result.error].nil?())
					error_list[delete_result.error] = delete_result.translate_error();
				end
			}
			
			if(!error_list.empty?())
				t = Template.instance("comments", "comments_delete_error");
				t.error_reason_list = error_list.values;
				$site.memcache.set("user_comment_post_error-#{request.session.user.userid}/#{request.user.userid}", t.display(), 10);
			end
			
			num_pages = (request.user.comments_count.to_f/comments_limit).ceil();
			if(page > num_pages)
				page = num_pages;
			end
			
			site_redirect(url / request.user.username / :comments & {:page => page});
		end
	
		def self.process_delete_comment(user_id, comment_id, deleting_user, admin_user = false, request=nil)
			comment_obj = Comment.find(:first, [user_id.to_i(), comment_id.to_i()]);
			
			comment_delete_result = CommentDeleteResult.new();
			
			if(comment_obj.nil?())
				comment_delete_result.success = false;
				comment_delete_result.error = :not_found;
			elsif(comment_obj.deleted)
				comment_delete_result.success = false;
				comment_delete_result.error = :deleted;
			elsif(!admin_user && deleting_user.userid != comment_obj.userid && (deleting_user.userid != comment_obj.authorid || !deleting_user.plus?()))
				comment_delete_result.success = false;
				comment_delete_result.error = :not_allowed;
			end
			
			if(!comment_delete_result.success)
				return comment_delete_result;
			end
			
			comment_delete_result.removed_comment_id = comment_id;
			comment_delete_result.author_id = comment_obj.authorid;
			comment_obj.deleted = true;
			comment_obj.store();
			
			# We need to do this because we aren't actually deleting the comment, just changing it's deleted status.
			# As such the code to invalidate these two relations doesn't get called.
			$site.memcache.delete(comment_obj.user.first_five_comments.cache_key());
			$site.memcache.delete(comment_obj.user.comments_count.cache_key());

			if(admin_user)
				AdminLog.log(request, "delete comments", "Comment #{comment_obj.userid}-#{comment_obj.id} was deleted")
			end

			return comment_delete_result;
		end
	end
end
