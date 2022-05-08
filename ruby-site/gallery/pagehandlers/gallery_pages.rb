lib_require :Core, "template/template"
lib_want :Userpics, "pics"

module Gallery
	class GalleryPage < PageHandler
		PAGE_WIDTH = 665
		COLUMNS = 6 #number of columns in the gallery table.
		ROWS = 4 #number of rows in the gallery table.
		EDIT_BOX_SIZE = 4 #number of columns the edit box should take up.
		
		declare_handlers("gallery") {
			area :Internal
			page :GetRequest, :Full, :gallery_select, "select"
			page :GetRequest, :Full, :gallery_select, "select", input(Integer)
			
			area :Self
			access_level :IsUser, CoreModule, :editgallery
	
			page   :GetRequest, :Full, :gallery_header_page
			page   :GetRequest, :Full, :create, "create"
			page   :GetRequest, :Full, :gallery_page, input(Integer)
			page   :GetRequest, :Full, :upload_page, "upload"
			page   :GetRequest, :Full, :upload_page, "upload", input(Integer)
			page   :GetRequest, :Full, :pic, "pic", input(Integer)
			page   :GetRequest, :Full, :edit_gallery, "edit_gallery", input(Integer)
			page   :GetRequest, :Full, :edit_pic, "edit_pic", input(Integer)
			page   :GetRequest, :Full, :gallery_header_page, "uploadfinished"
			page   :GetRequest, :Full, :edit_pic_panel, "edit_pic_panel", input(Integer)
			
			handle :GetRequest, :getpic, "pic", input(/.*\.jpg/)
			
			#JSON request handlers
			handle :GetRequest, :jsonpic, "jsonpic", input(Integer)
			handle :GetRequest, :jsonuser, "jsonuser"
			
			handle :PostRequest, :submit_gallery_edit, "submit_gallery_edit", input(Integer)
		 	handle :PostRequest, :delete, "delete", input(Integer)
			handle :PostRequest, :change_gallery_page, "change_gallery"
			handle :PostRequest, :delete_group, "delete_group"
			page   :PostRequest, :Full, :delete_gallery_page, "delete_gallery", input(Integer)
			handle :PostRequest, :move_pic, input(Integer), "move_pic"
			handle :PostRequest, :album_cover, "album_cover"
			handle :PostRequest, :set_as_profile_pic, "profile", "pic", "set"
			handle :PostRequest, :move_profile_pic, "profile", "pic", "move"
			handle :PostRequest, :delete_profile_pic, "profile", "pic", "delete", input(Integer)
			handle :PostRequest, :crop, "crop", input(Integer)

			handle :PostRequest, :update_pic_description, "pic", "update_description"
			page   :PostRequest, :Full, :action, "action", input(Integer)
			page   :PostRequest, :Full, :gallery_action, "gallery_action"
			page   :PostRequest, :Full, :submit_create, "submit_create"
			handle :PostRequest, :ajax_create, "ajax_create"
		
			handle :PostRequest, :upload_transfer, "upload", "send"
		}
		
		def jsonuser()
			lib_require :json, "exported"
			reply.headers['Content-Type'] = PageRequest::MimeType::PlainText
			puts request.user.to_json
		end
		
		def jsonpic(id)
			lib_require :json, "exported"
			pic = Gallery::Pic.find(:first, request.user.userid, id)
			reply.headers['Content-Type'] = PageRequest::MimeType::PlainText
			puts pic.to_json
		end
		
		def getpic(id)
			id = id.to_s
			userid = request.user.userid;
			rewrite(request.method, url/:gallery/(userid/1000)/userid/id, nil, :Images)
		end
		
		
		#delete the profile pic with the specified priority
		#does not remove the underlying gallery pic
		def delete_profile_pic(priority)
			pic = request.user.pic_slots[priority-1]
			if (!pic.empty?)
				pic.delete
			end
			reply.headers['Content-Type'] = PageRequest::MimeType::PlainText
			manage_profile_pictures
		end
		
		def crop(id)
			x,y,w,h = *[params["x", Float],params["y", Float],params["w", Float],params["h", Float]]
			
			pic = Gallery::Pic.find(:first, request.user.userid, id);
			pic.crop.delete if !pic.crop.nil?
			crop = Gallery::Crop.new()
			crop.userid = request.user.userid;
			crop.gallerypicid = id;
			crop.x = x;
			crop.y = y;
			crop.w = w;
			crop.h = h;
			crop.store;
			
			["gallerysquare","gallerylandscape","gallerylandscapethumb","gallerylandscapemini","gallerysquaremini"].each{|gen_class|
				f = NexFile.load(gen_class, pic.userid, "#{pic.id}.jpg");
				f.delete if (f)
			}

			$log.info "Cropping to #{x},#{y} x #{w},#{h}", :critical
		end
		
		def edit_pic_panel(id)
			pic = Pic.find(:first, request.user.userid, id);
			reply.headers['Content-Type'] = PageRequest::MimeType::PlainText
			t = Template::instance("gallery", "gallery_edit_panel")
			puts t.display
		end
		
		#takes params gallery_pic_id and priority
		#sets the picture as a profile pic with the given priority
		#if picture is already a pic with a different priority it repositions it
		#if a profile pic already exists with that priority it is removed
		def set_as_profile_pic
			gallery_pic_id = params["gallery_pic_id", Integer]
			priority = params["priority", Integer]
			
			#if the gallery pic we are using is already a profile pic delete it (a picture should only be in one profile pic slot)
			current_profile_pic = Pics.find(:first, request.user.userid, gallery_pic_id)
			if (current_profile_pic)
				current_profile_pic.delete
			end
			
			#if there is already a profile pic in the slot we're using lets replace it, otherwise create a new one
			replace_profile_pic = Pics.find(:first, :priority, request.user.userid, priority)
			if (!replace_profile_pic.nil?)
				replace_profile_pic.delete;
			end
			gallery_pic = Gallery::Pic.find(:first, session.user.userid, gallery_pic_id.to_i)
			UserpicsModule.set_as_userpic(session.user.userid, gallery_pic_id, gallery_pic.get_source, "My new pic", false, priority)

			reply.headers['Content-Type'] = PageRequest::MimeType::PlainText
			manage_profile_pictures
		end
		
		#takes params target_priority and priority
		#moves the picture from priority to target_priority, shifting all pictures in between up or down by 1 as apprioriate
		def move_profile_pic
			priority = params["priority", Integer]
			target_priority = params["target_priority", Integer]
			if (priority && target_priority)
				Pics.shift(request.user.userid, priority, target_priority)
			end
		end

		#display the manage profile pictures section
		def manage_profile_pictures
			t = Template::instance("gallery", "manage_profile_pictures")
			t.form_key = form_key
			t.user = request.user
			puts t.display
		end

		def album_cover
			id = params["id", Integer]
			if (id)
				pic = Pic.find(:first, request.user.userid, id)
				pic.gallery.previewpicture = id
				pic.gallery.store
			end
			reply.headers['Content-Type'] = PageRequest::MimeType::PlainText
			index_page
			edit_gallery(gallery.id)
		end
		
		def update_pic_description
			picid = params["id", Integer]
			description = params["description", String]
			if (picid)
				pic = Pic.find(:first, request.user.userid, picid)
				if (pic)
					pic.description = description
					pic.store
				end
			end
		end
		
		def move_pic(galleryid)
			gallery = GalleryFolder.get_by_id(session.user.userid, galleryid);
			id = params["id", Integer]
			priority = params["position", Integer]
			if (id && priority)
				pic = Pic.find(:first, session.user.userid, id.to_i)
				if (priority > 0)
					gallery.move_pic(pic, priority)
				end
			end
		end
		
		def delete(picid)
			pic = Pic.find(:first, request.user.userid, picid);
			galleryid = pic.galleryid if (pic)
			pic.delete;
			reply.headers['Content-Type'] = PageRequest::MimeType::PlainText
			index_page
			edit_gallery(galleryid)
		end
		
		def delete_group()
			ids = params['ids', String]
			ids = ids.split(',')
			ids = ids.map {|id| [request.user.userid, id.to_i]}
			pics = Pic.find(*ids);
			galleryid = pics.first.galleryid if (pics)
			pics.each {|pic|
				pic.delete
			}
			reply.headers['Content-Type'] = PageRequest::MimeType::PlainText
			index_page
			edit_gallery(galleryid)
		end
			
		
		def delete_gallery_page(galleryid)
			delete_gallery(galleryid)
		end
		
		def change_gallery_page()
			target = params["targetgallery", Integer]
			ids = params["ids", String].split(',').map {|id| id.to_i}
			change_gallery(ids, target)
			reply.headers['Content-Type'] = PageRequest::MimeType::PlainText
			index_page
			edit_gallery(params["galleryid", Integer])
		end
		
		def gallery_action()
			action(-1);
		end

		
		def action(galleryid)
			pics = params["picture", TypeSafeHash];
			actions = params["function", TypeSafeHash];
			
			if actions
				actions.each_key(){|action|
					if (action === "move")
						ids = params["picture", TypeSafeHash]
						target = params["selected_gallery", Integer];
						change_gallery(ids, target)
					elsif (action === "delete")
						ids = params["picture", TypeSafeHash]
						ids.each{|id|
							pic = Pic.find(:first, session.user.userid, id);
							pic.delete
						}
						ids = actions["delete", TypeSafeHash]
					elsif (action === "deletepic")
						id = actions["deletepic", TypeSafeHash].keys.first
						pic = Pic.find(:first, session.user.userid, id.to_i);
						pic.delete
					elsif (action === "movepicup")
						gallery = GalleryFolder.get_by_id(session.user.userid, galleryid);
						id = actions["movepicup", TypeSafeHash].keys.first
						pic = Pic.find(:first, session.user.userid, id.to_i);
						gallery.move_pic(pic, pic.priority - 1)
					elsif (action === "movepicdown")
						gallery = GalleryFolder.get_by_id(session.user.userid, galleryid);
						id = actions["movepicdown", TypeSafeHash].keys.first
						pic = Pic.find(:first, session.user.userid, id.to_i);
						gallery.move_pic(pic, pic.priority + 1)
					elsif (action === "deletegallery")
						id = actions["deletegallery", TypeSafeHash].keys.first
						delete_gallery(id);
					elsif (action == "cover")
						gallery = GalleryFolder.get_by_id(session.user.userid, galleryid);
						id = params["picture", TypeSafeHash].keys.first;
						gallery.previewpicture = id;
						gallery.store;
					elsif (action === "upload")
						galleryhash = actions[action, TypeSafeHash];
						if (galleryhash)
							site_redirect("/gallery/upload/#{galleryhash.keys.first}")
						else
							site_redirect("/gallery/upload")
						end
					elsif (action === "setuserpic")
						id = actions["setuserpic", TypeSafeHash].keys.first
						gallery_pic = Gallery::Pic.find(:first, session.user.userid, id.to_i)
						UserpicsModule.set_as_userpic(session.user.userid, id, gallery_pic.get_source, "My new pic", false)
					end
				}
			end
			if galleryid > -1
				site_redirect("/gallery/#{galleryid}")
			else
				site_redirect("/gallery/")
			end
		end
		
		def submit_gallery_edit(galleryid)
			gallery = GalleryFolder.get_by_id(request.user.userid, galleryid);
			gallery.name = params["name", String].to_s;
			gallery.description = params["description", String].to_s;
			begin
				gallery.permission = params["permission", String]
			rescue
				#if the string is not a valid enum entry this will catch the error
			end
			gallery.store;
			if (params["ajax", String] == "true")
				reply.headers['Content-Type'] = PageRequest::MimeType::PlainText;
				edit_gallery(galleryid)
				index_page
			else
				site_redirect("/gallery/#{galleryid}")
			end
		end
		
		def create()
			t = Template::instance("gallery", "create");
			puts t.display();		
		end
		
		def submit_create()
			create_gallery();
			site_redirect("/gallery/")
		end
		
		def ajax_create()
			gallery = create_gallery
			reply.headers['Content-Type'] = PageRequest::MimeType::PlainText;
			gallery_select(gallery.id);
			index_page
		end
		
		def index_page()
			t = Template::instance("gallery", "index");
			t.user = request.user;
			reply.headers['X-width'] = PAGE_WIDTH;
			puts t.display();
		end
	
		def gallery_header_page
			t = Template::instance("gallery", "gallery_header_view");
			if (params["Errors", String])
				t.error = params["Errors", String]
			end
			t.user = request.user;
			reply.headers['X-width'] = PAGE_WIDTH;
			puts t.display();
		end
		
		def gallery_page(n)
			t = Template::instance("gallery", "gallery_full_view");
			t.user = request.user;
			t.form_key = form_key
			t.gallery = GalleryFolder.get_by_id(request.user.userid, n);
			t.gallerypics = [];
			t.gallery.pics.each_with_index { |pic, i|
				current_page = (i+EDIT_BOX_SIZE)/(COLUMNS*ROWS)
				current_row = (i+EDIT_BOX_SIZE)/COLUMNS
				t.gallerypics[current_page] ||= []
				t.gallerypics[current_page][current_row] ||= [];
				t.gallerypics[current_page][current_row] << pic;
			}
			t.selected_gallery = n
			
			reply.headers['X-width'] = PAGE_WIDTH;
			puts t.display();
		end
		
		def upload_page(selected_gallery=nil)
			reply.headers["X-width"] = PAGE_WIDTH;
			t = Template::instance("gallery", "upload");
			t.selected_gallery = selected_gallery
			t.session = request.session.encrypt(Time.now)
			puts t.display
		end
		
		def gallery_select(selected_gallery=nil)
			t = Template::instance("gallery", "select")
			t.first_option = params["first_option", String]
			t.selected_gallery = selected_gallery
			t.galleries = request.user.galleries_sorted_by_name
			puts t.display
		end
		
		def edit_gallery(id)
			t = Template::instance("gallery", "edit_gallery");
			t.user = request.user;
			t.gallery = GalleryFolder.get_by_id(request.user.userid, id);
			reply.headers['X-width'] = PAGE_WIDTH;
			puts t.display();
		end
		
		def pic(id)
			t = Template::instance("gallery", "pic");
			t.user = request.user;
			t.pic = Pic.find(:first, request.user.userid, id);
			reply.headers['X-width'] = PAGE_WIDTH;
			puts t.display();
		end
		
		def edit_pic(id)
			pic = Pic.find(:first, request.user.userid, id);
			pic.description = params["description", String];
			pic.store;
			site_redirect("/gallery/#{pic.galleryid}")
		end
		
		def upload_transfer
			site_redirect("/my/gallery/")
		end
		
		private
		def create_gallery
			gallery = GalleryFolder.new();
			gallery.ownerid = request.user.userid;
			gallery.id = GalleryFolder.get_seq_id(request.user.userid)
			gallery.name = params["name", String]
			gallery.description = params["description", String]
			gallery.permission = params["permission", String]
			gallery.store
			return gallery
		end
		
		def delete_gallery(id)
			reply.headers['Content-Type'] = PageRequest::MimeType::PlainText
			gallery = GalleryFolder.find(:first, session.user.userid, id.to_i);
			gallery.delete
		end
		
		def change_gallery(ids, target_gallery_id)
			ids = ids.map{|id| id.to_i}
			initial_gallery_id = 0
			ids.each{|id|
				pic = Pic.find(:first, session.user.userid, id);
				initial_gallery_id = pic.galleryid
				pic.galleryid = target_gallery_id
				pic.move_to_end
				pic.store
			}
			initial_gallery = GalleryFolder.find(:first, request.user.userid, initial_gallery_id)
			target_gallery = GalleryFolder.find(:first, request.user.userid, target_gallery_id)
			initial_gallery.fix_cover!(ids) unless initial_gallery.nil?
			target_gallery.fix_cover! unless target_gallery.nil?
		end
	end
end
