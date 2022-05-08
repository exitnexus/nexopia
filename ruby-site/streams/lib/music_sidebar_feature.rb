lib_require  :Core, 'storable/cacheable'

module Music
	class SidebarFeature < Cacheable
		init_storable(:streamsdb, 'musicsidebarfeatures');
		
		attr_accessor :date_string, :content_type;
		
		def after_load()
			@date_string = Time.at(self.date).strftime("%b %d, %Y");
			if(/^(http:\/\/).+\.((swf))$/.match(self.content))
				@content_type = :flash;
			else
				@content_type = :image;
			end
		end
		
		def content_uri()	
			if(/^(http:\/\/).+\.((jpg)|(gif)|(png)|(jpeg)|(swf))$/.match(self.content))
				return self.content;
			#Find details on this!
			elsif(/^\w+\.((jpg)|(gif)|(png)|(jpeg)|(swf))$/.match(self.content))
				return "#{$site.static_files}/music/images/#{self.content}";
			end
		end
		
		def is_flash?()
			if(self.content_type == :flash)
				return true;
			end
			return false;
		end
		
		def is_image?()
			if(self.content_type == :image)
				return true;
			end
			return false;
		end
		
		def checked?()
			if(self.active)
				return "checked";
			end
		end
	end
end
