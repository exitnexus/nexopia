<?

	$login=0;
	$userprefs = array('friendslistthumbs', 'enablecomments', 'journalentries' ,'gallery', 'hideprofile', 'premiumexpiry');

	require_once("include/general.lib.php");

	$sortlist = array(  'friends.id' => "",
						'users.userid' => "",
						'username' => "users.username",
						'age' => "age",
						'sex' => "sex",
						'loc' => "loc",
						'online' => "online",
						'firstpic' => "",
						'friendscomments.comment' => ""
						);

	$uid = getREQval('uid', 'int', ($userData['loggedIn'] ? $userData['userid'] : 0));

	if(empty($uid)){
		header("location: /login.php?referer=" . urlencode($REQUEST_URI));
		exit;
	}

	$mode = getREQval('mode', 'int', 1);
	if($mode != 2 || !$userData['loggedIn'] || $uid!=$userData['userid'])
		$mode=1;

	$multiplyer = 1;
	if($userData['loggedIn'] && $uid == $userData['userid'] && $userData['premium'])
		$multiplyer = 2;


	if($userData['loggedIn'] && $userData['userid']==$uid){// && $mode==1){
		switch($action){
			case "add":
				if(!($id = getREQval('id', 'int')) || $mode == 2)
					break;

				if(!checkKey($id, getREQval('k')))
					break;

				$uid = $userData['userid'];

				$db->prepare_query("SELECT count(*) FROM friends WHERE userid = ?", $userData['userid']);
				$count = $db->fetchfield();

				if($count >= $config['maxfriends'] * $multiplyer ){
					$msgs->addMsg("You have the reached the maximum amount of friends allowed, which is currently set at $config[maxfriends] or " . ($config['maxfriends']*$multiplyer) . " for plus users.");
					break;
				}

				$db->prepare_query("SELECT premiumexpiry, friendsauthorization FROM users WHERE userid = #", $id);
				$line = $db->fetchrow();

				if(!$line){
					$msgs->addMsg("That user does not exist");
					break;
				}

				if(isIgnored($id, $userData['userid'], '', 0, true)){
					$msgs->addMsg("You may not add that user to your friends list.");
					break;
				}

				$db->prepare_query("INSERT IGNORE INTO friends SET userid = #, friendid = #", $userData['userid'], $id);

				if($db->affectedrows() == 0){
					$msgs->addMsg("He/She is already on your friends list");
				}else{
					$msgs->addMsg("Friend has been added to your friends list.");

					if($line['premiumexpiry'] > time() && $line['friendsauthorization'] == 'y')
						$messaging->deliverMsg($id, "Friends List Notification", "[user]$userData[username]" . "[/user] has added you to " . ($userData['sex'] == 'Male' ? "his" : "her") . " friends list. You may remove yourself by clicking [url=/friends.php?action=delete&mode=2&id=$userData[userid]]here[/url], or add " . ($userData['sex'] == 'Male' ? "him" : "her") . " to yours by clicking [url=/friends.php?action=add&id=$userData[userid]&k=" . makekey($userData['userid'], $id) . "]here[/url].");
				}

				$cache->remove(array($userData['userid'], "friends-$userData[userid]"));

				break;

			case "delete":
				if(($id = getREQval('id', 'int')) && checkKey($id, getREQval('k'))){
					if($mode == 1){
						$db->prepare_query("DELETE FROM friends WHERE userid = # && friendid = #",$userData['userid'], $id);

						if($db->affectedrows()){
							$db->prepare_query("SELECT premiumexpiry, friendsauthorization FROM users WHERE userid = #", $id);
							$line = $db->fetchrow();

							if($line['premiumexpiry'] > time() && $line['friendsauthorization'] == 'y')
								$messaging->deliverMsg($id, "Friends List Notification", "[user]$userData[username]" . "[/user] has removed you from " . ($userData['sex'] == 'Male' ? "his" : "her") . " friends list. You may remove " . ($userData['sex'] == 'Male' ? "him" : "her") . " from yours by clicking [url=/friends.php?action=delete&id=$userData[userid]]Here[/url]");
						}

						$cache->remove(array($userData['userid'], "friends-$userData[userid]"));
					}elseif($userData['premium']){
						$db->prepare_query("DELETE FROM friends WHERE userid = # && friendid = #", $id, $userData['userid']);

						if($db->affectedrows()){
							$db->prepare_query("SELECT premiumexpiry, friendsauthorization FROM users WHERE userid = ?", $id);
							$line = $db->fetchrow();

							if($line['premiumexpiry'] > time() && $line['friendsauthorization'] == 'y')
								$messaging->deliverMsg($id, "Friends List Notification", "[user]$userData[username]" . "[/user] has removed " . ($userData['sex'] == 'Male' ? "himself" : "herself") . " from your friends list.");
						}

						$cache->remove(array($id, "friends-$id"));
					}

					$msgs->addMsg("Friend Deleted");
				}
				break;
			case "update":
				if(($id = getREQval('id', 'int')) && getREQval('comment', 'bool') && $mode==1){ //bool to check that it exists, so that updating to '' works
					$comment = getREQval('comment');
					$db->prepare_query("SELECT id FROM friends WHERE userid = # && friendid = #", $userData['userid'], $id);
					if($db->numrows() == 0)
						break;

					$commentid = $db->fetchfield();

					if($comment==""){
						$db->prepare_query("DELETE FROM friendscomments WHERE id = #", $commentid);
					}else{
						$db->prepare_query("UPDATE friendscomments SET comment = ? WHERE id = #", removeHTML($comment), $commentid);
						if($db->affectedrows()==0)
							$db->prepare_query("INSERT IGNORE INTO friendscomments SET comment = ?, id = #", removeHTML($comment), $commentid);
					}
					$msgs->addMsg("Comment updated");
				}
				break;
		}
	}

	if($userData['loggedIn'] && $uid == $userData['userid']){
		$user = $userData;
	}else{
		$db->prepare_query("SELECT userid, username, enablecomments, journalentries, gallery, hideprofile, premiumexpiry, frozen FROM users WHERE userid = #", $uid);
		if($db->numrows()==0)
			die("User Doesn't exist");
		$user = $db->fetchrow();

		if($user['frozen'] == 'y' && !$mods->isAdmin($userData['userid'], 'listusers'))
			die("Bad user");
	}

	$user['plus'] = $user['premiumexpiry'] > time();

	if($user['plus'] && $user['hideprofile'] && isIgnored($uid, $userData['userid'], '', 0, true)){
		incHeader();

		echo "This user is ignoring you.";

		incFooter();
		exit;
	}

	if($mode == 1)
		$db->prepare_query("SELECT friends.id, users.userid, users.username, age, sex, loc, online, firstpic, friendscomments.comment, mutual.id IS NOT NULL as mutual FROM users, friends LEFT JOIN friendscomments ON friends.id=friendscomments.id LEFT JOIN friends AS mutual ON friends.friendid=mutual.userid && mutual.friendid = # WHERE friends.userid = # && friends.friendid=users.userid", $uid, $uid);
	else
		$db->prepare_query("SELECT friends.id, users.userid, users.username, age, sex, loc, online, firstpic, friendscomments.comment, mutual.id IS NOT NULL as mutual FROM users, friends LEFT JOIN friendscomments ON friends.id=friendscomments.id LEFT JOIN friends AS mutual ON friends.userid=mutual.friendid && mutual.userid = # WHERE friends.friendid = # && friends.userid=users.userid", $uid, $uid);

	$rows = array();
	while($line = $db->fetchrow())
		$rows[] = $line;

	sortCols($rows, SORT_ASC, SORT_CASESTR, 'username', SORT_DESC, SORT_STRING, 'online');

	$locations = & new category( $db, "locs");

	if($userData['loggedIn'])	$minage = $userData['defaultminage'];
	else						$minage = 14;

	if($userData['loggedIn'])	$maxage = $userData['defaultmaxage'];
	else						$maxage = 30;

	if($userData['loggedIn']){
		$sexes = $userData['defaultsex'];
		if($sexes == 'Male') 	$sex = 'm';
		else					$sex = 'f';
	}else{
		$sexes = array("Male","Female");
		$sex = 'b';
	}

	incHeader(0,array('incSortBlock','incSkyAdBlock','incTopGirls','incTopGuys','incNewestMembersBlock'));

	$cols=6;
	$showthumbs = false;
	if($userData['loggedIn']){
		if($userData['userid']==$uid)
			$cols++;
		if($userData['friendslistthumbs'] == 'y'){
			$cols++;
			$showthumbs = true;
		}
	}



	echo "<table width=600 align=center cellspacing=1 cellpadding=3>\n";


	$isFriend = $userData['loggedIn'] && ($userData['userid']==$user['userid'] || isFriend($userData['userid'],$user['userid']));

	$cols2=2;
	if($user['enablecomments']=='y')
		$cols2++;
	if(	$user['journalentries'] == WEBLOG_PUBLIC ||
		($user['journalentries'] == WEBLOG_LOGGEDIN && $userData['loggedIn']) ||
		($user['journalentries'] == WEBLOG_FRIENDS && $isFriend))
		$cols2++;
	if($user['gallery']=='anyone' || ($user['gallery']=='loggedin' && $userData['loggedIn']) || ($user['gallery']=='friends' && $isFriend))
		$cols2++;

	$width = 100.0/$cols2;

	echo "<tr>";
	echo "<td class=header2 colspan=$cols>";
	echo "<table width=100%>";
	echo "<td class=header align=center width=$width%><a class=header href=\"profile.php?uid=$user[userid]\"><b>Profile</b></a></td>";
	if($user['enablecomments']=='y')
		echo "<td class=header align=center width=$width%><a class=header href=\"usercomments.php?id=$user[userid]\"><b>Comments</b></a></td>";
	if($user['gallery']=='anyone' || ($user['gallery']=='loggedin' && $userData['loggedIn']) || ($user['gallery']=='friends' && $isFriend))
		echo "<td class=header align=center width=$width%><a class=header href=\"gallery.php?uid=$user[userid]\"><b>Gallery</b></a></td>";
	if(	$user['journalentries'] == WEBLOG_PUBLIC ||
		($user['journalentries'] == WEBLOG_LOGGEDIN && $userData['loggedIn']) ||
		($user['journalentries'] == WEBLOG_FRIENDS && $isFriend))
		echo "<td class=header align=center width=$width%><a class=header href=weblog.php?uid=$user[userid]><b>Blog</a></td>";
	echo "<td class=header align=center width=$width%><a class=header href=\"friends.php?uid=$user[userid]\"><b>Friends</b></a></td></tr>";
	echo "</table>";
	echo "</td></tr>";


	if($userData['loggedIn'] && $uid==$userData['userid']){// && $userData['premium']){
		if($mode==1)
			echo "<tr><td class=body colspan=$cols><table cellpadding=0 cellspacing=0 width=100%><tr><td class=body>People on $user[username]'s friends list:</td><td class=body align=right><a class=body href=$_SERVER[PHP_SELF]?uid=$uid&mode=2>Who added $user[username] to their friends list?</a></td></tr></table></td></tr>";
		else
			echo "<tr><td class=body colspan=$cols><table cellpadding=0 cellspacing=0 width=100%><tr><td class=body>People who've added $user[username] to their friends list:</td><td class=body align=right><a class=body href=$_SERVER[PHP_SELF]?uid=$uid&mode=1>Back to $user[username]'s friends list</a></td></tr></table></td></tr>";
	}

	echo "<tr>";
		if($showthumbs)
			echo "<td class=header width=$config[thumbWidth]>&nbsp;</td>";
		echo "<td class=header>Username</td>";
		echo "<td class=header align=center>Age</td>";
		echo "<td class=header align=center>Sex</td>";
		echo "<td class=header>Location</td>";
		echo "<td class=header align=center>Online</td>";
		echo "<td class=header align=center>Mutual</td>";
		if($userData['loggedIn'] && $userData['userid']==$uid)// && $mode==1)
			echo "<td class=header>&nbsp;</td>";
	echo "</tr>\n";

	echo "<script>function editcomment(str,id){comment = prompt('New comment:',str); if(comment != null){ location.href='$_SERVER[PHP_SELF]?id='+ id + '&action=update&comment=' + comment;} }</script>";

	foreach($rows as $line){
		echo "<tr>";
			if($showthumbs){
				if($line['firstpic']>0)
					echo "<td class=body rowspan=2><a class=body href='profile.php?uid=$line[userid]'><img src=http://" . chooseImageServer($line['firstpic']) . $config['thumbdir'] . floor($line['firstpic']/1000) . "/$line[firstpic].jpg border=0></td>";
				else
					echo "<td class=body rowspan=2>No pic</td>";
			}
			echo "<td class=body height=25><a class=body href='profile.php?uid=$line[userid]'>$line[username]</a></td>";
			echo "<td class=body align=center>$line[age]</td>";
			echo "<td class=body align=center>$line[sex]</td>";
			echo "<td class=body>" . $locations->getCatName($line['loc']) . "</td>";
			echo "<td class=body align=center>" . ($line['online'] == 'y' ? "<b>Online</b>" : "" ) . "</td>";
			echo "<td class=body align=center>" . ($line['mutual'] ? "Mutual" : "" ) . "</td>";
			if($userData['loggedIn'] && $userData['userid']==$uid){
				echo "<td class=body align=center>";

				if($mode==1)
					echo "<a class=body href=\"javascript: editcomment('" . (strpos($line['comment'],"'")===false && strpos($line['comment'],'"')===false ? $line['comment'] : ""  ) . "',$line[userid]); \"><img src=$config[imageloc]edit.gif border=0></a>";
				if($mode==1 || ($userData['loggedIn'] && $userData['premium']))
					echo "<a class=body href='$_SERVER[PHP_SELF]?id=$line[userid]&action=delete&mode=$mode&k=" . makekey($line['userid']) . "'><img src=$config[imageloc]delete.gif border=0></a>";
				echo "</td>";
			}
		echo "</tr>";
		echo "<tr>";
			echo "<td class=body></td><td class=body colspan=6 valign=top>$line[comment]</td>";
		echo "</tr>\n";
	}

	if($mode == 1)
		echo "<tr><td class=body colspan=$cols>You have " . count($rows) . " friends on your friends list. The maximum is " . ($config['maxfriends']*$multiplyer) . ".</td></tr>";
	else
		echo "<tr><td class=body colspan=$cols>" . count($rows) . " people have added you to their friends list.</td></tr>";
	echo "</form></table>\n";

	incFooter();


