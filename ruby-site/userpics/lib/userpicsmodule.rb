lib_require :Core, "user_error"
lib_require :Gallery, "gallery_pic"

class UserpicsModule < SiteModuleBase

	DEFAULT_PROFILE_PICTURES_GALLERY_NAME = "Uploaded Profile Pictures"
	DEFAULT_PROFILE_PICTURES_GALLERY_DESCRIPTION = "This album contains pictures you've uploaded to your profile using the profile picture uploader. Any pictures you delete from this album will also be deleted from your profile page."
	ARCHIVED_PROFILE_PICTURES_GALLERY_NAME = "Archived Profile Pictures"
	def after_load()
		lib_require :Userpics, "pic_queues"
	end

	class << self 
		def upload_handle(file, userid, params, original_filename)

			$log.info "Handling profile picture upload #{file.path}", :spam, :userpics
		
			if params['selected_gallery'].kind_of?(Array)
				galleryid = params['selected_gallery'].first.to_i
			else
				galleryid = params['selected_gallery'].to_i
			end
			
			# if we don't have a gallery selected we need to find the 'Uploaded Profile Pictures'
			if (galleryid == -1)

				owner = User.get_by_id(userid)
				default_gallery = (owner.galleries.select { |gallery| gallery.name == "Uploaded Profile Pictures"})[0]
				
				# If Uploaded Profile Pictures exists and is full, rename it and create a new one.
				max_pics = (owner.plus? ? Gallery::Pic::PLUS_MAX_PICS_PER_GALLERY : Gallery::Pic::MAX_PICS_PER_GALLERY)
				if(!default_gallery.nil?)
				  if(default_gallery.pics.length + 1 >= max_pics )
				    default_gallery.name = ARCHIVED_PROFILE_PICTURES_GALLERY_NAME
				    default_gallery.store
				    default_gallery = nil
			    end
			  end

				# If the user doesn't have a default gallery, we'll create one for them.
				# Otherwise use the gallery id of the gallery we found.
				if (default_gallery.nil?)
					gallery = Gallery::GalleryFolder.new();
					gallery.ownerid = userid;
					gallery.id = Gallery::GalleryFolder.get_seq_id(userid)
					gallery.name = DEFAULT_PROFILE_PICTURES_GALLERY_NAME
					gallery.description = DEFAULT_PROFILE_PICTURES_GALLERY_DESCRIPTION
					gallery.permission = "friends";
					gallery.store
					
					galleryid = gallery.id
				else
					galleryid = default_gallery.id
				end
			end
			
			gallery_pic = Gallery::GalleryHelper.store_pic(file, userid, galleryid, "");
			params.to_hash["pic_id"] = gallery_pic.id

			Pics.insert_profile_pic(userid, gallery_pic)

			return true
			
		end # upload_handle(file, userid, params, original_filename)
		
	end # class << self 
end # class UserpicsModule < SiteModuleBase
