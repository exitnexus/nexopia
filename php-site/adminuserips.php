<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isAdmin($userData['userid'], 'listusers')){
		header("location: /");
		exit;
	}

	$selectlist = array('userid' => 'Userid', 'username' => 'Username', 'ip' => 'IP');

	$type = getREQval('type', 'string', 'username');


	$uid = getPOSTval('uid');

	if(empty($uid)){
		$uid = getREQval('uid');

		if($uid && !checkKey($uid, getREQval('k')))
			$uid = "";
	}

	if(!blank($uid, $type)){

		$col = '';
		$param = '';
		$key = false;

		switch($type){
			case 'userid':
				$col = 'userid';
				$param = $uid;
				$where = $usersdb->prepare("userid = %", $param);
				break;

			case 'username':
				$col = 'userid';
				$param = getUserID($uid);
				$where = $usersdb->prepare("userid = %", $param);
				break;

			case 'ip':
				$col = 'ip';
				$param = ip2int($uid);
				$where = $usersdb->prepare("ip = #", $param);
				break;
		}

		if($where){
			$mods->adminlog('list ips',"List ips for $col: $param");

			$res = $usersdb->query("SELECT userid, activetime, ip, hits FROM userhitlog WHERE $where");


			$users = array();
			$userobjs = array();
			$usernames = array();
			$rows = array();
			while($line = $res->fetchrow()){
				$rows[] = $line;
				$users[] = $line['userid'];
			}

			if($users)
			{
				$userobjs = getUserInfo($users, false);
				$missing = array_diff($users, array_keys($userobjs));
				if ($missing)
					$usernames = getUserName($missing);
			}

			sortCols($rows, SORT_ASC, SORT_NUMERIC, 'activetime');
		}
	}

	incHeader();

	echo "<table align=center>";
	echo "<form action=$_SERVER[PHP_SELF] method=post>";

	echo "<tr><td class=header colspan=5 align=center><select class=body name=type>" . make_select_list_key($selectlist, $type) . "</select><input class=body class=text name=uid value='$uid'><input class=body type=submit value=Search></td><td class=body><a class=body href=/admincompareips.php>Compare Two Users</a></td></tr>";

	echo "</form>";

	if(!empty($rows)){
		echo "<tr>";
		echo "<td class=header align=center>Userid</td>";
		echo "<td class=header align=center>Username</td>";
		echo "<td class=header align=center>IP address</td>";
		echo "<td class=header align=center>Hostname</td>";
		echo "<td class=header align=center>Last Activetime</td>";
		echo "<td class=header align=center>Hits</td>";
		echo "</tr>";

		$hits = 0;
		$lastactive = 0;

		foreach($rows as $row){
			echo "<tr>";
			echo "<td class=body><a class=body href=/adminuserips.php?uid=$row[userid]&type=userid&k=" . makeKey($row['userid']) . ">$row[userid]</a></td>";
			echo "<td class=body>";
			if(isset($userobjs[$row['userid']]))
				echo "<a class=body href=/adminuser.php?search=$row[userid]&type=userid&k=" . makeKey($row['userid']) . ">" .$userobjs[$row['userid']]['username'] . "</a>";
			else
				echo "<a class=body href=/admindeletedusers.php?uid=$row[userid]&type=userid><strike>{$usernames[$row['userid']]}</strike></a>";
			echo "</td>";
			echo "<td class=body><a class=body href=/adminuserips.php?uid=" . long2ip($row['ip']) . "&type=ip&k=" . makeKey(long2ip($row['ip'])) . ">" . long2ip($row['ip']) . "</a></td>";
			echo "<td class=body align=center>". gethostbyaddr(long2ip($row['ip'])) . "</td>";
			echo "<td class=body>" . userDate("F j, Y, g:i a", $row['activetime']) . "</td>";
			echo "<td class=body align=right>$row[hits]</td>";
			echo "</tr>";
			$hits += $row['hits'];
			if($lastactive < $row['activetime'])
				$lastactive = $row['activetime'];
		}
		echo "<tr><td class=header colspan=4></td><td class=header>" . userDate("F j, Y, g:i a", $lastactive) . "</td><td class=header align=right>$hits</td></tr>";
	}

	echo "</table>";

	incFooter();


