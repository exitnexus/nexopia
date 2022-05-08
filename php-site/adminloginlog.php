<?

	$login = 1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"loginlog"))
		die("Permission denied");

	$col = getREQval('col');
	$page = getREQval('page', 'int');

	$val = trim(getPOSTval('val'));

	if(!$val){
		$val = trim(getREQval('val'));

		if($val && !checkKey($val, getREQval('k')))
			$val = "";
	}

	$rows = array();
	$uids = array();
	$numpages = 0;
	$linesPerPage = 100;

	if($col && $val){
		$query = "SELECT userid, time, ip, result FROM loginlog ";
		if($col == 'ip'){
			$query .= $usersdb->prepare("WHERE ip = # ", ip2int($val));
			$key = false;
		}else{
			if($col == 'username' || !is_numeric($col))
				$uid = getUserID($val);
			else //col == 'userid'
				$uid = $val;

			$query .= $usersdb->prepare("WHERE userid = %", $uid);
			$key = $uid;

		}

		$res = $usersdb->query($query);

		$rows = array();
		$uids = array();
		while($line = $res->fetchrow()){
			$rows[] = $line;
			$uids[$line['userid']] = $line['userid'];
		}

		$uids = getUserName($uids);

		sortCols($rows, SORT_DESC, SORT_NUMERIC, 'time');
		$numpages = ceil(count($rows)/$linesPerPage);

		$rows = array_slice($rows, ($page*$linesPerPage), $linesPerPage);
	}

	incHeader();

	echo "<table align=center>";
	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<tr><td class=body colspan=5 align=center>";
	echo "<select class=body name=col>" . make_select_list(array('username', 'userid', 'ip'), $col) . "</select>";
	echo "<input class=body type=text size=10 name=val value='" . htmlentities($val) . "'>";
	echo "<input class=body type=submit value=Go>";
	echo "</td></tr>";

	echo "<tr>";
	echo "<td class=header>User</td>";
	echo "<td class=header>Time</td>";
	echo "<td class=header>IP</td>";
	echo "<td class=header>Hostname</td>";
	echo "<td class=header>Result</td>";
	echo "</tr>";

	$hosts = array();

	foreach($rows as $row){
		$class = (in_array($row['result'], array('success','changepass','changeemail')) ? 'body' : 'body2');

		echo "<tr>";
		echo "<td class=$class><a class=body href=/profile.php?uid=$row[userid]>" . $uids[$row['userid']] . "</a></td>";
		echo "<td class=$class>" . userDate("F j, Y, g:i a", $row['time']) . "</td>";
		$ip = long2ip($row['ip']);
		echo "<td class=$class><a class=body href=/adminuser.php?search=$ip&type=ip&k=" . makeKey($ip) . ">$ip</a></td>";
		if(!isset($hosts[$ip]))
			$hosts[$ip] = gethostbyaddr($ip);
		echo "<td class=$class align=center>" . $hosts[$ip] . "</td>";
		echo "<td class=$class>$row[result]</td>";
		echo "</tr>";
	}

	echo "<tr><td class=header align=right colspan=5>Page: " . pageList("$_SERVER[PHP_SELF]?col=$col&val=$val&k=" . makeKey($val), $page, $numpages, 'header') . "</td></tr>";
	echo "</table>";

	incFooter();
