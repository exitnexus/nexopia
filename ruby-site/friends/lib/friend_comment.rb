class FriendComment < Cacheable
	init_storable(:usersdb, 'friendscomments')
	
	def to_s
		return self.comment
	end
end