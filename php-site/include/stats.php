<?

function updateStats(){
	if(defined("STATSUPDATED"))
		return;
	define("STATSUPDATED", true);

	global $userData, $config, $sWidth, $sHeight, $siteStats, $db, $fastdb, $statsdb, $cache, $logdb;

	$siteStats = $cache->hdget('stats', 20, 'getStats');

	if($userData['loggedIn']){
		if($userData['showrightblocks'] == 'y'){
			$online = $cache->get(array($userData['userid'], "friendsonline-$userData[userid]"));

			if($online === false){
				$db->prepare_query("SELECT friendid,username FROM friends,users WHERE friends.userid = # && friendid=users.userid && online='y'", $userData['userid']);
				$online = array();
				while($line = $db->fetchrow())
					$online[$line['friendid']] = $line['username'];

				$cache->put(array($userData['userid'], "friendsonline-$userData[userid]"), $online, 120);
			}
			$userData['friends'] = $online;
			$userData['friendsonline'] = count($online);
		}else{
			$online = $cache->get(array($userData['userid'], "numfriendsonline-$userData[userid]"));

			if($online === false){
				$db->prepare_query("SELECT count(*) FROM friends,users WHERE friends.userid = # && friendid=users.userid && online='y'", $userData['userid']);

				$online = $db->fetchfield();

				$cache->put(array($userData['userid'], "numfriendsonline-$userData[userid]"), $online, 120);
			}

			$userData['friendsonline'] = $online;
		}
	}

	$ip = ip2int(getip());
	$time = time();

//fastdb first, because it shares a server with $sessiondb which is used in auth()

	if($userData['loggedIn']){
		$fastdb->prepare_query($userData['userid'], "UPDATE useractivetime SET activetime = #, hits = hits+1, ip = #, online = 'y' WHERE userid = #", $time, $ip, $userData['userid']);
		if($fastdb->affectedrows()==0)
			$fastdb->prepare_query($userData['userid'], "INSERT IGNORE INTO useractivetime SET activetime = #, hits = 1, ip = #, online = 'y', userid = #", $time, $ip, $userData['userid']);

		$statsdb->query("UPDATE stats SET hitstotal = hitstotal + 1, hitsuser = hitsuser + 1, hits$userData[sex] = hits$userData[sex] + 1");
	}else{
		$statsdb->query("UPDATE stats SET hitstotal = hitstotal + 1, hitsanon = hitsanon + 1");
	}

	$statsdb->close();
	$fastdb->close(); //may and may not be redundant, don't disconnect earlier as $fastdb and $statsdb may be the same connection



	if($userData['loggedIn']){
		$logdb->prepare_query($userData['userid'], "UPDATE userhitlog SET activetime = #, hits = hits+1 WHERE userid = # && ip = #", $time, $userData['userid'], $ip);
		if($logdb->affectedrows()==0)
			$logdb->prepare_query($userData['userid'], "INSERT IGNORE INTO userhitlog SET activetime = #, hits = hits+1, userid = #, ip = #", $time, $userData['userid'], $ip);

		$logdb->prepare_query($ip, "UPDATE iplog SET time = #, hits = hits+1, userid = # WHERE ip = #", $time, $userData['userid'], $ip);
		if($logdb->affectedrows()==0)
			$logdb->prepare_query($ip, "INSERT IGNORE INTO iplog SET time = #, hits = 1, userid = #, ip = #", $time, $userData['userid'], $ip);
	}else{
		$logdb->prepare_query($ip, "UPDATE iplog SET time = #, hits = hits+1 WHERE ip = #", $time, $ip);
		if($logdb->affectedrows()==0)
			$logdb->prepare_query($ip, "INSERT IGNORE INTO iplog SET time = #, hits = 1, ip = #", $time, $ip);
	}

	$logdb->close();
}

function getStats(){
	global $db, $fastdb, $statsdb, $config, $cache, $sessiondb;

	$time = time();

//logout inactive users
	$fastdb->prepare_query(false, "SELECT userid FROM useractivetime WHERE online = 'y' && activetime <= #", ($time - $config['friendAwayTime']) );
	if($fastdb->numrows()){
		$loggedout = array();
		while($line = $fastdb->fetchrow())
			$loggedout[] = $line['userid'];

		logout($loggedout);
	}

//stats
	$statsdb->query("SELECT hitstotal, onlineusersmax, onlineguestsmax, userstotal, userswithpics, userswithsignpics FROM stats");
	$siteStats = $statsdb->fetchrow();

//online
	$fastdb->query(false, "SELECT count(*) as count FROM useractivetime WHERE online = 'y'"); //if load balanced, may return multiple rows

	$siteStats['online'] = 0;
	while($line = $fastdb->fetchrow())
		$siteStats['online'] += $line['count'];

//guests
	$sessiondb->prepare_query(false, "SELECT count(*) FROM sessions WHERE userid IS NULL && activetime >= #", ($time - $config['friendAwayTime']) );
	$siteStats['guests'] = $sessiondb->fetchfield();

//update stats
	$set = $params = array();

	$set[] = "onlineusers = #";		$params[] = $siteStats['online'];
	$set[] = "onlineguests = #";	$params[] = $siteStats['guests'];

//maxonline
	if($siteStats['onlineusersmax'] > 0 && $siteStats['online'] > 0 && $siteStats['onlineusersmax'] < $siteStats['online']){
		$set[] = "onlineusersmax = GREATEST(onlineusersmax, #)";	$params[] = $siteStats['online'];
	}
//maxguestsonline
	if($siteStats['onlineguestsmax'] > 0 && $siteStats['guests'] > 0 && $siteStats['onlineguestsmax'] < $siteStats['guests']){
		$set[] = "onlineguestsmax = GREATEST(onlineguestsmax, #)";	$params[] = $siteStats['guests'];
	}

	$statsdb->prepare_array_query("UPDATE stats SET " . implode(", ", $set), $params);

	return $siteStats;
}

function updateUserBirthdays(){
	global $db;

	$db->unbuffered_query("SELECT userid, dob, age, sex FROM users");

	$updateages = array();

	while($line = $db->fetchrow()){

		$age = getAge($line['dob']);
		if($age != $line['age']){
			if(!isset($updateages[$age]))
				$updateages[$age] = array();
			$updateages[$age][$line['userid']] = $line['sex'];
		}
	}

	$db->query("TRUNCATE bday");

	$vals = array();
	foreach($updateages as $age => $uids)
		foreach($uids as $uid => $sex)
			$vals[] = "($uid, $age, '$sex')";

	if(count($vals))
		$db->query("INSERT INTO bday (userid, age, sex) VALUES " . implode(',',$vals));

	$db->query("UPDATE users, bday SET users.age = bday.age WHERE users.userid = bday.userid");
	$db->query("UPDATE pics, bday SET pics.age = bday.age WHERE pics.itemid = bday.userid");
}

function updateUserIndexes(){
	global $db;

	$db->query("TRUNCATE usersearch");

//build to a temp table, then swap to be active?
//*
	$db->prepare_query("INSERT INTO usersearch (userid, age, sex, loc, active, pic, single, sexuality)
		SELECT userid, age, sex, loc, ( (activetime > #) + (online = 'y') ) as active,
		( (firstpic >= 1) + (signpic = 'y') ) as pic, (single = 'y') as single, sexuality
		FROM users ORDER BY username", time() - 86400*7);
/*/

//SLOW...

	$db->prepare_query("INSERT INTO usersearch (userid, age, sex, loc, active, pic, single, sexuality) SELECT userid, age, sex, loc, ( (activetime > #) + (online = 'y') ) as active, ( (firstpic >= 1) + (signpic = 'y') ) as pic, (single = 'y') as single, sexuality FROM users WHERE username < 'a' ORDER BY username", time() - 86400*7);

	for($c = ord('a'); $c <= ord('y'); $c++)
		$db->prepare_query("INSERT INTO usersearch (userid, age, sex, loc, active, pic, single, sexuality) SELECT userid, age, sex, loc, ( (activetime > #) + (online = 'y') ) as active, ( (firstpic >= 1) + (signpic = 'y') ) as pic, (single = 'y') as single, sexuality FROM users WHERE username LIKE '" . chr($c) . "%' ORDER BY username", time() - 86400*7);

	$db->prepare_query("INSERT INTO usersearch (userid, age, sex, loc, active, pic, single, sexuality) SELECT userid, age, sex, loc, ( (activetime > #) + (online = 'y') ) as active, ( (firstpic >= 1) + (signpic = 'y') ) as pic, (single = 'y') as single, sexuality FROM users WHERE username > 'z' ORDER BY username", time() - 86400*7);


//*/

//active = 0,1,2
//pic = 0,1,2

//all
	$db->query("CREATE TEMPORARY TABLE tempstats1 SELECT age, sex, count(*) as count FROM usersearch GROUP BY sex, age");
	$db->query("UPDATE agesexgroups, tempstats1 SET total = count WHERE agesexgroups.age = tempstats1.age && agesexgroups.sex = tempstats1.sex");

//active
	$db->query("CREATE TEMPORARY TABLE tempstats2 SELECT age, sex, count(*) as count FROM usersearch WHERE active IN (1,2) GROUP BY sex, age");
	$db->query("UPDATE agesexgroups, tempstats2 SET active = count WHERE agesexgroups.age = tempstats2.age && agesexgroups.sex = tempstats2.sex");

//pics
	$db->query("CREATE TEMPORARY TABLE tempstats3 SELECT age, sex, count(*) as count FROM usersearch WHERE pic IN (1,2) GROUP BY sex, age");
	$db->query("UPDATE agesexgroups, tempstats3 SET pics = count WHERE agesexgroups.age = tempstats3.age && agesexgroups.sex = tempstats3.sex");

//signpics
	$db->query("CREATE TEMPORARY TABLE tempstats4 SELECT age, sex, count(*) as count FROM usersearch WHERE pic = 2 GROUP BY sex, age");
	$db->query("UPDATE agesexgroups, tempstats4 SET signpics = count WHERE agesexgroups.age = tempstats4.age && agesexgroups.sex = tempstats4.sex");

//activepics
	$db->query("CREATE TEMPORARY TABLE tempstats5 SELECT age, sex, count(*) as count FROM usersearch WHERE active IN (1,2) && pic IN (1,2) GROUP BY sex, age");
	$db->query("UPDATE agesexgroups, tempstats5 SET activepics = count WHERE agesexgroups.age = tempstats5.age && agesexgroups.sex = tempstats5.sex");

//activesignpics
	$db->query("CREATE TEMPORARY TABLE tempstats6 SELECT age, sex, count(*) as count FROM usersearch WHERE active IN (1,2) && pic = 2 GROUP BY sex, age");
	$db->query("UPDATE agesexgroups, tempstats6 SET activesignpics = count WHERE agesexgroups.age = tempstats6.age && agesexgroups.sex = tempstats6.sex");

//single
	$db->query("CREATE TEMPORARY TABLE tempstats7 SELECT age, sex, count(*) as count FROM usersearch WHERE single = 1 GROUP BY sex, age");
	$db->query("UPDATE agesexgroups, tempstats7 SET single = count WHERE agesexgroups.age = tempstats7.age && agesexgroups.sex = tempstats7.sex");

//sexuality
	$db->query("CREATE TEMPORARY TABLE tempstats8 SELECT age, sex, sexuality, count(*) as count FROM usersearch GROUP BY sex, age, sexuality");
	$db->query("UPDATE agesexgroups, tempstats8 SET sexuality1 = count WHERE agesexgroups.age = tempstats8.age && agesexgroups.sex = tempstats8.sex && sexuality = 1");
	$db->query("UPDATE agesexgroups, tempstats8 SET sexuality2 = count WHERE agesexgroups.age = tempstats8.age && agesexgroups.sex = tempstats8.sex && sexuality = 2");
	$db->query("UPDATE agesexgroups, tempstats8 SET sexuality3 = count WHERE agesexgroups.age = tempstats8.age && agesexgroups.sex = tempstats8.sex && sexuality = 3");

//locs
	$db->query("CREATE TEMPORARY TABLE loccounts SELECT loc, count(*) as count FROM usersearch GROUP BY loc");
	$db->query("UPDATE locs, loccounts SET users = count WHERE id = loc");

//interests
	$db->query("CREATE TEMPORARY TABLE interestcounts SELECT interestid, count(*) as count FROM userinterests GROUP BY interestid");
	$db->query("UPDATE interests, interestcounts SET users = count WHERE id = interestid");

//straight age/sex search
//	$db->query("INSERT INTO agesexsearch (age, sex, userid) SELECT age, sex, userid FROM usersearch WHERE active >= 1 && pic >= 1");
		//uses the agegroups stats from above

}

function updatePicsVotable(){
	global $db, $config, $cache;

	$db->query("TRUNCATE TABLE picsvotable");

//add all votable pics
	$db->prepare_query("INSERT INTO picsvotable (sex, age, picid) SELECT pics.sex, pics.age, pics.id FROM pics, users WHERE pics.itemid = users.userid && users.activetime >= # && users.frozen = 'n' && pics.vote = 'y'", time() - 86400*$config['votableactivity']);
//	$db->prepare_query("INSERT INTO picsvotable (sex, age, picid) SELECT pics.sex, pics.age, pics.id FROM pics WHERE vote = 'y'");

/*
//add everyone who voted a second time for extra weighting
	$db->query("CREATE TEMPORARY TABLE voteusers SELECT DISTINCT userid FROM votehist");
	$db->query("INSERT INTO picsvotable (sex, age, picid) SELECT pics.sex, pics.age, pics.id FROM voteusers, pics WHERE voteusers.userid = pics.itemid && pics.vote = 'y'");
*/

	$db->query("CREATE TEMPORARY TABLE temppics SELECT age, sex, count(*) as count FROM picsvotable GROUP BY sex, age");
	$db->query("UPDATE agesexgroups, temppics SET picsvotable = count WHERE agesexgroups.age = temppics.age && agesexgroups.sex = temppics.sex");
}

function setTopLists(){
	global $db, $config, $cache;

	$sexes = array("Male","Female");
	$ages = range(14,60);

	$db->prepare_query("SELECT age, sex, MIN(score) AS score FROM picstop GROUP BY age, sex");

	$minscores = array();

	while($line = $db->fetchrow())
		$minscores[$line['age']][$line['sex']] = $line['score'];

	$usersByAge = $cache->get("usersByAge",3600*6,'getUsersByAge');

	foreach($ages as $age){
		if(!isset($minscores[$age]))
			$minscores[$age] = array("Male" => 0, "Female" => 0);
		foreach($sexes as $sex){
			$num = round($usersByAge[$age][$sex]["pics"] / 30);
			if($num > 100)		$num = 100;
			elseif($num < 10)	$num = 10;

			if(!isset($minscores[$age][$sex]) || $minscores[$age][$sex] < 4.1)
				$minscores[$age][$sex] = 4.1;

			$db->prepare_query("DELETE FROM picstop WHERE age = # && sex = ?", $age, $sex);

			$db->prepare_query("INSERT IGNORE INTO picstop SELECT id, pics.itemid, username, pics.age, pics.sex, score FROM pics, users WHERE pics.itemid=users.userid && pics.age = # && pics.sex = ? && vote = 'y' && votes >= # && score >= # && activetime >= # && frozen = 'n' ORDER BY score DESC LIMIT $num", $age, $sex, $config['minVotesTop10'], ($minscores[$age][$sex] - 0.1), time() - 86400*7 );
		}
	}
}

function updateSpotlightList(){
	global $db, $cache;

	$db->query("TRUNCATE TABLE spotlight");

	$db->prepare_query("INSERT INTO spotlight (userid, age, sex, username) SELECT userid, age, sex, username FROM users WHERE spotlight = 'y' && premiumexpiry > # && firstpic > 0 && frozen='n'", time());

	$cache->put("spotlightmax", $db->affectedrows(), 86400);
}

function getUsersByAge(){
	global $db;

	$db->query("SELECT * FROM agesexgroups ORDER BY age");

	$users = array();
	while($line = $db->fetchrow())
		$users[$line['age']][$line['sex']] = $line;

	return $users;
}

function getNumUsersInAgeSexCol($sexes, $minage, $maxage, $col){
	global $cache, $db;

	if(!is_array($sexes))
		$sexes = array($sexes);

	$users = $cache->get("usersByAge",3600*6,'getUsersByAge');

	$total = 0;
	for($age = $minage; $age <= $maxage; $age++)
		foreach($sexes as $sex)
			if(isset($users[$age][$sex]))
				$total += $users[$age][$sex][$col];

	return $total;
}

function getNumUsersInLocs($locs){
	global $cache, $db;

	$users = $cache->get("usersInLocs");

	if(!$users){
		$db->query("SELECT id, users FROM locs");

		$users = array();
		while($line = $db->fetchrow())
			$users[$line['id']] = $line['users'];

		$cache->put("usersInLocs", $users, 3600*6);
	}

	$total = 0;
	foreach($locs as $loc)
//		if(isset($users[$loc]))
		$total += $users[$loc];

	return $total;
}

