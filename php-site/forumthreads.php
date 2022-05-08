<?

	$login=0;

	require_once("include/general.lib.php");

	addRefreshHeaders();


	if(!($fid = getREQval('fid', 'int')))
		die("Bad Forum id");

	$perms = $forums->getForumPerms($fid);	//checks it's a forum, not a realm, users permissions here, and column info

	if(!$perms['view'])
		die("You don't have permission to view this forum");

	$isMod = $perms['move'] || $perms['deletethreads'] || $perms['lock'] || $perms['stick'] || $perms['announce'];
	$showModBar = $perms['move'] || $perms['deletethreads'] || $perms['lock'] || $perms['stick'] || $perms['announce'] || $perms['mute'] || $perms['modlog'] || $perms['invite'] || $perms['editmods'];

	$forumdata = $perms['cols'];

	if($userData['loggedIn']){
		switch($action){
			case "markallread":
				if($userData['loggedIn'] && ($k = getREQval('k')) && checkKey($fid, $k)){
					$time = time();
					$forums->db->prepare_query("UPDATE forumupdated SET readalltime = # WHERE userid = # && forumid = #", $time, $userData['userid'], $fid);
					if($forums->db->affectedrows()==0){
						$forums->db->prepare_query("INSERT IGNORE INTO forumupdated SET userid = #, forumid = #, time = #, readalltime = #", $userData['userid'], $fid, $time, $time);
					}
					if($config['memcached'])
						$cache->put("forumreadalltime-$userData[userid]-$fid", $time, 86400);
				}
				break;
			case "delete":
				if($perms['deletethreads'] && ($checkID = getPOSTval('checkID', 'array')))
					foreach($checkID as $id)
						$forums->deleteThread($id);
				break;
			case "lock":
				if($perms['lock'] && ($checkID = getPOSTval('checkID', 'array')))
					foreach($checkID as $id)
						$forums->lockThread($id);
				break;
			case "unlock":
				if($perms['lock'] && ($checkID = getPOSTval('checkID', 'array')))
					foreach($checkID as $id)
						$forums->unlockThread($id);
				break;
			case "stick":
				if($perms['stick'] && ($checkID = getPOSTval('checkID', 'array')))
					foreach($checkID as $id)
						$forums->stickThread($id);
				break;
			case "unstick":
				if($perms['stick'] && ($checkID = getPOSTval('checkID', 'array')))
					foreach($checkID as $id)
						$forums->unstickThread($id);
				break;
			case "announce":
				if($perms['announce'] && ($checkID = getPOSTval('checkID', 'array')))
					foreach($checkID as $id)
						$forums->announceThread($id);
				break;
			case "unannounce":
				if($perms['announce'] && ($checkID = getPOSTval('checkID', 'array')))
					foreach($checkID as $id)
						$forums->unannounceThread($id);
				break;
			case "flag":
				if($perms['flag'] && ($checkID = getPOSTval('checkID', 'array')))
					foreach($checkID as $id)
						$forums->flagThread($id);
				break;
			case "unflag":
				if($perms['flag'] && ($checkID = getPOSTval('checkID', 'array')))
					foreach($checkID as $id)
						$forums->unflagThread($id);
				break;
			case "move":
				if($perms['move'] && ($checkID = getPOSTval('checkID', 'array'))){
					if($moveto = getPOSTval('moveto', 'int')){
						foreach($checkID as $id)
							$forums->moveThread($id, $moveto);
					}else{
						$msgs->addMsg("You must specify a destination");
					}
				}
				break;
			case "withdraw":
				if($userData['loggedIn'] && ($k = getREQval('k')) && checkKey($fid, $k)){
					if($perms['invited'] && $userData['userid'] != $forumdata['ownerid']){
						$forums->unInvite($userData['userid'], $fid);
						$perms['invited']=false;
					}
				}
				break;
		}
	}

	if($userData['loggedIn']){
		$readalltime = $cache->get("forumreadalltime-$userData[userid]-$fid");

		if($readalltime === false){
			$res = $forums->db->prepare_query("SELECT readalltime FROM forumupdated WHERE userid = # && forumid = #", $userData['userid'], $fid);
			$updated = $res->fetchrow();
			if($updated)
				$readalltime = $updated['readalltime'];
			else
				$readalltime = 0;

			$cache->put("forumreadalltime-$userData[userid]-$fid", $readalltime, 86400);
		}

		$forums->db->prepare_query("UPDATE forumupdated SET time = # WHERE userid = # && forumid = #", time(), $userData['userid'], $fid);
		if($forums->db->affectedrows()==0)
			$forums->db->prepare_query("INSERT IGNORE INTO forumupdated SET time = #, userid = #, forumid = #", time(), $userData['userid'], $fid);

		$forumsort = $userData['forumsort'];
		$forumpostsperpage = $userData['forumpostsperpage'];
	}else{
		$readalltime = 0;
		$forumsort='post';
		$forumpostsperpage = 25;
	}

	$page = getREQval('page', 'int');

	$age = getREQval('age', 'int', $forumdata['sorttime']);

	if($age==0)
		$startdate = 0;
	else
		$startdate = time() - ($age * 86400);

	$threaddata = array();
	$threadids = array();
	$uids = array();

	$select = "id, title, authorid, `reads`, posts, time, lastauthorid, locked, sticky, moved, announcement, flag, pollid, '0' as new, '0' as subscribe";

	if($page == 0){
		$res = $forums->db->prepare_query("SELECT $select FROM  forumthreads USE INDEX (announcement) WHERE announcement='y' && forumid = # ORDER BY time DESC", $fid);

		while($line = $res->fetchrow()){
			$threaddata[$line['id']] = $line;
			$threadids[$line['id']] = $line['id'];
			$uids[$line['authorid']] = $line['authorid'];
			$uids[$line['lastauthorid']] = $line['lastauthorid'];
		}
	}


	$res = $forums->db->prepare_query("SELECT SQL_CALC_FOUND_ROWS $select FROM forumthreads USE INDEX (forumid) WHERE forumid = # && time >= # && sticky IN ('y','n') && announcement='n' ORDER BY sticky DESC," . ($forumsort=='post' ? "time" : "id") . " DESC LIMIT " . ($page*$config['linesPerPage']) . ", $config[linesPerPage]", $fid, $startdate);

	while($line = $res->fetchrow()){
		$threaddata[$line['id']] = $line;
		$threadids[$line['id']] = $line['id'];
		$uids[$line['authorid']] = $line['authorid'];
		$uids[$line['lastauthorid']] = $line['lastauthorid'];
	}

	$numthreads = $res->totalrows();
	$numpages =  ceil($numthreads / $config['linesPerPage']);

	$usernames = getUserName($uids);

	foreach($threaddata as $k => $v){
		$threaddata[$k]['author'] = ($v['authorid'] ? $usernames[$v['authorid']] : '');
		$threaddata[$k]['lastauthor'] = ($v['lastauthorid'] ? $usernames[$v['lastauthorid']] : '');
	}

//all new: 2, some new: 1, no new: 0
	if($userData['loggedIn'] && count($threadids)>0){
		$subscribes = array();
		$res = $forums->db->prepare_query("SELECT threadid,time,subscribe FROM forumread WHERE userid='$userData[userid]' && threadid IN (#)", $threadids);

		while($line = $res->fetchrow())
			$subscribes[$line['threadid']] = $line;

		foreach($threaddata as $threadid => $data){
			if(!isset($subscribes[$threadid])){
				if($readalltime < $threaddata[$threadid]['time'])
					$threaddata[$threadid]['new'] = 2; //subscribe is already 0
			}else{
				$threaddata[$threadid]['new'] = (($readalltime < $data['time']) && ($subscribes[$threadid]['time'] < $data['time']) ? 1 : 0);
				$threaddata[$threadid]['subscribe'] = ($subscribes[$threadid]['subscribe'] == 'y');
			}
		}
	}

	$lockids = array();
	foreach($threaddata as $threadid => $data){
		if($forumdata['autolock'] > 0 && $data['locked']=='n' && (time() - $data['time']) > $forumdata['autolock']){
			$lockids[] = $threadid;
			$threaddata[$threadid]['locked'] = 'y';
		}
	}
	if(count($lockids) > 0)
		$forums->db->prepare_query("UPDATE forumthreads SET locked='y' WHERE id IN (#)", $lockids);

	$numonline = $forums->forumsNumOnline($fid);
	$template = new template('forums/forumthreads');

	$key = makeKey($fid);
	$template->set('key', $key);
	$template->set('fid', $fid);
	$template->set('perms', $perms);
	$template->set('forumdata', $forumdata);
	$template->set('userData', $userData);
	$template->set('pageList', pageList("$_SERVER[PHP_SELF]?fid=$fid&age=$age",$page,$numpages,'header2'));
	$template->set('isMod', $isMod);
	$template->set('forumTrail', $forums->getForumTrail($perms['cols'], "header"));
	$template->set('forumTrail2', $forums->getForumTrail($perms['cols'], "header2"));
	$template->set('config', $config);
	$template->set('forumpostsperpage', $forumpostsperpage);
	$template->set('threaddata', $threaddata);
	$template->set('page', $page);
	$cols=5;
	if($isMod)
		$cols++;

	$template->set('cols', $cols);
	if($userData['loggedIn']){
		$template->set('personalizeCategories', ($perms['invited'] || $forumdata['ownerid'] == $userData['userid']));
	}


	$i = -1;
	$class = array();
	$showPages = array();
	foreach($threaddata as $line){
		$i++;

		$class[$i] = ($line['subscribe'] ? 'body2' : 'body');

		if($line['posts'] >= $forumpostsperpage){
			$last = ceil(($line['posts']+1) / $forumpostsperpage);

			$list = array();
			if($last <= 7){
				for($j=0; $j < $last; $j++)
					$list[] = "<a class=\"body\" href=\"/forumviewthread.php?tid=$line[id]&page=$j\">" . ($j+1) . "</a>";
			}else{
				$list[] = "<a class=\"body\" href=\"/forumviewthread.php?tid=$line[id]&page=0\">1</a>";
				$list[] = "<a class=\"body\" href=\"/forumviewthread.php?tid=$line[id]&page=1\">2</a>";
				$list[] = "<a class=\"body\" href=\"/forumviewthread.php?tid=$line[id]&page=2\">3</a>";
				$list[] = "...";
				$list[] = "<a class=\"body\" href=\"/forumviewthread.php?tid=$line[id]&page=" . ($last-3) . "\">" . ($last-2) . "</a>";
				$list[] = "<a class=\"body\" href=\"/forumviewthread.php?tid=$line[id]&page=" . ($last-2) . "\">" . ($last-1) . "</a>";
				$list[] = "<a class=\"body\" href=\"/forumviewthread.php?tid=$line[id]&page=" . ($last-1) . "\">$last</a>";
			}

			$showPages[$i] = " &nbsp; [ " . implode(" ", $list) . " ]";
		}
	}

	$template->set('showPages', $showPages);
	$template->set('class', $class);

	if($showModBar){
		echo "<input type=hidden name=fid value=$fid>";
		echo "<tr><td class=header colspan=$cols>";

		if($isMod){
			if($perms['move']){
				// TODO: replace with a more general solution.
				$cats = $forums->getCategories(0);
				$officialforums = $forums->getOfficialForumList();
				$officialforumobjs = $forums->getForums($officialforums['forums']);
				$options = array();
				$catinv = -1;
				foreach ($officialforums['categories'] as $catid => $forumlist)
				{
					$options[$catinv--] = '-&nbsp;' . $cats[$catid]['name'];
					foreach ($forumlist as $forumid)
					{
						$options[$forumid] = '-&nbsp;-&nbsp;' . $officialforumobjs[$forumid]['name'];
					}
				}
				$template->set('selectMoveThread', make_select_list_key($options, 0));
			}
		}

		$links = array();

		if($perms['mute'])
			$links[] = "<a class=header href=/forummute.php?fid=$fid>Mute Users</a>";

		if($perms['globalmute'])
			$links[] = "<a class=header href=/forummute.php?fid=0>Mute Users Globally</a>";

		if($perms['invite'])
			$links[] = "<a class=header href=/foruminvite.php?fid=$fid>Invite Users</a>";

		if($perms['modlog'])
			$links[] = "<a class=header href=/forummodlog.php?fid=$fid>Mod Log</a>";

		if($perms['editmods'])
			$links[] = "<a class=header href=/forummods.php?fid=$fid>Edit Mods</a>";

		if($perms['admin'])
			$links[] = "<a class=header href=/forumedit.php?id=$fid>Edit Forum</a>";

		$template->set('linksList', implode(" | ", $links));
	}

	$template->set('showModBar', $showModBar);
	$template->set('numonline', $numonline);
	$template->set('selectThreadAge', make_select_list_key($forums->sorttimes, $age));

	$template->display();

