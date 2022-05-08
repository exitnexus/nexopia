lib_require :Core, 'filesystem/file_system', 'filesystem/file_type';

class LocalFileSystem < FileSystem
	attr_accessor(:base_path);

	def initialize(base='.', base_options={})
		@base_path = base;
		@options = base_options
	end

	#Takes the IO object data and writes it to disk.
	def store(data, key, file_class, options={})
		options = self.options.merge(options);
		file = File.new(path_string(file_class.path, key), 'w');
		file.write(data.read);
		file.flush();
	end

	#Returns an object of type File opened for reading.
	def get(key, file_class, options={})
		options = self.options.merge(options);
		return File.new(path_string(file_class.path, key), 'r');
	end

	#Delete a file from the local filesystem.
	def delete(key, file_class, options={})
		options = self.options.merge(options);
		path = path_string(file_class.path, key)
		File.delete(path);
	end

	private
	def path_string(path, key)
		return self.base_path + '/' + path.join('/') + key.to_s;
	end
end
