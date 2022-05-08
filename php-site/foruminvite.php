<?

	$login=1;

	require_once("include/general.lib.php");

	if(!($fid = getREQval('fid', 'int')))
		die("Bad Forum id");

	$perms = $forums->getForumPerms($fid);	//checks it's a forum, not a realm

	if(!$perms['invite'])
		die("You don't have permission to mute people in this forum");

	$forumdata = $perms['cols'];

//	$forums->db->prepare_query("SELECT name, official, ownerid FROM forums WHERE id = #", $fid);
//	$forumdata = $forums->db->fetchrow();


	if($forumdata['official']=='y')
		die("You can't invite to official forums");

	switch($action){
		case "Invite":
			if(!($username = getPOSTval('username')))
				break;

			if(empty($username) || trim($username) == "")
				break;

			$uid = getUserId($username);

			if($uid == $username)
				break;

			$forums->invite($uid, $fid);
/*

			$forums->db->prepare_query("INSERT IGNORE INTO foruminvite SET userid = ?, forumid = ?", $uid, $fid);

			if($forums->db->affectedrows() == 0)
				break;

			$forums->modLog('invite',$fid,0,$uid);

			$messaging->deliverMsg($uid,"Forum Invite","You have been invited to join the forum [url=forumthreads.php?fid=$fid]" . $forumdata['name'] . "[/url]. Click [url=forumthreads.php?fid=$fid&action=withdraw]here[/url] to withdraw from the forum.");

			$cache->put(array($uid, "foruminvite-$uid-$fid"), 1, 10800);
*/

			$msgs->addMsg("User Invited");

			break;
		case "delete":
		case "Uninvite":

			if(!($deleteID = getPOSTval('deleteID', 'array')))
				break;

			$forums->unInvite($deleteID, $fid);

/*
			$forums->db->prepare_query("DELETE FROM foruminvite WHERE userid IN (#) && forumid = #", $deleteID, $fid);

			foreach($deleteID as $uid){
				$forums->modLog('uninvite',$fid,0,$uid);
				$cache->put(array($uid, "foruminvite-$uid-$fid"), 0, 10800);
			}
*/

			$msgs->addMsg("User(s) Uninvited");
			break;
	}


	$forums->db->prepare_query("SELECT userid FROM foruminvite WHERE forumid = #", $fid);

	$uids = array();
	while($line = $forums->db->fetchrow())
		$uids[] = $line['userid'];

	$rows = array();

	if(count($uids)){
		$db->prepare_query("SELECT userid, username FROM users WHERE userid IN (#)", $uids);

		while($line = $db->fetchrow())
			$rows[$line['userid']] = $line['username'];
	}

	natcasesort($rows);

	incHeader();

	echo "<table><form action=$_SERVER[PHP_SELF] method=post>";
	echo "<tr><td class=header colspan=2>Invite user</td></tr>";
	echo "<tr><td class=body>Username:</td><td class=body><input class=body type=text name=username></td></tr>";
	echo "<input type=hidden name=fid value=$fid>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=Invite></td></tr>";
	echo "</form></table>";
	echo "<br>";

	echo "<table><form action=$_SERVER[PHP_SELF] method=post>";
	echo "<input type=hidden name=fid value=$fid>";
	echo "<tr><td class=body colspan=3>";
	echo "<a class=body href=forumsusercreated.php>User Created Forums</a> > ";

	echo "<a class=body href=forumthreads.php?fid=$fid>$forumdata[name]</a> > ";
	echo "<a class=body href=$_SERVER[PHP_SELF]?fid=$fid>Invite Users</a>";
	echo "</td></tr>";

	echo "<tr><td class=header></td><td class=header>Username</td></tr>";

	foreach($rows as $uid => $username){
		if($uid == $forumdata['ownerid'])
			continue;
		echo "<tr>";
		echo "<td class=body><input class=body type=checkbox name=deleteID[] value=$uid></td>";
		echo "<td class=body><a class=body href=profile.php?uid=$uid>$username</a></td>";
		echo "</tr>";
	}

	echo "<td class=header colspan=2><input class=body name=selectall type=checkbox value='Check All' onClick=\"this.value=check(this.form,'deleteID')\"><input class=body type=submit name=action value=Uninvite></td>";

	echo "</table>";

	incFooter();

