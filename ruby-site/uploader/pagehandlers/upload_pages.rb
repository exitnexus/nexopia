lib_require :Gallery, "gallery_pic"

class Uploads < PageHandler
	declare_handlers("/") {
		area :Upload

		access_level :Any
		handle :GetRequest, :swfupload, "swfupload";
		handle :GetRequest, :http_upload, "httpupload", remain;

		handle :GetRequest, :cross_domain, "crossdomain.xml";
		
		area :Public
		handle :GetRequest, :cross_domain, "crossdomain.xml";
	}

	def cross_domain
		reply.headers['Content-Type'] = "text/xml";
		
		puts <<EOF
<?xml version="1.0"?>
<!DOCTYPE cross-domain-policy SYSTEM "http://www.macromedia.com/xml/dtds/cross-domain-policy.dtd"><cross-domain-policy><allow-access-from domain="*.#{$site.config.base_domain}" /></cross-domain-policy>
EOF
	end
	
	#Verify that user exists. 
	def verify_upload(params)
		userid = nil;
		if (params['session', String])
			#when the request is from the flash uploader we
			#pass some of the session information as an encrypted parameter.
			session = Session.decrypt(params['session', String])
			if (session)
				userid = session.userid
			else
				$log.info "Unable to load session from params.", :warning
			end
		elsif (!request.session.user.anonymous?)
			userid = request.session.user.userid
		end
		return userid;
	end
	
	def swfupload()
		begin
			result = upload();
			puts "Success"
			puts result
		rescue => error
		  if(!error.kind_of?(Gallery::GalleryError))
    		$log.error
    	end
			messages = InfoMessages.new
			messages.add_message error
			puts messages.html
		end
	end
	
	#Handles all file upload via http. A module will be passed as an argument,
	#and that module's upload_handler will be called. The following params are removed:
	#	-file_upload: the actual file data
	#	-type: the module to handle the upload
	#
	#Note regarding the site_redirect:
	#There have been 3 params added to the hash for each file input (this takes place in the upload() function).
	#They are: (prefix being whatever came before 'file_upload' in the name of the file input in the form):
	#	-{prefix}file_name -> The original filename of the uploaded file.
	#	-{prefix}file_mogile -> The filename in the mogile file system.
	#	-{prefix}file_mogile_path -> The fully qualified path to the file in mogile. (This will
	#									include the file class).
	def http_upload(remain)		
		begin
			upload();
		rescue FileSizeException, PageError, UserError, Gallery::GalleryError
			#These are not logged, but might still be reported to the user.
			params.to_hash["Errors"] = $!;
			my_url = url/:webrequest/$site.config.www_url/remain
			rewrite(:Post, my_url, params, [:Internal, session.user])
		rescue Object
			params.to_hash["Errors"] = $!;
			$log.error

			my_url = url/:webrequest/$site.config.www_url/remain
			
			rewrite(:Post, my_url, params, [:Internal, session.user])
		end
		
		url_str = "/#{remain.join('/')}"
		
		#Build the GET string. The if/else detects if it's the first or nth addition to
		#the string.
		params_str = nil
		key_list = params.keys();
		
		key_list.each{|key| 
			temp_val = params[key, Array, params[key, String]]
			temp_val.each {|val|
				if(/\?$/.match(params_str))
					params_str = "#{params_str}#{key}=#{CGI::escape(val.to_s)}";
				else
					params_str = "#{params_str}&#{key}=#{CGI::escape(val.to_s)}";
				end
			}
		};
		
		if (params_str)
			redirect_str = "#{url_str}?#{params_str}";
		else
			redirect_str = url_str;
		end

		external_redirect("#{$site.www_url}#{redirect_str}");
	end
	
	class FileSizeException < Exception
	end

	#Handle the actual logic of storing the filedata from the request.
	def upload()
		userid = verify_upload(params);
		
		#Find all the file upload inputs. They will all end in
		#'file_upload' or 'file_upload[int]' or 'file_upload(int)'. Any other
		#suffix will not be accepted. Prior to the 'file_upload' anything
		#is accepted.
		#
		#Exception to the above rule. Filedata will also be accepted, but only
		#one entry. Filedata is to be the exclusive to the Flash uploader. The Flash
		#uploader will probably be updated in the future to use 'file_upload'.
		file_params = Array.new();
		for param in params.keys()
			if (param =~ /^Filedata$/ || param =~ /file_upload$/)
				file_params << param;
			end
		end

		if (file_params.empty?)
			raise FileSizeException
		end
	
		#Grab the name of the module to handle the file.
		ftype = params["type", String, nil];
		if(ftype != nil)
			params.real_hash.delete("type");
		end
		
		params_hash = params.to_hash();
		single_upload_file = nil
		single_upload_original_name = nil
		
		tmpfiles = []
		begin		
			#This loop will store each file upload we detected earlier.
			for file_param in file_params
			
				#Pulling the file upload out of the params hash to keep
				#it from unnecessarily being passed on via the rest of
				#the request.
				file = params[file_param, IO];
				params.real_hash.delete(file_param);

				if(file == nil || file.length == 0)
					next;
				end
			
				#Store the file.
				file.original_filename.to_s =~ /\.([a-zA-Z0-9]+)$/
				# Create a tempfile if it's not already one
				if (file.kind_of?(StringIO))
					tmpfile = Tempfile.new("upload")
					tmpfile.write(file.read())
					class <<tmpfile
						attr :original_filename, true
					end
					tmpfile.original_filename = file.original_filename
					file, tmpfile = tmpfile, file
				end
				tmpfiles << file
				temp_file_name = file.path;

				if(file_param == "Filedata")
					file_param = "flash_file_upload";
				end
		
				params_hash["#{file_param}_name"] = file.original_filename
				params_hash["#{file_param}_tmpfile"] = temp_file_name
			
				single_upload_file = file
				single_upload_original_name = file.original_filename
			end
			
			#If there is a type specified by the form and a module supporting it;
			#add a task to the postprocessor.
			mod = SiteModuleBase.get(ftype)
			if (!mod)
				raise "The form you used required module '#{ftype}', which is not loaded."
			end
			if (!mod.class.respond_to?(:upload_handle))
				raise "The form you used required module '#{ftype}', which does not handle uploads."
			end
			if(single_upload_file.nil?)
			  raise Gallery::GalleryError, "Error uploading requested file."
		  else
  			result = mod.class.upload_handle(single_upload_file, userid, params_hash, single_upload_original_name)
			end
			return result
		ensure
			tmpfiles.each {|tmpfile|
				if (tmpfile.respond_to?(:close!))
					tmpfile.close!()
				else
					File.unlink(tmpfile.path)
					tmpfile.close()
				end
			}
		end
	end
end
