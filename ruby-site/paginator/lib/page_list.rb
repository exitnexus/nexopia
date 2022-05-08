lib_require :Paginator, "page_link";

module Paginator
	class PageList < Array
		attr_accessor :current_page, :total_pages, :base_url, :icon_color, :include_clear_element, :get_params, :minion_name;
	
		def initialize()
			@include_clear_element = false;
		end
		
		def current_page=(page_num)
			temp = PagingLink.new(page_num);
			temp.current_page = true;
			temp.base_url = @base_url;
			temp.get_params = @get_params;
			@current_page = temp;
			self << temp;
		end
	
		def add(page)
			if(!self.include?(page) && page < @total_pages && page >= 0)
				temp = PagingLink.new(page);
				temp.base_url = @base_url;
				temp.get_params = @get_params;
				self << temp;
			end
		end
	
		def include?(obj)
			if(!obj.kind_of?(Integer))
				obj = obj.to_i();
			end
		
			self.each{|page|
				if(page.page == obj)
					return true;
				end
			};
		
			return false;
		end
	
		def sequential?(page_obj)
			i = self.index(page_obj);
		
			if(page_obj.page+1 == self[i+1].page)
				return true;
			end
		
			return false;
		end
	
		def display()
			t = Template.instance("paginator", "paging_control");
			t.paging_list = self;	
			t.icon_color = @icon_color;
			t.include_clear_element = @include_clear_element;
			t.minion_name = @minion_name;
			
			return t.display();
		end
	end
end