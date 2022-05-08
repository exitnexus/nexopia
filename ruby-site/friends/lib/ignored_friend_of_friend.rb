module Friends
	class IgnoredFriendOfFriend < Cacheable
		init_storable(:usersdb, 'ignoredfriendsoffriends')
	end
end
class User
	relation :multi, :ignored_friends_of_friends, :userid, Friends::IgnoredFriendOfFriend
end