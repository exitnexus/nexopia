lib_require :Core, 'storable/cacheable', 'users/user'
lib_require :Streams, 'entry_tag', 'music_news', 'stream_icon'

class StreamEntry < Cacheable
	extend TypeID;
	init_storable(:streamsdb, 'streamentries');
	
	relation_multi(:entry_tags, :entryid, EntryTag, :entryid);
	relation_singular(:user, :userid, User);
	relation_singular(:display_icon, :iconid, StreamIcon);
	
	def entry
		storable_class = TypeID.get_class(self.typeid);
		if (storable_class.indexes[:PRIMARY].length > 1)
			return storable_class.find(self.primaryid, self.secondaryid, :promise, :first);
		else
			return storable_class.find(self.primaryid, :promise, :first);
		end
	end
	
=begin
	def display_icon
		if(iconid != nil && iconid > 0)
			icon = StreamIcon.find(:first, :promise, iconid);
		end
		
		return icon;
	end
=end
	
	def tags
		s = EntryTag.find(:entryid, self.entryid);
		stream_tag_ids = self.entry_tags.map {|entry_tag| entry_tag.tagid};
		stream_tags = StreamTag.find(*stream_tag_ids);
		return stream_tags;
	end
	
	def increment_views()
		self.views = self.views + 1;
		self.store();
	end

end