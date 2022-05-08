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

			$parse_bbcode = getPOSTval('parse_bbcode');
			createThread($fid, $title, $msg, isset($poll), ($subscribe == 'y'), true, $parse_bbcode); //exit

		case "Post":
//			$question = "";
//			$answers = array();
//			$title = getPOSTval('title');
//			$msg = getPOSTval('msg');

		case "Add":
			$question = getPOSTval('question', 'string', '');
			$answers = getPOSTval('answers', 'array', array());
			$title = getPOSTval('title', 'string', '');
			$msg = getPOSTval('msg', 'string', '');
			$parse_bbcode = getPOSTval('parse_bbcode', 'bool');

			$haveAns = false;
			foreach ($answers as $ans)
				if (strlen($ans))
					$haveAns = true;

			if ( $poll = getPOSTval('poll') || (strlen($question) && ! $haveAns) || ($haveAns && ! strlen($question)) || (strlen($question) && strlen($question) < 5) ) {
				$msgs->addMsg('Please enter a poll question (minimum 5 characters in length) and at least one answer, or leave the form blank to skip poll addition.');
				createThreadPoll($fid, $title, $msg, $subscribe, $question, 4, $answers); //exit
			}

			$tid = postThread($fid, $title, $msg, ($subscribe == 'y'), $question, $answers, $parse_bbcode);

			if($userData['replyjump']=='forum')
				header("location: forumthreads.php?fid=$fid");
			else
				header("location: forumviewthread.php?tid=$tid");
			exit;
	}

	createThread($fid, "", "", false, ($subscribe == 'y'), false, true);

////////////////////////////////

function postThread($fid, $title, $msg, $subscribe = false, $question = "", $answers = array(), $parse_bbcode = true){
	global $userData, $polls, $usersdb, $forums, $cache;

	$msg = trim($msg);

	$spam = spamfilter($msg);

	$ntitle = trim($title);
	$ntitle = removeHTML($ntitle);

	if(!$spam || strlen($ntitle)<=3)
		createThread($fid, $title, $msg, ($question != ""), $subscribe, true, $parse_bbcode);

//	$ntitle = censor($ntitle);

	$nmsg = html_sanitizer::sanitize($msg);
	$time = time();

	$res = $forums->db->prepare_query("SELECT id FROM forumthreads WHERE authorid = ? && title = ? && time >= ?", $userData['userid'], $ntitle, $time-60);
	$thread = $res->fetchrow();
	if($thread){	 //1 min dupe detection
		$tid = $thread['id'];
		return $tid;
	}


	$old_user_abort = ignore_user_abort(true);

	$pollid=0;
	if(!empty($question) && strlen($question) >= 5){
		$answers = array_filter($answers);
		if(count($answers))
			$pollid = $polls->addPoll($question,$answers,false);
	}

	$forums->db->prepare_query("INSERT INTO forumthreads SET forumid = ?, title = ?, authorid = ?, time = ?, lastauthorid = ?, pollid = ?",
						$fid, $ntitle, $userData['userid'], $time, $userData['userid'], $pollid);

	$tid = $forums->db->insertid();

	$parse_bbcode = $parse_bbcode ? 'y' : 'n';
	$forums->db->prepare_query("INSERT INTO forumposts SET threadid = ?, authorid = ?, msg = ?, time = ?,  parse_bbcode = ?",
						$tid, $userData['userid'], $nmsg, $time, $parse_bbcode);

	$forums->db->prepare_query("INSERT INTO forumread SET threadid = ?, userid = ?, readtime = ?, time = ?, subscribe = ?",
						$tid, $userData['userid'], $time, $time, ($subscribe ? 'y' : 'n') );

	$forums->db->prepare_query("UPDATE forums SET posts = posts+1, threads = threads+1, time = ? WHERE id = ?", $time, $fid);

	$usersdb->prepare_query("UPDATE users SET posts = posts+1 WHERE userid = %", $userData['userid']);
	$cache->incr("forumuserposts-$userData[userid]");

	ignore_user_abort($old_user_abort);

	return $tid;
}

function createThreadPoll($fid, $title, $msg, $subscribe, $question, $numAnswers, $answers){

	if(!isset($question))		$question = "";
	if(empty($numAnswers))		$numAnswers=2;
	if($numAnswers > 10)
		$numAnswers = 10;
	if(!isset($answers))		$answers=array();
	$answers = array_pad($answers, $numAnswers, "");
	if(count($answers) > $numAnswers)
		$answers = array_slice($answers,0,$numAnswers);

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

function createThread($fid, $title, $msg, $poll, $subscribe, $preview, $parse_bbcode){
	global $forum, $forums, $userData;

	$template = new template('forums/forumcreatethread/createThread');

	$template->set('forumTrail', $forums->getForumTrail($forum, "body"));
	$template->set('preview', $preview);

	if($preview){
		$ntitle = trim($title);
		$ntitle = removeHTML($ntitle);

		$msg = trim($msg);

		$msg = html_sanitizer::sanitize($msg);

		if($parse_bbcode)
			$nmsg3 = $forums->parsePost($msg);
		else
			$nmsg3 = $msg;

		$template->set('ntitle', $ntitle);
		$template->set('nmsg3', $nmsg3);

	}

	$template->set('fid', $fid);
	$template->set('title', $title);

/*	if(!isset($parse_bbcode))
		$template->set("checkbox_parsebbcode", makeCheckBox('parse_bbcode', 'Parse BBcode', $userData['parse_bbcode']));
	else
		$template->set("checkbox_parsebbcode", makeCheckBox('parse_bbcode', 'Parse BBcode', $parse_bbcode));
*/
	$template->set("checkbox_parsebbcode", '<input type="hidden" name="parse_bbcode" value="y"/>');



	ob_start();
	editBox($msg);
	$template->set('editBox', ob_get_contents());
	ob_end_clean();

	$template->set('pollCheckBox', makeCheckBox('poll', "Add a Poll", $poll));

	$template->set('subscribeSelected', ($subscribe ? ' selected' : ''));
	$template->display();
	exit;
}
