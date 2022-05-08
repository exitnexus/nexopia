<?

	$login=1;

	require_once("include/general.lib.php");


	if(!$mods->isAdmin($userData['userid'],"articles"))
		die("You do not have permission to see this page");

	$page = getREQval('page', 'int');


	$params = array();
	$query = "SELECT SQL_CALC_FOUND_ROWS premiumlog.userid, username, duration, time FROM premiumlog LEFT JOIN users ON premiumlog.userid = users.userid ";

	if($uid = getREQval('uid')){
		if(is_numeric($uid))
			$query .= "WHERE premiumlog.userid = ?";
		else
			$query .= "WHERE username = ?";
		$params[] = $uid;
	}

	$query .= " ORDER BY `time` DESC LIMIT " . ($page*$config['linesPerPage']) . ", $config[linesPerPage]";

	$db->prepare_array_query($query, $params);

	$rows = array();
	while($line = $db->fetchrow())
		$rows[] = $line;

	$db->query("SELECT FOUND_ROWS()");
	$numrows = $db->fetchfield();
	$numpages =  ceil($numrows / $config['linesPerPage']);

	incHeader();


	echo "<table align=center>";

	echo "<form action=$_SERVER[PHP_SELF]><tr><td class=header colspan=4 align=center>";
	echo "<input class=body type=text name=uid value=\"$uid\"><input class=body type=submit name=action value=Go>";
	echo "</td></tr></form>";

	echo "<tr>";
	echo "<td class=header>Userid</td>";
	echo "<td class=header>Username</td>";
	echo "<td class=header>Time</td>";
	echo "<td class=header>Duration</td>";
	echo "</tr>";

	foreach($rows as $row){
		echo "<tr>";
		echo "<td class=body align=right>$row[userid]</td>";
		echo "<td class=body>$row[username]</td>";
		echo "<td class=body align=right>" . userDate("D M j, Y G:i:s", $row['time']) . "</td>";
		echo "<td class=body align=right>" . round($row['duration']/(86400*31),2) . " months</td>";
		echo "</tr>";
	}

	echo "<tr><td class=header colspan=4 align=right>Page: " . pageList("$_SERVER[PHP_SELF]",$page,$numpages,'header') . "</td></tr>";

	echo "</table>";

	incFooter();



