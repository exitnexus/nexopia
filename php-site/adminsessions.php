<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isAdmin($userData['userid'], 'listusers')){
		header("location: /");
		exit;
	}

	$name = "";
	$uid = getREQval('uid', 'int');

	if(!empty($uid)){
		if(is_numeric($uid)){
			$name = getUserName($uid);
			if(!$name)
				$name = $uid;
		}else{
			$name = $uid;
			$uid = getUserID($name);
		}

		if($action == 'logout' && ($id = getREQval('id', 'int'))){
			$sessiondb->prepare_query($uid, "DELETE FROM sessions WHERE userid = # && id = #", $uid, $id);
			$mods->adminlog('remove sessions',"Remove session $id for user $uid");
		}

		$mods->adminlog('list sessions',"List sessions for user: $uid");

		$sessiondb->prepare_query($uid, "SELECT id, userid, activetime, ip, sessionid, cachedlogin, lockip FROM sessions WHERE userid = #", $uid);

		$rows = array();
		while($line = $sessiondb->fetchrow())
			$rows[] = $line;

		sortCols($rows, SORT_ASC, SORT_NUMERIC, 'activetime');
	}

	incHeader();

	echo "<table align=center>";
	echo "<form action=$_SERVER[PHP_SELF]>";

	echo "<tr><td class=header colspan=6 align=center>Username or Userid: <input class=body class=text name=uid value='$name'><input class=body type=submit value=Search></td></tr>";
	echo "</form>";

	if(!empty($uid)){
		echo "<tr>";
		echo "<td class=header align=center>IP address</td>";
		echo "<td class=header align=center>Hostname</td>";
		echo "<td class=header align=center>Last Activetime</td>";
		echo "<td class=header align=center>Cached</td>";
		echo "<td class=header align=center>Locked</td>";
		echo "<td class=header align=center>Remove</td>";
		echo "</tr>";


		foreach($rows as $row){
			echo "<tr>";
			echo "<td class=body><a class=body href=adminuser.php?search=" . long2ip($row['ip']) . "&type=ip>" . long2ip($row['ip']) . "</a></td>";
			echo "<td class=body align=center>". gethostbyaddr(long2ip($row['ip'])) . "</td>";
			echo "<td class=body>" . userDate("F j, Y, g:i a", $row['activetime']) . "</td>";
			echo "<td class=body align=right>" . ($row['cachedlogin'] == 'y' ? 'Cached' : '') . "</td>";
			echo "<td class=body align=right>" . ($row['lockip'] == 'y' ? 'Locked' : '') . "</td>";
			echo "<td class=body><a class=body href=$_SERVER[PHP_SELF]?action=logout&uid=$uid&id=$row[id]>Logout</a></td>";
			echo "</tr>";
		}
	}

	echo "</table>";

	incFooter();

