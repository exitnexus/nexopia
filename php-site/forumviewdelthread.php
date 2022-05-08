<?

	$login=1;

	require_once("include/general.lib.php");

	if(!($tid = getREQval('tid', 'int')))
		die("Bad Thread id");

	$perms = $forums->getForumPerms(26); //TODO: Fix this!

	if(!$perms['view'])
		die("You don't have permission to view this");


	$res = $forums->db->prepare_query("SELECT forumid, moved, title, posts, sticky, locked, announcement, flag, pollid, time FROM forumthreadsdel WHERE id = #", $tid);
	$thread = $res->fetchrow();

	if(!$thread || $thread['moved'])
		die("Bad Thread id");



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


	$res = $forums->db->prepare_query("SELECT id, author, authorid, time, msg, edit FROM forumpostsdel WHERE threadid = # ORDER BY time $sortd LIMIT $offset, $limit", $tid);
	$postdata = array();
	$posterids = array();
	$posterdata = array();


	if($userData['loggedIn'] && ($thread['locked']=='n' || $isMod) && $page==$numpages-1)
		$posterids[$userData['userid']] = $userData['userid'];

	$lasttime=0;
	while($line = $res->fetchrow()){
		$postdata[] = $line;
		if($sortd == 'ASC' || !$lasttime)
			$lasttime=$line['time'];

		if($line['authorid'])
			$posterids[$line['authorid']] = $line['authorid'];
	}

	if($sortd == 'DESC')
		$postdata = array_reverse($postdata);

	if(count($posterids)){
		$posterdata = getUserInfo($posterids);

		// remove posterids that weren't in there, they are deleted accounts.
		$missingdata = array_diff($posterids, array_keys($posterdata));
		foreach ($missingdata as $id)
		{
			$posterdata[$id]['username'] = getUserName($id);
			unset($posterids[$id]);
		}

		foreach($posterids as $id){
			$posterdata[$id]['posts'] = 0;
			$posterdata[$id]['nsigniture'] = '';
		}

		$res = $usersdb->prepare_query("SELECT userid, posts FROM users WHERE userid IN (%)", $posterids);

		while($line = $res->fetchrow())
			$postcounts[$line['userid']] = $line['posts'];


		foreach($postcounts as $id => $posts)
			$posterdata[$id]['posts'] = $posts;
	}




	$i = -1;
	foreach($postdata as $line){
		$i++;
		$frozenAuthor[$i] = false;
		$premiumRank[$i] = false;
		$showPicture[$i] = false;
		$showAbuses[$i] = false;

		if(isset($posterdata[$line['authorid']]) && $posterdata[$line['authorid']]['state'] == 'frozen'){
			$frozenAuthor[$i] = true;
			$data = $posterdata[$line['authorid']];
		}else{
			$line['authorid']=0;
		}

		if($line['authorid']){
			if($data['forumrank']!="" && $data['premiumexpiry'] > $time)
				$premiumRank[$i] = true;
			$forumRank[$i] = $forums->forumrank($posterdata[$line['authorid']]['posts']);
		}

		if($config['forumPic'] && $line['authorid'] && $data['firstpic']>0) {
			$showPicture[$i] = true;
			$imageDirectory[$i] = floor($data['authorid']/1000) . '/' . weirdmap($data['authorid']);
		}
		if($line['authorid']){
			if($data['showpostcount'] == 'y') {
				$postCount[$i] = number_format($data['posts']);
			}


			if($userData['loggedIn'] && $sigAdmin && $data['abuses']){
				$showAbuses[$i] = true;
			}
		}

		$parsedPost[$i] = $forums->parsePost($line['msg']);

		$links = array();

		if($sigAdmin)
			$links[] = "<a class=small href=/manageprofile.php?section=forums&uid=$line[authorid]>Sig</a>";
		if($perms['mute'])
			$links[] = "<a class=small href=/forummute.php?action=add&fid=$thread[forumid]&tid=$tid&username=" . htmlentities($line['author']) . ">Mute</a>";

		$links[] =  "<a class=small href=#top>Top</a>";

		$displayLinks[$i] = implode(" &nbsp; &nbsp; ", $links);

	}

	$template = new template('forums/forumviewdelthread');
	$template->set('pageList', pageList("$_SERVER[PHP_SELF]?tid=$tid",$page,$numpages,'header'));
	$template->set('tid', $tid);
	$template->set('config', $config);
	$template->set('thread', $thread);
	$template->set('postdata', $postdata);
	$template->set('posterdata', $posterdata);
	$template->set('premiumRank', $premiumRank);
	$template->set('frozenAuthor', $frozenAuthor);
	$template->set('postCount', $postCount);
	$template->set('showPicture', $showPicture);
	$template->set('imageDirectory', $imageDirectory);
	$template->set('showAbuses', $showAbuses);
	$template->set('parsedPost', $parsedPost);
	$template->set('displayLinks', $displayLinks);
	$template->set('forums', $forums);
	$template->set('forumRank', $forumRank);
	$template->display();

