lib_require :Core, 'storable/cacheable', 'typeid'

class StreamIconType < Cacheable
	init_storable(:streamsdb, 'streamicontypes');
	
end