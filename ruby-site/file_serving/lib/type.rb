lib_require :Core, "typeid", "http_fast"
lib_require :FileServing, "cached_mogilefs"
require 'tempfile'

module FileServing
	class RegistrationError < SiteError; end
	class BackendError < SiteError; end
	class GenerationError < SiteError; end

	# Derive from this to specify a file type to be served.
	# Typical derivation for an 'original' file would be something like:
	# class MyFileType < FileServing::Type
	#  short_name = "mine"
	#  def initialize(userid, id)
	#   super(url/userid/id)
	#  end
	# end
	# Or for a derived file type:
	# class MyThumbFileType < MyFileType
	#  short_name = "my_thumb"
	#
	#  def generate(original_file, output_file)
	#   # 
	# end
	class Type
		class <<self
			def register(short_name)
				@short_name = short_name
				if (Type.type_registry[short_name])
					raise RegistrationError, "File type #{short_name} already registered."
				end
				Type.type_registry[self.name] = self
				Type.type_registry[short_name] = self
				
				# we need typeids on these classes, so give it one.
				extend TypeID
			end
			# Returns the type registry hash with a map of names to objects.
			def type_registry
				@type_registry ||= {}
			end
			# Returns a particular file type class based on the name.
			def [](name)
				return Type.type_registry[name]
			end
			# Returns an array of type classes that derive from this one.
			# If a block is provided, yields each in turn to that block.
			def child_types(recurse = true)
				children = Type.type_registry.collect {|name, type|
					if (type.superclass == self)
						if (recurse)
							[type, type.child_types(recurse)]
						else
							type
						end
					else
						nil
					end
				}.flatten.compact.uniq
				if (block_given?)
					children.each {|child| yield child }
				end
				children
			end
				
			
			# If true for the class, files of this type should not be accessible from the www_domain.
			def secure_domain()
				@secure_domain = true
			end
			def secure_domain?()
				@secure_domain
			end
			
			# if true for the class, files of this type never change.
			def immutable()
				@immutable = true
			end
			def immutable?()
				@immutable
			end

			# not currently used, but will at some point allow for changing how files are actually stored.
			def storage_backend()
				return :mogilefs
			end
			
			# The mogile storage class of this type of file (:generated and :source are the likely ones)
			# default is :source for classes derived from FileServing::Type, and :generated for any derived
			# from subclasses of FileServing::Type.
			def mog_class(set = nil)
				if (set)
					@mog_class = set
					return set
				else
					if (!@mog_class)
						@mog_class = if (superclass == FileServing::Type)
							:source
						else
							:generated
						end
					end
					return @mog_class
				end
			end
		end		
		
		attr :path
		
		# Creates an object that represents an instance of a file in this class.
		# This path should not include a type prefix of any sort: each Type
		# derived class identifies a seperate namespace for its files.
		# Derived classes can override initialize to provide a more restrictive (id-based)
		# set of arguments, and callers of a derived class' new() should be prepared
		# to handle this class not accepting an arbitrary number of arguments.
		def initialize(*path)
			if (!Type.type_registry[self.class.name])
				raise RegistrationError, "Tried to instantiate filetype #{self.class.name}, which isn't registered."
			end
			@mogile_path = [self.class.typeid,*path].join('/')
			@path = path
		end
		
		# Returns the mogilefs backend object we're using for this file
		def backend()
			$site.mogile_connection(self.class.mog_class)
		end
		
		# Derived classes should overload this class method with a function that maps a public url
		# to the internal url. If they map directly, there is no need to overload this
		# function. Return super(translated_path) from your derived version.
		def self.new_external_url(*path)
			return new(*path)
		end
		
		# If this is a generated type of file, the derived class should implement this
		# function to translate from the base class file into the derived class' file.
		# Arguments are Tempfile objects. The input file will have the original contents, and the
		# output file will be empty. Function should raise if generation failed.
		def generate(input_file, output_file)
			raise GenerationError, "This class #{self.class.name} has not implemented generation."
		end
		
		# Puts the contents of the file into out_buffer (which is any IO derived object) and then
		# returns out_buffer, or returns false and does not modify io_out if the file does not exist.
		def get_contents(out_buffer)
			begin
				# use http_get_contents with no headers. This is like an http request with no cache-control.
				http_get_contents({}, out_buffer, {})
				return out_buffer
			rescue PageError => err
				if (err.code.to_i == 404)
					return false
				else
					$log.info("http_get_contents returned a status code of #{err.code} when it should only be able to return 404 or 200 with no cache-headers.", :error)
					raise
				end
			end
		end
		
		# Does the internal work needed to call generate() and return a meaningful result to the client
		# for the generated image.
		def http_generate(parent_file, out_buffer, out_headers)
			Tempfile.open("generated_image_input") {|in_file|
				parent_file.http_get_contents({}, in_file, {}) # if this raises an error, we just let that go through (probably a 404)
				in_file.rewind
				$log.info("Generating image #{@mogile_path} from source file that is #{in_file.size} bytes.", :debug, :fileserving)
				
				Tempfile.open("generated_image_output") {|out_file|
					generate(in_file, out_file)
					out_file.rewind
					put_contents(out_file)
					out_file.rewind
					out_buffer.write(out_file.read) # TODO: make this more efficient.
					$log.info("Generated image, from #{in_file.path} to #{out_file.path}", :spam, :fileserving)
					return out_buffer
				}
			}
		end
		
		# Overload this to provide behaviour for when the file can't be found in mogile (for legacy purposes
		# or because the file is generated from data -- eg. a graph).
		# Default behaviour is just to raise a 404 not found error.
		def not_found(out_file)
			raise PageError.new(404), "File does not exist."
		end
		
		# Does the internal work needed to call not_found and return a meaningful result to the client for
		# the generated image (if any)
		def http_not_found(out_buffer, out_headers)
			Tempfile.open("generated_image_output") {|out_file|
				not_found(out_file)
				$log.info("Not found handler for #{@mogile_path} generated a file of size #{out_file.size}", :debug, :fileserving)
				out_file.rewind
				put_contents(out_file)
				out_file.rewind
				out_buffer.write(out_file.read)
				$log.info("Generated source file to #{out_file.path}", :spam, :fileserving)
				return out_buffer
			}
		end
		
		def stored?()
			return self.backend.get_paths(@mogile_path.to_s)
		end

		# Gets the contents of the file using in_headers (a hash of cgi-like variables)
		# to avoid regetting a file that's already cached. Out_headers will be filled in with 
		# headers relevant to controlling cache of the output. For any situation where the file
		# is not fetched (client has a correctly cached copy, file is not there), the appropriate
		# PageError will be thrown. On success, the contents of the file will be written to
		# out_buffer, and out_buffer will be returned.
		def http_get_contents(in_headers, out_buffer, out_headers)
			# first ask mogile for the keys
			urls = self.backend.get_paths(@mogile_path.to_s)
			if (!urls)
				if (self.class.superclass == Type)
					return http_not_found(out_buffer, out_headers)
				else
					parent_file = self.class.superclass.new(*@path)
					return http_generate(parent_file, out_buffer, out_headers)
				end
			end
			
			decased_in_headers = {}
			in_headers.each {|key, val|
				decased_in_headers[key.downcase] = val
				$log.info("Input header: #{key.downcase} => #{val}", :spam, :fileserving) if (key.downcase =~ /^http_/)
			}
			in_headers = decased_in_headers
			
			# try each url in succession to get the file.
			urls.each {|url|
				$log.info("Pulling #{@mogile_path} from mogile url #{url}", :debug, :fileserving)
				url_obj = URI.parse(url)
				req = Net::HTTPFast::Get.new(url_obj.path)
				if (in_headers['http_if_modified_since'])
					$log.info("Passing on If-Modified-Since header to value #{in_headers['http_if_modified_since']}", :spam, :fileserving)
					req['if-modified-since'] = in_headers['http_if_modified_since']
				end
				if (in_headers['http_if_none_match'])
					$log.info("Passing on If-None-Match header to value #{in_headers['http_if_none_match']}", :spam, :fileserving)
					req['if-none-match'] = in_headers['http_if_none_match']
				end
				if (in_headers['http_if_match'])
					$log.info("Passing on If-Match header to value #{in_headers['http_if_match']}", :spam, :fileserving)
					req['if-match'] = in_headers['http_if_match']
				end
				begin
					res = Net::HTTPFast.start(url_obj.host, url_obj.port) {|http|
						http.read_timeout = 0.5;
						http.request(req)
					}
					case res.code.to_i
					when 200
						out_headers["Etag"] = res["Etag"]
						out_headers["Last-Modified"] = res['Last-Modified']
						
						$log.info("File is #{res.body.length} bytes long.", :spam, :fileserving)
						out_buffer.write(res.body)
						return out_buffer
					when 304
						raise PageError.new(304), "Not Modified"
					else
						$log.info("Fetch of mogile file #{@mogile_path} to mogile url #{url} returned unexpected http error code #{res.code} -- trying next.", :warning, :fileserving)
					end
				rescue Net::HTTPError, SystemCallError
					$log.info("Fetch of mogile file #{@mogile_path} to mogile url #{url} failed: #{$!} -- Trying next.", :warning, :fileserving)
				end
			}
			# if we get here, none of the servers gave a good result
			$log.info("Fetch of mogile file #{@mogile_path} failed to turn up a result at any of the servers mogile pointed to.", :error, :fileserving)
			self.backend.invalidate(@mogile_path.to_s)
			raise BackendError, "No backend servers were able to serve the request."
		end
		
		# Removes all images generated from this image type.
		def remove_generated()
			self.class.child_types {|child|
				child_obj = child.new(*@path)
				child_obj.remove(false)
			}
		end			
		
		# Puts the data given into the file backend. Contents is an io object.
		def put_contents(contents, invalidate_children = true)
			$log.info("Storing data to #{@mogile_path} with class #{self.class.mog_class} (#{self.class.name})", :spam, :fileserving)
			self.backend.store_file(@mogile_path.to_s, self.class.mog_class, contents)
			if (invalidate_children)
				remove_generated
			end
		end
		
		# Removes the file from the backing store.
		def remove(invalidate_children = true)
			self.backend.delete(@mogile_path.to_s)
			if (invalidate_children)
				remove_generated
			end
		end
	end
end