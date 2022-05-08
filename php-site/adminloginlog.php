<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"loginlog"))
		die("Permission denied");

	$col = getREQval('col');
	$val = getREQval('val');
	$page = getREQval('page', 'int');

	$rows = array();
	$uids = array();
	$numpages = 0;

	if($col && $val){
		$query = "SELECT userid, time, ip, result FROM loginlog ";
		if($col == 'ip'){
			$query .= $logdb->prepare("WHERE ip = ? ", ip2int($val));
			$key = false;
		}else{
			if($col == 'username')
				$uid = getUserID($val);
			else //col == 'userid'
				$uid = $val;

			$query .= $logdb->prepare("WHERE userid = ? ", $uid);
			$key = $uid;

		}

		$logdb->query($key, $query);

		$rows = array();
		$uids = array();
		while($line = $logdb->fetchrow()){
			$rows[] = $line;
			$uids[$line['userid']] = $line['userid'];
		}

		sortCols($rows, SORT_DESC, SORT_NUMERIC, 'time');

		$uids = getUserName($uids);
	}

	incHeader();

	echo "<table align=center>";
	echo "<form action=$_SERVER[PHP_SELF]>";
	echo "<tr><td class=body colspan=4 align=center>";
	echo "<select class=body name=col>" . make_select_list(array('username', 'userid', 'ip'), $col) . "</select>";
	echo "<input class=body type=text size=10 name=val value='$val'>";
	echo "<input class=body type=submit value=Go>";
	echo "</td></tr>";

	echo "<tr><td class=header>User</td><td class=header>Time</td><td class=header>IP</td><td class=header>Hostname</td><td class=header>Result</td></tr>";

	$hosts = array();

	foreach($rows as $row){
		echo "<tr>";
		echo "<td class=body><a class=body href=profile.php?uid=$row[userid]>" . $uids[$row['userid']] . "</a></td>";
		echo "<td class=body>" . userDate("F j, Y, g:i a", $row['time']) . "</td>";
		echo "<td class=body><a class=body href=adminuser.php?search=" . long2ip($row['ip']) . "&type=ip>" . long2ip($row['ip']) . "</a></td>";
		$ip = long2ip($row['ip']);
		if(!isset($hosts[$ip]))
			$hosts[$ip] = gethostbyaddr($ip);
		echo "<td class=body align=center>" . $hosts[$ip] . "</td>";
		echo "<td class=body>$row[result]</td>";
		echo "</tr>";
	}

	echo "<tr><td class=header colspan=5 align=right>";
	echo "Page: " . pageList("$_SERVER[PHP_SELF]?col=$col&val=$val",$page,$numpages,'header');
	echo "</td></tr>";
	echo "</table>";

	incFooter();

