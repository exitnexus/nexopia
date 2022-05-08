lib_require :Core, "text_manip";
lib_require :Gallery, "gallery_comment";
lib_require :Gallery, "gallery_comment_post_result";
lib_require :Gallery, "gallery_comment_delete_result";

module Gallery
	class GalleryCommentsPageHandler < PageHandler
		declare_handlers("gallery/comments") {
			area :User
			access_level :Any
			handle :GetRequest, :view_gallery_comments, input(Integer);
			
			access_level :LoggedIn
			handle :GetRequest, :view_gallery_comments_page, input(Integer), "list";
			
			handle :PostRequest, :post_comment, input(Integer), "post";
			handle :PostRequest, :dynamic_comment_post, input(Integer), "post", "dynamic";
			
			handle :PostRequest, :delete_comments, input(Integer), "delete";
			handle :GetRequest, :quick_comment_delete, input(Integer), "delete", input(Integer);
			handle :GetRequest, :dynamic_quick_comment_delete, input(Integer), "delete", input(Integer), "dynamic";
			
			#viewing for admins will be JS only! On click grab view_gallery_comments_page with admin=true
			access_level :Admin, CoreModule, :deletecomments
			handle :PostRequest, :admin_delete_comments, input(Integer), "admin", "delete";
		}
		
		def view_gallery_comments(gallery_pic_id)

			page = params['page', Integer, 0];
			show_deleted_comments = params["admin_view", Boolean, false] && PageRequest.current.session.has_priv?(CoreModule, :deletecomments);
			post_error = $site.memcache.get("user_gallery_comment_post_error-#{request.session.user.userid}/#{request.user.userid}/#{gallery_pic_id}");
			abuse_error = $site.memcache.get("comments_abuse_error-#{request.session.userid}/#{request.user.userid}/#{gallery_pic_id}");

			show_gallery_comments(gallery_pic_id, page, show_deleted_comments, post_error, abuse_error)

		end
		

		def show_gallery_comments(gallery_pic_id, page = 0, show_deleted_comments = false, post_error = nil, abuse_error = nil)
			gallery_pic_obj = Pic.find(:first, :userid, [request.user.userid, gallery_pic_id]);
			
			if(gallery_pic_obj.nil?())
				return;
			end
			
			comment_limit = GalleryComment::COMMENTS_PER_PAGE;
			page_count = (gallery_pic_obj.comments_count.to_f()/comment_limit).ceil();
			
			if( (page >= page_count) && (page_count > 0) )
				page = 0;
			end
			
			t = Template.instance("gallery", "gallery_comments_view");
			
			if(show_deleted_comments)
				t.gallery_pic_comment_list = GalleryComment.find(:userid, [request.user.userid, gallery_pic_obj.id], :page => page+1, :limit => comment_limit, :order => "id ASC");
				t.gallery_pic_comment_count = GalleryComment.find(:count, :userid, [request.user.userid, gallery_pic_obj.id]);
				t.get_params = "admin_view=true";
			else
				t.gallery_pic_comment_list = GalleryComment.find(:userid, [request.user.userid, gallery_pic_id], :conditions => ["deleted = 'n'"], :page => page+1, :limit => comment_limit, :order => "time ASC");
				t.gallery_pic_comment_count = gallery_pic_obj.comments_count;
			end
			
			# This is needed for non-ajax comment post errors.
			if(!post_error.nil?())
				t.post_error = post_error;
				$site.memcache.delete("user_gallery_comment_post_error-#{request.session.user.userid}/#{request.user.userid}/#{gallery_pic_id}")
			end
			
			# This is needed for abuse log errors
			if(!abuse_error.nil?())
				t.comments_abuse_error = abuse_error;
				$site.memcache.delete("comments_abuse_error-#{request.session.userid}/#{request.user.userid}/#{gallery_pic_id}")
			end
			
			t.viewing_user = request.session.user;
			t.gallery_user = request.user;
			
			t.admin_viewer = PageRequest.current.session.has_priv?(CoreModule, :deletecomments);
			t.admin_view = :normal;
			
			t.show_editor = false;
			if(gallery_pic_obj.gallery.allowcomments)
				t.show_editor = GalleryComment.can_post?(request.user, request.session.user);
			end
			t.editor_text = "Post a comment...";
			t.comment_post_url = url / :users / request.user.username / :gallery / :comments / gallery_pic_obj.id / :post;
			
			t.show_delete_controls = ((t.admin_viewer || request.user.userid == request.session.user.userid) && gallery_pic_obj.comments_count > 0);
			t.comments_quick_delete_url = url / :users / request.user.username / :gallery / :comments / gallery_pic_obj.id / :delete;
			if(t.admin_viewer && request.session.user != request.user)
				t.comments_delete_url = url / :users / request.user.username / :gallery / :comments / gallery_pic_obj.id / :admin / :delete;
			else
				t.comments_delete_url = url / :users / request.user.username / :gallery / :comments / gallery_pic_obj.id / :delete;
			end
			
			if(t.admin_viewer)
				t.admin_view_url = url / :users / request.user.username / :gallery / gallery_pic_obj.galleryid / gallery_pic_obj.id & {:admin_view => true};
			end
			
			t.page_url = url / :users / request.user.username / :gallery / :comments / gallery_pic_obj.id / :list;
			t.current_page = page;
			t.page_count = page_count 
			
			if(t.page_count < 1)
				page_count = 1;
			end
			
			if(show_deleted_comments)
				addition_get_params = {:admin_view => true};
			end
			
			page_list = Paginator::Paginator.generate_page_list(t.current_page, page_count, url / :users / request.user.username / :gallery / gallery_pic_obj.galleryid / gallery_pic_obj.id, request.user.gallery_skin[:primary_block_icon_color], addition_get_params);
			page_list.minion_name = "gallery_comments:paging_control";
			t.paging_string = page_list.display();
			t.gallery_pic_obj = gallery_pic_obj;
			
			puts t.display();
		end
		
		
		def view_gallery_comments_page(gallery_pic_id)
			gallery_pic_obj = Pic.find(:first, :userid, [request.user.userid, gallery_pic_id]);
			
			page = params["page", Integer, 0];
			show_deleted_comments = params["admin_view", Boolean, false] && PageRequest.current.session.has_priv?(CoreModule, :deletecomments);
			
			# Figure out the page count.  If we're showing deleted comments we'll have to get the actual number of comments first.
			comments_limit = GalleryComment::COMMENTS_PER_PAGE;
			if(show_deleted_comments)
				total_comment_count = GalleryComment.find(:count, :userid, [request.user.userid, gallery_pic_obj.id]);
				page_count = (total_comment_count.to_f()/comments_limit).ceil();
			else
				page_count = (gallery_pic_obj.comments_count.to_f()/comments_limit).ceil();
			end

			# If the request was for a page greater than our page count
			# show the last page instead.
			if(page >= page_count)
				page = page_count - 1;
			end
			
			t = Template.instance("gallery", "gallery_comments_page_view");

			# Get the comments we want to show for that page, including the deleted comments if requested.
			if(show_deleted_comments)
				comments_list = GalleryComment.find(:userid, [request.user.userid, gallery_pic_id], :page => page + 1, :limit => comments_limit, :order => "id ASC");
			else
				comments_list = GalleryComment.find(:userid, [request.user.userid, gallery_pic_id], :conditions => ["deleted = 'n'"], :page => page + 1, :limit => comments_limit, :order => "time ASC");
			end
			t.gallery_pic_comment_list = comments_list;
			t.gallery_pic_comment_count = comments_list.length;

			t.viewing_user = request.session.user;
			t.gallery_user = request.user;
			
			t.show_delete_controls = ((t.admin_viewer || request.user.userid == request.session.user.userid) && gallery_pic_obj.comments_count > 0);
			t.comments_quick_delete_url = url / :users / request.user.username / :gallery / :comments / gallery_pic_obj.id / :delete;
			
			t.current_page = page;

			if(show_deleted_comments)
				addition_get_params = {:admin_view => true};
			end
			
			page_list = Paginator::Paginator.generate_page_list(t.current_page, page_count, url / :users / request.user.username / :gallery / gallery_pic_obj.galleryid / gallery_pic_obj.id, request.user.gallery_skin[:primary_block_icon_color], addition_get_params);
			page_list.minion_name = "gallery_comments:paging_control";
			t.paging_string = page_list.display();
			
			puts t.display();
		end
		
		
		def post_comment(gallery_pic_id)
			content = params['comment_text', String, ""];
			
			gallery_pic_obj = Pic.find(:first, :userid, [request.user.userid, gallery_pic_id]);
			
			result = GalleryCommentsPageHandler.process_comment_post(request.session.user, request.user, gallery_pic_obj, content, request.get_ip_as_int());
			
			if(!result.success)
				$site.memcache.set("user_gallery_comment_post_error-#{request.session.user.userid}/#{request.user.userid}/#{gallery_pic_obj.id}", result.error, 10);
			end

			site_redirect(url / request.user.username / :gallery / gallery_pic_obj.galleryid / gallery_pic_obj.id);
		end
				
				
		def dynamic_comment_post(gallery_pic_id)
			content = params['comment_text', String, ""];
			
			gallery_pic_obj = Pic.find(:first, :userid, [request.user.userid, gallery_pic_id]);
			
			result = GalleryCommentsPageHandler.process_comment_post(request.session.user, request.user, gallery_pic_obj, content, request.get_ip_as_int());
			
			result.thumbnail_image_type = :landscapemini;
			result.comments_delete_url = url / :users / request.user.username / :gallery / :comments / gallery_pic_obj.id / :delete;
			
			if (!result.success)
				error_message = result.error
			end
			
			page_count = (gallery_pic_obj.comments_count.to_f()/GalleryComment::COMMENTS_PER_PAGE).ceil();
			
			show_gallery_comments(gallery_pic_id, page_count - 1, false, error_message)
			
			print result.to_json();
		end
		
		
		def delete_comments(gallery_pic_id)
			gallery_pic_obj = Pic.find(:first, :userid, [request.user.userid, gallery_pic_id]);
			
			error_list = Hash.new();
			valid_keys = Array.new();
			
			page = params["page", Integer, 0];
			comments_limit = GalleryComment::COMMENTS_PER_PAGE;

			params.keys.each{|key|
				if(key =~ /^comment_delete_\d*\:\d*$/)
					valid_keys << key;
				end
			};
			
			valid_keys.each{|key|
				comment_key = key.gsub(/^comment_delete_/, "");
				comment_key_parts = comment_key.split(":");
				if(comment_key_parts.length != 2)
					next;
				end
				
				delete_result = GalleryCommentsPageHandler.process_delete_comment(comment_key_parts[0], comment_key_parts[1], gallery_pic_obj, request.session.user);
				
				if(!delete_result.success && error_list[delete_result.error].nil?())
					error_list[delete_result.error] = delete_result.translate_error();
				end
			}
			
			if(!error_list.empty?())
				t = Template.instance("gallery", "gallery_comments_delete_error");
				t.error_reason_list = error_list.values;
				$site.memcache.set("user_gallery_comment_post_error-#{request.session.user.userid}/#{request.user.userid}/#{gallery_pic_id}", t.display(), 10);
			end
			
			if(gallery_pic_obj.nil?())
				site_redirect(url / request.user.username / :gallery);
			end
			
			num_pages = (gallery_pic_obj.comments_count.to_f/comments_limit).ceil();
			if(page > num_pages)
				page = num_pages;
			end
			
			site_redirect(url / request.user.username / :gallery / gallery_pic_obj.galleryid / gallery_pic_obj.id);
		end
		
		
		def quick_comment_delete(gallery_pic_id, comment_id)
			user_key = params['key', String, nil];
			
			gallery_pic_obj = Pic.find(:first, [request.user.userid, gallery_pic_id]);
			if(!Authorization.instance.check_key(request.session.user.userid, user_key))
				$site.memcache.set("user_gallery_comment_post_error-#{request.session.user.userid}/#{request.user.userid}/#{gallery_pic_id}", "Could not delete comment, key did not verify", 10);
			else
				delete_result = GalleryCommentsPageHandler.process_delete_comment(request.user.userid, comment_id, gallery_pic_obj, request.session.user);
			
				page = params["page", Integer, 0];
		
				if(!delete_result.success)
					$site.memcache.set("user_gallery_comment_post_error-#{request.session.user.userid}/#{request.user.userid}/#{gallery_pic_id}", delete_result.json_translate_error(), 10);
				end
			end
			
			if(gallery_pic_obj.nil?())
				site_redirect(url / request.user.username / :gallery);
			else
				site_redirect(url / request.user.username / :gallery / gallery_pic_obj.galleryid / gallery_pic_obj.id);
			end
		end
		
		
		def dynamic_quick_comment_delete(gallery_pic_id, comment_id)
			user_key = params["key", String, nil];
			last_comment_id = params["last_id", Integer, nil];
			
			gallery_pic_obj = Pic.find(:first, [request.user.userid, gallery_pic_id]);
			
			if(!Authorization.instance.check_key(request.session.user.userid, user_key))
				delete_result = CommentsDeleteResult.new();
				delete_result.success = false;
				delete_result.error = :not_allowed;
			else
				delete_result = GalleryCommentsPageHandler.process_delete_comment(request.user.userid, comment_id, gallery_pic_obj, request.session.user);
			end
			
			if(!delete_result.success)
				print delete_result.to_json();
				return;
			end
			
			page = params["page", Integer, 0];
			comments_limit = GalleryComment::COMMENTS_PER_PAGE;
			
			num_pages = (gallery_pic_obj.comments_count.to_f/comments_limit).ceil();
			if(page > num_pages || last_comment_id.nil?())
				print delete_result.to_json();
				return;
			end

			next_comment = GalleryComment.find(:first, :userid, [request.user.userid, gallery_pic_obj.id], :conditions => ["id < ? AND deleted = 'n'", last_comment_id], :limit => 1, :order => "id ASC");
						
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
			delete_result.comments_delete_url = url / :users / request.user.username / :gallery / :comments / gallery_pic_obj.id / :delete;
			
			print delete_result.to_json();
		end
		
		
		def admin_delete_comments(gallery_pic_id)
			gallery_pic_obj = Pic.find(:first, [request.user.userid, gallery_pic_id]);
			
			valid_keys = Array.new();
			
			comments_limit = GalleryComment::COMMENTS_PER_PAGE;
			
			abuse_log_entry = params["abuse_log_entry", String, ""];
			abuse_log_subject = params["abuse_log_subject", String, ""];
			abuse_log_reason = params["abuse_log_reason", Integer, nil];
			
			if(abuse_log_reason.nil?() || abuse_log_subject.length < 1)
				$site.memcache.set("comments_abuse_error-#{request.session.userid}/#{request.user.userid}/#{gallery_pic_obj.id}", "Comments not deleted. Abuse reason and subject needed.", 10);
				
				site_redirect((url / request.user.username / :gallery / gallery_pic_obj.galleryid / gallery_pic_obj.id).to_s() + "#abuse_log_anchor")
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

				delete_result = GalleryCommentsPageHandler.process_delete_comment(comment_key_parts[0], comment_key_parts[1], gallery_pic_obj, request.session.user, true);
				
				if(delete_result.success)
					author_id_list << delete_result.author_id;
				elsif(!delete_result.success && error_list[delete_result.error])
					error_list[delete_result.error] = delete_result.translate_error();
				end
			}
			
			if(!error_list.empty?())
				t = Template.instance("gallery", "gallery_comments_delete_error");
				t.error_reason_list = error_list.values;
				$site.memcache.set("comments_abuse_error-#{request.session.userid}/#{request.user.userid}/#{gallery_pic_obj.id}", t.display(), 10);
			end
			
			author_id_list.uniq!();
			author_id_list.each{|author_id|
				AbuseLog.make_entry(request.session.user.userid, author_id, 
					AbuseLog::ABUSE_ACTION_EDIT_GALLERY_COMMENTS, abuse_log_reason, abuse_log_subject, abuse_log_entry);
			}
			
			site_redirect(url / request.user.username / :gallery / gallery_pic_obj.galleryid / gallery_pic_obj.id);
		end
		
		
		def self.process_comment_post(posting_user, comments_user, gallery_pic_obj, content, posting_user_ip)
			comment_post_result = GalleryCommentPostResult.new();
			
			if(gallery_pic_obj.nil?())
				comment_post_result.success = false;
				comment_post_result.error = "The gallery picture specified doesn't exist";
			end
			
			if(!gallery_pic_obj.gallery.allowcomments && comment_post_result.success)
				comment_post_result.success = false;
				comment_post_result.error = "This gallery does not allow comments";
			end
			
			if(content.length <= 0 && comment_post_result.success)
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
			
			if(comment_post_result.success && GalleryCommentsPageHandler.hit_post_rate_limit(posting_user.userid))
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
			
			gallery_comment = GalleryComment.new();
			gallery_comment.userid = comments_user.userid;
			gallery_comment.picid = gallery_pic_obj.id;
			gallery_comment.id = GalleryComment.get_seq_id(comments_user.userid);
			gallery_comment.authorid = posting_user.userid;
			gallery_comment.authorip = posting_user_ip;
			gallery_comment.time = Time.now.to_i();
			gallery_comment.nmsg = content;
			
			gallery_comment.store();
			
			comment_post_result.comment_id = gallery_comment.id;
			comment_post_result.comment_obj = gallery_comment;
			comment_post_result.profile_user = comments_user;
			comment_post_result.viewing_user = posting_user;
			comment_post_result.admin_viewer = PageRequest.current.session.has_priv?(CoreModule, :deletecomments);
			
			return comment_post_result;
		end
		
		
		def self.process_delete_comment(user_id, comment_id, gallery_pic_obj, deleting_user, admin_user = false)
			gallery_comment_obj = GalleryComment.find(:first, [user_id.to_i(), comment_id.to_i()]);
			
			comment_delete_result = GalleryCommentDeleteResult.new();
			if(gallery_pic_obj.nil?())
				comment_delete_result.success = false;
				comment_delete_result.error = :pic_not_exist;
			elsif(gallery_comment_obj.nil?())
				comment_delete_result.success = false;
				comment_delete_result.error = :not_found;
			elsif(gallery_comment_obj.deleted)
				comment_delete_result.success = false;
				comment_delete_result.error = :deleted;
			elsif(!admin_user && deleting_user.userid != gallery_comment_obj.userid && (deleting_user.userid != gallery_comment_obj.authorid || !deleting_user.plus?()))
				comment_delete_result.success = false;
				comment_delete_result.error = :not_allowed;
			end

			if(!comment_delete_result.success)
				return comment_delete_result;
			end

			comment_delete_result.removed_comment_id = comment_id;
			comment_delete_result.author_id = gallery_comment_obj.authorid;
			gallery_comment_obj.deleted = true;
			gallery_comment_obj.store();

			# We need to do this because we aren't actually deleting the comment, just changing it's deleted status.
			# As such the code to invalidate these two relations doesn't get called.
			$site.memcache.delete(gallery_pic_obj.first_five_comments.cache_key());
			$site.memcache.delete(gallery_pic_obj.comments_count.cache_key());

			$log.info("Gallery Comment #{gallery_comment_obj.userid}-#{gallery_comment_obj.id}-#{gallery_comment_obj.picid} was deleted", :info, :admin);

			return comment_delete_result;
		end
		
		
		# Returns true or false depending on if the action can be performed. The time limit is defined
		#  in seconds. If the user trips the rate limit the timeout becomes longer to better prevent spamming.
		def self.hit_post_rate_limit(user_id)
			initial_time_limit = 5;
			repeat_time_limit = 15;
			key = "user_gallery_comments_post_rate_limit-#{user_id}";
			
			limit = $site.memcache.get(key);
			if(limit)
				$site.memcache.set(key, true, repeat_time_limit);
				return true;
			else
				$site.memcache.set(key, true, initial_time_limit);
				return false;
			end
		end
	end
end
