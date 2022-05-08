lib_require :Core, "storable/storable"

module LegacyModqueue
	class ModItem < Storable
		init_storable(:moddb, "moditems")

		@@mod_types = {"MOD_PICS"=> 		1,
				"MOD_SIGNPICS"=>			2,
				"MOD_PICABUSE"=>			3,
				"MOD_QUESTIONABLEPICS"=>	4,
				
				"MOD_FORUMPOST"=>			11,
				"MOD_FORUMRANK"=>			12,
				"MOD_FORUMBAN"=>			13,
				
				"MOD_GALLERY"=>				21,
				"MOD_GALLERYABUSE"=>		22,
				
				"MOD_USERABUSE"=>			31,
				"MOD_USERABUSE_CONFIRM"=>	32,
				
				"MOD_BANNER"=>				41,
				
				"MOD_ARTICLE"=>				51,
				
				"MOD_POLL"=>				61}
		
		class << self
			def create(type, splitid, itemid, priority)
				return if ( self.find(:first, :item, @@mod_types[type], itemid, splitid) )
				mod_item = self.new;
				mod_item.type = @@mod_types[type];
				mod_item.splitid = splitid;
				mod_item.itemid = itemid;
				mod_item.priority = priority;
				mod_item.store;
			end
		end
	end
end