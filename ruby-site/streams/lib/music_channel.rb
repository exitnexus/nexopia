lib_require :Streams, "music_channel_tag_selection"

module Music
	class MusicChannel < Cacheable
		init_storable(:streamsdb, "musicchannels");
		
		relation_multi(:channel_tags, :id, :channelid, MusicChannelTagSelection);
		
		def articles()
			stream_tag_list = Array.new();
			stream_tag_list = channel_tags.map {|channel_tag| channel_tag.stream_tag};
			
			entry_tag_list = stream_tag_list.map {|stream_tag| stream_tag.entry_tags };
			
			entry_tag_list.flatten!();
			
			stream_entry_id_list = entry_tag_list.map{|entry_tag| entry_tag.entryid };
			
			stream_entry_list = StreamEntry.find(stream_entry_id_list);
			
			item_hash = Hash.new();
			
			for stream_entry in stream_entry_list
				if(item_hash[stream_entry.typeid.to_s()].nil?())
					item_hash[stream_entry.typeid.to_s()] = Array.new();
				end
				storable_class = TypeID.get_class(stream_entry.typeid);
				if(storable_class.indexes[:PRIMARY].length > 1)
					item_hash[stream_entry.typeid.to_s()] << [stream_entry.primaryid, stream_entry.secondaryid];
				else
					item_hash[stream_entry.typeid.to_s()] << stream_entry.primaryid;
				end
			end
		end
	end
end
