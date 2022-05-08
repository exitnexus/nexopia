lib_require :Core, 'pagehandler'
lib_require :Forums, 'post', 'forumsmodule'
lib_require :Adminutils, 'mod_log'
module Forum
	class Delete < PageHandler
		declare_handlers("forums/delete") {
			area :Public
			access_level :Any

			page :GetRequest, :Full, :confirm_post_delete, 'post', 'confirm', input(Integer), input(Integer)
			handle :PostRequest, :delete_post, 'post'
			page :GetRequest, :Full, :confirm_thread_delete, 'thread', 'confirm', input(Integer), input(Integer)
			handle :PostRequest, :delete_thread, 'thread'
		}
		
		def confirm_post_delete(forum_id, post_id)
			t = Template::instance('forums', 'delete_post');
			t.forum_id = forum_id;
			t.post_id = post_id;
			puts t.display;
		end
		
		def delete_post(forum_id=nil, post_id=nil, reason=nil)
			forum_id = params['forum_id', Integer] if forum_id.nil?;
			post_id = params['post_id', Integer] if post_id.nil?;
			reason = params['reason', String] if reason.nil?;
			post = Post.find(:first, forum_id, post_id);
			perform_post_delete(post);
		end
		
		def confirm_thread_delete(forum_id, thread_id)
			t = Template::instance('forums', 'delete_thread');
			t.forum_id = forum_id;
			t.thread_id = thread_id;
			puts t.display;
		end
		
		def delete_thread(forum_id=nil, thread_id=nil, reason=nil)
			forum_id = params['forum_id', Integer] if forum_id.nil?;
			thread_id = params['thread_id', Integer] if thread_id.nil?;
			reason = params['reason', String] if reason.nil?;
			thread = Forum::Thread.find(:first, forum_id, thread_id);
			ModLog.log(request.session.user, post.author, ForumsModule, post, :deletethread, reason);
			thread.delete();
			thread.posts.each {|post|
				perform_post_delete(post, reason);
			}
		end
		
		private 
		def perform_post_delete(post, reason)
			#def log(admin, user, mod, storable, action, reason, extra1=0, extra2=0)
			ModLog.log(request.session.user, post.author, ForumsModule, post, :deletepost, reason);
			post.delete();
		end
		
	end
end
