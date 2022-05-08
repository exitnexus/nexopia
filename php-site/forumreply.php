<?

	$login=1;

	$userprefs = array('replyjump');

	require_once("include/general.lib.php");

	if(!($tid = getREQval('tid', 'int')))
		die("Bad Thread id");


	$thread = $cache->get(array($tid, "forumthread-$tid"));

	if($thread === false){
		$forums->db->prepare_query("SELECT forumid, moved, title, posts, sticky, locked, announcement, flag, pollid, time FROM forumthreads WHERE id = ?", $tid);
		$thread = $forums->db->fetchrow();

		if($thread)
			$cache->put(array($tid, "forumthread-$tid"), $thread, 10800);
	}

	if(!$thread || $thread['moved'])
		die("Bad Thread id");


	$perms = $forums->getForumPerms($thread['forumid']);	//checks it's a forum, not a realm

	if(!$perms['view'])
		die("You don't have permission to view this forum");

	if(!$perms['post'])
		die("You don't have permission to post in this forum");

	if($thread['locked']=='y' && !$perms['postlocked'])
		die("You don't have permission to post in locked threads");


	switch($action){
		case "Post":
			if(!($msg = getPOSTval('msg')))
				reply('', true);

			$subscribe = getPOSTval('subscribe');

			postReply($msg, ($subscribe == 'y' ? 'y' : 'n'));
			break;
		case "quote":
			$pid = getREQval('pid', 'int');

			quote($pid);
			break;
		case "reply":
		case "Preview":
			$msg = getPOSTval('msg');

			reply($msg, ($action == "Preview"));
			break;
	}
	die("reply failed");


function quote($pid){
	global $forums, $tid;

	$forums->db->prepare_query("SELECT author, msg FROM forumposts WHERE id = # && threadid = #", $pid, $tid);
	$line = $forums->db->fetchrow();

	if(!$line)
		reply('', false);

	$msg = "[quote][i]Originally posted by: [b]" . $line['author'] . "[/b][/i]\n" . $line['msg'] . "[/quote]\n";
	reply($msg,false);
}

function reply($msg, $preview){
	global $tid, $userData, $forums, $thread, $perms;

	$forums->db->prepare_query("SELECT subscribe FROM forumread WHERE userid = # && threadid = #", $userData['userid'], $tid);
	$subscribe = $forums->db->fetchfield();

	$forums->db->prepare_query("SELECT name, official, autolock FROM forums WHERE id = #",$thread['forumid']);
	$forum = $forums->db->fetchrow();

	if($forum['autolock'] > 0 && (time() - $thread['time']) > $forum['autolock']){
		$forums->db->prepare_query("UPDATE forumthreads SET locked = 'y' WHERE id = #",$tid);
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


	echo "<form action=$_SERVER[PHP_SELF] method=post enctype=\"application/x-www-form-urlencoded\" name=editbox>\n";
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
	global $userData, $tid, $thread, $config, $emaildomain, $wwwdomain, $forums, $db, $cache;


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

//	$forums->db->query("LOCK TABLES forumposts WRITE, forumread WRITE");

//	$forums->db->begin();

//doublepost
	$dupe = $cache->get(array($userData['userid'], "forumpostdupe-$tid-$userData[userid]")); //should block fast dupes, like bots, use a short time since it blocks ALL posts by that user in that thread, not just the same one.

	if($dupe){
		ignore_user_abort($old_user_abort);

		header("location: forumthreads.php?fid=$thread[forumid]");
		exit;
	}


	$forums->db->prepare_query("SELECT threadid FROM forumposts WHERE threadid = # && time >= # && authorid = # && msg = ?", $tid, $time-30, $userData['userid'], $nmsg);

	if($forums->db->numrows() > 0){
//		$forums->db->query("UNLOCK TABLES");
		$forums->db->rollback();

		$cache->put(array($userData['userid'], "forumpostdupe-$tid-$userData[userid]"), 1, 3); //block for another 3 seconds

		ignore_user_abort($old_user_abort);

		header("location: forumthreads.php?fid=$thread[forumid]");
		exit;
	}


	$forums->db->prepare_query("UPDATE forumread SET time = #, subscribe = ? WHERE userid = # && threadid = #", $time, $subscribe, $userData['userid'], $tid);
	if($forums->db->affectedrows()==0)
		$forums->db->prepare_query("INSERT IGNORE INTO forumread SET userid = #, threadid = #, time = #, subscribe = ?", $userData['userid'], $tid, $time, $subscribe);


	$forums->db->prepare_query("INSERT INTO forumposts SET threadid = #, authorid = #, author = ?, msg = ?, nmsg = ?, time = #", $tid, $userData['userid'], $userData['username'], $nmsg, $nmsg3, $time);

//	$forums->db->query("UNLOCK TABLES");

	$forums->db->prepare_query("UPDATE forumthreads SET posts = posts+1, time = #, lastauthor = ?, lastauthorid = # WHERE id = #", $time, $userData['username'], $userData['userid'], $tid);

	$forums->db->prepare_query("UPDATE forums SET posts = posts+1,time = # WHERE id = #", $time, $thread['forumid']);

	$forums->db->commit();

	$db->prepare_query("UPDATE users SET posts = posts+1 WHERE userid = #", $userData['userid']);


	$cache->put(array($userData['userid'], "forumread-$userData[userid]-$tid"), array('subscribe' => $subscribe, 'time' => $time, 'posts' => $thread['posts']+1), 10800);

	$cachethread = array(	'forumid' => $thread['forumid'],
							'moved' => $thread['moved'],
							'title' => $thread['title'],
							'posts' => $thread['posts'] + 1,
							'sticky' => $thread['sticky'],
							'locked' => $thread['locked'],
							'announcement' => $thread['announcement'],
							'time' => $time,
							'pollid' => $thread['pollid']);

	$cache->put(array($tid, "forumthread-$tid"), $cachethread, 10800);

	$cache->put(array($userData['userid'], "forumpostdupe-$tid-$userData[userid]"), 1, 3); //block dupes for 3 seconds

	ignore_user_abort($old_user_abort);

	if($userData['replyjump']=='forum')
		header("location: forumthreads.php?fid=$thread[forumid]");
	else
		header("location: forumviewthread.php?tid=$tid");

	exit;
}



