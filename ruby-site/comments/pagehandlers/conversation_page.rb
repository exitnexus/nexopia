module Comments
	class ConversationPageHandler < PageHandler
		declare_handlers("comments"){
			area :User
			access_level :LoggedIn
			
				page :GetRequest, :Full, :view_conversation, "conversation", input(String);
		}
		
		def view_conversation(other_user_name)	
			request.reply.headers["X-width"] = 0;
			
			if(request.user.commentsskin > 0)
				user_skin = Profile::UserSkin.find(:first, [request.user.userid, request.user.commentsskin]);
				if(!user_skin.nil?() && request.user.plus?())
					request.reply.headers['X-user-skin'] = user_skin.header();
				end
			end
			
			comments_user = request.user;
			conversation_user = User.get_by_name(other_user_name);
			if(conversation_user.nil?())
				user_name_obj = UserName.by_name(other_user_name);
				if(!user_name_obj.nil?())
					conversation_user = DeletedUser.find(:first, other_user_name.userid);
				end
				if(conversation_user.nil?())
					site_redirect(url / request.user.username / :comments);
				end
			end
			
			results = Comment.find(:total_rows,
				:conditions => ["((userid = # && authorid = ?) || (userid = # && authorid = ?)) && deleted = 'n'",
				comments_user.userid, conversation_user.userid, conversation_user.userid, comments_user.userid]);
			
			page = params["page", Integer, 0];
			comments_limit = Comment::COMMENTS_PER_PAGE;
			page_count = (results.total_rows.to_f()/comments_limit).ceil();
			
			#grab id, userid, time from comments where userid = userid and authorid = other_user_id and deleted = 'n'
			#results = Comments.db.query("SELECT id, userid, time FROM usercomments WHERE (userid = # AND authorid = ?) OR (userid = # AND authorid = ?) AND deleted = 'n'", request.user.userid, other_user.userid, other_user.userid, request.user.userid);
			
			sorted_results = results.sort_by{|result| -result.time };
			section_result = sorted_results.slice(page*comments_limit, comments_limit);
			
			t = Template.instance("comments", "conversation_view");
			t.comments_list = section_result;
			
			t.viewing_user = request.session.user;
			t.profile_user = comments_user;
			t.conversation_user = conversation_user;
			
			t.comments_count = section_result.total_rows;
			t.num_pages = page_count;
			t.current_page = page;
			t.comments_id_list = section_result.map{|comment| comment.id}; 
			t.number_of_comments = results.total_rows;
			t.page_url = url / :users / request.user.username / :comments / :conversation / conversation_user.username;
			t.conversation_page = true;
			
			if(request.session.user == conversation_user || request.session.user == comments_user)
				t.show_editor = true;
			else
				t.show_editor = false;
			end
			
			if(request.session.user == conversation_user)
				t.comment_post_url = url / :users / request.user.username / :comments / :post;
			else
				t.comment_post_url = url / :users / conversation_user.username / :comments / :post;
			end
			t.editor_text = "Post a comment...";
			
			t.thumbnail_image_type = :landscapethumb;	
			if(t.admin_viewer && request.user.userid != request.session.user.userid)
				t.comments_delete_url = url / :users / request.user.username / :comments / :admin / :delete;
			else
				t.comments_delete_url = url / :users / request.user.username / :comments / :delete;
			end
			
			if(page_count > 1)
				page_list = Paginator::Paginator.generate_page_list(page, page_count, url / :users / request.user.username / :comments / :conversation / conversation_user.username, request.user.comments_skin[:primary_block_icon_color]);
				t.paging_string = page_list.display();
			end
			print t.display();
		end
	end
end