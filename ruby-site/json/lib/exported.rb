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
	def json_field(*args)
		@json_fields ||= []
		@json_fields.concat(args.map{|arg|arg.to_s})
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
			result.merge!(@extra_json_properties) if @extra_json_properties
			result.to_json(*a)
		}
		cl.send(:define_method, :add_json_property) { |name, value|
			@extra_json_properties ||= {}
			@extra_json_properties[name] = value
		}
	end
end

Lazy::Promise.send(:undef_method, :to_json)
