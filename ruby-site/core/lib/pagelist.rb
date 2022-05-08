

class PageList

	END_SIZE = 2;    #num pages at the start and end
	MIDDLE_SIZE = 2; #num pages to either side of the current page
	PAGE_SIZE = 25;  #default page size

	def initialize(url, cur_page, num_pages = 0)
		@url = url;
		@cur_page = cur_page;
		@num_pages = num_pages;
	end

	def set_num_pages(num_rows, page_size = PAGE_SIZE)
		@num_pages = (num_rows.to_f/page_size).ceil;
	end

	def to_s
		pages = [];

		if(@num_pages <= 0)
			@num_pages = 1;
		end

		if(@cur_page < 0)
			@cur_page = 0;
		end

		if(@cur_page >= @num_pages)
			@cur_page = @num_pages-1;
		end

	#set the ranges
		head = (0...END_SIZE);
		mid = (@cur_page - MIDDLE_SIZE)..(@cur_page + MIDDLE_SIZE);
		tail = ((@num_pages-END_SIZE)...(@num_pages));

	#sanitize and collapse the ranges
		prev = nil;

		[head, mid, tail].each {|part|
			part.each {|i|
				if(i < @num_pages)
					if(prev)
						if(i == prev+2) #up by two, fill in the gap
							pages << i-1;
						elsif(i > prev+2) #big jump, add '...'
							pages << "...";
						end
					end
					if(!prev || i > prev)
						pages << i;
						prev = i;
					end
					#if two ranges overlap, i will be smaller than prev, and be ignored
				end
			}
		}

	#convert to html
		sep = (@url['?'] ? '&' : '?');

		pages = pages.map {|page| 
			if (page.kind_of?(String)) #match the '...'
				page
			elsif (page == @cur_page)
				"<a class='current_page' href='#{@url}#{sep}page=#{page}'>#{page+1}</a>"
			else
				"<a href='#{@url}#{sep}page=#{page}'>#{page+1}</a>"
			end
		}

		if (@cur_page > 0)
			pages.unshift("<a href='#{@url}#{sep}page=#{@cur_page-1}'>&lt; Previous</a>");
		end

		if (@cur_page < @num_pages-1)
			pages << "<a href='#{@url}#{sep}page=#{@cur_page+1}'>Next &gt;</a>";
		end

		return "<div class='pages'>#{pages.join('&#160;')}</div>"
	end
end

