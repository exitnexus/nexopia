
lib_require :Core, "storable/storable";
lib_require :Core, "hierarchy";

class Locs < Storable
	init_storable(:configdb, "locs");

	include Hierarchy;
	init_hierarchy("All Locations");

	def name_path
		@name_path = Locs.get_parent_properties(self.id, "name") * ", " if(!@name_path)
		return @name_path
	end


	def modifier
		return nil if self.type == 'N'
		
		if(!@modifier)
			name_parts = Locs.get_parent_properties(self.id, "name")
			type_parts = Locs.get_parent_properties(self.id, "type")

			if(self.type == 'S')
				mod_index = type_parts.index('N')
			else
				mod_index = type_parts.index('S') || type_parts.index('N')
			end
			
			@modifier = mod_index ? name_parts[mod_index] : nil
		end

		return @modifier
	end


	def augmented_name
		self.modifier ? self.name + ", " + self.modifier : self.name
	end


	def extra
		return 'Country' if self.type == 'N'
		
		if(!@extra)
			name_parts = Locs.get_parent_properties(self.id, "name")
			type_parts = Locs.get_parent_properties(self.id, "type")

			mod_index = type_parts.index('N')
			@extra = (mod_index.nil? || name_parts[mod_index] == self.modifier) ? nil : name_parts[mod_index]
		end
		
		return @extra
	end
end