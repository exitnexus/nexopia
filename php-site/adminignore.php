<?

	$login=1;

	require_once("include/general.lib.php");

	if(!isAdmin($userData['userid'],"ignoreusers"))
		die("You do not have permission to see this page");

	switch($action){
		case "ignore":
		case "Ignore":
			if(!is_numeric($id))
				$id = getUserID($id);

			if(!empty($id)){
				$db->prepare_query("INSERT IGNORE INTO `ignore` SET userid = 0, ignoreid = ?", $id);
				adminlog("ignore", "ignore $id");
			}
			break;
		case "unignore":
			if(!empty($id)){
				$db->prepare_query("DELETE FROM `ignore` WHERE userid = 0 && ignoreid = ?", $id);
				adminlog("unignore", "unignore $id");
			}
			break;
	}

	incHeader();

	$res = $db->query("SELECT ignoreid,username FROM `ignore`,users WHERE `ignore`.userid = 0 && `ignore`.ignoreid = users.userid");

	echo "<table>";
	echo "<tr><td class=header>Username</td><td class=header></td></tr>";

	while($line = $res->fetchrow()){
		echo "<tr><td class=body><a class=body href='/users/". $line["username"] ."'>$line[username]</a></td>";
		echo "<td class=body><a class=body href=$PHP_SELF?action=unignore&id=$line[ignoreid]><img src=$config[imageloc]/delete.gif border=0></a></td></tr>";
	}
	echo "</table><br>";

	echo "<table><form action=$PHP_SELF>";
	echo "<tr><td class=header colspan=2>Add to ignore list</td></tr>";
	echo "<tr><td class=body>Username:</td><td class=body><input class=body type=text name=id></td></tr>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=Ignore></td></tr>";
	echo "</form></table>";

	incFooter();
	exit;


