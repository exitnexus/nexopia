
lib_require :Core, "storable/storable";
lib_require :Core, "hierarchy";

class Locs < Storable
	init_storable(:configdb, "locs");
	
	include Hierarchy;
	init_hierarchy("All Locations");
	
	def name_path		
		return Locs.get_parent_properties(self.id, "name") * ", ";
	end
end
