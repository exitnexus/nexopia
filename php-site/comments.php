<?

	$login=0;

	require_once("include/general.lib.php");

	$id = getREQval('id', 'int');
	$usedb = getREQval('db');
	$page = getREQval('page', 'int');

	if($usedb != 'polls' && $usedb != 'articles')
		die("Bad DB");

	if(empty($id))
		die("Bad id");

	$isAdmin = false;
	if($userData['loggedIn'])
		$isAdmin = $mods->isAdmin($userData['userid'],'deletecomments');

	if($usedb == 'polls')
		$res = $polldb->prepare_query("SELECT question FROM polls WHERE id = #", $id);
	else
		$res = $articlesdb->prepare_query("SELECT title, category FROM articles WHERE id = #", $id);
	$data = $res->fetchrow();

	if(!$data)
		die("Bad id");


	switch($action){
		//add comment
		case "Post":
		case "Preview":
			$msg = getPOSTval('msg');

			if($userData['loggedIn'] && $msg)
				addComment($id, $msg, $action, array(), $usedb);
			break;

		case "Delete":
			$checkID = getREQval('checkID', 'array');
			if($isAdmin && count($checkID)){
				if($usedb == 'polls'){
					$polldb->prepare_query("DELETE pollcomments, pollcommentstext FROM pollcomments LEFT JOIN pollcommentstext ON pollcomments.id = pollcommentstext.id WHERE itemid = # && pollcomments.id IN (#)", $id, $checkID);

					$polldb->prepare_query("UPDATE polls SET comments = comments - # WHERE id = #", count($checkID), $id);
					$mods->adminlog("delete poll comments", "Delete poll comments: poll $id");
				}else{
					$articlesdb->prepare_query("DELETE comments, commentstext FROM comments LEFT JOIN commentstext ON comments.id = commentstext.id WHERE itemid = # && comments.id IN (#)", $id, $checkID);

					$articlesdb->prepare_query("UPDATE articles SET comments = comments - # WHERE id = #", count($checkID), $id);
					$mods->adminlog("delete article comments", "Delete article comments: article $id");
				}
			}
			break;
	}

	if($usedb == 'polls')
		$res = $polldb->prepare_query("SELECT SQL_CALC_FOUND_ROWS pollcomments.id, authorid, time, msg, nmsg FROM pollcomments, pollcommentstext WHERE itemid = # && pollcomments.id = pollcommentstext.id ORDER BY pollcomments.id ASC LIMIT #, #", $id, ($page*$config['linesPerPage']), $config['linesPerPage']);
	else
		$res = $articlesdb->prepare_query("SELECT SQL_CALC_FOUND_ROWS comments.id, authorid, time, msg, nmsg FROM comments, commentstext WHERE itemid = # && comments.id = commentstext.id ORDER BY comments.id ASC LIMIT #, #", $id, ($page*$config['linesPerPage']), $config['linesPerPage']);

	$uids = array();
	$comments = array();
	while($line = $res->fetchrow()){
		$line['nmsg'] = removeHTML($line['msg']);
		$line['nmsg'] = parseHTML($line['nmsg']);
		$line['nmsg'] = smilies($line['nmsg']);
		$line['nmsg'] = wrap($line['nmsg']);
		$line['nmsg'] = nl2br($line['nmsg']);

		$comments[] = $line;
		$uids[$line['authorid']] = $line['authorid'];
	}

	$numrows = $res->totalrows();
	$numpages =  ceil($numrows / $config['linesPerPage']);

	if(count($uids)){
		$usernames = getUserName($uids);

		foreach($comments as $k => $v)
			$comments[$k]['author'] = $usernames[$v['authorid']];
	}

	if($usedb == 'polls'){
		$root = 0;
	}else{
		$cats = new category( $articlesdb, "cats");
		$root = $cats->makeroot($data['category']);
	}

	$template = new template('comments/comments');

	$template->set('root', $root);
	$template->set('pageList', pageList("$_SERVER[PHP_SELF]?id=$id&db=$usedb",$page,$numpages,'header'));
	$template->set('noComment', count($comments) == 0);
	$template->set('data', $data);
	$template->set('isAdmin', $isAdmin);
	$template->set('comments', $comments);
	$template->set('userData', $userData);
	$template->set('id', $id);
	$template->set('db', $usedb);
	$template->set('editBox', editBoxStr(""));
	$template->display();
	exit;


function addComment($id, $msg, $preview = "changed", $params = array(), $usedb = 'polls'){
	global $userData, $articlesdb, $polldb;

	if(!$userData['loggedIn'])
		return;

	if(trim($msg)=="")
		return;

	$nmsg = removeHTML($msg);
	$nmsg2 = parseHTML($nmsg);
	$nmsg3 = smilies($nmsg2);
	$nmsg3 = wrap($nmsg3);
	$nmsg3 = nl2br($nmsg3);

	if($preview == "Preview"){
		incHeader();

		echo "Here is a preview of what the post will look like:<hr><blockquote>\n";

		echo $nmsg3;

		echo "</blockquote><hr>\n";

		echo "<form action=$_SERVER[PHP_SELF] method=post name=editbox>\n";
		echo "<table width=100% cellspacing=0>";

		echo "<input type=hidden name='id' value='" . htmlentities($id) . "'>\n";
		echo "<input type=hidden name='action' value='comment'>\n";
		echo "<input type=hidden name='db' value='" . htmlentities($usedb) . "'>\n";

		foreach($params as $k => $v)
			echo "<input type=hidden name='$k' value='" . htmlentities($v) . "'>\n";

		echo "<tr><td class=body>";

		editBox($nmsg);

		echo "</td></tr>\n";
		echo "<tr><td class=header align=center><input type=submit name=postaction value=Preview> <input type=submit name=postaction value='Post' accesskey='s' onClick='checksubmit()'></td></tr>\n";

		echo "</table>";
		echo "</form>";

		incFooter();
		exit;
	}

	$uid = $userData['userid'];
	enqueue("Comment", "create", $uid, array($uid, $id));

	if($usedb == 'polls'){
		$result=$polldb->prepare_query("SELECT id FROM pollcomments WHERE itemid = # && time > # && authorid = #", $id, time() - 15, $userData['userid']);
		if($result->fetchrow()) //double post
			return false;

		$polldb->prepare_query("INSERT INTO pollcomments SET itemid = #, authorid = #, time = #", $id, $userData['userid'], time());
		$insertid = $polldb->insertid();

		$polldb->prepare_query("INSERT INTO pollcommentstext SET id = #, msg = ?, nmsg = ?", $insertid, $msg, $nmsg3);
		$polldb->prepare_query("UPDATE polls SET comments = comments+1 WHERE id = #", $id);
	}else{
		$result = $articlesdb->prepare_query("SELECT id FROM comments WHERE itemid = # && time > # && authorid = #", $id, time() - 15, $userData['userid']);
		if($result->fetchrow()) //double post
			return false;

		$articlesdb->prepare_query("INSERT INTO comments SET itemid = #, authorid = #, time = #", $id, $userData['userid'], time());
		$insertid = $articlesdb->insertid();

		$articlesdb->prepare_query("INSERT INTO commentstext SET id = #, msg = ?, nmsg = ?", $insertid, $msg, $nmsg3);

		$articlesdb->prepare_query("UPDATE articles SET comments = comments+1 WHERE id = #", $id);
	}

	scan_string_for_notables($nmsg);

}
