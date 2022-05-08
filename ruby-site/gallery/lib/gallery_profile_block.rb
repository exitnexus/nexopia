lib_require :Core, "storable/cacheable"
lib_require :Gallery, "gallery_folder.rb"

class GalleryProfileBlock < Cacheable
	set_prefix("gallery_profile_block")
	init_storable(:usersdb, "galleryprofileblock")
	
	relation_singular :gallery, [:userid, :galleryid], Gallery::GalleryFolder
	
end