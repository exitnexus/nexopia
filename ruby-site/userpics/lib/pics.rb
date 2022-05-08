lib_require :Core, "storable/storable", 'acts_as_file_uri';
lib_require :Core, 'users/user'
lib_require :Core, 'uploads'
lib_require :Modqueue, 'legacy_modqueue'

lib_require :Core, "storable/storable"
lib_require :Worker, "kernel_addon"

lib_require :Gallery, 'gallery_pic'

lib_want :Observations, "observable"




class PicsPending < Storable
	extend TypeID
	init_storable(:usersdb, "picspending");

	def before_create
		#@id = PicsPending.get_seq_id(@userid)
	end

	def to_html
		return "<img src='#{$site.image_url}/gallery/#{link}'/>"
	end
	
	def link
		"#{$site.mogilefs.class_code['gallery']}/#{userid}/#{gallerypicid}.jpg"		
	end

	def self.handle_moderated(item)
		if (item.points < 0)
			item_key = Marshal.load(item.item);
			mod_item = PicsPending.find(:first, item_key)
			pic = Pics.find(:first, mod_item.userid, mod_item.id)
			pic.delete
			
			mod_item.delete
		end
	end
end

class PicBans < Storable
	init_storable(:usersdb, "picbans");
	
end

class Pics < Storable
	extend TypeID
    attr_reader :src
    @@total = 0
	init_storable(:usersdb, "pics");
	
	relation_singular :gallery_pic, [:userid, :gallerypicid], Gallery::Pic
	relation_singular :owner, :userid, User, true

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

	def img_info(type = 'thumb')
		return self.gallery_pic.img_info(type)
	end
	##################################################
	#end passthrough to gallerypic functions
	##################################################
	
	if (site_module_loaded? :Observations)
		include Observations::Observable
		OBSERVABLE_NAME = "User Pictures"

		observable_event :create, proc{"#{owner.link} added a picture to #{owner.possessive_pronoun} profile."}
	end

	acts_as_file_uri(:hash_id => :userid, :site_address => $site.config.image_url,:file_group =>'gallerypics', :file_id => :id, :extention => 'jpg')

	def Pics.create(userid)
		pic = Pics.new();
		pic.userid = userid;
		pic.id = Pics.get_seq_id(userid)
		return pic;
	end

	def owner
		return User.get_by_id(userid);
	end

	def thumb
		self.gallery_pic.thumb
	end

	def landscape
		self.gallery_pic.landscape
	end

	def landscapethumb
		self.gallery_pic.landscapethumb
	end

	def Pics.all(userid)
        return Pics.find(:promise, :all, userid)
	end

	def Pics.get_by_userid(userid)
		Pics.mcfind(:all, userid, :memcache => ["ruby_pics", userid, 86400]);
	end
	
	#moves the picture with starting priority to ending priority, shifting
	#all the pictures in between by 1 to make room
	def self.shift(userid, starting_priority, ending_priority)
		Pics.db.query("UPDATE pics SET priority = 0 WHERE priority = ? && userid = #", starting_priority, userid)
		if (ending_priority > starting_priority)
			Pics.db.query("UPDATE pics SET priority = priority-1 WHERE priority > ? && priority <= ? && userid = #", starting_priority, ending_priority, userid)
		else
			Pics.db.query("UPDATE pics SET priority = priority+1 WHERE priority < ? && priority >= ? && userid = #", starting_priority, ending_priority, userid)
		end
		Pics.db.query("UPDATE pics SET priority = ? WHERE priority = 0 && userid = #", ending_priority, userid)
		pics = Pics.find(userid)
		pics.each {|pic|
			pic.invalidate_cache_keys
			pic.memcache_invalidate
		}
		
		update_user_info(userid)
	end
	
	def self.update_user_info(userid)
		firstpic = Pics.find(:first, userid, :order => "priority ASC")
		owner = User.get_by_id(userid)
		owner.firstpic = if (firstpic)
			firstpic.gallerypicid
		else
			0
		end
		
		signpic = Pics.find(:first, :conditions => ["userid = # AND signpic = 'y'", userid], :order => "priority ASC")
		owner.signpic = signpic ? true : false
		
		owner.store
	end
	
	def after_delete
		if (id && id > 0)
			NexFile.new("userpics", self.userid, "#{self.id}.jpg").delete
			NexFile.new("userpicsthumb", self.userid, "#{self.id}.jpg").delete
		end

		self.class.update_user_info(self.userid)
		super
	end
	
	def after_update
		self.class.update_user_info(self.userid)
		super
	end
	
	def after_create
		self.class.update_user_info(self.userid)
		super
	end

	def self.generate_path(userid, id)
		"#{userid/1000}/#{userid}/#{id}.jpg"
	end
	
 	def link
 		return generate_path(self.userid, self.id)
 	end

end

class EmptyPicSlot
	attr_accessor(:priority)
	
	def initialize(priority)
		@priority = priority
	end
	
	def img_info(type=nil)
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
	relation_multi_cached :pics, :userid, Pics, "ruby_pics", :promise
	relation_singular :first_picture, [:userid, :firstpic], Gallery::Pic, :promise

	#return an array of priority to pic with an EmptyPicSlot for any missing pics
	def pic_slots
		slots = Array.new(self.max_pics)
		self.pics.each {|pic|
			slots[pic.priority-1] = pic #priorties start at 1 so shift down by 1 to make arrays happy
		}

		#fill any empty slots with EmptyPicSlot objects
		priority = 0
		return slots.map {|slot| 
			priority += 1
			slot.nil? ? EmptyPicSlot.new(priority) : slot 
		}
	end

	def img_info(type = 'landscapethumb')
		if (!first_picture.nil?)
			[username, first_picture.img_info(type)[1]]
		else
			[username, $site.static_files_url/:Userpics/:images/"nopic.gif"]
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

	def max_pics
		return max_pics = (self.plus? ? 12 : 8);
	end
end
