<?

	$login=0;

	require_once("include/general.lib.php");

	if(!($tid = getREQval('tid', 'int')))
		die("Bad Thread id");

	$noreload = getREQval('noreload', 'bool');
	$time = time();


//get the thread data
	$thread = $cache->get("forumthread-$tid");

	if($thread === false){
		$res = $forums->db->prepare_query("SELECT forumid, moved, title, posts, sticky, locked, announcement, flag, pollid, time FROM forumthreads WHERE id = #", $tid);
		$thread = $res->fetchrow();

		if($thread)
			$cache->put("forumthread-$tid", $thread, 10800);
	}

	if(!$thread || $thread['moved'])
		die("Bad Thread id");

//get the forum data and check permissions
	$perms = $forums->getForumPerms($thread['forumid']);	//checks it's a forum, not a realm

	if(!$perms['view'])
		die("You don't have permission to view this forum");

	$isAdmin = false;
	$sigAdmin = false;
	if($userData['loggedIn']){
		$isAdmin = $mods->isAdmin($userData['userid'],'listusers');
		$sigAdmin = $mods->isAdmin($userData['userid'],'editsig');
	}

	$isMod = $perms['move'] || $perms['deletethreads'] || $perms['deleteposts'] || $perms['lock'] || $perms['stick'];

	$forumdata = $perms['cols'];

	$autolock = false;
	if($thread['locked']=='n' && $forumdata['autolock'] > 0 && (time() - $thread['time']) > $forumdata['autolock'] ){
		$autolock = true;
		$thread['locked']='y';
	}

//process actions, mod or not
	if($userData['loggedIn']){
		switch($action){
			case "subscribe":
				$forums->subscribe($tid);
				$cache->remove("forumread-$userData[userid]-$tid");

				if($noreload)
					exit;

				break;
			case "unsubscribe":
				$forums->unsubscribe($tid);
				$cache->remove("forumread-$userData[userid]-$tid");

				if($noreload)
					exit;

				break;
			case "delete":
				if($perms['deleteposts']){
					if(!($checkID = getPOSTval('checkID', 'array')))
						break;

					foreach($checkID as $id)
						$forums->deletePost($id);
					$thread['posts'] -= count($checkID);
				}
				break;
			case "deletethread":	//deletes the whole thread.
				if($perms['deletethreads']){
					$forums->deleteThread($tid);
					$cache->remove("forumthread-$tid");
					header("location: forumthreads.php?fid=$thread[forumid]");
					exit;
				}
				break;
			case "lock":
				if($perms['lock']){
					if($forums->lockThread($tid)){
						$thread['locked']='y';
						$cache->remove("forumthread-$tid");
					}
				}
				break;
			case "unlock":
				if($perms['lock']){
					if($forums->unlockThread($tid)){
						$thread['locked']='n';
						$cache->remove("forumthread-$tid");
					}
				}
				break;
			case "stick":
				if($perms['stick']){
					if($forums->stickThread($tid)){
						$thread['sticky']='y';
						$cache->remove("forumthread-$tid");
					}
				}
				break;
			case "unstick":
				if($perms['stick']){
					if($forums->unstickThread($tid)){
						$thread['sticky']='n';
						$cache->remove("forumthread-$tid");
					}
				}
				break;
			case "announce":
				if($perms['announce']){
					if($forums->announceThread($tid)){
						$thread['announcement']='y';
						$cache->remove("forumthread-$tid");
					}
				}
				break;
			case "unannounce":
				if($perms['announce']){
					if($forums->unannounceThread($tid)){
						$thread['announcement']='n';
						$cache->remove("forumthread-$tid");
					}
				}
				break;
			case "flag":
				if($perms['flag']){
					if($forums->flagThread($tid)){
						$thread['flag']='y';
						$cache->remove("forumthread-$tid");
					}
				}
				break;
			case "unflag":
				if($perms['flag']){
					if($forums->unflagThread($tid)){
						$thread['flag']='n';
						$cache->remove("forumthread-$tid");
					}
				}
				break;
		}
	}

//set some defaults
	$subscribe = 'n';
	$oldposts = 0;
	$readtime = 0;
	$postsPerPage = 25;

//overwrite them with users prefs/history
	if($userData['loggedIn']){
		$line = $cache->get("forumread-$userData[userid]-$tid");

		if(!$line){
			$res = $forums->db->prepare_query("SELECT subscribe, time, posts FROM forumread WHERE userid = # && threadid = #", $userData['userid'], $tid);
			$line = $res->fetchrow();
		}

		if($line){
			$subscribe = $line['subscribe'];
			$oldposts = $line['posts'];
			$readtime = $line['time'];
		}

		$postsPerPage = $userData['forumpostsperpage'];
	}

//get page stuff
	$numpages = ceil(($thread['posts']+1)/$postsPerPage);

	if(($page = getREQval('page', 'int', -1)) === -1)
		$page = floor($oldposts/$postsPerPage);

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

//init variables
	$postdata = array();
	$posterids = array();
	$posterdata = array();
	$lasttime=0;

	if($userData['loggedIn'])
		$posterids[$userData['userid']] = $userData['userid'];

//grab all posts for this page
	$res = $forums->db->prepare_query("SELECT id, authorid, time, msg, edit FROM forumposts WHERE threadid = # ORDER BY time $sortd LIMIT $offset, $limit", $tid);

	while($line = $res->fetchrow()){
		$postdata[] = $line;

		if($sortd == 'ASC' || !$lasttime)
			$lasttime=$line['time'];

		$posterids[$line['authorid']] = $line['authorid'];
	}

	if($sortd == 'DESC')
		$postdata = array_reverse($postdata);

//grab the data for the users that posted
	if(count($posterids)){
		$posterdata = getUserInfo($posterids);

	// remove posterids that weren't in there, they are deleted accounts.
		$missingdata = array_diff($posterids, array_keys($posterdata));
		foreach ($missingdata as $id){
			$posterdata[$id]['state'] = 'deleted';
			$posterdata[$id]['username'] = getUserName($id);
			unset($posterids[$id]);
		}

	//set defaults for posts and signatures for non-deleted accounts
		foreach($posterids as $id){
			$posterdata[$id]['posts'] = 0;
			$posterdata[$id]['nsigniture'] = '';
		}

	//get postcounts
		$postcounts = $cache->get_multi($posterids, 'forumuserposts-');

		$missingcounts = array_diff($posterids, array_keys($postcounts));
		if(count($missingcounts)){
			$res = $usersdb->prepare_query("SELECT userid, posts FROM users WHERE userid IN (%)", $missingcounts);

			while($line = $res->fetchrow()){
				$postcounts[$line['userid']] = $line['posts'];
				$cache->put("forumuserposts-$line[userid]", $line['posts'], 86400);
			}
		}

		foreach($postcounts as $id => $posts)
			$posterdata[$id]['posts'] = $posts;

	//get sigs if the user wants them
		if($userData['loggedIn'] && $userData['showsigs'] == 'y'){

			$sigs = $cache->get_multi($posterids, 'forumusersigs-');

			$missingsigs = array_diff($posterids, array_keys($sigs));

			if(count($missingsigs)){
				$res = $usersdb->prepare_query("SELECT userid, nsigniture, enablesignature FROM profile WHERE userid IN (%)", $missingsigs);

				while($line = $res->fetchrow()){
					$sigs[$line['userid']] = $line;
					$cache->put("forumusersigs-$line[userid]", $line, 86400*7);
				}
			}

			foreach($sigs as $id => $sig)
				if($sig['enablesignature'] == 'y')
					$posterdata[$id]['nsigniture'] = $sig['nsigniture'];
		}
	}


//update the users forum read status
	if($userData['loggedIn']){
		$newoldposts = max($oldposts, $postsPerPage*$page + count($postdata) - 1);
		$newreadtime = max($readtime, $lasttime);

		$curtime = time();

		if($readtime > 0)
			$forums->db->prepare_query("UPDATE forumread SET time = #, readtime = #, posts = # WHERE userid = # && threadid = #",
			                                  $time, $curtime, $newoldposts, $userData['userid'], $tid);
		if($readtime == 0 || ($readtime > 0 && $forums->db->affectedrows()==0))
			$forums->db->prepare_query("INSERT IGNORE INTO forumread SET time = #, readtime = #, posts = #, userid = #, threadid = #",
			                                  $time, $curtime, $newoldposts, $userData['userid'], $tid);

		$cache->put("forumread-$userData[userid]-$tid", array('subscribe' => $subscribe, 'time' => $newreadtime, 'posts' => $newoldposts), 10800);

	//increase the threads view counter, but only if they're reading something new (refreshing the page doesn't work)
		if($newreadtime > $readtime)
			$forums->db->prepare_query("UPDATE forumthreads SET reads=reads+1" . ($autolock ? ", locked = 'y'" : '') . " WHERE id = #", $tid);
	}


//get a poll if there is one
	$poll=false;
	if($thread['pollid'] && $thread['locked'] == 'n'){
		if($userData['loggedIn'] && $action=='Vote'){
			$ans = getREQval('ans', 'int', -1);
			$k = getREQval('k');

			if($ans != -1 && checkKey($tid, $k))
				$polls->votePoll($thread['pollid'], $ans);
		}

		if($page==0){
			$poll = $polls->getPoll($thread['pollid'], false);
			$voted = $polls->pollVoted($thread['pollid']);
		}


		if($poll){
			$showPollChoices = false;
			$width = array();
			$votePercent = array();
			if((!$userData['loggedIn'] || !$voted) && $thread['locked'] == 'n'){
				$showPollChoices = true;
			}else{
				$maxval=0;
				foreach($poll['answers'] as $ans)
					if($ans['votes']>$maxval)
						$maxval = $ans['votes'];

				foreach($poll['answers'] as $ans){
					$width[] = ($poll['tvotes'] == 0) ? 0 : (((int)$ans["votes"])*$config['maxpollwidth']/$maxval);
					$votePercent[] = ($poll['tvotes'] == 0 ? '' : '(' . number_format($ans["votes"]/$poll['tvotes']*100, 1) . ' %)' );
				}
			}
		}
	}


//stores all the data for a single post
	$posts = array();

	$jpid = 0;

	foreach($postdata as $line){
		if($readtime >= $line['time'])
			$jpid = $line['id'];

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
				$post['thumb'] = $config['thumbloc'] . floor($line['authorid']/1000) . '/' . weirdmap($line['authorid']) . "/" . $user['firstpic'] . ".jpg";
			$post['age'] = $user['age'];
			$post['sex'] = $user['sex'];
			$post['postcount'] = ($user['showpostcount'] == 'y' ? $user['posts'] : '');
			$post['abuses'] = (($sigAdmin  || ($forumdata['official'] == 'y' && $perms['mute']) || $perms['globalmute']) && $user['abuses'] ? $user['abuses'] : '');

			$post['sig'] = $user['nsigniture'];

			$post['sigedit'] = $sigAdmin;
			$post['mute'] = $perms['mute'];
		}else{
			$post['sigedit'] = false;
			$post['mute'] = false;
			$post['sig'] = '';
		}

		$post['msg'] = $forums->parsePost($line['msg']);
		$post['edittime'] = $line['edit'];


		$post['postedit'] = ($perms['editallposts'] || (($perms['editownposts'] == 1 || $perms['editownposts'] > $time - $line['time']) && $line['authorid'] == $userData['userid']));
		$post['reply'] = $perms['post'];
		$post['reporttype'] = ($userData['loggedIn'] ? MOD_FORUMPOST : '');


		$posts[] = $post;
	}

	$canPost = false;
	$lastPage = false;
	if($userData['loggedIn'] && $perms['post'] && ($thread['locked']=='n' || $perms['postlocked'])){
		$canPost = true;
		if($page==$numpages-1){
			$lastPage = true;
		}
	}

	$pid = getREQval('pid', 'int');
	if(!$pid && ($userData['loggedIn'] && $userData['forumjumplastpost'] == 'y' && $jpid))
		$pid = $jpid;


	$template = new template('forums/forumviewthread');
	$template->set('key', makeKey($tid));
	$template->set('pageList', pageList("$_SERVER[PHP_SELF]?tid=$tid",$page,$numpages,'header'));
	$template->set('thread', $thread);
	$template->set('config', $config);
	$template->set('forumTrail', $forums->getForumTrail($forumdata, "header"));
	$template->set('tid', $tid);
	$template->set('userData', $userData);
	$template->set('subscribe', $subscribe);
	$template->set('poll', $poll);
	if($poll){
		$template->set('showPollChoices', $showPollChoices);
		$template->set('width', $width);
		$template->set('votePercent', $votePercent);
	}
	$template->set('isMod', $isMod);
	$template->set('posts', $posts);
	$template->set('perms', $perms);
	$template->set('canPost', $canPost);
	$template->set('lastPage', $lastPage);
	$template->set('pid', $pid);
	$template->set('subscribeSelected', (($userData['loggedIn'] && ($userData['autosubscribe'] == 'y' || $subscribe=='y')) ? ' selected' : ''));
	$template->set('currentPage', $_SERVER['REQUEST_URI']);
	$template->set('editBox', editBoxStr(""));
	$template->display();
