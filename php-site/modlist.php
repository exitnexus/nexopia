<?

	$login=1;

	require_once("include/general.lib.php");

	$sortlist = array( 	'userid' => "",
						'username' => "username",
						'online' => "'n'",
						'activetime' => "'0'"
						);

	$res = $mods->db->prepare_query("SELECT userid, 'n' as online, 0 as activetime FROM mods WHERE type = ?", MOD_PICS);

	$rows = array();
	while($line = $res->fetchrow())
		$rows[$line['userid']] = $line;

	if(!$mods->isAdmin($userData['userid']) && !isset($rows[$userData['userid']]))
		die("You don't have permission to see this page");

	$res = $usersdb->prepare_query("SELECT userid, online, activetime FROM useractivetime WHERE userid IN (%)", array_keys($rows));

	while($line = $res->fetchrow()){
		$rows[$line['userid']]['online'] = $line['online'];
		$rows[$line['userid']]['activetime'] = $line['activetime'];
	}
	
	$usernames = getUserName(array_keys($rows));
	
	foreach($rows as $k => $v)
		$rows[$k]['username'] = $usernames[$k];

	sortCols($rows, SORT_ASC, SORT_CASESTR, 'username', SORT_DESC, SORT_CASESTR, 'online');

	incHeader();

	echo "<table align=center>";

	echo "<tr>";
		echo "<td class=header>Username</td>";
		echo "<td class=header>Activetime</td>";
	echo "</tr>";

	foreach($rows as $line){
		echo "<tr>";
		echo "<td class=body><a class=body href=/profile.php?uid=$line[userid]>$line[username]</a></td>";
		echo "<td class=body>" . ($line['online'] == 'y' ? "<b>Online</b>" : ($line['activetime'] == 0 ? "Never" : userDate("M j, Y G:i", $line['activetime']) )) . "</td>";
	}

	echo "</table>";

	incFooter();

