module Meta
	def meta
		if (!@meta)
			@meta = MetaObject.new;
		end
		return @meta
	end

	class MetaObject
		def initialize
			@__meta_array__ = Hash.new
		end
		
		# redefine most (all?) of the Object functions to push them to the enclosed object
		[:hash, :id].each { |sym|
			define_method(sym) {|*args|
				method_missing(sym, *args);
			}
		}
		
		def method_missing(method_name, *args)
			chomped_name = method_name.to_s.chomp("=").to_sym;
			if (chomped_name != method_name)
				@__meta_array__[chomped_name] = *args
				method_name = chomped_name
			end
			return @__meta_array__[method_name];
		end
	end
end

class Array
	include Meta
end

class Storable
	include Meta
end

class Hash
	include Meta
end

class StorableResult < Array
	include Meta
end