<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'], "adminlog"))
		die("Permission denied");

	$uid = getREQval('uid', 'int');
	$page = getREQval('page','int');

//	$mods->adminlog("admin log","Admin log, user: $uid");

	$query = "SELECT SQL_CALC_FOUND_ROWS * FROM adminlog";
	if($uid)
		$query .= " WHERE " . $mods->db->prepare("userid = ?", getUserID($uid));
	$query .=" ORDER BY id DESC LIMIT " . $page*$config['linesPerPage'] . ", $config[linesPerPage]";
	$res = $mods->db->query($query);

	$rows = array();
	$uids = array();
	while($line = $res->fetchrow()){
		$rows[] = $line;
		$uids[$line['userid']] = $line['userid'];
	}

	$numrows = $res->totalrows();
	$numpages =  ceil($numrows / $config['linesPerPage']);

	$usernames = getUserName($uids);

	incHeader();

	echo "<table width=100%>";
	echo "<tr>";
	echo "<td class=header>Username</td>";
	echo "<td class=header>Time</td>";
	echo "<td class=header>IP</td>";
	echo "<td class=header>Page</td>";
	echo "<td class=header>Action</td>";
	echo "<td class=header>Description</td>";
	echo "</tr>";

	foreach($rows as $row){
		echo "<tr>";
		echo "<td class=body><a class=body href=/profile.php?uid=$row[userid]>" . $usernames[$row['userid']] . "</a></td>";
		echo "<td class=body nowrap>" . userDate("F j, Y, g:i a", $row['time']) . "</td>";
		$ip = long2ip($row['ip']);
		echo "<td class=body><a class=body href=/adminuser.php?type=ip&search=$ip&k=" . makeKey($ip) . ">$ip</a></td>";
		echo "<td class=body>$row[page]</td>";
		echo "<td class=body>$row[action]</td>";
		echo "<td class=body>$row[description]</td>";
		echo "</tr>";
	}

	echo "<tr><td class=header colspan=6>";

	echo "<table width=100%><tr>";
	echo "<form action=$_SERVER[PHP_SELF]>";
	echo "<td class=header>";
	echo "Admin Name: <input class=body type=text name=uid value='$uid'><input class=body type=submit value=Go>";
	echo "</td>";
	echo "</form>";

	echo "<td class=header align=right>";
	echo "Page: " . pageList("$_SERVER[PHP_SELF]?uid=$uid",$page,$numpages,'header');
	echo "</td>";
	echo "</tr></table>";
	echo "</td></tr>";

	echo "</table>";

	incFooter();

