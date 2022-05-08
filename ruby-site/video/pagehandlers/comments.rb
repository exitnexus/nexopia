lib_require :Core, 'template/template';
lib_require :Video, 'video';
lib_require :Video, 'video_comment';
lib_require :Video, 'comment_mute';

module Vid

  class Comments < PageHandler

		COMMENTS_FULL_LIMIT = 10;
		COMMENTS_PARTIAL_LIMIT = 3;

  	def initialize(*args)
  		super(*args);
  		@dump = StringIO.new;
  	end


  	declare_handlers("videos") {
  		# Public Level Handlers
  		area :Public

			# Comments (input is the id of the video to display comments for)
			page :GetRequest, :Full, :show_comments_partial, "comments", "partial", input(Integer) 
			page :GetRequest, :Full, :show_comments_full, "comments", "full", input(Integer)
			page :PostRequest, :Full, :post_comment, "comments", "post"
  	}


		def show_comments_partial(videoid)
			show_comments(videoid, true);
		end


		def show_comments_full(videoid)
			show_comments(videoid, false);
		end


		# Comments template
		def show_comments(videoid,inline)
			parameters = VideoParameters.new(params,videoid);
			
			if (inline)
				t = Template::instance('video', 'comments_list_partial');
				t.comments = VideoComment.find(:all, :conditions => ["videoid = ?", videoid], :order => "time DESC", :limit => COMMENTS_PARTIAL_LIMIT);
			else
				request.reply.headers['X-width'] = 0;
				t = Template::instance('video', 'comments_list_full');
				page = params['page', Integer, 1];
				back_link = params['back_link', String] || request.headers["HTTP_REFERER"];
				t.back_link = parameters.to_url;
				t.comments = VideoComment.find(:total_rows, :page => page, :conditions => ["videoid = ?", videoid], :order => "time DESC", :limit => COMMENTS_FULL_LIMIT);
			end
			
			t.videoid = videoid;
			t.handler_root = "/videos";
						
			is_video_mod = false;
			if (!request.session.anonymous?())
				is_video_mod = request.session.has_priv?(VideoModule, :mute);
			end
			t.is_video_mod = is_video_mod;
			
			puts t.display();
		end


		def post_comment()
			request.reply.headers['X-width'] = 0;
			userid = session.user.userid;
			videoid = params['videoid', Integer];

			parameters = VideoParameters.new(params,videoid);
			
			mute = CommentMute.find_last(userid, videoid);
			if (mute.nil?)
				comment = params['comment', String];
				submit_value = params['action', String];

				video_comment = VideoComment.new;
				video_comment.videoid = videoid;
				video_comment.userid = userid;
				lib_require :Core, "text_manip"
				begin
					comment.spam_filter();
				rescue SpamFilterException
					t = Template::instance("core", "simple_message")
					t.text = $!.to_s;
					puts t.display
					throw :page_done
				end
				video_comment.comment = comment;
				video_comment.time = Time.now.to_i;
			
				if (submit_value == "Post")
					video_comment.store();
					site_redirect("/videos" + parameters.to_url);
				elsif (submit_value == "Preview")
					t = Template::instance('video', 'comments_preview');
					t.comment = video_comment;
					t.handler_root = "/videos";

					puts t.display();	
				end
			else
				t = Template::instance('video', 'comments_inform_muted');
				t.mute = mute;
				puts t.display();
			end
		end

	end

end