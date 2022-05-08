lib_require :Core, 'storable/storable'

class Invite < Storable
	init_storable(:db, "invites");
end