<?

	$login=1;

	require_once("include/general.lib.php");


	$db->prepare_query("SELECT forummods.userid,username,forums.name as forumname,forummods.forumid, online, activetime FROM forummods,users LEFT JOIN forums ON forummods.forumid=forums.id WHERE forummods.userid = users.userid && (forums.official='y' || forummods.forumid = 0)");

	$mod = $mods->isAdmin($userData['userid'],"forums");

	$forummods = array();
	while($line = $db->fetchrow()){
		$forummods[] = $line;
		if($line['userid'] == $userData['userid'])
			$mod = true;
	}

	if(!$mod)
		die("You don't have permission to see this");

	$sortd = SORT_ASC;
	$sortt = SORT_CASTSTR;
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

	sortCols($forummods, SORT_ASC, SORT_CASESTR, 'forumname', SORT_ASC, SORT_CASESTR, 'username', $sortd, $sortt, $sortn);

	incHeader();

	echo "<table align=center>";
	echo "<tr>";
	echo "<td class=header><a class=header href=$PHP_SELF?sortn=username>Username</a></td>";
	echo "<td class=header><a class=header href=$PHP_SELF?sortn=forumname>Forum Name</a></td>";
	echo "<td class=header><a class=header href=$PHP_SELF?sortn=online>Online</a></td>";
	echo "<td class=header><a class=header href=$PHP_SELF?sortn=activetime>Activetime</a></td>";
	echo "</tr>";

	foreach($forummods as $mod){
		echo "<tr>";
		echo "<td class=body><a class=body href=profile.php?uid=$mod[userid]>$mod[username]</a></td>";
		echo "<td class=body>";
		if(empty($mod['forumid']))
			echo "Global";
		else
			echo "<a class=body href=forumthreads.php?fid=$mod[forumid]>$mod[forumname]</a>";
		echo "</td>";
		echo "<td class=body>" . ($mod['online'] == 'y' ? 'Online' : '') . "</td>";
		echo "<td class=body>" . userDate("F j, Y \\a\\t g:i a", $mod['activetime']) . "</td>";
		echo "</tr>";
	}
	echo "</table>";
	incFooter();

