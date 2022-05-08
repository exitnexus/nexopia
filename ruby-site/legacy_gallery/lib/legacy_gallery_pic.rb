lib_require :Core, "storable/storable"
lib_require :Core, "uploads"
lib_want :Observations, "observable"
lib_want :Modqueue, "legacy_modqueue"
lib_require :LegacyGallery, "legacy_gallery"

module LegacyGallery
	class Pic < Storable
		init_storable(:usersdb, "gallerypics");

		if (site_module_loaded?(:Observations))
			include Observations::Observable;
			OBSERVABLE_NAME = "Gallery Pictures"

			#observable_event :edit, proc{"#{owner.link} edited the caption on a picture in #{gallery.link}."}
			observable_event :create, proc{ 
				path = "/ObservableDisplay/display/#{self.class.name.gsub(':', '_')}";
				__req = PageHandler.current.subrequest(StringIO.new(), :GetRequest, path, {'pic' => self}, :Internal, nil ) 
				if (__req.reply.ok?)
					__req.reply.out.string;
				else 
					"Error!"
				end
			}
		end
		
		relation_singular :gallery, [:userid, :galleryid], GalleryFolder;
		relation_singular :owner, :userid, User		
		
		class CollapsedPicEvent
			attr :list, true;
			
			def initialize(list)
				@list = list;
			end
			
			def time
				@list.first.time
			end
			def user
				User.get_by_id(@list.first.userid)
			end
			def originator
				User.get_by_id(@list.first.originatorid)
			end
			def userid
				@list.first.userid
			end
			def originatorid
				@list.first.originatorid
			end
			
			def display_message
				__req = PageHandler.current.subrequest(StringIO.new(), :GetRequest, "/ObservableDisplay/display/#{self.class.name.gsub(':', '_')}", {'obj' => self}, :Internal, nil )
				if (__req.reply.ok?)
					__req.reply.out.string;
				else 
					"Error!"
				end
			end

			def image
				return originator
			end
		end
		
		def collapsed_event(list)
			list2 = list.select{|elt| elt.object.kind_of? Pic }
			
			if (list2.empty?)
				return list.first
			end
		
			CollapsedPicEvent.new(list2)
		end
		
		# This doesn't directly depend on self, but it needs a base class/descendant
		# implementation, so this is the most straightforward way.
		def collapse?(event)
			if (event.classtypeid == Pic.typeid)
				return (event.originatorid == self.userid and event.object.gallery == self.gallery)
			end
			if (event.classtypeid == GalleryFolder.typeid)
				return (event.originatorid == self.userid and event.object == self.gallery)
			end
			false;
		end
		
		def collapsable_bucket(event)
			return [GalleryFolder::typeid, self.userid, self.gallery, event.eventtype]
		end
		
		def after_create
			if (self.gallery.album_cover.nil?)
				self.gallery.previewpicture = id;
				self.gallery.store
			end
			LegacyModqueue::ModItem.create("MOD_GALLERY", userid, id, User.find(:first, :nomemcache, :refresh, userid).plus?)
			# Super called at the end, because it inserts an event and we don't want that event
			# added repeatedly
			super();
		end

		
		def self.handle_moderated(item)
			$log.info "Item moderated: #{item} #{item.points}", :info
			item_key = Marshal.load(item.item);
			pending_item = LegacyGallery::Pic.find(:first, item_key);
			if (item.points < 0)
				pic = LegacyGallery::Pic.find(pending_item.userid);
				pic.delete
			end
			pending_item.delete;
		end
		
		def delete
			if !gallery.nil? && (gallery.previewpicture == self.id)
				gallery.fix_cover!(self.id)
			end
			
			LegacyGallery::Pic.db.query("UPDATE #{LegacyGallery::Pic.table} SET priority = priority - 1 WHERE priority > ?", self.priority);
			
			NexFile.new("gallery", self.userid, "#{self.id}.jpg").delete
			NexFile.new("gallerythumb", self.userid, "#{self.id}.jpg").delete
			NexFile.new("galleryfull", self.userid, "#{self.id}.jpg").delete
			NexFile.new("source", self.userid, "#{self.id}.jpg").delete
			
			super();
		end
		
		def get_source
			source_pic = NexFile.load("source", self.userid, "#{self.id}.jpg")
			if not (source_pic.exists?)
				source_pic = NexFile.load("gallery", self.userid, "#{self.id}.jpg")
			end
			return source_pic
		end
		
		def move_to_end
			self.priority = GalleryFolder.max_priority(self.userid, self.galleryid) + 1
		end
		
		def uri_info(mode = 'other')
			return [@description, "/galleries/#{owner.username}/#{gallery.id}/#{gallery.name}/#{id}"];
		end
		
		def self.generate_path(userid, id)
			return "#{userid/1000}/#{userid}/#{id}.jpg"
		end
		
		def img_info(type="galleryfull")
			return [@description, "#{$site.image_url}/#{type}/#{self.class.generate_path(self.userid, self.id)}"];
		end
		
		def full
			return Img.new(@description, "#{$site.image_url}/galleryfull/#{self.class.generate_path(self.userid, self.id)}");
		end
		
		def thumb
			return Img.new(@description, "#{$site.image_url}/gallerythumb/#{self.class.generate_path(self.userid, self.id)}");
		end

		def source
			return Img.new(@description, "#{$site.image_url}/source/#{self.class.generate_path(self.userid, self.id)}");
		end
		
		def square
			return Img.new(@description, "#{$site.image_url}/gallerysquare/#{self.class.generate_path(self.userid, self.id)}");
		end

	end

	class GalleryFolder < Storable
		relation_multi_cached :pics_internal, [:ownerid, :id], Pic, "ruby_gallerypics", :usercat
	end
	
end
