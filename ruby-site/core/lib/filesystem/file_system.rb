class FileSystem
	attr_accessor(:options);
	
	def store(data, key, file_class, options)
		raise FileSystemError, "Attempt to call generic FileSystem write process."
	end
	
	def get(key, file_class, options)
		raise FileSystemError, "Attempt to call generic FileSystem read process."
	end
	
	def delete(key, file_class, options)
		raise FileSystemError, "Attempt to call generic FileSystem read process."
	end
	
	class << self
	end
end

class FileSystemError < SiteError
end