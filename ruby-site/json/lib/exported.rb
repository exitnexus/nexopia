require "json"
require "json/add/core"

#class Blah
#	attr :a
#	attr :b
#	attr :c
#	extend JSONExported
#	json_field :b
#end
#b = Blah.new
#b.send(:instance_variable_set, :@a, 1)
#b.send(:instance_variable_set, :@b, [])
#b.send(:instance_variable_set, :@c, 'c')
#JSON.generate(b)

module JSONExported
	def json_field(name)
		@json_fields ||= []
		@json_fields << name.to_s
	end
	def self.extended(cl)
		cl.send(:define_method, :to_json){|*a|
			result = {
				'json_class' => self.class.name
			}
			[*self.class.send(:instance_variable_get, :"@json_fields")].inject(result) do |r, name|
				r[name] = send "#{name}"
				r
			end
			result.to_json(*a)
		}
	end
end

Lazy::Promise.send(:undef_method, :to_json)

module Gallery
class Pic < Cacheable
	extend JSONExported
	json_field :id
	json_field :description
end
class GalleryFolder < Storable
	extend JSONExported
	json_field :id
	json_field :name
	json_field :description
end
end

class User < Cacheable
	extend JSONExported
	json_field :userid
	json_field :galleries
end
