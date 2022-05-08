<?

function validUsername($user){
	return (bool)getUserID($user); //returns true if getUserID returns a userid
}

function plusCallback($data){

	$userid = $data['input'];
	$duration = $data['quantity'];
	$fromid = $data['buyer'];
	$trackid = $data['invoice'];

	return addPlus($userid, $duration, $fromid, $trackid);
}

function addPlus($userid, $duration, $fromid, $trackid){ //duration in months
	global $db, $usersdb, $cache, $messaging, $userData;

	$adminid = 0;
	if($userData['userid'] != $fromid)
		$adminid = $userData['userid'];

	if(empty($userid))
		return "Bad userid: $userid";

	if(!is_numeric($userid)){
		$userid = getUserID($userid);
		if(!is_numeric($userid))
			return "Bad userid: $userid";
	}

	if(empty($duration) || $duration <= 0)
		return "Bad duration";

	$seconds = floor($duration * (86400*31)); //convert to seconds in a 31 day month

	$usersdb->prepare_query("UPDATE users SET premiumexpiry = GREATEST(premiumexpiry, #) + # WHERE userid = %", time(), $seconds, $userid);

	$db->prepare_query("INSERT INTO pluslog SET userid = #, time = #, `from` = #, `to` = #, admin = #, duration = #, trackid = #",
							$userid, time(), $fromid, $userid, $adminid, $seconds, $trackid );

	$cache->remove("userprefs-$userid");
	$cache->remove("userinfo-$userid");

	$subject = "You've got Plus!";

	$message = getStaticValue('plusaddmsg');
	$message = str_replace("%duration%", number_format($duration*31,0), $message);

	$messaging->deliverMsg($userid, $subject, $message, 0, "Nexopia", 0);

	return "Plus Added for userid $userid for $duration months\n";
}

//untested
function remindAlmostExpiredPlus(){
	global $usersdb, $messaging;

	$time = time();

	$res = $usersdb->prepare_query("SELECT userid FROM users WHERE premiumexpiry BETWEEN # AND #", $time + 86400*7, $time + 86400*8);

	$to = array();
	while($line = $res->fetchrow())
		$to[] = $line['userid'];

	$subject = "Not Much Plus Left!";
	$message = getStaticValue('plusremindermsg');

	$messaging->deliverMsg($to, $subject, $message, 0, "Nexopia", 0);
}

function deletePlus($userid){
	global $db, $forums, $config, $mods, $docRoot;

die("broken function");

//reset forumrank, anonymousviews
	$usersdb->prepare_query("UPDATE users SET forumrank = '', anonymousviews = 'n' WHERE userid = %", $userid);

//pending forumranks
	$res = $forums->db->prepare_query("SELECT id FROM forumrankspending WHERE userid = #", $userid);
	$rank = $res->fetchrow();
	if($rank){
		$id = $rank['id'];
		$forums->db->prepare_query("DELETE FROM forumrankspending WHERE userid = #", $userid);
		$mods->deleteItem('forumrank',$id);
	}

/*
//pics
	$result = $db->prepare_query("SELECT id FROM pics WHERE itemid = ? && priority > ?", $userid, $config['maxpics']);

	while($line = $result->fetchrow())
		removePic($userid, $line['id']);
*/
//gallery
	$result = $gallerydb->prepare_query("SELECT id FROM gallery WHERE userid = %", $userid);

	while($line = $result->fetchrow())
		removeGalleryPic($id);

	$gallerydb->prepare_query("DELETE FROM gallerycats WHERE userid = %", $uid);

//files
	rmdirrecursive( $docRoot . $config['basefiledir'] . floor($userid/1000) . "/" . $userid );

	//custom forums?

}

function transferPlus($from, $to){
	global $db, $msgs, $cache, $userData;

	settype($from, 'int');
	settype($to, 'int');

	if(empty($from) || empty($to)){
		$msgs->addMsg("Missing from ($from) or to ($to)");
		return false;
	}

	$time = time();

	$db->begin();

	$expiry = getPlusExpiry($from);

	$remaining = $expiry - $time;

	if($remaining < 86400*7){
		$msgs->addMsg("User $from doesn't have enough plus to transfer");
		return false;
	}

	$db->prepare_query("INSERT INTO pluslog SET userid = #, time = #, `from` = #, `to` = #, admin = #, duration = #",
							$from, $time, $from, $to, $userData['userid'], (0 - $remaining));

	$db->prepare_query("INSERT INTO pluslog SET userid = #, time = #, `from` = #, `to` = #, admin = #, duration = #",
							$to,   $time, $from, $to, $userData['userid'], $remaining - 86400*7 );


/*
	$db->prepare_query("UPDATE users SET premiumexpiry = # WHERE userid = #", $time, $from);
	$cache->remove("userinfo-$from");
	$cache->remove("userprefs-$from");
/*/
	fixPlus($from);
//*/

	fixPlus($to);

	$db->commit();

	return true;
}

function removePlus($userid, $id){ //removes a specific buy
	global $db, $userData;

	$res = $db->prepare_query("SELECT * FROM pluslog WHERE userid = # && id = #", $userid, $id);

	$line = $res->fetchrow();

	if(!$line)
		return;

	$db->prepare_query("INSERT INTO pluslog SET userid = #, time = #, `from` = #, `to` = #, admin = #, duration = #, trackid = #",
							$line['userid'], $line['time'], $line['userid'], 0, $userData['userid'], (0 - $line['duration']), $id);

	fixPlus($userid);
}

function fixPlus($userid){
	global $usersdb, $msgs, $cache;

	$expiry = getPlusExpiry($userid);

	$usersdb->prepare_query("UPDATE users SET premiumexpiry = # WHERE userid = %", $expiry, $userid);

	$cache->remove("userinfo-$userid");
	$cache->remove("userprefs-$userid");

	$msgs->addMsg("Plus for $userid set expire " . userDate("D M j, Y g:i a", $expiry));
}

function getPlusExpiry($userid){
	global $db;

	$expiry = gmmktime(7,0,0,6,1,2004); // end of trial period

	$res = $db->prepare_query("SELECT userid, duration, time FROM pluslog WHERE userid = # ORDER BY `time` ASC", $userid);

	while($line = $res->fetchrow()){
		if($expiry < $line['time'])
			$expiry = $line['time'];

		$expiry += $line['duration'];
	}

	return $expiry;
}

