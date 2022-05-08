lib_require :Core, "storable/cacheable"
lib_require :Gallery, "gallery_folder"
lib_want :Profile, "profile_display_block"

class GalleryProfileBlock < Cacheable
	set_prefix("gallery_profile_block")
	init_storable(:usersdb, "galleryprofileblock")
	
	relation_singular :gallery, [:userid, :galleryid], Gallery::GalleryFolder
	if (site_module_loaded?(:Profile))
		relation_singular :profile_display_block, [:userid, :id], Profile::ProfileDisplayBlock
	end
	
	VISIBILITY_PERMISSION = {
		:all => "anyone",
		:friends => "friends",
		:logged_in => "loggedin",
		:none => "none"
	};
	
	def before_delete
		if (site_module_loaded?(:Profile))
			unless(self.profile_display_block.nil?)
				self.profile_display_block.delete
			end
		end
	end
	
	def self.update_visibility(gallery, owner)
		gallery_block = GalleryProfileBlock.find(:first, owner.userid, gallery.id, :index => :usergallery);
		if(gallery_block.nil?)
			return;
		end
		
		display_block = Profile::ProfileDisplayBlock.find(:first, owner.userid, gallery_block.id);
		if(!display_block.nil?())
			return;
		end
		
		display_block.visibility = GalleryProfileBlock.permission_to_visibility(gallery.permission);
		display_block.store();
	end
	
	def self.visibility_to_permission(visibility)
		return VISIBILITY_PERMISSION[visibility];
	end
	
	def self.permission_to_visibility(perm)
		VISIBILITY_PERMISSION.each{|visibility, permission|
				if(perm == permission)
					return visibility;
				end
			};
	end
end

module Gallery
	class GalleryFolder < Cacheable
		relation_singular :gallery_profile_block, [:ownerid, :id], GalleryProfileBlock, :usergallery
		prechain_method(:before_delete) {
			unless (self.gallery_profile_block.nil?)
				self.gallery_profile_block.delete
			end
		}
	end
end