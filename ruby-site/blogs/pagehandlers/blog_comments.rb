lib_require :Blogs, "blog_post";
lib_require :Blogs, "blog_comment";
lib_require :Blogs, "blog_comment_post_result";
lib_require :Blogs, "blog_comment_unread_notification";

lib_require :Core, "admin_log";

lib_require :Nexoskel, "abuse_log_entry_processing";

lib_want :Profile, "user_skin";

module Blogs
	class BlogCommentsPageHandler < PageHandler
		declare_handlers("blog") {
			area :User
			access_level :Any
			page :GetRequest, :Full, :view_blog_comments, input(Integer), "comments";
			
			access_level :LoggedIn
			page :GetRequest, :Full, :new_comment_view, input(Integer), "comment", input(Integer), "reply";
			
			handle :PostRequest, :add_comment, input(Integer), "comment", "submit";
			handle :PostRequest, :add_comment, input(Integer), "comment", input(Integer), "submit";
			
			handle :PostRequest, :dynamic_add_comment, input(Integer), "comment", "submit", "dynamic";
			handle :PostRequest, :dynamic_add_comment, input(Integer), "comment", input(Integer), "submit", "dynamic";
			
			handle :PostRequest, :delete_comments, input(Integer), "comments", "delete";
			handle :GetRequest, :quick_comment_delete, input(Integer), "comment", input(Integer), "delete";
			handle :GetRequest, :ignore_link, "comment", "ignore_link";
			
			area :Self
			handle :PostRequest, :ignore_user, 'ignore_user', input(Integer);
			handle :PostRequest, :unignore_user, 'unignore_user', input(Integer);	
		}
		
		declare_handlers("blog/admin") {
			area :User
			access_level :Admin, CoreModule, :editjournal
			handle :PostRequest, :admin_delete_comments, input(Integer), "comments", "delete";
		}
		
		include AbuseLogEntryProcessing
		
		def view_blog_comments(post_id)
			blog_post = BlogPost.find(:first, [request.user.userid, post_id]);
			
			if(blog_post.nil?())
				return;
			elsif(!blog_post.allowcomments)
				return;
			end
			
			build_comment_tree(blog_post);
			
			page = params["page", Integer, 0];
			
			comment_limit = BlogComment::COMMENTS_PER_PAGE;
			total_pages = (blog_post.total_comments_count.to_f()/comment_limit).ceil();
			
			#$log.info("total pages is: #{total_pages}");
			mark_display_nodes(blog_post, page*comment_limit, comment_limit);

			
			if(total_pages == 0)
				total_pages = 1;
			end
			t = Template.instance("blogs", "blog_comments_view");

			post_error = $site.memcache.get("user_blog_comment_post_error-#{request.session.user.userid}/#{request.user.userid}");
			if(!post_error.nil?())
				t.post_error = post_error;
				$site.memcache.delete("user_blog_comment_post_error-#{request.session.user.userid}/#{request.user.userid}")
			end
						
			t.blog_post = blog_post;
			t.blog_user = request.user;
			t.viewing_user = request.session.user;
			t.admin_viewer = request.session.has_priv?(CoreModule, :editjournal) && request.user != request.session.user;
			t.total_pages = total_pages;
			t.current_page = page;
			t.show_delete_controls = ((t.admin_viewer || request.user.userid == request.session.user.userid) && blog_post.comments_count > 0);
			if(total_pages > 1)
				page_list = Paginator::Paginator.generate_page_list(page, total_pages, url / :users / request.user.username / :blog / blog_post.id, request.user.blog_skin[:primary_block_icon_color]);
				page_list.include_clear_element = true;
				t.paging_string = page_list.display();
			end
			print t.display();
		end
		
		def new_comment_view(post_id, parent_id)
			request.reply.headers['X-width'] = 0;
			
			if(request.user.blogskin > 0 && request.user.plus?())
				user_skin = Profile::UserSkin.find(:first, [request.user.userid, request.user.blogskin]);
				#user_skin = request.user.blog_skin;
				if(!user_skin.nil?())
					request.reply.headers['X-user-skin'] = user_skin.header();
				end
			end
			
			blog_post_obj = BlogPost.find(:first, [request.user.userid, post_id]);
			
			if(blog_post_obj.nil?())
				#spew error
			end
			
			parent_comment = BlogComment.find(:first, [request.user.userid, parent_id]);
			
			if(parent_comment.nil?())
				#error
			end
			
			t = Template.instance("blogs", "blog_comment_edit_view");
			
			t.blog_post = blog_post_obj;
			t.parent_comment = parent_comment;
			t.blog_user = request.user;
			t.viewing_user = request.session.user;
			
			print t.display();		
		end
		
		def add_comment(post_id, parent_id = 0)
			blog_post_obj = BlogPost.find(:first, [request.user.userid, post_id]);		
			comment_content = params['blog_comment_content', String, ""];
			
			result = BlogCommentsPageHandler.process_add_comment(request.session.user, blog_post_obj, parent_id, comment_content);
			if(!result.success)
				$site.memcache.set("user_blog_comment_post_error-#{request.session.user.userid}/#{request.user.userid}", result.error, 10);
			end
			
			site_redirect(url / request.user.username / :blog / post_id);
		end
		
		def dynamic_add_comment(post_id, parent_id = 0)
			blog_post_obj = BlogPost.find(:first, [request.user.userid, post_id]);		
			comment_content = params['blog_comment_content', String, ""];
			
			result = BlogCommentsPageHandler.process_add_comment(request.session.user, blog_post_obj, parent_id, comment_content);
		end
		
		def self.process_add_comment(posting_user, blog_post, parent_id, comment_content)
			blog_comment_post_result = BlogCommentPostResult.new();
			
			if(blog_post.nil?())
				blog_comment_post_result.success = false;
				blog_comment_post_result.error = "The blog post you're trying to reply to doesn't exist.";
				return blog_comment_post_result;
			end
			
			blog_user = blog_post.user;
			
			if(blog_comment_post_result.success && parent_id != 0)
				comment_parent = BlogComment.find(:first, [blog_user.userid, parent_id]);
				if(comment_parent.nil?())
					blog_comment_post_result.success = false;
					blog_comment_post_result.error = "The comment you're replying to doesn't exist.";
				end
			end
			
			if(blog_comment_post_result.success && comment_content.length <= 0)
				blog_comment_post_result.success = false;
				blog_comment_post_result.error = "Comment post had no content";
			end
			
			if(blog_comment_post_result.success)
				begin
					comment_content.spam_filter();
				rescue SpamFilterException
					blog_comment_post_result.success = false;
					blog_comment_post_result.error = $!.to_s();
				end
			end
			
			if(blog_comment_post_result.success && BlogCommentsPageHandler.hit_post_rate_limit(posting_user.userid))
				blog_comment_post_result.success = false;
				blog_comment_post_result.error = "You've sent too many comments too quickly";
			end

			if(blog_comment_post_result.success && posting_user.anonymous?())
				blog_comment_post_result.success = false;
				blog_comment_post_result.error = "Comment posts by anonymous users are not allowed";
			end

			if(blog_comment_post_result.success && blog_user.ignored?(posting_user))
				blog_comment_post_result.success = false;
				blog_comment_post_result.error = "You are ignored by #{blog_user.username} and are not allowed to post comments";
			end

			if(blog_comment_post_result.success && blog_user.friends_only?(:comments) && (!blog_user.friend?(posting_user) && blog_user.userid != posting_user.userid))
				blog_comment_post_result.success = false;
				blog_comment_post_result.error = "#{blog_user.username} only accepts comments from friends";
			end

			if(blog_comment_post_result.success && blog_user.ignore_section_by_age?(:comments) && (!blog_user.friend?(posting_user) && blog_user.userid != posting_user.userid) && 
					(posting_user.age < blog_user.defaultminage || posting_user.age > blog_user.defaultmaxage))	
				blog_comment_post_result.success = false;
				blog_comment_post_result.error = "#{blog_user.username} does not accept comments from users outside #{blog_user.possessive_pronoun} age range";
			end
			
			
			if(!blog_comment_post_result.success)
				return blog_comment_post_result;
			end
				
			comment_obj = BlogComment.new();
			
			comment_obj.id = BlogComment.get_seq_id(blog_user.userid);
			comment_obj.blogid = blog_post.id;
			comment_obj.bloguserid = blog_user.userid;
			comment_obj.userid = posting_user.userid;
			comment_obj.time = Time.now.to_i();
			comment_obj.msg = comment_content;
			
			if(!comment_parent.nil?())
				comment_obj.parentid = comment_parent.id;
				if(comment_parent.rootid > 0)
					comment_obj.rootid = comment_parent.rootid;
				else
					comment_obj.rootid = comment_parent.id;
				end
			else
				comment_obj.parentid = 0;
				comment_obj.rootid = 0;
			end
			
			comment_obj.store();
			
			notified_user_id = 0;
			comment_parent_id = 0;
			if(!comment_parent.nil?() && comment_parent.author.kind_of?(User))
			  if(comment_parent.author.userid != blog_post.userid)
   				BlogCommentsPageHandler.add_unread_reply_notification(blog_post, comment_parent.userid, comment_obj.id, comment_parent.id);
   				notified_user_id = comment_parent.userid;
   				comment_parent_id = comment_parent.id;
 				end
			end

			if(posting_user.userid != blog_post.userid && (notified_user_id == 0 || notified_user_id != blog_post.userid))
				BlogCommentsPageHandler.add_unread_reply_notification(blog_post, blog_post.userid, comment_obj.id, comment_parent_id);				
			end
			blog_comment_post_result.comment_id = comment_obj.id;
			
			return blog_comment_post_result;
		end
		
		def self.add_unread_reply_notification(blog_post, notified_user_id, comment_id, parent_comment_id = 0)
			comment_notify = BlogCommentUnreadNotification.new();
			
			comment_notify.userid = notified_user_id;
			comment_notify.bloguserid = blog_post.userid;
			comment_notify.blogid = blog_post.id;
			comment_notify.replytoid = parent_comment_id;
			comment_notify.commentid = comment_id;
			comment_notify.time = Time.now.to_i();
			
			comment_notify.store();
			
			$site.memcache.incr("weblog-newreplies-#{notified_user_id}-total");
		end
		
		# Returns true if the user hasn't hit the comments post rate, false if he/she has. The time limit is defined
		#  in seconds. If the user trips the rate limit the timeout becomes longer to better prevent spamming.
		def self.hit_post_rate_limit(user_id)
			initial_time_limit = 5;
			repeat_time_limit = 15;
			key = "user_blog_comments_post_rate_limit-#{user_id}";
			
			limit = $site.memcache.get(key);
			if(limit)
				$site.memcache.set(key, true, repeat_time_limit);
				return true;
			else
				$site.memcache.set(key, true, initial_time_limit);
				return false;
			end
		end
		
		def delete_comments(post_id)
			blog_post = BlogPost.find(:first, [request.user.userid, post_id]);
			
			if(blog_post.nil?())
				#error
			end
			
			valid_keys = Array.new();

			page = params["page", Integer, 0];
			comments_limit = BlogComment::COMMENTS_PER_PAGE;

			params.keys.each{|key|
				if(key =~ /^blog_comment_delete_(\d*)\:(\d*)$/)
					valid_keys << $~[2].to_i;
				end
			};
			valid_keys.compact!

			error_list = Hash.new();

			valid_keys.each{|key|
				delete_result = BlogCommentsPageHandler.process_delete_comment(request.user.userid, key, request.session.user);
			}

			num_pages = (request.user.comments_count.to_f/comments_limit).ceil();
			if(page > num_pages)
				page = num_pages;
			end

			site_redirect(url / request.user.username / :blog / post_id & {:page => page});
		end
		
		def quick_comment_delete(post_id, comment_id)
			blog_post = BlogPost.find(:first, [request.user.userid, post_id]);
			
			user_key = params['key', String, nil];
			
			if(!Authorization.instance.check_key(request.session.user.userid, user_key))
				#error
			else
				delete_result = BlogCommentsPageHandler.process_delete_comment(request.user.userid, comment_id, request.session.user);
			
				page = params["page", Integer, 0];
			end
			
			site_redirect(url / request.user.username / :blog / post_id & {:page => page});
		end

		def admin_delete_comments(post_id)			
			blog_post = BlogPost.find(:first, [request.user.userid, post_id]);
			
			if(blog_post.nil?())
				#error
			end
			
			valid_keys = Array.new();

			page = params["page", Integer, 0];
			comments_limit = BlogComment::COMMENTS_PER_PAGE;

			params.keys.each{|key|
				if(key =~ /^blog_comment_delete_(\d*)\:(\d*)$/)
					valid_keys << $~[2].to_i;
				end
			};
			valid_keys.compact!
			
			error_list = Hash.new();
			
			if(process_abuse_log_params(params, AbuseLog::ABUSE_ACTION_EDIT_BLOG_COMMENTS))
				AdminLog.log(request, "remove comments", "Removed comments (ids: #{valid_keys * ','}) from #{request.user.username}'s blog post (id: #{post_id})")

				valid_keys.each{|key|
					delete_result = BlogCommentsPageHandler.process_delete_comment(request.user.userid, key, request.session.user);
				}

				num_pages = (request.user.comments_count.to_f/comments_limit).ceil();
				if(page > num_pages)
					page = num_pages;
				end
			end

			site_redirect(url / request.user.username / :blog / post_id & {:page => page});
		end
		
		def self.process_delete_comment(blog_user_id, comment_id, deleting_user, admin_user = false)
			blog_comment_obj = BlogComment.find(:first, [blog_user_id.to_i(), comment_id.to_i()]);
=begin			
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
=end
			blog_comment_obj.deleted = true;
			blog_comment_obj.store();
			
			$site.memcache.delete(blog_comment_obj.post.comments_count.cache_key());
			
			# We need to do this because we aren't actually deleting the comment, just changing it's deleted status.
			# As such the code to invalidate these two relations doesn't get called.
			#$site.memcache.delete(comment_obj.user.first_five_comments.cache_key());
			#$site.memcache.delete(comment_obj.user.comments_count.cache_key());
			
			$log.info("Comment #{blog_comment_obj.userid}-#{blog_comment_obj.id} was deleted", :info, :admin);
			
			#return comment_delete_result;
		end
		
		def build_comment_tree(blog_post_obj)
			comments_list = blog_post_obj.comments;
			
			sorted_comments_list = comments_list.sort();
			
			sorted_comments_list.each{|comment|
				if(comment.rootid == 0)
					blog_post_obj.root_comments << comment;
				else
					root_comment = nil;
					blog_post_obj.root_comments.each{|temp_comment|
						if(temp_comment.id == comment.rootid)
							root_comment = temp_comment;
							break;
						end
					};
					
					if(root_comment.nil?())
						#well shit
					end
					if(attach_comment(root_comment, root_comment, comment))
						next;
					end
				end
			};
		end
		
		def attach_comment(root_comment, parent_comment, new_comment)
			#if the id's dont' match we're in the wrong branch of the tree
			if(root_comment.id != new_comment.rootid)
				return false;
			end
			
			if(parent_comment.id == new_comment.parentid)
				parent_comment.child_nodes << new_comment;
				new_comment.parent_node = parent_comment;
				parent_comment.increment_descendant_count();
				return true;
			end
			
			parent_comment.child_nodes.each{|comment|
				if(attach_comment(root_comment, comment, new_comment))
					return true;
				end
			};
						
			return false;
		end
		
		def mark_display_nodes(blog_post, starting_index, display_count)
			index = 0;
			finish_index = starting_index + display_count;
			
			blog_post.root_comments.each{|comment|
				#$log.info("index is: #{index}")
				if(index < starting_index)
					if(comment.descendant_node_count + index + 1 < starting_index)
						index = index + comment.descendant_node_count + 1;
					else
						result = mark_node(comment, starting_index - index, 0, display_count);
						index = index + result[0] + result[1];
						if(result[1] > 0)
							comment.descendant_displayed = true;
						end
					end
					next;
				end
				
				if(index < finish_index)
					found = index - finish_index + display_count;
					result = mark_node(comment, 0, found, display_count);
					
					index = index + result[1];
					if(result[1] > 0)
						comment.descendant_displayed = true;
					end
				else
					break;
				end
			};
		end
		
		def mark_node(comment_node, num_skip, num_marked, mark_limit)
			total_marked = num_marked;
			#$log.info("Looking at #{comment_node.id} it's parent_node is: #{comment_node.parentid} the args are: #{num_skip}, #{num_marked}, #{mark_limit}");
			if(num_skip > 0)
				remaining_skip = num_skip - 1;
				combined_info = [1, 0];
				comment_node.child_nodes.each{|node|
					result = mark_node(node, remaining_skip, total_marked, mark_limit);
					combined_info[0] = combined_info[0] + result[0];
					combined_info[1] = combined_info[1] + result[1];
					total_marked = total_marked + result[1];
					remaining_skip = num_skip - result[0];
				};
			else
				if(num_marked <= mark_limit)
					comment_node.displayed = true;
					combined_info = [0, 1];
					total_marked = total_marked + 1;
					comment_node.child_nodes.each{|node|
						if(total_marked <= mark_limit)
							result = mark_node(node, num_skip, total_marked, mark_limit);
							combined_info[0] = combined_info[0] + result[0];
							combined_info[1] = combined_info[1] + result[1];
							total_marked = total_marked + result[1];
						else
							break;
						end
					};
				end
			end
			
			if(combined_info[1] > 0)
				comment_node.descendant_displayed = true;
			end
			
			return combined_info;
		end
		

		def ignore_link(viewing_user=nil, blog_comment_author=nil, link_id=nil)
			t = Template.instance("blogs", "blog_comment_ignore_link")
			
			t.viewing_user = viewing_user || params.to_hash["viewing_user"]
			t.blog_comment_author = blog_comment_author || params.to_hash["blog_comment_author"]
			t.link_id = link_id || params.to_hash["link_id"]
			t.ignore_form_key = SecureForm.encrypt(t.viewing_user, "/Self/blog/#{t.viewing_user.ignored?(t.blog_comment_author) ? 'unignore' : 'ignore'}_user")
			
			puts t.display
		end
		
		def ignore_user(userid)
			request.session.user.ignore(userid);
			refresh_ignore_links(userid)
		end
		
		def unignore_user(userid)
			request.session.user.unignore(userid);
			refresh_ignore_links(userid)
		end
		
		def refresh_ignore_links(userid)
			links = params["link_ids", TypeSafeHash, Hash.new]
			links.each_pair(Integer) { |k,v| 
				if(v == userid)
					t = Template.instance("blogs", "blog_comment_ignore_link")
					
					ignore_link(request.session.user, User.find(:first, userid), k)
				end
			}
		end
		private :refresh_ignore_links
		
	end
end