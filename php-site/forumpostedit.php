<?

	$login=1;

	require_once("include/general.lib.php");

	$msgid = getREQval('msgid', 'integer');
	$action = getREQval('action', 'string', 'edit');

	if(!$msgid)
		die("bad data");

	switch($action){
		case "edit":
			edit($msgid);
			break;
		case "changed":
		case "Preview":
		case "Post":
			$msg = getPOSTval('msg', 'string');

			postEdit($msgid,$msg,$action);
			break;
	}
	die("how did you get here?");

function edit($msgid){
	global $userData, $forums;

	$res = $forums->db->prepare_query("SELECT forumposts.msg, forumposts.authorid, forumthreads.forumid, forumposts.time FROM forumposts,forumthreads WHERE forumposts.id = # && forumposts.threadid=forumthreads.id", $msgid);

	$line = $res->fetchrow();

	if(!$line)
		die("bad data");

	$perms = $forums->getForumPerms($line['forumid']);

	if(!($perms['editallposts'] || (($perms['editownposts'] == 1 || $perms['editownposts'] > time() - $line['time']) && $line['authorid'] == $userData['userid'])))
		die("bad data");


	$template = new template('forums/forumpostedit/edit');

	$template->set('editbox', editBoxStr($line['msg']));
	$template->set('msgid', $msgid);
	$template->display();
	exit;
}

function postEdit($msgid, $msg, $postaction){
	global $userData, $forums;

	$res = $forums->db->prepare_query("SELECT forumposts.authorid, forumthreads.forumid, forumposts.threadid, forumposts.time FROM forumposts, forumthreads WHERE forumposts.id = ? && forumposts.threadid=forumthreads.id", $msgid);

	$line = $res->fetchrow();

	if(!$line)
		die("bad data");

	$perms = $forums->getForumPerms($line['forumid']);

	if(!($perms['editallposts'] || (($perms['editownposts'] == 1 || $perms['editownposts'] > time() - $line['time']) && $line['authorid'] == $userData['userid'])))
		die("bad data");

	if($line['authorid'] != $userData['userid'])
		$forums->modlog('editpost', $line['forumid'], $line['threadid'], $msgid, $line['authorid']);

	$tid = $line['threadid'];



	$nmsg = cleanHTML($msg);

	$nmsg3 = $forums->parsePost($nmsg);

	if($postaction=="Preview"){
		$template = new template('forums/forumpostedit/preview');
		$template->set('editBox', editBoxStr($nmsg));
		$template->set('msgid', $msgid);
		$template->set('nmsg3', $nmsg3);
		$template->display();
		exit;
	}

//	$nmsg .= "\n\n[edited on " . date("F j, Y \\a\\t g:i a") . " by $userData[username]]";

	$forums->db->prepare_query("UPDATE forumposts SET msg = ?, edit = # WHERE id = #", $nmsg, time(), $msgid);

	scan_string_for_notables($nmsg);

	if($userData['replyjump'] == 'forum')
		header("location: forumthreads.php?fid=$line[forumid]");
	else
		header("location: forumviewthread.php?tid=$tid");

	exit;
}
