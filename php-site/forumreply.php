<?

	$login=1;

	require_once("include/general.lib.php");


	if(!($tid = getREQval('tid', 'int')))
		die("Bad Thread id");


	$thread = $cache->get("forumthread-$tid");

	if($thread === false){
		$res = $forums->db->prepare_query("SELECT forumid, moved, title, posts, sticky, locked, announcement, flag, pollid, time FROM forumthreads WHERE id = ?", $tid);
		$thread = $res->fetchrow();

		if($thread)
			$cache->put("forumthread-$tid", $thread, 10800);
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

			$parse_bbcode = getPOSTval('parse_bbcode');
			$subscribe = getPOSTval('subscribe');

			postReply($msg, ($subscribe == 'y' ? 'y' : 'n'), $parse_bbcode);
			break;
		case "quote":
			$pid = getREQval('pid', 'int');

			quote($pid);
			break;
		case "reply":
		case "Preview":
			$msg = getPOSTval('msg');

			$parse_bbcode = getPOSTval('parse_bbcode');
			reply($msg, ($action == "Preview"), $parse_bbcode);
			break;
	}
	die("reply failed");


function quote($pid){
	global $forums, $tid, $userData;

	$res = $forums->db->prepare_query("SELECT authorid, msg FROM forumposts WHERE id = # && threadid = #", $pid, $tid);
	$line = $res->fetchrow();

	if(!$line)
		reply('', false);

	if(fckeditor::IsCompatible() && !$userData['bbcode_editor'])
		$msg = "<br /><blockquote><hr /><em>Originally posted by: <strong>" . getUserName($line['authorid']) . "</strong></em><br />" . $line['msg'] . "<hr /></blockquote><br/>\n";
	else
		$msg = "[quote][i]Originally posted by: [b]" . getUserName($line['authorid']) . "[/b][/i]\n" . $line['msg'] . "[/quote]\n";

	reply($msg, false);
}

function reply($msg, $preview, $parse_bbcode = true){
	global $tid, $userData, $forums, $thread, $perms;
	$template = new template('forums/forumreply');

	$res = $forums->db->prepare_query("SELECT subscribe FROM forumread WHERE userid = # && threadid = #", $userData['userid'], $tid);
	$subscribe = $res->fetchfield();

	$forum = $forums->getForums($thread['forumid']);

	if($forum['autolock'] > 0 && (time() - $thread['time']) > $forum['autolock']){
		$forums->db->prepare_query("UPDATE forumthreads SET locked = 'y' WHERE id = #",$tid);
		$thread['locked']='y';

		if(!$perms['postlocked'])
			die("You don't have permission to post in locked threads");
	}

	$template->set('forumTrail', $forums->getForumTrail($forum, "header2"));
	$template->set('thread', $thread);
	$template->set('preview', $preview);



	if($preview){
		$msg = trim($msg);

		$nmsg = html_sanitizer::sanitize($msg);

		if($parse_bbcode)
			$nmsg3 = $forums->parsePost($nmsg);
		else
			$nmsg3 = $nmsg;

		$template->set('nmsg3', $nmsg3);

	}

/*	if(!isset($parse_bbcode))
        $template->set("checkbox_parsebbcode", makeCheckBox('parse_bbcode', 'Parse BBcode', $userData['parse_bbcode']));
    else
        $template->set("checkbox_parsebbcode", makeCheckBox('parse_bbcode', 'Parse BBcode', $parse_bbcode));*/
	$template->set("checkbox_parsebbcode", '<input type="hidden" name="parse_bbcode" value="y"/>');




	$template->set('tid', $tid);


	ob_start();
	editBox($msg);
	$template->set('editbox', ob_get_contents());
	ob_end_clean();

	$template->set('subscribeSelected', ($subscribe=='y' || $userData['autosubscribe'] == 'y'));
	$template->display();
	exit;
}

function postReply($msg,$subscribe){
	global $userData, $tid, $thread, $config, $emaildomain, $wwwdomain, $forums, $usersdb, $cache;


	$msg = trim($msg);

	$spam = spamfilter($msg);

	if(!$spam)
		reply($msg,true);

	$nmsg = html_sanitizer::sanitize($msg);

	$time = time();

	$old_user_abort = ignore_user_abort(true);

//	$forums->db->query("LOCK TABLES forumposts WRITE, forumread WRITE");

//	$forums->db->begin();

//doublepost
	$dupe = $cache->get("forumpostdupe-$tid-$userData[userid]"); //should block fast dupes, like bots, use a short time since it blocks ALL posts by that user in that thread, not just the same one.

	if($dupe){
		ignore_user_abort($old_user_abort);

		header("location: forumthreads.php?fid=$thread[forumid]");
		exit;
	}


	$res = $forums->db->prepare_query("SELECT threadid FROM forumposts WHERE threadid = # && time >= # && authorid = # && msg = ?", $tid, $time-30, $userData['userid'], $nmsg);

	if($res->fetchrow()){
//		$forums->db->query("UNLOCK TABLES");
		$forums->db->rollback();

		$cache->put("forumpostdupe-$tid-$userData[userid]", 1, 3); //block for another 3 seconds

		ignore_user_abort($old_user_abort);

		header("location: forumthreads.php?fid=$thread[forumid]");
		exit;
	}


	$forums->db->prepare_query("UPDATE forumread SET time = #, subscribe = ? WHERE userid = # && threadid = #", $time, $subscribe, $userData['userid'], $tid);
	if($forums->db->affectedrows()==0)
		$forums->db->prepare_query("INSERT IGNORE INTO forumread SET userid = #, threadid = #, time = #, subscribe = ?", $userData['userid'], $tid, $time, $subscribe);


	$forums->db->prepare_query("INSERT INTO forumposts SET threadid = #, authorid = #, msg = ?, time = #", $tid, $userData['userid'], $nmsg, $time);

//	$forums->db->query("UNLOCK TABLES");

	$forums->db->prepare_query("UPDATE forumthreads SET posts = posts+1, time = #, lastauthorid = # WHERE id = #", $time, $userData['userid'], $tid);

	$forums->db->prepare_query("UPDATE forums SET posts = posts+1,time = # WHERE id = #", $time, $thread['forumid']);

	$forums->db->commit();

	$usersdb->prepare_query("UPDATE users SET posts = posts+1 WHERE userid = %", $userData['userid']);
	$cache->incr(array($userData['userid'], "forumuserposts-$userData[userid]"));

	$cache->put("forumread-$userData[userid]-$tid", array('subscribe' => $subscribe, 'time' => $time, 'posts' => $thread['posts']+1), 10800);

	$cachethread = array(	'forumid' => $thread['forumid'],
							'moved' => $thread['moved'],
							'title' => $thread['title'],
							'posts' => $thread['posts'] + 1,
							'sticky' => $thread['sticky'],
							'locked' => $thread['locked'],
							'announcement' => $thread['announcement'],
							'time' => $time,
							'pollid' => $thread['pollid']);

	$cache->put("forumthread-$tid", $cachethread, 10800);

	$cache->put("forumpostdupe-$tid-$userData[userid]", 1, 3); //block dupes for 3 seconds

	ignore_user_abort($old_user_abort);

	if($userData['replyjump']=='forum')
		header("location: forumthreads.php?fid=$thread[forumid]");
	else
		header("location: forumviewthread.php?tid=$tid");

	exit;
}



