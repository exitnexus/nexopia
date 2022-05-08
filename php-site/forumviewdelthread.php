<?

	$login=1;

	require_once("include/general.lib.php");


	if(!$mods->isadmin($userData['userid'],"forums"))
		die("Permission denied");


	$isAdmin = $mods->isAdmin($userData['userid'],'listusers');

	$tid = getREQval('tid', 'int');

	if($tid){
		$res = $forums->db->prepare_query("SELECT forumid, moved, title, posts, sticky, locked, announcement, flag, pollid, time FROM forumthreadsdel WHERE id = #", $tid);
		$thread = $res->fetchrow();
	
		if(!$thread || $thread['moved']){
			$tid = 0;
			$msgs->addMsg("Bad Threadid");
		}
	}
	
	if(!$tid){
		incHeader();
	
		echo "<form action=$_SERVER[PHP_SELF] method=post>";
	
		echo "Thread id: <input class=body type=text name=tid value=" . ($tid ? $tid : '') . "> ";
		echo "<input class=body type=submit value=Go>";
		echo "</form>";
		incFooter();
		exit;
	}


	$postsPerPage = $userData['forumpostsperpage'];

	$numpages = ceil(($thread['posts']+1)/$postsPerPage);

	$page = getREQval('page', 'int');

	if($page<0)
		$page=0;
	if($page>=$numpages)
		$page=$numpages-1;

	if($page <= $numpages/2){
		$sortd = 'ASC';
		$offset = $page*$postsPerPage;
		$limit = $postsPerPage;
	}else{
		$sortd = 'DESC';
		$offset = max(0,($thread['posts']+1) - ($page+1)*$postsPerPage);
		$limit = min($postsPerPage, ($thread['posts']+1) - $page*$postsPerPage);
		if($limit <= 0)
			die("Error, bad page $page");
		$limit = abs($limit);
	}


	$res = $forums->db->prepare_query("SELECT id, authorid, time, msg, edit FROM forumpostsdel WHERE threadid = # ORDER BY time $sortd LIMIT $offset, $limit", $tid);
	$postdata = array();
	$posterids = array();
	$posterdata = array();

	while($line = $res->fetchrow()){
		$postdata[] = $line;

		if($line['authorid'])
			$posterids[$line['authorid']] = $line['authorid'];
	}

	if($sortd == 'DESC')
		$postdata = array_reverse($postdata);

	if(count($posterids)){
		$posterdata = getUserInfo($posterids);

		// remove posterids that weren't in there, they are deleted accounts.
		$missingdata = array_diff($posterids, array_keys($posterdata));
		foreach($missingdata as $id){
			$posterdata[$id]['state'] = 'deleted';
			$posterdata[$id]['username'] = getUserName($id);
		}
	}

	$time = time();

	$posts = array();

	// NEX-801
	// Get any cached image paths
	$picids = array();
	foreach( $postdata as $line ) {
		$user = $posterdata[$line['authorid']];
		$picids[] = $line['authorid'] . "-" . $user['firstpic'];
	}	
	$imagepaths = $cache->get_multi($picids, 'galleryimagepaths-');
	
	// figure out if there are any images that didn't have cached paths
	$missingpaths = array_diff($picids, array_keys($imagepaths));

	// If there are any missing paths get them from the DB.
	if(count($missingpaths)){

		// Generate a list of user id, pic id pairs for the query.	
		$keys = array('userid' => '%', 'id' => '#');
		$itemid = array();
		foreach( $postdata as $line ) {
			$user = $posterdata[$line['authorid']];
			$itemid[] = array($line['authorid'], $user['firstpic']);
		}
	
		// Get any remaining images
		$res = $usersdb->prepare_query("SELECT userid, revision, id FROM gallerypics WHERE ^", $usersdb->prepare_multikey($keys, $itemid));
		while($line = $res->fetchrow()){

			// Generate the uncached image paths.
			$imagepaths[$line['userid'] . "-" . $line['id']] = $line['revision'] . '/' . weirdmap($line['userid']) . "/" . $line['id'] . ".jpg";

			// Cache the paths.
			$cache->put("galleryimagepaths-$line[userid]-$line[id]", $imagepaths[$line['userid'] . "-" . $line['id']], 86400*7);
		}
	
	}

	foreach($postdata as $line){
		$post = array();

		$user = $posterdata[$line['authorid']];

		$post['id'] = $line['id'];
		$post['authorid'] = $line['authorid'];
		$post['author'] = $user['username'];
		$post['time'] = $line['time'];

		if($user['state'] == 'active'){
			$post['userstate'] = 'active';
		}elseif($user['state'] == 'frozen'){
			$post['userstate'] = ($isAdmin ? 'frozen' : 'deleted');
		}else{
			$post['userstate'] = 'deleted';
		}

		if($post['userstate'] == 'active' || $post['userstate'] == 'frozen'){
			$post['online'] = ($user['online'] == 'y');
			$post['forumrank'] = ($user['forumrank'] && $user['premiumexpiry'] > $time ? $user['forumrank'] : $forums->forumrank($user['posts']));
			$post['thumb'] = "";
			if($config['forumPic'] && $user['firstpic'])
				$post['thumb'] = $config['thumbloc'] . $imagepaths[$line['authorid'] . "-" . $user['firstpic']];
			$post['age'] = $user['age'];
			$post['sex'] = $user['sex'];
			$post['postcount'] = ($user['showpostcount'] == 'y' ? $user['posts'] : '');
			$post['abuses'] = ($user['abuses'] ? $user['abuses'] : '');
		}

		$post['msg'] = $forums->parsePost($line['msg']);
		$post['edittime'] = $line['edit'];

		$posts[] = $post;
	}

	$template = new template('forums/forumviewdelthread');
	$template->set('pageList', pageList("$_SERVER[PHP_SELF]?tid=$tid", $page, $numpages, 'header'));
	$template->set('tid', $tid);
	$template->set('config', $config);
	$template->set('thread', $thread);
	$template->set('posts', $posts);
	$template->set('forums', $forums);
	$template->display();

