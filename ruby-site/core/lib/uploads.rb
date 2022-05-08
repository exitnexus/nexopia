lib_want :Modqueue, "queue"

=begin
	
This is a file management abstraction layer, backed by:
 1) ruby 'File' class on disk
 2) MogileFS

Every file has a FileClass (identified by a class name).  The FileClass defines 
storage symantics such as the path name on Mogile and the path name on disk.  
FileClasses can also ignore one of the backing mechanisms, so files in that 
class are only stored in 1 way. It wouldn't be too hard to add other backing 
mechanisms in the future.  

If the file is backed by both mechanisms, the code is designed to automatically
duplicate from disk to mogile. If the disk version exists but the mogile
doesn't, it will duplicate from disk to mogile, but note that it will not 
duplicate from mogile to disk. (This could be done if we ever need it)

You create file classes with this code:
	filetype 'the_fileclass_name', { 
		:disk => proc{|*components| "c:\docs\1.jpg" } #proc to determine disk path
		:mog_path => proc{|*components| "mogileroot\1.jpg" } #proc to determine mog path
	}

Then you load images like so:
	file = NexFile.new("the_fileclass_name", file_id); #will create if it doesn't exist
	file = NexFile.load("the_fileclass_name", file_id); #will return nil if doesn't exist

	data = file.read(); #return the contents of the file
	file.write(data); #write the data to the file.
=end
class NexFile
	class FileClass
		attr :disk_path, true
		attr :mog_path, true
	end
	@@file_types = {}

	attr :path_components, true
	attr :mog_class, true

	def self.filetype(class_name, params)
		filetype = FileClass.new()
		
		filetype.disk_path = params[:disk]
		filetype.mog_path = params[:mog_path]
		
		@@file_types[class_name] = filetype
	end
	
	filetype 'generated', 	{ 
		:disk => nil, 
		:mog_path => proc{|klass,path|
			"generated/#{klass}/#{path}"
		}
	}
	filetype 'banners', 		{ 
		:disk => proc{|filename|
			"#{$site.config.banners_dir}/#{filename}"
		},
		:mog_path => proc{|filename|
			"7/#{filename}"
		}
	}
	filetype 'uploads', 			{ 
		:disk => proc{|userid,filename|
			"#{$site.config.uploads_dir}/#{userid/1000}/#{userid}/#{filename}"
		}, 
		:mog_path => proc{|userid,raw_path| 
			"8/#{userid}/#{raw_path}"
		}
	}
	filetype 'temp', 			{ 
		:disk => proc{|filename|
			"#{$site.config.pending_dir}/#{filename}"
		},
		:mog_path => nil 
	}
	
	
	
	def self.get_mime(filename)
		@@mime_map.each{|mime|
			return mime[1] if (filename =~ /#{mime[0]}/)
		}
		return 'text/plain';
	end

	
	def self.load(mog_class, *path_components)
		
		f = self.new(mog_class, *path_components)
		if (f.mog_path and f.disk_path)
			#if the file exists on disk but not in mogile, dup it.
			tries = 0;
			begin
				if (!$site.mogilefs.get_paths(f.mog_path) and File.exists?(f.disk_path))
					$log.info "Mirroring '#{mog_class}/#{path_components.join(',')}' onto mogile.", :debug
					data = f.disk_get
					f.mog_store(data) 
				end
			rescue MogileFS::UnreadableSocketError
				tries += 1;
				retry if (tries < 3)
				raise
			end
		end
		return f
	end
	
	def initialize(mog_class, *path_components)
		@path_components = path_components
		@mog_class = mog_class
	end
	
	# Return the FileClass object associated with this file. 
	def file_class
		#Hash access means extra overhead, but storing the actual FileClass
		#object here doesn't work because FileClass objects contain procs and
		#can't be Marshaled.
		@@file_types[@mog_class]
	end
	
	# Return the path that this file uses for mogile storage 
	def mog_path
		if (self.file_class.mog_path)
			return self.file_class.mog_path.call(*@path_components)
		end
		return nil
	end
	
	# Return the path that this file uses for disk storage 
	def disk_path
		if (self.file_class.disk_path)
			return self.file_class.disk_path.call(*@path_components)
		end
		return nil
	end
	
	# Symantics of this are that it must exist on disk OR in mogile.
	def exists?
		return ($site.mogilefs.get_paths(mog_path) or File.exists?(disk_path))
	end
	
	# Private-ish, use get_file for general purpose
	def mog_get()
		$site.mogilefs.get(self.mog_path, self.mog_class)
	end
	
	# Private-ish, use get_file for general purpose
	def disk_get()
		File.new(disk_path, "r")
	end
	
	# Returns an object that responds to read().
	def get_file()
		 if (!mog_path)
		 	return disk_get
		 end
		 
 		if (!disk_path)
 			return mog_get
 		end

		
		source_file = mog_get
		if not (source_file)
			#No source found.  Mogile probably failed.  Load from disk.
			f = disk_get
			mog_store(f) 
			
			source_file = mog_get
			if (source_file.nil?)
				$log.info "File #{self.mog_path} failed to mirror properly to mogile, possibly due to high load.", :error
				return f;
			end
		end
		return source_file;
	end
	
	# Returns a string object representing the contents of the file.
	def read()
		return get_file.read
	end
	
	# Private-ish, use .write(data) for general purpose
	def mog_store(data)
		$site.mogilefs.store(data, self.mog_path, self.mog_class) if (mog_path)
	rescue MogileFS::CreationFailure
		$log.info "Failed to store #{self.mog_path} on mogile, possibly due to high load.", :error
	end
	
	# Private-ish, use .write(data) for general purpose
	def disk_store(data)
		return if (!disk_path)
		$log.info "file-system: Storing #{data.length} bytes at '#{disk_path}'.", :debug
		
		already_failed = false;
		begin
			f = File.new(disk_path, "w+")
		rescue Exception
			unless (already_failed)
				already_failed = true
				require 'fileutils'

				# Not using FileUtils.mkdir_p so that the permissions can be set correctly for each new
				# directory that needs to be created.
				dir_array = disk_path.split("/")[0...-1];
				dir = "";
				dir_array.each { |part| 
					dir = "#{dir}/#{part}"
					if(!File.exists?(dir))
						FileUtils.mkdir dir;
						FileUtils.chmod 0777, dir;
					end
				};
				retry
			else
				raise
			end
		end
		f.write(data)
		f.flush()
		f.close
		
		FileUtils.chmod 0777, disk_path;
	end
	
	#Write the data to all backing mechanisms
	def write(data)
		$log.info "Storing #{data.length} bytes at #{mog_class} '#{path_components}'", :info
		disk_store(data)
		mog_store(StringIO.new(data))
	end
	
	#Delete the file from all backing mechanisms
	def delete()
		File.delete(disk_path) if (disk_path and File.exists?(disk_path)) 
		$site.mogilefs.delete(mog_path) if (mog_path)
	end
	
	#do a mogile request - the symantics of this are that HTTP headers will
	#be returned.  Therefore a disk_get doesn't make sense.
	def request()
		file = $site.mogilefs.request(self.mog_path) if (mog_path)
		if (!file)
			return false
		end
		raw_path = self.path_components.to_s
		PageRequest.current.reply.headers['Content-Type'] = NexFile.get_mime(raw_path)
		NexFile.headers(file)
	end
	
	#Private-ish, returns the headers from a mogile http request.
	def self.headers(mog_reply)
		if (!mog_reply)
			puts "No file stored."
			throw :page_done;
		end 
			
		PageRequest.current.reply.headers["Cache-Control"] = mog_reply.header['cache-control'] if mog_reply.header['cache-control'];
		PageRequest.current.reply.headers["Last-Modified"] = mog_reply.header['last-modified'] || (Time.now - (86400*7)).httpdate()
		PageRequest.current.reply.headers["Expires"] = (Time.now + 7*86400).httpdate();
		#PageRequest.current.reply.headers["Expires"] = (Time.at(0)).httpdate();
		PageRequest.current.reply.headers["ETag"] = mog_reply.header['etag'];
		PageRequest.current.reply.headers["Age"] = mog_reply.header['age'] if mog_reply.header['age'];
	
		last_modified = Time.httpdate(mog_reply.header['last-modified']) if (mog_reply.header['last-modified'])
		if_modified_since = Time.httpdate(PageRequest.current.headers['HTTP_IF_MODIFIED_SINCE']) if PageRequest.current.headers['HTTP_IF_MODIFIED_SINCE']
	
		if (last_modified && if_modified_since && if_modified_since >= last_modified) 
			# Hasn't been modified, so just send the headers
			$log.info "file-server: last-modified short circuit", :info
			
			raise PageError.new(304), "Not modified"
		end
	
		#might want to output mime type.
		
		if (PageRequest.current.headers['REQUEST_METHOD'] == 'HEAD')
			$log.info "file-server: request method short circuit", :info
			throw :page_done;
		end
		$log.info "file-server: long load.  Serving #{mog_reply.body.length} bytes", :info
		puts mog_reply.body;	
		throw :page_done;
	end	

	@@mime_map = [
		['\.jpe?g$',	'image/jpeg'],
		['\.gif$',		'image/gif'],
		['\.swf$',		'application/x-shockwave-flash'],
		['\.pdf$', 'application/pdf'],
		['\.sig$', 'application/pgp-signature'],
		['\.spl$', 'application/futuresplash'],
		['\.class$', 'application/octet-stream'],
		['\.ps$', 'application/postscript'],
		['\.torrent$', 'application/x-bittorrent'],
		['\.dvi$', 'application/x-dvi'],
		['\.gz$', 'application/x-gzip'],
		['\.pac$', 'application/x-ns-proxy-autoconfig'],
		['\.swf$', 'application/x-shockwave-flash'],
		['\.t(ar\.)?gz$', 'application/x-tgz'],
		['\.tgz$', 'application/x-tgz'],
		['\.tar$', 'application/x-tar'],
		['\.zip$', 'application/zip'],
		['\.mp3$', 'audio/mpeg'],
		['\.m3u$', 'audio/x-mpegurl'],
		['\.wma$', 'audio/x-ms-wma'],
		['\.wax$', 'audio/x-ms-wax'],
		['\.wav$', 'audio/x-wav'],
		['\.png$', 'image/png'],
		['\.xbm$', 'image/x-xbitmap'],
		['\.xpm$', 'image/x-xpixmap'],
		['\.xwd$', 'image/x-xwindowdump'],
		['\.css$', 'text/css'],
		['\.html?$', 'text/html'],
		['\.js$', 'text/javascript'],
		['\.(dtd|xml)$', 'text/xml'],
		['\.mpeg$', 'video/mpeg'],
		['\.mpg$', 'video/mpeg'],
		['\.mov$', 'video/quicktime'],
		['\.qt$', 'video/quicktime'],
		['\.avi$', 'video/x-msvideo'],
		['\.as[fx]$', 'video/x-ms-asf'],
		['\.wmv$', 'video/x-ms-wmv']
	];
	
	
end
