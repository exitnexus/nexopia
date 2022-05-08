lib_require :Json, "exported"
lib_require :Gallery, 'gallery_helper'
lib_require :Gallery, "gallery_folder"
lib_require :Gallery, "gallery_pic"

class GalleryModule < SiteModuleBase
	
	def after_load()
		lib_require :Gallery, "gallery_queues", 'gallery_folder', 'gallery_comment'
	end
	
	set_javascript_dependencies([SiteModuleBase.get(:Json)])
	class <<self
		def upload_handle(file, userid, params, original_filename)
			
			$log.info("Handling gallery upload #{file.path}", :spam, :gallery)
			description = params['description'] || ""
		
			if params['selected_gallery'].kind_of?(Array)
				galleryid = params['selected_gallery'].first.to_i
			else
				galleryid = params['selected_gallery'].to_i
			end
			
			Gallery::GalleryHelper.store_pic(file, userid, galleryid, description);

			return true
		end
	end
end
