lib_require :Blogs, 'blog_type'

module Blogs
	class PhotoBlog < BlogType
		set_enums(
			:size => {
				:size_original => 0,
				:size_100 => 1,
				:size_75 => 2,
				:size_50 => 3,
				:size_25 => 4
			},
			:align => {
				:center => 0,
				:left => 1,
				:right => 2
			}
		)
		
		init_storable(:usersdb, 'blogtype_photo')
		
		SIZE_PERCENTAGES = {
			:size_original => "",
			:size_100 => "98%",
			:size_75 => "75%",
			:size_50 => "50%",
			:size_25 => "25%"
		}
		
		ALIGN_STYLES = {
			:center => "margin-left:auto;margin-right:auto;",
			:left => "margin-right:auto;",
			:right => "margin-left:auto;"
		}
		
		extend TypeID
		
		def display(request)
			t = Template::instance('blogs', 'photo_blog')
			t.photo_blog = self
			return t.display
		end
		
		def size_percent
			return SIZE_PERCENTAGES[self.size]
		end
		
		def align_style
			return ALIGN_STYLES[self.align]
		end
		
		def self.build(post, user, params)
			if (post.extra_content.nil?)
				photo = PhotoBlog.new
				photo.userid = user.userid
				photo.blogid = post.id
			else
				photo = post.extra_content
			end
			photo.link = params["blog_post_photo_link", String, ""]
			photo.size = params["blog_post_photo_size", Integer, 0]
			photo.align = params["blog_post_photo_align", Integer, 0]
			return photo
		end
		
		def blog_type
			return :photo
		end
		
	end
end