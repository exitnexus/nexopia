lib_require :Userpics, "pics"

class UserPicPages < PageHandler
	declare_handlers("pictures"){
		area :Self
		access_level :IsUser, CoreModule, :editprofile
		
		page :GetRequest, :Full, :edit_profile_pictures
		handle :GetRequest, :edit_profile_pictures, 'refresh'
		handle :GetRequest, :add_profile_picture, 'add'
		
		handle :PostRequest, :remove_profile_picture, :remove, input(Integer)
		handle :PostRequest, :move_profile_picture, :move, input(Integer), :to, input(Integer)
	}
	
	def edit_profile_pictures
		request.reply.headers['X-width'] = 0
		t = Template::instance('userpics', 'edit_profile_pictures')
		t.pics = request.user.pic_slots
		t.request = request
		t.form_key = form_key
		puts t.display
	end
	
	def add_profile_picture
		t = Template::instance("gallery", "add_profile_pic")
		t.selected_gallery = -1
		t.session = request.session.encrypt(Time.now)
		puts t.display
	end
	
	#delete the profile pic with the specified priority
	#does not remove the underlying gallery pic
	def remove_profile_picture(id)
		pic = Pics.find(request.user.userid, id, :first)
		unless (pic.nil?)
			if (request.impersonation?)
				$log.info "#{PageRequest.current.session.user.username} removed profile picture #{pic.gallerypicid} for #{request.user.username}.", :info, :admin
			end
			pic.delete
		end
		refresh(params["refresh", String])
	end
	
	def move_profile_picture(id, target_priority)
		pic = Pics.find(request.user.userid, id, :first)
		if (pic)
			Pics.move(request.user.userid, pic.priority, target_priority)
		end
		refresh(params["refresh", String])
	end
	
	def refresh(type=nil)
		case type
		when "edit_profile_pictures"
			self.edit_profile_pictures
		end
	end
	private :refresh

end