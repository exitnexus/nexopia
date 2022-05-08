<?

	$login=1;

	require_once("include/general.lib.php");

	$mod = $mods->isAdmin($userData['userid'],"forums");

	$forums->db->prepare_query("SELECT forummods.userid, forums.name as forumname, forummods.forumid, forummods.activetime FROM forummods LEFT JOIN forums ON forummods.forumid=forums.id WHERE (forums.official='y' || forummods.forumid = 0)");

	$users = array();
	$forummods = array();
	while($line = $forums->db->fetchrow()){
		$forummods[] = $line;
		if($line['userid'] == $userData['userid'])
			$mod = true;
		$users[$line['userid']] = $line['userid'];
	}

	if(!$mod)
		die("You don't have permission to see this");

	$db->prepare_query("SELECT userid, username, online, activetime FROM users WHERE userid IN (?)", $users);

	while($line = $db->fetchrow())
		$users[$line['userid']] = $line;

/*
	if(empty($sortn))
		$sortn = "";

	$sortd = SORT_ASC;
	$sortt = SORT_CASESTR;
	switch($sortn){
		case 'forumname':
		case 'username':
			break;
		case 'activetime':
			$sortt = SORT_NUMERIC;
			$sortd = SORT_DESC;
			break;
		case 'online':
			$sortt = SORT_STRING;
			$sortd = SORT_DESC;
			break;
		default:
			$sortn = 'forumname';
			break;
	}
*/
	sortCols($forummods, SORT_ASC, SORT_CASESTR, 'forumname');//, SORT_ASC, SORT_CASESTR, 'username', $sortd, $sortt, $sortn);

	incHeader();

	echo "<table align=center>";
	echo "<tr>";
	echo "<td class=header>Username</td>";
	echo "<td class=header>Forum Name</td>";
	echo "<td class=header>Mod Activity</td>";
	echo "<td class=header>User Activity</td>";
	echo "</tr>";

	foreach($forummods as $mod){
		echo "<tr>";
		echo "<td class=body><a class=body href=profile.php?uid=$mod[userid]>" . $users[$mod['userid']]['username'] . "</a></td>";
		echo "<td class=body>";
		if(empty($mod['forumid']))
			echo "Global";
		else
			echo "<a class=body href=forumthreads.php?fid=$mod[forumid]>$mod[forumname]</a>";
		echo "</td>";
		echo "<td class=body>" . ($mod['activetime'] ? userDate("F j, Y \\a\\t g:i a", $mod['activetime']) : 'Unknown') . "</td>";
		echo "<td class=body>" . ($users[$mod['userid']]['online'] == 'y' ? '<b>Online</b>' : userDate("F j, Y \\a\\t g:i a", $users[$mod['userid']]['activetime'])) . "</td>";
		echo "</tr>";
	}
	echo "</table>";
	incFooter();

