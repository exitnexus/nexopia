<?
/* MODULE stats.php */

/* SYNOPSIS
 * This module contains functions related to both populating the stats tables in the system on a nightly
 * basis, along with retreiving information from them
 * END SYNOPSIS */


/* FUNCTION updateStats */

/* SYNOPSIS
 * This function is called to update stats on every page load
 * END SYNOPSIS */

/* HISTORY
 * IncrDev May 29, 2006 by pdrapeau
 * END HISTORY */
function updateStats(){
	global $userData, $config, $sWidth, $sHeight, $siteStats, $db, $masterdb, $cache, $usersdb;
	if(defined("STATSUPDATED"))
		return $siteStats;
	define("STATSUPDATED", true);

	$siteStats = $cache->hdget('stats', 20, 'getStats');

	if($userData['halfLoggedIn']){
		$online = $cache->get("friendsonline-$userData[userid]");

		if($online === false){
			$online = array();

			$friends = getFriendsListIDs($userData['userid']);

			if(count($friends)){
				$activetimes = $cache->get_multi($friends, 'useractive-');

				$time = time();

				foreach($activetimes as $uid => $activetime)
					if($activetime > $time - $config['friendAwayTime'])
						$online[] = $uid;
			}

			$cache->put("friendsonline-$userData[userid]", $online, 60);
		}

		$userData['friends'] = $online;
		$userData['friendsonline'] = count($online);
	}

	$ip = ip2int(getip());
	$time = time();

	if($userData['loggedIn']){
		$usersdb->prepare_query("UPDATE useractivetime SET activetime = #, hits = hits+1, ip = #, online = 'y' WHERE userid = %", $time, $ip, $userData['userid']);
		if($usersdb->affectedrows()==0)
			$usersdb->prepare_query("INSERT IGNORE INTO useractivetime SET activetime = #, hits = 1, ip = #, online = 'y', userid = %", $time, $ip, $userData['userid']);

		$plusclause = ($userData['premium'] ? ", hitsplus = hitsplus + 1" : "");

		$sex = trim($userData['sex']);
		$usersdb->squery($userData['userid'], "UPDATE stats SET hitstotal = hitstotal + 1, hitsuser = hitsuser + 1, `hits$sex` = `hits$sex` + 1$plusclause");
	}else{
		$masterdb->query("UPDATE anonstats SET hitstotal = hitstotal + 1, hitsanon = hitsanon + 1");
	}

	if($userData['loggedIn']){
		$usersdb->prepare_query("UPDATE userhitlog SET activetime = #, hits = hits+1 WHERE userid = % && ip = #", $time, $userData['userid'], $ip);
		if($usersdb->affectedrows()==0)
			$usersdb->prepare_query("INSERT IGNORE INTO userhitlog SET activetime = #, hits = hits+1, userid = %, ip = #", $time, $userData['userid'], $ip);
	}
	return $siteStats;
}
/* END FUNCTION updateStats */


/* FUNCTION rebuildStats */

/* SYNOPSIS
 * This function is called on a nightly basis to re-build totals in the stats table in each instance of the users
 * database
 * the following totals are updated:
 *
 * userstotal
 *
 * END SYNOPSIS */

/* HISTORY
 * IncrDev May 29, 2006 by pdrapeau
 * END HISTORY */
function rebuildStats () {
	global $usersdb, $masterdb;

	// process the count of total users in each database instance

	$dbs = $usersdb->getSplitDBs();
	$userTotal = 0;
	foreach($dbs as $db){
		$resultSet = $db->query("SELECT count(*) FROM users");
		$numUsers = $resultSet->fetchfield();

		$userTotal += $numUsers;

		$db->prepare_query("UPDATE stats SET userstotal = #", $numUsers);
		if($db->affectedrows() == 0) {
			$resultSet = $db->query("SELECT count(*) FROM stats");
			$numRows = $resultSet->fetchfield();
			if ($numRows < 1)
				$db->prepare_query("INSERT IGNORE INTO stats SET userstotal = #", $numUsers);
		}
	}
	return true;
}
/* END FUNCTION rebuildStats */


/* FUNCTION getStats */

/* SYNOPSIS
 * This function retuns global site stats
 * END SYNOPSIS */

/* HISTORY
 * IncrDev May 29, 2006 by pdrapeau
 * END HISTORY */
function getStats(){
	global $usersdb, $masterdb, $config, $cache, $auth;

	// get current time
	$time = time();

	// define an array of values that holds what stats we're keeping track of in both the masterdb
	// and in each instance of the usersdb, init to 0
	$siteStatsMasterdb = Array();
	$siteStatsMasterdb['hitsanon'] = 0;
	$siteStatsMasterdb['hitstotal'] = 0;

	$siteStatsUserdb = Array();
	$siteStatsUserdb['hitsFemale'] = 0;
	$siteStatsUserdb['hitsMale'] = 0;
	$siteStatsUserdb['hitsuser'] = 0;
	$siteStatsUserdb['hitsplus'] = 0;
	$siteStatsUserdb['hitstotal'] = 0;
	$siteStatsUserdb['userstotal'] = 0;


	//logout inactive users
	$res = $usersdb->prepare_query("SELECT userid FROM useractivetime WHERE online = 'y' && activetime <= #", ($time - $config['friendAwayTime']) );
	$loggedout = array();
	while($line = $res->fetchrow())
		$loggedout[] = $line['userid'];

	if($loggedout){
		$auth->logout($loggedout);
	}

	// get stats from the masterdb
	$results = $masterdb->query("SELECT * FROM anonstats");
	$line = $results->fetchrow();
	foreach($line as $k => $v)
		$siteStatsMasterdb[$k] += $v;


	// query each user database for values held in their tables, and add the values onto the array
	$res = $usersdb->query("SELECT * FROM stats");
	while($line = $res->fetchrow())
		foreach($line as $k => $v)
			$siteStatsUserdb[$k] += $v;

	// now combine the two into one array, adding common values
	$siteStats = $siteStatsUserdb;
	$siteStats['hitsanon'] = $siteStatsMasterdb['hitsanon'];
	$siteStats['hitstotal'] += $siteStatsMasterdb['hitstotal'];

	//online
	$siteStats['onlineusers'] = $auth->getUserCount();
	$siteStats['onlineguests'] = $auth->getGuestCount();

	//update stats
	$siteStats['time'] = time();

	$parts = array();
	foreach($siteStats as $k => $v)
		$parts[] = $masterdb->prepare("$k = ?", $v);

	$masterdb->query("INSERT IGNORE INTO statshist SET " . implode(", ", $parts));

	return $siteStats;
}
/* END FUNCTION getStats */


/* FUNCTION updateUserBirthdays */

/* SYNOPSIS
 * This function updates the ages of all users on the site based on their b-days
 * as well as updating the bday table for each instance of the users database
 * END SYNOPSIS */

/* HISTORY
 * IncrDev May 29, 2006 by pdrapeau
 * END HISTORY */
function updateUserBirthdays() {
	global $usersdb, $db;

	$dbs = $usersdb->getSplitDBs();

	$db->query("TRUNCATE bday");

	foreach($dbs as $dbInstance) {

		$res = $dbInstance->query("SELECT userid, dob, age, sex FROM users");
		$updateages = array();

		while($line = $res->fetchrow()) {
			$age = getAge($line['dob']);
			if($age != $line['age']) {
				enqueue( "User", "birthday", $line['userid'], array($line['userid']) );
				if(!isset($updateages[$age]))
					$updateages[$age] = array();
				$updateages[$age][$line['userid']] = $line['sex'];
			}
		}

		$vals = array();
		foreach($updateages as $age => $uids)
			foreach($uids as $uid => $sex)
				$vals[] = $db->prepare("(#,#,?)", $uid, $age, $sex);

		if(count($vals)){
			$db->query("INSERT INTO bday (userid, age, sex) VALUES " . implode(',',$vals));
			foreach($updateages as $age => $uids)
				$dbInstance->prepare_query("UPDATE users SET age = # WHERE userid IN (#)", $age, array_keys($uids));
		}
	}
}
/* END FUNCTION updateUserBirthdays */


/* FUNCTION rebuildAgeSexGroups */

/* SYNOPSIS
 * This function re-builds the age sex groups table on an arbatrary database using the supplied table name
 * END SYNOPSIS */

/* HISTORY
 * Created May 29, 2006 by pdrapeau
 * END HISTORY */
function rebuildAgeSexGroups (
	&$db, 			// I: database object to perform operations on
	$tableName,		// I: name of agesexgroups table in database
	$ageMin,		// I: minimum age stats are kept for
	$ageMax			// I: maximum age stats are kept for
) {

	// figure out the number of entries we should have in each agesexgroups table (ages covered * 2)
	$expNumEntries = $ageMax - ($ageMin - 1);
	$expNumEntries = $expNumEntries * 2;

	// get the count of entries in each database, if theres a mismatch, truncate and re-build
	$result = $db->query("SELECT count(*) FROM $tableName");
	$numRows = $result->fetchfield();

	if ($numRows != $expNumEntries) {
		$db->query("TRUNCATE $tableName");
		for ($i = $ageMin; $i <= $ageMax; $i++) {
			$db->prepare_query("INSERT INTO $tableName (age, sex) VALUES (#,?)", $i, "Male");
			$db->prepare_query("INSERT INTO $tableName (age, sex) VALUES (#,?)", $i, "Female");
		}
	}

}
/* END FUNCTION rebuildAgeSexGroups */


/* FUNCTION rebuildLocInterestStats */

/* SYNOPSIS
 * This function re-builds the locstats and intereststats tables on an arbatrary database using the supplied table name
 * END SYNOPSIS */

/* HISTORY
 * Created May 29, 2006 by pdrapeau
 * END HISTORY */
function rebuildLocInterestStats (
	&$db, 			// I: database object to perform operations on
	$locStats,		// I: name of the locstats table in the database
	$interestStats	// I: name of the intereststats table in the database
) {
	global $configdb;

	// check if we need to re-build the locstats tables incase the locs table in the configdb changed
	$result = $configdb->query("SELECT count(*) FROM locs");
	$numLocs = $result->fetchfield();


	$result = $db->query("SELECT count(*) FROM $locStats");
	$dbNumLocs = $result->fetchfield();

	if ($dbNumLocs != $numLocs) {
		$db->query("TRUNCATE $locStats");
		$results = $configdb->query("SELECT id FROM locs");
		while ($row = $results->fetchrow())
			$db->prepare_query("INSERT INTO $locStats (id, users) VALUES (#,0)", $row['id']);
	}


	// check if we have to re-build the intereststats table
	$result = $configdb->query("SELECT count(*) FROM interests");
	$numInterests = $result->fetchfield();

	$result = $db->query("SELECT count(*) FROM $interestStats");
	$dbNumInterests = $result->fetchfield();
	if ($dbNumInterests != $numInterests) {
		$db->query("TRUNCATE $interestStats");
		$results = $configdb->query("SELECT id FROM interests");
		while ($row = $results->fetchrow())
			$db->prepare_query("INSERT INTO $interestStats (id, users) VALUES (#,0)", $row['id']);
	}

}
/* END FUNCTION rebuildLocInterestStats */


/* FUNCTION updateUserIndexes */

/* SYNOPSIS
 * This function re-builds the indexs used for the user
 * search functions in the site in each instance of the users database
 * as well as updating the required fields in the master database dictating where records are being currently
 * kept
 * END SYNOPSIS */

/* HISTORY
 * IncrDev May 29, 2006 by pdrapeau
 * END HISTORY */
function updateUserIndexes(){
	global $usersdb, $configdb, $masterdb, $cache;
	global $systemAgeMin, $systemAgeMax;

	// define the age ranges we are keeping stats for in the agesexgroups table
	$ageMin = $systemAgeMin;
	$ageMax = $systemAgeMax;

	$dbs = $usersdb->getSplitDBs();

	// first loop through each users database re-building the usersearch tables
	$insertId = 1;

	foreach($dbs as $serverid => $db) {
		$dbRecordCount = 0;
		$db->query("TRUNCATE usersearch");
		$db->prepare_query("ALTER TABLE usersearch auto_increment = #", $insertId);
		$result = $db->prepare_query("INSERT INTO usersearch (userid, age, sex, loc, active, pic, single, sexuality)
			SELECT userid, age, sex, loc, ( (activetime > #) + (online = 'y' && activetime > #) ) as active,
				( (firstpic >= 1) + (signpic = 'y') ) as pic, (single = 'y') as single, sexuality
				FROM users", time() - 86400*7, time() - 3600);

		$firstId = $insertId;
		$insertId += $result->affectedrows();
		$dbRecordCount += $result->affectedrows();

		if ($dbRecordCount > 0)
			$masterdb->prepare_query("REPLACE INTO usersearchidrange (serverid, startid, endid) VALUES (#,#,#)", $serverid + 1, $firstId, $insertId - 1);
		else
			$masterdb->prepare_query("REPLACE INTO usersearchidrange (serverid, startid, endid) VALUES (#,#,#)", $serverid + 1, 0, 0);
	}

	// now re-build the agesexgroups tables if needed
	foreach($dbs as $db)
		rebuildAgeSexGroups($db, "agesexgroups", $ageMin, $ageMax);

	// check if we need to re-build the locstats or interestats tables in each user database
	foreach($dbs as $db)
		rebuildLocInterestStats($db, "locstats", "intereststats");

	foreach($dbs as $serverid => $db){
		// now update the stats in the agesexgroup tables
		// all
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
		$db->query("UPDATE locstats, loccounts SET users = count WHERE id = loc");

		//interests
		$db->query("CREATE TEMPORARY TABLE interestcounts SELECT interestid, count(*) as count FROM userinterests GROUP BY interestid");
		$db->query("UPDATE intereststats, interestcounts SET users = count WHERE id = interestid");
	}

	// okay now that we have the values for each of the databases updated, we now need to update the values on the master
	// and insure that the tables are insync with whats defined for the system configuration, and than update values
	// first we need to figure out what the col names are in the masteragesexgroups table on the master database
	// we know the two keys will be age and sex, thus we can shift those cols off before building our table
	$statsTable = Array();
	$colNames = Array();
	$resultSet = $masterdb->query("SHOW COLUMNS FROM masteragesexgroups");
	while ($row = $resultSet->fetchrow()) {
		$colNames[] = $row['Field'];
	}

	array_shift($colNames);
	array_shift($colNames);

	// okay now build an array that mirrors the masteragesex table, with keys being the expected age ranges
	// and sex, than build keys under each of them for each col name in addition in the masteragesexgroups table
	// setting the initial value to 0, building this array before hand and updating is much faster than doing it
	// as we get values from the database
	for ($i = $ageMin; $i <= $ageMax; $i++) {
		$statsTable[$i]['Male'] = Array();
		$statsTable[$i]['Female'] = Array();
		foreach ($colNames as $key => $name) {
			$statsTable[$i]['Female'][$name] = 0;
			$statsTable[$i]['Male'][$name] = 0;
		}
	}



	// now query each database for results from the agesexgroups table, than update the numbers stored in the array
	// forming totals which span all user databases
	$result = $usersdb->query("SELECT * FROM agesexgroups");

	while($row = $result->fetchrow()) {
		foreach($row as $colName => $colValue) {
			if($colName != "age" && $colName != "sex") {
				$statsTable[$row['age']][$row['sex']][$colName] += $colValue;
			}
		}
	}

	// okay now that we grabbed all our values from the slaves, re-build the master database to match
	rebuildAgeSexGroups($masterdb, "masteragesexgroups", $ageMin, $ageMax);

	// now that the table is insync record wise, we can simply push the updates we need to against it
	foreach ($statsTable as $age => $ageData) {
		foreach ($ageData as $sex => $sexData) {
			$setValues = Array();
			foreach ($sexData as $colName => $colValue) {
				$setValues[] = $masterdb->prepare("$colName = #", $colValue);
			}
			$masterdb->query("UPDATE masteragesexgroups SET " . implode(", ", $setValues) . $masterdb->prepare(" WHERE age = # AND sex = ?", $age, $sex));
		}
	}

	unset($statsTable);

	// remove the cached values for usersByAge
	$cache->remove("usersByAge");

	$result = $configdb->query("SELECT count(*) FROM interests");
	$numInterests = $result->fetchfield();

	// init array for locs
	$locStatsTable = Array();
	$results = $configdb->query("SELECT id FROM locs");
	while ($row = $results->fetchrow())
		$locStatsTable[$row['id']] = 0;

	// init array for interests
	$interestStatsTable = Array();
	$results = $configdb->query("SELECT id FROM interests");
	while ($row = $results->fetchrow())
		$interestStatsTable[$row['id']] = 0;


	// okay next we need to get the locstats and intereststats from each database
	$result = $usersdb->query("SELECT * FROM locstats");

	while ($row = $result->fetchrow())
		$locStatsTable[$row['id']] += $row['users'];


	$result = $usersdb->query("SELECT * FROM intereststats");

	while ($row = $result->fetchrow())
		$interestStatsTable[$row['id']] += $row['users'];

	// okay now re-build the master tables and than update the values in the table
	rebuildLocInterestStats($masterdb, "masterlocstats", "masterintereststats");

	foreach ($locStatsTable as $id => $users) {
		$masterdb->prepare_query("UPDATE masterlocstats SET users=# WHERE id=#", $users, $id);
	}

	foreach ($interestStatsTable as $id => $users) {
		$masterdb->prepare_query("UPDATE masterintereststats SET users=# WHERE id=#", $users, $id);
	}

	// remove the values being held in cache for both
	$cache->remove("usersInLocs");
	foreach ($locStatsTable as $id => $users)
		$cache->remove("interestnum-$id");

	// remove any other cached values associated with user search functions
	$cache->remove("usersearch-table-indexes");
	$cache->remove("usersearch-id-ranges");
}
/* END FUNCTION updateUserIndexes */


/* FUNCTION updateSpotlightList */

/* SYNOPSIS
 * This function updates the local spotlight lists for each instance of the users database
 * END SYNOPSIS */

/* HISTORY
 * IncrDev May 29, 2006 by pdrapeau
 * END HISTORY */
function updateSpotlightList(){
	global $usersdb, $cache, $db;

	$result = $usersdb->prepare_query("SELECT userid FROM users WHERE spotlight = 'y' && premiumexpiry > # && firstpic > 0 && state='active'", time());

	$db->query("TRUNCATE TABLE spotlight");

	$spotlightmax = 0;

	while($row = $result->fetchrow()){
		$db->prepare_query("INSERT INTO spotlight (userid) VALUES (#)", $row['userid']);
		$spotlightmax++;
	}

	$cache->put("spotlightmax", $spotlightmax, 86400);
}
/* END FUNCTION updateSpotlightList */


/* FUNCTION getUsersByAge */

/* SYNOPSIS
 * This function is the callback function used in getNumUsersInAgeSexCol, its called when the stats data for users
 * in a specific loc isn't avaliable in the cache, and thus is used when we must fall back to calling the database
 * END SYNOPSIS */

/* HISTORY
 * IncrDev May 29, 2006 by pdrapeau
 * END HISTORY */
function getUsersByAge(){
	global $masterdb;

	$res = $masterdb->query("SELECT * FROM masteragesexgroups ORDER BY age");

	$users = array();
	while($line = $res->fetchrow())
		$users[$line['age']][$line['sex']] = $line;

	return $users;
}
/* END FUNCTION getUsersByAge */


/* FUNCTION getNumUsersInAgeSexCol */

/* SYNOPSIS
 * This function is used to retreive stats data for users based on an age range, and their sex
 * END SYNOPSIS */

/* HISTORY
 * IncrDev May 29, 2006 by pdrapeau
 * END HISTORY */
function getNumUsersInAgeSexCol($sexes, $minage, $maxage, $col){
	global $cache;

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
/* END FUNCTION getNumUsersInAgeSexCol */


/* FUNCTION getNumUsersInLocs */

/* SYNOPSIS
 * This function is used to retreive stats data for users based on their user location
 * END SYNOPSIS */

/* HISTORY
 * IncrDev May 29, 2006 by pdrapeau
 * END HISTORY */
function getNumUsersInLocs($locs){
	global $cache, $masterdb;

	$users = $cache->get("usersInLocs");

	if(!$users){
		$res = $masterdb->query("SELECT id, users FROM masterlocstats");

		$users = array();
		while($line = $res->fetchrow())
			$users[$line['id']] = $line['users'];

		$cache->put("usersInLocs", $users, 3600*6);
	}

	$total = 0;
	foreach($locs as $loc)
		if (isset($users[$loc]))
			$total += $users[$loc];

	return $total;
}
/* END FUNCTION getNumUsersInLocs */


/* FUNCTION getNumUsersByInterests */

/* SYNOPSIS
 * This function gets the number of users by interests or multiple interests
 * END SYNOPSIS */

/* HISTORY
 * Created June 02, 2006 by pdrapeau
 * END HISTORY */
function getNumUsersByInterests($interestIds){
	global $cache, $masterdb;

	// check cache for totals
	$interestTotals = Array();
	$interestTotals['total'] = 0;

	foreach ($interestIds as $key => $value) {
		$interestTotal = $cache->get("interestnum-$value");
		if ($interestTotal != "") {
			$interestTotals[$value] = $interestTotal;
			$interestTotals['total'] += $interestTotal;
		}
	}

	// get values that are not cached from the database, and set them in memcache if there are any
	$notCached = array_diff($interestIds, array_keys($interestTotals));
	if (count($notCached) > 0) {
		$resultSet = $masterdb->prepare_query("SELECT id, users FROM masterintereststats WHERE id IN (#)", $notCached);
		while ($row = $resultSet->fetchrow()) {
			$cache->put("interestnum-$row[id]", $row['users'], 86400);
			$interestTotals[$row['id']] = $row['users'];
			$interestTotals['total'] += $row['users'];
		}
	}

	return $interestTotals;
}
/* END FUNCTION getNumUsersByInterests */


function dumpActiveAccountStats(){
	global $usersdb, $masterdb;

	$activetimes = array(
	             "day" => 86400,
	             "3days" => 86400*3,
	             "week" => 86400*7,
	             "2weeks" => 86400*14,
	             "month" => 86400*30,
	             "2months" => 86400*60,
	             "3months" => 86400*90,
	             "6months" => 86400*180,
	             "year" => 86400*365,
	            );

	$time = time();

	$times = array();

	$query = "INSERT INTO statsactiveaccountshist SET ";

	foreach($activetimes as $name => $period){
		$res = $usersdb->prepare_query("SELECT count(*) AS count FROM users WHERE activetime >= #", ($time - $period));
		$count = 0;
		while($line = $res->fetchrow())
			$count += $line['count'];

		$query .= "$name = $count, ";
	}

	$res = $usersdb->query("SELECT count(*) AS count FROM users WHERE state = 'active'");
	$activated = 0;
	while($line = $res->fetchrow())
		$activated += $line['count'];

	$query .= "activated = $activated, ";


	$res = $usersdb->query("SELECT count(*) AS count FROM users");
	$total = 0;
	while($line = $res->fetchrow())
		$total += $line['count'];

	$query .= "total = $total, ";


	$query .= "time = $time";

	$masterdb->query($query);
}

/* END MODULE stats.php */
