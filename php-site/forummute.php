<?

	$login=1;

	require_once("include/general.lib.php");

	$fid = getREQval('fid', 'int');

	if($fid){
		$perms = $forums->getForumPerms($fid);	//checks it's a forum, not a realm

		if(!$perms['mute'])
			die("You don't have permission to mute people in this forum");

		$forumdata = $perms['cols'];
	}else{ //fid = 0
		if(!$mods->isAdmin($userData['userid'],'forums')){
			$forums->db->prepare_query("SELECT mute FROM forummods WHERE userid = # && forumid = 0", $userData['userid']);

			if($forums->db->numrows() == 0 || $forums->db->fetchfield() == 'n')
				die("You don't have permission to mute people in this forum");
		}

		$forumdata = array("name" => "Global", 'official' => 'y');
	}

	switch($action){
		case "Mute":
			if(!($uid = trim(getPOSTval('username'))))
				break;

			$uid = getUserId($uid);

			if(!$uid){
				$msgs->addMsg("Must select a valid user");
				break;
			}

			$time = getPOSTval('time', 'int', 0);

			if(!isset($forums->mutelength[$time])){
				$msgs->addMsg("Must select a valid length");
				break;
			}

			if(!($reasonid = getPOSTval('reasonid', 'int')) || !isset($forums->reasons[$reasonid])){
				$msgs->addMsg("Must specify a category");
				break;
			}

			if(!($reason = getPOSTval('reason'))){
				$msgs->addMsg("Must specify a reason");
				break;
			}

			$forums->forumMuteUser($uid, $fid, $time, $reasonid, $reason, $forumdata['name'], ($forumdata['official'] == 'y'));

			break;
		case "delete":
			if($uid = getREQval('uid', 'int'))
				$forums->forumUnmuteUser($uid, $fid);

			break;
	}

//get mutes
	$forums->db->prepare_query("SELECT forummute.id, userid, mutetime, unmutetime, reasonid, modid, reason FROM forummute, forummutereason WHERE forummute.id = forummutereason.id && forummute.forumid = #", $fid);

	$muterows = array();
	$uids = array();
	while($line = $forums->db->fetchrow()){
		$muterows[] = $line;
		$uids[$line['userid']] = $line['userid'];
		$uids[$line['modid']] = $line['modid'];
	}

//get usernames of users and mods, also used to delete/unmute users that don't exist anymore
	$userrows = array();
	if(count($uids)){
		$db->prepare_query("SELECT userid, username FROM users WHERE userid IN (#)", $uids);

		while($line = $db->fetchrow())
			$userrows[$line['userid']] = $line['username'];
	}

	$unmute = array();
	$delete = array();
	$time = time();

//join the two tables, keep track of those to unmute
	$rows = array();
	foreach($muterows as $line){
		if(!isset($userrows[$line['userid']])){ //user deleted
			$delete[] = $line['userid'];
		}elseif($line['unmutetime'] && $line['unmutetime'] < $time){ //exists, but should be unmuted
			$unban[] = $line['userid'];
			$delete[] = $line['userid'];
		}else{
			$rows[$userrows[$line['userid']]] = $line;
			$rows[$userrows[$line['userid']]]['username'] = $userrows[$line['userid']];
			if(isset($userrows[$line['modid']]))
				$rows[$userrows[$line['userid']]]['modname'] = $userrows[$line['modid']];
		}
	}

//unmute them, log those where the accounts still exist and uncache the mutes
	if(count($delete))
		$forums->db->prepare_query("DELETE FROM forummute WHERE userid IN (#) && forumid = #", $delete, $fid);

	if(count($unmute)){
		foreach($unmute as $uid){
			$forums->modLog('unmute', $fid, 0, $uid, $rows[$uid]['unmutetime'], 0);
			$cache->remove(array($uid, "forummutes-$uid-$fid"));
		}
	}


	uksort($rows, 'strcasecmp');

	incHeader();

	echo "<table>";

	echo "<tr><td class=body colspan=6>";
	if($forumdata['official']=='y')
		echo "<a class=body href=forums.php>Forums</a> > ";
	else
		echo "<a class=body href=forumsusercreated.php>User Created Forums</a> > ";

	if($fid != 0)
		echo "<a class=body href=forumthreads.php?fid=$fid>$forumdata[name]</a> > ";
	echo "<a class=body href=$_SERVER[PHP_SELF]?fid=$fid>Mute Users</a>";
	echo "</td></tr>";

	echo "<tr>";
	echo "<td class=header></td>";
	echo "<td class=header>Username</td>";
	echo "<td class=header>Mute Time</td>";
	echo "<td class=header>UnMute Time</td>";
	echo "<td class=header>Moderator</td>";
	echo "<td class=header>Category</td>";
	echo "<td class=header>Reason</td>";
	echo "</tr>";



	foreach($rows as $line){
		echo "<tr>";
		echo "<td class=body><a class=body href=$_SERVER[PHP_SELF]?action=delete&uid=$line[userid]&fid=$fid><img src=$config[imageloc]delete.gif border=0></a></td>";
		echo "<td class=body><a class=body href=profile.php?uid=$line[userid]>$line[username]</a></td>";
		echo "<td class=body nowrap>" . ($line['mutetime'] ? userdate("F j, Y, g:i a", $line['mutetime']) : "Unknown") . "</td>";
		echo "<td class=body nowrap>" . ($line['unmutetime'] ? userdate("F j, Y, g:i a", $line['unmutetime']) : "Indefinitely") . "</td>";
		echo "<td class=body>" . (isset($line['modname']) ? "<a class=body href=profile.php?uid=$line[modid]>$line[modname]</a>" : "(deleted)") . "</td>";
		echo "<td class=body nowrap>" . ($line['reasonid'] ? $forums->reasons[$line['reasonid']] : "Unknown") . "</td>";
		echo "<td class=body>$line[reason]</td>";
		echo "</tr>";
	}

	echo "</table>";
	echo "<br>";

	echo "<table><form action=$_SERVER[PHP_SELF] method=post>";
	echo "<tr><td class=header colspan=5>Mute user</td></tr>";
	echo "<tr><td class=body>Username:</td><td class=body><input class=body type=text name=username></td></tr>";
	echo "<tr><td class=body>Length:</td><td class=body><select class=body name=time>" . make_select_list_key($forums->mutelength) . "</select></td></tr>";
	echo "<tr><td class=body>Category:</td><td class=body><select class=body name=reasonid><option value=0>Category" . make_select_list_key($forums->reasons) . "</select></td></tr>";
	echo "<tr><td class=body>Reason:</td><td class=body><input class=body type=text name=reason size=50 maxlength=255></td></tr>";
	echo "<input type=hidden name=fid value=$fid>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=Mute>";
	echo "</td></tr>";
	echo "</form></table>";

	incFooter();

