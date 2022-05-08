lib_require :Core, 'template/template';
lib_require :Video, 'video', 'video_navigate', 'video_parameters';

module Vid

	class Videos < PageHandler

		def initialize(*args)
			super(*args);
			@dump = StringIO.new;
		end


		declare_handlers("videos") {
			# Public Level Handlers
			area :Public
		
			page :GetRequest, :Full, :show_main
			page :GetRequest, :Full, :show_main, input(Integer)

			page :GetRequest, :Full, :show_embed, "embed", input(Integer)
			
			# Show video (input is the id of the video to show)
			page :GetRequest, :Full, :show_video, "show", input(Integer)

			page :PostRequest, :Full, :update_views, "view", input(Integer)
		}


		def show_main(id=nil)
			request.reply.headers['X-width'] = 0;

			parameters = VideoParameters.new(params,id);

			# TODO: showvideoid should actually return "nil" instead of 0 if it has been set to NULL in
			# the database. Storable should be updated to reflect this.
			if (id.nil?)
				random_id_query = "SELECT id FROM video ORDER BY RAND() limit 1";
				result = Video.db.query(random_id_query);
				id = result.fetch_field.to_i;
			end

			if (parameters.view == "feature")
				t = Template::instance('video', 'main_feature');
			else
				t = Template::instance('video', 'main_default');
			end

			t.view_parameters = parameters.to_url(['view','page']);
			t.videoid = "#{id}";

			t.handler_root = "/videos";
			puts t.display();
		end
		

		def show_video(videoid)
			t = Template::instance('video', 'show_video');
			
			parameters = VideoParameters.new(params,videoid);
			
			if (videoid.nil?)
				display_video = nil;
				display_video = Video.new; # take out later.
			else
				display_video = Video.find(:first, videoid);
			end

			t.video = display_video;
			t.video_parameters = parameters.to_url;
			t.handler_root = "/videos";
			
			puts t.display();
		end


		def show_embed(videoid)
			request.reply.headers['X-width'] = 0;
			
			parameters = VideoParameters.new(params,videoid);
	
			video = Video.find(:first, videoid);
			
			embed_text = video.embed;
			
			t = Template::instance('video', 'show_embed');
			t.embed_text = embed_text;
			
			t.video_params = parameters.to_url;

			puts t.display();
		end
		
		
		def update_views(videoid)
			video = Video.find(:first, videoid);
			video.hit();
			video.store();
		end

	end

end