<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"listmods"))
		die("Permission denied");

	$rows = array();

	$username = getREQval('username');
	$picusername = getREQval('picusername');
	$itemid = getREQval('itemid');

	if(!empty($username) || !empty($itemid) || !empty($picusername)){

		$commands = array();

		if(!empty($username)){
			if(!is_numeric($username))
				$uid = getUserID($username);
			else
				$uid = $username;

			$commands[] = $moddb->prepare("modid = #", $uid);
		}
		if(!empty($itemid))
		{
			$key = array('userid' => '#', 'picid' => '#');
			$commands[] = $db->prepare_multikey($key, array(str_replace('/',':',$itemid)));
		}
		if (!empty($picusername))
		{
			if (!is_numeric($picusername))
				$picuid = getUserID($picusername);
			else
				$picuid = $picusername;
			$commands[] = $moddb->prepare("userid = #", $picuid);
		}

		$mods->adminlog('mod log',"Show mod vote log, search by user: $username, itemid: $itemid, picuser: $picusername");

		$page = getREQval('page', 'int');

		$res = $moddb->query("SELECT SQL_CALC_FOUND_ROWS modid, vote, picid, userid, time FROM modvoteslog WHERE " . implode(" && ", $commands) . " ORDER BY time ASC LIMIT " .($page*$config['linesPerPage']) .", $config[linesPerPage]");

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
		echo "<tr><td class=header colspan=2 align=right>Page: " . pageList("$_SERVER[PHP_SELF]?username=$username&itemid=$itemid&picusername=$picusername",$page,$numpages,'header') . "</td></tr>";

		$picloc = $config['picloc'];

		foreach($rows as $line){
			if($users[$line['userid']]['sex']=='Female') 	$bgcolor = '#FFAAAA';
			else											$bgcolor = '#AAAAFF';

			echo "<tr><td class=body valign=top align=right style=\"background-color: $bgcolor\"><img src=$picloc" . floor($line['userid']/1000) . "/" . weirdmap($line['userid']) . "/$line[picid].jpg></td>";
			echo "<td class=body valign=top style=\"background-color: $bgcolor\">";
			echo "<a class=body href=/users/". urlencode($users[$line["userid"]]["username"]) .">" . $users[$line['userid']]['username'] . "</a><br>";
			echo "Age: " . $users[$line['userid']]['age'] . "<br>";
			echo "Sex: <b>" . $users[$line['userid']]['sex'] . "</b><br>";
			echo "Time: " . userDate("M j, y g:i a", $line['time']) . "<br><br>";
			echo "$line[description]<br><br>";
			echo "Vote:<br>";
			echo "<a class=body href=/users/". urlencode($users[$line['modid']]["username"]) .">" . $users[$line['modid']]['username'] . "</a><br>";
			if($line['vote']=='y')
				echo "Accept";
			else
				echo "Deny";
			echo "<br><br>";

			if(empty($username))
				echo "<div><a class=body href=$_SERVER[PHP_SELF]?username=$line[modid]>Search this mod</a></div>";
			if(empty($itemid))
				echo "<div><a class=body href=$_SERVER[PHP_SELF]?itemid=$line[userid]/$line[picid]>Search this pic</a></div>";
			if(empty($picusername))
				echo "<div><a class=body href=$_SERVER[PHP_SELF]?picusername=$line[userid]>Search this user</a></div>";
			echo "</td></tr>";
			echo "<tr><td colspan=2>&nbsp;</td></tr>\n";
		}

		echo "<tr><td class=header colspan=2 align=right>Page: " . pageList("$_SERVER[PHP_SELF]?username=$username&itemid=$itemid&picusername=$picusername",$page,$numpages,'header') . "</td></tr>";
		echo "</table>";
	}

	echo "<table><form action=$_SERVER[PHP_SELF]>";
	echo "<tr><td class=body>Mod Username</td><td class=body><input class=body type=text name=username value='" . htmlentities($username) . "'></td></tr>";
	echo "<tr><td class=body>Pic Username</td><td class=body><input class=body type=text name=picusername value='" . htmlentities($picusername) . "'></td></tr>";
	echo "<tr><td class=body>Picid </td><td class=body><input class=body type=text name=itemid value=$itemid></td></tr>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit value=Search></td></tr>";
	echo "</form></table>";
	echo "<p>The picid is the bold part of the following url: http://images.nexopia.com/userpicsthumb/1105/<b>1105139/17870284</b>.jpg</p>";

	incFooter();


