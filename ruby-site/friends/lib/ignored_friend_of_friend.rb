class IgnoredFriendOfFriend < Cacheable
	init_storable(:usersdb, 'ignoredfriendsoffriends')
end

class User
	relation_multi_cached :ignored_friends_of_friends, :userid, IgnoredFriendOfFriend, "ruby_ignored_friends_of_friends"
end