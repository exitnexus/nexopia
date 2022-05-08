lib_want :Uploader, "file_handler"
lib_require :Worker, "kernel_addon"
lib_require :Images, "images"
lib_require :Core, "user_error"
lib_require :Images, "image_creator"

class UserpicsModule < SiteModuleBase

######################ImageCreator
	extend ImageCreator
	worker_task :create_image

	def self.get_source(pic)
		source_file = NexFile.load("userpics", pic.userid, "#{pic.id}.jpg")
		return [pic, source_file]
	end

	def self.queue_create_image(handle, pic, output_file)
		WorkerModule::do_task(SiteModuleBase.get(:Userpics), "create_image", [handle, pic, output_file]);
	end

	image_type :normal, :get_source, :to_rmagick, [:resize, $site.config.gallery_image_size], [:write_old_style, "userpics"]
	image_type :thumb, :get_source, :to_rmagick, [:resize, $site.config.gallery_thumb_image_size], [:write_old_style, "userpicsthumb"]
#########################

#########################NexFile stuff	
	NexFile::filetype 'userpicsthumb', 	{ 
		:disk => proc{|userid,filename|
			"#{$site.config.user_pic_thumb_dir}/#{userid/1000}/#{userid}/#{filename}"
		}, 
		:mog_path => proc{|userid,raw_path|
			raw_path =~ /(.*)\.(.*)/
			"1/#{userid}/#{$1}"
		} 
	}
	NexFile::filetype 'userpics', 		{ 
		:disk => proc{|userid,filename|
			"#{$site.config.user_pic_dir}/#{userid/1000}/#{userid}/#{filename}"
		}, 
		:mog_path => proc{|userid,raw_path| 
			raw_path =~ /(.*)\.(.*)/
			"2/#{userid}/#{$1}"
		}
	}
####################################

	def moderate_queue_name
		return "Userpics"
	end
	
	#Serve a file by "userid, id" key
	def self.serve(filename, fclass)
		if (filename =~ /^(\d+)\/(\d+)\/(\d+)\.(...+)/)
			$log.info "userpic-file-server: Requesting #{fclass} #{$2} #{$3}", :info
			file = NexFile.load(fclass, $2.to_i, "#{$3}.jpg")
			if (!file.request)
				pic = Pics.find(:first, :legacy, $2.to_i, $3.to_i) || PicsPending.find(:first, $2.to_i, $3.to_i)
				raise(PageError.new(404), "Not a valid userpic") if (!pic)
				
				if (pic.gallerypicid and pic.gallerypicid != 0)
					if (fclass =~ /^userpics$/)
						PageHandler.current.rewrite(:GetRequest, "/gallery/#{pic.userid/1000}/#{pic.userid}/#{pic.gallerypicid}.jpg");
						throw :page_done
					elsif (fclass =~ /^userpicsthumb$/)
						PageHandler.current.rewrite(:GetRequest, "/gallerythumb/#{pic.userid/1000}/#{pic.userid}/#{pic.gallerypicid}.jpg");
						throw :page_done
					end
				end

				raise PageError.new(404), "You can't access this image through this path."
			end
		else
			raise PageError.new(404), "Not a valid image address."
		end
		throw :page_done
	end

	if (site_module_loaded? :Uploader)
		file_handler ["userpics|userpicsthumb"], proc{|filename, fclass|
			serve(filename, fclass)
		}
	end


=begin
	non-legacy Modqueue stuff.  Uncomment later, when we switch modqueues.
	class << self 
		def upload_handle(filename, userid, params, original_filename)
			q = Modqueue::Queue.new(Pics);
			
			pic = PicsPending.new
			pic.id = filename.to_i
			pic.userid = params;#['userid']
			pic.store;
			
			item = Modqueue::ModItem.from(pic)
		end
	end
=end

	def moderate_queue_name
		return "PicsPending"
	end

	# take a gallerypic and set it as a userpic.
	def self.set_as_userpic(userid, gallerypicid, file, description, signpic, set_priority = nil)

		image_md5 = MD5.new(file.read).to_s;
		
		ban = PicBans.find(:first, [image_md5, 0], [image_md5, userid])
		if (ban)
			if (ban.times > 1)
				raise UserError.new, "This picture has been banned because it has been denied twice already."
			end
		end

		pending = PicsPending.find(:first, :conditions => ["md5 = ? && userid = #", image_md5, userid])
		if (pending)
			raise UserError.new, "You already uploaded this picture"
		end

		#This should have been written during gallery upload.
		image = Magick::Image.read(file.disk_path).first
		if (image['exif:ImageDescription'])
			desc = image['exif:ImageDescription'];
			if (desc =~ /Nexopia\.com:(\d+)/)
				if ($1.to_i != userid)
					raise UserError.new, "This picture has been blocked because it was taken from another Profile or Gallery."
				end
			end
		else
			image['exif:ImageDescription'] = "Nexopia.com:#{userid}"
			image.write(file.disk_path);
		end

###########
#Create the item for the moderation system.
		mod_item = PicsPending.new
		mod_item.id = PicsPending.get_seq_id(userid)
		
		priority = User.find(:first, userid).plus? or (mod_item.id == 1)
		
		mod_item.time = Time.now.to_i
		mod_item.description = description;
		mod_item.md5 = image_md5
		mod_item.priority = priority
		mod_item.userid = userid
#		mod_item.store
###########

#Create the pic
		pic = Pics.new
		pic.userid = userid
		pic.id = mod_item.id
		if (!set_priority)
			sorted_pics = Pics.find(:all, userid).sort{|a,b| b.priority <=> a.priority}
			if (!sorted_pics.empty?)
				pic.priority = sorted_pics.first.priority + 1
			else
				pic.priority = 1
			end
		else
			pic.priority = set_priority
		end
		pic.signpic = false
		pic.gallerypicid = gallerypicid
		pic.store
###########


		#ImagesModule::resize_and_tag_image(file, "320x320", NexFile.new("userpics", userid, "#{pic.id}.jpg"));
		#ImagesModule::resize_image(file, "100x150", NexFile.new("userpicsthumb", userid, "#{pic.id}.jpg"));

		$log.info "Modqueuing", :critical
		#LegacyModqueue::ModItem.create("MOD_PICS", userid, gallerypicid, priority);
		mod_queue_item = Modqueue::ModItem.from(mod_item)
		return true;
	end

end
