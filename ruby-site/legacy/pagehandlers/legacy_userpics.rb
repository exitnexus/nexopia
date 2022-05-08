lib_want :Gallery, "gallery_pic"

if (site_module_loaded? :Gallery)
	module Legacy
		class UserPicsForward < PageHandler
			declare_handlers("/") {
				area :Images
				handle :GetRequest, :userpic, "userpics", Integer, input(Integer), input(/([0-9]+)\.jpg/)
				handle :GetRequest, :userpic_thumb, "userpicsthumb", Integer, input(Integer), input(/([0-9]+)\.jpg/)
			}
		
			def userpic(userid, picid_match)
				picid = picid_match[1].to_i
				gallerypic = Gallery::Pic.find(:first, :userpic, userid, picid)
				if (gallerypic)
					rewrite(request.method, url/:galleryprofile/gallerypic.revision/userid/"#{gallerypic.id}.jpg")
				else
					raise PageError.new(404), "Not found."
				end
			end
			def userpic_thumb(userid, picid_match)
				picid = picid_match[1].to_i
				gallerypic = Gallery::Pic.find(:first, :userpic, userid, picid)
				if (gallerypic)
					rewrite(request.method, url/:gallerythumb/gallerypic.revision/userid/"#{gallerypic.id}.jpg")
				else
					raise PageError.new(404), "Not found."
				end
			end
		end
	end
end