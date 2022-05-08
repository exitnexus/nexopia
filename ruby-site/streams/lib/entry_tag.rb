lib_require :Core, 'storable/cacheable', 'typeid'
#lib_require :Streams, 'stream_entry'

class EntryTag < Cacheable
	init_storable(:streamsdb, 'entrytags');
	
end