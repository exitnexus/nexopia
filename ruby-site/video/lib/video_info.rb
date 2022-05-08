require "rexml/document";
require "rubygems";
require "youtube";
require "rexml/document";
require "open-uri";
require "mechanize";
require "hpricot";
require "htmlentities";

# Encapsulates information about a video based on its embed tag. This hides the differences
# between sites and how they provide their information, so that videos included on nexopia
# can be handled in a source independent manner.
#
# Current site-independent attributes include:
# 
# embed
# id
# title
# description
# categories
# thumbnail_url
#
# Note: "categories" is a loose term for meta information about the nature of the content
# of the video. YouTube provides easy category information, but on other sites, the categories
# can actually be pretty random. Some category information will really be "tags", which are
# totally up to what users decide to enter and may be misleading. The idea in grabbing this
# information is that it's better than nothing in terms of grabbing the initial information
# that will be stored locally about a video.
class VideoInfo

	attr_accessor :embed, :adjembed, :width, :height;
	attr_reader :id, :title, :description, :categories, :thumbnail_url;

	WIDTH_REGX = /(width.*?[:=].*?["']*)(\d*)(.*?["']*)/;
	HEIGHT_REGX = /(height.*?[:=].*?["']*)(\d*)(.*?["']*)/;
	EMBED_INSERT_REGEX = /(.*?<embed)(.*?)(>.*)/;
	OBJECT_INSERT_REGEX = /(.*?<object)(.*?)(>.*)/;
	PARAM_INSERT_REGEX = /(.*<object.*>)(.*?)(<\/object>.*)/;
	CHECK_WMODE_REGEX = /wmode\s*=\s*"transparent"/;
	VIMEO_TAG_REGEX = /.*tag:(.*)/;
	
	# Factory method. This takes an embed tag that a user can put in his/her profile and uses
	# it to create a VideoInfo object that will correctly grab necessary information about the
	# video for including it on our main video page.
	def VideoInfo.create(embed_tag)
		
		# Find the actual src url for the video from the embed tag.
		doc = REXML::Document.new(embed_tag.to_s);
		src = REXML::XPath.first(doc, "embed/attribute::src|object/embed/attribute::src|object/attribute::data").to_s;

		# Figure out which site it's from and create a specific VideoInfo configured
		# to correctly parse that site.
		youtube_url = "youtube.com";
		youtube_delayed_url = "youtube-nocookie.com"
		google_url = "video.google.com";
		break_url = "break.com";
		photobucket_url = "photobucket.com";
		vimeo_url = "vimeo.com";

		if (src.index(youtube_url) != nil || src.index(youtube_delayed_url) != nil)
			info = VideoInfoYoutube.new(src);
		elsif (src.index(google_url) != nil)
			info = VideoInfoGoogle.new(src);
		elsif (src.index(break_url) != nil)
			info = VideoInfoBreak.new(src);
		elsif (src.index(photobucket_url) != nil)
			info = VideoInfoPhotobucket.new(src);
		elsif (src.index(vimeo_url) != nil)
			info = VideoInfoVimeo.new(src);
		end
		
		# Tack on the embed tag to the object's information for good measure.
		info.embed = embed_tag;
		
		adj_embed = embed_tag.gsub("\n",'');
		width_s = adj_embed[WIDTH_REGX, 2];
		height_s = adj_embed[HEIGHT_REGX, 2];

		__add_size_placeholders!(adj_embed);
		__attach_event_handler!(adj_embed);
		__ensure_object_wmode_attribute!(adj_embed);
		__ensure_object_wmode_param!(adj_embed);

		info.adjembed = adj_embed;
		info.width = width_s.to_i;
		info.height = height_s.to_i;
		
		return info;
	end


	def initialize()
	end

	
	def VideoInfo.__ensure_object_wmode_param!(embed_tag)
		doc = REXML::Document.new(embed_tag);
		
		if (REXML::XPath.match(doc, "object/param/attribute::name='wmode'").include?(true))
			return;
		end
		
		if (embed_tag.match(PARAM_INSERT_REGEX) != nil)
			embed_tag.sub!(PARAM_INSERT_REGEX, '\1' + '\2' + "<param name=\"wmode\" value=\"transparent\"/>" + '\3');
		end
	end
	
	
	def VideoInfo.__ensure_object_wmode_attribute!(embed_tag)
		if (!embed_tag.match(CHECK_WMODE_REGEX))
			embed_tag.gsub!(EMBED_INSERT_REGEX, '\1' + '\2' + " wmode=\"transparent\"" + '\3');
		end
	end
	
	
	def VideoInfo.__attach_event_handler!(embed_tag)
		[OBJECT_INSERT_REGEX, EMBED_INSERT_REGEX].each { |onmouseup_insert|
			embed_tag.gsub!(onmouseup_insert, '\1' + '\2' + " onmouseup='Views.update({videoid})'" + '\3');
		}
	end


	def VideoInfo.__add_size_placeholders!(embed_tag)
		embed_tag.gsub!(WIDTH_REGX, '\1' + "{width}" + '\3');
		embed_tag.gsub!(HEIGHT_REGX, '\1' + "{height}" + '\3');
	end


	class VideoInfoYoutube < VideoInfo
		def initialize(src)
			@id = src.gsub(/\n/,'').sub(/.*\/(.*)$/,'\1');

			youtube = YouTube::Client.new($site.config.youtube_dev_id);
			details = youtube.video_details(id);

			@title = details.title;
			@description = details.description;
			@categories = details.channel_list.values * ",";
			@thumbnail_url = details.thumbnail_url;
		end
	end


	class VideoInfoGoogle < VideoInfo
		def initialize(src)
			@id = src.gsub(/\n/,'').sub(/.*docId=([^&]*).*$/,'\1').to_i;

			url = "http://video.google.com/videohosted?docid=#{@id}";
			content = "";
			open(url) { |f| content = f.read };
			doc = Hpricot(content);
			
			@title = HTMLEntities.decode_entities(
				(doc/"div[@id='pvprogtitle']").inner_html.strip);
			@description = HTMLEntities.decode_entities(
				(doc/"div[@id='description']/font").inner_html.strip);
			@categories = "";
			@thumbnail_url = HTMLEntities.decode_entities(
				(doc/"img[@class='detailsimage']").first.get_attribute("src"));

# Note: Google Video has changed since the last update to the GoogleVideo library. Since it
# looks like the GoogleVideo library is not being actively updated, and we don't need nearly
# the number of details that the library gives (and we're doing our own screen scraping for
# break.com and photobucket.com), I'm just doing that above instead of trying to modify the
# library to work and figure out how to get the changes into ruby-forge. If the library does
# get updated later, it might be worthwhile re-investigating. And perhaps some day google
# will release its own API, which will be way more reliable (comment made: June 22, 2007).
=begin			
			client = GoogleVideo::Client.new;
			request = GoogleVideo::VideoDetailsRequest.new :doc_id => id;
			response = client.video_details request;
			video = response.video;

			@title = video.title;
			@description = video.description;
			
			# TODO:
			#
			# I'm trying to use pre-made libraries where I can. However, in this case, there is no ability
			# to retrieve tag information (the closest thing to "categories") for a video from the google
			# library. Perhaps this will be updated. If it becomes more important to have categories for
			# everything, we may just want to replace the library code with our own html scraping like we
			# do for Break and Photobucket, where APIs do not exist. Until categories become more important,
			# however, I'm going to leave this one "as is".
			@categories = "";
			@thumbnail_url = video.video_frame_thumbnails.first.thumbnail_image_url;
=end
		end
	end


	class VideoInfoBreak < VideoInfo
		def initialize(src)
			agent = WWW::Mechanize.new;
			page = agent.get(src);
			redirect_uri = page.uri.to_s;
			encoded_url = redirect_uri.gsub(/\n/,'').sub(/.*contentURL=([^&]*).*$/,'\1');
			decoded_url = URI.unescape(encoded_url);
			doc = open(decoded_url) { |f| Hpricot(f) };

			@id = src.gsub(/\n/,'').sub(/.*?embed\.break\.com.*?\/(.*)$/,'\1');
			@title = (doc/:html/:title).innerHTML.strip;
			@description = (doc/"div[@id='description_content']"/"span").innerHTML.strip;
			@categories = "";
			(doc/"span[@class='gen_link']"/"a[@class='gen_link']").each{ |e| @categories += e.innerHTML + "," };
			if (categories.size > 1)
				@categories = @categories[0,@categories.size-1];
			end
			
			@thumbnail_url = (doc/"link[@rel='videothumbnail']").first.attributes['href'].strip;
			# Fix stupid break.com bug
			@thumbnail_url.gsub!(/http.*?http/,"http");
		end
	end


	class VideoInfoPhotobucket < VideoInfo
		def initialize(src)
			prefix = "http://photobucket.com/mediadetail/?media=";
			media_src = src.gsub(/\n/,'').sub(/.*file=(.*)$/,'\1');			 
			encoded_url = prefix + media_src;
			decoded_url = URI.unescape(encoded_url);
			
			agent = WWW::Mechanize.new;
			page = agent.get(decoded_url);
			doc = Hpricot(page.body);
			
			@id = media_src.sub(/.*?albums\/(.*)/,'\1');
			@title = (doc/:html/:title).innerHTML.strip;
			@description = (doc/"meta[@name='description']").first.attributes['content'].strip;
			@categories = (doc/"meta[@name='keywords']").first.attributes['content'].strip;
			@thumbnail_url = doc.to_s.gsub(/\n/,'').sub(/.*\[IMG\](.*?)\[\/IMG\].*$/,'\1');
		end
	end


	class VideoInfoVimeo < VideoInfo
		def initialize(src)
			prefix = "http://vimeo.com/";
			media_src = src.gsub(/\n/,'').sub(/.*clip_id=(\d*).*$/,'\1');			 
			url = prefix + media_src;
			
			content = "";
			open(url) { |f| content = f.read };
			doc = Hpricot(content);
			
			@id = media_src.to_i;
			@title = HTMLEntities.decode_entities(
				(doc/"div[@id='cliptitle']").inner_html.strip);
			@description =	HTMLEntities.decode_entities(
				(doc/"div[@id='caption']").inner_html.strip);
				
			taglist = (doc/"div[@id='tag_list']"/"ul"/"li"/"a");

			category_array = taglist.map { |tag| 
				tag.attributes["href"].sub(VIMEO_TAG_REGEX,'\1');
			}
			category_array.uniq!;
			@categories = category_array * ",";
			
			@thumbnail_url = (doc/"link[@rel='image_src']").first.attributes["href"];
		end
	end
end