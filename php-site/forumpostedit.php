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

			$parse_bbcode = getPOSTval('parse_bbcode', 'bool');
			postEdit($msgid,$msg,$action,  $parse_bbcode);
			break;
	}
	die("how did you get here?");

function edit($msgid){
	global $userData, $forums;


	$res = $forums->db->prepare_query("SELECT forumposts.msg,forumposts.authorid,forumthreads.forumid, forumposts.time,  forumposts.parse_bbcode FROM forumposts,forumthreads WHERE forumposts.id = ? && forumposts.threadid=forumthreads.id", $msgid);

	$line = $res->fetchrow();

	if(!$line)
		die("bad data");

	$perms = $forums->getForumPerms($line['forumid']);

	if(!($perms['editallposts'] || (($perms['editownposts'] == 1 || $perms['editownposts'] > time() - $line['time']) && $line['authorid'] == $userData['userid'])))
		die("bad data");


	$template = new template('forums/forumpostedit/edit');

//	$template->set("checkbox_parsebbcode", makeCheckBox('parse_bbcode', 'Parse BBcode', $line['parse_bbcode'] == 'y' ? true :false));
	$template->set("checkbox_parsebbcode", '<input type="hidden" name="parse_bbcode" value="y"/>');



	ob_start();
	editBox($line['msg']);
	$template->set('editbox', ob_get_contents());
	ob_end_clean();

	$template->set('msgid', $msgid);
	$template->display();
	exit;
}

function postEdit($msgid, $msg, $postaction, $parse_bbcode){
	global $userData, $forums;

	$res = $forums->db->prepare_query("SELECT forumposts.authorid, forumthreads.forumid, forumposts.threadid, forumposts.time, forumposts.parse_bbcode FROM forumposts, forumthreads WHERE forumposts.id = ? && forumposts.threadid=forumthreads.id", $msgid);

	$line = $res->fetchrow();

	if(!$line)
		die("bad data");

	$perms = $forums->getForumPerms($line['forumid']);

	if(!($perms['editallposts'] || (($perms['editownposts'] == 1 || $perms['editownposts'] > time() - $line['time']) && $line['authorid'] == $userData['userid'])))
		die("bad data");

	if($line['authorid'] != $userData['userid'])
		$forums->modlog('editpost', $line['forumid'], $line['threadid'], $msgid, $line['authorid']);

	$tid = $line['threadid'];



	$nmsg = html_sanitizer::sanitize($msg);

	if($parse_bbcode)
		$nmsg3 = $forums->parsePost($nmsg);
	else
		$nmsg3 = $nmsg;

	if($postaction=="Preview"){

		$template = new template('forums/forumpostedit/preview');
		ob_start();
		editBox($nmsg);
		$template->set('editBox', ob_get_contents());
		ob_end_clean();
//		$template->set("checkbox_parsebbcode", makeCheckBox('parse_bbcode', 'Parse BBcode', $parse_bbcode));
		$template->set("checkbox_parsebbcode", '<input type="hidden" name="parse_bbcode" value="y"/>');

		$template->set('msgid', $msgid);
		$template->set('nmsg3', $nmsg3);
		$template->display();
		exit(0);
	}

//	$nmsg .= "\n\n[edited on " . date("F j, Y \\a\\t g:i a") . " by $userData[username]]";

	$forums->db->prepare_query("UPDATE forumposts SET msg = ?, edit = #, parse_bbcode = ? WHERE id = #", $nmsg, time(), $parse_bbcode, $msgid);


	if($userData['replyjump'] == 'forum')
		header("location: forumthreads.php?fid=$line[forumid]");
	else
		header("location: forumviewthread.php?tid=$tid");

	exit;
}
