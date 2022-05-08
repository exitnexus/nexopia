lib_require :Core, 'template/template';
lib_require :Video, 'video';
lib_require :Video, 'muted_user';

module Vid

	class Admin < PageHandler

		def initialize(*args)
			super(*args);
			@dump = StringIO.new;
		end


		declare_handlers("videos") {

			# Public Level Handlers
			area :Admin
		
			# The default video moderation section
			page :GetRequest, :Full, :main
		
			# Ban/Unban videos that have been reported
			page :GetRequest, :Full, :list_content, "content"
			page :GetRequest, :Full, :list_content, "content", input(String)
			page :PostRequest, :Full, :update_content, "content", "update"
			
			# Mute users
			page :GetRequest, :Full, :mute_user, "mute", input(Integer)
			page :PostRequest, :Full, :mute_post, "mute", "post"
			
			# Display/unmute muted users
			page :GetRequest, :Full, :list_mutes, "mutes"
		}
		

		def main()
			t = Template::instance('video', 'admin');
			
			puts t.display;
		end

		
		def list_content(type="reported")
			page = params['page', Integer, 1];
			
			if (type == "reported")
				videos = Video.find_reported(page,6);
			elsif (type == "banned")
				videos = Video.find_banned(page,6);
			end
			
			t = Template::instance('video', 'admin_list_content');
			t.videos = videos;
			
			t.handler_root = "/admin/videos";
			t.handler_query = type;
			
			puts t.display;
		end
	
	
		def update_content
			video_params = params['videos', TypeSafeHash];
			ban_params = params['ban', TypeSafeHash];
			ids = video_params.keys;
			
			videos = Video.find(:all, :conditions => ["id in ?", ids]);
			
			videos.each { |video|
				banned = !ban_params.nil? && ban_params.has_key?(video.id.to_s);
				video.ban = banned;
				video.store;
			};
			
			handler_query = params['handler_query', String];
			
			site_redirect("/videos/content/#{handler_query}");
		end
		
	
		def mute_user(comment_id)
			comment = VideoComment.find(:first, comment_id);
			user = User.find(:first, comment.userid);
			
			t = Template::instance('video', 'mute_user');
			t.comment = comment;
			t.handler_root = "/admin/videos";
			t.back_link = request.headers["HTTP_REFERER"];
			
			puts t.display();
		end
	
		
		def mute_post()
			username = params['username', String];
			commentid = params['comment_id', Integer];
			length = params['length', Integer];
			global_length = params['global_length', Integer];
			videoid = params['video_id', Integer];
			reason = params['reason', String];
			
			user = User.get_by_name(username);
			
			comment_mute = CommentMute.new;
			comment_mute.userid = user.userid;
			comment_mute.videoid = videoid;
			comment_mute.mutetime = Time.now.to_i;
			comment_mute.unmutetime = comment_mute.mutetime + length;
			comment_mute.reason = reason;
			comment_mute.modid = session.user.userid;
			comment_mute.global = (global_length < 0);
			comment_mute.store();
			
			t = Template::instance('video', 'mute_post');
			t.handler_root = '/admin/videos';
			t.back_link = params['back_link', String];
			
			t.mute = comment_mute;
			
			puts t.display();
		end
			
		
		def list_mutes()
			page = params['page', Integer, 1];
			
			t = Template::instance('video', 'admin_list_mutes');
			t.muted_users = MutedUser.get_page(page, 10);
			
			puts t.display();
		end
				
	end

end