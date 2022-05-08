<?

	$login=1;

	require_once("include/general.lib.php");

	if(empty($fid) || !is_numeric($fid))
		die("Bad Forum id");

	$perms = getForumPerms($fid);	//checks it's a forum, not a realm

	if(!$perms['invite'])
		die("You don't have permission to mute people in this forum");

	$db->prepare_query("SELECT name,official,ownerid FROM forums WHERE id = ?", $fid);
	$forumdata = $db->fetchrow();


	if($forumdata['official']=='y')
		die("You can't invite to official forums");

	switch($action){
		case "Invite":
			if(trim($username) == "")
				break;

			$uid = getUserId($username);

			$db->prepare_query("SELECT id FROM foruminvite WHERE userid = ? && forumid = ?", $uid, $fid);
			if($db->numrows() > 0 )
				break;

			$db->prepare_query("INSERT IGNORE INTO foruminvite SET userid = ?, forumid = ?", $uid, $fid);

			modLog('invite',$fid,0,$uid);

			deliverMsg($uid,"Forum Invite","You have been invited to join the forum [url=forumthreads.php?fid=$fid]" . $forumdata['name'] . "[/url]");

			$msgs->addMsg("User Invited");

			break;
		case "delete":
			$db->prepare_query("SELECT userid FROM foruminvite WHERE id = ? && forumid = ?", $id, $fid);
			$line = $db->fetchrow();

			if(!$line)
				break;

			modLog('uninvite',$fid,0,$line['userid']);

			$db->prepare_query("DELETE FROM foruminvite WHERE id = ?", $id);

			$msgs->addMsg("User Uninvited");
			break;
	}


	$result = $db->prepare_query("SELECT id,users.userid,username FROM foruminvite,users WHERE users.userid=foruminvite.userid && forumid = ? ORDER BY username", $fid);

	incHeader();

	echo "<table>";

	echo "<tr><td class=body colspan=3>";
	echo "<a class=body href=forumsusercreated.php>User Created Forums</a> > ";

	echo "<a class=body href=forumthreads.php?fid=$fid>$forumdata[name]</a> > ";
	echo "<a class=body href=$PHP_SELF?fid=$fid>Invite Users</a>";
	echo "</td></tr>";

	echo "<tr><td class=header></td><td class=header>Username</td></tr>";

	while($line = $db->fetchrow($result)){
		if($line['userid'] == $forumdata['ownerid'])
			continue;
		echo "<tr><td class=body><a class=body href=$PHP_SELF?action=delete&id=$line[id]&fid=$fid><img src=/images/delete.gif border=0></a></td>";
		echo "<td class=body><a class=body href=profile.php?uid=$line[userid]>$line[username]</a></td>";
	}

	echo "</table>";
	echo "<br>";

	echo "<table><form action=$PHP_SELF>";
	echo "<tr><td class=header colspan=2>Invite user</td></tr>";
	echo "<tr><td class=body>Username:</td><td class=body><input class=body type=text name=username></td></tr>";
	echo "<input type=hidden name=fid value=$fid>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=Invite></td></tr>";
	echo "</form></table>";

	incFooter();
