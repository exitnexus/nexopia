lib_require :Core, 'storable/storable'

class BannedWords < Storable
	init_storable(:db, "bannedwords");
end