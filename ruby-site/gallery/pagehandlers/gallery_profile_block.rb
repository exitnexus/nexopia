lib_want :Profile, "profile_block_query_info_module";
lib_require :Gallery, "gallery_profile_block"

module Gallery
	class GalleryProfileBlockHandler < PageHandler
		
		declare_handlers("profile_blocks/Gallery/gallery") {
			area :User
			access_level :Any
			page :GetRequest, :Full, :gallery, input(Integer)
			
			area :Self
			page :GetRequest, :Full, :gallery_edit, input(Integer), 'edit' 
			page :GetRequest, :Full, :gallery_edit, "new";
			
			handle	:PostRequest, :gallery_block_save, input(Integer), "save";
			handle	:PostRequest, :gallery_block_save, input(Integer), "create";
			handle	:PostRequest, :gallery_block_remove, input(Integer), "remove";
			handle	:PostRequest, :visibility_save, input(Integer), "visibility";
		}
		
		def gallery_block_remove(id)
			gpb = GalleryProfileBlock.find(:first, request.user.userid, id)
			if(!gpb.nil?())
				gpb.delete();
			end
		end
		
		def gallery_block_save(id)
			gallery_id = params['gallery_id', Integer];
			
			# If there is no gallery id sent along, remove the profile display block
			#  which was created.
			if(gallery_id.nil?())
				Profile::ProfileDisplayBlock.find(:first, request.user.userid, id).delete;
				return;
			end
			
			# If the user is creating a new block and already has a strip containing that gallery, do nothing and delete the
			#  precreated profile display block. The user can not have two profile display blocks with the same gallery.
			if(id.nil?())
				gallery_block = GalleryProfileBlock.find(:first, request.user.userid, gallery_id, :index => :usergallery);
				if(!gallery_block.nil?())
					Profile::ProfileDisplayBlock.find(:first, request.user.userid, id).delete();
					return;
				end
			end
			
			gpb = GalleryProfileBlock.find(:first, request.user.userid, id);
			if (gpb.nil?)
				gpb = GalleryProfileBlock.new();
			end
			gpb.userid = request.user.userid;
			gpb.id = id;
			gpb.galleryid = gallery_id;
			gpb.store();			
		end
		
		def gallery_edit(block_id = nil)
			request.reply.headers['Content-Type'] = PageRequest::MimeType::PlainText;			
			t = Template::instance("gallery", "gallery_block_edit");
			gallery_profile_block_list = GalleryProfileBlock.find(:all, request.user.userid);
			gallery_list = request.user.galleries;
			
			gallery_block_id_list = gallery_profile_block_list.map{|gallery_block| gallery_block.galleryid};
			
			filtered_list = Array.new();
			gallery_list.each{|gallery|
				if(!gallery_block_id_list.index(gallery.id))
					filtered_list << gallery;
				end
			};
			
			if (block_id)
				gpb = GalleryProfileBlock.find(:first, request.user.userid, block_id);
				filtered_list << gpb.gallery;
				
				t.selected_gallery_id = gpb.galleryid;
			else
				t.selected_gallery_id = nil;
			end
			
			t.owner_user = request.user;
			t.filtered_galleries = filtered_list;
			print t.display();
		end
		
		def gallery(id)
			edit_mode = params["profile_edit_mode", Boolean, false];
		
			if(!Profile::ProfileDisplayBlock.verify_visibility(id, request.user, request.session.user, edit_mode))
				print "<h1>Not visible</h1>";
				return;
			end
			
			block = GalleryProfileBlock.find(:first, request.user.userid, id)
			
			if (request.user != request.session.user)
				if (block.gallery.permission == 'friends')
					if ((request.user != request.session.user) &&
						(!request.user.friend? request.session.user))
						return
					end
				elsif (block.gallery.permission == 'loggedin')
					return if (request.session.user.anonymous?())
				end
			end
			
			width = params['layout', Integer]
			
			
			t = Template::instance("gallery", "gallery_quick_view");
			t.user = request.user;
			t.form_key = form_key
			t.gallery = block.gallery;
			t.gallerypics = t.gallery.pics.map{|p|p}
			t.selected_gallery = block.galleryid

			# Don't display the gallery strip if there aren't any pictures in the gallery
			if (t.gallerypics.empty?)
				return;
			end

			puts t.display();		
		end
		
		def visibility_save(block_id)
			display_block = Profile::ProfileDisplayBlock.find(:first, request.session.user.userid, block_id);
			gallery_block = GalleryProfileBlock.find(:first,  request.session.user.userid, block_id);
			
			gallery_block.gallery.permission = GalleryProfileBlock.visibility_to_permission(display_block.visibility);
			gallery_block.gallery.store();
		end
		
		def self.gallery_query(info)
			if(site_module_loaded?(:Profile))
				info.extend(ProfileBlockQueryInfo);
				info.title = "Gallery Strip";
				info.initial_position = 20;
				info.initial_column = 1;
				info.multiple = true;
				info.max_number = 5;
				info.form_factor = :wide;
				info.add_visibility_exclude(:none);
				info.add_visibility_exclude(:friends_of_friends);
				
				# changes on a per user basis because it shows only galleries the reader has a right to read.
				# if we want to make it work well, we could make it only ever show public galleries (if any)
				info.content_cache_timeout = 0 
			end
			return info;
		end
	end
end