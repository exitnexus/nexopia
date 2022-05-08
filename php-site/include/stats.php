<?
function updateStats(){
	if(defined("STATSUPDATED"))
		return;
	define("STATSUPDATED", true);

	global $userData, $config, $sWidth, $sHeight, $siteStats, $db, $fastdb, $cache;

	if((ereg("Nav", getenv("HTTP_USER_AGENT"))) || (ereg("Gold", getenv("HTTP_USER_AGENT"))) || (ereg("X11", getenv("HTTP_USER_AGENT"))) || (ereg("Mozilla", getenv("HTTP_USER_AGENT"))) || (ereg("Netscape", getenv("HTTP_USER_AGENT"))) AND (!ereg("MSIE", getenv("HTTP_USER_AGENT")))) $browser = "Netscape";
	elseif(ereg("MSIE", getenv("HTTP_USER_AGENT"))) $browser = "MSIE";
	elseif(ereg("Lynx", getenv("HTTP_USER_AGENT"))) $browser = "Lynx";
	elseif(ereg("Opera", getenv("HTTP_USER_AGENT"))) $browser = "Opera";
	elseif(ereg("WebTV", getenv("HTTP_USER_AGENT"))) $browser = "WebTV";
	elseif(ereg("Konqueror", getenv("HTTP_USER_AGENT"))) $browser = "Konqueror";
	elseif((eregi("bot", getenv("HTTP_USER_AGENT"))) || (ereg("Google", getenv("HTTP_USER_AGENT"))) || (ereg("Slurp", getenv("HTTP_USER_AGENT"))) || (ereg("Scooter", getenv("HTTP_USER_AGENT"))) || (eregi("Spider", getenv("HTTP_USER_AGENT"))) || (eregi("Infoseek", getenv("HTTP_USER_AGENT")))) $browser = "Bot";
	else $browser = "Other";

	if(ereg("Win", getenv("HTTP_USER_AGENT"))) $os = "Windows";
	elseif((ereg("Mac", getenv("HTTP_USER_AGENT"))) || (ereg("PPC", getenv("HTTP_USER_AGENT")))) $os = "Mac";
	elseif(ereg("Linux", getenv("HTTP_USER_AGENT"))) $os = "Linux";
	elseif(ereg("FreeBSD", getenv("HTTP_USER_AGENT"))) $os = "FreeBSD";
	elseif(ereg("SunOS", getenv("HTTP_USER_AGENT"))) $os = "SunOS";
	elseif(ereg("IRIX", getenv("HTTP_USER_AGENT"))) $os = "IRIX";
	elseif(ereg("BeOS", getenv("HTTP_USER_AGENT"))) $os = "BeOS";
	elseif(ereg("OS/2", getenv("HTTP_USER_AGENT"))) $os = "OS/2";
	elseif(ereg("AIX", getenv("HTTP_USER_AGENT"))) $os = "AIX";
	else $os = "Other";

	function getUsersOnline(){
		global $db, $fastdb, $config;

		$time = time();

		$fastdb->prepare_query("SELECT userid FROM useractivetime WHERE online = 'y' && activetime <= ?", ($time-$config['friendAwayTime']) );

		if($fastdb->numrows()){
			$loggedout = array();
			while($line = $fastdb->fetchrow())
				$loggedout[] = $line['userid'];

			$fastdb->prepare_query("UPDATE useractivetime SET online = 'n' WHERE userid IN (?)", $loggedout);
			$db->prepare_query("UPDATE users SET online = 'n' WHERE userid IN (?)", $loggedout);
			$fastdb->prepare_query("UPDATE useractivetime SET online = 'n' WHERE userid IN (?)", $loggedout);
		}


		$online = array();
		$fastdb->query("SELECT count(*) FROM useractivetime WHERE online = 'y'");
		$online['online'] = $fastdb->fetchfield();

		$fastdb->prepare_query("SELECT count(*) FROM sessions WHERE userid IS NULL && activetime >= ?", ($time-$config['friendAwayTime']) );
		$online['guests'] = $fastdb->fetchfield();
		return $online;
	}

	$cache->prime(array("stats","online"));

	$online = $cache->get('online',15,'getUsersOnline',array('online'=> 0, 'guests' => 0));

	$siteStats['online'] = $online['online'];
	$siteStats['guests'] = $online['guests'];

	function getStats(){
		global $fastdb;

		$fastdb->query("SELECT count,var,type FROM stats WHERE (type='hits' && var='total') || (type='users' && var='maxonline') || (type='users' && var='total') || (type='users' && var='maxid') || (type='users' && var='userswithpics') || (type='config' && var='userclusters') || (type='config' && var='picgroups') || (type='config' && var='userswsignpics')");

		$siteStats = array();

		while($line = $fastdb->fetchrow()){
			if($line['type']=='hits' && $line['var'] == 'total')
				$siteStats['hits'] = $line['count'];
			if($line['type']=='users' && $line['var'] == 'maxonline')
				$siteStats['maxonline'] = $line['count'];
			if($line['type']=='users' && $line['var'] == 'total')
				$siteStats['totalusers'] = $line['count'];
			if($line['type']=='users' && $line['var'] == 'maxid')
				$siteStats['maxuserid'] = $line['count'];
			if($line['type']=='users' && $line['var'] == 'userswithpics')
				$siteStats['userswithpics'] = $line['count'];
			if($line['type']=='config' && $line['var'] == 'userclusters')
				$siteStats['userclusters'] = $line['count'];
			if($line['type']=='config' && $line['var'] == 'picgroups')
				$siteStats['picgroups'] = $line['count'];
			if($line['type']=='config' && $line['var'] == 'userswsignpics')
				$siteStats['userswsignpics'] = $line['count'];
		}
		return $siteStats;
	}

	$line = $cache->get('stats',30,'getStats');

	$siteStats = array_merge($siteStats, $line);

	if($userData['loggedIn']){
		if($userData['showrightblocks'] == 'y'){
			$db->prepare_query("SELECT friendid,username FROM friends,users WHERE friends.userid = ? && friendid=users.userid && online='y'", $userData['userid'] );
			$online = array();
			while($line = $db->fetchrow())
				$online[$line['friendid']] = $line['username'];
			$userData['friends'] = $online;
			$userData['friendsonline'] = $siteStats['friends'] = count($online);
		}else{
			$db->prepare_query("SELECT count(*) FROM friends,users WHERE friends.userid = ? && friendid=users.userid && online='y'", $userData['userid'] );
			$userData['friendsonline'] = $siteStats['friends'] = $db->fetchfield();
		}

		$siteStats['newmsgs'] = $userData['newmsgs'];
	}

	$query = "UPDATE stats SET count=count+1 WHERE (type='hits' && var='total')";
		$query .= " || (type='browser' && var='$browser')";
		$query .= " || (type='os' && var='$os' )";
	if(isset($sWidth) && isset($sHeight))
		$query .= " || (type='screen' && var = '" . $db->escape($sWidth) . " x " . $db->escape($sHeight) . "')";
	if($userData['loggedIn']){
		$query .= " || (type='hits' && var='user')";
		$query .= " || (type='hits' && var='$userData[sex]')";
	}else $query .= " || (type='hits' && var='anon')";
	$fastdb->query($query);

	if($siteStats['maxonline'] > 0 && $siteStats['online']>0 && $siteStats['maxonline'] < $siteStats['online']){
		$fastdb->prepare_query("UPDATE stats SET count = GREATEST(count, ?) WHERE type='users' && var='maxonline'", $siteStats['online']);
	}

	$ip = ip2int(getip());

	$query = "UPDATE iplog SET time = '" . time() . "', hits = hits+1" . ($userData['loggedIn'] ? ", userid='$userData[userid]'" : "" ) . " WHERE ip='$ip'";
	$fastdb->query($query);
	if($fastdb->affectedrows()==0){
		$query = "INSERT IGNORE INTO iplog SET time = '" . time() . "', hits = 1," . ($userData['loggedIn'] ? " userid='$userData[userid]'," : "" ) . " ip='$ip'";
		$fastdb->query($query);
	}
}

function updateUserGroups(){
	global $db, $config, $fastdb, $cache;

	$db->query("LOCK TABLES users WRITE, userclusters WRITE");

	$query = "SELECT userid,sex,dob,age,single,signpic FROM users ORDER BY userid ASC";
	$result = $db->unbuffered_query($query);

	$ages = array_flip(range(10,80));

	$updateages = array();

	$groups = array();

	$signpics = 0;

	$start=1;
	$groupid=0;
	$i = 0;
	while($line = $db->fetchrow($result)){
		$i++;

		if($line['signpic'] == 'y')
			$signpics++;

		$age = getAge($line['dob']);
		if($age != $line['age']){
			if(!isset($updateages[$age]))
				$updateages[$age] = array();
			$updateages[$age][] = $line['userid'];
		}

		if(!is_array($ages[$age]))
			$ages[$age] = array('Male' => 0, 'Female' => 0, 'singleMale' => 0, 'singleFemale' => 0);
		$ages[$age][$line['sex']]++;
		if($line['single']=='y')
			$ages[$age]["single$line[sex]"]++;

		if($i==$config['userclustersize']){
			$groupid++;

			$groups[$groupid] = array('start' => $start, 'end' => $line['userid']);

			$i=0;
			$start = $line['userid']+1;
		}
	}

	if($i < $config['userclustersize']/2){
		if($groupid==0){
			$groupid=1;
			$groups[$groupid] = array('start' => 0, 'end' => 0);
		}else{
			$groups[$groupid]['end']=0;
		}
	}else{
		$groupid++;
		$groups[$groupid] = array('start' => $start, 'end' => 0);
	}

	$db->query("SELECT count(*) as count FROM userclusters");
	$line = $db->fetchrow();

	$numgroups = $line['count'];

	foreach($groups as $groupid => $group){
		if($groupid <= $numgroups)
			$db->query("UPDATE userclusters SET start='$group[start]', end='$group[end]' WHERE id='$groupid'");
		else
			$db->query("INSERT INTO userclusters SET start='$group[start]', end='$group[end]', id='$groupid'");
	}

	if($numgroups > $groupid)
		$db->prepare_query("DELETE FROM userclusters WHERE id > ?", count($groups) );

	$fastdb->prepare_query("UPDATE stats SET count = ? WHERE type='config' && var='userclusters'", count($groups) );
	$fastdb->prepare_query("UPDATE stats SET count = ? WHERE type='config' && var='userswsignpics'", $signpics );

	$db->query("UNLOCK TABLES");

	foreach($ages as $age => $sexs){
		if(!is_array($sexs))
			$sexs = array('Male' => 0, 'Female' => 0, 'singleMale' => 0, 'singleFemale' => 0);
		$db->prepare_query("UPDATE agegroups SET Male = ?, Female = ?, singleMale = ?, singleFemale = ? WHERE age = ?", $sexs['Male'], $sexs['Female'], $sexs['singleMale'], $sexs['singleFemale'], $age);
	}

	$db->query("TRUNCATE bday");

	$vals = array();
	foreach($updateages as $age => $uids)
		foreach($uids as $uid)
			$vals[] = "($uid, $age)";

	$db->query("INSERT INTO bday (userid, age) VALUES " . implode(',',$vals));


	$db->query("UPDATE users, bday SET users.age = bday.age WHERE users.userid = bday.age");
	$db->query("UPDATE pics, bday SET pics.age = bday.age WHERE pics.itemid = bday.age");

/*
	foreach($updateages as $age => $uids){
		$db->prepare_query("UPDATE users SET age = ? WHERE userid IN (?)", $age, $uids);
		$db->prepare_query("UPDATE pics SET age = ? WHERE itemid IN (?)", $age, $uids);
	}
*/
	$cache->resetFlag("usersByAge");
	$cache->resetFlag('userclusters');
}

function updateUserLocs(){
	global $db, $cache;

	$db->query("LOCK TABLES users WRITE, locs WRITE");

	$result = $db->query("SELECT count(*) as count,loc FROM users GROUP BY loc");

	$db->query("UPDATE locs SET users = 0");

	while($line = $db->fetchrow($result))
		$db->query("UPDATE locs SET users = $line[count] WHERE id = $line[loc]");

	$db->query("UNLOCK TABLES");

	$cache->resetFlag("usersInLocs");
}

function updatePicGroups(){
	global $db, $config,$fastdb, $cache;

	$db->query("LOCK TABLES pics WRITE, picgroups WRITE, agegroups WRITE");

	$result = $db->unbuffered_query("SELECT id,age,sex FROM pics ORDER BY id ASC");

	$groups = array();
	$ages = array();

	$start=1;
	$groupid=0;
	$i = 0;
	while($line = $db->fetchrow($result)){
		$i++;

		if(!isset($ages[$line['age']]))
			$ages[$line['age']] = array('Male' => 0, 'Female' => 0);
		$ages[$line['age']][$line['sex']]++;

		if($i==$config['picgroupsize']){
			$groupid++;

			$groups[$groupid] = array('start' => $start, 'end' => $line['id']);

			$i=0;
			$start = $line['id']+1;
		}
	}

	foreach($ages as $age => $sexs){
		if(!is_array($sexs))
			$sexs = array('Male' => 0, 'Female' => 0);
		$db->prepare_query("UPDATE agegroups SET picsMale = ?, picsFemale = ? WHERE age = ?", $sexs['Male'], $sexs['Female'], $age);
	}

	if($i < $config['picgroupsize']/2){
		if($groupid==0){
			$groupid=1;
			$groups[$groupid] = array('start' => 0, 'end' => 0);
		}else{
			$groups[$groupid]['end']=0;
		}
	}else{
		$groupid++;
		$groups[$groupid] = array('start' => $start, 'end' => 0);
	}

	$db->query("SELECT count(*) as count FROM picgroups");
	$line = $db->fetchrow();

	$numgroups = $line['count'];

	foreach($groups as $groupid => $group){
		if($groupid <= $numgroups)
			$db->query("UPDATE picgroups SET start='$group[start]', end='$group[end]' WHERE id='$groupid'");
		else
			$db->query("INSERT INTO picgroups SET start='$group[start]', end='$group[end]', id='$groupid'");
	}

	if($numgroups > $groupid)
		$db->prepare_query("DELETE FROM picgroups WHERE id > ?", count($groups));

	$fastdb->prepare_query("UPDATE stats SET count = ? WHERE type='config' && var='picgroups'", count($groups) );

	$db->query("UNLOCK TABLES");

	$cache->resetFlag('picclusters');
}
/*
function setTopLists(){
	global $db, $config,$fastdb;

	$db->prepare_query("SELECT id,score FROM pics WHERE vote='y' && sex = 'Female' && votes >= ? ORDER  BY score DESC LIMIT 1000", $config['minVotesTop10']);

	$score = 0;
	$top = array();
	while($line = $db->fetchrow()){
		$top[] = $line['id'];
		$score = $line['score'];
	}

	$fastdb->prepare_query("UPDATE stats SET type='config', count = ? WHERE var='minScoreTop10Female'", floor($score));

	$db->prepare_query("SELECT id,score FROM pics WHERE vote='y' && sex = 'Male' && votes >= ? ORDER  BY score DESC LIMIT 1000", $config['minVotesTop10']);

	$score = 0;
	while($line = $db->fetchrow()){
		$top[] = $line['id'];
		$score = $line['score'];
	}

	$fastdb->prepare_query("UPDATE stats SET type='config', count = ? WHERE var='minScoreTop10Male'", floor($score));

	$db->query("LOCK TABLES pics WRITE");

	$db->query("UPDATE pics SET top='n' WHERE top='y'");

	$db->prepare_query("UPDATE pics SET top='y' WHERE id IN (?)", $top);

	$db->query("UNLOCK TABLES");
}
*/
function setTopLists(){
	global $db, $config, $fastdb, $cache;

	$sexes = array("Male","Female");
	$ages = range(14,60);

	$db->prepare_query("SELECT age, sex, MIN(score) AS score FROM picstop GROUP BY age, sex");

	$minscores = array();

	while($line = $db->fetchrow())
		$minscores[$line['age']][$line['sex']] = $line['score'];

	$usersByAge = $cache->hdget("usersByAge",'getUsersByAge');

	foreach($ages as $age){
		if(!isset($minscores[$age]))
			$minscores[$age] = array("Male" => 0, "Female" => 0);
		foreach($sexes as $sex){
			$num = round($usersByAge[$age]["pics$sex"] / 30);
			if($num > 100)		$num = 100;
			if($num < 10)		$num = 10;

			if(!isset($minscores[$age][$sex]))
				$minscores[$age][$sex] = 0;

			$db->prepare_query("DELETE FROM picstop WHERE age = ? && sex = ?", $age, $sex);

			$db->prepare_query("INSERT IGNORE INTO picstop SELECT id,pics.itemid,username,pics.age,pics.sex,score FROM pics,users WHERE pics.itemid=users.userid && pics.age = ? && pics.sex = ? && vote = 'y' && votes >= ? && score >= ? ORDER BY score DESC LIMIT $num", $age, $sex, $config['minVotesTop10'], ($minscores[$age][$sex] - 0.1) );
		}
	}
}

function getSexFactor(){
	global $db;

	$db->prepare_query("SELECT sexuality, count(*) as count FROM users GROUP BY sexuality");

	$rows = array();
	$total = 0.0;

	while ($line = $db->fetchrow()){
		$rows[$line['sexuality']] = $line['count'];
		$total += $line['count'];
	}

	foreach($rows as $k => $count)
		$rows[$k] = ($count / $total);

	return $rows;
}

function getSexualityFactor($sexuality){
	global $cache;

	$rows = $cache->get("sexfactor", 86400, 'getSexFactor');

	return $rows[$sexuality];
}

function getUsersByAge(){
	global $db;

	$db->query("SELECT * FROM agegroups ORDER BY age");

	$usersByAge = array();
	while($line = $db->fetchrow())
		$usersByAge[$line['age']] = $line;

	return $usersByAge;
}

function getNumUsersInAge($sexes, $min,$max){
	global $cache;

	if(!is_array($sexes))
		$sexes = array($sexes);

	$usersByAge = $cache->hdget("usersByAge",'getUsersByAge');

	$total = 0;
	for($age = $min; $age <= $max; $age++)
		foreach($sexes as $sex)
			$total += $usersByAge[$age][$sex];

	return $total;
}

function getUsersInLocs(){
	global $db;

	$db->query("SELECT id,users FROM locs");

	$usersByLoc = array();
	while($line = $db->fetchrow())
		$usersByLoc[$line['id']] = $line['users'];

	return $usersByLoc;
}

function getNumUsersInLocs($locs){
	global $cache;

	$usersByLoc = $cache->hdget("usersInLocs",'getUsersInLocs');

	$total = 0;
	foreach($locs as $loc)
		$total += $usersByLoc[$loc];

	return $total;
}

function getUserClusters(){
	global $db;

	$db->prepare_query("SELECT id,start,end FROM userclusters");

	$userclusters = array();
	while($line = $db->fetchrow())
		$userclusters[$line['id']] = $line;

	return $userclusters;
}

function getUserClusterUserid($min,$max){
	global $cache;

	$userclusters = $cache->hdget('userclusters','getUserClusters');

	return array('minuserid' => $userclusters[$min]['start'],'maxuserid' => $userclusters[$max]['end']);
}

function getPicClusters(){
	global $db;

	$db->query("SELECT id,start,end FROM picgroups");

	$picclusters = array();
	while($line = $db->fetchrow())
		$picclusters[$line['id']] = $line;

	return $picclusters;
}

function getPicClusterpicid($min,$max){
	global $cache;

	$picclusters = $cache->hdget('picclusters','getPicClusters');

	return array('minpicid' => $picclusters[$min]['start'],'maxpicid' => $picclusters[$max]['end']);
}

