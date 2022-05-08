lib_require :Worker, "post_process_queue"
lib_require :Core, 'uploads'
lib_require :Core, "filesystem/mogile_file_system"
lib_require :Uploader, 'mogile_upload_pair';

class Uploads < PageHandler
	declare_handlers("/") {
		area :Upload

		access_level :Any
		handle :GetRequest, :swfupload, "swfupload";
		handle :GetRequest, :http_upload, "httpupload", remain;

		access_level :LoggedIn
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
		mogile_pairs = upload();
		puts "Success."
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
		rescue FileSizeException, PageError
			#These are not logged, but might still be reported to the user.
			params.to_hash["Errors"] = $!;
			my_url = url/:webrequest/$site.config.www_url/remain
			rewrite(:Post, my_url, params, [:Internal, session.user])
		rescue Object
			params.to_hash["Errors"] = $!;
			$log.info "The following error received during upload: #{$!}", :error
			$log.info $!.backtrace, :error

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
			if (/^.*file_upload([\[\(]\d+[\]\)])?$/.match(param)) or (param == "Filedata")
				if (params.to_hash[param])
					file_params << param;
				end
			end
		end

		if (file_params.empty?)
			raise FileSizeException
		end
	
		
		#Grab the name of the module to handle the file.
		ftype = params["type", String, nil];
		if(ftype != nil)
			params.to_hash.delete("type");
		end
		
		#the array we will store the MogileFileUploadPair objects in
		mogile_file_pairs = Array.new();
		
		#This loop will store each file upload we detected earlier.
		for file_param in file_params
			
			#Pulling the file upload out of the params hash to keep
			#it from unnecessarily being passed on via the rest of
			#the request.
			file = params[file_param, IO];
			params.to_hash.delete(file_param);

			if(file == nil || file.length == 0)
				next;
			end
			
			#If the input names are formed with array style accessors []
			#the pagehandler will automatically form them into a hash based off
			#of the prefix. As such we could have a hash; if so, we will need
			#to store the values internal.
			#
			#Multidimensional hashes are not supported. Nonunique form input names
			#are also not supported. (Both are by the pagehandler)
			if(file.kind_of?(Hash))
				for temp_key in file.keys()
					#Coming out of the pagehandler, our hash is full of arrays.
					#To get the file we will need to grab the first element from
					#this array.
					#To support nonunique form inputs we'd need to change this.
					hash_file = file[temp_key];
					if(hash_file.kind_of?(Array))
						hash_file = hash_file.first;
					end
					
					if(hash_file == nil || hash_file.length == 0)
						next;
					end
					
					#Store the recieved file.
					hash_file.original_filename =~ /\.([a-zA-Z0-9]+)$/
					temp_file_name = "#{UniqueFilename.get_next()}.#{$1}";
					temp_mogile_file_name = "8/#{temp_file_name}";

					begin
						source = "#{$site.config.pending_dir}/#{temp_file_name}"
						$log.info "Storing #{source}"
						f = File.new(source, "w+")
						file.rewind
						f.write(hash_file.read)
						f.flush()
						f.close()
					rescue Exception
						$log.info $!, :error
						$log.info $!.backtrace.join("\n"), :error
					end
					
					#Obtain file information and store it in an association object
					mogile_pair = MogileFileUploadPair.new();
					
					mogile_pair.mogile_file_name = temp_file_name;
					mogile_pair.original_file_name = hash_file.original_filename;
					mogile_pair.mogile_file_path = temp_mogile_file_name;
					
					temp_input_name_base = file_param.slice(/^.*file/);
					mogile_pair.input_name_base = temp_input_name_base;
					
					mogile_pair.input_name_index = temp_key;
					
					mogile_file_pairs << mogile_pair;
				end
				next;
			end

			#Store the file.
			file.original_filename.to_s =~ /\.([a-zA-Z0-9]+)$/
			temp_file_name = "#{UniqueFilename.get_next()}.#{$1}";
			temp_mogile_file_name = "8/#{temp_file_name}";
			
			begin
				source = "#{$site.config.pending_dir}/#{temp_file_name}"
				$log.info "Storing #{source}"
				f = File.new(source, "w+")
				file.rewind
				f.write(file.read)
				f.flush()
				f.close()
			rescue Exception
				$log.info $!, :error
				$log.info $!.backtrace.join("\n"), :error
			end


			
			#Obtain file information and store it in an association object
			mogile_pair = MogileFileUploadPair.new();
			
			mogile_pair.mogile_file_name = temp_file_name;
			mogile_pair.original_file_name = file.original_filename;
			mogile_pair.mogile_file_path = temp_mogile_file_name;
			
			if(file_param == "Filedata")
				file_param = "flash_file_upload";
			end
			
			temp_input_name_base = file_param.slice(/^.*file/);
			mogile_pair.input_name_base = temp_input_name_base;
			
			#Strip out the input index if needed.
			if(/^.*file_upload([\[\(]\d+[\]\)])$/.match(file_param))
				temp_raw_input_index = file_param.slice(/([\[\(]\d+[\]\)])$/);
				temp_input_index = temp_raw_input_index.slice(/\d+/);
				
				mogile_pair.input_name_index = temp_input_index;
				
				temp_index_wrapper = temp_raw_input_index.slice(/[\]\)]$/);
				if(temp_index_wrapper == ")")
					mogile_pair.input_array_syntax_type = :parenthesis;
				end
			end
			
			mogile_file_pairs << mogile_pair;
		end

		#We need to check if file is nil or if the original_filename is nil
		#because if the file argument is a hash coming in, we don't want the
		#postprocess queue to blow up because file doesn't respond to original_filename.
		#
		#We don't need to perform the same check for temp_file_name because it will always
		#exist if we got file or a hash earlier.
		if(file != nil && file.original_filename != nil)
			original_filename = file.original_filename;
		else
			original_filename = "";
		end
		
		if(temp_file_name == nil)
			temp_file_name = "";
		end
		
		params_hash = params.to_hash();
		
		#We want to include the mogile file name and the original file name into
		#the params data being passed along to the PostProcess handler.
		#
		#The mogile_key will be the name of the upload field with 'upload' replaced by 'mogile'
		#The file_key will be the name of the upload field with 'upload' replaced by 'name'
		# ie.	input name:	my_resume_file_upload
		#		mogile_key:	my_resume_file_mogile
		#		file_key:	my_resume_file_name
		#Any array indices or hash values sent in will also be preserved at the end of the key.
		for mogile_pair in mogile_file_pairs
			params_hash[mogile_pair.mogile_key()] = mogile_pair.mogile_file_name;
			params_hash[mogile_pair.file_key()] = mogile_pair.original_file_name;
			params_hash[mogile_pair.mogile_path_key()] = mogile_pair.mogile_file_path;
		end
		
		#If there is a type specified by the form and a module supporting it;
		#add a task to the postprocessor.
		if (SiteModuleBase.get(ftype).nil?)
			raise "The form you used required module '#{ftype}', which is not loaded."
		end
		WorkerModule.do_task(SiteModuleBase.get(ftype), "upload_handle", [temp_file_name.to_s, userid, params_hash, original_filename]);

	end
end
