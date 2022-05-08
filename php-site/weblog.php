<?

	$login=0;
	$userprefs = array('enablecomments', 'journalentries', 'gallery');

	require_once("include/general.lib.php");

	$uid = getREQval('uid', 'int', ($userData['loggedIn'] ? $userData['userid'] : die("Bad user")));

	$page = getREQval('page', 'int');

	if($userData['loggedIn'] && $userData['userid'] == $uid){
		$user = $userData;
		$isFriend = true;
		$isAdmin = true;
	}else{
		$db->prepare_query("SELECT username, enablecomments, journalentries, gallery, hideprofile, premiumexpiry, frozen FROM users WHERE userid = #", $uid);

		if($db->numrows() == 0)
			die("Bad user");

		$user = $db->fetchrow();

		if($user['frozen'] == 'y' && !$mods->isAdmin($userData['userid'], 'listusers'))
			die("Bad user");

		$isAdmin = $userData['loggedIn'] && $mods->isAdmin($userData['userid'], 'editjournal');

		$user['plus'] = $user['premiumexpiry'] > time();

		if($user['plus'] && $user['hideprofile'] == 'y' && isIgnored($uid, $userData['userid'], false, 0, true)){
			incHeader();

			echo "This user is ignoring you.";

			incFooter();
			exit;
		}

		$isFriend = $isAdmin || ($userData['loggedIn'] && isFriend($userData['userid'], $uid));
	}

	$scope = WEBLOG_PUBLIC;
	if($userData['loggedIn'])
		$scope = WEBLOG_LOGGEDIN;
	if($isFriend)
		$scope = WEBLOG_FRIENDS;
	if($userData['loggedIn'] && $uid == $userData['userid'])
		$scope = WEBLOG_PRIVATE;


	$id = getREQval('id', 'int');

	if($action == "Delete" && ($uid == $userData['userid'] || $isAdmin) && $id){
		if($check = getPOSTval('checkID', 'array'))
			$weblog->deleteComments($uid, $id, $check);
	}

	if($id)	showEntry($uid, $id);
	else	listEntries($uid);


function listEntries($uid){
	global $weblog, $scope, $page, $user, $userData, $isFriend;

	$rows = $weblog->getEntries($uid, $scope, $page);

	$lastpage = (count($rows) < $weblog->entriesPerPage);

	incHeader(0,array('incTextAdBlock','incSortBlock','incTopGirls','incTopGuys','incNewestMembersBlock'));

	echo "<table width=100% cellpadding=3>";

	$cols=2;
	if($user['enablecomments']=='y')
		$cols++;
	if($user['journalentries'] <= $scope)
		$cols++;
	if($user['gallery']=='anyone' || ($user['gallery']=='loggedin' && $userData['loggedIn']) || ($user['gallery']=='friends' && $isFriend))
		$cols++;

	$width = 100.0/$cols;

	echo "<tr>";
	echo "<td class=header2 colspan=3>";
	echo "<table width=100%>";
	echo "<td class=header align=center width=$width%><a class=header href=\"profile.php?uid=$uid\"><b>Profile</b></a></td>";
	if($user['enablecomments']=='y')
		echo "<td class=header align=center width=$width%><a class=header href=\"usercomments.php?id=$uid\"><b>Comments</b></a></td>";
	if($user['gallery']=='anyone' || ($user['gallery']=='loggedin' && $userData['loggedIn']) || ($user['gallery']=='friends' && $isFriend))
		echo "<td class=header align=center width=$width%><a class=header href=\"gallery.php?uid=$uid\"><b>Gallery</b></a></td>";
	if($user['journalentries'] <= $scope)
		echo "<td class=header align=center width=$width%><a class=header href=weblog.php?uid=$uid><b>Blog</a></td>";
	echo "<td class=header align=center width=$width%><a class=header href=\"friends.php?uid=$uid\"><b>Friends</b></a></td></tr>";
	echo "</table>";
	echo "</td></tr>";


	echo "<tr><td class=header colspan=$cols>";
	echo "<table width=100%><tr><td class=header>";

	echo "<a class=header href=profile.php?uid=$uid>$user[username]'s Profile</a> > <a class=header href=$_SERVER[PHP_SELF]?uid=$uid>$user[username]'s Blog</a>";

	echo "</td>";

	echo "<td class=header align=right>";

//	echo "Subscribe";

	echo "</td></tr></table></td></tr>";


	foreach($rows as $line){
		echo "<tr>";
		echo "<td class=header><a class=header href=$_SERVER[PHP_SELF]?uid=$uid&id=$line[id]>$line[title]</a></td>";
		echo "<td class=header align=right>" . userdate("M j, Y G:i:s", $line['time']) . "</td>";
		echo "</tr>";

		echo "<td class=body colspan=2>";

		echo $line['nmsg'] . "&nbsp;<br><br>";

		echo "[ <a class=body href=$_SERVER[PHP_SELF]?uid=$uid&id=$line[id]>" . ($line['comments'] ? $line['comments'] : "No") . " Comment" . ($line['comments'] == 1 ? "" : "s") . "</a> ]";

		echo "</td></tr>";
		echo "<tr><td colspan=2 class=header2>&nbsp;</td></tr>";
	}
	echo "<tr><td class=header colspan=2 align=right>";

	if($page){
		if($page - 1 > 0)
			echo "<a class=header href=$_SERVER[PHP_SELF]?uid=$uid&page=0>First</a> | ";
		echo "<a class=header href=$_SERVER[PHP_SELF]?uid=$uid&page=" . ($page - 1) . ">Prev</a> | ";
	}

	echo "Page " . ($page + 1);

	if(!$lastpage)
		echo " | <a class=header href=$_SERVER[PHP_SELF]?uid=$uid&page=" . ($page + 1) . ">Next</a>";

	echo "</td></tr>";

	echo "</table>\n";

	incFooter();
	exit;
}

function showEntry($uid, $id){
	global $weblog, $scope, $user, $userData, $action, $isAdmin, $db, $config, $isFriend;


	$entry = $weblog->getEntry($id);

	if(!$entry || $entry['scope'] > $scope || $entry['userid'] != $uid)
		die("Bad Entry");

	if(($action == 'Post' || $action == 'Preview') && ($msg = getPOSTval('msg')) && !isIgnored($uid, $userData['userid'], 'comments', $userData['age']))
		$weblog->addComment($id, $userData['userid'], $userData['username'], $msg);


	$comments = $weblog->getComments($id);

	$authorids = array();
	$authors = array();

	if(count($comments)){
		foreach($comments as $line)
			$authorids[$line['userid']] = $line['userid'];

		$db->prepare_query("SELECT userid, age, firstpic, online FROM users WHERE userid IN (#)", $authorids);

		while($line = $db->fetchrow())
			$authors[$line['userid']] = $line;
	}


	incHeader(0, array('incTextAdBlock','incSortBlock','incTopGirls','incTopGuys','incNewestMembersBlock'));

	echo "<table width=100% cellpadding=3>";

	$cols=2;
	if($user['enablecomments']=='y')
		$cols++;
	if($user['journalentries'] <= $scope)
		$cols++;
	if($user['gallery']=='anyone' || ($user['gallery']=='loggedin' && $userData['loggedIn']) || ($user['gallery']=='friends' && $isFriend))
		$cols++;

	$width = 100.0/$cols;

	echo "<tr>";
	echo "<td class=header2 colspan=3>";
	echo "<table width=100%>";
	echo "<td class=header align=center width=$width%><a class=header href=\"profile.php?uid=$uid\"><b>Profile</b></a></td>";
	if($user['enablecomments']=='y')
		echo "<td class=header align=center width=$width%><a class=header href=\"usercomments.php?id=$uid\"><b>Comments</b></a></td>";
	if($user['gallery']=='anyone' || ($user['gallery']=='loggedin' && $userData['loggedIn']) || ($user['gallery']=='friends' && $isFriend))
		echo "<td class=header align=center width=$width%><a class=header href=\"gallery.php?uid=$uid\"><b>Gallery</b></a></td>";
	if($user['journalentries'] <= $scope)
		echo "<td class=header align=center width=$width%><a class=header href=weblog.php?uid=$uid><b>Blog</a></td>";
	echo "<td class=header align=center width=$width%><a class=header href=\"friends.php?uid=$uid\"><b>Friends</b></a></td></tr>";
	echo "</table>";
	echo "</td></tr>";


	echo "<tr><td class=header colspan=$cols>";
	echo "<table width=100%><tr><td class=header>";

	echo "<a class=header href=profile.php?uid=$uid>$user[username]'s Profile</a> > ";
	echo "<a class=header href=$_SERVER[PHP_SELF]?uid=$uid>$user[username]'s Blog</a> > ";
	echo "<a class=header href=$_SERVER[PHP_SELF]?uid=$uid&id=$id>$entry[title]</a>";

	echo "</td>";

	echo "<td class=header align=right>";

	echo userdate("M j, Y G:i:s", $entry['time']);

	echo "</td></tr></table></td></tr>";

	echo "<td class=body colspan=2>";

	echo $entry['nmsg'] . "&nbsp;<br><br>";

	echo "</td></tr>";

	if($entry['allowcomments'] == 'y'){

		echo "<tr><td colspan=3 class=header2>&nbsp;</td></tr>";

		if(count($comments)){
			echo "<tr><td class=header colspan=2>Comments:</td></tr>";

			if($uid == $userData['userid'] || $isAdmin){
				echo "<form action=$_SERVER[PHP_SELF] method=post>";
				echo "<input type=hidden name=id value='$id'>";
				echo "<input type=hidden name=uid value=$uid>";
			}

			foreach($comments as $line){
				echo "<tr>";

				echo "<td class=body valign=top nowrap>";

				if(isset($authors[$line['userid']])){
					echo "<a class=body href=profile.php?uid=$line[userid]><b>$line[username]</b></a>";
					$author = $authors[$line['userid']];
				}else{
					$line['userid'] = 0;
					echo "<b>$line[username]</b><br>deleted account";
				}

				echo "<br>";

				if($line['userid'] && $author['online'] == 'y')
					echo "- Online -<br>";

				if($line['userid'] && $author['firstpic'])
					echo "<a class=header href=profile.php?uid=$line[userid]><img src=http://" . chooseImageServer($author['firstpic']) . $config['thumbdir'] . floor($author['firstpic']/1000) . "/$author[firstpic].jpg border=0></a><br>";

				if($line['userid'])
					echo "<br>Age: <i>$author[age]</i><br>";

				echo "</td><td class=body valign=top width=100%>";
				echo $line['nmsg'] . "&nbsp;";

				echo "</td></tr>\n";
				echo "<tr>";

				echo "<td class=small colspan=2>";

				echo "<table width=100% cellspacing=0 cellpadding=0><tr><td class=small>";

				if($isAdmin){
					echo "<input type=checkbox name=checkID[] value=$line[id]>";
					echo "</td><td class=small>";
				}

				echo userdate("l F j, Y, g:i a",$line['time']) ."</td><td align=right class=small>";

				$links = array();

				if($userData['loggedIn'] && $line['userid']){
					$links[] = "<a class=small href=\"profile.php?uid=$line[userid]\">Profile</a>";
					if($userData['userid'] == $id)
						$links[] = "<a class=small href=\"javascript:confirmLink('/messages.php?action=ignore&id=$line[userid]','ignore this user')\">Ignore User</a>";
					$links[] = "<a class=small href=\"messages.php?action=write&to=$line[userid]\">Message</a>";
					$links[] = "<a class=small href=\"usercomments.php?id=$line[userid]\">Comments</a>";
	//				$links[] =  "<a class=small href=reportabuse.php?type=comment&id=$line[id]>Report</a>";
				}

				$links[] =  "<a class=small href=#top>Top</a>";

				echo implode(" &nbsp; &nbsp; ", $links);

				echo "</td></tr></table>";
				echo "</td></tr>";
				echo "<tr><td class=header2 colspan=2 height=2></td></tr>\n";
			}

			if($isAdmin){
				echo "<tr>";
				echo "<td class=header colspan=2>";
				echo "<input class=body name=selectall type=checkbox value='Check All' onClick=\"this.value=check(this.form,'check')\">";
				echo "<input class=body type=submit name=action value=Delete></td></form>";
				echo "</tr>";
			}
		}


		if(!$userData['loggedIn']){
			echo "<tr><td colspan=2 class=header2>You have to be logged in to leave a comment.</td></tr>";
		}elseif(isIgnored($uid, $userData['userid'], 'comments', $userData['age'])){
			echo "<tr><td colspan=2 class=header2>You are ignored, so can't leave a comment.</td></tr>";
		}else{
			echo "<tr><td colspan=2 class=header2>";
			echo "<table cellspacing=0 align=center>";
			echo "<tr><td class=header align=center>Leave a Comment</td></tr>";

			echo "<form action=$_SERVER[PHP_SELF] method=post name=editbox>\n";
			echo "<input type=hidden name=uid value=$uid>\n";
			echo "<input type=hidden name=id value=$id>\n";

			echo "<tr><td class=header2 align=center>";

			editBox("",true);

			echo "</td></tr>\n";
			echo "<tr><td class=header2 align=center><input class=body type=submit name=action accesskey='s' value=Post></td></tr>\n";
			echo "</form>";
			echo "</table>\n";
			echo "</td></tr>";
		}
	}else{
		echo "<tr><td colspan=2 class=header2>Comments are disabled for this entry.</td></tr>";
	}

	echo "</table>\n";

	incFooter();
	exit;
}

