module Paginator
	class PagingLink
		attr_accessor :page, :current_page, :base_url, :get_params;
	
		def initialize(page_num)
			@page = page_num;
			@current_page = false;
		end
	
		def <=>(anOther)
			if(!anOther.kind_of?(PagingLink))
				raise(ArgumentError.new("#{anOther.class} is not comparable with #{self.class}"));
			end
			if(self.page < anOther.page)
				return -1;
			elsif(self.page > anOther.page)
				return 1;
			else
				return 0;
			end
		end
	
		def uri_info(linktype = 'default')
			full_get_params = ["page=#{@page}"];
			if(!@get_params.nil?())
				@get_params.each_pair{|key, value|
					full_get_params << "#{key}=#{value}";
				};
			end
			return [page+1, @base_url + "?" + full_get_params.join('&')];
		end
	end
end