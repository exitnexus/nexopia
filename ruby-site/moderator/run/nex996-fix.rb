# There was a brief period of time (a few days?) where just looking at a person's page who was indefinitely 
# frozen would unfreeze them. This happened after a previous fix to make sure users were unfrozen after their 
# frozentime expired (didn't take into account the frozentime == 0 special case).
#
# This script finds the userids in the abuselog of users who have been frozen indefinitely (we can figure this
# out by the existence of '[ Indefinite ]' in the subject of the abuselog) and not subsequently unfrozen (no
# action=11 record entered after the action=5 record). It then finds the user for each of those ids and sets
# the state to frozen if it is not frozen already.

SHOULD_BE_FROZEN_QUERY = <<-EOS
	SELECT DISTINCT frozen.userid
	FROM abuselog frozen 
	WHERE 
		frozen.action = 5 AND
		frozen.subject LIKE '%[ Indefinite ]%' AND 
		NOT EXISTS
			(SELECT unfrozen.id
			FROM abuselog unfrozen
			WHERE
				unfrozen.action = 11 AND
				unfrozen.userid = frozen.userid AND
				unfrozen.time > frozen.time)
EOS

results = $site.dbs[:moddb].query(SHOULD_BE_FROZEN_QUERY)

results.each { |row|
	user = User.find :first, row['userid'].to_i
	if(!user.nil?)
		if(user.state == 'new' || user.state == 'active')
			user.state = 'frozen'
			user.store
			puts("Refroze user: #{user.username} (userid: #{user.userid})")
		end
	end
}