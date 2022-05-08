lib_require :Core, "user_error"
lib_want :Gallery, "gallery_pic"

class BlogsModule < SiteModuleBase

	DEFAULT_BLOG_GALLERY_NAME = "Uploaded Blog Pictures"
	DEFAULT_BLOG_GALLERY_DESCRIPTION = "This album contains pictures you've uploaded to your blog. Any pictures you delete from this album will no longer show up in your blogs."
	ARCHIVED_BLOG_GALLERY_NAME = "Archived Blog Pictures"

	class << self 
		if (site_module_loaded?(:Gallery))
			def upload_handle(file, userid, params, original_filename)

				# if we don't have a gallery selected we need to find the 'Uploaded Profile Pictures'
				owner = User.get_by_id(userid)
				default_gallery = (owner.galleries.select { |gallery| gallery.name == DEFAULT_BLOG_GALLERY_NAME})[0]

				# If Uploaded Profile Pictures exists and is full, rename it and create a new one.
				max_pics = (owner.plus? ? Gallery::Pic::PLUS_MAX_PICS_PER_GALLERY : Gallery::Pic::MAX_PICS_PER_GALLERY)
				if(!default_gallery.nil?)
					if(default_gallery.pics.length + 1 >= max_pics )
						default_gallery.name = ARCHIVED_BLOG_GALLERY_NAME
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
					gallery.name = DEFAULT_BLOG_GALLERY_NAME
					gallery.description = DEFAULT_BLOG_GALLERY_DESCRIPTION
					gallery.permission = "friends";
					gallery.store

					galleryid = gallery.id
				else
					galleryid = default_gallery.id
				end

				gallery_pic = Gallery::GalleryHelper.store_pic(file, userid, galleryid, "");
				params.to_hash["pic_id"] = gallery_pic.id

				t = Template::instance("blogs", "photo_link")
				t.pic = gallery_pic
				t.photo_id = params["photo_id"]
				t.valid = 1

				return t.display
			end # upload_handle(file, userid, params, original_filename)
		end

	end # class << self 
end # class BlogsModule < SiteModuleBase
