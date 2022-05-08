lib_require :Core, 'storable/storable'

module FriendFinder
	class InviteOptout < Storable
		init_storable(:db, "inviteoptout");
	end
end