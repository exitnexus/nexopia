<?
	$login = 1;
	$userprefs = array('replyjump','autosubscribe');

	require_once("include/general.lib.php");

	if(empty($fid))
		die("Bad Forum id");

	$perms = $forums->getForumPerms($fid);	//checks it's a forum, not a realm

	if(!$perms['view'])
		die("You don't have permission to view this forum");

	if(!$perms['post'])
		die("You don't have permission to create threads in this forum");

	$forum = $perms['cols'];

	if(!isset($subscribe))
		$subscribe = $userData['autosubscribe'];

	switch($action){
		case "Preview":
			if(empty($fid))		$fid = "";
			if(empty($title))	$title = "";
			if(empty($msg))		$msg = "";
			createThread($fid, $title, $msg, isset($poll), ($subscribe == 'y'), true); //exit

		case "Update":
			if(empty($question))	$question = "";
			if(empty($answers))		$answers = array();
			if(empty($fid))			$fid = "";
			if(empty($subscribe))	$subscribe = "";
			if(empty($title))		$title = "";
			if(empty($msg))			$msg = "";
			createThreadPoll($fid, $title, $msg, $subscribe, $question, $numAnswers, $answers); //exit

		case "Post":
			$question = "";
			$answers = array();
			if(empty($fid))		$fid = "";
			if(empty($title))	$title = "";
			if(empty($msg))		$msg = "";
			if(isset($poll))
				createThreadPoll($fid, $title, $msg, $subscribe, $question, 4, $answers); //exit

		case "Add":
			if(empty($question))	$question = "";
			if(empty($answers))		$answers = array();
			if(empty($fid))			$fid = "";
			if(empty($title))		$title = "";
			if(empty($msg))			$msg = "";
			$tid = postThread($fid, $title, $msg, ($subscribe == 'y'), $question, $answers);

			if($userData['replyjump']=='forum')
				header("location: forumthreads.php?fid=$fid");
			else
				header("location: forumviewthread.php?tid=$tid");
			exit;
	}

	createThread($fid, "", "", false, ($subscribe == 'y'), false);

////////////////////////////////

function postThread($fid, $title, $msg, $subscribe = false, $question = "", $answers = array()){
	global $userData, $polls, $db, $forums;

	$msg = trim($msg);

	$spam = spamfilter($msg);

	$ntitle = trim($title);

	if(!$spam || strlen($ntitle)<=3)
		createThread($fid, $title, $msg, ($question != ""), $subscribe, true);

	$ntitle = removeHTML($ntitle);
//	$ntitle = censor($ntitle);

	$nmsg = removeHTML($msg);
	$nmsg2 = parseHTML($nmsg);
	$nmsg3 = smilies($nmsg2);
	$nmsg3 = wrap($nmsg3);
	$nmsg3 = nl2br($nmsg3);

	$time = time();

	$forums->db->prepare_query("SELECT id FROM forumthreads WHERE authorid = ? && title = ? && time >= ?", $userData['userid'], $ntitle, $time-60);
	if($forums->db->numrows() > 0){	 //1 min dupe detection
		$tid = $forums->db->fetchfield();
		return $tid;
	}

	$old_user_abort = ignore_user_abort(true);

	$pollid=0;
	if(!empty($question) && strlen($question) >= 5){
		$answers = array_filter($answers);
		if(count($answers))
			$pollid = $polls->addPoll($question,$answers,false);
	}

	$forums->db->prepare_query("INSERT INTO forumthreads SET forumid = ?, title = ?, authorid = ?, author = ?, time = ?, lastauthorid = ?, lastauthor = ?, pollid = ?",
						$fid, $ntitle, $userData['userid'], $userData['username'], $time, $userData['userid'], $userData['username'], $pollid);

	$tid = $forums->db->insertid();

	$forums->db->prepare_query("INSERT INTO forumposts SET threadid = ?, author = ?, authorid = ?, msg = ?, nmsg = ?, time = ?",
						$tid, $userData['username'], $userData['userid'], $nmsg, $nmsg3, $time);

	$forums->db->prepare_query("INSERT INTO forumread SET threadid = ?, userid = ?, readtime = ?, time = ?, subscribe = ?",
						$tid, $userData['userid'], $time, $time, ($subscribe ? 'y' : 'n') );

	$forums->db->prepare_query("UPDATE forums SET posts = posts+1, threads = threads+1, time = ? WHERE id = ?", $time, $fid);

	$db->prepare_query("UPDATE users SET posts = posts+1 WHERE userid = ?", $userData['userid']);

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

	incHeader();

	echo "<table align=center><form action=$_SERVER[PHP_SELF] method=post>";

	echo "<input type=hidden name='fid' value='$fid'>\n";
	echo "<input type=hidden name=title value=\"" . htmlentities($title) . "\">";
	echo "<input type=hidden name=msg value=\"" . htmlentities($msg) . "\">";
	echo "<input type=hidden name=subscribe value='$subscribe'>";

	echo "<tr><td colspan=2 class=header align=center>Add Poll</td></tr>";
	echo "<tr><td class=body align=right>Question:</td><td class=body><input class=body type=text size=40 name=question value=\"" . htmlentities($question) . "\" maxlength=64></td></tr>";

	for($i=0;$i<$numAnswers;$i++)
		echo "<tr><td class=body align=right>" . ($i+1) . ".</td><td class=body><input class=body type=text size=40 name=answers[] value=\"" . htmlentities($answers[$i]) . "\" maxlength=64></td></tr>";

	echo "<tr><td class=body>Number of Answers</td><td class=body><input type=text class=body name=numAnswers size=3 value=$numAnswers><input class=body type=submit name=action value=Update></td></tr>";

	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=Add></td></tr>";

	echo "</form></table>";

	incFooter();
	exit;

}

function createThread($fid, $title, $msg, $poll, $subscribe, $preview){
	global $forum;

	incHeader();

	echo "<table cellspacing=0 align=center><tr><td class=body>Create a thread in ";

	if($forum['official']=='y')
		echo "<a class=body href=forums.php>Forums</a> > ";
	else
		echo "<a class=body href=forumsusercreated.php>User Created Forums</a> > ";

	echo "<a class=body href=forumthreads.php?fid=$fid>$forum[name]</a></td></tr>";


	if($preview){
		$ntitle = trim($title);
		$ntitle = removeHTML($ntitle);
//		$ntitle = censor($ntitle);

		$msg = trim($msg);
		$nmsg = removeHTML($msg);
		$nmsg2 = parseHTML($nmsg);
		$nmsg3 = smilies($nmsg2);
		$nmsg3 = wrap($nmsg3);
		$nmsg3 = nl2br($nmsg3);

		echo "<tr><td colspan=2 class=body>";

		echo "Here is a preview of what the post will look like:";

		echo "<blockquote>$ntitle<hr>" . $nmsg3 . "</blockquote>";

		echo "<hr>";
		echo "</td></tr>";
	}

	echo "<tr><td class=body>";
	echo "<table align=center>";
	echo "<form action=$_SERVER[PHP_SELF] method=post enctype=\"application/x-www-form-urlencoded\" name=editbox>\n";
	echo "<input type=hidden name='fid' value='$fid'>\n";
	echo "<tr><td class=body>Subject:<input class=body type=text size=50 name=title value=\"". htmlentities($title) ."\" maxlength=64></td></tr>";
	echo "<tr><td class=body>";
	editBox($msg,true);
	echo "</td></tr>";
	echo "<tr><td class=body align=left>" . makeCheckBox('poll', "Add a Poll", $poll) . " ";
	echo "<select class=body name=subscribe><option value=n>Don't Subscribe<option value=y" . ($subscribe ? ' selected' : '') . ">Subscribe</select>";
	echo "<input class=body name=action type=submit value='Preview'><input class=body name=action type=submit value='Post' accesskey='s' onClick='checksubmit()'>";
	echo "</td></tr>\n";
	echo "</table>";
	echo "</td></tr>";
	echo "</form>";
	echo "</table>";

	incFooter();
	exit;
}
