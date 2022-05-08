<?



define("MOD_PICS", 				1);
define("MOD_SIGNPICS",			2);
define("MOD_PICABUSE",			3);
define("MOD_QUESTIONABLEPICS",	4);

define("MOD_FORUMPOST",			11);
define("MOD_FORUMRANK",			12);
define("MOD_FORUMBAN",			13);

define("MOD_GALLERY",			21);
define("MOD_GALLERYABUSE",		22);

define("MOD_USERABUSE",			31);
define("MOD_USERABUSE_CONFIRM",	32);

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
							13	=> "Forum Ban",

							21	=> "Gallery",
							22	=> "Gallery Abuse",

							31	=> "User Abuse",
							32	=> "Confirmed Abuse",

							41	=> "Banner",

							51	=> "Article",

							61	=> "Polls",
						);

	var $db;
/*
tables:
-admin
-adminlog
-mods
-moditems
-modvotes
-modhist

function moderator()
function getAdmins()
function getMods()
function getModDump()
function getAdminDump()
function addMod($userid, $type, $level)
function updateMod($userid, $type, $level)
function moveMod($userid, $newid, $type = false)
function isAdmin($uid,$type="")
function adminLog($action,$description)
function newItem($type,$items,$priority=false)
function deleteItem($type,$itemid)
function isMod($userid,$type=false)
function getModLvl($userid, $type = false)
function getModItemCounts()
function getModPrefs($userid, $type, $pref = "")
function setModPrefs($userid, $type, $autoscroll, $picsperpage)
function recommendPromotions($to)
function suggestedModLevel($pics, $error, $curlevel)
function getModItems($type, $num, $locktime)
function vote($votes,$type)

	$items['y'][] = $itemid;
	$items['n'][] = $itemid;
	$votes[$itemid][$userid] = $vote;

function modpics($pics, $votes)
function modsignpics($pics, $votes)
function modpicabuse($pics, $votes)
function modgallery($pics, $votes)
function modgalleryabuse($pics, $votes)
function moduserabuse($pics, $votes)
function moduserabuseconfirm($pics, $votes)
function modforumpost($pics, $votes)
function modforumban($pics, $votes)
function modarticles($pics, $votes)
function modbanners($pics, $votes)
function modforumrank($pics, $votes)
function modpolls($polls, $votes)

*/

///////////////////////////////////////////

	var $mods;
	var $admins;
	var $modprefs;

	function moderator( & $db ){
		global $cache;

		$this->modprefs = array();

		$this->db = & $db;

		$this->mods = $cache->hdget("mods", 0, array(&$this, 'getModDump'));

		$this->admins = $cache->hdget("admins", 0, array(&$this, 'getAdminDump'));

		$types = array();
		foreach($this->modtypes as $v => $n)
			$types[$v]=10;

		foreach($this->admins as $userid => $admin)
			if($admin['moderator'] == 'y')
				$this->mods[$userid] = $types;
	}

	function getAdmins($perm = false){
		if($perm){
			$uids = array();
			foreach($this->admins as $uid => $admin)
				if($this->admins[$uid][$perm] == 'y')
					$uids[] = $uid;
			return $uids;
		}else
			return array_keys($this->admins);
	}

	function getMods(){
		return array_keys($this->mods);
	}

	function getModDump(){
		$this->db->query("SELECT userid,type,level FROM mods");

		$mods = array();
		while($line = $this->db->fetchrow())
			$mods[$line['userid']][$line['type']] = $line['level'];

		return $mods;
	}

	function getAdminDump(){
		$this->db->query("SELECT * FROM admin");

		$admins = array();
		while($line = $this->db->fetchrow())
			$admins[$line['userid']] = $line;

		return $admins;
	}

	function deleteAdmin($userid){
		global $cache;
		$this->db->prepare_query("DELETE FROM admin WHERE userid IN (#)", $userid);
	}

	function addMod($userid, $type, $level){
		global $cache;
		$this->db->prepare_query("INSERT IGNORE INTO mods SET userid = #, username = ?, level = #, type = #, creationtime = #", $userid, getUserName($userid), $level, $type, time());

		return $this->db->affectedrows();
	}

	function updateMod($userid, $type, $level){
		global $cache;
		$this->db->prepare_query("UPDATE mods SET level = # WHERE userid = # && type = #", $level, $userid, $type);
	}

	function deleteMod($userid, $type = false){
		global $cache;
		if($type)
			$this->db->prepare_query("DELETE FROM mods WHERE userid IN (#) && type = #", $userid, $type);
		else
			$this->db->prepare_query("DELETE FROM mods WHERE userid IN (#)", $userid);
	}

	function moveMod($userid, $newid, $type = false){
		if($type)
			$this->db->prepare_query("UPDATE mods SET userid = #, username = ? WHERE userid = # && type = #", $newid, getUserName($newid), $userid, $type);
		else
			$this->db->prepare_query("UPDATE mods SET userid = #, username = ? WHERE userid = #", $newid, getUserName($newid), $userid);
	}

	function isAdmin($uid, $type=""){
		global $userData;
		if($userData['loggedIn'] && $uid == $userData['userid'] && !$userData['sessionlockip']) //remove admin powers from admins on unlocked sessions
			return false;
		return ( isset($this->admins[$uid]) && ($type=="" || $this->admins[$uid][$type]=='y') );
	}

	function adminLog($action, $description){
		global $userData;

		$this->db->prepare_query("INSERT INTO adminlog SET userid = #, ip = #, time = #, page = ?, action = ?, description = ?", $userData['userid'], ip2int(getip()), time(), $_SERVER['PHP_SELF'], $action, $description);
	}

	function newItem($type, $items, $priority = false){
		$entries = array();
		if(is_array($items))
			foreach($items as $itemid)
				$entries[] = $this->db->prepare("(#,#,?)", $type, $itemid, ($priority ? 'y' : 'n'));
		else
			$entries[] = $this->db->prepare("(#,#,?)", $type, $items, ($priority ? 'y' : 'n'));

		$this->db->prepare_query("INSERT IGNORE INTO moditems (type, itemid, priority) VALUES " . implode(", ", $entries));
//		$this->db->prepare_query("INSERT IGNORE INTO moditems SET type = #, itemid = #, priority = ?", $type, $itemid, ($priority ? 'y' : 'n'));
	}

	function dumpModStats(){
		$this->db->prepare_query("INSERT IGNORE INTO modhist SELECT # as dumptime, userid, type, username, `right`, `wrong`, lenient, strict, level, time, creationtime FROM mods", gmmktime(0,0,0,gmdate("n"),gmdate("j"),gmdate("Y")));
	}

	function deleteItem($type, $itemid){
		$this->db->prepare_query("DELETE moditems, modvotes FROM moditems LEFT JOIN modvotes ON moditems.id = modvotes.moditemid WHERE moditems.type = # && moditems.itemid IN (#)", $type, $itemid);
	}

	function isMod($userid, $type = false){
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
		global $userData, $cache;

		static $myitems = array();
		if(count($myitems) > 0)
			return $myitems;

		$types = $this->getModLvl($userData['userid']);

		if(empty($types))
			return;

//*
		$myvotes = $cache->get("modvotes-$userData[userid]");

		if($myvotes === false){
			$this->db->prepare_query("SELECT type, count(*) as count FROM modvotes WHERE modid = # && type IN (#) GROUP BY type", $userData['userid'], array_keys($types));

			while($line = $this->db->fetchrow())
				$myvotes[$line['type']] = $line['count'];

			$cache->put("modvotes-$userData[userid]", $myvotes, 600); // 10 minutes
		}


		$items = $cache->get("moditems");

		if($items === false){
			$this->db->prepare_query("SELECT type, count(*) as count FROM moditems WHERE `lock` <= # GROUP BY type", time());

			while($line = $this->db->fetchrow())
				$items[$line['type']] = $line['count'];

			$cache->put("moditems", $items, 60);
		}

		foreach($types as $type => $level){
			if(!empty($items[$type])){
				$myitems[$type] = $items[$type];
				if(isset($myvotes[$type]))
					$myitems[$type] -= $myvotes[$type];
			}
		}

/*/

		$this->db->prepare_query("SELECT moditems.type, count(*) as count FROM moditems LEFT JOIN modvotes ON moditems.id=modvotes.moditemid && modvotes.modid = # WHERE modvotes.moditemid IS NULL && moditems.type IN (#) && moditems.`lock` <= # GROUP BY moditems.type", $userData['userid'], array_keys($types), time());

		while($line = $this->db->fetchrow())
			$myitems[$line['type']] = $line['count'];
//*/

		return $myitems;
	}

	function getModPrefs($userid, $type, $pref = ""){
		global $cache;

		if(!isset($this->modprefs[$userid][$type])){
			$prefs = false;//$cache->get(array($userid,"modprefs-$userid-$type"));

			if($prefs === false){
				$this->db->prepare_query("SELECT level, (`right`+`wrong`) as total, autoscroll, time, picsperpage FROM mods WHERE userid = # && type = #", $userid, $type);
				if($this->db->numrows())
					$prefs = $this->db->fetchrow();
				else
					$prefs = array('autoscroll' => 'y', 'picsperpage' => 35);

				$cache->put(array($userid,"modprefs-$userid-$type"), $prefs, 3600);
			}

			$this->modprefs[$userid][$type] = $prefs;
		}

		if($pref)
			return $this->modprefs[$userid][$type][$pref];
		else
			return $this->modprefs[$userid][$type];
	}

	function setModPrefs($userid, $type, $autoscroll, $picsperpage){
		global $cache;

		$this->db->prepare_query("UPDATE mods SET autoscroll = ?, picsperpage = # WHERE userid = # && type = #", $autoscroll, $picsperpage, $userid, $type);
		$this->modprefs[$userid][$type] = array('autoscroll' => $autoscroll, 'picsperpage' => $picsperpage);

		$cache->remove(array($userid,"modprefs-$userid-$type"));
	}

	function recommendPromotions($to, $type = MOD_PICS){
		global $messaging;
		$this->db->prepare_query("SELECT userid, username, type, `right`, IF(`right`+`wrong`=0,0,100.0*`wrong`/(`right` + `wrong`)) as `error`, level FROM mods");

		$promotions = array();
		while($line = $this->db->fetchrow()){
			$line['newlevel'] = $this->suggestedModLevel($line['right'], $line['error'], $line['level']);
			if($line['newlevel'] > $line['level'])
				$promotions[$line['userid']] = $line;
		}

		if(count($promotions)){
			$subject = "Mod Promotions";

			$message = "";
			foreach($promotions as $user)
				$message .= "[user]" . $user['username'] . "[/user], " . $this->modtypes[$user['type']] . ",  level $user[level] -> $user[newlevel] ($user[right] right, $user[error]%) [url=/adminmods.php?action=edit&uid=$user[userid]&type=$user[type]]Edit[/url], [url=/adminmods.php?type=$user[type]&uid=$user[userid]&level=$user[newlevel]&action=Update]Promote[/url]\n";

			$messaging->deliverMsg($to, $subject, $message, 0, "Nexopia", 0);
		}
	}

	function doPromotions($to, $type = MOD_PICS){
		global $messaging;
		$this->db->prepare_query("SELECT userid, username, type, `right`, IF(`right`+`wrong`=0,0,100.0*`wrong`/(`right` + `wrong`)) as `error`, level FROM mods WHERE type IN (#)", $type);

		$promotions = array();
		while($line = $this->db->fetchrow()){
			$line['newlevel'] = $this->suggestedModLevel($line['right'], $line['error'], $line['level']);
			if($line['newlevel'] != $line['level'])
				$promotions[$line['userid']] = $line;
		}

		if(count($promotions)){
			$subject = "Mod Promotions";

			$message = "";
			foreach($promotions as $user){
				$this->updateMod($user['userid'], $user['type'], $user['newlevel']);
				$message .= "[user]" . $user['username'] . "[/user], " . $this->modtypes[$user['type']] . ",  level $user[level] -> $user[newlevel] ($user[right] right, $user[error]%) [url=/adminmods.php?action=edit&uid=$user[userid]&type=$user[type]]Edit[/url], promoted\n";
			}
			$messaging->deliverMsg($to, $subject, $message, 0, "Nexopia", 0);
		}
	}

	function suggestedModLevel($pics, $error, $curlevel){
		if($curlevel >= 6)		return $curlevel;
		if($pics < 1000 || $error > 12)		return 0;
		if($pics < 2000 || $error > 8.5)	return 1;
		if($pics < 3000 || $error > 6.3)	return 2;
		if($pics < 4000 || $error > 2.5)	return 3;
		if($pics < 5000 || $error > 1)		return 4;
		return 5; //ie above 5000, below 1%
	}

	function getModItems($type, $num, $lockm, $lockb = 0){ //locktime = mx+b
		global $userData, $cache;

		$time = time();

		$lvl = $this->getModLvl($userData['userid'],$type);

//		$this->db->query("LOCK TABLES moditems WRITE, modvotes READ");

		$this->db->begin();

		$this->db->prepare_query("SELECT moditems.id, moditems.itemid FROM moditems LEFT JOIN modvotes ON moditems.id=modvotes.moditemid && modvotes.modid = # WHERE modvotes.moditemid IS NULL && moditems.type = # && moditems.lock <= # ORDER BY priority DESC," . ($lvl >= 6 ? " moditems.points ASC," : "") . " id ASC LIMIT $num FOR UPDATE", $userData['userid'], $type, $time);

		if($this->db->numrows()==0){
//			$this->db->query("UNLOCK TABLES");
			$this->db->rollback();
			return array();
		}

		$ids = array();
		$itemids = array();
		while($line = $this->db->fetchrow()){
			$itemids[$line['itemid']] = $line['itemid'];
			$ids[] = $line['id'];
		}

		$this->db->prepare_query("UPDATE moditems SET `lock` = # WHERE type = # && id IN (#)", ($time + count($ids)*$lockm + $lockb), $type, $ids);

		$cache->remove("moditems");

//		$this->db->query("UNLOCK TABLES");
		$this->db->commit();

		return $itemids;
	}

	function vote($votes, $type){	//votes = array( moditems.itemid => vote, ..), vote = y/n, must all be same type
		global $userData, $config, $cache;

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

//		$this->db->query("LOCK TABLES modvotes WRITE, moditems WRITE, mods WRITE");
		$this->db->begin();

	//find items left to vote for
		$this->db->prepare_query("SELECT moditems.id, moditems.itemid FROM moditems LEFT JOIN modvotes ON moditems.id=modvotes.moditemid && modvotes.modid = # WHERE modvotes.moditemid IS NULL && moditems.type = # && moditems.itemid IN (#) FOR UPDATE", $userData['userid'], $type, array_keys($votes));

	//none left to vote for
		if($this->db->numrows()==0){
			$this->db->rollback();
//			$this->db->query("UNLOCK TABLES");
			return false;
		}

	//haven't voted for these, sort them
		$sortedvotes = array();
		$voteids = array();
		while($line = $this->db->fetchrow()){
			$sortedvotes[$votes[$line['itemid']]][] = $line['id'];
			$voteids[$line['id']] = $line['itemid'];
		}

	//log the votes
		$query = "INSERT INTO modvotes (moditemid, type, modid, vote, points) VALUES ";
		$parts = array();
		foreach($voteids as $id => $itemid)
			$parts[] = $this->db->prepare("(#,#,#,?,#)", $id, $type, $userData['userid'], $votes[$itemid], $lvl);
		$query .= implode(",", $parts);
		$this->db->query($query);

	//can't change any points, done
		if($lvl == 0){
			$this->db->commit();
//			$this->db->query("UNLOCK TABLES");
			return;
		}

	//place votes
		if(isset($sortedvotes['y']) && count($sortedvotes['y'])>0)
			$this->db->prepare_query("UPDATE moditems SET points = points + # WHERE id IN (#) && type = #", $lvl, $sortedvotes['y'], $type);

		if(isset($sortedvotes['n']) && count($sortedvotes['n'])>0)
			$this->db->prepare_query("UPDATE moditems SET points = points - # WHERE id IN (#) && type = #", $lvl, $sortedvotes['n'], $type);


	//find those that passed/failed
		$this->db->prepare_query("SELECT *, 0 AS y, 0 AS n FROM moditems WHERE id IN (#) && ABS(points) >= 6", array_keys($voteids));

		$moditems = array();
		$moditemids = array();
		while($line = $this->db->fetchrow()){
			$moditems[$line['id']] = $line;
			$moditemids[] = $line['id'];

			unset($voteids[$line['id']]);
		}

	//unlock the remaining ones
		if(count($voteids))
			$this->db->prepare_query("UPDATE moditems SET `lock` = 0 WHERE id IN (#)", array_keys($voteids));

	//done
		if(count($moditemids) == 0){
			$this->db->commit();
//			$this->db->query("UNLOCK TABLES");
			return;
		}

		$this->db->prepare_query("SELECT * FROM modvotes WHERE moditemid IN (#)", $moditemids);

		$mods = array();
		$modvotes = array();
		while($line = $this->db->fetchrow()){
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
			$this->db->prepare_query("UPDATE mods SET `right` = `right` + #, wrong = wrong + #, strict = strict + #, lenient = lenient + #, time = # WHERE userid = # && type = #", $points['right'], $points['wrong'], $points['strict'], $points['lenient'], $time, $userid, $type);
		}

		$this->db->prepare_query("DELETE FROM moditems WHERE id IN (#)", $moditemids);
		$this->db->prepare_query("DELETE FROM modvotes WHERE moditemid IN (#)", $moditemids);

//		$this->db->query("UNLOCK TABLES");
		$this->db->commit();


	/*
		$items['y'][] = $itemid;
		$items['n'][] = $itemid;

		$votes[$itemid][$userid] = $vote;
	*/

//print_r($items);

		$cache->remove("modvotes-$userData[userid]");


		switch($type){
			case MOD_PICS:				$this->modpics($items, $votes);			break;
			case MOD_SIGNPICS:			$this->modsignpics($items, $votes);		break;
			case MOD_PICABUSE:			$this->modpicabuse($items, $votes);		break;
			case MOD_QUESTIONABLEPICS:	die("no function");//	$this->modpicabuse($items, $votes);		break;

			case MOD_FORUMPOST:			$this->modforumpost($items, $votes);	break;
			case MOD_FORUMRANK:			$this->modforumrank($items, $votes);	break;
			case MOD_FORUMRANK:			$this->modforumban($items, $votes);		break;

			case MOD_GALLERY:			$this->modgallery($items, $votes);		break;
			case MOD_GALLERYABUSE:		$this->modgalleryabuse($items, $votes);	break;

			case MOD_USERABUSE:			$this->moduserabuse($items, $votes);	break;
			case MOD_USERABUSE_CONFIRM:	$this->moduserabuseconfirm($items, $votes);	break;

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

		if(count($questionables))
			$this->newItem(MOD_QUESTIONABLEPICS, $questionables);

//mod votes log
		$db->prepare_query("SELECT id, itemid FROM picspending WHERE id IN (#)", array_merge($pics['y'], $pics['n']));

		$userids = array();
		while($line = $db->fetchrow())
			$userids[$line['id']] = $line['itemid'];

		$querytail = array();

		foreach($votes as $picid => $items)
			foreach($items as $userid => $vote)
				$querytail[] = $db->prepare("(#,#,#,?,#)", $userid, $picid, $userids[$picid], $vote, $time);

		$db->query("INSERT INTO modvoteslog (modid, picid, userid, vote, time) VALUES " . implode(",", $querytail));


//pics that were denied
		if(isset($pics['n']) && count($pics['n'])){
//			$db->query("LOCK TABLES picspending READ, picbans WRITE");
			$db->begin();

			$result = $db->prepare_query("SELECT id,itemid,md5 FROM picspending WHERE id IN (#) FOR UPDATE", $pics['n']);

			while($line = $db->fetchrow($result)){
				$db->prepare_query("SELECT times,userid FROM picbans WHERE md5 = ? ORDER BY userid ASC FOR UPDATE", $line['md5']);

				if($db->numrows()){
					if($db->numrows() >= 4){ //perma-ban
						$db->prepare_query("INSERT INTO picbans SET md5 = ?, times = 10, userid = 0", $line['md5']);
						$db->prepare_query("DELETE FROM picbans WHERE md5 = ? && userid != 0", $line['md5']);
					}else{
						$rows = array();
						while($line2 = $db->fetchrow())
							$rows[$line2['userid']] = $line2;

						if(isset($rows[$line['itemid']])){ //already denied before
							$db->prepare_query("UPDATE picbans SET times = times + 1 WHERE md5 = ? && userid = #", $line['md5'], $line['itemid']);
						}else{
							$db->prepare_query("INSERT INTO picbans SET md5 = ?, userid = #, times = 1", $line['md5'], $line['itemid']);
						}
					}
				}else{
					$db->prepare_query("INSERT INTO picbans SET md5 = ?, userid = #, times = 1", $line['md5'], $line['itemid']);
				}
			}

//			$db->query("UNLOCK TABLES");
			$db->commit();

//			foreach($pics['n'] as $id)
//				removePicPending($id);
			removePicPending($pics['n'], false);
		}

//pics that were accepted
		if(isset($pics['y']) && count($pics['y'])){
//			$db->query("LOCK TABLES users WRITE, pics WRITE, picspending WRITE, picbans WRITE");
			$db->begin();

			$result = $db->prepare_query("SELECT id,itemid,signpic FROM picspending WHERE id IN (#)", $pics['y']);

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

			$db->prepare_query("SELECT itemid,count(*) as count FROM pics WHERE itemid IN (#) GROUP BY itemid", $uids);

			while($line = $db->fetchrow())
				$numpics[$line['itemid']] = $line['count'];

			$db->prepare_query("INSERT IGNORE INTO pics (id,itemid,vote,description) SELECT id,itemid,vote,description FROM picspending WHERE id IN (#)", $pics['y']);

			$db->prepare_query("UPDATE pics,users SET pics.sex=users.sex, pics.age=users.age WHERE users.userid=pics.itemid && pics.id IN (#)", $pics['y']);

			$db->prepare_query("SELECT picbans.id FROM picbans,picspending WHERE picspending.id IN (#) && picbans.userid=picspending.itemid && picbans.md5=picspending.md5", $pics['y']);

			$unbans = array();
			while($line = $db->fetchrow())
				$unbans[] = $line['id'];

			if(count($unbans))
				$db->prepare_query("DELETE FROM picbans WHERE id IN (#)", $unbans);


			$db->prepare_query("DELETE FROM picspending WHERE id IN (#)", $pics['y']);

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
				$db->prepare_query("UPDATE pics SET priority = # WHERE id IN (#)", $priority, $picids);

			setFirstPic($uids);

//			$db->query("UNLOCK TABLES");
			$db->commit();

			$db->prepare_query("INSERT INTO picsvotable (sex, age, picid) SELECT pics.sex, pics.age, pics.id FROM pics WHERE vote = 'y' && pics.id IN (#)", $pics['y']);

			if(count($signpics))
				$this->newItem(MOD_SIGNPICS,$signpics);
		}
	}

	function modsignpics($pics, $votes){
		global $db;

		if(count($pics['n']))
			$db->prepare_query("UPDATE users, pics SET users.signpic = 'n', pics.signpic = 'n' WHERE users.userid = pics.itemid && pics.id IN (#)", $pics['n']);
//should be fixed
		if(count($pics['y']))
			$db->prepare_query("UPDATE users, pics SET users.signpic = 'y', pics.signpic = 'y' WHERE users.userid = pics.itemid && pics.id IN (#)", $pics['y']);
	}

	function modpicabuse($pics, $votes){
		global $db;

		$db->prepare_query("DELETE FROM abuse WHERE type = # && itemid IN (#)", MOD_PICABUSE, array_merge($pics['y'], $pics['n']));

		if(isset($pics['n']) && count($pics['n'])){
			$db->prepare_query("SELECT id,itemid FROM pics WHERE id IN (#)", $pics['n']);

			$ids = array();
			$uids = array();

			while($line = $db->fetchrow()){
				$ids[] = $line['id'];
				$uids[] = $line['itemid'];
			}

			foreach($ids as $id)
				removePic($id);

			$db->prepare_query("UPDATE users LEFT JOIN pics ON users.userid=pics.itemid && pics.priority=1 SET users.firstpic = pics.id WHERE users.userid IN (#)",$uids);
		}
	}

	function modgallery($pics, $votes){
		global $db;

		if(isset($pics['n']) && count($pics['n'])){
			$result = $db->prepare_query("SELECT id,userid,category FROM gallery WHERE id IN (#)", $pics['n']);

			while($line = $db->fetchrow($result)){
				removeGalleryPic($line['id']);
				setFirstGalleryPic($line['userid'],$line['category']);
			}
		}
	}

	function modgalleryabuse($pics, $votes){
		global $db;

		$db->prepare_query("DELETE FROM abuse WHERE type = # && itemid IN (#)", MOD_GALLERYABUSE, array_merge($pics['y'], $pics['n']));

		if(isset($pics['n']) && count($pics['n'])){
			$result = $db->prepare_query("SELECT id, userid, category FROM gallery WHERE id IN (#)", $pics['n']);

			while($line = $db->fetchrow($result)){
				removeGalleryPic($line['id']);
				setFirstGalleryPic($line['userid'],$line['category']);
			}
		}
	}

	function moduserabuse($pics, $votes){
		global $db;

		if(count($pics['y']))
			$db->prepare_query("DELETE FROM abuse WHERE type = # && itemid IN (#)", MOD_USERABUSE, $pics['y']);

		if(count($pics['n'])){
			$db->prepare_query("UPDATE abuse SET type = # WHERE itemid IN (#)", MOD_USERABUSE_CONFIRM, $pics['n']);
			$this->newItem(MOD_USERABUSE_CONFIRM, $pics['n']);
		}
	}

	function moduserabuseconfirm($pics, $votes){
		global $db;
		$db->prepare_query("DELETE FROM abuse WHERE type = # && itemid IN (#)", MOD_USERABUSE_CONFIRM, array_merge($pics['n'], $pics['y']));
	}

	function modforumpost($pics, $votes){
		global $db;
		$db->prepare_query("DELETE FROM abuse WHERE type = # && itemid IN (#)", MOD_FORUMPOST, array_merge($pics['n'], $pics['y']));
	}

	function modforumban($pics, $votes){
	}

	function modarticles($pics, $votes){
		global $db, $messaging;

		if(count($pics['y'])){
			$result = $db->prepare_query("SELECT authorid, title FROM articles WHERE id IN (#)", $pics['y']);

			while($line = $db->fetchrow($result))
				$messaging->deliverMsg($line['authorid'],"Article Accepted", "Your article '$line[title]' has been accepted.", 0, "Nexopia", 0);

			$db->prepare_query("UPDATE articles SET moded='y', time = # WHERE id IN (#)", time(), $pics['y']);
		}

		if(count($pics['n'])){
			$result = $db->prepare_query("SELECT authorid, title FROM articles WHERE id IN (#)", $pics['n']);

			while($line = $db->fetchrow($result))
				$messaging->deliverMsg($line['authorid'],"Article Rejected", "Your article '$line[title]' has been rejected. Some of the reasons why articles are rejected are listed [url=/faq.php?q=31]here[/url].", 0, "Nexopia", 0);

			$db->prepare_query("DELETE FROM articles WHERE id IN (#)", $pics['n']);
		}
	}

	function modbanners($pics, $votes){

	}

	function modforumrank($pics, $votes){
		global $db, $msgs;

		if(count($pics['y']))
			$db->prepare_query("UPDATE users,forumrankspending SET users.forumrank=forumrankspending.forumrank WHERE users.userid=forumrankspending.userid && forumrankspending.id IN (#)", $pics['y']);

		$db->prepare_query("DELETE FROM forumrankspending WHERE id IN (#)", array_merge($pics['y'], $pics['n']));

		$msgs->addMsg(count($pics['y']) . " accepted, " . count($pics['n']) . " denied");
	}

	function modpolls($modpolls, $votes){
		global $db, $msgs, $cache, $polls;

		if(count($modpolls['y']))
			$polls->db->prepare_query("UPDATE polls SET moded = 'y', date = # WHERE id IN (#)", time(), $modpolls['y']);

		if(count($modpolls['n']))
			$polls->deletePoll($modpolls['n']);
	}
}

