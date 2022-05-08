<?

	$login=2;

	require_once("include/general.lib.php");

	$isAdmin = $mods->isAdmin($userData['userid'],'viewrecentvisitors');

	if(!$isAdmin || !($uid = getREQval('uid', 'int')))
		$uid = $userData['userid'];
	else
		$mods->adminlog("recent visitors","View Recent Visitors to user: $uid");

	$page = getREQval('page', 'int');

/*
	$db->prepare_query("SELECT SQL_CALC_FOUND_ROWS profileviews.viewuserid, users.username, users.anonymousviews, users.premiumexpiry, users.age, users.sex, users.loc, profileviews.time, users.online, myviews.time as mytime FROM profileviews USE  INDEX (userid), users LEFT JOIN profileviews AS myviews ON profileviews.viewuserid=myviews.userid && myviews.viewuserid = # WHERE users.userid=profileviews.viewuserid && profileviews.userid = # ORDER BY time DESC LIMIT " . ($page*$config['linesPerPage']) . ", $config[linesPerPage]", $uid, $uid);

	$users = array();
	while($line = $db->fetchrow())
		$users[] = $line;

	$db->query("SELECT FOUND_ROWS()");
	$numrows = $db->fetchfield();
	$numpages =  ceil($numrows / $config['linesPerPage']);
//*/

/*
	$db->prepare_query("SELECT SQL_CALC_FOUND_ROWS viewuserid, time FROM profileviews USE  INDEX (userid) WHERE userid = # ORDER BY time DESC LIMIT " . ($page*$config['linesPerPage']) . ", $config[linesPerPage]", $uid);

	$users = array();
	while($line = $db->fetchrow())
		$users[$line['viewuserid']] = $line;

	$db->query("SELECT FOUND_ROWS()");
	$numrows = $db->fetchfield();
	$numpages = ceil($numrows / $config['linesPerPage']);

	if(count($users)){
		$db->prepare_query("SELECT users.userid, users.username, users.anonymousviews, users.premiumexpiry, users.age, users.sex, users.loc, users.online, myviews.time as mytime FROM users LEFT JOIN profileviews AS myviews ON users.userid=myviews.userid && myviews.viewuserid = # WHERE users.userid IN (#)", $uid, array_keys($users));

		while($line = $db->fetchrow())
			$users[$line['userid']] += $line;

	}
//*/

//*
//split, load balanced solution
	$profviewsdb->prepare_query($uid, "SELECT SQL_CALC_FOUND_ROWS viewuserid, time FROM profileviews WHERE userid = # ORDER BY time DESC LIMIT " . ($page*$config['linesPerPage']) . ", $config[linesPerPage]", $uid);

	$users = array();
	while($line = $profviewsdb->fetchrow())
		$users[$line['viewuserid']] = $line;

	$profviewsdb->query("SELECT FOUND_ROWS()");
	$numrows = $profviewsdb->fetchfield();
	$numpages = ceil($numrows / $config['linesPerPage']);

	if(count($users)){
		$db->prepare_query("SELECT userid, username, anonymousviews, premiumexpiry, age, sex, loc, online, 0 as mytime FROM users WHERE userid IN (#)", array_keys($users));

		while($line = $db->fetchrow())
			$users[$line['userid']] += $line;


		$profviewsdb->prepare_query(array_keys($users), "SELECT userid, time as mytime FROM profileviews WHERE viewuserid = # && userid IN (#)", $uid, array_keys($users));

		while($line = $profviewsdb->fetchrow())
			$users[$line['userid']]['mytime'] = $line['mytime'];
	}

	$profviewsdb->close();

//*/

	incHeader();

	echo "<table align=center>";
	echo "<tr>";
	echo "<td class=header>Username</td>";
	echo "<td class=header>Age</td>";
	echo "<td class=header>Sex</td>";
	echo "<td class=header>Location</td>";
	echo "<td class=header>User Online</td>";
	echo "<td class=header>Their last visit</td>";
	echo "<td class=header>Your last visit this week</td>";
	echo "</tr>";

	$locations = & new category( $db, "locs");

	$time = time();

	$classes = array('body','body2');
	$i=1;

	foreach($users as $user){
		if(!isset($user['userid']))
			continue;

		$i = !$i;
		echo "<tr>";

		if($user['anonymousviews']=='y' && $user['premiumexpiry'] > $time && !$isAdmin){
			echo "<td class=$classes[$i]>Anonymous</td><td class=$classes[$i] colspan=4></td>";
		}else{
			echo "<td class=$classes[$i]><a class=body href=/profile.php?uid=$user[viewuserid]>$user[username]</a></td>";
			echo "<td class=$classes[$i]>$user[age]</td>";
			echo "<td class=$classes[$i]>$user[sex]</td>";
			echo "<td class=$classes[$i]>" . $locations->getCatName($user['loc']) . "</td>";
			echo "<td class=$classes[$i] align=center>" . ($user['online']=='y' ? "Online" : "" ) . "</td>";
		}

		echo "<td class=$classes[$i]>" . userdate("M j, Y g:i:s a", $user['time']) . " &nbsp; &nbsp;</td>";
		echo "<td class=$classes[$i]>";
		if((!($user['anonymousviews']=='y' && $user['premiumexpiry'] > $time) || $isAdmin) && $user['mytime'])
			echo userdate("M j, Y g:i:s a", $user['mytime']);
		echo "</td>";
		echo "</tr>";
	}
	echo "<tr><td class=header colspan=5>$numrows unique visitors in the past week</td>";
	echo "<td class=header align=right colspan=2>";
	echo "Page: " . pageList("$_SERVER[PHP_SELF]?uid=$uid",$page,$numpages,'header');
	echo "</td></tr>";
	echo "</table>";

	incFooter();

