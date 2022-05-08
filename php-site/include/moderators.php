<?


function newModItem($type,$itemid,$priority=false){
	global $db;

	$db->prepare_query("INSERT IGNORE INTO moditems SET type = ?, itemid = ?, priority = ?", $type, $itemid, ($priority ? 'y' : 'n'));
}

function deleteModItem($type,$itemid){
	global $db;

	$db->query("BEGIN");

	$db->prepare_query("SELECT id FROM moditems WHERE type = ? && itemid = ?", $type, $itemid);

	if($db->numrows()==0)
		return;

	$id = $db->fetchfield();

	$query = $db->prepare_query("DELETE FROM modvotes WHERE moditemid = ?", $id);
	$query = $db->prepare_query("DELETE FROM moditems WHERE id = ?", $id);

	$db->query("COMMIT");
}

function isMod($userid,$type=false){
	global $mods,$config;

	if(empty($userid))
		return false;

	if(isAdmin($userid,"moderator")){
		$alltypes = $config['modtypes'];
		$types = array();
		foreach($alltypes as $n)
			$types[$n]=10;
		$mods[$userid]=$types;
	}

	if(!isset($mods[$userid]))
		return false;

	if($type){
		if(isset($mods[$userid][$type]))
			return $mods[$userid][$type];
		else
			return false;
	}

	return $mods[$userid];
}

function getModLvl($userid,$type){
	if(isAdmin($userid,"moderator"))
		return 10;

	global $mods;

	if(!isset($mods[$userid][$type]))
		return false;
	return $mods[$userid][$type];
}

function getModItemCounts(){
	global $db,$userData, $config;

	static $items = array();
	if(count($items) > 0)
		return $items;

	$types = isMod($userData['userid']);

	if(count($types)==0)
		return;

	$db->prepare_query("SELECT type, count(*) as count FROM moditems LEFT JOIN modvotes ON moditems.id=modvotes.moditemid && modvotes.modid = ? WHERE modvotes.moditemid IS NULL && type IN (?) && `lock` < ? GROUP BY type", $userData['userid'], array_keys($types), time());

	while($line = $db->fetchrow())
		$items[$line['type']] = $line['count'];

	return $items;
}

function modVote($votes,$type){	//votes = array( moditems.id => vote, ..), vote = y/n, must all be same type
	global $userData,$config,$db;

	if(count($votes)==0)
		return false;

	foreach($votes as $id => $vote)
		if($vote != 'y' && $vote != 'n')
			unset($votes[$id]);

	if(count($votes)==0)
		return false;

	$lvl = getModLvl($userData['userid'],$type);

	if($lvl === false)
		return;

	$query = "LOCK TABLES modvotes WRITE, moditems WRITE, mods WRITE, modvoteslog WRITE";
	if($type=='pics')
		$query .= ", picspending READ";

	$db->query($query);

//find items left to vote for
	$db->prepare_query("SELECT moditems.id FROM moditems LEFT JOIN modvotes ON moditems.id=modvotes.moditemid && modvotes.modid = ? WHERE modvotes.moditemid IS NULL && type = ? && moditems.id IN (?)", $userData['userid'], $type, array_keys($votes));

//none left to vote for
	if($db->numrows()==0){
		$db->query("UNLOCK TABLES");
		return false;
	}

//haven't voted for these, sort them
	$sortedvotes = array();
	$voteids = array();
	while($line = $db->fetchrow()){
		$sortedvotes[$votes[$line['id']]][] = $line['id'];
		$voteids[] = $line['id'];
	}

//place votes
	if(isset($sortedvotes['y']) && count($sortedvotes['y'])>0){
		$db->prepare_query("UPDATE moditems SET points = points + ? WHERE id IN (?) && type = ?", $lvl, $sortedvotes['y'], $type);
	}
	if(isset($sortedvotes['n']) && count($sortedvotes['n'])>0){
		$db->prepare_query("UPDATE moditems SET points = points - ? WHERE id IN (?) && type = ?", $lvl, $sortedvotes['n'], $type);
	}

	$query = "INSERT INTO modvotes (moditemid, modid, vote, points) VALUES ";
	$parts = array();
	foreach($voteids as $id)
		$parts[] = $db->prepare("(?,?,?,?)", $id, $userData['userid'], $votes[$id], $lvl);
	$query .= implode(",", $parts);
//	$query = $db->prepare("INSERT INTO modvotes SET moditemid = ?, modid = ?, vote = ?, points = ?", $id, $userData['userid'], $votes[$id], $lvl);
	$db->query($query);

//find those that passed/failed
	$db->prepare_query("SELECT * FROM moditems WHERE id IN (?) && ABS(points) >= 5", $voteids);

	$moditems = array();
	$moditemids = array();
	while($line = $db->fetchrow()){
		$moditems[$line['id']] = $line;
		$moditems[$line['id']]['y'] = 0;
		$moditems[$line['id']]['n'] = 0;
		$moditemids[] = $line['id'];
	}

//unlock the remaining ones
	$unlocks = array();
	foreach($voteids as $id)
		if(!in_array($id,$moditemids))
			$unlocks[] = $id;

	if(count($unlocks)){
		$db->prepare_query("UPDATE moditems SET `lock` = 0 WHERE id IN (?)", $unlocks);
	}

//done
	if(count($moditemids) == 0){
		$db->query("UNLOCK TABLES");
		return;
	}

	$db->prepare_query("SELECT * FROM modvotes WHERE moditemid IN (?)", $moditemids);

	$mods = array();
	$modvotes = array();
	while($line = $db->fetchrow()){
		$modvotes[$line['moditemid']][] = $line;

		if(!isset($mods[$line['modid']]))
			$mods[$line['modid']] = array('right' => 0, 'wrong' => 0, 'lenient' => 0, 'strict' => 0);
	}


	foreach($moditems as $item){
		if($item['points'] > 0){
			foreach($modvotes[$item['id']] as $modvote){
				if($modvote['vote']=='y'){
					$mods[$modvote['modid']]['right']++;
				}else{
					$mods[$modvote['modid']]['wrong']++;
					$mods[$modvote['modid']]['strict']++;
				}
			}
		}else{
			foreach($modvotes[$item['id']] as $modvote){
				if($modvote['vote']=='y'){
					$mods[$modvote['modid']]['wrong']++;
					$mods[$modvote['modid']]['lenient']++;
				}else{
					$mods[$modvote['modid']]['right']++;
				}
			}
		}
		foreach($modvotes[$item['id']] as $modvote)
			$moditems[$item['id']][$modvote['vote']]++;
	}

	$time = time();

	foreach($mods as $userid => $points){
		$db->prepare_query("UPDATE mods SET `right` = `right` + ?, wrong = wrong + ?, strict = strict + ?, lenient = lenient + ?, time = ? WHERE userid = ? && type = ?", $points['right'], $points['wrong'], $points['strict'], $points['lenient'], $time, $userid, $type);
	}

	if($type == 'pics')
		$db->prepare_query("INSERT INTO modvoteslog SELECT modvotes.id, modvotes.modid, moditems.itemid, picspending.itemid, modvotes.vote, modvotes.points, ? as time FROM modvotes,moditems,picspending WHERE modvotes.moditemid=moditems.id && moditems.itemid=picspending.id && moditems.id IN (?)", time(), $moditemids);

	$db->prepare_query("DELETE FROM moditems WHERE id IN (?)", $moditemids);
	$db->prepare_query("DELETE FROM modvotes WHERE moditemid IN (?)", $moditemids);

	$db->query("UNLOCK TABLES");

	switch($type){
		case "pics":
			$pics = array();

			foreach($moditems as $id => $item){
				if($item['points']>0)
					$pics['y'][] = $item['itemid'];
				else
					$pics['n'][] = $item['itemid'];
			}


			if(isset($pics['n']) && count($pics['n'])){
				$db->query("LOCK TABLES picspending READ, picbans WRITE");

				$result = $db->prepare_query("SELECT id,itemid,md5 FROM picspending WHERE id IN (?)", $pics['n']);

				while($line = $db->fetchrow($result)){
					$db->prepare_query("SELECT times,userid FROM picbans WHERE md5 = ? ORDER BY userid ASC", $line['md5']);

					if($db->numrows()){
						if($db->numrows() >= 4){ //perma-ban
							$db->prepare_query("INSERT INTO picbans SET md5 = ?, times = 10, userid = 0", $line['md5']);
							$db->prepare_query("DELETE FROM picbans WHERE md5 = ? && userid != 0", $line['md5']);
						}else{
							$rows = array();
							while($line2 = $db->fetchrow())
								$rows[$line2['userid']] = $line2;

							if(isset($rows[$line['itemid']])){ //already denied before
								$db->prepare_query("UPDATE picbans SET times = times + 1 WHERE md5 = ? && userid = ?", $line['md5'], $line['itemid']);
							}else{
								$db->prepare_query("INSERT INTO picbans SET md5 = ?, userid = ?, times = 1", $line['md5'], $line['itemid']);
							}
						}
					}else{
						$db->prepare_query("INSERT INTO picbans SET md5 = ?, userid = ?, times = 1", $line['md5'], $line['itemid']);
					}
				}

				$db->query("UNLOCK TABLES");

				foreach($pics['n'] as $id)
					removePicPending($id);
			}

			if(isset($pics['y']) && count($pics['y'])){
				$db->query("LOCK TABLES users WRITE, pics WRITE, picspending WRITE, picbans WRITE, moditems WRITE");

				$result = $db->prepare_query("SELECT id,itemid,signpic FROM picspending WHERE id IN (?)", $pics['y']);

				$uids = array();  // array(userids)
				$users = array(); // userid => array(picids)
				$numpics = array(); // userid => numpics
				while($line = $db->fetchrow($result)){
					$uids[] = $line['itemid'];
					if(!isset($users[$line['itemid']]))
						$users[$line['itemid']] = array();
					$users[$line['itemid']][] = $line['id'];
					$numpics[$line['itemid']] = 0;
					if($line['signpic'] == 'y')
						newModItem("signpics",$line['id']);
				}

				$db->prepare_query("SELECT itemid,count(*) as count FROM pics WHERE itemid IN (?) GROUP BY itemid", $uids);

				while($line = $db->fetchrow())
					$numpics[$line['itemid']] = $line['count'];

				$db->prepare_query("INSERT INTO pics (id,itemid,vote,description) SELECT id,itemid,vote,description FROM picspending WHERE id IN (?)", $pics['y']);

				$db->prepare_query("UPDATE pics,users SET pics.sex=users.sex, pics.age=users.age WHERE users.userid=pics.itemid && pics.id IN (?)", $pics['y']);

				$db->prepare_query("SELECT picbans.id FROM picbans,picspending WHERE picspending.id IN (?) && picbans.userid=picspending.itemid && picbans.md5=picspending.md5", $pics['y']);

				$unbans = array();
				while($line = $db->fetchrow())
					$unbans[] = $line['id'];

				if(count($unbans))
					$db->prepare_query("DELETE FROM picbans WHERE id IN (?)", $unbans);


				$db->prepare_query("DELETE FROM picspending WHERE id IN (?)", $pics['y']);

				$newnumpics = array(); // numpics => array(userid);
				$priorities = array(); // priority => array(picids);

				foreach($users as $uid => $userpics){
					$num = $numpics[$uid] + count($userpics);
					if(!isset($newnumpics[$num]))
						$newnumpics[$num] = array();
					$newnumpics[$num][] = $uid;

					foreach($userpics as $i => $picid){
						$num = $numpics[$uid] + $i + 1;
						if(!isset($priorities[$num]))
							$priorities[$num] = array();
						$priorities[$num][] = $picid;
					}
				}

				foreach($priorities as $priority => $picids)
					$db->prepare_query("UPDATE pics SET priority = ? WHERE id IN (?)", $priority, $picids);

				$db->prepare_query("UPDATE users LEFT JOIN pics ON users.userid=pics.itemid && pics.priority=1 SET users.firstpic = pics.id WHERE users.userid IN (?)",$uids);

				$db->query("UNLOCK TABLES");
			}

			break;
		case "signpics":
			$pics = array();

			foreach($moditems as $id => $item)
				if($item['points']>0)
					$pics[] = $item['itemid'];

			if(count($pics))
				$db->prepare_query("UPDATE users, pics SET users.signpic = 'y', pics.signpic = 'y' WHERE users.userid = pics.itemid && pics.id IN (?)", $pics);

			break;
		case "picabuse":
			$pics = array();
			$picids = array();

			foreach($moditems as $id => $item){
				if($item['points']<0)
					$pics['n'][] = $item['itemid'];
				$picids[] = $item['itemid'];
			}

//			$db->prepare_query("DELETE FROM abuse WHERE type = 'picabuse' && itemid IN (?)", $picids);

			if(isset($pics['n']) && count($pics['n'])){
				$db->prepare_query("SELECT id,itemid FROM pics WHERE id IN (?)", $pics['n']);

				$ids = array();
				$uids = array();

				while($line = $db->fetchrow()){
					$ids[] = $line['id'];
					$uids[] = $line['itemid'];
				}

				foreach($ids as $id)
					removePic($id);

				$db->prepare_query("UPDATE users LEFT JOIN pics ON users.userid=pics.itemid && pics.priority=1 SET users.firstpic = pics.id WHERE users.userid IN (?)",$uids);
			}
			break;
		case "gallery":
			$pics = array();

			foreach($moditems as $id => $item){
				if($item['points']<0)
					$pics['n'][] = $item['itemid'];
			}

			if(isset($pics['n']) && count($pics['n'])){
				$result = $db->prepare_query("SELECT id,userid,category FROM gallery WHERE id IN (?)", $pics['n']);

				while($line = $db->fetchrow($result)){
					removeGalleryPic($line['id']);
					setFirstGalleryPic($line['userid'],$line['category']);
				}
			}
			break;
		case "galleryabuse":
			$pics = array();
			$picids = array();

			foreach($moditems as $id => $item){
				if($item['points']<0)
					$pics['n'][] = $item['itemid'];
				$picids[] = $item['itemid'];
			}

//			$db->prepare_query("DELETE FROM abuse WHERE type = 'galleryabuse' && itemid IN (?)", $picids);

			if(isset($pics['n']) && count($pics['n'])){
				$result = $db->prepare_query("SELECT id,userid,category FROM gallery WHERE id IN (?)", $pics['n']);

				while($line = $db->fetchrow($result)){
					removeGalleryPic($line['id']);
					setFirstGalleryPic($line['userid'],$line['category']);
				}
			}
			break;
		case "userabuse":
			$picids = array();

			foreach($moditems as $id => $item)
				$picids[] = $item['itemid'];

//			$db->prepare_query("DELETE FROM abuse WHERE type = 'userabuse' && itemid IN (?)", $picids);
			break;
		case "forumpost":
			$ids = array();

			foreach($moditems as $id => $item)
				$ids[] = $item['itemid'];

//			$db->prepare_query("DELETE FROM abuse WHERE type = 'forumpost' && itemid IN (?)", $ids);
			break;
		case "articles":
			foreach($moditems as $item){
				if($item['points']>0){
					$query = $db->prepare("UPDATE articles SET moded='y' WHERE id = ?", $item['itemid']);
					$db->query($query);
				}else{
					$query = $db->prepare("DELETE FROM articles WHERE id = ?", $item['itemid']);
					$db->query($query);
				}
			}
			break;
		case "banners":
			global $fastdb;
			foreach($moditems as $item){
				if($item['points']>0){
					$fastdb->prepare_query("UPDATE banners SET moded='y' WHERE id = ?", $item['itemid']);
				}else{
					$fastdb->prepare_query("DELETE FROM banners WHERE id = ?", $item['itemid']);
				}
			}
			break;
		case "forumrank":
			$ids = array();
			$acceptids = array();
			foreach($moditems as $item){
				$ids[] = $item['itemid'];
				if($item['points']>0)
					$acceptids[] = $item['itemid'];
			}

			if(count($acceptids))
				$db->prepare_query("UPDATE users,forumrankspending SET users.forumrank=forumrankspending.forumrank WHERE users.userid=forumrankspending.userid && forumrankspending.id IN (?)",$acceptids);

			$db->prepare_query("DELETE FROM forumrankspending WHERE id IN (?)", $ids);

			global $msgs;

			$msgs->addMsg(count($acceptids) . " accepted, " . (count($ids) - count($acceptids)) . " denied");

			break;
	}
}


