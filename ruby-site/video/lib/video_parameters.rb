lib_require :Core, 'typesafehash'

class VideoParameters
	attr_reader :view, :sort, :page, :videoid;
	attr_writer :view, :sort, :page, :videoid;
	
	def initialize(params=nil,id=nil)
		
		# Default settings for the video page parameters
		@videoid = nil;
		@view = "default";
		@sort = "recent";
		@page = 1;

		if (!params.nil? && params.kind_of?(String))
			@videoid = params.sub(/\/(\d*).*/,'\1').to_i;
			@view = params.sub(/.*view=([^&$]*).*/,'\1');
			@sort = params.sub(/.*sort=([^&$]*).*/,'\1');
			@page = params.sub(/.*page=([^&$]*).*/,'\1').to_i;
		elsif (!params.nil? && params.kind_of?(TypeSafeHash))
			@videoid = id;
			@view = params['view', String, @view];
			@sort = params['sort', String, @sort];
			@page = params['page', Integer, @page];
		end
	end
	
	
	def to_url(changing_parameter=nil)
		changing = Array.new;
		
		if (changing_parameter.kind_of?(Array))
			changing = changing_parameter;
		else
			changing << changing_parameter;
		end
		
		url = "";
		if (!changing.include?("videoid") && !videoid.nil?)
			url += "/#{videoid}";
		end
		
		url += "?";
		
		if (!changing.include?("view") && !view.nil?)
			url = add_param(url,"view=#{view}");
		end			
		
		if (!changing.include?("sort") && !sort.nil?)
			url = add_param(url,"sort=#{sort}");
		end
		
		if (!changing.include?("page") && !page.nil?)
			url = add_param(url,"page=#{page}");
		end
		
		return url;
	end
	
	
	def add_param(url,param)
		if (url.index("?") == url.length - 1)
			return url + param;
		else
			return url + "&" + param;
		end
	end
	
end