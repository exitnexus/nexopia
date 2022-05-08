lib_want :Profile, "profile_block_query_info_module";
lib_require :Gallery, "gallery_profile_block"

module Gallery
	class GalleryProfileBlockHandler < PageHandler
		
		declare_handlers("profile_blocks/Gallery") {
			area :User
			access_level :Any
			page :GetRequest, :Full, :gallery, 'gallery', input(Integer)
			page :GetRequest, :Full, :galleryList, 'gallerylist', input(Integer)

			area :Self
			page :GetRequest, :Full, :gallery_edit, 'gallery', input(Integer), 'edit' 
			page :GetRequest, :Full, :gallery_edit, 'gallery', "new";
			
			handle	:PostRequest, :gallery_block_save, 'gallery', input(Integer), "save";
			handle	:PostRequest, :gallery_block_save, 'gallery', input(Integer), "create";
			handle	:PostRequest, :gallery_block_remove, 'gallery', input(Integer), "remove";


		}
		
		def gallery_block_remove(id)
			gpb = GalleryProfileBlock.find(:first, request.user.userid, id)
			gpb.delete;
		end
		
		def gallery_block_save(id)
			gpb = GalleryProfileBlock.find(:first, request.user.userid, id)
			if (gpb.nil?)
				gpb = GalleryProfileBlock.new
			end
			gpb.userid = request.user.userid;
			gpb.id = id
			gpb.galleryid = params['gallery_id', Integer]
			gpb.store;
		end
		
		def gallery_edit(*id)
			request.reply.headers['Content-Type'] = PageRequest::MimeType::PlainText;
			t = Template::instance("gallery", "gallery_block_edit")
			if (id.first)
				gpb = GalleryProfileBlock.find(:first, request.user.userid, id.first)
				t.selected_gallery_id = gpb.galleryid;
			else
				t.selected_gallery_id = nil;
			end
			puts t.display
		end
		
		def gallery(id)
			block = GalleryProfileBlock.find(:first, request.user.userid, id)
			
			if (request.user != request.session.user)
				if (block.gallery.permission == 'friends')
					return if (!request.user.friend? request.session.user)
				elsif (block.gallery.permission == 'loggedin')
					return if (request.session.user.anonymous)
				end
			end
			
			width = params['layout', Integer]
			
			
			t = Template::instance("gallery", "gallery_quick_view");
			t.user = request.user;
			t.form_key = form_key
			t.gallery = block.gallery;
			t.gallerypics = t.gallery.pics.map{|p|p}
			t.selected_gallery = block.galleryid
			reply.headers['X-width'] = 420;
			puts t.display();
				
		end
		
		def self.gallery_query(info)
			if(site_module_loaded?(:Profile))
				info.extend(ProfileBlockQueryInfo);
				info.title = "Gallery";
				info.initial_position = 20;
				info.initial_column = 0;
				info.multiple = false;
				info.max_number = 10;
				info.form_factor = :both;
			end
			return info;
		end

	
		def galleryList(ignored)
			t = Template::instance("gallery", "galleries_quick_view");
			t.user = request.user;
			
			t.galleries = request.user.galleries.select{|gallery|
				if (request.user == request.session.user)
					true
				else
					if (gallery.permission == 'friends') && (!request.user.friend? request.session.user)
						false
					elsif (gallery.permission == 'loggedin') && (request.session.user.anonymous)
						false
					else 
						true
					end
				end
			}
			t.form_key = form_key
			reply.headers['X-width'] = 177;
			puts t.display();
				
		end
		
		def self.galleryList_query(info)
			if(site_module_loaded?(:Profile))
				info.extend(ProfileBlockQueryInfo);
				info.title = "Galleries";
				info.initial_position = 20;
				info.initial_column = 0;
				info.editable = false;
				info.multiple = false;
				info.max_number = 1;
				info.form_factor = :both;
			end
			return info;
		end
	
	end
end