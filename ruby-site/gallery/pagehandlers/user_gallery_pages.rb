lib_require :Core, "template/template"
lib_want :Profile, "user_skin"

module Gallery
	class UserGalleryPages < PageHandler
		RECENT_GALLERY_COUNT = 4
		
		declare_handlers("gallery") {

			area :User
			page :GetRequest, :Full, :gallery_overview
			page :GetRequest, :Full, :recent_galleries, "recent"
			page :GetRequest, :Full, :gallery, input(/(\d+)-*.*/)
			page :GetRequest, :Full, :gallery, input(/(\d+)-*.*/), input(Integer)
			handle :GetRequest, :share, 'share', input(Integer)

			area :Self
			access_level :IsUser, CoreModule, :editgallery
			
			handle :PostRequest, :delete, "delete", input(Integer)
		}
		
		def manage_gallery
			if (request.session.user.userid == request.user.userid)
				return manage_gallery = url/:my
			elsif (request.session.has_priv?(CoreModule, :editgallery))
				return manage_gallery = url/:admin/:self/request.user.username
			end
			return nil
		end
		private :manage_gallery
		
		def gallery_overview
			request.reply.headers['X-width'] = 0
			apply_user_skin!

			galleries = nil
			if ( request.session.has_priv?(CoreModule, :editgallery) )
				galleries = request.user.galleries_sorted_by_created()
			else
				galleries = request.user.galleries_sorted_by_created(request.session.user)
			end			

			t = Template::instance("gallery", "user_gallery_overview")
			
			t.galleries = galleries
			t.viewer = request.session.user
			t.manage_gallery = manage_gallery
			t.user = request.user
			t.form_key = SecureForm.encrypt(request.session.user, url/:Self/:gallery)

			puts t.display

		end
		
		def recent_galleries
			t = Template::instance("gallery", "recent_galleries")
			t.galleries = request.user.public_galleries_sorted_by_created[0,RECENT_GALLERY_COUNT]
			t.gallery_count = request.user.galleries(request.session.user).length
			t.viewer = request.session.user
			t.user = request.user
			puts t.display
		end
		
		def gallery(gallery_id, pic_id=0)
			# Note: We never actually use the title part of the gallery_id. It's simply there to 
			# allow more descriptive gallery links, where -the-gallery-name would come after the
			# id. We still use only the id to retrieve the correct gallery and throw the title
			# part away. This regular expression match should *never* fail because there should
			# always be a numerical ID part of the string even if there is no description text.
			gallery_id = gallery_id[1].to_i

			gallery = GalleryFolder.find(:first, request.user.userid, gallery_id)
			
			if (gallery.nil?)
				puts "Not a valid album"
				return
			end
			
			if (gallery.viewable_by_user?(request.session.user)  || request.session.has_priv?(CoreModule, :editgallery) )
			
			
				t = Template::instance("gallery", "user_gallery")
			
				# if it's the user or an admin include the manage links
				t.manage_gallery = request.session.user.userid == request.user.userid || request.session.has_priv?(CoreModule, :editgallery)

				t.gallery = GalleryFolder.find(:first, request.user.userid, gallery_id)
				t.user = request.user
				t.comment_url = url/:users/request.user.username/:gallery/:comments
				
				#if they provide a pic id and it exists use it, otherwise take the first pic in the gallery
				t.current_pic = nil
				if (!pic_id.zero?)
					t.current_pic = Gallery::Pic.find(:first, request.user.userid, pic_id)
				end
				if (!t.current_pic)
					t.current_pic = t.gallery.pics.first
				end
			
				t.manage_gallery_form_key = SecureForm.encrypt(request.session.user, "/Self/gallery");
				t.current_profile_pics = PageRequest.current.user.pics_internal.map{ |p| p.gallerypicid } * ',';
			
				t.gallery.pics.each {|pic|
					pic.add_json_property(:gallery_link, "#{request.area_base_uri}/gallery/#{t.gallery.id}/#{pic.id}")
					pic.add_json_property(:share_link, "#{request.area_base_uri}/gallery/share/#{pic.id}")
				}
				request.reply.headers['X-width'] = 0
				apply_user_skin!
			
				t.comments_admin_view = params["admin_view", Boolean, false];
				t.comments_page = params["page", Integer, 0];
			
				puts t.display
				
			else
				puts "You don't have permission to see this album"
			end
			
		end


		def share(pic_id)
			t = Template::instance('gallery', 'share_picture')
			t.pic = Gallery::Pic.find(:first, request.user.userid, pic_id)
			request.reply.headers["X-width"] = 0
			puts t.display
		end

		
		# AJAX gallery delete.  Deletes the given gallery and returns the gallery overview page minus the deleted gallery.
		def delete(gallery_id)
			
			gallery = GalleryFolder.find(:first, request.user.userid, gallery_id.to_i);
			
			if (gallery)
				gallery.delete
				if (request.impersonation?)
					$log.info "#{PageRequest.current.session.user.username} deleted gallery #{gallery_id} of #{request.user.username}.", :info, :admin
				end
			else
				InfoMessages.display_errors{
					request.reply.headers['Status'] = 500
					raise "This gallery has already been deleted."
				}
			end
			
			galleries = nil
			if ( request.session.has_priv?(CoreModule, :editgallery) )
				galleries = request.user.galleries_sorted_by_created()
			else
				galleries = request.user.galleries_sorted_by_created(request.session.user)
			end

			t = Template.instance("gallery", "detailed_gallery_list")
			
			t.galleries = galleries
			t.viewer = request.session.user
			t.manage_gallery = manage_gallery
			t.user = request.user

			t.form_key = SecureForm.encrypt(request.session.user, url/:Self/:gallery)
			
			puts t.display
			
		end
		
		private
		def apply_user_skin!
			user_skin = Profile::UserSkin.find(:first, [request.user.userid, request.user.galleryskin]);
			if(!user_skin.nil?() && request.user.plus?())
				request.reply.headers['X-user-skin'] = user_skin.header();
			end
		end
	end
end