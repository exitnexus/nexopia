<?

	$login=0;

	require_once("include/general.lib.php");

	$sortlist = array(  'friends.id' => "",
						'users.userid' => "",
						'username' => "users.username",
						'dob' => "dob",
						'sex' => "sex",
						'loc' => "loc",
						'online' => "online",
						'firstpic' => "",
						'friendscomments.comment' => ""
						);


	if(empty($uid)){
		if($userData['loggedIn']){
			$uid=$userData['userid'];
		}else{
			header("location: /login.php?referer=" . urlencode($REQUEST_URI));
			exit;
		}
	}

	if(!isset($mode) || $mode!=2 || !$userData['loggedIn'] || $uid!=$userData['userid'])  // || !$userData['premium'])
		$mode=1;

	if($userData['loggedIn'] && $userData['userid']==$uid){// && $mode==1){
		switch($action){
			case "add":
				if(empty($id) || $mode == 2)
					break;
				$uid = $userData['userid'];

				$db->prepare_query("SELECT count(*) FROM friends WHERE userid = ?", $userData['userid']);
				$count = $db->fetchfield();

				if($count >= $config['maxfriends']){
					$msgs->addMsg("You have the reached the maximum amount of friends allowed, which is currently set at $config[maxfriends].");
					break;
				}

				$db->prepare_query("SELECT premiumexpiry, friendsauthorization FROM users WHERE userid = ?", $id);
				$line = $db->fetchrow();

				if(!$line){
					$msgs->addMsg("That user does not exist");
					break;
				}

				$db->prepare_query("INSERT IGNORE INTO friends SET userid = ?, friendid = ?", $userData['userid'], $id);

				if($db->affectedrows() == 0)
					$msgs->addMsg("He/She is already on your friends list");
				else
					$msgs->addMsg("Friend has been added to your friends list.");

				if($line['premiumexpiry'] > time() && $line['friendsauthorization'] == 'y'){
					deliverMsg($id, "Friends List Notification", "$userData[username] has added you to " . ($userData['sex'] == 'Male' ? "his" : "her") . " friends list. You may remove yourself by clicking [url=/friends.php?action=delete&id=$userData[userid]]Here[/url].");
				}

				break;

			case "delete":
				if(!empty($id)){
					if($mode == 1){
						$db->prepare_query("DELETE FROM friends WHERE userid = ? && friendid = ?",$userData['userid'], $id);

						$db->prepare_query("SELECT premiumexpiry, friendsauthorization FROM users WHERE userid = ?", $id);
						$line = $db->fetchrow();

						if($line['premiumexpiry'] > time() && $line['friendsauthorization'] == 'y'){
							deliverMsg($id, "Friends List Notification", "$userData[username] has removed you from " . ($userData['sex'] == 'Male' ? "his" : "her") . " friends list.");
						}
					}elseif($userData['premium']){
						$db->prepare_query("DELETE FROM friends WHERE userid = ? && friendid = ?", $id, $userData['userid']);

						$db->prepare_query("SELECT premiumexpiry, friendsauthorization FROM users WHERE userid = ?", $id);
						$line = $db->fetchrow();

						if($line['premiumexpiry'] > time() && $line['friendsauthorization'] == 'y'){
							deliverMsg($id, "Friends List Notification", "$userData[username] has removed " . ($userData['sex'] == 'Male' ? "himself" : "herself") . " from your friends list.");
						}
					}

					$msgs->addMsg("Friend Deleted");
				}
				break;
			case "update":
				if(isset($comment) && $mode==1){
					$db->prepare_query("SELECT id FROM friends WHERE userid = ? && friendid = ?", $userData['userid'], $id);
					if($db->numrows()==0)
						break;

					$commentid = $db->fetchfield();

					if($comment==""){
						$db->prepare_query("DELETE FROM friendscomments WHERE id = ?", $commentid);
					}else{
						$db->prepare_query("UPDATE friendscomments SET comment = ? WHERE id = ?",removeHTML($comment),$commentid);
						if($db->affectedrows()==0)
							$db->prepare_query("INSERT IGNORE INTO friendscomments SET comment = ?, id = ?", removeHTML($comment), $commentid);
					}
					$msgs->addMsg("Comment updated");
				}
				break;
		}
	}

	$db->prepare_query("SELECT userid,username,enablecomments,journalentries,gallery FROM users WHERE userid = ?", $uid);
	if($db->numrows()==0)
		die("User Doesn't exist");
	$user = $db->fetchrow();

	if($mode==1)
		$db->prepare_query("SELECT " . makeSortSelect($sortlist) . " FROM users,friends LEFT JOIN friendscomments ON friends.id=friendscomments.id WHERE friends.userid = ? && friends.friendid=users.userid", $uid);
	else
		$db->prepare_query("SELECT " . makeSortSelect($sortlist) . " FROM users,friends LEFT JOIN friendscomments ON friends.id=friendscomments.id WHERE friends.friendid = ? && friends.userid=users.userid", $uid);

/*	$rows = array();
	while($line = $db->fetchrow())
		$rows[$line['username']] = $line;

	uksort($rows,'strcasecmp');
*/

	$rows = array();
	while($line = $db->fetchrow())
		$rows[] = $line;

	sortCols($rows, SORT_ASC, SORT_CASESTR, 'username', SORT_DESC, SORT_STRING, 'online');

	$locations = & new category("locs");

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


	$cache->prime(array('online',"top5f:$minage-$maxage","top5m:$minage-$maxage","new5$sex:$minage-$maxage"));

	incHeader(0,array('incTextAdBlock','incSortBlock','incTopGirls','incTopGuys','incNewestMembersBlock'));

	if($userData['loggedIn'] && $userData['userid']==$uid)
		$cols=8;
	else
		$cols=7;

	echo "<table width=600 align=center cellspacing=1 cellpadding=3>\n";


	$isFriend = $userData['loggedIn'] && ($userData['userid']==$user['userid'] || isFriend($userData['userid'],$user['userid']));

	$cols2=2;
	if($user['enablecomments']=='y')
		$cols2++;
	if($user['journalentries'] == 'public' || ($user['journalentries']=='friends' && $isFriend))
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
	if($user['journalentries'] == 'public' || ($user['journalentries']=='friends' && $isFriend))
		echo "<td class=header align=center width=$width%><a class=header href=weblog.php?id=$user[userid]><b>Journal</a></td>";
	echo "<td class=header align=center width=$width%><a class=header href=\"friends.php?uid=$user[userid]\"><b>Friends</b></a></td></tr>";
	echo "</table>";
	echo "</td></tr>";


	if($userData['loggedIn'] && $uid==$userData['userid']){// && $userData['premium']){
		if($mode==1)
			echo "<tr><td class=body colspan=$cols><table cellpadding=0 cellspacing=0 width=100%><tr><td class=body>People on $user[username]'s friends list:</td><td class=body align=right><a class=body href=$PHP_SELF?uid=$uid&mode=2>Who added $user[username] to their friends list?</a></td></tr></table></td></tr>";
		else
			echo "<tr><td class=body colspan=$cols><table cellpadding=0 cellspacing=0 width=100%><tr><td class=body>People who've added $user[username]'s to their friends list:</td><td class=body align=right><a class=body href=$PHP_SELF?uid=$uid&mode=1>Back to $user[username]'s friends list</a></td></tr></table></td></tr>";
	}

	echo "<tr>";
		echo "<td class=header width=$config[thumbWidth]>&nbsp;</td>";
		echo "<td class=header>Username</td>";
		echo "<td class=header align=center>Age</td>";
		echo "<td class=header align=center>Sex</td>";
		echo "<td class=header>Location</td>";
		echo "<td class=header align=center>Online</td>";
		if($userData['loggedIn'] && $userData['userid']==$uid)// && $mode==1)
			echo "<td class=header>&nbsp;</td>";
	echo "</tr>\n";

	echo "<script>function editcomment(str,id){comment = prompt('New comment:',str); if(comment){ location.href='$PHP_SELF?id='+ id + '&action=update&comment=' + comment;} }</script>";

	foreach($rows as $line){
		echo "<tr>";
			if($line['firstpic']>0)
				echo "<td class=body rowspan=2><a class=body href='profile.php?uid=$line[userid]'><img src=$config[thumbloc]" . floor($line['firstpic']/1000) . "/$line[firstpic].jpg border=0></td>";
			else
				echo "<td class=body rowspan=2>No pic</td>";
			echo "<td class=body height=25><a class=body href='profile.php?uid=$line[userid]'>$line[username]</a></td>";
			echo "<td class=body align=center>" . getAge($line['dob']) . "</td>";
			echo "<td class=body align=center>$line[sex]</td>";
			echo "<td class=body>" . $locations->getCatName($line['loc']) . "</td>";
			echo "<td class=body align=center>";
			if($line['online'] == 'y')
				echo "<b>Online</b>";
			echo "</td>";
			if($userData['loggedIn'] && $userData['userid']==$uid){
				echo "<td class=body align=center>";
				if($mode==1)
					echo "<a class=body href=\"javascript: editcomment('" . (strpos($line['comment'],"'")===false && strpos($line['comment'],'"')===false ? $line['comment'] : ""  ) . "',$line[userid]); \"><img src=$config[imageloc]edit.gif border=0></a>";
				if($mode==1 || ($userData['loggedIn'] && $userData['premium']))
					echo "<a class=body href='$PHP_SELF?id=$line[userid]&action=delete&mode=$mode'><img src=$config[imageloc]delete.gif border=0></a>";
				echo "</td>";
			}
		echo "</tr>";
		echo "<tr>";
			echo "<td class=body></td><td class=body colspan=5 valign=top>$line[comment]</td>";
		echo "</tr>\n";
	}

	if($mode == 1)
		echo "<tr><td class=body colspan=$cols>You have " . count($rows) . " friends on your friends list. The maximum is $config[maxfriends].</td></tr>";
	else
		echo "<tr><td class=body colspan=$cols>" . count($rows) . " people have added you to their friends list.</td></tr>";
	echo "</form></table>\n";

	incFooter();


