<?

	$login=0;

	require_once("include/general.lib.php");

	$id = getREQval('id', 'int');
	$msg = getPOSTval('msg');
	$checkID = getREQval('checkID', 'array');
	$action = getREQval('action');
	$postaction = getPOSTval('postaction');
	$usedb = getREQval('db');
	$parse_bbcode = getPOSTval('parse_bbcode', 'bool');

	if(empty($id))
		die("Bad id");

	$isAdmin = false;
	if($userData['loggedIn'])
		$isAdmin = $mods->isAdmin($userData['userid'],'deletecomments');

//add comment
	if($userData['loggedIn'] && isset($postaction) && isset($msg) && isset($id))
		addComment($id,$msg,$postaction, array(), $parse_bbcode, $usedb);
	if($usedb == 'polls')
	{
		$res = $polldb->prepare_query("SELECT question FROM polls WHERE id = ?", $id);
		$data = $res->fetchrow();
	}
	else
	{
		$res = $articlesdb->prepare_query("SELECT title,category FROM articles WHERE id = ?", $id);
		$data = $res->fetchrow();
	}
	if(!$data)
		die("Bad id");

//delete
	if($isAdmin && $action=="Delete" && isset($checkID) && is_array($checkID)){
		if($usedb == 'polls')
		{
			$polldb->prepare_query("DELETE pollcomments, pollcommentstext FROM pollcomments LEFT JOIN pollcommentstext ON pollcomments.id = pollcommentstext.id WHERE itemid = ? && pollcomments.id IN (?)", $id, $checkID);

			$polldb->prepare_query("UPDATE polls SET comments = comments - ? WHERE id = ?", count($checkID), $id);
			$mods->adminlog("delete poll comments", "Delete poll comments: poll $id");
		}
		else
		{
			$articlesdb->prepare_query("DELETE comments, commentstext FROM comments LEFT JOIN commentstext ON comments.id = commentstext.id WHERE itemid = ? && comments.id IN (?)", $id, $checkID);

			$articlesdb->prepare_query("UPDATE articles SET comments = comments - ? WHERE id = ?", count($checkID), $id);
			$mods->adminlog("delete article comments", "Delete article comments: article $id");
		}
	}

	$uids = array();
	$page = getREQval('page', 'int');
	if($usedb == 'polls')
	{

		$res = $polldb->prepare_query("SELECT SQL_CALC_FOUND_ROWS pollcomments.id, authorid, time, msg, nmsg, parse_bbcode FROM pollcomments, pollcommentstext WHERE itemid = ?  && pollcomments.id = pollcommentstext.id ORDER BY pollcomments.id ASC LIMIT " . ($page*$config['linesPerPage']) . ", $config[linesPerPage]", $id);

		$comments = array();
		while($line = $res->fetchrow())
		{
			$line['nmsg'] = html_sanitizer::sanitize($line['msg']);
			if($line['parse_bbcode'] == 'y')
			{
				$line['nmsg'] = parseHTML($line['nmsg']);
				$line['nmsg'] = smilies($line['nmsg']);
				$line['nmsg'] = wrap($line['nmsg']);
				$line['nmsg'] = nl2br($line['nmsg']);
			}
			$comments[] = $line;
			$uids[$line['authorid']] = $line['authorid'];
		}
		$numrows = $res->totalrows();
	}
	else
	{

		$res = $articlesdb->prepare_query("SELECT SQL_CALC_FOUND_ROWS comments.id, authorid, time, msg, nmsg, parse_bbcode FROM comments, commentstext WHERE itemid = ?  && comments.id = commentstext.id ORDER BY comments.id ASC LIMIT " . ($page*$config['linesPerPage']) . ", $config[linesPerPage]", $id);

		$comments = array();
		while($line = $res->fetchrow())
		{
			$line['nmsg'] = html_sanitizer::sanitize($line['msg']);
			if($line['parse_bbcode'] == 'y')
			{
				$line['nmsg'] = parseHTML($line['nmsg']);
				$line['nmsg'] = smilies($line['nmsg']);
				$line['nmsg'] = wrap($line['nmsg']);
				$line['nmsg'] = nl2br($line['nmsg']);
			}
			$comments[] = $line;
			$uids[$line['authorid']] = $line['authorid'];
		}

		$numrows = $res->totalrows();
	}

	if(count($uids)){
		$usernames = getUserName($uids);

		foreach($comments as $k => $v)
			$comments[$k]['author'] = $usernames[$v['authorid']];
	}

	$numpages =  ceil($numrows / $config['linesPerPage']);
	if($usedb == 'polls')
		$root = 0;
	else
	{
		$cats = new category( $articlesdb, "cats");
		$root = $cats->makeroot($data['category']);
	}

	$template = new template('comments/comments');

	$template->set('root', $root);
	if($usedb=='polls')
		$template->set('pageList', pageList("$_SERVER[PHP_SELF]?id=$id&db=polls",$page,$numpages,'header'));
	else
		$template->set('pageList', pageList("$_SERVER[PHP_SELF]?id=$id",$page,$numpages,'header'));
	$template->set('noComment', count($comments) == 0);
//	$template->set('checkParseBB', makeCheckBox('parse_bbcode', 'Parse BBcode', $userData['parse_bbcode']));
	$template->set("checkParseBB", '<input type="hidden" name="parse_bbcode" value="y"/>');
	$template->set('data', $data);
	$template->set('isAdmin', $isAdmin);
	$template->set('comments', $comments);
	$template->set('userData', $userData);
	$template->set('id', $id);
	$template->set('db', $usedb);
	ob_start();
	editBox("");
	$template->set('editBox', ob_get_clean());
	$template->display();

