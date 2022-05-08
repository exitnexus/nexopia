lib_require :Streams, "stream_tag"

module Music
	class MusicChannelTagSelection < Cacheable
		init_storable(:streamsdb, "musicchanneltagselections");
		
		relation_singular(:stream_tag, :tagid, StreamTag);
	end
end
