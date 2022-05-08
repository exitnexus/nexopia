lib_require :Core, "storable/storable", 'acts_as_file_uri';
lib_require :Core, 'users/user'
lib_require :Core, 'uploads'
lib_require :Modqueue, 'legacy_modqueue'

lib_require :Core, "storable/storable"
lib_require :Worker, "kernel_addon"
lib_require :Images, "image_creator"
lib_want :Observations, "observable"

# These support classes are outside the module, because they need to match typeids
# with the same classes in the regular "Userpics" module.
class PicsPending < Storable
	extend TypeID
	init_storable(:usersdb, "picspending");

	def before_create
		#@id = PicsPending.get_seq_id(@userid)
	end

	def to_html
		return "<img src='/files/testpics/#{@id}.jpg'/>"
	end

	def self.handle_moderated(item)
		$log.info "**********************************************";
		$log.info "MODERATION SUCCESS??? #{item} #{item.points}"
		item_key = Marshal.load(item.item);
		oldpic = PicsPending.find(:first, item_key);
		if (item.points > 0)

			newpic = Pics.create(oldpic.userid);

			$log.info "Converting #{oldpic.id} to #{newpic.id}";
			file = $site.mogilefs.get(oldpic.id, "userpics");
			$site.mogilefs.store(file, newpic.id, "userpics");
			$site.mogilefs.delete(oldpic, "userpics");
		end
		oldpic.delete;
	end
end

class PicBans < Storable
	init_storable(:usersdb, "picbans");
	
end

module LegacyUserpics
	
	
	class Pics < Storable
		extend TypeID
	    attr_reader :src
	    @@total = 0
		init_storable(:usersdb, $site.config.legacy_userpic_table);
	
		def owner
			return User.get_by_id(userid)
		end
	
		if (site_module_loaded? :Observations)
			include Observations::Observable
			OBSERVABLE_NAME = "User Pictures"
	
			observable_event :create, proc{"#{owner.link} added a picture to #{owner.possessive_pronoun} profile."}
		end
	
		acts_as_file_uri(:hash_id => :userid, :site_address => $site.config.image_url,:file_group =>'userpics', :file_id => :id, :extention => 'jpg')
	
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
			@thumb ||= Img.new(owner.username, "#{$site.image_url}/userpicsthumb/#{link}");
			return @thumb;
		end
	
		def img_info(type = 'thumb')
			return [username, "#{$site.image_url}/userpics/#{link}"];
		end
	
		def Pics.all(userid)
	        return Pics.find(:promise, :all, userid)
		end
	
		def Pics.get_by_userid(userid)
			Pics.mcfind(:all, userid, :memcache => ["ruby_pics", userid, 86400]);
		end
		def Pics.invalidate_by_userid(userid)
			$site.memcache.delete("ruby_pics#{userid}");
		end
	
		def after_load()
			@@total += 1
			@src = "http://images.live.nexopia.com/userpics/0/#{userid}/#@@total.jpg"
		end
	 
	 	def link
	 		"#{$site.mogilefs.class_code['userpics']}/#{userid}/#{id}.jpg"
	 	end
	
	 	def thumb_link
	 		"#{$site.mogilefs.class_code['userpicsthumb']}/#{userid}/#{id}.jpg"
	 	end
	 	
	 	class << self
		 	def link(userid, id)
		 		"#{$site.mogilefs.class_code['userpics']}/#{userid}/#{id}.jpg"
		 	end
		 	def thumb_link(userid, id)
		 		"#{$site.mogilefs.class_code['userpicsthumb']}/#{userid}/#{id}.jpg"
		 	end
	 		
	 	end
	      
		def get_source()
			source_file = $site.mogilefs.get(link, "userpics");
			if not (source_file)
				#No source found.  Mogile probably failed.  Load from disk.
				f = File.new("#{$site.config.user_pic_dir}/#{userid/1000}/#{userid}/#{id}.jpg", "r")
				
				#Unnecessary.  Helps for debugging though.
				data = f.read;
				f.rewind;
				$log.info "Loaded source '#{link}' from disk.  Size of #{data.length}", :debug
				
				$site.mogilefs.store(f, link, "userpics")
				source_file = $site.mogilefs.get(link, "userpics");
				if (source_file.nil?)
					raise "Source file wasn't properly mirrored to mogile."
				end
			end
			return source_file;
		end
	end
	
end

class User < Cacheable
	relation_multi_cached :pics, :userid, LegacyUserpics::Pics, "ruby_pics"

	def thumb
		if (pics.first)
			picid = pics.first.id;
		else
			picid = 0;
		end

		@thumb ||= Img.new(username, "#{$site.image_url}/userpicsthumb/#{userid / 1000}/#{userid}/#{picid}.jpg");
		return @thumb;
	end

	def img_info(type = 'thumb')
		if (pics.first)
			picid = pics.first.id;
			return ["[#{username}]", "#{$site.image_url}/userpics/#{userid / 1000}/#{userid}/#{picid}.jpg"];
		else
			return ["[#{username}]", "#{$site.static_files_url}/Userpics/images/nopic.gif"];
		end
	end

end

