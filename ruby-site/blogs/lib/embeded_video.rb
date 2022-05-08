module Blogs
class EmbededVideo
		
	WIDTH_REGX = /(width.*?[:=].*?["']*)(\d*)(.*?["']*)/;
	HEIGHT_REGX = /(height.*?[:=].*?["']*)(\d*)(.*?["']*)/;

	BLOG_VIDEO_MAX_WIDTH = 640
	BATTLE_VIDEO_MAX_WIDTH = 354

	def self.width(embed)
		embed.match(WIDTH_REGX)
		return $2.to_i
		
	end
	
	def self.height(embed)
		embed.match(HEIGHT_REGX)
		return $2.to_i
	end
	
	# Spits out the embed tag for the video resized for the given page.
	# Current options are :blog, :battle
	# The profile page takes care of resizing videos itself.
	def self.resize(embed, size)
		
		resized_embed = embed
		max_width = 0
		case size
			when :blog
				max_width = BLOG_VIDEO_MAX_WIDTH
			when :battle
				max_width = BATTLE_VIDEO_MAX_WIDTH
		end
	
		embed.match(WIDTH_REGX)
		original_width = $2.to_i
		embed.match(HEIGHT_REGX)
		original_height = $2.to_i
	
		if( original_width > max_width )
		
			original_ratio = max_width.to_f / original_width.to_f
		
			new_width = max_width
			new_height =  original_height * original_ratio
		
			resized_embed = embed.gsub(WIDTH_REGX, "width='#{new_width}'")
			resized_embed.gsub!(HEIGHT_REGX, "height='#{new_height}'")
		
		end
	
		return resized_embed
	
	end
	
end # class EmbededVideo
end # module Blogs
