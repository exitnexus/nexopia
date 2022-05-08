lib_require :Worker, "kernel_addon"
lib_require :Images, "images"
lib_require :Core, "uploads"
lib_want :Uploader, "file_handler"
lib_require :Images, "image_creator"
lib_require :Json, "exported"

class GalleryModule < SiteModuleBase
	
	worker_task :upload_handle
	worker_task :resize_images
	worker_task :create_image
	
	set_javascript_dependencies([SiteModuleBase.get(:Json)])

	def self.resize_images(pic)
		queue_create_image(:thumb, pic, NexFile.new("gallerythumb", pic.userid, "#{pic.id}.jpg"))
		queue_create_image(:normal, pic, NexFile.new("gallery", pic.userid, "#{pic.id}.jpg"))
		queue_create_image(:full, pic, NexFile.new("galleryfull", pic.userid, "#{pic.id}.jpg"))
		queue_create_image(:square, pic, NexFile.new("gallerysquare", pic.userid, "#{pic.id}.jpg"))
	end

#################ImageCreator stuff
	extend ImageCreator
	
	def self.get_source(pic)
		source_file = nil;
		if (pic.userpicid and pic.userpicid != 0)
			user_pic = Pics.find(:first, :legacy, pic.userid, pic.userpicid)
			ignored, source_file = UserpicsModule::get_source(user_pic)
		else
			source_file = NexFile.load("source", pic.userid, "#{pic.sourceid}.jpg")
			if not (source_file.exists?)
				source_file = NexFile.load("gallery", pic.userid, "#{pic.id}.jpg")
			end
		end
		[pic, source_file]
	end
	
	def self.user_crop(input, width, height)
		pic,img = input
		if (!pic.crop.nil?)
			ImagesModule::crop(img, pic.crop.x * img.columns, pic.crop.y * img.rows, pic.crop.w * img.columns, pic.crop.h * img.rows, true);
			ImagesModule::crop_resized(img, width, height)
		else
			ImagesModule::crop_resized(img, width, height)
			$log.info "No user crop available.", :warning
		end
		
		[pic,img]
	end

	def self.queue_create_image(handle, pic, output_file)
		WorkerModule::do_task(SiteModuleBase.get(:Gallery), "create_image", [handle, pic, output_file]);
	end

	image_type :normal, :get_source, :to_rmagick, [:resize, $site.config.gallery_image_size], :tag, [:write_old_style, "gallery"]
	image_type :full, :get_source, :to_rmagick, :tag, [:write_old_style, "galleryfull"]
	image_type :thumb, :get_source, :to_rmagick, [:resize, $site.config.gallery_thumb_image_size], [:write_old_style, "gallerythumb"]
	
	image_type :square, :get_source, :to_rmagick, [:user_crop, 120, 100], [:crop_center, 100, 100], [:write, "gallerysquare"]
	image_type :squaremini, :get_source, :to_rmagick, [:user_crop, 90, 75], [:crop_center, 75, 75], [:write, "gallerysquaremini"]
	
	image_type :landscape, :get_source, :to_rmagick, [:user_crop, 240, 200], [:write, "gallerylandscape"]
	image_type :landscapethumb, :get_source, :to_rmagick, [:user_crop, 100, 83], [:write, "gallerylandscapethumb"]
	image_type :landscapemini, :get_source, :to_rmagick, [:user_crop, 50, 42], [:write, "gallerylandscapemini"]

##################


	NexFile::filetype 'gallerysquare', {
		:disk => nil, 
		:mog_path => proc{|userid,img_id|
			"generated/gallerysquare/#{userid}/#{img_id}"
		} 
	}
	NexFile::filetype 'gallerysquaremini', {
		:disk => nil, 
		:mog_path => proc{|userid,img_id|
			"generated/gallerysquaremini/#{userid}/#{img_id}"
		} 
	}
	NexFile::filetype 'gallery', 		{ 
		:disk => proc{|userid,filename|
			"#{$site.config.gallery_dir}/#{userid/1000}/#{userid}/#{filename}"
		}, 
		:mog_path => proc{|userid,raw_path| 
			raw_path =~ /(.*)\.(.*)/
			"3/#{userid}/#{$1}"
		}
	}
	NexFile::filetype 'galleryfull', 	{ 
		:disk => proc{|userid,filename|
			"#{$site.config.gallery_full_dir}/#{userid/1000}/#{userid}/#{filename}"
		}, 
		:mog_path => proc{|userid,raw_path| 
			raw_path =~ /(.*)\.(.*)/
			"4/#{userid}/#{$1}"
		} 
	}
	NexFile::filetype 'gallerythumb', 	{ 
		:disk => proc{|userid,filename|
			"#{$site.config.gallery_thumb_dir}/#{userid/1000}/#{userid}/#{filename}"
		}, 
		:mog_path => proc{|userid,raw_path| 
			raw_path =~ /(.*)\.(.*)/
			"5/#{userid}/#{$1}"
		} 
	}
	NexFile::filetype 'source', 			{ 
		:disk => proc{|userid,filename|
			"#{$site.config.source_pic_dir}/#{userid/1000}/#{userid}/#{filename}"
		}, 
		:mog_path => proc{|userid,raw_path| 
			raw_path =~ /(.*)\.(.*)/
			"6/#{userid}/#{$1}"
		}
	}
	NexFile::filetype 'gallerylandscape', {
		:disk => nil, 
		:mog_path => proc{|userid,img_id|
			"generated/gallerylandscape/#{userid}/#{img_id}"
		} 
	}
	NexFile::filetype 'gallerylandscapethumb', {
		:disk => nil, 
		:mog_path => proc{|userid,img_id|
			"generated/gallerylandscapethumb/#{userid}/#{img_id}"
		} 
	}
	NexFile::filetype 'gallerylandscapemini', {
		:disk => nil, 
		:mog_path => proc{|userid,img_id|
			"generated/gallerylandscapemini/#{userid}/#{img_id}"
		} 
	}
	
	
	if (site_module_loaded? :Uploader)
		file_handler ["source"], proc{|filename, fclass|
			lib_require :Gallery, "gallery_folder"
			lib_require :Gallery, "gallery_pic"
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

		file_handler ["gallerythumb|gallery|galleryfull|gallerysquare|gallerysquaremini|gallerylandscape|gallerylandscapethumb|gallerylandscapemini"], 
			proc{|filename, fclass|	serve(filename, fclass)
		}

	end
	
	class << self
		def serve(filename, file_class)
			lib_require :Gallery, "gallery_folder"
			lib_require :Gallery, "gallery_pic"
			
			if (filename =~ /^(\d+)\/(\d+)\/(\d+)\.(...+)/)
				$log.info "gallery-file-server: Requesting #{file_class} #{$2} #{$3}", :info
				file = NexFile.load(file_class, $2.to_i, "#{$3}.jpg");
				if (!file.request)
					$log.info "gallery-file-server: Not found.", :debug
					pic = Gallery::Pic.find(:first, $2.to_i, $3.to_i)
					if (!pic)
						raise PageError.new(404), "Not a valid image."
					end
					
					if (file_class == "gallerythumb")
						queue_create_image(:thumb, pic, file)
					elsif (file_class == "gallery")
						queue_create_image(:normal, pic, file)
					elsif (file_class == "galleryfull")						
						queue_create_image(:full, pic, file)
					elsif (file_class == "gallerysquare")						
						queue_create_image(:square, pic, file)
					elsif (file_class == "gallerysquaremini")						
						queue_create_image(:squaremini, pic, file)
					elsif (file_class == "gallerylandscape")						
						queue_create_image(:landscape, pic, file)
					elsif (file_class == "gallerylandscapethumb")						
						queue_create_image(:landscapethumb, pic, file)
					elsif (file_class == "gallerylandscapemini")						
						queue_create_image(:landscapemini, pic, file)
					end

					file.request || puts("Error - couldn't load file '#{file_class}'")
				end
			else
				raise PageError.new(404), "Not a valid image."
			end
			
			throw :page_done;

		end
		
		def upload_handle(filename, userid, params, original_filename)
			lib_require :Worker, "post_process_queue"
			require 'RMagick'

			pic = Gallery::Pic.new
			pic.userid = userid;
			pic.id = Gallery::Pic.get_seq_id(userid);
		
			if (params['description'].kind_of?(Array))
				pic.description = params['description'].first || filename
			else
				pic.description = params['description'] || filename
			end
		
			if params['selected_gallery'].kind_of?(Array)
				pic.galleryid = params['selected_gallery'].first.to_i
			else
				pic.galleryid = params['selected_gallery'].to_i
			end
			pic.priority = Gallery::GalleryFolder.max_priority(pic.userid, pic.galleryid) + 1
			pic.userpicid = nil;
			pic.store;

		
			original = NexFile.load("temp", filename)
			
			image = Magick::Image.read(original.disk_path).first
			if (!image['exif:ImageDescription'])
				image['exif:ImageDescription'] = "Nexopia.com:#{userid}"
				image.write(original.disk_path);
			end

			source_file = Gallery::SourcePic.new();
			source_file.userid = pic.userid;
			source_file.id = Gallery::SourcePic.get_seq_id(pic.userid);
			source_file.store;
			pic.sourceid = source_file.id;
			
			source_pic = NexFile.new("source", pic.userid, "#{pic.sourceid}.jpg")
			source_pic.write(original.read)
			original.delete;
			
			Worker::PostProcessQueue.queue(GalleryModule, "resize_images", 
				[ pic ]
			);
			return true
		end
		
	end
end
