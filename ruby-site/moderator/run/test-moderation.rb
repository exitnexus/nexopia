# Test moderators (type 99):
#  - ???? 6639 lvl 3
#  - examtester 195 lvl 3
#  - graham 175 lvl 6
#  - timo 5 lvl 12

last_id = nil

$log.to(:stderr) {
$log.log_minlevel_raise(:sql, :debug) {
	
	def vote_on_item(userid, vote)
		last_id = nil
		Moderator::TestQueue.fetch_votable_items(User.find(:first, userid), 1, nil).each {|i| last_id = i.id }
		Moderator::TestQueue.vote_on_items(User.find(:first, userid), {last_id => vote}) if (last_id)
	end
	
	def q_main_vote_on_item(userid, vote)
		last_id = nil
		Moderator::TestWithQuestionableQueue.fetch_votable_items(User.find(:first, userid), 1, nil).each {|i| last_id = i.id }
		Moderator::TestWithQuestionableQueue.vote_on_items(User.find(:first, userid), {last_id => vote}) if (last_id)
	end		
	def q_second_vote_on_item(userid, vote)
		last_id = nil
		Moderator::TestQuestionableQueue.fetch_votable_items(User.find(:first, userid), 1, nil).each {|i| last_id = i.id }
		Moderator::TestQuestionableQueue.vote_on_items(User.find(:first, userid), {last_id => vote}) if (last_id)
	end		

	$log.info("Shouldn't get item back again if you vote on it once.", :critical)
	Moderator::TestQueue.add_item(0, 55, false)
	vote_on_item(195, false);
	items = Moderator::TestQueue.fetch_votable_items(User.find(:first, 195), 1, nil)
	if (items.length > 0)
		puts("Got items back but probably shouldn't have: #{items.join(',')}")
		Moderator::TestQueue.vote_on_items(User.find(:first, 195), {items.last.id => false})
	end
	# veto it out
	vote_on_item(5, false)
	
	$log.info("One veto(+12) vote in favour", :critical)
	Moderator::TestQueue.add_item(1, 2, false)
	vote_on_item(5, true)
	
	$log.info("One veto(-12) vote against", :critical)
	Moderator::TestQueue.add_item(3, 4, false)
	vote_on_item(5, false)
	
	$log.info("One full points vote in favour", :critical)
	Moderator::TestQueue.add_item(5, 6, false)
	vote_on_item(175, true)

	$log.info("One full points vote against", :critical)
	Moderator::TestQueue.add_item(5, 6, false)
	vote_on_item(175, false)
	
	$log.info("Two half points vote in favour", :critical)
	Moderator::TestQueue.add_item(5, 6, false)
	vote_on_item(6639, true)
	vote_on_item(195, true)

	$log.info("Two half points vote against", :critical)
	Moderator::TestQueue.add_item(5, 6, false)
	vote_on_item(6639, false)
	vote_on_item(195, false)

	$log.info("One half point and one full point in favour", :critical)
	Moderator::TestWithQuestionableQueue.add_item(5, 6, false)
	q_main_vote_on_item(6639, true)
	q_main_vote_on_item(175, true)

	$log.info("One half point and one full point against", :critical)
	Moderator::TestWithQuestionableQueue.add_item(5, 60, false)
	q_main_vote_on_item(6639, false)
	q_main_vote_on_item(175, false)

	$log.info("Two half points votes in opposite directions and one veto in favour", :critical)
	Moderator::TestQueue.add_item(5, 6, false)
	vote_on_item(6639, true)
	vote_on_item(195, false)
	vote_on_item(5, true)

	$log.info("Two half points votes in opposite directions and one veto against", :critical)
	Moderator::TestQueue.add_item(5, 6, false)
	vote_on_item(6639, false)
	vote_on_item(195, true)
	vote_on_item(5, false)

	$log.info("Should not allow multiple votes from the same person", :critical)
	Moderator::TestQueue.add_item(7, 8, false)
	vote_on_item(6639, true)
	vote_on_item(6639, false)
	vote_on_item(195, true)
	
	def q_main_vote_on_item(userid, vote)
		last_id = nil
		Moderator::TestWithQuestionableQueue.fetch_votable_items(User.find(:first, userid), 1, nil).each {|i| last_id = i.id }
		Moderator::TestWithQuestionableQueue.vote_on_items(User.find(:first, userid), {last_id => vote}) if (last_id)
	end		
	def q_second_vote_on_item(userid, vote)
		last_id = nil
		Moderator::TestQuestionableQueue.fetch_votable_items(User.find(:first, userid), 1, nil).each {|i| last_id = i.id }
		Moderator::TestQuestionableQueue.vote_on_items(User.find(:first, userid), {last_id => vote}) if (last_id)
	end		
	
	$log.info("Straight yes vote on questionable-activated queue should just go through", :critical)
	Moderator::TestWithQuestionableQueue.add_item(10, 11, false)
	q_main_vote_on_item(5, true)
	
	$log.info("Split vote on questionable activated queue should go to questionable, and then yes vote on that queue should correctly do yes behaviour", :critical)
	Moderator::TestWithQuestionableQueue.add_item(12, 13, false)
	q_main_vote_on_item(6639, false)
	q_main_vote_on_item(195, true)
	q_main_vote_on_item(5, false)
	q_second_vote_on_item(175, true)
}}