<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isAdmin($userData['userid'], 'listusers')){
		header("location: /");
		exit;
	}

	$selectlist = array('userid' => 'Userid', 'username' => 'Username', 'ip' => 'IP');

	$uid = getREQval('uid');
	$type = getREQval('type', 'string', 'username');

	if(!empty($uid) && !empty($type)){

		$col = '';
		$param = '';
		$key = false;

		switch($type){
			case 'userid':
				$col = 'userid';
				$param = $uid;
				$key = $uid;
				break;

			case 'username':
				$col = 'userid';
				$param = getUserID($uid);
				$key = $param;
				break;

			case 'ip':
				$col = 'ip';
				$param = ip2int($uid);
				break;
		}

		if($col && $param){
			$mods->adminlog('list ips',"List ips for $col: $param");

			$logdb->prepare_query($key, "SELECT userid, activetime, ip, hits FROM userhitlog WHERE $col = #", $param);


			$users = array();
			$rows = array();
			while($line = $logdb->fetchrow()){
				$rows[] = $line;
				$users[] = $line['userid'];
			}

			if($users){
				$db->prepare_query("SELECT userid, username FROM users WHERE userid IN (#)", $users);

				$users = array();

				while($line = $db->fetchrow())
					$users[$line['userid']] = $line['username'];
			}


			sortCols($rows, SORT_ASC, SORT_NUMERIC, 'activetime');
		}
	}

	incHeader();

	echo "<table align=center>";
	echo "<form action=$_SERVER[PHP_SELF]>";

	echo "<tr><td class=header colspan=6 align=center><select class=body name=type>" . make_select_list_key($selectlist, $type) . "</select><input class=body class=text name=uid value='$uid'><input class=body type=submit value=Search></td></tr>";
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
			echo "<td class=body><a class=body href=adminuserips.php?uid=$row[userid]&type=userid>$row[userid]</a></td>";
			echo "<td class=body>";
			if(isset($users[$row['userid']]))
				echo "<a class=body href=adminuser.php?search=$row[userid]&type=userid>" .$users[$row['userid']] . "</a>";
			else
				echo "<a class=body href=admindeletedusers.php?uid=$row[userid]&type=userid>(deleted)</a>";
			echo "</td>";
			echo "<td class=body><a class=body href=adminuserips.php?uid=" . long2ip($row['ip']) . "&type=ip>" . long2ip($row['ip']) . "</a></td>";
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


