module Blogs
	class BlogType < Storable
		#return the html for displaying the extra content of the blog
		def display(request)
		end
		
		#create and store a new object based on the blog post, the user, and the params
		#provided.
		def self.build!(post, user, params)
		end
		
		#returns a symbol that describes the type of blog.
		def blog_type()
		end
	end
end
		