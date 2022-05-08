lib_require :Video, 'video_info';
lib_require :Video, 'video';
lib_require :Worker, "kernel_addon"
module Vid
	
	class EmbedHandler

		OBJECT_TAG = %r{<object [^>]*>.*?</object>};
		EMBED_TAG = %r{<embed [^>]*>.*?</embed>};

		extend TypeID;
		
		# Class method for use by the PostProcessQueue that will create a new EmbedHandler on the content
		# and then store the new videos referenced by the contained embed tags.
		def EmbedHandler.handle_content_from_profile(content)
			handler = EmbedHandler.new(content);
			handler.store_new_videos();
		end
	  worker_task :handle_content_from_profile;
	
	  def initialize(content)
	    @content = content;
	  end
  

	  def store_new_videos
			normalized_content = @content.gsub(/\n/,'');
			
			embeds = normalized_content.scan(OBJECT_TAG);
			
			# Remove any scanned object tags so that object tags with inner embed tags do not go through
			# with the next scan.
			normalized_content.gsub!(OBJECT_TAG, '');
			
			embeds << normalized_content.scan(EMBED_TAG);
			embeds.flatten!;
	
	    embeds.each { |embed|
	      handle_video_embed(embed);
	    };		
	  end

  
	  def handle_video_embed(embed)
	    video_info = VideoInfo.create(embed);
    
	    video = Video.find(:first, :conditions => ["siteid = ?", video_info.id.to_s]);
	    if (video.nil?)
	      video = Video.new;
	      video.siteid = video_info.id;
	      video.embed = video_info.embed;
	      video.adjembed = video_info.adjembed;
	      video.title = video_info.title;
	      video.description = video_info.description;
	      video.categories = video_info.categories;
	      video.thumbnail = video_info.thumbnail_url;
	      video.width = video_info.width;
	      video.height = video_info.height;
	      video.views = 0;
	      video.addtime = Time.now.to_i();
	      video.store();
	    end
	  end

	end
end