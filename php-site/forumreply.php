<?

	$login=1;

	require_once("include/general.lib.php");

	if(!isset($tid) || $tid=="" || $tid==0)
		die("Bad Thread id");

	$query = "SELECT title,forumid,locked,time FROM forumthreads WHERE id='$tid'";
	$result = $db->query($query);
	if($db->numrows($result)==0)
		die("Bad thread id");

	$thread = $db->fetchrow($result);


	$perms = getForumPerms($thread['forumid']);	//checks it's a forum, not a realm

	if(!$perms['view'])
		die("You don't have permission to view this forum");

	if(!$perms['post'])
		die("You don't have permission to post in this forum");

	if($thread['locked']=='y' && !$perms['postlocked'])
		die("You don't have permission to post in locked threads");


	if(!isset($subscribe) || $subscribe != 'y')
		$subscribe='n';

	if(!isset($msg))
		$msg="";

	switch($action){
		case "Post":
			postReply($msg,$subscribe);
			break;
		case "quote":
			quote($pid);
			break;
		case "reply":
		case "Preview":
			reply($msg, ($action == "Preview"));
			break;
	}
	die("reply failed");


function quote($pid){
	global $db;

	$db->prepare_query("SELECT author,msg FROM forumposts WHERE id = ?",$pid);
	$line = $db->fetchrow();

	$msg = "[quote][i]Originally posted by: [b]" . $line['author'] . "[/b][/i]\n" . $line['msg'] . "[/quote]\n";
	reply($msg,false);
	return;
}

function reply($msg, $preview){
	global $tid,$userData,$db,$thread,$PHP_SELF,$perms;

	$db->prepare_query("SELECT subscribe FROM forumread WHERE userid = ? && threadid = ?",$userData['userid'],$tid);
	$subscribe = $db->fetchfield();

	$db->prepare_query("SELECT name,official,autolock FROM forums WHERE id = ?",$thread['forumid']);
	$forum = $db->fetchrow();

	if($forum['autolock'] > 0 && (time() - $thread['time']) > $forum['autolock']){
		$db->prepare_query("UPDATE forumthreads SET locked = 'y' WHERE id = ? ",$tid);
		$thread['locked']='y';

		if(!$perms['postlocked'])
			die("You don't have permission to post in locked threads");
	}


	incHeader();

	echo "<table align=center>";
	echo "<tr><td class=header2 colspan=2>Post a reply in ";

	if($forum['official']=='y')
		echo "<a class=header2 href=forums.php>Forums</a> > ";
	else
		echo "<a class=header2 href=forumsusercreated.php>User Created Forums</a> > ";
	echo "<a class=header2 href=forumthreads.php?fid=$thread[forumid]>$forum[name]</a> > ";
	echo "<a name=top class=header2 href=forumviewthread.php?tid=$tid>$thread[title]</a> ";

	echo "</td></tr>\n";

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
	echo "<input type=hidden name='tid' value='$tid'>\n";
	echo "<tr><td class=header2 colspan=2>";

	editBox($msg,true);

	echo "</td></tr>";
	echo "<tr><td class=header2 align=center colspan=2>";
	echo "<select class=body name=subscribe><option value=n>Don't Subscribe<option value=y" . (($subscribe=='y' || getUserInfo('autosubscribe',$userData['userid']) == 'y') ? ' selected' : '') . ">Subscribe</select>";
	echo "<input class=body name=action type=submit value='Preview'><input class=body name=action type=submit value='Post' accesskey='s' onClick='checksubmit()'></td></tr>\n";
	echo "</form>";
	echo "</table>";
	incFooter();
	exit;
}

function postReply($msg,$subscribe){
	global $userData,$tid,$thread,$config,$emaildomain,$wwwdomain,$db,$PHP_SELF;


	$msg = trim($msg);

	$spam = spamfilter($msg);

	if(!$spam)
		reply($msg,true);

	$nmsg = removeHTML($msg);
	$nmsg2 = parseHTML($nmsg);
	$nmsg3 = smilies($nmsg2);
	$nmsg3 = wrap($nmsg3);
	$nmsg3 = nl2br($nmsg3);

	$time = time();

	$old_user_abort = ignore_user_abort(true);

	$db->query("LOCK TABLES forumposts WRITE, forumread WRITE");

//doublepost
	$db->prepare_query("SELECT id FROM forumposts WHERE threadid = ? && authorid = ? && msg = ? && time > ?", $tid, $userData['userid'], $nmsg, $time-30);

	if($db->numrows() > 0){
		$db->query("UNLOCK TABLES");

		ignore_user_abort($old_user_abort);

		header("location: forumthreads.php?fid=$thread[forumid]");
		exit(0);
	}

//	$query = $db->prepare("UPDATE forumread SET updated='y' WHERE threadid = ? && updated='n'", $tid);
//	$db->query($query);

//	$query = $db->prepare("UPDATE forumread SET time = ?, subscribe = ?, updated='n' WHERE userid = ? && threadid = ?", $time, $subscribe, $userData['userid'], $tid);
	$db->prepare_query("UPDATE forumread SET time = ?, subscribe = ? WHERE userid = ? && threadid = ?", $time, $subscribe, $userData['userid'], $tid);
	if($db->affectedrows()==0){
		$db->prepare_query("INSERT IGNORE INTO forumread SET userid = ?, threadid = ?, time = ?, subscribe = ?", $userData['userid'], $tid, $time, $subscribe);
	}

	$db->prepare_query("INSERT INTO forumposts SET threadid = ?, authorid = ?, author = ?, msg = ?, nmsg = ?, time = ?", $tid, $userData['userid'], $userData['username'], $nmsg, $nmsg3, $time);

	$db->query("UNLOCK TABLES");

	$db->prepare_query("UPDATE forumthreads SET posts = posts+1, time = ?, lastauthor = ?,lastauthorid = ? WHERE id = ?", $time, $userData['username'], $userData['userid'], $tid);

	$db->prepare_query("UPDATE forums SET posts = posts+1,time = ? WHERE id = ?", $time, $thread['forumid']);

	$db->prepare_query("UPDATE users SET posts = posts+1 WHERE userid = ?", $userData['userid']);

	if($config['allowThreadUpdateEmails']){
		$db->prepare_query("SELECT users.userid,username,email FROM users,forumread WHERE users.userid=forumread.userid && users.threadupdates='y' && forumread.threadid = ? && forumread.notified='n' && users.userid != ?", $tid, $userData['userid']);

		if($db->numrows() > 0){
			$subject = "Reply to post '$thread[title]' on $wwwdomain.";

			$users = array();
			while($line = $db->fetchrow($result)){
				$message = "Hello $line[username],\n\n$userData[username] has just replied to a thread you have subscribed to entitled - $thread[title].\n\nThis thread is located at: http://$wwwdomain/viewthread.php?tid=$tid \n\nThere may be other replies also, but you will not receive any more notifications until you visit the board again.";
				$users[] = $line['userid'];

				smtpmail("$line[username] <$line[email]>", $subject, $message, "From: $config[title] <no-reply@$emaildomain>") or trigger_error("Error sending email",E_USER_NOTICE);
			}

			$db->prepare_query("UPDATE forumread SET notified='y' WHERE forumread.threadid = ? && userid IN (?)", $tid, $users);
		}
	}

	ignore_user_abort($old_user_abort);

	$db->prepare_query("SELECT replyjump FROM users WHERE userid = ?", $userData['userid']);
	$replyjump = $db->fetchfield();


	if($replyjump=='forum')
		header("location: forumthreads.php?fid=$thread[forumid]");
	else
		header("location: forumviewthread.php?tid=$tid");

	exit;
}



