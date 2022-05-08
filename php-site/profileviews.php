<?

	$login=2;

	require_once("include/general.lib.php");

	$isAdmin = $mods->isAdmin($userData['userid'],'viewrecentvisitors');

	if(empty($uid) || !$isAdmin)
		$uid = $userData['userid'];
	else
		$mods->adminlog("recent visitors","View Recent Visitors to user: $uid");

	if(empty($page))
		$page = 0;

	$query = $db->prepare("SELECT SQL_CALC_FOUND_ROWS profileviews.viewuserid, users.username, users.anonymousviews, users.age, users.sex, profileviews.time, profileviews.hits, users.online, myviews.time as mytime FROM profileviews, users LEFT JOIN profileviews AS myviews ON profileviews.viewuserid=myviews.userid && myviews.viewuserid = ? WHERE users.userid=profileviews.viewuserid && profileviews.userid = ? ORDER BY time DESC LIMIT " . ($page*$config['linesPerPage']) . ", $config[linesPerPage]", $uid, $uid);
	$db->query($query);

	$users = array();
	while($line = $db->fetchrow())
		$users[] = $line;

	$rowresult = $db->query("SELECT FOUND_ROWS()");
	$numrows = $db->fetchfield(0,0,$rowresult);
	$numpages =  ceil($numrows / $config['linesPerPage']);

	incHeader();

	echo "<table align=center>";
	echo "<tr>";
	echo "<td class=header>Username</td>";
	echo "<td class=header>Age</td>";
	echo "<td class=header>Sex</td>";
	echo "<td class=header>User Online</td>";
	echo "<td class=header>Their last visit</td>";
	echo "<td class=header>Your last visit this week</td>";
		echo "</tr>";

	foreach($users as $user){
		echo "<tr>";

		if($user['anonymousviews']=='y' && !$isAdmin){
			echo "<td class=body>Anonymous</td><td class=body></td><td class=body></td><td class=body></td>";
		}else{
			echo "<td class=body><a class=body href=/profile.php?uid=$user[viewuserid]>$user[username]</a></td>";
			echo "<td class=body>$user[age]</td>";
			echo "<td class=body>$user[sex]</td>";
			echo "<td class=body align=center>" . ($user['online']=='y' ? "Online" : "" ) . "</td>";
		}

		echo "<td class=body>" . userdate("M j, Y g:i:s a", $user['time']) . " &nbsp; &nbsp;</td>";
		echo "<td class=body>";
		if(($user['anonymousviews']!='y' || $isAdmin) && $user['mytime'])
			echo userdate("M j, Y g:i:s a", $user['mytime']);
		echo "</td>";
		echo "</tr>";
	}
	echo "<tr><td class=header colspan=4>$numrows unique visitors in the past week</td>";
	echo "<td class=header align=right colspan=2>";
	echo "Page: " . pageList("$PHP_SELF?uid=$uid",$page,$numpages,'header');
	echo "</td></tr>";
	echo "</table>";

	incFooter();
