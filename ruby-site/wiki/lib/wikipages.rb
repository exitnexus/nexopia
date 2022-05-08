lib_require :Core, "storable/storable"

class WikiPage < Storable
	init_storable(:wikidb, "wikipages");

	def WikiPage.get_by_id(id)
		return find(:first, :conditions => ["id = ?", id]);
	end

	def WikiPage.get_by_name(parentid, name)
		return find(:first, :conditions => ["parent = ? && name = ?", parentid, name]);
	end

	def WikiPage.get_children(parentid)
		return find(:all, :conditions => ["parent = ?", parentid]);
	end

	def parent_wiki
		return WikiPage.find(:first, :conditions => ["id = ?", @parent]);
	end

	def uri_info
		if (!@parent or @parent == 0)
			return [@name, "/wiki/#{@name}"]
		else
			parent_url = WikiPage.get_by_id(@parent).uri_info[1]
			return [@name, "#{parent_url}/#{@name}"]
		end
	end

end



