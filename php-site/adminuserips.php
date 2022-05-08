<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isAdmin($userData['userid'],'listusers')){
		header("location: /");
		exit;
	}

	$name = "";
	if(!empty($uid)){
		if(is_numeric($uid)){
			$name = getUserName($uid);
			if(!$name)
				$name = $uid;
		}else{
			$name = $uid;
			$uid = getUserID($name);
		}

		$mods->adminlog('list ips',"List ips for user: $uid");

		$fastdb->prepare_query("SELECT activetime,ip,hits FROM userhitlog WHERE userid = ?", $uid);

		$rows = array();
		while($line = $fastdb->fetchrow())
			$rows[] = $line;

		sortCols($rows, SORT_ASC, SORT_NUM, 'activetime');
	}

	incHeader();

	echo "<table align=center>";
	echo "<form action=$PHP_SELF>";

	echo "<tr><td class=header colspan=4 align=center>Username or Userid: <input class=body class=text name=uid value='$name'><input class=body type=submit value=Search></td></tr>";
	echo "</form>";

	if(!empty($uid)){
		echo "<tr><td class=header align=center>IP address</td><td class=header align=center>Hostname</td><td class=header align=center>Last Activetime</td><td class=header align=center>Hits</td></tr>";

		$hits = 0;
		$lastactive = 0;

		foreach($rows as $row){
			echo "<tr><td class=body><a class=body href=adminuser.php?search=" . long2ip($row['ip']) . "&type=ip>" . long2ip($row['ip']) . "</a></td>";
			echo "<td class=body align=center>". gethostbyaddr(long2ip($row['ip'])) . "</td>";
			echo "<td class=body>" . userDate("F j, Y, g:i a", $row['activetime']) . "</td>";
			echo "<td class=body align=right>$row[hits]</td></tr>";
			$hits += $row['hits'];
			if($lastactive < $row['activetime'])
				$lastactive = $row['activetime'];
		}
		echo "<tr><td class=header></td><td class=header></td><td class=header>" . userDate("F j, Y, g:i a", $lastactive) . "</td><td class=header align=right>$hits</td></tr>";
	}

	echo "</table>";

	incFooter();


