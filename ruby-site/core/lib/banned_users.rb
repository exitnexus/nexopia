lib_require :Core, 'storable/storable'

class BannedUsers < Storable
	init_storable(:db, "bannedusers");
end