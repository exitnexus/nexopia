<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"forums"))
		die("Permission denied");

	$tid = getPOSTval('tid', 'int');

	$users = array();
	$usernames = array();

	if($tid){
	
		$res = $forumdb->prepare_query("SELECT forumid FROM forumthreads WHERE id = #", $tid);
		$fid = $res->fetchfield();


		$res = $forumdb->prepare_query("SELECT * FROM forumread WHERE threadid = # ORDER BY readtime DESC", $tid);

		while($line = $res->fetchrow()){
			$line['mod'] = -1;
			$users[$line['userid']] = $line;
		}


		$res = $forumdb->prepare_query("SELECT userid, forumid FROM forummods WHERE forumid IN (#,0) && userid IN (#) ORDER BY forumid DESC", $fid, array_keys($users));

		while($line = $res->fetchrow())
			$users[$line['userid']]['mod'] = $line['forumid'];

		$usernames = getUsername(array_keys($users));
	}

	incHeader();

	echo "<table align=center>";

	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<tr><td class=header colspan=5 align=center>";

	echo "Thread id: <input class=body type=text name=tid value=" . ($tid ? $tid : '') . "> ";
	echo "<input class=body type=submit value=Go>";

	echo "</td></tr>";
	echo "</form>";

	if($tid){
		echo "<tr>";
		echo "<td class=header>User</td>";
		echo "<td class=header>Subscribed</td>";
		echo "<td class=header>Last Read time</td>";
		echo "<td class=header>Posts Read</td>";
		echo "<td class=header>Moderator?</td>";
		echo "</tr>";

		$classes = array('body','body2');
		$i = 0;

		foreach($users as $user){
			echo "<tr>";
			echo "<td class=$classes[$i]><a class=body href=/profile.php?uid=$user[userid]>" . $usernames[$user['userid']] . "</a></td>";
			echo "<td class=$classes[$i]>" . ($user['subscribe'] == 'y' ? 'Subscribed' : '') . "</td>";
			echo "<td class=$classes[$i]>" . userdate("F j, Y, g:i a", $user['readtime']) . "</td>";
			echo "<td class=$classes[$i] align=right>" . ($user['posts']+1) . "</td>";
			echo "<td class=$classes[$i] align=center>" . ($user['mod'] == -1 ? '' : ($user['mod'] == 0 ? "Global" : "Mod")) . "</td>";
			echo "</tr>";
			$i = !$i;
		}
		
		echo "<tr><td class=header colspan=5>" . count($users) . " Users read the thread</td></tr>";
	}

	echo "</table>";

	incFooter();

