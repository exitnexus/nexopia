module Userpics
	class UserpicsHelper
		
		class << self			
			# take a gallerypic and set it as a userpic.
			def set_as_userpic(userid, gallery_pic, set_priority = nil)

				gallerypicid = gallery_pic.id
				file_obj = gallery_pic.get_source
				image_md5 = gallery_pic.md5	
	
				ban = PicBans.find(:first, [userid, image_md5])
				globalban = GlobalPicBans.find(:first, image_md5)

				if (globalban)
					if (globalban.times > 3)
						$log.info "Attempt to make a global banned picture a profile pic.\nban md5: #{globalban.md5}, image md5:#{image_md5}, times: #{globalban.times}", :error
						raise UserError.new, "This picture has a global ban because of repeated abuse."
					end
				end
				if (ban)
					if (ban.times > 1)
						$log.info "Attempt to make a banned picture a profile pic.\nuserid: #{ban.userid}, ban md5:#{ban.md5}, image md5: #{image_md5} times: #{ban.times}", :error
						raise UserError.new, "This picture has been banned because it has been denied twice already."
					end
				end

				# If it's not already a profile pic create a new one.
				pic = Pics.find(:first, userid, gallerypicid)
				demand pic
				if (!pic)
					pic = Pics.create(userid, gallerypicid, set_priority)					
				end
				
				if (pic.gallery_pic.userpic == :failed)
					raise UserError.new, "This picture has already been denied as a profile picture."
				end
			
				#Create the item for the moderation system if it isn't already there.
				if (pic.gallery_pic.userpic != :pending && pic.gallery_pic.userpic != :accepted )
					priority = User.find(:first, userid).plus?
					pic.gallery_pic.userpic = :pending
					pic.gallery_pic.store
					$log.info "Modqueuing picture #{userid}/#{gallerypicid} with priority #{priority}", :info
					Userpics::PicsQueue.add_item(userid, gallerypicid, priority);
				end

				#store the pic
				pic.store

				user = User.find(:first, userid)
				if (user.firstpic == 0)
					user.firstpic = pic.gallerypicid
					user.store;
				end

				return true;
				
			end # def set_as_userpic()
		end # class << self	
	end # class UserpicsHelper
end # module Userpics