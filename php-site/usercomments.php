<?

	$login=1;
	$userprefs = array('enablecomments', 'journalentries', 'gallery', 'hideprofile', 'premiumexpiry');

	require_once("include/general.lib.php");

	$uid = getREQval('id', 'int', ($userData['loggedIn'] ? $userData['userid'] : die("Bad User")));

	$isAdmin = false;
	if($userData['loggedIn']){
		if($userData['userid'] == $uid)
			$isAdmin = 1;
		else
			$isAdmin = $mods->isAdmin($userData['userid'], 'deletecomments');
	}

	if($userData['loggedIn'] && $userData['userid'] == $uid){
		$data = $userData;
	}else{
		$db->prepare_query($uid, "SELECT username, enablecomments, journalentries, gallery, hideprofile, premiumexpiry, frozen FROM users WHERE userid = ?", $uid);

		if($db->numrows() == 0)
			die("Bad user");

		$data = $db->fetchrow();

		if($data['frozen'] == 'y' && !$mods->isAdmin($userData['userid'], 'listusers'))
			die("Bad user");
	}

	if($data['enablecomments']=='n'){
		header("location: /profile.php?uid=$uid");
		exit;
	}

	$data['plus'] = $data['premiumexpiry'] > time();

	if($data['plus'] && $data['hideprofile'] == 'y' && isIgnored($uid, $userData['userid'], false, 0, true)){
		incHeader();

		echo "This user is ignoring you.";

		incFooter();
		exit;
	}

	if($userData['loggedIn']){
		switch($action){
			case "Delete":
				if($isAdmin && ($checkID = getPOSTval('checkID', 'array'))){
					$usercomments->db->prepare_query($uid, "DELETE usercomments, usercommentstext FROM usercomments, usercommentstext WHERE usercomments.id=usercommentstext.id && usercomments.id IN (#) && itemid = #", $checkID, $uid);

					$cache->remove(array($uid, "comments5-$uid"));

					if($uid != $userData['userid'])
						$mods->adminlog("delete user comments", "Delete user comments: userid $uid");
				}
				break;

			case "Preview":
				$msg = getPOSTval('msg');
				addUserComment($uid, $msg, true);

			case "Post":
				$msg = getPOSTval('msg');

				if(!empty($msg)){
					if(isIgnored($uid, $userData['userid'], 'comments', $userData['age'])){
						$msgs->addMsg("This user is ignoring you. You cannot leave a comment");
					}else{
						if(!$usercomments->postUserComment($uid, $msg, $userData['userid'], $userData['username']))
							addUserComment($uid, $msg, true);
					}
				}
		}

		if($userData['userid']==$uid && $userData['newcomments']>0){
			$db->prepare_query("UPDATE users SET newcomments='0' WHERE userid = #", $userData['userid']);
			$userData['newcomments']=0;
//			$cache->put(array($userData['userid'], "newcomments-$userData[userid]"), 0, $config['maxAwayTime']);
		}
	}

	listComments();
///////////////////////////////


function listComments(){
	global $usercomments, $userData, $data, $uid, $config, $isAdmin, $db;

	$leftblocks = array('incSortBlock','incSkyAdBlock','incTopGirls','incTopGuys','incNewestMembersBlock');

	$page = getREQval('page', 'int');

//	$usercomments->db->prepare_query($uid, "SELECT SQL_CALC_FOUND_ROWS usercomments.id, author, authorid, usercomments.time, nmsg FROM usercomments, usercommentstext WHERE itemid = #  && usercomments.id = usercommentstext.id ORDER BY id DESC LIMIT " . ($page*$config['linesPerPage']) . ", $config[linesPerPage]", $uid);
	$usercomments->db->prepare_query($uid, "SELECT SQL_CALC_FOUND_ROWS id, author, authorid, time FROM usercomments WHERE itemid = # ORDER BY time DESC LIMIT " . ($page*$config['linesPerPage']) . ", $config[linesPerPage]", $uid);

	$authorids = array();
	$comments = array();
	while($line = $usercomments->db->fetchrow()){
		$comments[$line['id']] = $line;
		$authorids[$line['authorid']] = $line['authorid'];
	}

	$usercomments->db->query($uid, "SELECT FOUND_ROWS()");
	$numrows = $usercomments->db->fetchfield();
	$numpages =  ceil($numrows / $config['linesPerPage']);


	if(count($comments)){
		$usercomments->db->prepare_query($uid, "SELECT id, nmsg FROM usercommentstext WHERE id IN (#)", array_keys($comments));

		while($line = $usercomments->db->fetchrow())
			$comments[$line['id']]['nmsg'] = $line['nmsg'];
	}


	$authors = array();
	if(count($authorids)){
		$db->prepare_query("SELECT userid, age, firstpic, online FROM users WHERE userid IN (#)", $authorids);

		while($line = $db->fetchrow())
			$authors[$line['userid']] = $line;
	}

	incHeader(0,$leftblocks);

	echo "<table width=100% cellpadding=3>";

	$isFriend = $userData['loggedIn'] && ($userData['userid']==$uid || isFriend($userData['userid'], $uid));

	$cols=3;
	if(	$data['journalentries'] == WEBLOG_PUBLIC ||
		($data['journalentries'] == WEBLOG_LOGGEDIN && $userData['loggedIn']) ||
		($data['journalentries'] == WEBLOG_FRIENDS && $isFriend))
		$cols++;
	if($data['gallery']=='anyone' || ($data['gallery']=='loggedin' && $userData['loggedIn']) || ($data['gallery']=='friends' && $isFriend))
		$cols++;

	$width = round(100.0/$cols);

	echo "<tr>";
	echo "<td class=header2 colspan=2>";
	echo "<table width=100%>";
	echo "<td class=header align=center width=$width%><a class=header href=\"profile.php?uid=$uid\"><b>Profile</b></a></td>";
	echo "<td class=header align=center width=$width%><a class=header href=\"usercomments.php?id=$uid\"><b>Comments</b></a></td>";
	if($data['gallery']=='anyone' || ($data['gallery']=='loggedin' && $userData['loggedIn']) || ($data['gallery']=='friends' && $isFriend))
		echo "<td class=header align=center width=$width%><a class=header href=\"gallery.php?uid=$uid\"><b>Gallery</b></a></td>";
	if(	$data['journalentries'] == WEBLOG_PUBLIC ||
		($data['journalentries'] == WEBLOG_LOGGEDIN && $userData['loggedIn']) ||
		($data['journalentries'] == WEBLOG_FRIENDS && $isFriend))
		echo "<td class=header align=center width=$width%><a class=header href=weblog.php?uid=$uid><b>Blog</a></td>";
	echo "<td class=header align=center width=$width%><a class=header href=\"friends.php?uid=$uid\"><b>Friends</b></a></td></tr>";
	echo "</table>";
	echo "</td></tr>";

	echo "<tr><td class=header colspan=2>";
	echo "<table width=100%><tr><td class=header>";

	echo "<a class=header href=profile.php?uid=$uid>$data[username]'s Profile</a> > <a class=header href=$_SERVER[PHP_SELF]?id=$uid>Comments</a>";

	echo "</td>";

	echo "<td class=header align=right>";

	echo "Page:";
	echo pageList("$_SERVER[PHP_SELF]?id=$uid",$page,$numpages,'header');

	echo "</td></tr></table></td></tr>";

	if(count($comments) == 0){
		echo "<tr><td class=body colspan=2 align=center>No Comments</td></tr>";
	}else{
		if($isAdmin){
			echo "<form action=$_SERVER[PHP_SELF] method=post>";
			echo "<input type=hidden name=id value='$uid'>";
		}

		$keys = array();

		foreach($comments as $line){
			echo "<tr>";

			echo "<td class=body valign=top nowrap>";

			if(isset($authors[$line['authorid']])){
				echo "<a class=body href=profile.php?uid=$line[authorid]><b>$line[author]</b></a>";
				$author = $authors[$line['authorid']];
			}else{
				$line['authorid'] = 0;
				echo "<b>$line[author]</b><br>deleted account";
			}

			echo "<br>";

			if($line['authorid'] && $author['online'] == 'y')
				echo "- Online -<br>";

			if($line['authorid'] && $author['firstpic'])
				echo "<a class=header href=profile.php?uid=$line[authorid]><img src=http://" . chooseImageServer($author['firstpic']) . $config['thumbdir'] . floor($author['firstpic']/1000) . "/$author[firstpic].jpg border=0></a><br>";

			if($line['authorid'])
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

			if($userData['loggedIn'] && $line['authorid']){
				$links[] = "<a class=small href=\"profile.php?uid=$line[authorid]\">Profile</a>";
				if($userData['userid'] == $uid){
					if(!isset($keys[$line['authorid']]))
						$keys[$line['authorid']] = makeKey($line['authorid']);
					$links[] = "<a class=small href=\"javascript:confirmLink('/messages.php?action=ignore&id=$line[authorid]&k=" . $keys[$line['authorid']] . "','ignore this user')\">Ignore User</a>";
				}
				$links[] = "<a class=small href=\"messages.php?action=write&to=$line[authorid]\">Message</a>";
				$links[] = "<a class=small href=\"usercomments.php?id=$line[authorid]\">" . ($userData['userid'] == $uid ? "Reply" : "Comments" ) . "</a>";
//				$links[] =  "<a class=small href=reportabuse.php?type=comment&id=$line[id]>Report</a>";
			}

			$links[] =  "<a class=small href=#top>Top</a>"; // <img src=$skindir/forum/top.jpg border=0>

			echo implode(" &nbsp; &nbsp; ", $links);

			echo "</td></tr></table>";
			echo "</td></tr>";
			echo "<tr><td class=header2 colspan=2 height=2></td></tr>\n";


/*
			echo "<tr><td class=header>";

			if($isAdmin)
				echo "<input type=checkbox name=checkID[] value=$line[id]>";

			echo "By: ";

			if($line['authorid'])	echo "<a class=header href=profile.php?uid=$line[authorid]>$line[author]</a>";
			else					echo "$line[author]";

			echo "</td><td class=header>Date: " . userdate("F j, Y, g:i a",$line['time']) . "</td>";
			echo "</tr>";

			echo "<td class=body colspan=2>";

			echo $line['nmsg'] . "&nbsp;";

			echo "</td></tr>";
	//		echo "<tr><td colspan=2 class=header2>&nbsp;</td></tr>";

*/
		}
	}

	echo "<tr><td class=header colspan=2 align=right>";

	if($isAdmin){
		echo "<table width=100%><tr>";
		echo "<td class=header>";
		echo "<input class=body name=selectall type=checkbox value='Check All' onClick=\"this.value=check(this.form,'check')\">";
		echo "<input class=body type=submit name=action value=Delete></td></form>";
		echo "<td class=header align=right>";
	}

	echo "Page:" . pageList("$_SERVER[PHP_SELF]?id=$uid",$page,$numpages,'header');

	if($isAdmin)
		echo "</td></tr></table>";

	echo "</td></tr>";

	if(!$userData['loggedIn']){
		echo "<tr><td class=header2 colspan=2 align=center>You have to be logged in to leave a comment.</td></tr>";
//	}elseif($uid == $userData['userid']){
//		echo "<tr><td class=header2 colspan=2 align=center>You can't leave yourself a comment.</td></tr>";
	}elseif(isIgnored($uid,$userData['userid'],'comments', $userData['age'])){
		echo "<tr><td class=header2 colspan=2 align=center>You are ignored, so you can't leave a comment.</td></tr>";
	}else{
		echo "<tr><td colspan=2 class=header2>";
		echo "<a name=reply></a>";
		echo "<table cellspacing=0 align=center>";
		echo "<form action=$_SERVER[PHP_SELF] method=post name=editbox>\n";
		echo "<input type=hidden name=id value=$uid>\n";

		echo "<tr><td class=header2 align=center>";

		editBox("",true);

		echo "</td></tr>\n";
		echo "<tr><td class=header2 align=center><input class=body type=submit name=action value=Preview> <input class=body type=submit name=action accesskey='s' value=Post></td></tr>\n";
		echo "</form>";
		echo "</table>\n";
		echo "</td></tr>";
	}
	echo "</table>\n";

	incFooter();
	exit;
}

function addUserComment($uid, $msg, $preview){

	incHeader();

	echo "<table align=center cellspacing=0>";

	if($preview){
		$msg = trim($msg);
		$nmsg = removeHTML($msg);
		$nmsg2 = parseHTML($nmsg);
		$nmsg3 = smilies($nmsg2);
		$nmsg3 = wrap($nmsg3);

		echo "<tr><td colspan=2 class=body>";

		echo "Here is a preview of what the post will look like:";

		echo "<blockquote>" . nl2br($nmsg3) . "</blockquote>";

		echo "<hr>";
		echo "</td></tr>";
	}


	echo "<form action=$_SERVER[PHP_SELF] method=post name=editbox>\n";
	echo "<input type=hidden name='id' value='$uid'>\n";

	echo "<tr><td class=body>";

	editBox($msg,true);

	echo "</td></tr>\n";
	echo "<tr><td class=body align=center><input class=body type=submit name=action value=Preview> <input class=body type=submit name=action accesskey='s' value=Post></td></tr>\n";
	echo "</form>";

	echo "</table>";

	incFooter();
	exit;
}

