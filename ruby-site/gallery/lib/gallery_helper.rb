lib_require :Gallery, "gallery_pic"
lib_require :Gallery, "image_manipulate"

require 'digest/md5';

module Gallery
	class GalleryHelper
		class << self
			def store_pic(file, userid, galleryid, description)
				tmpfile_name = file.path
				$log.info "starting store_pic #{tmpfile_name} (#{file})"
				gallery = GalleryFolder.get_by_id(userid, galleryid)

				pic = Pic.new
				pic.userid = userid;
				pic.id = Pic.get_seq_id(userid);

				pic.description = description;
				pic.galleryid = galleryid;
				pic.priority = GalleryFolder.max_priority(pic.userid, pic.galleryid) + 1
				pic.userpicid = 0;
				Tempfile.open("resize") {|resize|
					begin
						res = ImageManipulate.with_image(tmpfile_name) {|img|
							$log.info("Resizing #{tmpfile_name} to max 2560x1920", :spam, :gallery)
							img.resize_max(2560, 1920) {|max|
								max.save(resize.path)
							}
						}
						if (!res)
							raise TypeError, "Unknown failure in imagescience" # works around weird situation where imagescience doesn't yield or raise.
						end
					rescue TypeError
						$log.error
						raise "We were not able to load your image."
					end
			
					File.open(resize.path, "r") {|original|
						md5 = Digest::MD5.new.update(original.read).to_s
						if (md5.nil?)
							$log.info "MD5 is null. WTF?\nuserid = #{pic.userid}\nid = #{pic.id}\nOn the off chance that we can fix this by just trying again, we'll try again", :error
							raise UserError.new("Error while saving image.  Please try again.")
						end
						pic.md5 = md5
						# Set the preview picture on the gallery only if it doesn't have one already
						gallery.previewpicture = pic.id if gallery.previewpicture.zero?
						gallery.store
			
						pic.store
			
						source_pic = SourceFileType.new(pic.userid, pic.id)
						original.rewind
						source_pic.put_contents(original)
					}
		
					return pic;
				}
			end
		end
	end
end