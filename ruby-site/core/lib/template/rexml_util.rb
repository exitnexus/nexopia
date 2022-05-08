module REXMLUtil
	def search_children(tag, &block)
		return if (kind_of? REXML::Comment)
		each{ |e|
			if (e.kind_of? REXML::Text)
				if (e.to_s.index(tag) != nil)
					yield self, e
					return;
				else
				end
			else
				e.search_children(tag, &block)
			end
		}
	end

	def deep_clone
		copy = clone();
		if (kind_of? REXML::Parent)
			each{|elt|
				copy.add(elt.deep_clone);
			}
		end
		return copy;
	end
	
end

module REXML
	class Child < Object
		include REXMLUtil;
	end
end