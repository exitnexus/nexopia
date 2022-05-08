require 'singleton'
require 'yaml'

lib_require :Core, 'skin'

class SkinMediator
	attr_accessor :skeleton_list;
	
	include Singleton
	
	def initialize()
		@skeleton_list = Hash.new();
	end
	
	def load_skins(skeleton)
		if (skeleton.kind_of? SiteModuleBase)
			skeleton = skeleton.name
		else
			skeleton = skeleton.to_s
		end
		
		if (self.skeleton_list[skeleton])
			return self.skeleton_list[skeleton]
		end
		
		skel_obj = site_module_get(skeleton)
		if (!skel_obj || !skel_obj.skeleton?)
			raise SiteError.new(500), "Unknown skeleton #{skeleton}"
		end
		skin_path = skel_obj.skin_data_path;
		
		self.skeleton_list[skeleton] = {}
		
		skin_file_list = Dir.entries(skin_path);
		
		Dir["#{skin_path}/*.yaml"].each {|file|
			temp = YAML.load(File.open(file));
			if(temp.kind_of?(Skin))
				temp.skeleton = skel_obj
				self.skeleton_list[skeleton][temp.skin_name] = temp;
			else
				$log.info("Skin #{skeleton}.#{file} did not evaluate to a skin object.", :warning, :skin)
			end
		}
		return self.skeleton_list[skeleton]
	end
	
	def SkinMediator.request_skin_list(skeleton)
		return SkinMediator.instance.get_skin_list(skeleton);
	end
	
	def get_skin_list(skeleton)
		return load_skins(skeleton).keys
	end
	
	def SkinMediator.request_skin_value(skeleton, skin, property)
		return SkinMediator.instance.get_skin_value(skeleton, skin, property);
	end
	
	def SkinMediator.request_value(skeleton, user_obj, property)
		return SkinMediator.instance.get_value(skeleton, user_obj, property);
	end
	
	def get_skin_value(skeleton, skin, property)
		skin_list = load_skins(skeleton)
		skin_obj = skin_list[skin.to_s];
		value  = skin_obj[property];
		
		if(value == nil)
			skin_obj = skin_list['default'];
			value = skin_obj[property];
		end
		
		return value;
	end
	
	def get_value(skeleton, user_obj, property)
		if(!user_obj.anonymous && user_obj.skin != nil)
			user_skin = user_obj.skin;
		else
			user_skin = 'default';
		end
		return get_skin_value(skeleton, user_obj.skin, property);
	end
	
	def SkinMediator.set_skin_value(skeleton, skin, property, value)
		SkinMediator.instance.set_value(skeleton, skin, property, value);
	end
	
	def set_value(skeleton, skin, property, value)
		skin_list = load_skins(skeleton)
		skin_obj = skin_list[skin.to_s];
		default_skin_obj = skin_list['default'];
		
		if(!default_skin_obj.exist?(property))
			default_skin_obj[property] = value;
			save_skin(default_skin_obj);
		end
		skin_obj[property] = value;
		save_skin(skin_obj);
	end
	
	def save_skin(skin_obj)
		File.open(skin_obj.path, 'w') { |f|
			f.puts(skin_obj.to_yaml())
		};
	end
	
	def SkinMediator.new_skin(skeleton, skin_name)
		SkinMediator.instance.create_skin(skeleton, skin_name);
	end
	
	def create_skin(skeleton, skin_name)
		skin_list = load_skins(skeleton)
		if(skin_list[skin_name] != nil)
			return nil;
		end
		
		skel_obj = site_module_get(skeleton)
		if (!skel_obj || !skel_obj.skeleton?)
			raise SiteError, "No such skeleton as #{skeleton}"
		end
		default_skin_path = "#{skel_obj.skin_data_path}/default.yaml";
		temp = YAML.load(File.open(skin_path));
		
		temp.skin_name = skin_name;
		
		save_skin(temp);
	end
	
	def SkinMediator.delete_skin(skeleton, skin_name)
		SkinMediator.instance.remove_skin(skeleton, skin_name);
	end
	
	def remove_skin(skeleton, skin_name)
		skin_list = load_skins(skeleton)
		if(skin_list[skin_name] == nil)
			return nil;
		end
		
		skin_list.delete(skin_name);

		skel_obj = site_module_get(skeleton)
		if (!skel_obj || !skel_obj.skeleton?)
			raise SiteError, "No such skeleton as #{skeleton}"
		end

		skin_file_path = "#{skel_obj.skin_data_path}/#{skin_name}.yaml";
		
		File.delete(skin_file_path);
	end
	
	def SkinMediator.request_all_values(skeleton, skin)
		if(skin.nil?() || skin == "")
			skin = "default";
		end
		return SkinMediator.instance.get_all_values(skeleton, skin);
	end
	
	def get_all_values(skeleton, skin)
		skin_list = load_skins(skeleton)
		skin_obj = skin_list[skin.to_s];
		default_skin_obj = skin_list['default'];
		if (skin_obj == nil)
			raise "Invalid skin '#{skin}'"
		end
		
		skin_keys = skin_obj.skin_properties.keys();
		default_keys = default_skin_obj.skin_properties.keys();
		
		complete_key_list = Array.new();
		
		for key in skin_keys
			complete_key_list << key;
			default_keys.delete(key);
		end
		
		for key in default_keys
			complete_key_list << key;
		end
		
		value_hash = Hash.new();
		for key in complete_key_list
			value_hash[key] = get_skin_value(skeleton, skin, key);
		end
		
		return value_hash;
	end
	
	def SkinMediator.request_display_name(skeleton, skin)
		return SkinMediator.instance.get_display_name(skeleton, skin);
	end
	
	def get_display_name(skeleton, skin)
		return load_skins(skeleton)[skin].display_name;
	end
end
