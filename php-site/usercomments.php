<?

	$login=0;

	require_once("include/general.lib.php");

	if(empty($id))
		die("Bad id");

	$isAdmin = false;
	if($userData['loggedIn']){
		if($userData['userid'] == $id)
			$isAdmin = 1;
		else
			$isAdmin = $mods->isAdmin($userData['userid'],'deletecomments');
	}

	$db->prepare_query("SELECT username,enablecomments,journalentries,gallery FROM users WHERE userid = ?", $id);
	if($db->numrows()==0)
		die("Bad id");
	$data = $db->fetchrow();

	if($data['enablecomments']=='n'){
		header("location: /profile.php?uid=$id");
		exit;
	}

	if($userData['loggedIn']){
		switch($action){
			case "Delete":
				if($isAdmin){
					$db->prepare_query("DELETE usercomments, usercommentstext FROM usercomments, usercommentstext WHERE usercomments.id=usercommentstext.id && usercomments.id IN (?) && itemid = ?", $checkID, $id);

					if($id != $userData['userid'])
						$mods->adminlog("delete user comments", "Delete user comments: userid $id");
				}
				break;

			case "Preview":
				addUserComment($id, $msg, true);

			case "Post":

				if(!empty($msg) && !empty($id)){
					if(isIgnored($id,$userData['userid'],'comments'))
						$msgs->addMsg("This user is ignoring you. You cannot leave a comment");
					else{
						postUserComment($id,$msg);
					}
				}
		}

		if($userData['userid']==$id && $userData['newcomments']>0){
			$db->prepare_query("UPDATE users SET newcomments='0' WHERE userid = ?", $userData['userid']);
			$userData['newcomments']=0;
		}
	}

	if(empty($page))
		$page = 0;

	listComments();
///////////////////////////////


function listComments(){
	global $page, $PHP_SELF, $db, $userData, $data, $id, $config, $isAdmin;

	$leftblocks = array('incTextAdBlock','incSortBlock','incTopGirls','incTopGuys','incNewestMembersBlock');

	if(empty($page))
		$page = 0;

	$db->prepare_query("SELECT SQL_CALC_FOUND_ROWS usercomments.id, author, authorid, time, nmsg FROM usercomments, usercommentstext WHERE itemid = ?  && usercomments.id = usercommentstext.id ORDER BY id DESC LIMIT " . ($page*$config['linesPerPage']) . ", $config[linesPerPage]", $id);

	$comments = array();
	while($line = $db->fetchrow())
		$comments[] = $line;

	$db->query("SELECT FOUND_ROWS()");
	$numrows = $db->fetchfield();
	$numpages =  ceil($numrows / $config['linesPerPage']);

	incHeader(0,$leftblocks);

	echo "<table width=100% cellpadding=3>";

	$isFriend = $userData['loggedIn'] && ($userData['userid']==$id || isFriend($userData['userid'],$id));

	$cols=3;
	if($data['journalentries'] == 'public' || ($data['journalentries']=='friends' && $isFriend))
		$cols++;
	if($data['gallery']=='anyone' || ($data['gallery']=='loggedin' && $userData['loggedIn']) || ($data['gallery']=='friends' && $isFriend))
		$cols++;

	$width = round(100.0/$cols);

	echo "<tr>";
	echo "<td class=header2 colspan=2>";
	echo "<table width=100%>";
	echo "<td class=header align=center width=$width%><a class=header href=\"profile.php?uid=$id\"><b>Profile</b></a></td>";
	echo "<td class=header align=center width=$width%><a class=header href=\"usercomments.php?id=$id\"><b>Comments</b></a></td>";
	if($data['gallery']=='anyone' || ($data['gallery']=='loggedin' && $userData['loggedIn']) || ($data['gallery']=='friends' && $isFriend))
		echo "<td class=header align=center width=$width%><a class=header href=\"gallery.php?uid=$id\"><b>Gallery</b></a></td>";
	if($data['journalentries'] == 'public' || ($data['journalentries']=='friends' && $isFriend))
		echo "<td class=header align=center width=$width%><a class=header href=weblog.php?id=$id><b>Journal</a></td>";
	echo "<td class=header align=center width=$width%><a class=header href=\"friends.php?uid=$id\"><b>Friends</b></a></td></tr>";
	echo "</table>";
	echo "</td></tr>";

	echo "<tr><td class=header colspan=2>";
	echo "<table width=100%><tr><td class=header>";

	echo "<a class=header href=profile.php?uid=$id>$data[username]'s Profile</a> > <a class=header href=$PHP_SELF?id=$id>Comments</a>";

	echo "</td>";

	echo "<td class=header align=right>";

	echo "Page:";
	echo pageList("$PHP_SELF?id=$id",$page,$numpages,'header');

	echo "</td></tr></table></td></tr>";

	if(count($comments)==0)
		echo "<tr><td class=body colspan=2 align=center>No Comments</td>";

	if($isAdmin){
		echo "<form action=$PHP_SELF method=post>";
		echo "<input type=hidden name=id value='$id'>";
	}

	foreach($comments as $line){
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
	}
	echo "<tr><td class=header colspan=2 align=right>";

	if($isAdmin){
		echo "<table width=100%><tr>";
		echo "<td class=header>";
		echo "<input class=body name=selectall type=checkbox value='Check All' onClick=\"this.value=check(this.form,'check')\">";
		echo "<input class=body type=submit name=action value=Delete></td></form>";
		echo "<td class=header align=right>";
	}

	echo "Page:" . pageList("$PHP_SELF?id=$id",$page,$numpages,'header');

	if($isAdmin)
		echo "</td></tr></table>";

	echo "</td></tr>";

	if($userData['loggedIn']){
		echo "<tr><td colspan=3>";
		echo "<table  cellspacing=0 align=center>";
		echo "<tr><td class=header2><a name=reply>Post a Comment:</a></td></tr>\n";

		echo "<form action=\"$PHP_SELF\" method=post enctype=\"application/x-www-form-urlencoded\" name=editbox>\n";
		echo "<input type=hidden name=id value=$id>\n";

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

function addUserComment($id, $msg, $preview){
	global $PHP_SELF;


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


	echo "<form action=\"$PHP_SELF\" method=post enctype=\"application/x-www-form-urlencoded\" name=editbox>\n";
	echo "<input type=hidden name='id' value='$id'>\n";

	echo "<tr><td class=body>";

	editBox($msg,true);

	echo "</td></tr>\n";
	echo "<tr><td class=body align=center><input class=body type=submit name=action value=Preview> <input class=body type=submit name=action accesskey='s' value=Post></td></tr>\n";
	echo "</form>";

	echo "</table>";

	incFooter();
	exit;
}

function postUserComment($id,$msg){
	global $userData,$PHP_SELF,$db;

	if(!$userData['loggedIn'])
		return;

	$msg = trim($msg);

	$spam = spamfilter($msg);

	if(!$spam)
		addUserComment($id,$msg,true);

	$nmsg = removeHTML($msg);
	$nmsg2 = parseHTML($nmsg);
	$nmsg3 = smilies($nmsg2);
	$nmsg3 = wrap($nmsg3);
	$nmsg3 = nl2br($nmsg3);


	$time = time();

	$old_user_abort = ignore_user_abort(true);


	$db->prepare_query("SELECT id FROM usercomments WHERE itemid = ? && time > ?", $id, $time - 30);

	if($db->numrows() > 0) //double post
		return false;


	$db->prepare_query("INSERT INTO usercomments SET itemid = ?, author = ?, authorid = ?, time = ?", $id, $userData['username'], $userData['userid'], $time);

	$insertid = $db->insertid();

	$db->prepare_query("INSERT INTO usercommentstext SET id = ?, msg = ?, nmsg = ?", $insertid, $msg, $nmsg3);

	$db->prepare_query("UPDATE users SET newcomments = newcomments + 1 WHERE userid = ?", $id);

	ignore_user_abort($old_user_abort);
}
