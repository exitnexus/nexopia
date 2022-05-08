lib_require :Core, 'filesystem/file_system', 'filesystem/mogilefs/mogilefs'
lib_require :Core, 'storable/storable'
lib_require :Core, 'typeid'
lib_require :Core, 'filesystem/http_fast'

class UniqueFilename < Storable
	init_storable(:db, "uniquefilenames");

	def self.get_next
		u = UniqueFilename.new();
		u.store;
		unique = u.id
		u.delete
		return unique;
	end
end

class HTTP404 < Exception
end

class MogileFileSystem < FileSystem
	extend TypeID;
	attr_reader :mogilefs

	USER_PICS_THUMB = 1
	USER_PICS = 2
	GALLERY = 3
	GALLERY_FULL = 4
	GALLERY_THUMB = 5
	SOURCE = 6
	BANNERS = 7
	UPLOADS = 8

	@@class_code = {
		'userpicsthumb'		=> USER_PICS_THUMB,
		'userpics'			=> USER_PICS,
		'gallery'			=> GALLERY,
		'galleryfull'		=> GALLERY_FULL,
		'gallerythumb'		=> GALLERY_THUMB,
		'source'			=> SOURCE,
		'banners'			=> BANNERS,
		'uploads'			=> UPLOADS
	}

	def class_code
		return @@class_code
	end

	def initialize(tracker_hosts, cache, domain='nexopia.com', base_options={})
		@options = base_options
		@mogilefs = MogileFS::MogileFS.new(:hosts => tracker_hosts, :domain => domain)
		@cache = cache;
	end

	#Takes the IO object data and writes it to MogileFS.
	def store(data, key, file_class, options={})
		$log.info "mogilefs: storing at #{key}", :info
		@cache.delete("ruby_file-#{key}")
		path = mogilefs.store_file(key, file_class, data)
		paths = get_paths(key);
	end

	def get_paths(key)
		$log.info "mogilefs: looking up path for file '#{key}'", :info
		paths = @cache.load("ruby_file", key, 86400){|hash|
			hash.each_pair{ |fkey, null|
				mpaths = mogilefs.get_paths(*fkey)
				if (mpaths)
					$log.info "mogilefs: Path not cached.  Caching #{mpaths.join(',')} at #{fkey}", :info
					hash[fkey] = mpaths
				else
					hash.delete(fkey);
				end
			}
			hash
		}
		if (!paths or paths.empty?)
			return nil	
		end
		paths
	end

	def get_file(path)
		$log.info "mogilefs: getting file from '#{path}'", :info
		path = URI.parse path 
		#return timeout(60, MogileFS::Timeout) { path.read }
		#path.read
		req = Net::HTTPFast::Get.new(path.path)

		res = Net::HTTPFast.start(path.host, path.port) {|http|
			http.read_timeout = 10;
			http.request(req)
		}
		if (res.code.to_i == 404)
			raise HTTP404, "404 returned"
		end
		return res;
	end

	#Returns an object of type StringIO for reading.
	#XXX: Why does this take extra args?
	def get(key, file_class=nil, options={}, should_retry = true)
		#data = mogilefs.get_file_data(key)
		paths = get_paths(key)
		begin
			if (paths)
				paths.each do |path|
					begin
						return StringIO.new(get_file(path).body)
					rescue MogileFS::Timeout
						next
					end
				end
			else
				return nil
			end
		rescue OpenURI::HTTPError
			@cache.delete("ruby_file-#{key}")
			$log.info "File not found."
			#raise $!
		rescue HTTP404
			@cache.delete("ruby_file-#{key}")
			if (should_retry)
				return get(key, file_class, options, false)
			else
				delete(key)
				raise PageError.new(404), "File not found"
			end
		end
		raise PageError.new(503);

		return nil
	end

	#Returns an object of type StringIO for reading.
	def request(key, should_retry = true)
		#data = mogilefs.get_file_data(key)
		paths = get_paths(key)
		begin
			if (paths)
				paths.each do |path|
					begin
						return get_file(path)
					rescue MogileFS::Timeout
						next
					end
				end
			else
				return nil
			end
		rescue OpenURI::HTTPError
			@cache.delete("ruby_file-#{key}")
			$log.info "mogilefs: file '#{key}' not found.", :info
			#raise $!
		rescue HTTP404
			@cache.delete("ruby_file-#{key}")
			if (should_retry)
				return request(key, false)
			else
				delete(key)
				raise PageError.new(404), "File not found"
			end
		end
		raise PageError.new(503);

		return nil;
	end

	#Delete a file from MogileFS.
	def delete(key, file_class=nil, options={})
		$log.info "mogilefs: deleting key '#{key}'", :info
		@cache.delete("ruby_file-#{key}")
		mogilefs.delete(key)
	end

	#Move a file in MogileFS.
	def move(from, to)
		mogilefs.rename(from, to)
	end

	def ls
		out = [];
		lst = mogilefs.list_keys("", nil, 1000);
		out << lst[0];
		while (lst)
			lst = mogilefs.list_keys("", lst[1], 10000);
			if (lst)
				out << lst[0]
			end
		end
		return out;
	end
	
	def options
		return @options;
	end
end
