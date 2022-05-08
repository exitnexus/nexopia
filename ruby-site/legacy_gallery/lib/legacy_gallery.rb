lib_require :Core, "storable/storable"
lib_want :Observations, "observable"

module LegacyGallery
	class GalleryFolder < Storable
		init_storable(:usersdb, "gallery");

		relation_singular :owner, :ownerid, User
		
		if (site_module_loaded?(:Observations))
			include Observations::Observable;
			OBSERVABLE_NAME = "Gallery Entries"
			observable_event :create, proc{
				name = self.class.name.gsub(':', '_')
				path = "/ObservableDisplay/display/#{name}";
				__req = PageHandler.current.subrequest(StringIO.new(), :GetRequest, path, {'gallery' => self}, :Internal, nil ) 
				if (__req.reply.ok?)
					__req.reply.out.string;
				else 
					"Error!"
				end
			}
		end
		#observable_event :edit, proc{"#{owner.link} changed the gallery named #{name}."}


		#@pics  = Pic.memcache_find("ruby_gallerypics", [ownerid, id], 86400, :usercat, ownerid, id);

		def collapsed_event(list)
			list2 = list.select{|elt| elt.object.kind_of? Pic }
			
			if (list2.empty?)
				return list.first
			end
		
			Pic::CollapsedPicEvent.new(list2)
		end
		
		# This doesn't directly depend on self, but it needs a base class/descendant
		# implementation, so this is the most straightforward way.
		def collapse?(event)
			if (event.classtypeid == Pic.typeid)
				return (event.originatorid == self.userid and event.object.gallery == self)
			end
			false;
		end
		
		def collapsable_bucket(event)
			return [GalleryFolder::typeid, owner.userid, self, event.eventtype]
		end


		def after_create
			permissions = {"none" => 0, "friends" => 1, "loggedin" => 2, "anyone" => 3}
			if (permissions[self.permission] > permissions[owner.gallery])
				owner.gallery = self.permission
			end
			# Super called at the end, because it inserts an event and we don't want that event
			# added repeatedly
			super
		end
	
		def before_delete
			self.pics.each {|pic|
				pic.delete
			}
		end
		
		def pics
			#Run a quick check for consistency.  Might be possible to remove
			#this on the live site, but its fairly quick so it may not be 
			#necessary.
			ordered_pics = self.pics_internal.sort_by{|pic| pic.priority}
			ordered_pics.each_with_index{|pic, index|
				if (pic.priority != index)
					pic.priority = index;
					pic.store;
				end
			}
			return ordered_pics
		end
			


		def self.get_by_id(uid, id)
			return GalleryFolder.find(:first, [uid, id], :promise);	
		end
		
		def fix_cover!(avoid_ids=[])
			avoid_ids = [*avoid_ids]
			return if (self.album_cover && self.album_cover.galleryid == self.id && !avoid_ids.include?(self.previewpicture))
			self.previewpicture = 0
			self.pics.each {|pic|
				if (!avoid_ids.include?(pic.id))
					self.previewpicture = pic.id
					break
				end
			}
			self.store
		end
		
		def album_cover
			return Pic.find(:first, @ownerid, @previewpicture);
		end
		
		def userid
			return @ownerid;
		end

		def move_pic(picture, position)
	
			if (picture.priority == position + 1 || picture.priority == position - 1)
				ordered_pics = pics.sort_by{|pic| pic.priority}
				other = ordered_pics[position] 
				if !other.nil?
					other.priority, picture.priority = picture.priority, other.priority;
					other.store;
					picture.store;
				end
				return;
			end
			
			if (position < picture.priority)
				Pic.db.query("UPDATE #{Pic.table} SET priority = priority + 1 WHERE userid = # && galleryid = ? && priority >= ? && priority < ?", picture.userid, picture.galleryid, position, picture.priority);
			else
				Pic.db.query("UPDATE #{Pic.table} SET priority = priority - 1 WHERE userid = # && galleryid = ? && priority > ? && priority <= ?", picture.userid, picture.galleryid, picture.priority, position);
			end
			picture.priority = position;
			picture.store;
		end
		
		def uri_info(mode = 'other')
			case mode
			when "self"
				return [@name, "/#{owner.username}/gallery/#{@id}"];
			when "other"
				return [@name, "/galleries/#{owner.username}/#{@id}/#{name}"];
			end
		end
		
		def img_info(mode = 'self')
			if (!album_cover)
				return [@name, "#{$site.static_files_url}/Gallery/images/noalbum.gif"]
			end
			return album_cover.thumb.img_info;
		end
		
		class << self
			def max_priority(userid, galleryid)
				result = self.db.query("SELECT MAX(priority) FROM `gallerypics` WHERE userid=# and galleryid=?", userid, galleryid).fetch_set.first;
				return result["MAX(priority)"].to_i
			end
		end
	end
end

class User < Cacheable
	relation_multi_cached :galleries, :userid, LegacyGallery::GalleryFolder, "ruby_galleries"
end

