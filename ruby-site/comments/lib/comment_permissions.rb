module Comments
		def self.can_post?(owner, poster)
			
				# User can always comment on own pages
				return true if owner == poster
				
				# variables to figure out if the current user can post a comment or not.
				within_age_range = (poster.age >= owner.defaultminage && poster.age <= owner.defaultmaxage)
				ignore_by_age = (owner.ignorebyage == "comments" || owner.ignorebyage == "both") ? true : false
	
				# A user should be able to post with "only friends turned on"
				are_friends = owner.friend?(poster) || (owner.userid== poster.userid)
				friends_only = (owner.onlyfriends == "comments" || owner.onlyfriends == "both") ? true : false
	
				# It's a little complicated to figure out if someone can post or not.
				# Let's start with whether or not comments are turned on.
				can_post = Profile::ProfileBlockVisibility.visible?(owner.send("commentsmenuaccess".to_sym()), owner, poster)
				# But if they're being ignored or they're anonymous they can't post
				can_post &&= ( !poster.anonymous? && !owner.ignored?(poster) )
				# And if they're outside the person's age range they can't post if that preference is turned on.  But being friends overrides that.
				can_post &&= (ignore_by_age ? (within_age_range || are_friends) : true)
				# An if the person only wants comments from friends and they aren't friends, then they can't post.
				can_post &&= (friends_only ? are_friends : true)
	
				return can_post
		end
end