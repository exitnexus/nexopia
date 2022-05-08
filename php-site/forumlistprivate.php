<?

	$login=1;

	require_once("include/general.lib.php");

	$perms = $forums->getForumPerms(26); // must specify a forum, choose admin forum

	if(!$perms['view'])
		die("You don't have permission to see this");

	$isAdmin = $mods->isAdmin($userData['userid'], "forums");

	if($isAdmin && $action == 'Delete' && ($checkid = getPOSTval('checkid', 'array'))){
		foreach($checkid as $id)
			$forums->deleteForum($id);
	}

	$res = $forums->db->query("SELECT id, name, description, threads, forums.posts, time, ownerid, 0 as invited,public FROM forums WHERE official='n' && public='n' ORDER BY posts DESC, id ASC");

	$uids = array();
	$forumdata = array();
	$forumids = array();
	while($line = $res->fetchrow()){
		$forumdata[$line['id']] = $line;
		$forumids[] = $line['id'];
		$uids[] = $line['ownerid'];
	}

	$users = array();

	if($uids)
		$users = getUserName($uids);

	$forums->db->prepare_query("SELECT forumid, count(*) as count FROM foruminvite WHERE forumid IN (#) GROUP BY forumid", $forumids);

	while($line = $forums->db->fetchrow())
		$forumdata[$line['forumid']]['invited'] = $line['count'];

	foreach($forumdata as $forum){
		$ownerExists[$forum['id']] = isset($users[$forum['ownerid']]);
	}

	$template = new template('forums/forumlistprivate');
	$template->set('isAdmin', $isAdmin);
	$template->set('ownerExists', $ownerExists);
	$template->set('forumdata', $forumdata);
	$template->set('users', $users);
	$template->display();
