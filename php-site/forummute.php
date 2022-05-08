<?

	$login=1;

	require_once("include/general.lib.php");

	if(!isset($fid) || !is_numeric($fid))
		die("Bad Forum id");

	if($fid){
		$perms = getForumPerms($fid,array('name','official'));	//checks it's a forum, not a realm

		if(!$perms['mute'])
			die("You don't have permission to mute people in this forum");

		$forumdata = $perms['cols'];
	}else{ //fid = 0
		if(!$mods->isAdmin($userData['userid'],'forums')){
			$db->prepare_query("SELECT mute FROM forummods WHERE userid = ? && forumid = 0", $userData['userid']);

			if($db->numrows() == 0 || $db->fetchfield() == 'n')
				die("You don't have permission to mute people in this forum");
		}

		$forumdata = array("name" => "Global", 'official' => 'y');
	}

	switch($action){
		case "Mute":
			if(trim($username) == "")
				break;

			$uid = getUserId($username);

			forumMuteUser($uid,$fid,$time,$reason);

			break;
		case "delete":
			forumUnmuteUser($uid,$fid);

			break;
	}

	$db->prepare_query("SELECT 	forummute.id,
								forummute.userid,
								muteuser.username,
								mutetime,
								unmutetime,
								forummutereason.modid,
								moduser.username as modname,
								reason
						FROM 	forummute,
								forummutereason,
								users as muteuser
							LEFT JOIN users as moduser ON moduser.userid = forummutereason.modid
						WHERE 	forummute.id = forummutereason.id &&
								muteuser.userid = forummute.userid &&
								forummute.forumid = ?
						ORDER BY username", $fid);

	$rows = array();
	while($line = $db->fetchrow())
		$rows[] = $line;

	incHeader();

	echo "<table>";

	echo "<tr><td class=body colspan=6>";
	if($forumdata['official']=='y')
		echo "<a class=body href=forums.php>Forums</a> > ";
	else
		echo "<a class=body href=forumsusercreated.php>User Created Forums</a> > ";

	if($fid != 0)
		echo "<a class=body href=forumthreads.php?fid=$fid>$forumdata[name]</a> > ";
	echo "<a class=body href=$PHP_SELF?fid=$fid>Mute Users</a>";
	echo "</td></tr>";

	echo "<tr>";
	echo "<td class=header></td>";
	echo "<td class=header>Username</td>";
	echo "<td class=header>Mute Time</td>";
	echo "<td class=header>UnMute Time</td>";
	echo "<td class=header>Moderator</td>";
	echo "<td class=header>Reason</td>";
	echo "</tr>";

	foreach($rows as $line){
		echo "<tr>";
		echo "<td class=body><a class=body href=$PHP_SELF?action=delete&uid=$line[userid]&fid=$fid><img src=/images/delete.gif border=0></a></td>";
		echo "<td class=body><a href=profile.php?uid=$line[userid]>$line[username]</a></td>";
		echo "<td class=body nowrap>" . ($line['mutetime']==0 ? "Unknown" : userdate("F j, Y, g:i a",$line['mutetime'])) . "</td>";
		echo "<td class=body nowrap>" . ($line['unmutetime']==0 ? "Indefinately" : userdate("F j, Y, g:i a",$line['unmutetime'])) . "</td>";
		echo "<td class=body><a href=profile.php?uid=$line[modid]>$line[modname]</a></td>";
		echo "<td class=body>$line[reason]</td>";
		echo "</tr>";
	}

	echo "</table>";
	echo "<br>";

	echo "<table><form action=$PHP_SELF>";
	echo "<tr><td class=header colspan=5>Mute user</td></tr>";
	echo "<tr><td class=body>Username:</td><td class=body><input class=body type=text name=username></td></tr>";
	echo "<tr><td class=body>Length:</td><td class=body><select class=body name=time>" . make_select_list_key($mutelength) . "</select></td></tr>";
	echo "<tr><td class=body>Reason:</td><td class=body><input class=body type=text name=reason size=50 maxlength=255></td></tr>";
	echo "<input type=hidden name=fid value=$fid>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=Mute></td></tr>";
	echo "</form></table>";

	incFooter();
