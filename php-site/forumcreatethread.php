<?
	$login = 1;

	require_once("include/general.lib.php");

	$fid = getREQval('fid', 'integer', 0);

	if(empty($fid))
		die("Bad Forum id");

	$perms = $forums->getForumPerms($fid);	//checks it's a forum, not a realm

	if(!$perms['view'])
		die("You don't have permission to view this forum");

	if(!$perms['post'])
		die("You don't have permission to create threads in this forum");

	$forum = $perms['cols'];

	$subscribe = getREQval('subscribe', 'string', $userData['autosubscribe']);

	switch($action){
		case "Preview":
			$title = getPOSTval('title');
			$msg = getPOSTval('msg');

			createThread($fid, $title, $msg, isset($poll), ($subscribe == 'y'), true); //exit

		case "Post":
//			$question = "";
//			$answers = array();
//			$title = getPOSTval('title');
//			$msg = getPOSTval('msg');

		case "Add":
			$title = getPOSTval('title');
			$msg = getPOSTval('msg');

			$poll = getPOSTval('poll');
			$question = getPOSTval('question');
			$answers = getPOSTval('answers', 'array');

			$haveAns = false;
			foreach($answers as $ans)
				if(strlen($ans))
					$haveAns = true;

			if($poll || (strlen($question) && !$haveAns) || ($haveAns && !strlen($question))) {
				$msgs->addMsg('Please enter a poll question (minimum 5 characters in length) and at least one answer, or leave the form blank to skip poll addition.');
				createThreadPoll($fid, $title, $msg, $subscribe, $question, $answers); //exit
			}

			if(!($tid = postThread($fid, $title, $msg, ($subscribe == 'y'), $question, $answers)))
				return;

			if($userData['replyjump']=='forum')
				header("location: forumthreads.php?fid=$fid");
			else
				header("location: forumviewthread.php?tid=$tid");
			exit;
	}

	createThread($fid, "", "", false, $subscribe, false);

////////////////////////////////

function postThread($fid, $title, $msg, $subscribe = false, $question = "", $answers = array()){
	global $userData, $polls, $usersdb, $forums, $cache, $msgs;

	$msg = trim($msg);

	$spam = spamfilter($msg);

	$ntitle = str_replace("&#32;", " ", $title);
	$ntitle = str_replace("&#32", " ", $ntitle);

	$ntitle = trim($ntitle);
	$ntitle = removeHTML($ntitle);

	if(!$spam || strlen($ntitle)<=3)
		createThread($fid, $title, $msg, ($question != ""), $subscribe, true);

//	$ntitle = censor($ntitle);

	$nmsg = cleanHTML($msg);
	$time = time();

//spamming across multiple forums
	$limit = $cache->get("forumsratelimit-$userData[userid]");

	if($limit){
		$cache->put("forumsratelimit-$userData[userid]", 1, 5); //block for another 5 seconds
		$msgs->addMsg("You can only create one thread per second");
		createThread($fid, $title, $msg, ($question != ""), $subscribe, true);
	}

//dupe detection
	$res = $forums->db->prepare_query("SELECT id FROM forumthreads WHERE authorid = ? && title = ? && time >= ?", $userData['userid'], $ntitle, $time-60);
	$thread = $res->fetchrow();
	if($thread){	 //1 min dupe detection
		$tid = $thread['id'];
		return $tid;
	}

	$cache->put("forumsratelimit-$userData[userid]", 1, 2); //block spam


	$old_user_abort = ignore_user_abort(true);

	$pollid=0;
	if(!empty($question)){
		$answers = array_filter($answers);
		if(count($answers))
			if(!($pollid = $polls->addPoll($question,$answers,false)))
			{
				$msgs->addMsg('Please enter a poll question (minimum 5 characters in length) and at least one answer, or leave the form blank to skip poll addition.');		
				createThreadPoll($fid, $title, $msg, $subscribe, $question, $answers);
			}
	}

	$forums->db->prepare_query("INSERT INTO forumthreads SET forumid = ?, title = ?, authorid = ?, time = ?, lastauthorid = ?, pollid = ?",
						$fid, $ntitle, $userData['userid'], $time, $userData['userid'], $pollid);

	$tid = $forums->db->insertid();

	$forums->db->prepare_query("INSERT INTO forumposts SET threadid = ?, authorid = ?, msg = ?, time = ?",
						$tid, $userData['userid'], $nmsg, $time);

	$forums->db->prepare_query("INSERT INTO forumread SET threadid = ?, userid = ?, readtime = ?, time = ?, subscribe = ?",
						$tid, $userData['userid'], $time, $time, ($subscribe ? 'y' : 'n') );

	$forums->db->prepare_query("UPDATE forums SET posts = posts+1, threads = threads+1, time = ? WHERE id = ?", $time, $fid);

	$usersdb->prepare_query("UPDATE users SET posts = posts+1 WHERE userid = %", $userData['userid']);
	$cache->incr("forumuserposts-$userData[userid]");

	ignore_user_abort($old_user_abort);

	scan_string_for_notables($nmsg);

	return $tid;
}

function createThreadPoll($fid, $title, $msg, $subscribe, $question = '', $answers = array()){

	$answers = array_pad($answers, 4, "");
	if(count($answers) > 10)
		$answers = array_slice($answers, 0, 10);

	$template = new template('forums/forumcreatethread/createThreadPoll');
	$template->set('fid', $fid);
	$template->set('title', $title);
	$template->set('msg', $msg);
	$template->set('subscribe', $subscribe);
	$template->set('question', $question);
	$template->set('answers', $answers);
	$template->display();

	exit;
}

function createThread($fid, $title, $msg, $poll, $subscribe, $preview){
	global $forum, $forums, $userData;

	$template = new template('forums/forumcreatethread/createThread');

	$template->set('forumTrail', $forums->getForumTrail($forum, "header"));
	$template->set('preview', $preview);

	if($preview){
		$ntitle = trim($title);
		$ntitle = removeHTML($ntitle);

		$msg = trim($msg);

		$msg = cleanHTML($msg);

		$nmsg3 = $forums->parsePost($msg);

		$template->set('ntitle', $ntitle);
		$template->set('nmsg3', $nmsg3);
	}

	$template->set('fid', $fid);
	$template->set('title', $title);
	$template->set('editBox', editBoxStr($msg));
	$template->set('pollCheckBox', makeCheckBox('poll', "Add a Poll", $poll));
	$template->set('subscribeSelected', ($subscribe ? ' selected' : ''));
	$template->display();
	exit;
}
