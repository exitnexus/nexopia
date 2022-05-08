lib_require :Core, 'storable/cacheable'
lib_require :Streams, 'entry_tag', 'stream_icon'

class StreamTag < Cacheable
	extend TypeID;
	init_storable(:streamsdb, 'streamtags');
	relation_multi(:entry_tags, :tagid, EntryTag, :tagid);
	#relation_singular(:category_icon, :iconid, StreamIcon);
	
	def entry_ids
		return self.entry_tags.map {|entry_tag| entry_tag.entryid}
	end
	
	def entries
		stream_entry_ids = self.entry_tags.map {|entry_tag| entry_tag.entryid};
		stream_entries = StreamEntry.find(:order => "date DESC", *stream_entry_ids);
		
		ordered_entries = stream_entries.sort{|x, y| y.date <=> x.date};
		
		return ordered_entries.map {|stream_entry| stream_entry.entry};
	end
	
	def uri_info(mode='self')
		return [self.tagname, "/music/stream/#{self.tagid}/"];
	end
	
	class << self
		#provides a list of all entries associated with a specified tag. If
		#the tag is not found an empty ordered map will be returned. Without that
		#we have a nil error (from the call to entries).
		def find_items_by_name(tag)
			if (tag.kind_of?(String))
				tag = self.find(tag, :tagname, :first);
			end
			if(tag != nil)
				return tag.entries;
			else
				return OrderedMap.new();
			end
		end
	end
end