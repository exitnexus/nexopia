<?

	$login=1;

	require_once("include/general.lib.php");

	if(!isset($msgid))
		die("bad data");

	if(!isset($action))
		$action="edit";

	switch($action){
		case "edit":
			edit($msgid);
			break;
		case "changed":
		case "Preview":
		case "Post":
			postEdit($msgid,$msg,$action);
			break;
	}
	die("how did you get here?");

function edit($msgid){
	global $userData,$PHP_SELF,$db;


	$db->prepare_query("SELECT forumposts.msg,forumposts.authorid,forumthreads.forumid FROM forumposts,forumthreads WHERE forumposts.id = ? && forumposts.threadid=forumthreads.id", $msgid);

	if($db->numrows()==0)
		die("bad data");

	$line = $db->fetchrow();

	$perms = getForumPerms($line['forumid']);

	if(!$perms['editallposts']){
		if(!$perms['editownposts'] || $line['authorid']!=$userData['userid'])
			die("bad data");
	}

	incHeader();

	echo "<table align=center>";
	echo "<tr><td class=header colspan=2><a name=reply>Edit a reply:</a></td></tr>\n";
	echo "<form action=\"$PHP_SELF\" method=post enctype=\"application/x-www-form-urlencoded\" name=editbox>\n";
	echo "<input type=hidden name='msgid' value='$msgid'>\n";
	echo "<tr><td class=header2 colspan=2>";

	editBox($line['msg'],true);

	echo "</td></tr>";
	echo "<tr><td class=header2 align=center colspan=2><select class=body name=action><option value=changed>Preview if changes made<option value=Post>Post without previewing<option value=Preview>Preview</select><input class=body type=submit value='Post' accesskey='s' onClick='checksubmit()'></td></tr>\n";
	echo "</form>";
	echo "</table>";
	incFooter();
	exit;
}

function postEdit($msgid,$msg,$postaction='changed'){
	global $userData,$PHP_SELF,$db;

	$db->prepare_query("SELECT forumposts.authorid,forumthreads.forumid,forumposts.threadid FROM forumposts,forumthreads WHERE forumposts.id = ? && forumposts.threadid=forumthreads.id", $msgid);

	if($db->numrows()==0)
		die("bad data");

	$line = $db->fetchrow();

	$perms = getForumPerms($line['forumid']);

	if(!$perms['editallposts']){
		if(!$perms['editownposts'] || $line['authorid']!=$userData['userid'])
			die("bad data");
	}

	if($line['authorid'] != $userData['userid'])
		modlog('editpost', $line['forumid'], $line['threadid'], $msgid, $line['authorid']);

	$tid = $line['threadid'];

	$nmsg = removeHTML($msg);
	$nmsg2 = parseHTML($nmsg);
	$nmsg3 = smilies($nmsg2);
	$nmsg3 = wrap($nmsg3);
	$nmsg3 = nl2br($nmsg3);

//	$nmsg3 .= "\n<br>\n<br>[edited on " . date("F j, Y \\a\\t g:i a") . " by $userData[username]]";

	if($postaction=="Preview" || ($nmsg2 != $nmsg && $postaction=="changed")){
		incHeader();

		echo "Some changes have been made (be it smilies, html removal, or code to html conversions). Here is a preview of what the post will look like:<hr><blockquote>\n";

		echo $nmsg3;

		echo "</blockquote><hr>\n";
		echo "<table width=100% cellspacing=0>";
		echo "<tr><td class=header>You can make any changes needed below:</td></tr>\n";
		echo "<form action=\"$PHP_SELF\" method=post enctype=\"application/x-www-form-urlencoded\" name=editbox>\n";
		echo "<input type=hidden name='msgid' value='$msgid'>\n";

		echo "<tr><td class=header align=center>";

		editBox($nmsg,true);

		echo "</td></tr>";
		echo "<tr><td class=header align=center><input type=submit name=action value=Preview> <input type=submit name=action value='Post' accesskey='s' onClick='checksubmit()'></td></tr>\n";
		echo "</form>";


		echo "</table>\n";

		incFooter();
		exit(0);
	}

//	$nmsg .= "\n\n[edited on " . date("F j, Y \\a\\t g:i a") . " by $userData[username]]";

	$time = time();

	$db->prepare_query("UPDATE forumposts SET msg = ?, nmsg = ?, edit = ? WHERE id = ?", $nmsg, $nmsg3, time(), $msgid);


	$db->prepare_query("SELECT replyjump FROM users WHERE userid = ?", $userData['userid']);

	$user = $db->fetchrow();

	if($user['replyjump']=='forum')
		header("location: forumthreads.php?fid=$line[forumid]");
	else
		header("location: forumviewthread.php?tid=$tid");

	exit;
}
