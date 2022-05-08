<?


function addPremium($userid,$duration){ //duration in months
	global $db;

	if(empty($userid))
		return "Bad userid: $userid";

	if(!is_numeric($userid)){
		$userid = getUserID($userid);
		if(!is_numeric($userid))
			return "Bad userid: $userid";
	}

	if(empty($duration))
		return "Bad duration";

	$seconds = $duration * (86400*31); //convert to seconds in a 31 day month

	$db->prepare_query("UPDATE users SET premiumexpiry = GREATEST(premiumexpiry,?) + ? WHERE userid = ?", time(), $seconds, $userid);

	$db->prepare_query("INSERT INTO premiumlog SET userid = ?, duration = ?, time = ?", $userid, $seconds, time());

	return "Plus Added for userid $userid for $duration months\n";
}

function deletePremium($userid){
	global $db, $config, $mods;

//reset forumrank, anonymousviews
	$db->prepare_query("UPDATE users SET forumrank = '', anonymousviews = 'n' WHERE userid = ?", $userid);

//pending forumranks
	$db->prepare_query("SELECT id FROM forumrankspending WHERE userid = ?", $userid);
	if($db->numrows() > 0){
		$id = $db->fetchfield();
		$db->prepare_query("DELETE FROM forumrankspending WHERE userid = ?", $userid);
		$mods->deleteItem('forumrank',$id);
	}

/*
//pics
	$result = $db->prepare_query("SELECT id FROM pics WHERE itemid = ? && priority > ?", $userid, $config['maxpics']);

	while($line = $db->fetchrow($result))
		removePic($line['id']);
*/
//gallery
	$result = $db->prepare_query("SELECT id FROM gallery WHERE userid = ?", $userid);

	while($line = $db->fetchrow($result))
		removeGalleryPic($id);

	$db->prepare_query("DELETE FROM gallerycats WHERE userid = ?", $uid);

//files
	rmdirrecursive( $masterserver . $config['basefiledir'] . floor($userid/1000) . "/" . $userid );

	//custom forums?

}

function transferPremium($from, $to){
	global $db, $msgs;

	if(empty($from) || empty($to)){
		$msgs->addMsg("Missing from ($from) or to ($to)");
		return;
	}

	$db->prepare_query("UPDATE premiumlog SET userid = ? WHERE userid = ?", $to, $from);

	if($db->affectedrows() == 0){
		$msgs->addMsg("User $from had no plus");
		return;
	}

	$expiry = gmmktime(7,0,0,6,1,2004); // end of trial period

	$db->prepare_query("SELECT userid,duration,time FROM premiumlog WHERE userid = ? ORDER BY id ASC", $to);

	while($line = $db->fetchrow()){
		if($expiry < $line['time'])
			$expiry = $line['time'];

		$expiry += $line['duration'];
	}



	$db->prepare_query("UPDATE users SET premiumexpiry = ? WHERE userid = ?", $expiry, $to);
	$db->prepare_query("UPDATE users SET premiumexpiry = 0 WHERE userid = ?", $from);

	$msgs->addMsg("Plus for $to set expire " . userDate("D M j, Y g:i a", $expiry));
}


function fixPremium($userid){
	global $db;

	$expiry = gmmktime(7,0,0,6,1,2004); // end of trial period

	$db->prepare_query("SELECT userid,duration,time FROM premiumlog WHERE userid = ? ORDER BY id ASC", $userid);

	while($line = $db->fetchrow()){
		if($expiry < $line['time'])
			$expiry = $line['time'];

		$expiry += $line['duration'];
	}

	$db->prepare_query("UPDATE users SET premiumexpiry = ? WHERE userid = ?", $expiry, $userid);
}


