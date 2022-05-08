lib_require :Core, "uploads"

module ImageCreator
	############################### Image functions	
	
	def to_rmagick(input)
		pic, source_file = input
		
		if not (File.exists?(source_file.disk_path))
			#No source found.  Mogile probably failed.  Load from disk.
			f = source_file.mog_get
			
			if (f)
				source_file.disk_store(f.read)
			else
				raise "Source file not available"
			end
		end
		
		image = Magick::Image.read(source_file.disk_path).first
		[pic, image]
	end
	
	def resize(input, size)
		(pic, img) = input;
		ImagesModule::resize(img, size);
		[pic, img]
	end

	def crop_resized(input, width, height)
		(pic, img) = input;
		ImagesModule::crop_resized(img, width, height);
		[pic, img]
	end

	def crop_center(input, *args)
		(pic, img) = input;
		args << true
		ImagesModule::crop(img, Magick::CenterGravity, *args);
		[pic, img]
	end

	def tag(input)
		(pic, img) = input;
		ImagesModule::tag(img, "#{$site.config.site_base_dir}/images/static/images/nexopia_tag.png");
		[pic, img]
	end
	
	#Write a file that is stored using the old php mogile class system.
	def write_old_style(input, fclass)
		(pic, img) = input;
		file = NexFile.new(fclass, pic.userid, "#{pic.id}.jpg")
		img.write(file.disk_path);
		file.get_file; #since rmagick writes to disk directly, we have to manually
			#backup to mogile.
	end
	
	#Write a file that is stored using one of the newer classes, not the php ones.
	def write(input, fclass)
		(pic, img) = input;
		
		tempfile = NexFile.new("temp", "#{fclass}-#{pic.userid}-#{pic.id}.jpg")
		img.write(tempfile.disk_path);
		data = tempfile.get_file.read
		
		file = NexFile.new("generated", fclass, "#{pic.userid}/#{pic.id}.jpg")
		file.write(data)
		tempfile.delete;
	end

	#Write a file that is stored using one of the newer classes, not the php ones.
	def write_2(input, fclass)
		(pic, img) = input;
		
		tempfile = NexFile.new("temp", "#{fclass}-#{pic.userid}-#{pic.id}.jpg")
		img.write(tempfile.disk_path);
		data = tempfile.get_file.read
		
		file = NexFile.new("generated", fclass, "#{pic.userid}/#{pic.priority}.jpg")
		file.write(data)
		tempfile.delete;
	end

###############################

	def image_type(handle, *actions)
		@image_creation_instructions ||= {}
		@image_creation_instructions[handle] = actions
	end
	def create_image(handle, input, output_file)
		$log.info "Doing image creator for #{handle}", :critical
		@image_creation_instructions[handle].each{|method, *args|
			$log.info "image-maker: Doing #{method} with args #{args.join(', ')}."
			[*input].flatten.each{|inp|
				if (inp.kind_of? Magick::Image)
					$log.info inp.columns, :warning
					$log.info inp.rows, :warning
				end
			}
			input = self.method(method).call(input, *args)
			[*input].flatten.each{|inp|
				if (inp.kind_of? Magick::Image)
					$log.info inp.columns, :warning
					$log.info inp.rows, :warning
				end
			}
			$log.info "image-maker: done."
		}
	end

end
