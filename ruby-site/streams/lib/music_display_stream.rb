
module Music
	class MusicDisplayStream < Cacheable
		init_storable(:streamsdb, "musicdisplaystreams");
		
		def ==(obj)
			return false unless obj.kind_of?(MusicDisplayStream);
			
			if(obj.tagwords == self.tagwords)
				return true;
			end
			return false;
		end
		
		def ===(obj)
			return false unless obj.kind_of?(MusicDisplayStream);
			
			if(obj.tagwords == self.tagwords && obj.title == self.title && obj.priority == self.priority)
				return true;
			end
			return false;
		end
	end
end