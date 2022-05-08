lib_require :FileServing, "type"

module UserFiles
	class FileType < FileServing::Type
		register "uploads"
		secure_domain
		def self.new_external_url(*path)
			path.shift # take off the revision component
			super(*path)
		end
		# This pulls from the legacy mogile instance if it's set up 
		# by pulling from the mogile paths that the old site used in order.
		def not_found(out_file)
			if (legacy = $site.mogile_connection(:legacy))				
				if (data = legacy.get_file_data([self.class.typeid, *self.path].join('/')))
					out_file.write(data)
					return true
				end
			end
			return super(out_file) # let it throw a 404
		end
	end
end