<?



define("MOD_PICS", 				1);
define("MOD_SIGNPICS",			2);
define("MOD_PICABUSE",			3);
define("MOD_QUESTIONABLEPICS",	4);

define("MOD_FORUMPOST",			11);
define("MOD_FORUMRANK",			12);

define("MOD_GALLERY",			21);
define("MOD_GALLERYABUSE",		22);

define("MOD_USERABUSE",			31);

define("MOD_BANNER",			41);

define("MOD_ARTICLE",			51);

define("MOD_POLL",				61);

class moderator{
	var $modtypes = array( 	1	=> "Pics", //can't use defines due to bug in php
							2	=> "Sign Pics",
							3	=> "Pic Abuse",
							4	=> "Questionable",

							11	=> "Forum Post",
							12	=> "Forum Rank",

							21	=> "Gallery",
							22	=> "Gallery Abuse",

							31	=> "User Abuse",

							41	=> "Banner",

							51	=> "Article",

							61	=> "Polls"
						);

	var $moddb;
/*
moddb tables:
-admin
-adminlog
-mods
-moditems
-modvotes
*/

	var $mods;
	var $admins;
	var $modprefs;

	function moderator(){
		global $db, $cache;

		$this->modprefs = array();

		$this->moddb = & $db;

		$this->mods = $cache->hdget("mods", array('function' => array($this, 'getModDump'), 'params' => array()));

		$this->admins = $cache->hdget("admins",array('function' => array($this, 'getAdminDump'), 'params' => array()));

		$types = array();
		foreach($this->modtypes as $v => $n)
			$types[$v]=10;

		foreach($this->admins as $userid => $admin)
			if($admin['moderator'] == 'y')
				$this->mods[$userid] = $types;
	}

	function getAdmins(){
		return array_keys($this->admins);
	}

	function getMods(){
		return array_keys($this->mods);
	}

	function getModDump(){
		$this->moddb->query("SELECT userid,type,level FROM mods");

		$mods = array();
		while($line = $this->moddb->fetchrow())
			$mods[$line['userid']][$line['type']] = $line['level'];

		return $mods;
	}

	function getAdminDump(){
		$this->moddb->query("SELECT * FROM admin");

		$admins = array();
		while($line = $this->moddb->fetchrow())
			$admins[$line['userid']] = $line;

		return $admins;
	}

	function addMod($userid, $type, $level){
		global $cache;
		$this->moddb->prepare_query("INSERT INTO mods SET userid = ?, level = ?, type = ?, creationtime = ?", $userid, $level, $type, time());
		$cache->hdput("mods", $this->getModDump());
	}

	function updateMod($userid, $type, $level){
		global $cache;
		$this->moddb->prepare_query("UPDATE mods SET level = ? WHERE userid = ? && type = ?", $level, $userid, $type);
		$cache->hdput("mods", $this->getModDump());
	}

	function deleteMod($userid, $type = false){
		global $cache;
		if($type)
			$this->moddb->prepare_query("DELETE FROM mods WHERE userid IN (?) && type = ?", $userid, $type);
		else
			$this->moddb->prepare_query("DELETE FROM mods WHERE userid IN (?)", $userid);
		$cache->hdput("mods", $this->getModDump());
	}

	function moveMod($userid, $newid, $type = false){
		if($type)
			$this->moddb->prepare_query("UPDATE mods SET userid = ? WHERE userid = ? && type = ?", $newid, $userid, $type);
		else
			$this->moddb->prepare_query("UPDATE mods SET userid = ? WHERE userid = ?", $newid, $userid);
	}

	function isAdmin($uid,$type=""){
		return ( isset($this->admins[$uid]) && ($type=="" || $this->admins[$uid][$type]=='y') );
	}

	function adminLog($action,$description){
		global $userData, $PHP_SELF;

		$this->moddb->prepare_query("INSERT INTO adminlog SET userid = ?, ip = ?, time = ?, page = ?, action = ?, description = ?", $userData['userid'], ip2int(getip()), time(), $PHP_SELF, $action, $description);
	}

	function newItem($type,$items,$priority=false){
		$entries = array();
		if(is_array($items))
			foreach($items as $itemid)
				$entries[] = $this->moddb->prepare("(?,?,?)", $type, $itemid, ($priority ? 'y' : 'n'));
		else
			$entries[] = $this->moddb->prepare("(?,?,?)", $type, $items, ($priority ? 'y' : 'n'));

		$this->moddb->prepare_query("INSERT IGNORE INTO moditems (type, itemid, priority) VALUES " . implode(", ", $entries));
//		$this->moddb->prepare_query("INSERT IGNORE INTO moditems SET type = ?, itemid = ?, priority = ?", $type, $itemid, ($priority ? 'y' : 'n'));
	}

	function deleteItem($type,$itemid){
		$this->moddb->prepare_query("DELETE modvotes, moditems FROM moditems LEFT JOIN modvotes ON moditems.id = modvotes.moditemid WHERE moditems.type = ? && moditems.itemid IN (?)", $type, $itemid);
	}

	function isMod($userid,$type=false){
		if(!isset($this->mods[$userid]))
			return false;

		if($type)
			return isset($this->mods[$userid][$type]);

		return true;
	}

	function getModLvl($userid, $type = false){
		if(!isset($this->mods[$userid]))
			return false;

		if($type){
			if(isset($this->mods[$userid][$type]))
				return $this->mods[$userid][$type];
			else
				return false;
		}

		return $this->mods[$userid];
	}

	function getModItemCounts(){
		global $userData;

		static $items = array();
		if(count($items) > 0)
			return $items;

		$types = $this->getModLvl($userData['userid']);

		if(empty($types))
			return;

		$this->moddb->prepare_query("SELECT type, count(*) as count FROM moditems LEFT JOIN modvotes ON moditems.id=modvotes.moditemid && modvotes.modid = ? WHERE modvotes.moditemid IS NULL && type IN (?) && `lock` <= ? GROUP BY type", $userData['userid'], array_keys($types), time());

		while($line = $this->moddb->fetchrow())
			$items[$line['type']] = $line['count'];

		return $items;
	}

	function getModPrefs($userid, $type, $pref = ""){

		if(!isset($this->modprefs[$userid][$type])){
			$prefs = array('autoscroll' => 'y', 'picsperpage' => 35);

			$this->moddb->prepare_query("SELECT autoscroll, picsperpage, level, (`right` + `wrong`) AS total, time FROM mods WHERE userid = ? && type = ?", $userid, $type);
			if($this->moddb->numrows())
				$prefs = $this->moddb->fetchrow();

			$this->modprefs[$userid][$type] = $prefs;
		}

		if($pref)
			return $this->modprefs[$userid][$type][$pref];
		else
			return $this->modprefs[$userid][$type];
	}

	function setModPrefs($userid, $type, $autoscroll, $picsperpage){
		$this->moddb->prepare_query("UPDATE mods SET autoscroll = ?, picsperpage = ? WHERE userid = ? && type = ?", $autoscroll, $picsperpage, $userid, $type);
		$this->modprefs[$userid][$type] = array('autoscroll' => $autoscroll, 'picsperpage' => $picsperpage);
	}

	function getModItems($type, $num, $locktime){
		global $userData;

		$time = time();

		$lvl = $this->getModLvl($userData['userid'],$type);

		$this->moddb->query("LOCK TABLES moditems WRITE, modvotes READ");

		$this->moddb->prepare_query("SELECT moditems.id, moditems.itemid FROM moditems LEFT JOIN modvotes ON moditems.id=modvotes.moditemid && modvotes.modid = ? WHERE modvotes.moditemid IS NULL && moditems.type = ? && moditems.lock <= ? ORDER BY priority DESC," . ($lvl >= 5 ? " moditems.points ASC," : "") . " id ASC LIMIT $num", $userData['userid'], $type, $time);

		if($this->moddb->numrows()==0){
			$this->moddb->query("UNLOCK TABLES");
			return array();
		}

		$ids = array();
		$itemids = array();
		while($line = $this->moddb->fetchrow()){
			$itemids[$line['itemid']] = $line['itemid'];
			$ids[] = $line['id'];
		}

		$this->moddb->prepare_query("UPDATE moditems SET `lock` = ? WHERE type = ? && id IN (?)", ($time + count($ids)*$locktime), $type, $ids);

		$this->moddb->query("UNLOCK TABLES");

		return $itemids;
	}

	function vote($votes,$type){	//votes = array( moditems.itemid => vote, ..), vote = y/n, must all be same type
		global $userData,$config;

		if(count($votes)==0)
			return false;

		foreach($votes as $id => $vote)
			if($vote != 'y' && $vote != 'n')
				unset($votes[$id]);

		if(count($votes)==0)
			return false;

		$lvl = $this->getModLvl($userData['userid'],$type);

		if($lvl === false)
			return;

		$this->moddb->query("LOCK TABLES modvotes WRITE, moditems WRITE, mods WRITE");

	//find items left to vote for
		$this->moddb->prepare_query("SELECT moditems.id, moditems.itemid FROM moditems LEFT JOIN modvotes ON moditems.id=modvotes.moditemid && modvotes.modid = ? WHERE modvotes.moditemid IS NULL && type = ? && moditems.itemid IN (?)", $userData['userid'], $type, array_keys($votes));

	//none left to vote for
		if($this->moddb->numrows()==0){
			$this->moddb->query("UNLOCK TABLES");
			return false;
		}

	//haven't voted for these, sort them
		$sortedvotes = array();
		$voteids = array();
		while($line = $this->moddb->fetchrow()){
			$sortedvotes[$votes[$line['itemid']]][] = $line['id'];
			$voteids[$line['id']] = $line['itemid'];
		}

	//log the votes
		$query = "INSERT INTO modvotes (moditemid, modid, vote, points) VALUES ";
		$parts = array();
		foreach($voteids as $id => $itemid)
			$parts[] = $this->moddb->prepare("(?,?,?,?)", $id, $userData['userid'], $votes[$itemid], $lvl);
		$query .= implode(",", $parts);
		$this->moddb->query($query);

	//can't change any points, done
		if($lvl == 0){
			$this->moddb->query("UNLOCK TABLES");
			return;
		}

	//place votes
		if(isset($sortedvotes['y']) && count($sortedvotes['y'])>0)
			$this->moddb->prepare_query("UPDATE moditems SET points = points + ? WHERE id IN (?) && type = ?", $lvl, $sortedvotes['y'], $type);

		if(isset($sortedvotes['n']) && count($sortedvotes['n'])>0)
			$this->moddb->prepare_query("UPDATE moditems SET points = points - ? WHERE id IN (?) && type = ?", $lvl, $sortedvotes['n'], $type);


	//find those that passed/failed
		$this->moddb->prepare_query("SELECT *, 0 AS y, 0 AS n FROM moditems WHERE id IN (?) && ABS(points) >= 5", array_keys($voteids));

		$moditems = array();
		$moditemids = array();
		while($line = $this->moddb->fetchrow()){
			$moditems[$line['id']] = $line;
			$moditemids[] = $line['id'];

			unset($voteids[$line['id']]);
		}

	//unlock the remaining ones
		if(count($voteids))
			$this->moddb->prepare_query("UPDATE moditems SET `lock` = 0 WHERE id IN (?)", array_keys($voteids));

	//done
		if(count($moditemids) == 0){
			$this->moddb->query("UNLOCK TABLES");
			return;
		}

		$this->moddb->prepare_query("SELECT * FROM modvotes WHERE moditemid IN (?)", $moditemids);

		$mods = array();
		$modvotes = array();
		while($line = $this->moddb->fetchrow()){
			$modvotes[$line['moditemid']][] = $line;

			if(!isset($mods[$line['modid']]))
				$mods[$line['modid']] = array('right' => 0, 'wrong' => 0, 'lenient' => 0, 'strict' => 0);
		}


		$items = array( 'y' => array(), 'n' => array() );
		$votes = array();

		foreach($moditems as $item){

			$votes[$item['itemid']] = array();

			if($item['points'] > 0){
				$items['y'][] = $item['itemid'];

				foreach($modvotes[$item['id']] as $modvote){
					$votes[$item['itemid']][$modvote['modid']] = $modvote['vote'];

					if($modvote['vote']=='y'){
						$mods[$modvote['modid']]['right']++;
					}else{
						$mods[$modvote['modid']]['wrong']++;
						$mods[$modvote['modid']]['strict']++;
					}
				}
			}else{
				$items['n'][] = $item['itemid'];

				foreach($modvotes[$item['id']] as $modvote){
					$votes[$item['itemid']][$modvote['modid']] = $modvote['vote'];

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
			$this->moddb->prepare_query("UPDATE mods SET `right` = `right` + ?, wrong = wrong + ?, strict = strict + ?, lenient = lenient + ?, time = ? WHERE userid = ? && type = ?", $points['right'], $points['wrong'], $points['strict'], $points['lenient'], $time, $userid, $type);
		}

		$this->moddb->prepare_query("DELETE FROM moditems WHERE id IN (?)", $moditemids);
		$this->moddb->prepare_query("DELETE FROM modvotes WHERE moditemid IN (?)", $moditemids);

		$this->moddb->query("UNLOCK TABLES");


	/*
		$items['y'][] = $itemid;
		$items['n'][] = $itemid;

		$votes[$itemid][$userid] = $vote;
	*/

//print_r($items);


		switch($type){
			case MOD_PICS:				$this->modpics($items, $votes);			break;
			case MOD_SIGNPICS:			$this->modsignpics($items, $votes);		break;
			case MOD_PICABUSE:			$this->modpicabuse($items, $votes);		break;
			case MOD_QUESTIONABLEPICS:	die("no function");//	$this->modpicabuse($items, $votes);		break;

			case MOD_FORUMPOST:			$this->modforumpost($items, $votes);	break;
			case MOD_FORUMRANK:			$this->modforumrank($items, $votes);	break;

			case MOD_GALLERY:			$this->modgallery($items, $votes);		break;
			case MOD_GALLERYABUSE:		$this->modgalleryabuse($items, $votes);	break;

			case MOD_USERABUSE:			$this->moduserabuse($items, $votes);	break;

			case MOD_BANNER:			$this->modbanners($items, $votes);		break;

			case MOD_ARTICLE:			$this->modarticles($items, $votes);		break;

			case MOD_POLL:				$this->modpolls($items, $votes);		break;
		}
	}






/////////////////////////////////////
	function modpics($pics, $votes){
		global $db, $userData;

		$time = time();

//add pics that didn't get a unanimous vote either way
		$questionables = array();

		foreach($votes as $picid => $uservotes){
			$questionable = false;
			foreach($uservotes as $userid => $vote){
				if(!$questionable)
					$questionable = $vote;
				elseif($questionable != $vote){
					$questionables[] = $picid;
					break;
				}
			}
		}

//		foreach($questionables as $id)
//			$this->newItem(MOD_QUESTIONABLEPICS, $id);
		if(count($questionables))
			$this->newItem(MOD_QUESTIONABLEPICS, $questionables);

//mod votes log
		$db->prepare_query("SELECT id, itemid FROM picspending WHERE id IN (?)", array_merge($pics['y'], $pics['n']));

		$userids = array();
		while($line = $db->fetchrow())
			$userids[$line['id']] = $line['itemid'];

		$querytail = array();

		foreach($votes as $picid => $items)
			foreach($items as $userid => $vote)
				$querytail[] = $db->prepare("(?,?,?,?,?)", $userid, $picid, $userids[$picid], $vote, $time);

		$db->query("INSERT INTO modvoteslog (modid, picid, userid, vote, time) VALUES " . implode(",", $querytail));


//pics that were denied
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

//			foreach($pics['n'] as $id)
//				removePicPending($id);
			removePicPending($pics['n'], false);
		}

//pics that were accepted
		if(isset($pics['y']) && count($pics['y'])){
			$db->query("LOCK TABLES users WRITE, pics WRITE, picspending WRITE, picbans WRITE");

			$result = $db->prepare_query("SELECT id,itemid,signpic FROM picspending WHERE id IN (?)", $pics['y']);

			$uids = array();  // array(userids)
			$users = array(); // userid => array(picids)
			$numpics = array(); // userid => numpics
			$signpics = array();
			while($line = $db->fetchrow($result)){
				$uids[] = $line['itemid'];
				if(!isset($users[$line['itemid']]))
					$users[$line['itemid']] = array();
				$users[$line['itemid']][] = $line['id'];
				$numpics[$line['itemid']] = 0;
				if($line['signpic'] == 'y')
					$signpics[] = $line['id'];
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

//			foreach($signpics as $id)
//				$this->newItem(MOD_SIGNPICS,$id);

			$this->newItem(MOD_SIGNPICS,$signpics);
		}
	}

	function modsignpics($pics, $votes){
		global $db;

		if(count($pics['y']))
			$db->prepare_query("UPDATE users, pics SET users.signpic = 'y', pics.signpic = 'y' WHERE users.userid = pics.itemid && pics.id IN (?)", $pics['y']);

		if(count($pics['n']))
			$db->prepare_query("UPDATE users, pics SET users.signpic = 'n', pics.signpic = 'n' WHERE users.userid = pics.itemid && pics.id IN (?)", $pics['n']);
	}

	function modpicabuse($pics, $votes){
		global $db;

//		$db->prepare_query("DELETE FROM abuse WHERE type = 'picabuse' && itemid IN (?)", array_merge($pics['y'], $pics['n']));

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
	}

	function modgallery($pics, $votes){
		global $db;

		if(isset($pics['n']) && count($pics['n'])){
			$result = $db->prepare_query("SELECT id,userid,category FROM gallery WHERE id IN (?)", $pics['n']);

			while($line = $db->fetchrow($result)){
				removeGalleryPic($line['id']);
				setFirstGalleryPic($line['userid'],$line['category']);
			}
		}
	}

	function modgalleryabuse($pics, $votes){
		global $db;

//		$db->prepare_query("DELETE FROM abuse WHERE type = 'galleryabuse' && itemid IN (?)", array_merge($pics['y'], $pics['n']));

		if(isset($pics['n']) && count($pics['n'])){
			$result = $db->prepare_query("SELECT id,userid,category FROM gallery WHERE id IN (?)", $pics['n']);

			while($line = $db->fetchrow($result)){
				removeGalleryPic($line['id']);
				setFirstGalleryPic($line['userid'],$line['category']);
			}
		}
	}

	function moduserabuse($pics, $votes){
//		$db->prepare_query("DELETE FROM abuse WHERE type = 'userabuse' && itemid IN (?)", array_merge($pics['n'], $pics['y']));
	}

	function modforumpost($pics, $votes){
//		$db->prepare_query("DELETE FROM abuse WHERE type = 'forumpost' && itemid IN (?)", array_merge($pics['n'], $pics['y']));
	}

	function modarticles($pics, $votes){
		global $db;

		if(count($pics['y']))
			$db->prepare_query("UPDATE articles SET moded='y', time = ? WHERE id IN (?)", time(), $pics['y']);

		if(count($pics['n']))
			$db->prepare_query("DELETE FROM articles WHERE id IN (?)", $pics['n']);
	}

	function modbanners($pics, $votes){
		global $fastdb;

		if(count($pics['y']))
			$fastdb->prepare_query("UPDATE banners SET moded='y' WHERE id IN (?)", $pics['y']);

		if(count($pics['n']))
			$fastdb->prepare_query("DELETE FROM banners WHERE id IN (?)", $pics['n']);
	}

	function modforumrank($pics, $votes){
		global $db, $msgs;

		if(count($pics['y']))
			$db->prepare_query("UPDATE users,forumrankspending SET users.forumrank=forumrankspending.forumrank WHERE users.userid=forumrankspending.userid && forumrankspending.id IN (?)", $pics['y']);

		$db->prepare_query("DELETE FROM forumrankspending WHERE id IN (?)", array_merge($pics['y'], $pics['n']));

		$msgs->addMsg(count($pics['y']) . " accepted, " . count($pics['n']) . " denied");
	}

	function modpolls($polls, $votes){
		global $db, $msgs, $cache;

		if(count($polls['y'])){
			$db->prepare_query("UPDATE polls SET moded = 'y', date = ? WHERE id IN (?)", time(), $polls['y']);
			$cache->resetFlag("poll");
		}

		if(count($polls['n']))
			deletePoll($polls['n']);
	}
}

