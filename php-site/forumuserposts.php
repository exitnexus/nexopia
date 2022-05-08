<?

	$login=1;

	require_once("include/general.lib.php");
	$template = new template('forums/forumuserposts');
	
	$globalmodpowers = $forums->getModPowers($userData['userid'], array(0));

	if(!isset($globalmodpowers[0]))
		die("You don't have permission.");

	$viewall = $mods->isAdmin($userData['userid'],'forums') || $globalmodpowers[0]['view'] == 'y';
	$delete =  $mods->isAdmin($userData['userid'],'forums') || $globalmodpowers[0]['deleteposts'] == 'y';

	$user = getPOSTval('user');
	$uid = getUserID($user);
	$group = getPOSTval('group', 'bool');
	
	$isAdmin = $mods->isAdmin($userData['userid'],'listusers');


	$postdata = array();
	$threaddata = array();
	$forumdata = array();
	$parsedPost = array();
	$forumTrail = array();

	if($uid){
		if($action == "Delete" && $delete && ($checkID = getPOSTval('checkID', 'array'))){
			foreach($checkID as $id)
				$forums->deletePost($id);
		}


		$postsPerPage = 100;
		$page = getREQval('page', 'int');

		$offset = $page*$postsPerPage;
		$limit = $postsPerPage;

		$threadids = array();
		$forumids = array();
		$inviteids = array();

		$res = $forums->db->prepare_query("SELECT id, threadid, time, msg, edit FROM forumposts WHERE authorid = # ORDER BY time DESC LIMIT #, #", $uid, $offset, $limit);

		while($line = $res->fetchrow()){
			$postdata[] = $line;
			$threadids[$line['threadid']] = $line['threadid'];
		}

		if(count($threadids)){

			if($group)
				sortCols($postdata, SORT_ASC, SORT_NUMERIC, 'time', SORT_ASC, SORT_NUMERIC, 'threadid');
			else
				$postdata = array_reverse($postdata);


			$res = $forums->db->prepare_query("SELECT id, forumid, title, sticky, locked, announcement, flag FROM forumthreads WHERE id IN (#)", $threadids);

			while($line = $res->fetchrow()){
				$threaddata[$line['id']] = $line;
				$forumids[$line['forumid']] = $line['forumid'];
			}

			if(count($forumids)){
				$res = $forums->db->prepare_query("SELECT * FROM forums WHERE id IN (#)", $forumids);
	
				while($line = $res->fetchrow()){
					$forumdata[$line['id']] = $line;
					$forumdata[$line['id']]['view'] = $viewall || $line['public'] == 'y';
					if(!$forumdata[$line['id']]['view'])
						$inviteids[] = $line['id'];
				}
			}

			if(count($inviteids)){
				$res = $forums->db->prepare_query("SELECT forumid FROM foruminvite WHERE userid = # && forumid IN (#)", $userData['userid'], $inviteids);

				while($line = $res->fetchrow())
					$forumdata[$line['forumid']]['view'] = true;
			}
		}
	}

	if($uid){
		$time = time();
		$i = -1;
		foreach($postdata as $line){
			$i++;
			if(!$forumdata[$threaddata[$line['threadid']]['forumid']]['view'])
				continue;
			$parsedPost[$i] = $forums->parsePost($line['msg']);
			$forumTrail[$i] = $forums->getForumTrail($forumdata[$threaddata[$line['threadid']]['forumid']], "header");
		}
	}
	
	$template->set('config', $config);
	$template->set('delete', $delete);
	$template->set('lastthreadid', 0);
	$template->set('parsedPost', $parsedPost);
	$template->set('user', $user);
	$template->set('uid', $uid);
	$template->set('group', $group);
	$template->set('isAdmin', $isAdmin);
	$template->set('groupCheck', makeCheckBox('group', "Group by Thread", $group));
	$template->set('threaddata', $threaddata);
	$template->set('postdata', $postdata);
	$template->set('forumdata', $forumdata);
	$template->set('forumTrail', $forumTrail);
	$template->display();

