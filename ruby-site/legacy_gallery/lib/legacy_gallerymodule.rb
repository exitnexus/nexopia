lib_want :Uploader, "file_handler"
lib_require :Worker, "kernel_addon"
lib_require :Images, "images"
lib_require :Core, "user_error"
lib_require :Images, "image_creator"

class LegacyGalleryModule < SiteModuleBase
	
	#################ImageCreator stuff
	extend ImageCreator
	worker_task :create_image
	
	def self.get_source(pic)
		source_file = NexFile.load("source", pic.userid, "#{pic.id}.jpg")
		if not (source_file.exists?)
			source_file = NexFile.load("gallery", pic.userid, "#{pic.id}.jpg")
		end
		[pic, source_file]
	end
	
	def self.queue_create_image(handle, pic, output_file)
		WorkerModule::do_task(SiteModuleBase.get(:LegacyGallery), "create_image", [handle, pic, output_file]);
	end
	
	image_type :normal, :get_source, :to_rmagick, [:resize, $site.config.gallery_image_size], :tag, [:write_old_style, "gallery"]
	image_type :full, :get_source, :to_rmagick, :tag, [:write_old_style, "galleryfull"]
	image_type :thumb, :get_source, :to_rmagick, [:resize, $site.config.gallery_thumb_image_size], [:write_old_style, "gallerythumb"]
	
	##################
	NexFile::filetype 'gallery', { 
		:disk => proc{|userid,filename|
			"#{$site.config.gallery_dir}/#{userid/1000}/#{userid}/#{filename}"
		}, 
		:mog_path => proc{|userid,raw_path| 
			raw_path =~ /(.*)\.(.*)/
			"3/#{userid}/#{$1}"
		}
	}
	NexFile::filetype 'galleryfull', { 
		:disk => proc{|userid,filename|
			"#{$site.config.gallery_full_dir}/#{userid/1000}/#{userid}/#{filename}"
		}, 
		:mog_path => proc{|userid,raw_path| 
			raw_path =~ /(.*)\.(.*)/
			"4/#{userid}/#{$1}"
		} 
	}
	NexFile::filetype 'gallerythumb', { 
		:disk => proc{|userid,filename|
			"#{$site.config.gallery_thumb_dir}/#{userid/1000}/#{userid}/#{filename}"
		}, 
		:mog_path => proc{|userid,raw_path| 
			raw_path =~ /(.*)\.(.*)/
			"5/#{userid}/#{$1}"
		} 
	}
	NexFile::filetype 'source', { 
		:disk => proc{|userid,filename|
			"#{$site.config.source_pic_dir}/#{userid/1000}/#{userid}/#{filename}"
		}, 
		:mog_path => proc{|userid,raw_path| 
			raw_path =~ /(.*)\.(.*)/
			"6/#{userid}/#{$1}"
		}
	}
	
	
	#if (site_module_loaded? :Uploader)
	file_handler ["source"], proc{|filename, fclass|
		if (filename =~ /^(\d+)\/(\d+)\/(\d+)\.(...+)/) 
			source_pic = NexFile.load("source", $2.to_i, "#{$3}.jpg")
			if (!source_pic.request)
				raise PageError.new(404), "Not a valid source image"
			end
		else
			raise PageError.new(404), "Not a valid image."
		end
		throw :page_done
	}
	
	file_handler ["gallerythumb|gallery|galleryfull"], 
	proc{|filename, fclass|	serve(filename, fclass)
	}
	
	#end
	
	class << self
		def serve(filename, file_class)
			lib_require :LegacyGallery, "legacy_gallery_pic"
			
			if (filename =~ /^(\d+)\/(\d+)\/(\d+)\.(...+)/)
				$log.info "gallery-file-server: Requesting #{file_class} #{$2} #{$3}", :info
				file = NexFile.load(file_class, $2.to_i, "#{$3}.jpg");
				if (!file.request)
					$log.info "gallery-file-server: Not found.", :debug
					pic = LegacyGallery::Pic.find(:first, $2.to_i, $3.to_i)
					if (!pic)
						raise PageError.new(404), "Not a valid image."
					end
					
					if (file_class == "gallerythumb")
						queue_create_image(:thumb, pic, file)
					elsif (file_class == "gallery")
						queue_create_image(:normal, pic, file)
					elsif (file_class == "galleryfull")						
						queue_create_image(:full, pic, file)
					end
					
					file.request || puts("Error - couldn't load file '#{file_class}'")
				end
			else
				raise PageError.new(404), "Not a valid image."
			end
			
			throw :page_done;
			
		end
	end
end