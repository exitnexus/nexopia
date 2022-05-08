<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"listmods"))
		die("Permission denied");

	$rows = array();

	$username = getREQval('username');
	$itemid = getREQval('itemid');

	if(!empty($username) || !empty($itemid)){

		$commands = array();

		if(!empty($username)){
			if(!is_numeric($username))
				$uid = getUserID($username);
			else
				$uid = $username;

			$commands[] = $db->prepare("modid = #", $uid);
		}
		if(!empty($itemid))
			$commands[] = $db->prepare("picid = #", $itemid);

		$mods->adminlog('mod log',"Show mod vote log, search by user: $username, itemid: $itemid");

		$page = getREQval('page', 'int');

		$res = $moddb->query("SELECT SQL_CALC_FOUND_ROWS modid, vote, picid, userid FROM modvoteslog WHERE " . implode(" && ", $commands) . " ORDER BY time ASC LIMIT " .($page*$config['linesPerPage']) .", $config[linesPerPage]");

		$uids = array();

		while($line = $res->fetchrow()){
			$rows[] = $line;
			$uids[$line['modid']] = $line['modid'];
			$uids[$line['userid']] = $line['userid'];
		}

		$numrows = $res->totalrows();
		$numpages =  ceil($numrows / $config['linesPerPage']);

		if($numrows == 0)
			$msgs->addMsg("No results found");

		if(count($uids))
			$users = getUserInfo($uids);
	}

	incHeader();

//print_r($rows);

	if(count($rows)){
		echo "<table>";
		echo "<tr><td class=header colspan=2 align=right>Page: " . pageList("$_SERVER[PHP_SELF]?username=$username&itemid=$itemid",$page,$numpages,'header') . "</td></tr>";

		$picloc = $config['picloc'];

		foreach($rows as $line){
			if($users[$line['userid']]['sex']=='Female') 	$bgcolor = '#FFAAAA';
			else											$bgcolor = '#AAAAFF';

			echo "<tr><td class=body valign=top align=right style=\"background-color: $bgcolor\"><img src=$picloc" . floor($line['userid']/1000) . "/" . weirdmap($line['userid']) . "/$line[picid].jpg></td>";
			echo "<td class=body valign=top style=\"background-color: $bgcolor\">";
			echo "<a class=body href=/profile.php?uid=$line[userid]>" . $users[$line['userid']]['username'] . "</a><br>";
			echo "Age: " . $users[$line['userid']]['age'] . "<br>";
			echo "Sex: <b>" . $users[$line['userid']]['sex'] . "</b><br><br>";
			echo "$line[description]<br><br>";
			echo "Vote:<br>";
			echo "<a class=body href=/profile.php?uid=$line[modid]>" . $users[$line['modid']]['username'] . "</a><br>";
			if($line['vote']=='y')
				echo "Accept";
			else
				echo "Deny";
			echo "<br><br>";

			if(empty($username))
				echo "<a class=body href=$_SERVER[PHP_SELF]?username=$line[modid]>Search this mod</a>";
			if(empty($itemid))
				echo "<a class=body href=$_SERVER[PHP_SELF]?itemid=$line[picid]>Search this pic</a>";
			echo "</td></tr>";
			echo "<tr><td colspan=2>&nbsp;</td></tr>\n";
		}

		echo "<tr><td class=header colspan=2 align=right>Page: " . pageList("$_SERVER[PHP_SELF]?username=$username&itemid=$itemid",$page,$numpages,'header') . "</td></tr>";
		echo "</table>";
	}

	echo "<table><form action=$_SERVER[PHP_SELF]>";
	echo "<tr><td class=body>Username</td><td class=body><input class=body type=text name=username value='" . htmlentities($username) . "'></td></tr>";
	echo "<tr><td class=body>Picid</td><td class=body><input class=body type=text name=itemid value=$itemid></td></tr>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit value=Search></td></tr>";
	echo "</form></table>";

	incFooter();


