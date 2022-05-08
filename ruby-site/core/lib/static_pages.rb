lib_require :Core, 'storable/storable'

class StaticPage < Storable
	init_storable(:db, "staticpages");
	
	def StaticPage.by_name(name)
		page = StaticPage.find(:first, :conditions => ["name = ?", name]);
		
		return page;
	end
end