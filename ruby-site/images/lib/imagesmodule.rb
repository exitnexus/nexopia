lib_require :Worker, "kernel_addon"
require 'rubygems'
require 'RMagick'

class ImagesModule < SiteModuleBase
	worker_task :resize_and_tag_image
	worker_task :resize_image
	
	class << self
#############################
		def resize_and_tag_image(file, size, output)
			return if output.exists?
		
			file.get_file
			$log.info "Resizing and tagging '#{file.disk_path}' to #{size} and saving to #{output.disk_path}", :debug
			
			image = Magick::Image.read(file.disk_path).first
			resize(image, size)
			tag(image, "#{$site.config.site_base_dir}/images/static/images/nexopia_tag.png")
			
			output.disk_store("");
			image.write(output.disk_path);
			return true;
		end

		def resize_image(file, size, output)
			return if output.exists?
		
			file.get_file
			$log.info "Resizing '#{file.disk_path}' to #{size}", :debug
			
			image = Magick::Image.read(file.disk_path).first
			resize(image, size)
			
			output.disk_store("");
			image.write(output.disk_path);
			return true;
		end

################################3333
		
		def resize(img, new_size)
			img.change_geometry(new_size){ |cols, rows, img|
				img.thumbnail!(cols, rows)
			}
		end
	
		def tag(img, tagfilename)
			#We should set Image::Info attributes to load less of the file.
			tag = Magick::Image.read(tagfilename).first;
	
			img.composite!(tag, Magick::SouthEastGravity, 10,10, Magick::OverCompositeOp)
		end

		def crop_resized(image, height, width)
			image.crop_resized!(height, width)
		end
		
		def crop(image, *args)
			image.crop!(*args)
		end
		
	end


end