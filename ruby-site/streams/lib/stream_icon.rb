lib_require :Core, 'storable/cacheable', 'typeid'
lib_require :Streams, 'stream_icon_type';

class StreamIcon < Cacheable
	attr_accessor :selected;
	
	init_storable(:streamsdb, 'streamicons');
	
	relation_multi(:icon_types, :iconid, StreamIconType, :iconid);
	
	def image_location()
		return generate_image_location(self.image);
	end
	
	def thumbnail_location()
		return generate_image_location(self.thumbnail);
	end
	
	#This won't work if we go live before all the domain changes.
	def generate_image_location(in_image)	
		if(/^(http:\/\/).+\.((jpg)|(gif)|(png)|(jpeg))$/.match(in_image))
			return in_image;
		elsif(/^\w+\.((jpg)|(gif)|(png)|(jpeg))$/.match(in_image))
			return "#{$site.static_files_url}/streams/images/#{in_image}";
		else
			return "#{$site.static_files_url}/streams/images/#{in_image}.png";
		end
	end
end