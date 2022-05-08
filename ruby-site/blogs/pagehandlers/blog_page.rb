lib_require :Blogs, "blog_user";
lib_require :Blogs, "blog_post";
lib_require :Blogs, "blog_comment";
lib_require :Blogs, "blog_view";
lib_require :Blogs, "blog_navigation";
lib_require :Blogs, "blog_profile";
lib_require :Blogs, "blog_comment_unread_notification";
lib_require :Nexoskel, "abuse_log_entry_processing";
lib_require :Core, "admin_log";

lib_want :Blogs, "video_blog";
lib_want :Profile, "user_skin";

module Blogs
	class BlogPageHandler < PageHandler
		declare_handlers("blog") {
			area :User
			access_level :Any
			page :GetRequest, :Full, :view_blog;
			page :GetRequest, :Full, :view_blog_post, input(/(\d*)-*.*/);
			page :GetRequest, :Full, :view_select_entries, "view", "entries";
			page :GetRequest, :Full, :view_friend_entries, "friends";
			
			handle	:PostRequest, :update_views, "views", "update";

			handle	:PostRequest, :blogpoll_skip, input(Integer), "poll_skip";
			handle	:PostRequest, :blogpoll_vote, input(Integer), "poll_vote";

			handle	:PostRequest, :blogbattle_skip, input(Integer),
			 	"battle_skip";
			handle	:PostRequest, :blogbattle_vote, input(Integer),
				"battle_vote";

			area :Self
			site_redirect(:GetRequest) { ['/blog', [:User, PageRequest.current.session.user]] };
			
			access_level :IsUser, CoreModule, :editjournal
			handle :PostRequest, :delete_post, "post", "delete";
									
			access_level :IsUser
			page :GetRequest, :Full, :new_post_view, "post", "new";
			page :GetRequest, :Full, :view_new_replies, "new", "replies";
			
			handle :PostRequest, :change_permissions, "post", "change_permissions";
			handle :PostRequest, :show_details, "navigation", input(Integer), input(Integer), "show_details";
			handle :PostRequest, :hide_details, "navigation", input(Integer), input(Integer), "hide_details";
		}
		
		include AbuseLogEntryProcessing

		#
		# Creates the blog page for the current user
		#
		def view_blog()			
			request.reply.headers['X-width'] = 0;

			apply_user_skin!
			
			post_count = request.user.post_count			
			
			num_pages = 0;
			viewable_post_count = 0;
			admin_viewer = request.session.has_priv?(CoreModule, :editjournal) && (request.user != request.session.user);
			filter_type = params['filter_type', Integer]
			
			#
			# get some minimal info about the blog posts that the viewer can see.
			#
			if (post_count > 0)
				#This is passed through to the template which does an ajax request back to mark the blog as viewed (I think) --Nathan
				request_blog_view_info = BlogView.view(request.session.user, request.user);
				
				viewer_visibility_level = BlogVisibility.determine_visibility_level(request.user, request.session.user, admin_viewer);
				
				conditions = []
				if (filter_type.nil?)
					conditions = ["visibility >= ?", viewer_visibility_level]
				else
					conditions = ["typeid = ? && visibility >= ?", filter_type, viewer_visibility_level]
				end
				
				# Note that really this should just be user.post_count, but instead it's filtered_post_count see the comment in blog_user.rb NEX-1363
				viewable_post_count = request.user.filtered_post_count(:conditions => conditions)
				num_pages = (viewable_post_count.to_f/BlogPost::POSTS_PER_PAGE).ceil()
			end

			#
			# If the user we're looking at doesn't have any posts or no posts visible to the viewer then
			# display the "no posts" template.
			#
			if ( (num_pages == 0) )

				t = Template.instance("blogs", "blog_no_posts");
				t.quick_post = true
				
				t.post_count = post_count;
				t.viewable_post_count = viewable_post_count;
				t.num_pages = num_pages;
				
				t.blog_user = request.user;
				t.owner_view = request.user.userid == request.session.user.userid;
				t.tab = :blog;
				t.filter_type = filter_type;
				if ( t.owner_view && (request.user.blog_friends_last_read.postcount > 0) )
					t.friends_unread_post_count = request.user.blog_friends_last_read.postcount;
				end
				
				print t.display();
				
				return;
			end
			
			# Redirect the user if the page param is out of range.
			page = params['page', Integer, 0];
			if(page < 0)
				site_redirect(url / request.user.username / :blog);
			elsif( (page >= num_pages) && (num_pages > 0) )
				site_redirect(url / request.user.username / :blog & {:page => num_pages-1});
			end
			
			if (filter_type)
				# Get the chunk of posts that we want to display for this page.
				blog_post_list = request.user.blog_posts(:conditions => ["typeid = ? && visibility >= ?", filter_type, viewer_visibility_level], :page => page + 1, :limit => BlogPost::POSTS_PER_PAGE)
			else
				blog_post_list = request.user.blog_posts(:conditions => ["visibility >= ?", viewer_visibility_level], :page => page + 1, :limit => BlogPost::POSTS_PER_PAGE)
			end
			
			# Fill in the template.
			t = Template.instance("blogs", "blog_view");
			t.tab = :blog;
			t.blog_user = request.user;
			t.viewing_user = request.session.user;
			t.owner_view = (request.user.userid == request.session.user.userid);
			t.quick_post = t.owner_view && request.user.blog_profile.showquickpost
			t.request_blog_view_info = request_blog_view_info;
			t.blog_views = BlogView.views(request.user, :display);
			t.blog_post_list = blog_post_list
			t.viewer_navigation_form_key = SecureForm.encrypt(request.session.user, "/Self/blog/navigation");
			t.request = request;
			t.post_count = post_count;
			t.filter_type = filter_type;
			
			# Don't try to find blog navigation items if we're not logged in
			if(!request.session.anonymous?)
				navigation_list = request.session.user.collapsed_blog_posts(:conditions => ["bloguserid = ?", request.user.userid])
				t.navigation_list = navigation_list.map { |navigation| [navigation.bloguserid, navigation.postid] }				
			else
				t.navigation_list = Array.new
			end

			# Get the number of unread blog posts from the user's friends
			if( t.owner_view && (request.user.blog_friends_last_read.postcount > 0) )
				t.friends_unread_post_count = request.user.blog_friends_last_read.postcount;
			end
			
			# Output the delete_url and matching form key if the viewing user is the blog owner.
			if (request.user.userid == request.session.user.userid || admin_viewer)
				t.delete_url = url / :my / :blog / :post / :delete;
				t.delete_form_key = SecureForm.encrypt(request.session.user, "/Self/blog/post/delete");
				t.permissions_form_key = SecureForm.encrypt(request.session.user, "/Self/blog/post/change_permissions");
			end
			
			t.current_page = page;

			if(num_pages < 1)
				num_pages = 1;
			end
			if(num_pages > 1)
				page_list = Paginator::Paginator.generate_page_list(page, num_pages, url / :users / request.user.username / :blog, request.user.blog_skin[:primary_block_icon_color], {:filter_type => filter_type});
				page_list.include_clear_element = true;
				t.paging_string = page_list.display();
			end
			t.admin_view = admin_viewer;
			t.anon = request.session.anonymous?
			
			print t.display();
		end

		def skip(blogid, tid)
			userpoll_vote = Polls::User::UserPollVote.find(:first,
				[request.user.userid, tid, blogid,
				 request.session.user.userid])
			# Ensure the user hasn't already voted.  If they have, don't record
			# the vote, just show the results
			if userpoll_vote == nil
				# Vote with null answer
				userpoll_vote = Polls::User::UserPollVote.new()
				userpoll_vote.userid = request.user.userid
				userpoll_vote.typeid = tid
				userpoll_vote.parentid = blogid
				userpoll_vote.voterid = request.session.user.userid
				userpoll_vote.answer = nil
				userpoll_vote.time = Time.now.to_i()
				userpoll_vote.store
			end

			# Load the results
			if (params['ajax', String, 'true'] == 'true')
				t = Template.instance('blogs', 'blog_post')
				t.blog_post = BlogPost.find(:first, request.user.userid, blogid)
				t.request = request
				t.single_post = true
				t.owner_view = request
				t.blog_user = request.user
				t.viewing_user = request.session.user
				t.owner_view = request.user.userid == request.session.user.userid
				t.admin_view = request.session.has_priv?(CoreModule, :editjournal) && request.user != request.session.user
				print t.display
			end
		end
			
		def blogpoll_skip(blogid)
			skip(blogid, PollBlog::typeid)			
		end
		
		def blogbattle_skip(blogid)
			skip(blogid, BattleBlog::typeid)
		end
		
		def vote(blogid, tid)
			raise "Anonymous users cannot vote" if request.session.anonymous?
			userpoll_vote = Polls::User::UserPollVote.find(:first,
				[request.user.userid, tid, blogid,
				 request.session.user.userid])
			# Ensure the user hasn't already voted.  If they have, don't record
			# the vote, just show the results
			if userpoll_vote == nil
				vote = params["vote_#{blogid}", String, '-1'].to_i
				raise "Invalid vote value" if (vote < 1) || (vote > 10)
				
				userpoll_answer = Polls::User::UserPollAnswer.find(:first,
					[request.user.userid, tid, blogid, vote])
				raise "Invalid vote value" if userpoll_answer == nil
				
				userpoll_question = Polls::User::UserPollQuestion.find(:first,
			 		[request.user.userid, tid, blogid])
				raise "Invalid question" if userpoll_question == nil
				
				# Vote with selected answer
				userpoll_vote = Polls::User::UserPollVote.new()
				userpoll_vote.userid = request.user.userid
				userpoll_vote.typeid = tid
				userpoll_vote.parentid = blogid
				userpoll_vote.voterid = request.session.user.userid
				userpoll_vote.answer = vote
				userpoll_vote.time = Time.now.to_i()
				userpoll_vote.store

				userpoll_answer.incr_vote()
				userpoll_question.incr_vote()
			end
			
			# Load the results
			if (params['ajax', String, 'true'] == 'true')
				t = Template.instance('blogs', 'blog_post')
				t.blog_post = BlogPost.find(:first, request.user.userid, blogid)
				t.request = request
				t.single_post = true
				t.owner_view = request
				t.blog_user = request.user
				t.viewing_user = request.session.user
				t.owner_view = request.user.userid == request.session.user.userid
				t.admin_view = request.session.has_priv?(CoreModule, :editjournal) && request.user != request.session.user
				print t.display
			end
		end
		
		def blogpoll_vote(blogid)
			vote(blogid, PollBlog::typeid)
		end
		
		def blogbattle_vote(blogid)
			vote(blogid, BattleBlog::typeid)
		end
		
		def update_views()
			user_id = params["userid", Integer];
			anon = params["anon", Integer];
			view_time = params["time", Integer];
			key = params["key", String];
			
			if(user_id != request.user.userid)
				return;
			end
			
			cur_time = Time.now.to_i();
			if(view_time <= cur_time && view_time + 60 > cur_time && ProfileView.check_key(key, user_id, anon, view_time))
				BlogView.increment_views(request.session.user, request.user, anon);
			end
		end
		

		def view_friend_entries()
			request.reply.headers['X-width'] = 0;
			
			if(request.user.blogskin > 0 && request.user.plus?())
				user_skin = Profile::UserSkin.find(:first, [request.user.userid, request.user.blogskin]);
				#user_skin = request.user.blog_skin;
				if(!user_skin.nil?())
					request.reply.headers['X-user-skin'] = user_skin.header();
				end
			end
			
			last = params["last", String, nil];
			first = params["first", String, nil];
			
			if(last.nil?() && first.nil?() && request.user == request.session.user)
				request.user.blog_friends_last_read.readtime = Time.now.to_i();
				request.user.blog_friends_last_read.postcount = 0;
				
				request.user.blog_friends_last_read.store();
				
				$site.memcache.delete("weblog-lastread-#{request.user.userid}-postcount");
				$site.memcache.delete("weblog-lastread-#{request.user.userid}-readtime");
			end
			
			last_id_pair = nil;
			first_id_pair = nil;
			first_page = false;
			if(!last.nil?())
				last_parts = last.split(":");
				if(last_parts.length != 2)
					#something is wrong
				else
					last_id_pair = [last_parts[0].to_i(), last_parts[1].to_i()];
				end
			elsif(!first.nil?())
				first_parts = first.split(":");
				if(first_parts.length != 2)
					#something is wrong
				else
					first_id_pair = [first_parts[0].to_i(), first_parts[1].to_i()];
				end
			else
				first_page = true;
			end
			
			sorted_list = request.user.friends_sorted_raw_blog_post_data
			
			display_list = [];
			user_visibility_list = {};
			
			admin_viewer = request.session.has_priv?(CoreModule, :editjournal);
			completed_list = true;
			
			if(!last_id_pair.nil?() || first_page)
				add_posts = false;
				if(first_page)
					add_posts = true;
				end
				
				#get the users all together prior to this
				sorted_list.each{|post_info|
					if(!add_posts && post_info.userid == last_id_pair[0] && post_info.id == last_id_pair[1])
						add_posts = true;
						next;
					elsif(!add_posts)
						next;
					end
					
					user_visibility = user_visibility_list[post_info.userid.to_s()];
					if(user_visibility.nil?())
						if request.session.anonymous?()
							user_visibility = 4
						else
							user = User.get_by_id(post_info.userid.to_i());
							if(!user.nil?())
								user_visibility =
									BlogVisibility.determine_visibility_level(
										user, request.session.user,
										admin_viewer);
							else
								user_visibility = 4;
							end
						end
						user_visibility_list[post_info.userid.to_s()] =
						 	user_visibility;
					end
					
					if(post_info.visibility >= user_visibility)
						display_list << [post_info.userid, post_info.id];
					end
				
					if(display_list.length > 25)
						completed_list = false;
						break;
					end
				};
			else
				starting_id = sorted_list.length() -1;
				sorted_list.each_index{|i|
					temp = sorted_list[i];
					if(temp.userid == first_id_pair[0] && temp.id == first_id_pair[1])
						starting_id = i;
						break;
					end
				};
				
				(0..starting_id).each{|i|
					post_info = sorted_list[starting_id - i];
					
					user_visibility = user_visibility_list[post_info.userid.to_s()];
					if(user_visibility.nil?())
						if request.session.anonymous?()
							user_visibility = 4
						else
							user = User.get_by_id(post_info.userid.to_i());
							if(!user.nil?())
								user_visibility =
									BlogVisibility.determine_visibility_level(
										user, request.session.user,
										admin_viewer);
							else
								user_visibility = 4;
							end
						end
						user_visibility_list[post_info.userid.to_s()] =
						 	user_visibility;
					end
					
					if(post_info.visibility >= user_visibility)
						display_list.insert(0, [post_info.userid, post_info.id]);
					end
				
					if(display_list.length > 25)
						completed_list = false;
						break;
					end
				};
			end
			
			if(display_list.length > 25)
				display_list = display_list.slice(0, 25);
			else
				completed_list = true;
			end
			
			
			blog_post_list = [];
			if(!display_list.empty?())
				blog_post_list = BlogPost.find(*display_list);
			end
			
			blog_post_list.each{|post| post.comments_count};
			
			t = Template.instance("blogs", "blog_friends_view");
			
			t.blog_post_list = blog_post_list.sort();			
			
			if(!sorted_list.last.nil?() && !display_list.last.nil?() && sorted_list.last.userid == display_list.last[0] && sorted_list.last.id == display_list.last[1] && !completed_list)
				completed_list = true;
			end
			
			t.icon_color = request.user.blog_skin[:primary_block_icon_color];
			if(!t.blog_post_list.empty?() && (!completed_list || !first.nil?()))
				t.show_next_page = true;
				t.next_page_get_param = "#{t.blog_post_list.last.userid}:#{t.blog_post_list.last.id}";
			end
			
			# If the first blog post displayed is the first blog post in the sorted_list, then we're back on the first page
			# and should not show a link to go to the previous page
			if(!t.blog_post_list.empty?() && (!last.nil?() || (!first.nil?() && !completed_list && 
					!(sorted_list.first.userid == t.blog_post_list.first.userid && sorted_list.first.id == t.blog_post_list.first.id))))
				t.show_prev_page = true;
				t.prev_page_get_param = "#{t.blog_post_list.first.userid}:#{t.blog_post_list.first.id}";
			end
			
			t.tab = :friends;
			t.blog_user = request.user;
			t.viewing_user = request.session.user;
			t.viewer_navigation_form_key = SecureForm.encrypt(request.session.user, "/Self/blog/navigation");
			blog_nav_ids = [];
			display_list.each{|id_list|
				blog_nav_ids << [request.session.user.userid, id_list[0], id_list[1]];
			};
			
			if(!request.session.anonymous?)
				t.navigation_list = BlogNavigation.find(*blog_nav_ids).map { |navigation| [navigation.bloguserid, navigation.postid] }
			else
				t.navigation_list = Array.new
			end
			
			print t.display();
		end
		

		def view_blog_post(post_id)
			# Note: We never actually use the title part of the id. It's simply there to allow
			# more descriptive blog post links, where -the-blog-post-title would come after
			# the id. We still use only the id to retrieve the correct blog post and throw
			# the title part away. This regular expression match should *never* fail because
			# there should always be a numerical ID part of the string even if there is no
			# description text.
			post_id = post_id[1].to_i

			request.reply.headers['X-width'] = 0;
			admin_viewer = request.session.has_priv?(CoreModule, :editjournal) && request.user != request.session.user;
			
			if(request.user.blogskin > 0 && request.user.plus?())
				user_skin = Profile::UserSkin.find(:first, [request.user.userid, request.user.blogskin]);
				#user_skin = request.user.blog_skin;
				if(!user_skin.nil?())
					request.reply.headers['X-user-skin'] = user_skin.header();
				end
			end
			
			blog_post = BlogPost.find(:first, [request.user.userid, post_id]);
			
			if(blog_post.nil?())
				site_redirect(url / request.user.username / :blog);
			end
			
			if(!BlogVisibility.visible?(blog_post.visibility, request.user, request.session.user) && (!admin_viewer || admin_viewer && blog_post.visibility == 0))
				puts "<h1>Private Entry</h1>"
				return
			end
			
			request_blog_view_info = BlogView.view(request.session.user, request.user);
			
			t = Template.instance("blogs", "individual_blog_post_view");
			
			t.tab = :blog;
			t.blog_user = request.user;
			t.viewing_user = request.session.user;
			t.blog_post = blog_post;
			t.admin_view = admin_viewer;
			t.single_post = true;
			t.owner_view = request.user.userid == request.session.user.userid;
			t.request_blog_view_info = request_blog_view_info;
			t.blog_views = BlogView.views(request.user, :display);
			if(t.owner_view && request.user.blog_friends_last_read.postcount > 0)
				t.friends_unread_post_count = request.user.blog_friends_last_read.postcount;
			end
			t.session = request.session
			
			print t.display();
		end
		
		#
		# 
		#
		def view_new_replies()
			request.reply.headers['X-width'] = 0;
			
			if(request.user.blogskin > 0 && request.user.plus?())
				user_skin = Profile::UserSkin.find(:first, [request.user.userid, request.user.blogskin]);
				#user_skin = request.user.blog_skin;
				if(!user_skin.nil?())
					request.reply.headers['X-user-skin'] = user_skin.header();
				end
			end
			
			if(request.user.blog_unread_replies_count < 1)
				site_redirect(url / :blog);
			end
			
			initial_reply = request.user.blog_unread_replies.first;
			
			blog_post = BlogPost.find(:first, [initial_reply.bloguserid, initial_reply.blogid]);
			
			display_list = [initial_reply];
						
			request.user.blog_unread_replies.each{|reply|
				if(reply == initial_reply)
					next;
				elsif(reply.bloguserid == initial_reply.bloguserid && reply.blogid == initial_reply.blogid)
					display_list << reply;
				end
			};
			
			display_id_list = [];
			
			display_list.each{|reply|
				if(reply.replytoid > 0)
					display_id_list << [reply.bloguserid, reply.replytoid];
				end
				display_id_list << [reply.bloguserid, reply.commentid];
			};
			
			display_id_list.uniq!();
			
			comment_list = BlogComment.find(*display_id_list);
			
			sorted_comment_list = comment_list.sort{|x,y| x.time <=> y.time};
			
			sorted_comment_list.each{|comment|
				if(comment.parentid == 0)
					blog_post.root_comments << comment;
				else
					found_parent = false;
					sorted_comment_list.each{|parent_comment|
						if(parent_comment.id == comment.parentid)
							parent_comment.child_nodes << comment;
							parent_comment.descendant_displayed = true;
							found_parent = true;
						end
					};
					if(!found_parent)
						blog_post.root_comments << comment;
					end
				end
				
				comment.displayed = true;
			};
			
			display_list.each{|reply|
				reply.delete();
			};
			
			old_count = $site.memcache.get("weblog-newreplies-#{request.user.userid}-total");
			
			new_count = old_count.to_i() - display_list.length;
			
			if(new_count > 0)
				$site.memcache.set("weblog-newreplies-#{request.user.userid}-total", new_count, 24*60*60);
			else
				$site.memcache.delete("weblog-newreplies-#{request.user.userid}-total");
			end
			
			if(blog_post.nil?())
				site_redirect(url / :blog / :new / :replies);
			end
			
			t = Template.instance("blogs", "blog_new_reply");
			
			t.blog_post = blog_post;
			t.blog_user = blog_post.user;
			t.viewing_user = request.session.user;
			t.owner_view = blog_post.userid == request.session.user.userid;
			t.blog_views = BlogView.views(blog_post.user, :display);
			t.show_delete_controls = t.owner_view;
			t.tab = :blog;
			t.single_post = true;
			t.reply_form_key = SecureForm.encrypt(request.session.user, "/User/blog/#{blog_post.id}/comment");
			
			print t.display();
		end
		
		#
		# 
		#
		def new_post_view()			
			site_redirect(url/ :blog / :new / :freeform);
		end
		
		#
		# 
		#
		def edit_post(post_id)

			# These two params are used to redirect the user back to the page they were on before they started editing a post when they're done editing the post.
			page = params["page", Integer, nil];
			single_post = params["single_post", Boolean, false];
			
			blog_post = BlogPost.find(:first, [request.user.userid, post_id]);
			
			#if the post doesn't exist, just bail
			if(blog_post.nil?())
				site_redirect(url / :blog);
			end

			site_redirect( url / :blog / :edit / blog_post.blog_type / post_id & {"page" => page, "single_post" => single_post} )
		end
		
	
		#
		# Create a page with only the given entries.  Only used from the calendar.  Entries are specified as a param.
		#
		def view_select_entries()
			request.reply.headers['X-width'] = 0;
			
			if(request.user.blogskin > 0 && request.user.plus?())
				user_skin = Profile::UserSkin.find(:first, [request.user.userid, request.user.blogskin]);
				#user_skin = request.user.blog_skin;
				if(!user_skin.nil?())
					request.reply.headers['X-user-skin'] = user_skin.header();
				end
			end
			
			admin_viewer = request.session.has_priv?(CoreModule, :editjournal) && request.user != request.session.user;
			
			blog_ids = params["id", String, ""];
			
			blog_id_list = blog_ids.split(",");
			
			if(blog_id_list.empty?)
				site_redirect(url / request.user.username / :blog);
			elsif(blog_id_list.length == 1)
				site_redirect(url / request.user.username / :blog / blog_id_list.first);
			end
			
			request_blog_view_info = BlogView.view(request.session.user, request.user);
			
			search_id_pairs = Array.new();
			
			blog_id_list.each{|id| search_id_pairs << [request.user.userid, id.to_i()]};
			
			blog_post_list = BlogPost.find(*search_id_pairs);
			
			t = Template.instance("blogs", "blog_view");
			
			t.tab = :blog;
			t.blog_user = request.user;
			t.viewing_user = request.session.user;
			t.owner_view = request.user.userid == request.session.user.userid;
			t.request_blog_view_info = request_blog_view_info;
			t.blog_views = BlogView.views(request.user, :display);
			
			t.viewer_navigation_form_key = SecureForm.encrypt(request.session.user, "/Self/blog/navigation");
			blog_nav_ids = [];
			search_id_pairs.each{|id_list|
				blog_nav_ids << [request.session.user.userid, id_list[0], id_list[1]];
			};
			
			if(!request.session.anonymous?)
				t.navigation_list = BlogNavigation.find(*blog_nav_ids).map { |navigation| [navigation.bloguserid, navigation.postid] }
			else
				t.navigation_list = Array.new
			end
			
			if(t.owner_view && request.user.blog_friends_last_read.postcount > 0)
				t.friends_unread_post_count = request.user.blog_friends_last_read.postcount;
			end
			
			if(request.user.userid == request.session.user.userid || admin_viewer)
				t.delete_url = url / :my / :blog / :post / :delete;
				t.delete_form_key = SecureForm.encrypt(request.user, "/Self/blog/post/delete");
				t.permissions_form_key = SecureForm.encrypt(request.user, "/Self/blog/post/change_permissions");
			end
			
			t.blog_post_list = blog_post_list;
			
			print t.display();
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
		
		def delete_post()
			post_hash = params["blog_post_select", TypeSafeHash, Hash.new]

			if(request.impersonation?)
				if(process_abuse_log_params(params, AbuseLog::ABUSE_ACTION_BLOG_EDIT))
					AdminLog.log(request, "blog post delete", "Removed blog posts (id: #{post_hash.keys() * ','}) from #{request.user.username}'s blog");
				else
					site_redirect(url / :blog, [:User, request.user]);
				end
			end

			primary_key_list = post_hash.keys().map{ |k| [request.user.userid, k.to_i] }
			blog_post_list = BlogPost.find(*primary_key_list);
			blog_post_list.each{|post|
				post.comments.each{|comment|
					comment.delete();
				};
				post.delete();
			};
			
			post_limit = BlogPost::POSTS_PER_PAGE;
			current_page = params["current_blog_page", Integer, 0];
			total_pages = (request.user.post_count/post_limit.to_f()).ceil();
			
			if(current_page < 0)
				current_page = 0;
			elsif(current_page > total_pages && total_pages > 0)
				current_page = total_pages - 1;
			end
			
			update_blog_menu_access_visibility(request.user);
			
			reset_friends_sorted_lists(request.user);
			$site.memcache.delete("user_blog_minimal_post_info-#{request.user.userid}");
			$site.memcache.decr("user_blog_post_count-#{request.user.userid}");
			
			site_redirect(url / :blog & {:page => current_page}, [:User, request.user])
		end		
		
		def change_permissions()
			allow_comments = params["comments", Boolean, nil]
			visibility = params["visibility", Integer, nil];
			
			if(BlogVisibility.instance.inverse_visibility_list[visibility.to_s()].nil?())
				visibility = nil;
			end

			post_hash = params["blog_post_select", TypeSafeHash, Hash.new]
			primary_key_list = post_hash.keys.map{ |k| [request.user.userid, k.to_i] }
			blog_post_list = BlogPost.find(*primary_key_list);
			blog_post_list.each{|post|
				post.allowcomments = allow_comments if !allow_comments.nil?
				post.visibility = visibility if !visibility.nil?
				post.store();
			};
			
			update_blog_menu_access_visibility(request.user);
			
			$site.memcache.delete("ruby_userinfo_blog_posts_minimal_relation-#{request.user.userid}");
			$site.memcache.delete("user_blog_minimal_post_info-#{request.user.userid}");
			reset_friends_sorted_lists(request.user);
			
			post_limit = BlogPost::POSTS_PER_PAGE;
			current_page = params["current_blog_page", Integer, 0];
			total_pages = (request.user.post_count/post_limit.to_f()).ceil();
			
			if(current_page < 0)
				current_page = 0;
			elsif(current_page > total_pages && total_pages > 0)
				current_page = total_pages - 1;
			end
			
			site_redirect(url / :blog & {:page => current_page}, [:User, request.user]);
		end

		
		def show_details(blog_user_id, post_id)
			# Don't record blog navigation settings if the user is not logged in
			return if request.session.anonymous?
			
			# Getting the proxy and calling delete on that allows us to make the delete and invalidate the cache
			# without actually having to do two queries (one to get the object and one to delete it)
			navigation_proxy = Blogs::BlogNavigation::StorableProxy.new(
				{
					:userid=>request.session.user.userid,
					:bloguserid=>blog_user_id,
					:postid=>post_id
				})
			navigation_proxy.delete
		end
		
		def hide_details(blog_user_id, post_id)
			# Don't record blog navigation settings if the user is not logged in
			return if request.session.anonymous?
			
			navigation = BlogNavigation.new
			navigation.userid = request.session.user.userid
			navigation.bloguserid = blog_user_id
			navigation.postid = post_id
			navigation.store(:ignore)
		end
		
		def valid_post?()
			result_obj = BlogPostResult.new();
			
			title = params["blog_post_title", String, ""];
			content = params["blog_post_content", String, ""];
			post_visibility = params['blog_post_visibility', Integer, 4];
			post_comments = params['blog_post_comments', Boolean, false];
			post_reset_timestamp = ["blog_post_reset_timestamp", Boolean, false];
			
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
		
		def apply_user_skin!
			# if the user is a Plus member and has a skin we want to load it.
			if( (request.user.blogskin > 0) && request.user.plus?() )
				user_skin = Profile::UserSkin.find(:first, [request.user.userid, request.user.blogskin]);
				if(!user_skin.nil?())
					request.reply.headers['X-user-skin'] = user_skin.header();
				end
			end
		end
		
	end
end