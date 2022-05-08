lib_require :Core, 'storable/storable'

# This is deprecated and should not be used. Things that are currently in 
# the static pages should be moved to the wiki.

class StaticPage < Storable
	init_storable(:db, "staticpages");
	
	def StaticPage.by_name(name)
		page = StaticPage.find(:first, :conditions => ["name = ?", name]);
		
		return page;
	end
end