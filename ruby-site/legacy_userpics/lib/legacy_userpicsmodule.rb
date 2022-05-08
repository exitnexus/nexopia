lib_want :Uploader, "file_handler"
lib_require :Worker, "kernel_addon"
lib_require :Images, "images"
lib_require :Core, "user_error"
lib_require :Images, "image_creator"

class LegacyUserpicsModule < SiteModuleBase
	
	######################ImageCreator
	extend ImageCreator
	worker_task :create_image
	
	def self.get_source(pic)
		source_file = NexFile.load("userpics", pic.userid, "#{pic.id}.jpg")
		return [pic, source_file]
	end
	
	def self.queue_create_image(handle, pic, output_file)
		WorkerModule::do_task(SiteModuleBase.get(:LegacyUserpics), "create_image", [handle, pic, output_file]);
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
	
	if (site_module_loaded? :Uploader)
		#Serve a file by "userid, id" key
		def self.serve(filename, fclass)
			if (filename =~ /^(\d+)\/(\d+)\/(\d+)\.(...+)/)
				$log.info "userpic-file-server: Requesting #{fclass} #{$2} #{$3}", :info
				file = NexFile.load(fclass, $2.to_i, "#{$3}.jpg")
				if (!file.request)
					lib_require :LegacyUserpics, "legacy_pics"
					pic = LegacyUserpics::Pics.find(:first, $2.to_i, $3.to_i) || PicsPending.find(:first, $2.to_i, $3.to_i)
					raise(PageError.new(404), "Not a valid userpic") if (!pic)
					
					if (fclass =~ /^userpics$/)
						queue_create_image(:normal, pic, file)
					elsif (fclass =~ /^userpicsthumb$/)
						queue_create_image(:thumb, pic, file)
					end
					
					file.request || raise(PageError.new(404), "Not a valid image")
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
	
	def self.add_pic(userid, filename, description, signpic)
		
		original = NexFile.load("temp", filename)
		image_md5 = MD5.new(original.read).to_s;
		
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
		image = Magick::Image.read(original.disk_path).first
		if (image['exif:ImageDescription'])
			desc = image['exif:ImageDescription'];
			if (desc =~ /Nexopia\.com:(\d+)/)
				if ($1.to_i != userid)
					raise UserError.new, "This picture has been blocked because it was taken from another Profile or Gallery."
				end
			end
		else
			image['exif:ImageDescription'] = "Nexopia.com:#{userid}"
			image.write(original.disk_path);
		end
		
		
		pic = PicsPending.new
		pic.id = PicsPending.get_seq_id(userid)
		
		priority = User.find(:first, userid).plus? or (pic.id == 1)
		
		pic.time = Time.now.to_i
		pic.description = description;
		pic.md5 = image_md5
		pic.priority = priority
		pic.userid = userid
		
		ImagesModule::resize_and_tag_image(original, "320x320", NexFile.new("userpics", userid, "#{pic.id}.jpg"));
		ImagesModule::resize_image(original, "100x150", NexFile.new("userpicsthumb", userid, "#{pic.id}.jpg"));
=begin		
			Worker::PostProcessQueue.queue(ImagesModule, "resize_image", 
				[
					original, 
					"100x150", 
					NexFile.new(userid, "#{pic.id}.jpg", "userpicsthumb")
				]
			);
=end
		#We're done with the pending file, so clean it up.
		original.delete()
		
		pic.store;
		
		LegacyModqueue::ModItem.create("MOD_PICS", userid, pic.id, priority);
		
		return true;
	end
	
	def self.upload_handle(filename, userid, params, original_filename)
		lib_require :LegacyUserpics, "legacy_pics"
		
		userfirstpic = params['firstpic'].to_s;
		usersignpic = params['signpic'].to_s;
		action = params['action'].to_s.downcase;
		
		# Allow the form to submit a "cancel" action which will cause this handler 
		# to not finish doing its thing. Having to split a form into two forms to 
		# solve this on the page side is just too much of a mess when
		# you have to worry about the layout of the form buttons as well.
		return if (action == "skip" || action == "cancel")
		
		user = User.find(:first, userid);
		
		max_pics = (user.plus? ? 12 : 8);
		
		max_pics += 1 if(usersignpic == 'y')
		
		reachedMax = false;
		picid = 0;
		
		pic_count = LegacyUserpics::Pics.find(userid).length + PicsPending.find(userid).length;
		
		$log.info "User #{userid} has #{pic_count} pics of #{max_pics}."
		if(pic_count < max_pics) #premium check
			$log.info "Adding pic..."
			description = original_filename.gsub(/(\.[jJ][pP][gG])$/, '');
			
			return add_pic(userid, filename, description, usersignpic);
		else
			raise UserError.new, "User pic not added due to pic limit (#{pic_count})."
		end
		return true;
	end
	worker_task :upload_handle
	
	
end
