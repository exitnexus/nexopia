lib_require :Blogs, "blog_view", "blog_visibility"

lib_want :Profile, "user_skin"

module Blogs
	class BlogCalendarPageHandler < PageHandler
	
		declare_handlers("blog/calendar") {

			area :User
			access_level :Any
			page :GetRequest, :Full, :blog_calendar
			page :GetRequest, :Full, :blog_calendar, input(Integer)
			handle :GetRequest, :draw_month, input(Integer), input(Integer)
		}
	
	
		def blog_calendar(selected_year=nil)
			request.reply.headers['X-width'] = 0;
			
			if(request.user.blogskin > 0 && request.user.plus?())
				user_skin = Profile::UserSkin.find(:first, [request.user.userid, request.user.blogskin]);
				#user_skin = request.user.blog_skin;
				if(!user_skin.nil?())
					request.reply.headers['X-user-skin'] = user_skin.header();
				end
			end
		
			t = Template.instance('blogs', 'blog_calendar')
		
			t.tab = :calendar
			t.blog_user = request.user
			t.viewing_user = request.session.user
		
			date_map = request.user.blog_post_date_map
		
			admin_viewer = request.session.has_priv?(CoreModule, :editjournal) && request.user != request.session.user;
			visibility_level = BlogVisibility.determine_visibility_level(request.user, request.session.user, admin_viewer);
			year_list = date_map.years(visibility_level)
		
			t.years = year_list
			t.selected_year = selected_year || year_list.first
			t.months = (1..12).to_a.reverse
			t.date_map = date_map
			t.blog_views = BlogView.views(request.user, :display)
			if(request.user == request.session.user && request.user.blog_friends_last_read.postcount > 0)
				t.friends_unread_post_count = request.user.blog_friends_last_read.postcount;
			end
			
			
			t.visibility_level = visibility_level
			
			puts t.display
		end
	
	
		def draw_month(year, month)
			date = Date.new(year, month, 1);

			month_title = date.strftime("%B")

			week_rows = []
			week_row = Array.new(7)
			while(date.month == month)
				week_row[date.wday] = "#{date.day}"	
				date = date + 1
				if(date.wday == 0)
					week_rows << week_row
					week_row = Array.new(7)
				end
			end
		
			# Get the last week row if it has days in it.
			week_rows << week_row if week_row[0]
		
			t = Template.instance('blogs', 'calendar_month')

			t.week_rows = week_rows
			t.month_title = month_title
			t.year = year
			t.month = month
			t.blog_user = request.user
			t.date_map = params.to_hash['date_map'] || request.user.blog_post_date_map
			t.visibility_level = params.to_hash['visibility_level'] || Visibility.instance.visibility_list[:all]
		
			puts t.display
		end	
	end
end