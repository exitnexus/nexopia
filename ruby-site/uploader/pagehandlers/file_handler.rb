class FileHandler < PageHandler
	declare_handlers("/") {
		area :Upload

		area :Images
		access_level :Any
		handle :GetRequest, :view, remain;

		area :UserFiles
		access_level :Any
		handle :GetRequest, :view, remain;
		
		area :Public
		access_level :Any
		handle :GetRequest, :list, "ls";
		
	}

	def list
		ls = $site.mogilefs.ls()
		ls.html_dump();
			
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
	
	

	def self.get_mime(filename)
		@@mime_map.each{|mime|
			return mime[1] if (filename =~ /#{mime[0]}/)
		}
		return 'text/plain';
	end
	
	def translate_filepath(uri)
		mogfilename = nil;
		$log.info "file-server: Requesting '#{uri}'", :info
		if (uri =~ /([a-z]+)\/(.*)/)
			fclass = $1;
			filename = $2;
			@@file_handlers.each{|key, handler|
				if (fclass =~ /^(#{key.join("|")})$/)
					mogfilename = handler.call(filename, fclass)
				end
			}
			if (mogfilename == nil)
				raise PageError.new(404), "Class not found."
			end
		else
			raise PageError.new(404), "Improperly formed file request."
			return;
		end
		contenttype = FileHandler.get_mime(mogfilename);

		reply.headers['Content-Type'] = contenttype;
		$log.info "file-server: Default handling for '#{mogfilename}' from class '#{fclass}'", :debug
		puts $site.mogilefs.get(mogfilename, fclass).read;
	end
	
	def view(remain)
		uri = remain.join("/")
		translate_filepath(uri);
		# get class, filename, mogfilename
	rescue PageError
		reply.headers['Status'] = $!.code
		puts $!;
		throw :page_done
	rescue
		$log.info "File '#{uri}' failed to load: #{$!}", :error
		$log.info $!.backtrace.join("\n"), :error
		reply.headers['Status'] = "500"
		puts "Internal Error"
		throw :page_done
	end
	
end
