lib_require :Blogs, 'blog_type'
lib_require :Blogs, 'embeded_video'

module Blogs
	class VideoBlog < BlogType
		init_storable(:usersdb, 'blogtype_video')
				
		extend TypeID

		def self.build(post, user, params)
			if (post.extra_content.nil?)
				video = VideoBlog.new
				video.userid = user.userid
				video.blogid = post.id
			else
				video = post.extra_content
			end
			video.embed = params["blog_post_video_embed", String, ""]

			return video
		end
		
		def blog_type
			return :video
		end
		
		def display(request)
			
			t = Template::instance('blogs', 'video_blog')			
			t.embed = Blogs::EmbededVideo.resize(self.embed, :blog)
			t.video_width = Blogs::EmbededVideo.width(t.embed)
			t.video_height = Blogs::EmbededVideo.height(t.embed)

			return t.display
			
		end
		
	end
end