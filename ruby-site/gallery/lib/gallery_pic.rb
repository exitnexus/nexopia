lib_require :Core, "storable/storable"
lib_want :GoogleProfile, "google_user"
lib_require :Gallery, "gallery_folder"
lib_require :FileServing, "type"
lib_require :Gallery, "image_manipulate"

module Gallery
	class Crop < Storable
		init_storable(:usersdb, "gallerypiccrops")

		extend JSONExported
		json_field(:x, :y, :w, :h, :gallerypicid, :userid, :time)

		def before_create
			validate!
		end

		def before_update
			validate!
		end

		def validate!
			if (self.x + self.w > 1 + Float::EPSILON || self.y + self.h > 1 + Float::EPSILON || self.w <= 0 || self.h <= 0)
				raise "Invalid dimensions specified for image crop."
			end
			return true
		end
	end # class Crop < Storable


	class SourcePic < Storable
		init_storable(:usersdb, "sourcepics")
	end # class SourcePic < Storable


	class SourceFileType < FileServing::Type
		register "source"
		immutable

		def self.new_external_url(*path)
			path.shift # take off the revision component
			if (path.last)
				path.last.gsub!(/\.[^\.]+$/, '')
			else
				raise Exception.new("path.last is nil. path = #{path}")
			end
			super(*path)
		end

		# This pulls from the legacy mogile instance if it's set up 
		# by pulling from the mogile paths that the old site used in order.
		def not_found(out_file)
			if (legacy = $site.mogile_connection(:legacy))				
				userid, picid = @path
				if (data = legacy.get_file_data([self.class.typeid, userid, picid].join('/')))
					out_file.write(data)
					return true
				end
			end
			return super(out_file) # let it throw a 404
		end
	end # class SourceFileType < FileServing::Type


	class Pic < Cacheable
		extend TypeID
		
		UNMODERATED = 0
		PENDING = 1
		ACCEPTED = 2
		FAILED = 3

		MAX_PICS_PER_GALLERY = 50
		MAX_PICS = 250

		PLUS_MAX_PICS_PER_GALLERY = 100
		PLUS_MAX_PICS = MAX_PICS*50

		set_enums :userpic => {
			:unmoderated => UNMODERATED,
			:pending => PENDING,
			:accepted => ACCEPTED,
			:failed => FAILED
			}, :signpic => {
				:unmoderated => UNMODERATED,
				:pending => PENDING,
				:accepted => ACCEPTED,
				:failed => FAILED
			}

			init_storable(:usersdb, "gallerypics");

			relation_singular :crop, [:userid, :id], Crop
			relation_singular :gallery, [:userid, :galleryid], GalleryFolder
			relation_singular :owner, :userid, User		

			extend JSONExported
			json_field(:description, :id, :full_link, :normal_link)

			#automatically set the created timestamp if it hasn't been set manually.
			def before_create
				if (self.created.zero?)
					self.created = Time.now.to_i
				end
				validate!(false)
			end

			def after_create
				if (site_module_loaded?(:GoogleProfile))
					self.owner.update_hash
				end
				super
			end

			def before_update
				validate!(true)
			end

			def after_update
				if (site_module_loaded?(:GoogleProfile))
					self.owner.update_hash
				end
				super
			end

			def before_delete
				if (site_module_loaded?(:GoogleProfile))
					self.owner.update_hash
				end
				super
			end

			def after_delete
				if(!crop.nil?)
					crop.delete
				end
			end

			#checks that the picture doesn't violate any constraints (pics per gallery, total pics)
			#raises an exception if it does
			def validate!(update)
				if (self.owner.plus?)
					max_pics = PLUS_MAX_PICS
					max_pics_per_gallery = PLUS_MAX_PICS_PER_GALLERY
				else
					max_pics = MAX_PICS
					max_pics_per_gallery = MAX_PICS_PER_GALLERY
				end

				if ((update && self.modified?(:galleryid)) || !update) #don't fail a validation if it's already in a gallery and not moving
					if (self.gallery.nil?)
						raise GalleryError, "Destination gallery has been deleted."
					elsif (self.gallery.pics.length >= max_pics_per_gallery)
						raise GalleryError, "You have already reached the maximum number of pictures in #{self.gallery.name} (#{max_pics_per_gallery})."
					end
				end

				if (!update && self.owner.total_pics_count >= max_pics) #don't fail validation if we are resaving an image we've already uploaded
					raise GalleryError, "You have reached the maximum number of pictures you may upload (#{max_pics})."
				end
				return true
			end

			def after_create
				if (self.gallery.album_cover.id.zero?)
					self.gallery.previewpicture = id;
					self.gallery.store
				end
				Gallery::GalleryQueue.add_item(userid, id, User.find(:first, :nomemcache, :refresh, userid).plus?)
				# Super called at the end, because it inserts an event and we don't want that event
				# added repeatedly
				super();
			end

			def self.handle_moderated(item)
				$log.info "Item moderated: #{item} #{item.points}", :info
				item_key = Marshal.load(item.item);
				pending_item = GalleryPic.find(:first, item_key);
				if (item.points < 0)
					pic = GalleryPic.find(pending_item.userid);
					pic.delete
				end
				pending_item.delete;
			end

			def delete(fix_order=true)
				if fix_order && !gallery.nil? && (gallery.previewpicture == self.id)
					gallery.fix_cover!(self.id)
					Gallery::Pic.db.query("UPDATE #{Gallery::Pic.table} SET priority = priority - 1 WHERE priority > ? && userid = # && galleryid = ?", self.priority, self.userid, self.galleryid);
				end


				source = SourceFileType.new(self.userid, self.id)
				source.remove(true) # recursively delete all derived images as well.

				pic = Pics.find(:first, self.userid, self.id)
				pic.delete if (pic)

				super();
			end

			def signpic?
				return (self.signpic == :accepted) ? true : false
			end

			def get_source
				return SourceFileType.new(self.userid, self.id)
			end

			def move_to_end
				self.priority = GalleryFolder.max_priority(self.userid, self.galleryid) + 1
			end

			def uri_info(mode = 'other')
				case mode
				when "thumb", "landscape", "landscapethumb", "landscapemini", "full", "square", "squaremini"
					img_info(mode)
				else
					return [@description, "/users/#{urlencode(owner.username)}/gallery/#{galleryid}-#{self.gallery.name_slug}/#{id}"];
				end
			end

			def self.generate_path(userid, id, revision)
				return "#{revision}/#{userid}/#{id}.jpg"
			end

			def self.img_info(userid, id, alt = "", type="normal", revision = 1)
				suffix = case type
				when "thumb", "landscape", "landscapethumb", "landscapemini", "full", "square", "squaremini", 'classicprofile', 'profile'
					type
				else
					""
				end
				return [alt, "#{$site.image_url}/gallery#{suffix}/#{self.generate_path(userid, id, revision)}"];
			end

			def img_info(type="normal")
				self.class.img_info(self.userid, self.id, @description, type, self.revision)
			end

			def full
				return Img.new(*img_info("full"))
			end

			def normal
				return Img.new(*img_info("normal"))
			end

			def thumb
				return Img.new(*img_info("thumb"))
			end

			def square
				return Img.new(*img_info("square")) # 100x100
			end

			def squaremini
				return Img.new(*img_info("squaremini")) # 75x75
			end

			def landscapemini
				return Img.new(*img_info("landscapemini"))
			end

			def landscape
				return Img.new(*img_info("landscape"))
			end

			def landscapethumb
				return Img.new(*img_info("landscapethumb"))
			end

			def full_link
				return img_info("full")[1]
			end

			def normal_link
				return img_info("normal")[1]
			end
		end

		class GalleryFullFileType < SourceFileType
			register "galleryfull"
			immutable

			def generate(input, output)
				ImageManipulate.with_image(input.path) {|img|
					img.resize_max(2560, 1600) {|smaller| # this isn't right. Needs to keep aspect ratio.
						smaller.save(output.path)
					}
				}			
			end
		end

		class GalleryFileType < SourceFileType
			register "gallery"
			immutable

			def generate(input, output)
				ImageManipulate.with_image(input.path) {|img|
					img.resize_max(640, 640) {|smaller| # FIXME: this isn't right. Needs to keep aspect ratio.
						smaller.save(output.path)
					}
				}			
			end
		end

		class ProfileFileType < SourceFileType
			register "galleryclassicprofile"
			immutable

			def generate(input, output)
				ImageManipulate.with_image(input.path) {|img|
					img.resize_max(470, 214) {|smaller| # FIXME: this isn't right. Needs to keep aspect ratio.
						smaller.save(output.path)
					}
				}			
			end
		end

		class GalleryProfileFileType < SourceFileType
			register "galleryprofile"
			immutable

			def generate(input, output)
				ImageManipulate.with_image(input.path) {|img|
					img.resize_max(320, 320) {|smaller| # FIXME: this isn't right. Needs to keep aspect ratio.
						smaller.save(output.path)
					}
				}			
			end
		end	

		class GalleryThumbFileType < SourceFileType
			register "gallerythumb"
			immutable

			def generate(input, output)
				ImageManipulate.with_image(input.path) {|img|
					img.resize_max(100, 150) {|smaller| # FIXME: this isn't right. Needs to keep aspect ratio.
						smaller.save(output.path)
					}
				}			
			end
		end

		module CropInformation
			def get_crop(pic, path)
				pic_obj = Pic.find(:first, path[0].to_i, path[1].to_i)
				crop = pic_obj && pic_obj.crop
				if (!crop.nil?)
					return [crop.x * pic.width, crop.y * pic.height, crop.w * pic.width, crop.h * pic.height].collect {|i| i.to_i }
				else
					$log.info("Cropping image with dimensions: #{pic.width}, #{pic.height}", :spam, :gallery)
					# figure out the best crop for this image based on its size.
					if (pic.width > pic.height) # landscape
						$log.info("Cropping landscape.", :spam, :gallery)
						cx = pic.height * (288.to_f/240.0)
						return [(pic.width/2) - (cx / 2), 0, cx, pic.height].collect {|i| i.to_i}
					else # portrait
						$log.info("Cropping portrait.", :spam, :gallery)
						cy = pic.width * (240.to_f/288.0)
						return [0, (pic.height/2) - (cy / 2), pic.width, cy].collect {|i| i.to_i}
					end
				end
			end
		end

		class GalleryLandscapeFileType < SourceFileType
			register "gallerylandscape"

			include CropInformation
			def generate(input, output)
				ImageManipulate.with_image(input.path) {|img|
					x, y, cx, cy = get_crop(img, @path)
					$log.info("Cropping image in bounds #{x},#{y},#{cx},#{cy}", :spam, :gallery)
					img.with_crop(x, y, x+cx, y+cy) {|crop|
						crop.resize(288, 240) {|landscape|
							landscape.save(output.path)
						}
					}
				}
			end
		end
		class GalleryLandscapeMiniFileType < GalleryLandscapeFileType
			register "gallerylandscapemini"

			def generate(input, output)
				ImageManipulate.with_image(input.path) {|img|
					img.thumbnail(50) {|thumb|
						thumb.save(output.path)
					}
				}			
			end
		end
		class GalleryLandscapeThumbFileType < GalleryLandscapeFileType
			register "gallerylandscapethumb"

			def generate(input, output)
				ImageManipulate.with_image(input.path) {|img|
					img.thumbnail(100) {|thumb|
						thumb.save(output.path)
					}
				}			
			end
		end


		class GallerySquareFileType < GalleryLandscapeFileType
			register "gallerysquare"

			def generate(input, output)
				ImageManipulate.with_image(input.path) {|img|
					img.cropped_thumbnail(100) {|thumb|
						thumb.save(output.path)
					}
				}			
			end
		end
		class GallerySquareMiniFileType < GallerySquareFileType
			register "gallerysquaremini"

			def generate(input, output)
				ImageManipulate.with_image(input.path) {|img|
					img.thumbnail(75) {|thumb|
						thumb.save(output.path)
					}
				}
			end
		end

		class GalleryFolder < Cacheable
			relation :multi, :pics_internal, [:ownerid, :id], Pic, :usercat
			relation :singular, :internal_album_cover, [:ownerid, :previewpicture], Pic
			relation :count, :size, [:ownerid, :id], Gallery::Pic, :usercat
			
			def album_cover
				if (self.previewpicture == 0)
					return nil
				else
					return self.internal_album_cover
				end
			end
		end


		class GalleryError < SiteError
		end
	end


	module Gallery
		class Pic < Cacheable
			extend JSONExported
			json_field :id
			json_field :description

		end
		class GalleryFolder < Cacheable
			extend JSONExported
			json_field :id
			json_field :name
			json_field :description
		end
	end

	class User < Cacheable
		extend JSONExported
		json_field :userid
		json_field :galleries

		def total_pics_count
			result = self.class.db.query("SELECT count(*) FROM #{Gallery::Pic.table} WHERE userid = # LIMIT 1", self.userid)
			count = result.fetch_field
			return count.to_i
		end
	end
