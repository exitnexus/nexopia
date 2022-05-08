<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"listmods"))
		die("Permission denied");

	$rows = array();

	if(empty($username))
		$username = "";
	if(empty($itemid))
		$itemid = "";

	if(!empty($username) || !empty($itemid)){

		$commands = array();

		if(!empty($username)){
			if(!is_numeric($username))
				$uid = getUserID($username);
			else
				$uid = $username;

			$commands[] = $db->prepare("modvoteslog.modid = ?", $uid);
		}
		if(!empty($itemid))
			$commands[] = $db->prepare("modvoteslog.picid = ?", $itemid);

		$mods->adminlog('mod log',"Show mod vote log, search by user: $username, itemid: $itemid");

		if(empty($page))
			$page = 0;

		$db->query("SELECT SQL_CALC_FOUND_ROWS modvoteslog.modid, moduser.username as modname, modvoteslog.vote, modvoteslog.picid, pics.description, modvoteslog.userid as picuserid, picuser.username as picusername, picuser.age, picuser.sex
						FROM modvoteslog
							LEFT JOIN pics ON modvoteslog.picid=pics.id
							LEFT JOIN users AS picuser ON modvoteslog.userid=picuser.userid
							LEFT JOIN users as moduser ON modvoteslog.modid=moduser.userid
						WHERE " . implode(" && ", $commands) . "
						ORDER BY modvoteslog.id DESC
						LIMIT " . $page*$config['linesPerPage'] . ", $config[linesPerPage]");

		while($line = $db->fetchrow())
			$rows[] = $line;

		$db->query("SELECT FOUND_ROWS()");
		$numrows = $db->fetchfield();
		$numpages =  ceil($numrows / $config['linesPerPage']);

		if($numrows == 0)
			$msgs->addMsg("No results found");
	}

	incHeader();

	if(count($rows)){
		echo "<table>";
		echo "<tr><td class=header colspan=2 align=right>Page: " . pageList("$PHP_SELF?username=$username&itemid=$itemid",$page,$numpages,'header') . "</td></tr>";

		$picloc = $config['picloc'];

		foreach($rows as $line){
			if($line['sex']=='Female') 	$bgcolor = '#FFAAAA';
			else						$bgcolor = '#AAAAFF';

			$age = $line['age'];
			echo "<tr><td class=body valign=top align=right style=\"background-color: $bgcolor\"><img src=$picloc" . floor($line['picid']/1000) . "/$line[picid].jpg></td>";
			echo "<td class=body valign=top style=\"background-color: $bgcolor\">";
			echo "<a class=body href=profile.php?uid=$line[picuserid]>$line[picusername]</a><br>";
			echo "Age: $age<br>";
			echo "Sex: <b>$line[sex]</b><br><br>";
			echo "$line[description]<br><br>";
			echo "Vote:<br>";
			echo "<a class=body href=profile.php?uid=$line[modid]>$line[modname]</a><br>";
			if($line['vote']=='y')
				echo "Accept";
			else
				echo "Deny";
			echo "<br><br>";

			if(empty($username))
				echo "<a class=body href=$PHP_SELF?username=$line[modid]>Search this mod</a>";
			if(empty($itemid))
				echo "<a class=body href=$PHP_SELF?itemid=$line[picid]>Search this pic</a>";
			echo "</td></tr>";
			echo "<tr><td colspan=2>&nbsp;</td></tr>\n";
		}

		echo "<tr><td class=header colspan=2 align=right>Page: " . pageList("$PHP_SELF?username=$username&itemid=$itemid",$page,$numpages,'header') . "</td></tr>";
		echo "</table>";
	}

	echo "<table><form action=$PHP_SELF>";
	echo "<tr><td class=body>Username</td><td class=body><input class=body type=text name=username value=$username></td></tr>";
	echo "<tr><td class=body>Picid</td><td class=body><input class=body type=text name=itemid value=$itemid></td></tr>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit value=Search></td></tr>";
	echo "</form></table>";

	incFooter();


