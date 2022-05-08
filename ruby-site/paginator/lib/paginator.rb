lib_require :Paginator, "page_list";

module Paginator
	class Paginator
		def self.generate_page_list(_current_page, _total_pages, _base_url, _icon_color, _get_params = nil, _surrounding_pages = 2,  _include_pages = nil)
			current_page = _current_page;
			if(current_page.nil?())
				raise ArgumentError.new("Paging requires a current page.");
			end
			
			total_pages = _total_pages;
			if(total_pages.nil?())
				raise ArgumentError.new("Paging requires a value for total number of pages.");
			elsif(total_pages < 1)
				raise ArgumentError.new("The total pages needs to be greater than 0.")
			end
			
			base_url = _base_url;
			if(base_url.nil?())
				raise ArgumentError.new("A base url is needed to generate paging links.")
			end
			
			include_pages = _include_pages;
			surrounding_pages = _surrounding_pages;
			if(surrounding_pages.nil?())
				surrounding_pages = 2;
			elsif(surrounding_pages < 1)
				raise ArgumentError.new("Paging error, there has to be at least 1 surrounding page.")
			end
			
			icon_color =_icon_color;
			if(icon_color.nil?())
				icon_color = "000000";
			end
				
			pages_list = PageList.new();
			pages_list.total_pages = total_pages;
			pages_list.base_url = base_url;
			pages_list.icon_color = icon_color;
			pages_list.get_params = _get_params;
			
			pages_list.current_page = current_page;
			(1..surrounding_pages).each{|i|
				pages_list.add(current_page-i);
				pages_list.add(current_page+i);
			}
			
			if(include_pages.nil?() || include_pages.empty?())
				pages_list.add(0);
				pages_list.add(1);
			
				pages_list.add(total_pages-2);
				pages_list.add(total_pages-1);
			else
				include_pages.each{|page_num| pages_list.add(page_num) };
			end
			
			pages_list.sort!();

			return pages_list;
		end
	end
end