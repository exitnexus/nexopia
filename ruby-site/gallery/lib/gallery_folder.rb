lib_require :Core, "slugly", "storable/storable", "users/user", "users/user_name"
lib_want :GoogleProfile, 'google_user'
lib_want :Profile, 'profile_block_visibility'
lib_want :Scoop, "reporter", 'story'

module Gallery
	class GalleryFolder < Cacheable
		init_storable(:usersdb, "gallery");

		extend TypeID
		
		MAX_GALLERIES = 50
		
		relation_singular :owner, :ownerid, User
		relation_singular :owner_username, :ownerid, UserName
		
		report :create, :userid_column => :ownerid, :restrict => :scoop_viewable?, :delay => 300, :sort => :feed_sort
		
		make_slugable :name
		
		def feed_sort
			return -self.created
		end
		
		def viewable_by_user?(user)
			case self.permission
			when "loggedin"
				return user.logged_in?
			when "friends"
				return !self.owner.nil? && (self.owner == user || self.owner.friend?(user))
			when "anyone"
				return true
			else
				return self.ownerid == user.userid
			end
		end
	
		def scoop_permission?(userid)
			return case self.permission
				when "loggedin"
					true
				when "friends"
					self.ownerid == userid || self.owner.friend?(userid)
				when "anyone"
					true
				else
					self.ownerid == userid
			end
		end
	
		def scoop_viewable?(userid)
			return scoop_permission?(userid) && (self.name != UserpicsModule::DEFAULT_PROFILE_PICTURES_GALLERY_NAME) && (self.name != BlogsModule::DEFAULT_BLOG_GALLERY_NAME)
		end
	
		#automatically set the created timestamp if it hasn't been set manually.
		def before_create
			if (self.created.zero?)
				self.created = Time.now.to_i
			end
			validate!
		end
		
		def validate!
			if (self.owner.galleries.length >= MAX_GALLERIES)
				raise "You have already reached the maximum number of galleries you may create (#{MAX_GALLERIES})."
			end
		end
		
		def after_create
			if (site_module_loaded?(:GoogleProfile))
				self.owner.update_hash
			end
			# Super called at the end, because it inserts an event and we don't want that event
			# added repeatedly
			super
		end
	
		def after_update
			update_menu_permissions!
			if (site_module_loaded?(:GoogleProfile))
				self.owner.update_hash
			end
			super
		end
		
		def update_menu_permissions!
			if (site_module_loaded?(:Profile))
				permissions_mapping = {"none" => :none, "friends" => :friends, "loggedin" => :logged_in, "anyone" => :all}
				min_level = 10000000 # this should hopefully always be bigger than what we got going on
				owner.galleries.each{|gallery|
					level = Profile::ProfileBlockVisibility.instance.visibility_list[permissions_mapping[gallery.permission]]
					min_level = level if (min_level > level)
				}
				if (min_level == 10000000)
					owner.gallerymenuaccess = :none
				else
					owner.gallerymenuaccess = min_level
				end
				owner.store
			end
		end
		
		def before_delete
			self.pics.each {|pic|
				pic.delete(false) #false tells it to not reorder images, since we are deleting them all this is a good idea.
			}
			if (site_module_loaded?(:GoogleProfile))
				self.owner.update_hash
			end
			super
		end
		
		def pics
			#Run a quick check for consistency.  Might be possible to remove
			#this on the live site, but its fairly quick so it may not be 
			#necessary.
			ordered_pics = self.pics_internal.sort_by{|pic| pic.priority}
			ordered_pics.each_with_index{|pic, index|
				if (pic.priority != index+1)
					pic.priority = index+1;
					pic.store;
				end
			}
			return ordered_pics
		end
		
		#returns a promise to the picture in the album with a given priority
		def pic_priority(priority)
			result = Gallery::Pic.find(:first, :promise, [self.ownerid, self.id, demand(priority)], :index => :usercat)
			return result
		end

		def self.get_by_id(uid, id)
			return GalleryFolder.find(:first, [uid, id], :promise);	
		end
		
		def fix_cover!(avoid_ids=[])
			avoid_ids = [*avoid_ids]
			return if (!self.album_cover.nil? && self.album_cover.galleryid == self.id && !avoid_ids.include?(self.previewpicture))
			self.previewpicture = 0
			self.pics.each {|pic|
				if (!avoid_ids.include?(pic.id))
					self.previewpicture = pic.id
					break
				end
			}
			self.store
		end
		
		def userid
			return @ownerid;
		end

		def move_pic(picture, position)
			if (position < picture.priority)
				pics_to_change = Pic.find(:conditions => ["userid = # && galleryid = ? && priority >= ? && priority < ?", picture.userid, picture.galleryid, position, picture.priority])
				pics_to_change.each {|pic|
					pic.priority += 1
					pic.store
				}
			else
				pics_to_change = Pic.find(:conditions => ["userid = # && galleryid = ? && priority > ? && priority <= ?", picture.userid, picture.galleryid, picture.priority, position]);
				pics_to_change.each {|pic|
					pic.priority -= 1
					pic.store
				}
			end
			picture.priority = position;
			picture.store;
		end
		
		def uri_info(mode = 'other')
			case mode
			when "self"
				return [@name, "/my/gallery/#{@id}"];
			when "other"
				if (!owner.nil?)
					return [@name, "/users/#{urlencode(owner.username)}/gallery/#{@id}-#{name_slug}"];
				else
					return [@name, nil] #if the album owner is deleted we can't generate a link to the album.
				end
			end
		end
		
		def img_info(mode = 'thumb')
			if (self.previewpicture == 0 || album_cover.nil? || album_cover.id.zero?)
				return [@name, "#{$site.static_files_url}/Gallery/images/no_images_#{mode}.gif"]
			end
			return album_cover.img_info(mode);
		end
		
		class << self
			def max_priority(userid, galleryid)
				result = self.db.query("SELECT MAX(priority) FROM `gallerypics` WHERE userid=# and galleryid=?", userid, galleryid).fetch_set.first;
				return result["MAX(priority)"].to_i
			end
		end

		if (site_module_loaded?(:UserDump))
		  extend Dumpable
			# Used by the UserDumpController to get the gallery pics for a given user.
			# Start and end time don't matter since we can only get the current gallery pics.
			def self.user_dump(userid, startTime = 0, endTime = Time.now())

				galleries = GalleryFolder.find(userid)
				pic_files = []
				pic_descriptions = "\"File Name\",\"Description\"\n"

				# loop through the pics, get the file from the source, write it out, add the file to the list
				# of files to be added to the zip.
				galleries.each { |gallery|
					gallery.pics.each { |pic| 
						pic_uri = URI.parse( pic.full_link )
						out_file = File.open("/tmp/"+ File.basename(pic_uri.select(:path).to_s), "wb")
						pic.get_source.get_contents( out_file )
						out_file.close
						pic_files.push( out_file )
						pic_descriptions += "\"#{File.basename(pic_uri.select(:path).to_s)}\",\"#{pic.description}\"\n"
					}
				}

				pic_files.push( Dumpable.str_to_file("#{userid}-gallery_pic_descriptions.csv", pic_descriptions))
				Dumpable.imgs_to_zip("#{userid}-gallery_pics.zip", pic_files)			

			end
		end
		
	end # class GalleryFolder < Cacheable
end # module Gallery

class User < Cacheable
	relation :multi, :all_galleries, :userid, Gallery::GalleryFolder
	relation :count, :all_galleries_count, :userid, Gallery::GalleryFolder
	if (site_module_loaded?(:Scoop))
		relation :count, :friends_galleries_count, :userid, Scoop::Story, :user_type, :conditions => ["typeid = ? && viewed = 'n'", Gallery::GalleryFolder.typeid], :extra_columns => :viewed
	end
	
	def galleries(user=nil)
		if (user)
			return self.all_galleries.select{|gallery| gallery.viewable_by_user?(user)}
		else
			return self.all_galleries
		end
	end
	
	def galleries_sorted_by_name(user=nil)
		return self.galleries(user).sort_by {|gallery| gallery.name.upcase}
	end
	
	def public_galleries
		return self.all_galleries.select{ |gallery| gallery.permission == "anyone" };
	end
	
	def galleries_sorted_by_created(user=nil)
		return self.galleries(user).sort_by {|gallery| [-gallery.created, gallery.name.upcase]}
	end

	def public_galleries_sorted_by_created
		return self.public_galleries.sort_by {|gallery| [-gallery.created, gallery.name.upcase]}
	end
end

