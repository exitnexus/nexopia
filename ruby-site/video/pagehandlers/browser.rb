lib_require :Core, 'template/template';
lib_require :Video, 'video', 'video_navigate', 'video_parameters';

module Vid

	class Browser < PageHandler

		BROWSE_PER_PAGE = 12;
		FEATURE_PER_PAGE = 15;
		FEATURE_PARTIAL = 3;

		def initialize(*args)
			super(*args);
			@dump = StringIO.new;
		end


		declare_handlers("videos") {
			# Public Level Handlers
			area :Public
		
			# Featured Videos (input is the mode == "feature" or "default")
			page :GetRequest, :Full, :feature_videos, "feature"
			page :GetRequest, :Full, :feature_videos, "feature", input(String), input(Integer)
			
			# Browse Videos
			page :GetRequest, :Full, :browse_videos, "browse"
			page :GetRequest, :Full, :browse_videos, "browse", input(Integer)
		}
		

		def browse_videos(id=nil)
			t = Template::instance('video', 'browser_videos');

			parameters = VideoParameters.new(params,id);
			#navigate = VideoNavigate.get_for_user(request_userid(session));

			if (parameters.sort == "recent")
				order_by = "addtime DESC";
			elsif (parameters.sort == "random")
				order_by = "RAND()";
			elsif (parameters.sort == "popular")
				order_by = "recentviews DESC";
			elsif (parameters.sort == "best")
				order_by = "views DESC";
			elsif (parameters.sort == "worst")
				order_by = "views ASC";
			end
			
			t.videos = Video.find(:total_rows, :page => parameters.page, :order => order_by, :conditions => ["ban='n' AND hide='n'"], :limit => BROWSE_PER_PAGE);
			t.thumbnail_parameters = parameters.to_url('videoid');
			t.sort_parameters = parameters.to_url('sort');
			t.selected_sort = parameters.sort;
			t.page_parameters = parameters.to_url('page');
			t.handler_root = "/videos";
			
			puts t.display();
		end


		def feature_videos(mode="partial",id=nil)
			parameters = VideoParameters.new(params,id);
			conditions = ["featured='y' AND ban='n' AND hide='n'"];

			if (mode == "partial")
				t = Template::instance('video', 'featured_videos');
				t.videos = Video.find(:all, :conditions => conditions, :order => "addtime DESC", :limit => FEATURE_PARTIAL);
				t.thumbnail_parameters = parameters.to_url('videoid');
			elsif (mode == "all")
				t = Template::instance('video', 'browser_videos');
				t.videos = Video.find(:total_rows, :page => parameters.page, :conditions => conditions, :order => "addtime DESC", :limit => FEATURE_PER_PAGE);
				t.thumbnail_parameters = parameters.to_url('videoid');
				t.page_parameters = parameters.to_url('page');
				t.hide_sort = true;
			end

			t.handler_root = "/videos";

			puts t.display();
		end

	end

end