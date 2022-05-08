lib_require :Blogs, 'blog_post', 'photo_blog', 'poll_blog', 'battle_blog', 'video_blog'

module Blogs
	class BlogCreation < PageHandler
		declare_handlers("blog") {
			area :Self
			access_level :IsUser

			page :GetRequest, :Full, :freeform_post, "new"
			page :GetRequest, :Full, :freeform_post, "new", "freeform"
			page :GetRequest, :Full, :photo_post, "new", "photo"
			page :GetRequest, :Full, :poll_post, "new", "poll"
			page :GetRequest, :Full, :video_post, "new", "video"
			page :GetRequest, :Full, :battle_post, "new", "battle"
			handle :PostRequest, :create_new_post, "new", "submit"

			access_level :IsUser, CoreModule, :editjournal
			page :GetRequest, :Full, :edit_post, "edit", input(Integer)
			handle :PostRequest, :update_post, "edit", input(Integer), "submit"

			handle :GetRequest, :preview_new_post, "new", "preview"

		}
		
		def edit_post(post_id)
			post = BlogPost.find([request.user.userid, post_id], :first, :promise)
			if (post.nil?)
				puts "Unable to find post."
				return
			end
			
			case post.typeid
			when 0
				freeform_post(post_id)
			when Blogs::PhotoBlog.typeid
				photo_post(post_id)
			when Blogs::VideoBlog.typeid
				video_post(post_id)
			else
				puts "You can't edit this post."
			end
		end
		
		def update_post(post_id)
			if(!valid_post?())
				site_redirect(url / :blog / :edit / post_id)
			end			

			if(request.impersonation?)
				if(!process_abuse_log_params(params, AbuseLog::ABUSE_ACTION_BLOG_EDIT))
					site_redirect(url / :blog / :edit / post_id)
				end
			end
			
			post = BlogPost.find(:first, :promise, [request.user.userid, post_id])
			if (post.nil?)
				site_redirect(url / :blog / :new)
			end

			post = BlogPost.build!(request.user, params, post)

			if(request.impersonation?)
				AdminLog.log(request, "blog post edit", "Edited blog post (id: #{post.id}) in #{request.user.username}'s blog");
				site_redirect(url / :blog & {:page => page}, [:User, request.user]);
			end


			# If it's a private post we don't need to update friends's unread post count or friends entry lists
			if(post.visibility > 0)
				reset_friends_sorted_lists(request.user, post.visibility);
				update_friends_unread_list(request.user, post.visibility);
			end

			update_blog_menu_access_visibility(request.user);
			$site.memcache.delete("user_blog_minimal_post_info-#{request.user.userid}");
			$site.memcache.incr("user_blog_post_count-#{request.user.userid}");

			site_redirect(url / :blog);
		end
		
		def freeform_post(post_id = nil)
			request.reply.headers['X-width'] = 0

			t = Template::instance('blogs', 'blog_new_entry_freeform')
			t.freeform = "current"
			t.blog_post_visibility = request.user.blog_profile.defaultvisibility
			t.blog_post_allow_comments = request.user.blog_profile.allowcomments
			t.action = "new"
			
			if (!post_id.nil?)
				t.action = "edit/#{post_id}"
				blog_post = BlogPost.find(:first, [request.user.userid, post_id], :promise);
				t.title = blog_post.title
				t.caption = blog_post.msg
				t.blog_post_allow_comments = blog_post.allowcomments
				t.blog_post_visibility = blog_post.visibility
				
				t.show_reset = true
			end			
			
			#we want to show help if there isn't a blog post of this type
			t.show_help = BlogPost.find(:first, request.user.userid, :conditions => ["typeid = ?", 0], :limit=>1).nil?
			t.user = request.user
			t.admin = request.impersonation?
			
			puts t.display
		end
		
		def photo_post(post_id = nil)
			request.reply.headers['X-width'] = 0

			t = Template::instance('blogs', 'blog_new_entry_photo')
			t.photo = "current"
			t.action = "new"
			t.blog_post_visibility = request.user.blog_profile.defaultvisibility
			t.blog_post_allow_comments = request.user.blog_profile.allowcomments
			t.session = request.session.encrypt
			t.user = request.user
			t.link = "http://"
			t.admin = request.impersonation?
			t.size = :size_original
			t.align = :center

			#we want to show help if there isn't a blog post of this type
			t.show_help = PhotoBlog.find(:first, request.user.userid, :limit => 1).nil?

			if (!post_id.nil?)
				t.action = "edit/#{post_id}"
				blog_post = BlogPost.find(:first, [request.user.userid, post_id], :promise);
				
				t.title = blog_post.title
				t.blog_post_visibility = blog_post.visibility
				t.blog_post_allow_comments = blog_post.allowcomments
				t.caption = blog_post.msg

				t.link = blog_post.extra_content.link
				t.size = blog_post.extra_content.size
				t.align = blog_post.extra_content.align

				t.show_reset = true
			end			

			puts t.display
		end

		def video_post(post_id = nil)
			request.reply.headers['X-width'] = 0

			t = Template::instance('blogs', 'blog_new_entry_video')
			t.video = "current"
			t.action = "new"
			t.blog_post_visibility = request.user.blog_profile.defaultvisibility
			t.blog_post_allow_comments = request.user.blog_profile.allowcomments
			t.user = request.user
			t.admin = request.impersonation?
			
			#we want to show help if there isn't a blog post of this type
			t.show_help = VideoBlog.find(:first, request.user.userid, :limit=>1).nil?
	
			if (!post_id.nil?)
				t.action = "edit/#{post_id}"
				blog_post = BlogPost.find(:first, [request.user.userid, post_id], :promise);
				
				t.title = blog_post.title
				t.blog_post_visibility = blog_post.visibility
				t.blog_post_allow_comments = blog_post.allowcomments
				t.caption = blog_post.msg
				
				t.embed_code = blog_post.extra_content.embed
				t.show_reset = true
				
			end			
	
			puts t.display
		end
		
		def battle_post(post_id = nil)
			request.reply.headers['X-width'] = 0

			t = Template::instance('blogs', 'blog_new_entry_battle')
			t.action = "new"
			t.battle = "current"
			t.blog_post_visibility = request.user.blog_profile.defaultvisibility
			t.blog_post_allow_comments = request.user.blog_profile.allowcomments
			t.session = request.session.encrypt
			t.user = request.user
			t.admin = request.impersonation?
	
			#we want to show help if there isn't a blog post of this type
			t.show_help = BattleBlog.find(:first, request.user.userid).nil?
	
			puts t.display
		end
		
		def poll_post(post_id = nil)
			request.reply.headers['X-width'] = 0
			t = Template::instance('blogs', 'blog_new_entry_poll')
			t.action = "new"
			t.poll = "current"
			t.session = request.session.encrypt
			t.user = request.user
			t.admin = request.impersonation?
			t.size = :size_original
			t.align = :center

			#we want to show help if there isn't a blog post of this type
			t.show_help = PollBlog.find(:first, request.user.userid, :limit=>1).nil?

			puts t.display
		end
		
		def create_new_post()
			if(!valid_post?())
				site_redirect(url / :blog / :post / :new)
			end			
			
			if(request.impersonation?)
				if(!process_abuse_log_params(params, AbuseLog::ABUSE_ACTION_BLOG_EDIT))
					site_redirect(url / :blog / :post / :new)
				end
			end
			
			post = BlogPost.build!(request.user, params)

			if(request.impersonation?)
				AdminLog.log(request, "blog post edit", "Edited blog post (id: #{post.id}) in #{request.user.username}'s blog");
				site_redirect(url / :blog & {:page => page}, [:User, request.user]);
			end
			

			# If it's a private post we don't need to update friends's unread post count or friends entry lists
			if(post.visibility > 0)
				reset_friends_sorted_lists(request.user, post.visibility);
				update_friends_unread_list(request.user, post.visibility);
			end
			
			update_blog_menu_access_visibility(request.user);
			$site.memcache.delete("user_blog_minimal_post_info-#{request.user.userid}");
			$site.memcache.incr("user_blog_post_count-#{request.user.userid}");
			
			if(!request.user.user_task_list.empty?())
				request.user.user_task_list.each{|task|
					if(task.taskid == 3)
						task.delete();
					end
				};
			end
			
			site_redirect(url / :blog);
		end

		def preview_new_post
			if(!valid_post?())
				puts "Invalid"
			end			
			post = BlogPost.preview(request.user, params)
			t = Template::instance("blogs", "blog_preview")
			t.blog_post = post
			t.request = request
			puts t.display
		end

		#assisting functions##########################################################
		def valid_post?()
			result_obj = BlogPostResult.new();
			
			title = params["blog_post_title", String, ""];
			content = params["blog_post_content", String, ""];
			post_visibility = params['blog_post_visibility', Integer, 4];
			post_comments = params['blog_post_comments', Boolean, false];
			post_reset_timestamp = params["blog_post_reset_timestamp", Boolean, false];
			
			if(title.strip() == "")
				result_obj.error_list << "a title";
				result_obj.success = false;
			elsif(title.length > 128)
				result_obj.error_list << "a shorter title"
				result_obj.success = false;
			end
			
			if(result_obj.success)
				$site.memcache.delete("error-blog_post-#{request.session.user.userid}");
			else
				result_obj.content = content;
				result_obj.title = title;
				result_obj.visibility = post_visibility;
				result_obj.allow_comments = post_comments;
				result_obj.reset_timestamp = post_reset_timestamp;
				
				$site.memcache.set("error-blog_post-#{request.session.user.userid}", result_obj, 10);
			end
				
			return result_obj.success;
		end
		private :valid_post?

		def update_blog_menu_access_visibility(blog_user)
			result = BlogPost.db.query("SELECT MAX(visibility) AS visibility FROM blog WHERE userid = #", blog_user.userid);
			
			max_visibility = BlogVisibility.instance.visibility_list[blog_user.blogsmenuaccess];
			result.each{|row|
				max_visibility = row['visibility'];
			};
			
			if(!max_visibility.nil?() && max_visibility.to_i() != BlogVisibility.instance.visibility_list[blog_user.blogsmenuaccess])
				blog_user.blogsmenuaccess = BlogVisibility.instance.inverse_visibility_list[max_visibility];
				blog_user.store();
			end
		end
		
		def reset_friends_sorted_lists(blog_user, visibility = 4)
			update_id_list = [];
			if(visibility < 3)
				blog_user.friends_ids.each{|friend|
					if(friend[1] == blog_user.userid)
						next;
					end
					
					if(blog_user.friends_of_ids.index([friend[1], blog_user.userid]))
						update_id_list << friend[1];
					end
				};
			else
				blog_user.friends_of_ids.each{|friend|
					if(friend[0] == blog_user.userid)
						next;
					end
					
					update_id_list << friend[0];
				};
			end
			
			update_id_list.each{|friend_id|
				$site.memcache.delete("user_blog_friends_post_info-#{friend_id}");
			};
		end
		
		def update_friends_unread_list(blog_user, visibility)
			update_id_list = [];
			if(visibility < 3)
				blog_user.friends_ids.each{|friend|
					if(friend[1] == blog_user.userid)
						next;
					end
					
					if(blog_user.friends_of_ids.index([friend[1], blog_user.userid]))
						update_id_list << friend[1];
					end
				};
			else
				blog_user.friends_of_ids.each{|friend|
					if(friend[0] == blog_user.userid)
						next;
					end
					
					update_id_list << friend[0];
				};
			end
			
			if(!update_id_list.empty?())
				BlogPost.db.query("UPDATE `bloglastreadfriends` SET postcount = postcount + 1 WHERE userid IN #", update_id_list);
			end
			
			update_id_list.each{|friend_id|
				$site.memcache.delete("Blogs::BlogLastReadFriends-#{friend_id}");
				$site.memcache.incr("weblog-lastread-#{friend_id}-postcount");
			};
			
		end


	end
end