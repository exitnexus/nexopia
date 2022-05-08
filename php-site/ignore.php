<?

//unused, use messages.php isntead

	$login=1;

	require_once("include/general.lib.php");

	switch($action){
		case "add":
		case "Add":

			if(!($uid = getREQval('uid'))){
				if(!($username = getREQval('username')))
					break;

				$uid = getUserID($username);
			}

			$uid = intval($uid);

			if(empty($uid))
				break;

			$db->prepare_query("INSERT IGNORE INTO `ignore` SET userid = #, ignoreid = #", $userData['userid'], $uid);

			$cache->remove(array($userData['userid'], "ignorelist-$userData[userid]"));

			$msgs->addMsg("He/She has been added to your ignore list.");

			break;
		case "delete":
			if(!($deleteID = getPOSTval('deleteID', 'array')))
				break;

			$db->prepare_query("DELETE FROM `ignore` WHERE userid = ? && ignoreid IN (?)", $userData['userid'], $deleteID);

			$cache->remove(array($userData['userid'], "ignorelist-$userData[userid]"));

			$msgs->addMsg("Deleted");
			break;
	}

	incHeader();

	$db->prepare_query("SELECT id, ignoreid, username FROM `ignore`, users WHERE `ignore`.userid = ? && `ignore`.ignoreid=users.userid", $userData['userid']);

	$rows = array();
	while($line = $db->fetchrow())
		$rows[] = $line;

	sortCols($rows, SORT_ASC, SORT_CASESTR, 'username');

	echo "<table width=100%><form action=$_SERVER[PHP_SELF] method=post>\n";
	echo "<tr>
			<td class=header>Delete</td>
			<td class=header>Ignored User</td>
		</tr>\n";

	foreach($rows as $line)
		echo "<tr><td class=body><input type=checkbox name=deleteID[] value='$line[ignoreid]'></td><td class=body><a class=body href='profile.php?uid=$line[ignoreid]'>$line[username]</a></td></tr>\n";

	echo "<tr><td class=header colspan=6><input class=body type=submit name=action value=delete></td></tr>\n";
	echo "</form></table>\n";

	echo "<table><form action=$_SERVER[PHP_SELF] method=post>";
	echo "<tr><td class=header colspan=2>Add to ignore list</td></tr>";
	echo "<tr><td class=body>Username:</td><td class=body><input class=body type=text name=username></td></tr>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=Add></td></tr>";
	echo "</form></table>";

	incFooter();


