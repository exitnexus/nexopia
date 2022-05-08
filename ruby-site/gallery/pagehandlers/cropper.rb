lib_require :Core, "template/template"

module Gallery
	class Cropper < PageHandler
	
		declare_handlers("gallery/cropper") {
			area :Self
			page :GetRequest, :Full, :cropper, input(Integer) #picid
		}
		
		def cropper(picid)
			t = Template::instance('gallery', 'cropper')
			t.pic = Pic.find(request.user.userid, picid, :first)
			puts t.display
		end
		
	end
end