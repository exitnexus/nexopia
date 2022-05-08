require "json";

lib_require :Core, "text_manip";
lib_require :Comments, "comment";
lib_require :Comments, "comment_post_result";
lib_require :Comments, "comment_delete_result";
lib_require :Profile, "user_skin";
lib_require :Paginator, "paginator";

module Comments
	class CommentsAdminPageHandler < PageHandler
		
		declare_handlers("comments/admin") {
			area :User
			access_level :Admin, CoreModule, :deletecomments
			
			page :GetRequest, :Full, :admin_view_comments;
			page :GetRequest, :Full, :admin_view_comments_by, "posted", "by";
			
			handle :PostRequest, :admin_delete_comments, "delete";
		}
		
		def admin_view_comments()
			t = Template.instance("comments", "comments_view");
			request.reply.headers['X-width'] = "0";
			
			if(request.user.commentsskin > 0 && request.user.plus?())
				user_skin = Profile::UserSkin.find(:first, [request.user.userid, request.user.commentsskin]);
				#user_skin = request.user.comments_skin;
				if(!user_skin.nil?())
					request.reply.headers['X-user-skin'] = user_skin.header();
				end
			end
			
			# This is needed for abuse log errors
			abuse_error = $site.memcache.get("comments_abuse_error-#{request.session.userid}/#{request.user.userid}");
			if(!abuse_error.nil?())
				t.comments_abuse_error = abuse_error;
				$site.memcache.delete("comments_abuse_error-#{request.session.userid}/#{request.user.userid}")
			end
			
			page = params['page', Integer, 0];
			comments_limit = Comment::COMMENTS_PER_PAGE;
			
			comments_count = Comment.find(:count, request.user.userid);
			page_count = (comments_count.to_f/comments_limit).ceil();
			
			comments_list = Comment.find(request.user.userid, :total_rows, :page => page+1, :limit => comments_limit, :order => "time DESC");
			t.comments_list = comments_list;
			
			t.viewing_user = request.session.user;
			t.profile_user = request.user;
			t.admin_viewer = true;
			
			t.comments_count = comments_count;
			t.num_pages = page_count;
			t.current_page = page;
			t.comments_id_list = comments_list.map{|comment| comment.id}; 
			
			t.show_editor = false;
			t.thumbnail_image_type = :landscapethumb;	
			t.show_delete_controls = (t.admin_viewer || request.user.userid == request.session.user.userid);
			t.comments_delete_url = url / :users / request.user.username / :comments / :admin / :delete;
			t.admin_view = :admin;
			
			if(page_count > 1)
				page_list = Paginator::Paginator.generate_page_list(page, page_count, url / :users / request.user.username / :comments / :admin, request.user.comments_skin[:primary_block_icon_color]);
				t.paging_string = page_list.display();
			end
			
			print t.display();
		end
		
		def admin_view_comments_by()			
			t = Template.instance("comments", "comments_view");
			request.reply.headers['X-width'] = "0";
			
			if(request.user.commentsskin > 0 && request.user.plus?())
				user_skin = Profile::UserSkin.find(:first, [request.user.userid, request.user.commentsskin]);
				#user_skin = request.user.comments_skin;
				if(!user_skin.nil?())
					request.reply.headers['X-user-skin'] = user_skin.header();
				end
			end
			
			# This is needed for abuse log errors
			abuse_error = $site.memcache.get("comments_abuse_error-#{request.session.userid}/#{request.user.userid}");
			if(!abuse_error.nil?())
				t.comments_abuse_error = abuse_error;
				$site.memcache.delete("comments_abuse_error-#{request.session.userid}/#{request.user.userid}")
			end
			
			page = params['page', Integer, 0];
			comments_limit = Comment::COMMENTS_PER_PAGE;
			
			#comments_id_list = Comment.db.query("SELECT userid, id FROM usercomments WHERE authorid = ?", request.user.userid);
			#$log.object(comments_id_list);
			#sorted_comments_id_list = comments_id_list.sort{|x,y| x['userid'] <=> y['userid']};
			#sliced_id_list = sorted_comments_id_list.slice(page*comments_limit, comments_limit);
			complete_comment_list = Comment.find(:total_rows, :conditions => ["authorid = ?", request.user.userid]);
			comments_count = complete_comment_list.total_rows;
			sorted_comment_list = complete_comment_list.sort{|x,y| y.time <=> x.time};
			comments_list = sorted_comment_list.slice(page*comments_limit, comments_limit);
			#comments_count = comments_id_list.length;
			#comments_list = Comment.find(*sliced_id_list);
			
			page_count = (comments_count.to_f/comments_limit).ceil();
			
			t.comments_list = comments_list;
			
			t.viewing_user = request.session.user;
			t.profile_user = request.user;
			t.admin_viewer = true;
			
			t.comments_count = comments_count;
			t.num_pages = page_count;
			t.current_page = page;
			t.comments_id_list = comments_list.map{|comment| comment.id}; 
			
			t.show_editor = false;
			t.thumbnail_image_type = :landscapethumb;	
			t.show_delete_controls = (t.admin_viewer || request.user.userid == request.session.user.userid);
			t.comments_delete_url = url / :users / request.user.username / :comments / :admin / :delete;
			t.admin_view = :admin_comments_by;
			t.show_comments_user_details = true;
			
			if(page_count > 1)
				page_list = Paginator::Paginator.generate_page_list(page, page_count, url / :users / request.user.username / :comments / :admin / :posted / :by, request.user.comments_skin[:primary_block_icon_color]);
				t.paging_string = page_list.display();
			end
			print t.display();
		end
		
		def admin_delete_comments()
			valid_keys = Array.new();
			
			admin_view = params["comments_admin_view", String, "normal"];
			
			page = params["page", Integer, 0];
			comments_limit = Comment::COMMENTS_PER_PAGE;
			current_view = params["current_view", String, "admin_comments"];
			
			abuse_log_entry = params["abuse_log_entry", String, ""];
			abuse_log_subject = params["abuse_log_subject", String, ""];
			abuse_log_reason = params["abuse_log_reason", Integer, nil];
			
			if(abuse_log_reason.nil?() || abuse_log_subject.length < 1)
				$site.memcache.set("comments_abuse_error-#{request.session.userid}/#{request.user.userid}", "Comments not deleted. Abuse reason and subject needed.", 10);
				if(admin_view == "admin")
					site_redirect((url / request.user.username / :comments / :admin & {:page => page}).to_s() + "#abuse_log_anchor");
				elsif(admin_view == "admin_comments_by")
					site_redirect((url / request.user.username / :comments / :admin & {:page => page}).to_s() + "#abuse_log_anchor");
				else
					site_redirect((url / request.user.username / :comments & {:page => page}).to_s() + "#abuse_log_anchor")
				end
			end
			
			params.keys.each{|key|
				if(key =~ /^comment_delete_\d*\:\d*$/)
					valid_keys << key;
				end
			};
			
			error_list = Hash.new();
			author_id_list = Array.new();

			valid_keys.each{|key|
				comment_key = key.gsub(/^comment_delete_/, "");
				comment_key_parts = comment_key.split(":");
				if(comment_key_parts.length != 2)
					next;
				end

				delete_result = CommentsPageHandler.process_delete_comment(comment_key_parts[0], comment_key_parts[1], request.session.user, true, request);
				
				if(delete_result.success)
					author_id_list << delete_result.author_id;
				elsif(!delete_result.success && error_list[delete_result.error])
					error_list[delete_result.error] = delete_result.translate_error();
				end
			}
			
			if(!error_list.empty?())
				t = Template.instance("comments", "comments_delete_error");
				t.error_reason_list = error_list.values;
				$site.memcache.set("comments_abuse_error-#{request.session.userid}/#{request.user.userid}", t.display(), 10);
			end
			
			author_id_list.uniq!();
			author_id_list.each{|author_id|
				AbuseLog.make_entry(request.session.user.userid, author_id, 
					AbuseLog::ABUSE_ACTION_EDIT_COMMENTS, abuse_log_reason, abuse_log_subject, abuse_log_entry);
			}
			
			num_pages = (request.user.comments_count.to_f/comments_limit).ceil();
			if(page > num_pages)
				page = num_pages;
			end
			
			if(admin_view == "admin")
				site_redirect(url / request.user.username / :comments / :admin & {:page => page});
			elsif(admin_view == "admin_comments_by")
				site_redirect(url / request.user.username / :comments / :admin / :posted / :by & {:page => page});
			else
				site_redirect(url / request.user.username / :comments & {:page => page});
			end
		end
	end
end