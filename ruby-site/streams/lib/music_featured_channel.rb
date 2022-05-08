module Music
	class MusicFeaturedChannel < Cacheable
		init_storable(:streamsdb, "musicfeaturedchannels");
	end
end
