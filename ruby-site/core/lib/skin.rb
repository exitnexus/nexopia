
class Skin
	attr_accessor :skeleton, :skin_properties, :skin_name, :display_name
	
	def to_yaml_properties
		[@skin_properties, @skin_name, @display_name]
	end

	def initialize(skeleton, skin_name = nil)
		self.skeleton = site_module_get(skeleton)
		if (!self.skeleton || !self.skeleton.skeleton?)
			raise SiteError, "No such skeleton as #{skeleton}"
		end
		self.skin_properties = Hash.new();
		self.skin_name = skin_name;
	end

	def [](key)
		value = skin_properties[key];

		if(/^\/.+\.((jpg)|(gif)|(png)|(jpeg))$/.match(value))
			value = "url(#{$site.static_files_url}#{value})";
		end

		return value;
	end

	def []=(key, value)
		skin_properties[key] = value;
	end

	def exist?(key)
		if(skin_properties[key] != nil)
			return true;
		end
		return false;
	end
	
	def path()
		"#{@skeleton.skin_data_path}/#{skin_file_name}.yaml"
	end
end
