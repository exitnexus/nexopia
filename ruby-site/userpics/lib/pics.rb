lib_require :Core, "storable/storable"
lib_require :Core, 'users/user'

lib_require :Core, "storable/storable"

lib_require :Gallery, 'gallery_pic'

lib_want :GoogleProfile, "google_user"

require 'net/http'
require 'open-uri'

class PicBans < Storable
	init_storable(:usersdb, "picbans");
end

class GlobalPicBans < Storable
	init_storable(:moddb, "globalpicbans");
end


class Pics < Cacheable
	extend TypeID
    attr_reader :src
    @@total = 0
	init_storable(:usersdb, "pics");
	
	relation_singular :gallery_pic, [:userid, :gallerypicid], Gallery::Pic
	relation_singular :owner, :userid, User, true

	extend JSONExported
	json_field(:gallerypicid, :userid)

	#empty returns true for pic slot objects and false for pics objects
	def empty?
		return false
	end

	##################################################
	#begin passthrough to gallerypic functions
	##################################################
	def description
		return self.gallery_pic.description
	end

	def signpic
		return self.gallery_pic.signpic
	end
	
	def crop
		return self.gallery_pic.crop
	end

	def signpic?
		self.gallery_pic.signpic?
	end

	def img_info(type = 'profile')
		if (!self.gallery_pic.nil?)
			type = 'profile' if type == 'normal'
			return self.gallery_pic.img_info(type)
		else
			$log.info "Profile picture exists without gallery image for user:id #{self.userid}:#{self.gallerypicid}."
			self.delete
			return EmptyPicSlot.img_info(type)
		end
	end
	##################################################
	#end passthrough to gallerypic functions
	##################################################
	
	def self.create(userid, gallerypicid, priority = nil)
		
		pic = self.new
		pic.userid = userid
		pic.id = 0 # This is the legacy id it should not be used anymore
		if(!priority)
			maxid = self.find(:first, userid, :order => "priority DESC")
			if (maxid.nil?)
				pic.priority = 1
			else
				pic.priority = maxid.priority + 1
			end
		else
			pic.priority = priority
		end
		pic.signpic = false
		pic.gallerypicid = gallerypicid
		
		return pic
	end

	def self.all(userid)
		return Pics.find(:promise, :all, userid)
	end

	def self.get_by_userid(userid)
		Pics.mcfind(:all, userid, :memcache => ["ruby_pics", userid, 86400]);
	end

	#moves the picture with starting priority to ending priority, shifting
	#all the pictures in between by 1 to make room
	def self.move(userid, starting_priority, ending_priority)
		user = User.find(userid, :first);
		pics = user.pics
		pics.each {|pic|
			pic.invalidate
		}
		
		Pics.db.query("UPDATE pics SET priority = 0 WHERE priority = ? && userid = #", starting_priority, userid)
		if (ending_priority > starting_priority)
			Pics.db.query("UPDATE pics SET priority = priority-1 WHERE priority > ? && priority <= ? && userid = #", starting_priority, ending_priority, userid)
		else
			Pics.db.query("UPDATE pics SET priority = priority+1 WHERE priority < ? && priority >= ? && userid = #", starting_priority, ending_priority, userid)
		end
		Pics.db.query("UPDATE pics SET priority = ? WHERE priority = 0 && userid = #", ending_priority, userid)
		
		update_user_info(userid)
	end

	# Move pic at starting_priority and all subsequent pics over by the number specified in positions.
	# A positive number moves everthing to lesser priorities, negative number move to greater priorities.
	# This function is dumb and doesn't wrap and won't check bounds.
	def self.shift(userid, starting_priority, positions = 1)
		
		user = User.find(userid, :first);
		pics = user.pics
		pics.each {|pic|
			pic.invalidate
		}
		
		if (positions > 0)
			Pics.db.query("UPDATE pics SET priority = priority + # WHERE priority >= ? && userid = #", positions, starting_priority, userid)
		else
			Pics.db.query("UPDATE pics SET priority = priority - # WHERE priority =< ? && userid = #", positions, starting_priority, userid)
		end
		
		update_user_info(userid)
	end

	
	# Takes a given gallery pic and inserts it as a profile pic with the given priority.
	# By default it will insert the pic as the first pic.
	# If the user has reached the max number of pics it will delete the last profile pic that isn't a sign pic.
	# If they're all sign pics it deletes the last sign pic.
	def self.insert_profile_pic(userid, gallery_pic, priority=1)
			
		owner = User.get_by_id(userid)
		profile_pics = self.all(userid)
		profile_pics_num = profile_pics.length

		# if max_pics has been reached then we need to get rid of a pic before we insert the new one.
		# priority is given to the sign pics.
		if (profile_pics_num >= owner.max_pics)

			sign_pics = []
			non_sign_pics = []
			profile_pics.each { |pic|
				if (pic.signpic?)
					sign_pics << pic
				else
					non_sign_pics << pic					
				end
			}
		
			sign_pics.sort! { |a, b| 
				a.priority <=> b.priority
			}
			non_sign_pics.sort! { |a, b| 
				a.priority <=> b.priority
			}
			
			sign_pics.each { |pic|
			}
			
			non_sign_pics.each { |pic|
			}
			
			# if there's nothing to delete in the non_sign_pics list then we'll throw out the last sign pic.
			if non_sign_pics[-1] != nil
				non_sign_pics[-1].delete
			else
				sign_pics[-1].delete
			end
						
		end
	
		# shift the pic at the given priority down by one.
		Pics.shift(userid, priority)
	
		# make the gallery pic a profile pic at the given priority
		Userpics::UserpicsHelper.set_as_userpic(userid, gallery_pic, priority)		
	end
	
	def self.update_user_info(userid)
		pics = Pics.find(userid, :order => "priority ASC")
		
		owner = User.get_by_id(userid)
		owner.firstpic = if (pics.first)
			pics.first.gallerypicid
		else
			0
		end
		
		signpic = false;
		pics.each{|pic|
			signpic = true if (pic.gallery_pic.signpic == :accepted)
		}
		
		owner.signpic = signpic ? true : false
		
		owner.store
	end
	
	def owner
		return User.get_by_id(userid);
	end

	def thumb
		self.gallery_pic.thumb
	end
	
	def normal
		self.gallery_pic.profile
	end
	def profile
		self.gallery_pic.profile
	end

	def landscape
		self.gallery_pic.landscape
	end

	def landscapethumb
		self.gallery_pic.landscapethumb
	end
	
	def after_delete
		self.class.update_user_info(self.userid)
		super
	end
	
	def after_update
		self.class.update_user_info(self.userid)
		if (site_module_loaded?(:GoogleProfile))
			self.owner.update_hash
		end
		super
		if(self.gallery_pic.nil?)
			$log.info "Error UPDATING pic (userid: #{self.userid}, id: #{self.id}, gallerypicid: #{self.gallerypicid})", :error
			$log.info "Deleting pic because there is no backing gallery pic", :error
			$log.object caller, :error
			self.delete;
		end
	end

	
	def after_create
		self.class.update_user_info(self.userid)
		if (site_module_loaded?(:GoogleProfile))
			self.owner.update_hash
		end
		super
		if(self.gallery_pic.nil?)
			$log.info "Error CREATING pic (userid: #{self.userid}, id: #{self.id}, gallerypicid: #{self.gallerypicid})", :error
			$log.info "Deleting pic because there is no backing gallery pic", :error
			$log.object caller, :error
			self.delete;
		end
		
		if(!self.owner.user_task_list.empty?())
			self.owner.user_task_list.each{|task|
				if(task.taskid == 4)
					task.delete()
				end
			}
		end
	end


	def after_load
		if(self.gallery_pic.nil?)
			$log.info "Error LOADING pic (userid: #{self.userid}, id: #{self.id}, gallerypicid: #{self.gallerypicid})", :error
			$log.info "Deleting pic because there is no backing gallery pic", :error
			$log.object caller, :error
			self.delete;
		end
	end		


	def before_delete
		if (site_module_loaded?(:GoogleProfile))
			self.owner.update_hash
		end
		if(!self.gallery_pic.nil?)
  		if (self.signpic == :accepted)
  			any_sign_pics = false
  			self.owner.pics.each {|pic|
  				if (pic.signpic == :accepted)
  					any_sign_pics = true
  				end
  			}
  			self.owner.signpic = any_sign_pics
  			self.owner.store
  		end
		end
	end

	if (site_module_loaded?(:UserDump))
	  extend Dumpable
		# Used by the UserDumpController to get the profile pics for a given user.
		# Start and end time don't matter since we can only get the current profile pics.
		def self.user_dump(userid, startTime = 0, endTime = Time.now())
			
			pics = self.all(userid)
			pic_files = []
			pic_descriptions = "\"File Name\",\"Description\"\n"

			# loop through the pics, get the file from the source, write it out, add the file to the list
			# of files to be added to the zip.
			pics.each { |pic|
				pic_uri = URI.parse( pic.gallery_pic.full_link )
				out_file = File.open("/tmp/"+ File.basename(pic_uri.select(:path).to_s), "wb")
				pic.gallery_pic.get_source.get_contents( out_file )
				out_file.close
				pic_files.push( out_file )
				pic_descriptions += "\"#{File.basename(pic_uri.select(:path).to_s)}\",\"#{pic.gallery_pic.description}\"\n"
			}
			
			pic_files.push( Dumpable.str_to_file("#{userid}-profile_pic_descriptions.csv", pic_descriptions))
			Dumpable.imgs_to_zip("#{userid}-profile_pics.zip", pic_files)			

		end
	end

end

class EmptyPicSlot
	attr_accessor(:priority, :user)
	
	def initialize(priority, user)
		@priority = priority
		@user = user
	end
	
	def img_info(type=nil)
		case self.type
		when :add_picture
			return ["Add Picture","#{$site.static_files_url}/Userpics/images/empty_pic_slot_landscapethumb.gif"]
		when :sign_pic
			return ["Signed Picture","#{$site.static_files_url}/Userpics/images/sign_pic_landscapethumb.gif"]
		when :plus_only
			return ["Plus Only","#{$site.static_files_url}/Userpics/images/plus_only_landscapethumb.gif"] 
		end
	end
	
	#returns :add_picture, :plus_only, or :sign_pic depending on what type of empty slot this is
	def type
		if (self.user.max_pics >= self.priority)
			return :add_picture
		elsif (self.user.plus? || (self.priority == (User::REGULAR_PIC_SLOTS+1)))
			return :sign_pic
		else
			return :plus_only
		end
	end
	
	def self.img_info(type=nil)
		return ["Drag image here","#{$site.static_files_url}/Userpics/images/empty_slot.png"]
	end
	
	def empty?
		return true
	end
	
	def description
		""
	end
end

class User < Cacheable
	relation :multi, :rel_pics_internal, :userid, Pics
	relation :singular, :internal_first_picture, [:userid, :firstpic], Gallery::Pic
	
	REGULAR_PIC_SLOTS = 8
	PLUS_PIC_SLOTS = 12
	
	def pics_internal
		list = self.rel_pics_internal
		before_length = list.length
		list = list.select { |p| !(demand(p)).nil? } # Making sure that there's no possible way for pics to be nil here
		after_length = list.length
		if (before_length != after_length)
			$log.info "User #{self.username} (id: #{self.userid}) pics_internal had #{before_length - after_length} nil elements that were removed", :error
			$log.info "Deleting the memcache key for this relation to correct the error", :error
			$site.memcache.delete("ruby_userinfo_rel_pics_internal_relation-#{self.userid}")
		end
		return list;
	end
	
	# This fuction removes any gaps in the list of priorities and makes sure the first pic has a priority of 1.
	# For example if through various adds and deletes a person has pics with priorities 3, 5, 6, 7, and 12, 
	# compact_profile_pics will reset the priorities to be 1 through 5.
	def compact_profile_pics!()

		slots = self.pics_internal.sort {|a, b| a.priority <=> b.priority }
		changed = Array.new(slots.length, false)
		
		slots.each_index { |index|
			if ((index + 1) != slots[index].priority)
				slots[index].priority = index + 1
				changed[index] = true
			end
		}
		
		slots.each_index { |index|
			if ( changed[index] == true )
				slots[index].store				
			end
		}
			
	end
	
	def first_picture
		if self.firstpic == 0
			return nil
		end
		return self.internal_first_picture
	end
	
	def pics
		return self.pic_slots.slice(0, self.max_pics).select {|pic| !pic.empty?}
	end

	#return an array of priority to pic with an EmptyPicSlot for any missing pics
	def pic_slots		
		compact_profile_pics!()
		
		slots = Array.new(User::PLUS_PIC_SLOTS+1)
		self.pics_internal.each {|pic|
			slots[pic.priority-1] = pic #priorities start at 1 so shift down by 1 to make arrays happy
		}

		#fill any empty slots with EmptyPicSlot objects
		priority = 0
		slots = slots.map {|slot| 
			priority += 1
			slot.nil? ? EmptyPicSlot.new(priority, self) : slot 
		}

		return slots.slice(0, User::PLUS_PIC_SLOTS+1)
	end

	def img_info(type = 'landscapethumb')
		if (self.firstpic > 0)
			# NEX-801 This was modified to deal with revisions for
			# gallery images.  We don't want to do a DB query
			# every time we do an img_info on the page.  So we
			# grab all of them at the same time to make sure they're in the cache.
			# Then when we do the acutal find we don't end up doing a query, just
			# a memcache hit.
			
			# Get all the unique user objects we currently have cached on the page.
			users = []
			User.internal_cache.each_value {|user|
				users.concat( user )
			}
			users.uniq!

			# for each of the users collect the userid and image id of the first pic.
			first_pics = []
			users.each {|user|
				first_pics.push( [user.userid, user.firstpic] )
			}

			# Make sure all the images are in the cache.
			Gallery::Pic.find(:promise, *first_pics)
			
			# Now we actually get what we want.
			gallery_pic = Gallery::Pic.find(:first, [self.userid, self.firstpic], :promise)
			if (!gallery_pic.nil?)
				gallery_pic.img_info(type)
			else
				$log.info "Found invalid first image #{self.firstpic} for #{self.userid}, auto-correcting.", :warning
				if (self.pics.length > 0)
					self.firstpic = self.pics.first.gallerypicid
				else
					self.firstpic = 0
				end
				self.store
				return self.img_info
			end
		else
			[username, $site.static_files_url/:Userpics/:images/"no_profile_image_#{type}.gif"]
		end
	end
	
	def thumb()
		Img.new(*img_info('thumb'))
	end

	def landscape()
		Img.new(*img_info('landscape'))
	end

	def landscapethumb()
		Img.new(*img_info('landscapethumb'))
	end

	def landscapemini()
		Img.new(*img_info('landscapemini'))
	end

	def max_pics
		return max_pics = (self.plus? ? PLUS_PIC_SLOTS : REGULAR_PIC_SLOTS) + (self.signpic ? 1 : 0);
	end
end
