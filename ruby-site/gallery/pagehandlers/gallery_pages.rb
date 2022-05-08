lib_require :Core, "template/template", 'info_messages'
lib_require	:Gallery, "gallery_profile_block";
lib_want :Userpics, "pics", "userpics_helper"

module Gallery
	class GalleryPage < PageHandler
		PAGE_WIDTH = 0;
		COLUMNS = 6 #number of columns in the gallery table.
		ROWS = 4 #number of rows in the gallery table.
		EDIT_BOX_SIZE = 0 #number of columns the edit box should take up.
		
		declare_handlers("gallery") {
			area :Internal
			page :GetRequest, :Full, :gallery_select, "select"
			page :GetRequest, :Full, :gallery_select, "select", input(Integer)
			
			area :Self
			access_level :IsUser, CoreModule, :editgallery
			handle :PostRequest, :move_pic, input(Integer), "move_pic"
			handle :PostRequest, :crop, "pic", input(Integer), "crop"
			handle :PostRequest, :sign, "pic", input(Integer), "sign"
			handle :PostRequest, :description, "pic", input(Integer), "description"
			handle :PostRequest, :make_profile_pic, "make_profile_pic"
			handle :PostRequest, :profile_picture, "pic", input(Integer), "profile_picture"
			handle :PostRequest, :delete_picture, "pic", input(Integer), "delete"
			handle :PostRequest, :album_cover, "pic", input(Integer), "album_cover"
			handle :PostRequest, :album_cover, "album_cover" # XXX: should probably clean this up.
			handle :PostRequest, :upload_transfer, "upload", "send"
			handle :PostRequest, :ajax_create, "ajax_create"
			handle :PostRequest, :change_gallery_page, "change_gallery"
			page   :GetRequest, :Full, :upload_page, "upload"
			page   :GetRequest, :Full, :upload_page, "upload", input(Integer)
			page   :GetRequest, :Full, :redirect_to_user_gallery, "uploadfinished"
			page   :PostRequest, :Full, :submit_create, "submit_create"
			page   :PostRequest, :Full, :delete_gallery_page, "delete_gallery", input(Integer)
			handle :GetRequest, :add_profile_pic, "add_profile_pic"
			handle :GetRequest, :profile_pic_uploaded, "profile_pic_uploaded"

			handle :PostRequest, :update_gallery, "update", input(Integer)
			page   :GetRequest, :Full, :redirect_to_user_gallery
			page   :GetRequest, :Full, :create_gallery_popup, "create_gallery_popup"
			page   :GetRequest, :Full, :create, "create"
			handle :PostRequest, :submit_create_gallery, "submit_create_gallery"
			page   :GetRequest, :Full, :gallery_page, input(Integer)
			handle :GetRequest, :edit_picture_panel, "pic", input(Integer), "edit"
			handle :GetRequest, :edit_first_picture_panel, "pic", 'first', "edit"
			page   :GetRequest, :Full, :edit_gallery, "edit_gallery", input(Integer)
			page   :GetRequest, :Full, :edit_pic, "edit_pic", input(Integer)
			
			handle :PostRequest, :delete_group, "delete_group"
			handle :PostRequest, :submit_abuse_log, "submit_abuse_log", input(Integer)
		}
		
		def profile_pic_uploaded()
 			puts params["pic_id", Integer]
		end
		
		# Popup page for the profile editor that allows the user to add a single profile pic.
		def add_profile_pic()
			t = Template::instance("gallery", "add_profile_pic")
			request.reply.headers["X-width"] = 0
			puts t.display						
		end

		def redirect_to_user_gallery
			site_redirect(url/request.user.username/:gallery, :User)
		end

		def edit_picture_panel(id)
			errors = InfoMessages.display_errors {
				t = Template::instance('gallery', 'edit_picture_panel')
				t.functions = picture_panel_functions(params["function_panel", String, "gallery"])
				t.pic = Gallery::Pic.find(request.user.userid, id, :first)
				t.form_key = form_key
				raise "Picture does not exist." if (!t.pic)
				puts t.display
			}
			request.reply.headers['Status'] = 404 if (errors)
		end

		def edit_first_picture_panel
			edit_picture_panel(request.user.pics.first.gallerypicid)
		end
		
		def submit_abuse_log(id)
			if (request.impersonation?)
				abuse_log_entry = params["abuse_log_entry", String, nil];
				abuse_log_subject = params["abuse_log_subject", String, nil];
				abuse_log_reason = params["abuse_log_reason", String, nil];
				if (abuse_log_reason || abuse_log_subject || abuse_log_entry)
					AbuseLog.make_entry(request.session.user.userid, request.user.userid, 
						AbuseLog::ABUSE_ACTION_EDIT_GALLERY, abuse_log_reason, abuse_log_subject, abuse_log_entry);
				end
			end
		end
				
		def crop(id)
			x = params["x", Float, 0]
			y = params["y", Float, 0]
			w = params["w", Float, 0]
			h = params["h", Float, 0]
			
			pic = Gallery::Pic.find(:first, request.user.userid, id);
			errors = InfoMessages.capture_errors {
  			raise "Attempted to crop a non-existent image." if pic.nil?
  			pic.crop.delete if !pic.crop.nil?
  			crop = Gallery::Crop.new()
  			crop.userid = request.user.userid;
  			crop.gallerypicid = id;
  			crop.x = x;
  			crop.y = y;
  			crop.w = w;
  			crop.h = h;
  			crop.store;
  			if (request.impersonation?)
  				$log.info "#{PageRequest.current.session.user.username} changed the crop of picture #{id} from #{request.user.username}'s gallery.", :info, :admin
  			end

  			source = SourceFileType.new(pic.userid, pic.id)
  			source.remove_generated()

				# since we changed the image update the revision.
				pic.revision += 1
				pic.store

  			t = Template::instance("gallery", "edit_photo_panel_image")
  			t.pic = pic
  			t.crop = crop
  			puts t.display
			}
			if (errors)
			  request.reply.headers['Status'] = 500
			  puts errors.html
		  end
		end

		#display the manage profile pictures section
		def manage_profile_pictures
			t = Template::instance("gallery", "manage_profile_pictures")
			t.form_key = form_key
			t.user = request.user
			puts t.display
		end

		#add a pic to the sign pic mod queue
		def sign(gallerypicid)
			pic = Gallery::Pic.find(request.user.userid, gallerypicid, :first)
			if (pic && pic.signpic == :unmoderated)
				Userpics::SignPicsQueue.add_item(request.user.userid, gallerypicid, true);
				pic.signpic = :pending
				pic.store
				if (request.impersonation?)
					$log.info "#{PageRequest.current.session.user.username} made picture #{gallerypicid} a sign pic from #{request.user.username}'s gallery.", :info, :admin
				end
			end
			t = Template::instance('gallery', 'edit_picture_panel_functions')
			t.functions = picture_panel_functions(params["function_panel", String, "profile_picture"])
			t.pic = pic
			puts t.display
		end

		def picture_panel_functions(panel_type)
			case panel_type
			when "gallery"
				return [:album_cover, :profile_picture, :delete]
			when "profile_picture"
				return [:sign_pic]
			else
				return [:album_cover, :profile_picture, :delete]
			end
		end
		private :picture_panel_functions

		def album_cover(id=nil)
			if (!id)
				id = params["id", Integer]
			end
			if (id)
				pic = Pic.find(:first, request.user.userid, id)
				pic.gallery.previewpicture = id
				pic.gallery.store
				reply.headers['Content-Type'] = PageRequest::MimeType::PlainText
				album_info(pic.galleryid)
				if (request.impersonation?)
					$log.info "#{PageRequest.current.session.user.username} changed the album cover of gallery #{pic.galleryid} to #{id} in #{request.user.username}'s gallery.", :info, :admin
				end
			end
		end

		def make_profile_pic(id=nil)
			if (!id)
				id = params["id", Integer]
			end
			if (id)
				gallery_pic = Gallery::Pic.find(:first, request.user.userid, id)
			
				errors = InfoMessages.capture_errors {
					Pics.insert_profile_pic(gallery_pic.userid, gallery_pic)
				}
				if (errors)
					request.reply.headers['Status'] = 500
					puts errors.html
				end
			
				if (request.impersonation?)
					$log.info "#{PageRequest.current.session.user.username} made gallerypic #{id} a profile pic for #{request.user.username}", :info, :admin
				end
			end
		end

		def profile_picture(id)
			gallery_pic = Gallery::Pic.find(request.user.userid, id, :first) #lets make sure the picture actually exists
			if (site_module_loaded?(:Userpics))
				errors = InfoMessages.display_errors {
					Pics.insert_profile_pic(gallery_pic.userid, gallery_pic)
				}
			end
			if (errors)
				request.reply.headers['Status'] = 500
			else
				if (request.impersonation?)
					$log.info "#{PageRequest.current.session.user.username} made gallery pic #{id} a profile pic for #{request.user.username}.", :info, :admin
				end				
			end
		end

		def description(picid)
			if (request.impersonation?)
				$log.info "#{PageRequest.current.session.user.username} changed the description of picture #{picid} from #{request.user.username}'s gallery.", :info, :admin
			end

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
					if (request.impersonation?)
						$log.info "#{PageRequest.current.session.user.username} moved picture #{id} from #{request.user.username}'s gallery.", :info, :admin
					end
				end
			end
		end
		
		def delete_picture(id)
			pic = Gallery::Pic.find(request.user.userid, id, :first)
			if (pic)
				galleryid = pic.galleryid
				pic.delete
				if (request.impersonation?)
					$log.info "#{request.session.user.username} deleted image #{id} from #{request.user.username}'s gallery", :info, :admin
				end
				reply.headers['Content-Type'] = PageRequest::MimeType::PlainText
				source = params['source', String]
			  if(source == "film_view")
			    load_filmview(galleryid)
			  else
  				album_info(galleryid)
  				edit_gallery(galleryid)
			  end
			end
		end
		
		def load_filmview(galleryid)
		  site_redirect(url/request.user.username/:gallery/galleryid, :User)
	  end
		
		def delete_group()
			ids = params['ids', String]
			ids = ids.split(',')
			ids = ids.map {|id| [request.user.userid, id.to_i]}

			if ( ids.length > 0 )
				pics = Pic.find(*ids);
				galleryid = pics.first.galleryid if (pics)
				pics.each {|pic|
					pic.delete
				}
				if (request.impersonation?)
					$log.info "#{request.session.user.username} deleted images [#{ids.join(',')}] from #{request.user.username}'s gallery", :info, :admin
				end
				reply.headers['Content-Type'] = PageRequest::MimeType::PlainText
				album_info(galleryid)
				edit_gallery(galleryid)
			end
		end
			
		
		def delete_gallery_page(galleryid)
			delete_gallery(galleryid)
		end
		
		# Moves multiple images from one gallery to another.
		def change_gallery_page()
			target = params["targetgallery", Integer]
			ids = params["ids", String].split(',').map {|id| id.to_i}

			reply.headers['Content-Type'] = PageRequest::MimeType::PlainText
			errors = InfoMessages.display_errors {change_gallery(ids, target)}
			
			if (errors)
				request.reply.headers['Status'] = 403
			else
				if (request.impersonation?)
					$log.info "#{request.session.user.username} moved images [#{ids.join(',')}] to #{request.user.username}'s gallery #{target}", :info, :admin
				end
			end
			edit_gallery(params["galleryid", Integer])
			album_info(params["galleryid", Integer])
		end
		
		def update_gallery(galleryid)
			gallery = GalleryFolder.get_by_id(request.user.userid, galleryid);
			gallery.name = params["name", String].to_s;
			gallery.description = params["description", String].to_s;
			begin
				gallery.permission = params["permission", String]
			rescue => e
				#if the string is not a valid enum entry this will catch the error
				$log.info "Rescued, #{e.inspect}"
			end
			gallery.allowcomments = !params["allow_comments", String].nil? ? true : false
			
			gallery.store;
			
			if (request.impersonation?)
				$log.info "#{request.session.user.username} updated properties in gallery #{galleryid} for #{request.user.username}", :info, :admin
			end
			
			GalleryProfileBlock.update_visibility(gallery, request.session.user);
			
			if (params["refresh", String] == "album_info")
				album_info(galleryid)
			else
				site_redirect("/gallery/#{galleryid}")
			end
			
		end
		
		# Page to create a new album and upload pictures.
		def create()
			request.reply.headers["X-width"] = 0
			t = Template::instance("gallery", "create")			
			puts t.display
		end
		
		# Quick create popup for galleries
		def create_gallery_popup()
			t = Template::instance("gallery", "create_gallery_popup")
			puts t.display()		
		end

		# This function deals with create album requests from the create album popup.
		def ajax_create()
			request.reply.headers['Content-Type'] = PageRequest::MimeType::PlainText

			error = InfoMessages.display_errors {
				gallery = create_gallery()				
				gallery_select(gallery.id)
			}
			
			if (error)
				request.reply.headers['Status'] = 403
			else
				if (request.impersonation?)
					$log.info "#{PageRequest.current.session.user.username} created gallery #{gallery.id} as #{request.user.username}.", :info, :admin
				end
			end
		end
		
		def submit_create_gallery()
			gallery = create_gallery()
			
			site_redirect(url/:gallery/:upload/gallery.id)
		end
		
		# Create a new gallery object with the params passed it and save it to the database.
		def create_gallery()
			gallery = GalleryFolder.new()
			gallery.ownerid = request.user.userid
			gallery.id = GalleryFolder.get_seq_id(request.user.userid)
			gallery.name = params["name", String]
			gallery.description = params["description", String]
			gallery.permission = params["permission", String]
			gallery.allowcomments = !params["allow_comments", String].nil? ? true : false
			gallery.store
			
			if (request.impersonation?)
				$log.info "#{request.session.user.username} created gallery #{gallery.id} as #{request.user.username}", :info, :admin
			end
			
			return gallery
		end
		
		def gallery_page(n)
			
			gallery = GalleryFolder.get_by_id(request.user.userid, n)

			if (gallery.nil?)
				puts "Not a valid album"
				return
			end
			
			if (gallery.viewable_by_user?(request.session.user)  || request.session.has_priv?(CoreModule, :editgallery) )
				t = Template::instance("gallery", "gallery_full_view")
				t.user = request.user
				t.form_key = form_key
				t.gallery = gallery
				t.selected_gallery = n

				reply.headers['X-width'] = PAGE_WIDTH;
				puts t.display();
			else
				puts "You don't have permission to see this album"
			end
			
		end
		
		def upload_page(selected_gallery=nil)
			reply.headers["X-width"] = PAGE_WIDTH;
			t = Template::instance("gallery", "upload");
			t.selected_gallery = selected_gallery
			t.session = request.session.encrypt(Time.now)
			puts t.display
		end
		
		# Return the gallery selection dropdown list with the given gallery selected
		def gallery_select(selected_gallery=nil)
			t = Template::instance("gallery", "select")
			t.first_option = params["first_option", String]
			t.selected_gallery = selected_gallery
			t.galleries = request.user.galleries_sorted_by_name
			puts t.display
		end
		
		# Display the gallery management page for the gallery with the given id.
		def edit_gallery(id)
			t = Template::instance("gallery", "gallery");
			t.user = request.user;
			t.gallery = GalleryFolder.get_by_id(request.user.userid, id);
			puts t.display();
		end
		
		def upload_transfer
			site_redirect("/my/gallery/")
		end
		
		def album_info(id)
			t = Template::instance("gallery", "album_info");
			t.gallery = GalleryFolder.find(request.user.userid, id, :first)
			puts t.display();
		end
		
		def delete_gallery(id)
			reply.headers['Content-Type'] = PageRequest::MimeType::PlainText
			gallery = GalleryFolder.find(:first, session.user.userid, id.to_i);
			if (gallery)
				gallery.delete
				if (request.impersonation?)
					$log.info "#{request.session.user.username} deleted gallery #{id} for #{request.user.username}", :info, :admin
				end
			end
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
		
		def output_pic(pic)
			if (pic)
				t = Template::instance('gallery', 'profile_pic')
				t.pic = pic
				puts t.display
			end
		end
	end
end
