lib_require :Core, 'storable/storable'

class InviteOptout < Storable
	init_storable(:db, "inviteoptout");
end