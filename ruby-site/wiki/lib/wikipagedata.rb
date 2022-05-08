lib_require :Core, "storable/storable"

class WikiPageData < Storable
	init_storable(:wikidb, "wikipagedata");

	def WikiPageData.get_page_rev(pageid, rev)
		return find(:first, :conditions => ["pageid = ? && revision = ?", pageid, rev]);
	end

	def WikiPageData.get_page_hist(pageid)
		return find(:all, :conditions => ["pageid = ?", pageid]);
	end


	def get_children()
		children = WikiPage.get_children(id);

		names = [];
		children.each { |child|
			names << child.name;
		}

		return children;
	end
	
	def uri_info
		if (!@parent or @parent == 0)
			return [@name, "/wiki/#{@name}?rev=#{revision}"]
		else
			parent_wiki = WikiPage.get_by_id(@parent);
			parent_url = "invalid wiki"
			if (parent_wiki)
				parent_url = parent_wiki.uri_info[1]
			end
			return [@name, "#{parent_url}/#{@name}?rev=#{revision}"]
		end
	end
end