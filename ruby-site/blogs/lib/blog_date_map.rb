lib_require :Blogs, "blog_post";
lib_require :Core, "visibility"

module Blogs
	class DateMap
		PostMapping = Struct.new :id, :visibility;
		
		def initialize(blog_posts)
			@internal_date_map = Hash.new
			@internal_years = Hash.new

			# We want at least the current year to be visible and initialized or we'll run into problems with
			# the data causing infinite loops, etc.
			current_year = Time.now.year
			@internal_date_map[current_year] = Hash.new
			@internal_years[current_year] = Visibility.instance.visibility_list[:all]		
			
			blog_posts.each { |post| 
				time = Time.at(post.time)

				@internal_date_map[time.year] = Hash.new if @internal_date_map[time.year].nil?
				@internal_date_map[time.year][time.month] = Hash.new if @internal_date_map[time.year][time.month].nil?
				@internal_date_map[time.year][time.month][time.day] = Array.new if @internal_date_map[time.year][time.month][time.day].nil?

				@internal_date_map[time.year][time.month][time.day] << PostMapping.new(post.id, post.visibility)
				
				@internal_years[time.year] = Visibility.instance.visibility_list[:none] if @internal_years[time.year].nil?
				if (post.visibility > @internal_years[time.year])
					@internal_years[time.year] = post.visibility
				end
			}
		end
		
		def years(visibility)
			return @internal_years.keys.select{ |key| @internal_years[key] >= visibility }.sort.reverse
		end
				
		def posts_on(year,month,day,visibility=Visibility.instance.visibility_list[:all])
			if (@internal_date_map[year] && @internal_date_map[year][month] && @internal_date_map[year][month][day])
				return @internal_date_map[year][month][day].select { |p| p.visibility >= visibility }
			else
				return []
			end
		end
		
		def post_ids_on(year,month,day,visibility=Visibility.instance.visibility_list[:all])
			return self.posts_on(year, month, day, visibility).map{ |p| p.id }
		end
	end
end