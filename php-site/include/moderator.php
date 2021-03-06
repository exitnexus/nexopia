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

define("MOD_VIDEO",				71);

define("SIGNPIC_UNMODERATED", 0);
define("SIGNPIC_PENDING",  1);
define("SIGNPIC_ACCEPTED", 2);
define("SIGNPIC_FAILED", 3);

define("USERPIC_UNMODERATED", 0);
define("USERPIC_PENDING",  1);
define("USERPIC_ACCEPTED", 2);
define("USERPIC_FAILED", 3);

class moderator{
	public $modtypes;

	public $db;
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
function getModItems($type, $num, $lockm, $lockb = 0, $split = false, $for_uid = null)
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

	public $mods;
	public $admins;
	public $modprefs;

	function __construct( & $db ){
		global $cache, $Ruby;
		$this->modtypes = $Ruby->send('Moderator::QueueBase.queues.collect_hash {|num, q| [num, q.pretty_name] }');
		ksort($this->modtypes);

		$this->modprefs = array();

		$this->db = & $db;

		$this->mods = $cache->hdget("mods", 1800, array(&$this, 'getModDump'));

		$this->admins = $cache->hdget("admins", 0, array(&$this, 'getAdminDump'));

		$types = array();
		foreach($this->modtypes as $v => $n)
			$types[$v]=10;

		foreach($this->admins as $userid => $admin)
			if(isset($admin['moderator']) && $admin['moderator'])
				$this->mods[$userid] = $types;
	}

	function getAdmins($perm = false){
		if($perm){
			$uids = array();
			foreach($this->admins as $uid => $admin)
				if(isset($this->admins[$uid][$perm]) && $this->admins[$uid][$perm])
					$uids[] = $uid;
			return $uids;
		}else
			return array_keys($this->admins);
	}

	function getMods(){
		return array_keys($this->mods);
	}

	function getModDump(){
		$res = $this->db->query("SELECT userid,type,level FROM mods");

		$mods = array();
		while($line = $res->fetchrow())
			$mods[$line['userid']][$line['type']] = $line['level'];

		return $mods;
	}

/*
//old way
	function getAdminDump(){
		$res = $this->db->query("SELECT * FROM adminroles");

		$roles = array();
		while($line = $res->fetchrow())
			$roles[$line['id']] = $line;

		$res = $this->db->query("SELECT * FROM admins");

		$admins = array();
		while($line = $res->fetchrow()){
			if(!isset($roles[$line['roleid']]))
				continue;

			$role = $roles[$line['roleid']];

			if(!isset($admins[$line['userid']]))
				$admins[$line['userid']] = array('title' => array(), 'roles' => array());

			$admins[$line['userid']]['roles'][] = $role['rolename'];

			if($role['title'])
				$admins[$line['userid']]['title'][] = $role['title'];

			unset($role['id'], $role['rolename'], $role['title']);

			foreach($role as $k => $v)
				if(!isset($admins[$line['userid']][$k]) || $v == 'y')
					$admins[$line['userid']][$k ] = ($v == 'y');
		}

		$usernames = getUserName(array_keys($admins));

		foreach($admins as $k => $v)
			$admins[$k]['username'] = $usernames[$k];

		return $admins;
	}

/*/
//new way with the new permission system
	function getAdminDump(){
		global $masterdb;

	//grab the role names
		$result = $this->db->query("SELECT * FROM adminroles");

		$roles = array();
		while($line = $result->fetchrow())
			$roles[$line['id']] = array('rolename' => $line['rolename'], 'title' => $line['title']);

	//get the list of roles users are part of
		$result = $masterdb->prepare_query("SELECT * FROM accountmap WHERE primaryid IN (#) && primaryid != accountid", array_keys($roles));

		$rolemembers = array();
		while($line = $result->fetchrow()) {
			if(!isset($rolemembers[$line['primaryid']]))
				$rolemembers[$line['primaryid']] = array();

			$rolemembers[$line['primaryid']][] = array($line['accountid'], $line['visible'] == 'y');
		}

	//assign permissions to roles
		$result = $this->db->query("SELECT * FROM globalgrant");

		$rolepermissions = array(); // array(roleid => array(userid, ...), ... )
		while($line = $result->fetchrow()){
			if(!isset($rolepermissions[$line['accountid']]))
				$rolepermissions[$line['accountid']] = array();

			$rolepermissions[$line['accountid']][] = $line['privilegeid'];
		}

	//map permission ids to permission names
		$result = $this->db->query("SELECT * FROM privilegenames");

		$privilegenames = array();
		while($line = $result->fetchrow())
			$privilegenames[$line['privilegeid']] = $line['name'];

	//join it all in the way isAdmin wants it
	//ignore the case where permissions are set directly on a user for now
		$admins = array();
		foreach($roles as $roleid => $role){
			if (isset($rolemembers[$roleid])) {
				foreach($rolemembers[$roleid] as $member){
					$uid = $member[0];
					$visible = $member[1];
					if(!isset($admins[$uid]))
						$admins[$uid] = array('title' => array(), 'roles' => array(), 'visible' => false);

					if($visible)
						$admins[$uid]['visible'] = true;
					if($role['title'])
						$admins[$uid]['title'][] = $role['title'];
					$admins[$uid]['roles'][] = $role['rolename'];


					if(isset($rolepermissions[$roleid]))
						foreach($rolepermissions[$roleid] as $permissionid)
							$admins[$uid][$privilegenames[$permissionid]] = true;
				}
			}
		}

		return $admins;
	}
//*/

	function deleteAdmin($userid){
		$this->db->prepare_query("DELETE FROM admins WHERE userid IN (#)", $userid);
	}

	function addMod($userid, $type, $level){
		$this->db->prepare_query("INSERT IGNORE INTO mods SET userid = #, level = #, type = #, creationtime = #", $userid, $level, $type, time());

		return $this->db->affectedrows();
	}

	function updateMod($userid, $type, $level){
		$this->db->prepare_query("UPDATE mods SET level = # WHERE userid = # && type = #", $level, $userid, $type);
	}

	function resetMod($userid, $type){
		$this->db->prepare_query("UPDATE mods SET level = 0, `right` = 0, wrong = 0, lenient = 0, strict = 0 WHERE userid = # && type = #", $userid, $type);
	}

	function deleteMod($userid, $type = false){
		if($type)
			$this->db->prepare_query("DELETE FROM mods WHERE userid IN (#) && type = #", $userid, $type);
		else
			$this->db->prepare_query("DELETE FROM mods WHERE userid IN (#)", $userid);
	}

	function moveMod($userid, $newid, $type = false){
		if($type)
			$this->db->prepare_query("UPDATE mods SET userid = # WHERE userid = # && type = #", $newid, $userid, $type);
		else
			$this->db->prepare_query("UPDATE mods SET userid = # WHERE userid = #", $newid, $userid);
	}

	function isAdmin($uid, $type = ""){
		global $userData;

	//remove admin powers from admins on unlocked sessions
		if($userData['loggedIn'] && $uid == $userData['userid'] && !$userData['sessionlockip'])
			return false;

		return ( isset($this->admins[$uid]) && ($type == "" || (isset($this->admins[$uid][$type]) && $this->admins[$uid][$type])) );
	}

	function getAdminTag($uid){
		return (isset($this->admins[$uid]) ? $this->admins[$uid]['title'] : false);
	}

	function adminLog($action, $description){
		global $userData;

		$this->db->prepare_query("INSERT INTO adminlog SET userid = #, ip = #, time = #, page = ?, action = ?, description = ?", $userData['userid'], ip2int(getip()), time(), $_SERVER['PHP_SELF'], $action, $description);
	}

	// array of uid=>item(s) mapping
	function newSplitItem($type, $items, $priority = false){

		$entries = array();

		foreach ($items as $uid => $item){
			if(is_array($item))
				foreach($item as $itemid)
					$entries[] = $this->db->prepare("(#,#,#,?,#)", $type, $uid, $itemid, ($priority ? 'y' : 'n'), time());
			else
				$entries[] = $this->db->prepare("(#,#,#,?,#)", $type, $uid, $item, ($priority ? 'y' : 'n'), time());
		}

		$this->db->prepare_query("INSERT IGNORE INTO moditems (type, splitid, itemid, priority, created) VALUES " . implode(", ", $entries));
//		$this->db->prepare_query("INSERT IGNORE INTO moditems SET type = #, itemid = #, priority = ?", $type, $itemid, ($priority ? 'y' : 'n'));
	}

	function newItem($type, $items, $priority = false){
		$this->newSplitItem($type, array(0 => $items), $priority);
	}

	function dumpModStats(){
		$this->db->prepare_query("INSERT IGNORE INTO modhist SELECT # as dumptime, userid, type, `right`, `wrong`, lenient, strict, level, time, creationtime FROM mods", gmmktime(0,0,0,gmdate("n"),gmdate("j"),gmdate("Y")));
	}

	function deleteItem($type, $itemid){
		$this->db->prepare_query("DELETE moditems, modvotes FROM moditems LEFT JOIN modvotes ON moditems.id = modvotes.moditemid WHERE moditems.type = # && moditems.itemid IN (#)", $type, $itemid);
	}

	function deleteSplitItem($type, $itemid){
		$keys = array('moditems.splitid' => '#', 'moditems.itemid' => '#');
		$this->db->prepare_query("DELETE moditems, modvotes FROM moditems LEFT JOIN modvotes ON moditems.id = modvotes.moditemid WHERE moditems.type = # && ^", $type,
			$this->db->prepare_multikey($keys, $itemid));
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
			$res = $this->db->prepare_query("SELECT type, count(*) as count FROM modvotes WHERE modid = # && type IN (#) GROUP BY type", $userData['userid'], array_keys($types));

			while($line = $res->fetchrow())
				$myvotes[$line['type']] = $line['count'];

			$cache->put("modvotes-$userData[userid]", $myvotes, 600); // 10 minutes
		}


		$items = $cache->get("moditems");

		if($items === false){
			$res = $this->db->prepare_query("SELECT type, count(*) AS count FROM moditems FORCE INDEX (`type`) WHERE `lock` <= # GROUP BY type", time());

			while($line = $res->fetchrow())
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

		$res = $this->db->prepare_query("SELECT moditems.type, count(*) as count FROM moditems LEFT JOIN modvotes ON moditems.id=modvotes.moditemid && modvotes.modid = # WHERE modvotes.moditemid IS NULL && moditems.type IN (#) && moditems.`lock` <= # GROUP BY moditems.type", $userData['userid'], array_keys($types), time());

		while($line = $res->fetchrow())
			$myitems[$line['type']] = $line['count'];
//*/

		return $myitems;
	}

	function getModPrefs($userid, $type, $pref = ""){
		global $cache;

		if(!isset($this->modprefs[$userid][$type])){
			$prefs = false;//$cache->get("modprefs-$userid-$type");

			if($prefs === false){
				$res = $this->db->prepare_query("SELECT level, (`right`+`wrong`) as total, autoscroll, time, picsperpage FROM mods WHERE userid = # && type = #", $userid, $type);
				$prefs = $res->fetchrow();
				if(!$prefs)
					$prefs = array('autoscroll' => 'y', 'picsperpage' => 35);

				$cache->put("modprefs-$userid-$type", $prefs, 3600);
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

		$cache->remove("modprefs-$userid-$type");
	}

	/* Gets lifetime, monthly, and weekly picmod stats for given userid */
	function getPicModStats ($uid) {
		global $cache, $config;

//		if ( ($modstats = $cache->get("modprefs-stats-{$uid}")) !== false )
//			return $modstats;

		$modstats = array(
			'lifetime'	=> array(
				'right'				=> 0,
				'wrong'				=> 0,
				'picsmodded'		=> 0,
				'errrate'			=> 0,
				'strict'			=> 0,
				'lenient'			=> 0,
				'level'				=> 0,
				'time'				=> 0,
				'creationtime'		=> 0,
				'lenientOrStrict'	=> 'neither'
			)
		);
		$modstats['weekly'] = $modstats['monthly'] = $modstats['lifetime'];

		// get lifetime mod stats
		$sth = $this->db->prepare_query("SELECT `right`, `wrong`, strict, lenient, level, time, creationtime FROM mods WHERE userid=# && type=#", $uid, MOD_PICS);
		if ( ($row = $sth->fetchrow()) !== false ) {
			if ($row['right'] + $row['wrong'] > 0)
				$row['lenientOrStrict'] = $row['strict'] > $row['lenient'] ? 'too strict' : 'too lenient';
			else
				$row['lenientOrStrict'] = 'neither';

			$row['picsmodded'] = $row['right'] + $row['wrong'];
			$row['errrate'] = $row['picsmodded'] ? $row['wrong'] * 100 / $row['picsmodded'] : 0;

			$modstats['lifetime'] = $modstats['monthly'] = $modstats['weekly'] = $row;
		}

		// create weekly stats by subtracting the totals as they were at the beginning of the current week.
		// the week used is monday-sunday, not sunday-saturday
		// worst date handling ever. this grabs the GMT epoch time for midnight of this week's monday

		$time = gmdate('U');
		$date = getdate($time);
		$wday = $date['wday'] == 0 ? 7 : $date['wday'];
		$startdate = getdate($time - (60 * 60 * 24 * abs(1 - $wday)));
		$startdate = gmmktime(0, 0, 0, $startdate['mon'], $startdate['mday'], $startdate['year']);

		$sth = $this->db->prepare_query("SELECT `right`, `wrong`, strict, lenient, level FROM modhist WHERE userid=# && type=? && dumptime BETWEEN # AND # ORDER BY dumptime ASC LIMIT 1", $uid, MOD_PICS, $startdate, $startdate + 86399);
		if ( ($row = $sth->fetchrow()) !== false ) {
			$modstats['weekly']['right'] -= $row['right'];
			$modstats['weekly']['wrong'] -= $row['wrong'];
			$modstats['weekly']['strict'] -= $row['strict'];
			$modstats['weekly']['lenient'] -= $row['lenient'];
			$modstats['weekly']['picsmodded'] -= $row['right'] + $row['wrong'];
			$modstats['weekly']['errrate'] = $modstats['weekly']['picsmodded'] ? ( ($modstats['weekly']['wrong'] * 100) / $modstats['weekly']['picsmodded'] ) : 0;

			if ($modstats['weekly']['errrate'] <= $config['picmodpluserrrate'] && $modstats['weekly']['picsmodded'] >= $config['picmodpluspicrate'])
				$modstats['weekly']['earnedplus'] = true;
			else
				$modstats['weekly']['earnedplus'] = false;
		}
		else
			$modstats['weekly']['earnedplus'] = false;

		// create monthly stats by subtracting the totals as they were at the beginning of the current month.
		$startdate = gmmktime(0, 0, 0, gmdate("n"), 1, gmdate("Y"));

		$sth = $this->db->prepare_query("SELECT `right`, `wrong`, strict, lenient, level FROM modhist WHERE userid=# && type=? && dumptime BETWEEN # AND # ORDER BY dumptime ASC LIMIT 1", $uid, MOD_PICS, $startdate, $startdate + 86399);
		if ( ($row = $sth->fetchrow()) !== false ) {
			$modstats['monthly']['right'] -= $row['right'];
			$modstats['monthly']['wrong'] -= $row['wrong'];
			$modstats['monthly']['strict'] -= $row['strict'];
			$modstats['monthly']['lenient'] -= $row['lenient'];
			$modstats['monthly']['picsmodded'] -= $row['right'] + $row['wrong'];
			$modstats['monthly']['errrate'] = $modstats['monthly']['picsmodded'] ? ( ($modstats['monthly']['wrong'] * 100) / $modstats['monthly']['picsmodded'] ) : 0;
		}

		foreach ($modstats as &$stats) {
//			if (! $stats['picsmodded'])
//				continue;

			$stats['right'] = number_format($stats['right']);
			$stats['wrong'] = number_format($stats['wrong']);
			$stats['strict'] = number_format($stats['strict']);
			$stats['lenient'] = number_format($stats['lenient']);
			$stats['lenientOrStrict'] = $stats['strict'] > $stats['lenient'] ? 'too strict' : 'too lenient';
			$stats['picsmodded'] = number_format($stats['picsmodded']);
			$stats['errrate'] = number_format($stats['errrate'], 2);
		}
		unset($stats);

//		$cache->put("modprefs-stats-{$uid}", $modstats, 60 * 15); // 15 min cache
		return $modstats;
	}


	function recommendPromotions($to, $type = MOD_PICS){
		global $messaging;
		$res = $this->db->prepare_query("SELECT userid, type, `right`, IF(`right`+`wrong`=0,0,100.0*`wrong`/(`right` + `wrong`)) as `error`, level FROM mods");

		$promotions = array();
		while($line = $res->fetchrow()){
			$line['newlevel'] = $this->suggestedModLevel($line['right'], $line['error'], $line['level']);
			if($line['newlevel'] > $line['level'])
				$promotions[$line['userid']] = $line;
		}

		if(count($promotions)){
			$usernames = getUserName(array_keys($promotions));

			$subject = "Mod Promotions";

			$message = "";
			foreach($promotions as $user)
				$message .= "[user]" . $usernames[$user['userid']] . "[/user], " . $this->modtypes[$user['type']] . ",  level $user[level] -> $user[newlevel] ($user[right] right, $user[error]%) [url=/adminmods.php?action=edit&uid=$user[userid]&type=$user[type]]Edit[/url], [url=/adminmods.php?type=$user[type]&uid=$user[userid]&level=$user[newlevel]&action=Update]Promote[/url]\n";

//			$messaging->deliverMsg($to, $subject, $message, 0, "Nexopia", 0);
		}
	}

	function doPromotions($to, $type = MOD_PICS){
		global $messaging;
		$res = $this->db->prepare_query("SELECT userid, type, `right`, IF(`right`+`wrong`=0,0,100.0*`wrong`/(`right` + `wrong`)) as `error`, level FROM mods WHERE type IN (#)", $type);

		$promotions = array();
		while($line = $res->fetchrow()){
			$line['newlevel'] = $this->suggestedModLevel($line['right'], $line['error'], $line['level']);
			if($line['newlevel'] != $line['level'])
				$promotions[$line['userid']] = $line;
		}

		if(count($promotions)){
			$usernames = getUserName(array_keys($promotions));

			$subject = "Mod Promotions";

			$message = "";
			foreach($promotions as $user){
				$this->updateMod($user['userid'], $user['type'], $user['newlevel']);
				$message .= "[user]" . $usernames[$user['userid']] . "[/user], " . $this->modtypes[$user['type']] . ",  level $user[level] -> $user[newlevel] ($user[right] right, $user[error]%) [url=/adminmods.php?action=edit&uid=$user[userid]&type=$user[type]]Edit[/url], promoted\n";
			}
//			$messaging->deliverMsg($to, $subject, $message, 0, "Nexopia", 0);
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

	function getModItems($type, $num, $lockm, $lockb = 0, $split = false, $for_uid = null){ //locktime = mx+b
		global $userData, $cache, $config;

		$time = time();

		$lvl = $this->getModLvl($userData['userid'],$type);

//		$this->db->query("LOCK TABLES moditems WRITE, modvotes READ");

		$this->db->begin();

		$uid_where = "";
		if($for_uid)
			$uid_where = $this->db->prepare("AND moditems.splitid = #", $for_uid); # always a number, so embedding this in the query later is safe.

		$res = $this->db->prepare_query("SELECT moditems.id, moditems.itemid, moditems.splitid FROM moditems LEFT JOIN modvotes ON moditems.id=modvotes.moditemid && modvotes.modid = # WHERE modvotes.moditemid IS NULL && moditems.type = # && moditems.lock <= # $uid_where ORDER BY priority DESC," . ($lvl >= 6 ? " moditems.points ASC," : "") . " id ASC LIMIT $num FOR UPDATE", $userData['userid'], $type, $time);

		$ids = array();
		$itemids = array();
		while($line = $res->fetchrow()){
			if(!$split && $line['splitid'])
				$split = true;

			$itemid = ($split ? "$line[splitid]:" : '') . $line['itemid'];
			$itemids[$itemid] = $itemid;
			$ids[] = $line['id'];
		}

		if(!$itemids){
//			$this->db->query("UNLOCK TABLES");
			$this->db->rollback();
			return array();
		}

		if(!isset($config['lockmoditems']) || $config['lockmoditems'])
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
			return false;

		set_time_limit(0);

		$this->db->query("LOCK TABLES modvotes WRITE, moditems WRITE, mods WRITE");
//		$this->db->begin();

		foreach ($votes as $votekey => $voteval) break; // get the first key out.

	//find items left to vote for
		if (strchr($votekey,':') !== false)
			$keys = array('splitid' => '#', 'itemid' => '#');
		else
			$keys = array('itemid' => '#');

		$res = $this->db->prepare_query("SELECT moditems.id, moditems.itemid, moditems.splitid FROM moditems LEFT JOIN modvotes ON moditems.id=modvotes.moditemid && modvotes.modid = # WHERE modvotes.moditemid IS NULL && moditems.type = # && ^ FOR UPDATE", $userData['userid'], $type,
			$this->db->prepare_multikey($keys, array_keys($votes)));

	//haven't voted for these, sort them
		$sortedvotes = array();
		$voteids = array();
		while($line = $res->fetchrow()){
			$itemid = ($line['splitid']? "$line[splitid]:$line[itemid]" : $line['itemid']);
			$sortedvotes[$votes[$itemid]][] = $line['id'];
			$voteids[$line['id']] = $itemid;
		}

	//none left to vote for
		if(!$voteids){
//			$this->db->rollback();
			$this->db->query("UNLOCK TABLES");
			return false;
		}

	//log the votes
		$query = "INSERT INTO modvotes (moditemid, type, modid, vote, points) VALUES ";
		$parts = array();
		foreach($voteids as $id => $itemid)
		{
			$parts[] = $this->db->prepare("(#,#,#,?,#)", $id, $type, $userData['userid'], $votes[$itemid], $lvl);
		}
		$query .= implode(",", $parts);
		$this->db->query($query);

	//can't change any points, done
		if($lvl == 0){
			$this->db->prepare_query("UPDATE moditems SET `lock` = 0 WHERE id IN (#)", array_keys($voteids));

//			$this->db->commit();
			$this->db->query("UNLOCK TABLES");
			return;
		}

	//place votes
		if(isset($sortedvotes['y']) && count($sortedvotes['y'])>0)
			$this->db->prepare_query("UPDATE moditems SET points = points + # WHERE id IN (#) && type = #", $lvl, $sortedvotes['y'], $type);

		if(isset($sortedvotes['n']) && count($sortedvotes['n'])>0)
			$this->db->prepare_query("UPDATE moditems SET points = points - # WHERE id IN (#) && type = #", $lvl, $sortedvotes['n'], $type);


	//find those that passed/failed
		$res = $this->db->prepare_query("SELECT *, 0 AS y, 0 AS n FROM moditems WHERE id IN (#) && ABS(points) >= 6", array_keys($voteids));

		$moditems = array();
		$moditemids = array();
		while($line = $res->fetchrow()){
			$moditems[$line['id']] = $line;
			$moditemids[] = $line['id'];

			unset($voteids[$line['id']]);
		}

	//unlock the remaining ones
		if(count($voteids))
			$this->db->prepare_query("UPDATE moditems SET `lock` = 0 WHERE id IN (#)", array_keys($voteids));

	//done
		if(count($moditemids) == 0){
//			$this->db->commit();
			$this->db->query("UNLOCK TABLES");
			return;
		}

		$res = $this->db->prepare_query("SELECT * FROM modvotes WHERE moditemid IN (#)", $moditemids);

		$mods = array();
		$modvotes = array();
		while($line = $res->fetchrow()){
			$modvotes[$line['moditemid']][] = $line;

			if(!isset($mods[$line['modid']]))
				$mods[$line['modid']] = array('right' => 0, 'wrong' => 0, 'lenient' => 0, 'strict' => 0);
		}


		$items = array( 'y' => array(), 'n' => array() );
		$votes = array();

		foreach($moditems as $item){
			$itemid = ($item['splitid']? "$item[splitid]:" : "") . $item['itemid'];

			$votes[$itemid] = array();

			if($item['points'] > 0){
				$items['y'][$itemid] = $itemid;

				foreach($modvotes[$item['id']] as $modvote){
					$votes[$itemid][$modvote['modid']] = $modvote['vote'];

					if($modvote['vote']=='y'){
						$mods[$modvote['modid']]['right']++;
					}else{
						$mods[$modvote['modid']]['wrong']++;
						$mods[$modvote['modid']]['strict']++;
					}
				}
			}else{
				$items['n'][$itemid] = $itemid;

				foreach($modvotes[$item['id']] as $modvote){
					$votes[$itemid][$modvote['modid']] = $modvote['vote'];

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

		$this->db->query("UNLOCK TABLES");
//		$this->db->commit();


	/*
		$items['y'][$itemid] = $itemid;
		$items['n'][$itemid] = $itemid;

		$votes[$itemid][$modid] = $vote;
	*/

//print_r($items);

		$cache->remove("modvotes-$userData[userid]");


		switch($type){
			case MOD_PICS:				$this->modpics($items, $votes, MOD_PICS);	break;
			case MOD_SIGNPICS:			$this->modsignpics($items, $votes);			break;
			case MOD_QUESTIONABLEPICS:	$this->modpics($items, $votes, MOD_QUESTIONABLEPICS);	break;

			case MOD_FORUMPOST:			$this->modforumpost($items, $votes);		break;
			case MOD_FORUMRANK:			$this->modforumrank($items, $votes);		break;
			case MOD_FORUMBAN:			$this->modforumban($items, $votes);			break;

			case MOD_GALLERY:			$this->modgallery($items, $votes);			break;
			case MOD_GALLERYABUSE:		$this->modgalleryabuse($items, $votes);		break;

			case MOD_USERABUSE:			$this->moduserabuse($items, $votes);		break;
			case MOD_USERABUSE_CONFIRM:	$this->moduserabuseconfirm($items, $votes);	break;

			case MOD_BANNER:			$this->modbanners($items, $votes);			break;

			case MOD_ARTICLE:			$this->modarticles($items, $votes);			break;

			case MOD_POLL:				$this->modpolls($items, $votes);			break;

			case MOD_VIDEO:				$this->modvideo($items, $votes);			break;
		}
	}



	function reorderPicPriorites($users, $numpics){
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
			$usersdb->prepare_query("UPDATE pics SET priority = # WHERE ^", $priority,
				$usersdb->prepare_multikey($keys, $picids));
	}


/////////////////////////////////////
	function modpics($pics, $votes, $type){
		global $usersdb, $moddb, $userData, $google, $RAP, $Ruby;

		$time = time();

		$allpics = array_merge($pics['y'], $pics['n'], array_keys($votes));
		$keys = array('userid' => '%', 'id' => '#');
		$res = $usersdb->prepare_query("SELECT id, userid, description, priority, md5 FROM gallerypics WHERE ^",
			$usersdb->prepare_multikey($keys, $allpics));

		$userids = array();
		$picids = array();
		$md5s = array();
		$descriptions = array();
		$priority = array();
		while($line = $res->fetchrow()){
			$id = "$line[userid]:$line[id]";
			$picids[$id] = $line['id'];
			$userids[$id] = $line['userid'];
			$md5s[$id] = $line['md5'];
			$descriptions[$id] = $line['description'];
			$priority[$id] = $line['priority'];
		}
		$missing = array_diff($allpics, array_keys($picids));
		foreach ($missing as $missingid)
		{
			if (isset($pics['y'][$missingid]))
				unset($pics['y'][$missingid]);
			if (isset($pics['n'][$missingid]))
				unset($pics['n'][$missingid]);
			unset($votes[$missingid]);
		}
		if (!$votes)
			return; // No valid pics in this queue.

		if($type == MOD_PICS){
		//log the votes
			$querytail = array();

			foreach($votes as $picid => $items) {
				foreach($items as $modid => $vote) {
					$lvl = $this->getModLvl($modid, MOD_PICS);
					if ($lvl === false)
						$lvl = 0;

					$querytail[] = $moddb->prepare("(#,#,#,?,#,?,#)", $modid, $picids[$picid], $userids[$picid], $vote, $time, $descriptions[$picid], $lvl);
				}
			}

			$moddb->query("INSERT INTO modvoteslog (modid, picid, userid, vote, time, description, points) VALUES " . implode(",", $querytail));

		//add pics that didn't get a unanimous vote either way
			$questionables = array('y' => array(), 'n' => array()); // y/n denotes priority or not

			foreach($votes as $picid => $uservotes){
				$questionable = false;
				foreach($uservotes as $userid => $vote){
					if(!$questionable){
						$questionable = $vote;
					}elseif($questionable != $vote){
						list($picuid, $picitem) = explode(':', $picid);
						$pic_priority = $priority[$picid] ? 'y' : 'n';

						if(!isset($questionables[$pic_priority][$picuid]))
							$questionables[$pic_priority][$picuid] = array();
						$questionables[$pic_priority][$picuid][] = $picitem;

						if(isset($pics['y'][$picid]))
							unset($pics['y'][$picid]);
						if(isset($pics['n'][$picid]))
							unset($pics['n'][$picid]);
						unset($votes[$picid]);

						break;
					}
				}
			}

		//if there are questionables, add them to the queue and remove them from this section
			if(count($questionables['y']))
				$this->newSplitItem(MOD_QUESTIONABLEPICS, $questionables['y'], true);

			if(count($questionables['n']))
				$this->newSplitItem(MOD_QUESTIONABLEPICS, $questionables['n'], false);


			if(count($pics['y']) == 0 && count($pics['n']) == 0)
				return;
		}

		// $votes	[ itemid ]	[ modid ]	=	y/n
		// $pics	[ y/n ]		[ itemid ]	=	itemid

		// votes for questionable queue are in. go back and fix the original pic mod's stats to reflect
		// the voting decision attained by the quesitonable queue mods
		elseif ($type == MOD_QUESTIONABLEPICS) {
			// fetch each vote cast for the pics in question (from original AND questionable queues)
			$logkeys = array('userid' => '#', 'picid' => '#');
			$sth = $moddb->prepare_query('SELECT picid, userid, modid, vote, points FROM modvoteslog WHERE ^',
				$moddb->prepare_multikey($logkeys, array_keys($votes)));
			$rows = $sth->fetchrowset();

			// need a point count of original picmods' votes
			$origvotes = array();
			foreach ($rows as $index => $row) {
				$picid = "$row[userid]:$row[picid]";
				// vote was cast in questionable queue. don't need it
				if (isset($votes[ $picid ][ $row['modid'] ])) {
					unset($rows[$index]);
					continue;
				}

				// vote was cast in original picmod queue
				if (! isset($origvotes[ $picid ]))
					$origvotes[ $picid ] = array('y' => 0, 'n' => 0);
				$origvotes[ $picid ][ $row['vote'] ] += $row['points'];
			}

			// loop through the original picmod votes, and calculate the stats diffs
			$modpoints = array();
			foreach ($rows as $row) {
				if (! isset($modpoints[ $row['modid'] ]))
					$modpoints[ $row['modid'] ] = array(
						'right' => 0, 'wrong' => 0, 'strict' => 0, 'lenient' => 0
					);

				// questionable queue mods accepted, original mods as a whole denied
				if (isset($pics['y'][ $picid ]) && $origvotes[ $picid ]['n'] > $origvotes[ $picid ]['y']) {
					// original mod denied
					if ($row['vote'] == 'n') {
						$modpoints[ $row['modid'] ]['right']--;
						$modpoints[ $row['modid'] ]['wrong']++;
						$modpoints[ $row['modid'] ]['strict']++;
					}

					// original mod accepted
					else {
						$modpoints[ $row['modid'] ]['wrong']--;
						$modpoints[ $row['modid'] ]['lenient']--;
						$modpoints[ $row['modid'] ]['right']++;
					}
				}

				// questionable queue mods denied, orginal mods as a whole approved
				elseif (isset($pics['n'][ $picid ]) && $origvotes[ $picid ]['y'] > $origvotes[ $picid ]['n']) {
					// original mod denied
					if ($row['vote'] == 'n') {
						$modpoints[ $row['modid'] ]['wrong']--;
						$modpoints[ $row['modid'] ]['strict']--;
						$modpoints[ $row['modid'] ]['right']++;
					}

					// original mod accepted
					else {
						$modpoints[ $row['modid'] ]['right']--;
						$modpoints[ $row['modid'] ]['wrong']++;
						$modpoints[ $row['modid'] ]['lenient']++;
					}
				}
			}

			foreach ($modpoints as $modid => $points)
				$this->db->prepare_query("UPDATE mods SET `right` = `right` + #, wrong = wrong + #, strict = strict + #, lenient = lenient + #, time = # WHERE userid = # && type = #", $points['right'], $points['wrong'], $points['strict'], $points['lenient'], time(), $modid, MOD_PICS);
		}

//pics that were denied
		if(isset($pics['n']) && count($pics['n'])){

			foreach($pics['n'] as $id){
				$res = $usersdb->prepare_query("SELECT times, userid FROM picbans WHERE md5 = ? FOR UPDATE", $md5s[$id]);
				$bans = $res->fetchrowset();

				if($bans){
					if(count($bans) >= 4){ //perma-ban
						$usersdb->prepare_query("INSERT INTO picbans SET md5 = ?, times = 10, userid = %", $md5s[$id], 0);
						$usersdb->prepare_query("DELETE FROM picbans WHERE md5 = ? && userid != 0", $md5s[$id]);
					}else{
						$rows = array();
						foreach ($bans as $line2)
							$rows[$line2['userid']] = $line2;

						if(isset($rows[$userids[$id]])){ //already denied before
							$usersdb->prepare_query("UPDATE picbans SET times = times + 1 WHERE md5 = ? && userid = %", $md5s[$id], $userids[$id]);
						}else{
							$usersdb->prepare_query("INSERT INTO picbans SET md5 = ?, userid = %, times = 1", $md5s[$id], $userids[$id]);
						}
					}
				}else{
					$usersdb->prepare_query("INSERT INTO picbans SET md5 = ?, userid = %, times = 1", $md5s[$id], $userids[$id]);
				}
			}

			$keys = array('userid' => '%', 'gallerypicid' => '#');
			$usersdb->prepare_query("DELETE FROM pics WHERE ^", $usersdb->prepare_multikey($keys, $pics['n']));
			global $cache;
			foreach($pics['n'] as $pic){
				list($uid, $id) = explode(":", $pic);
				$cache->remove("pics-$uid");
			}
			//$google->updateHash(array_keys($users));

			// Update gallery pic table to indicate that a picture has failed the queue.
			$keys = array('userid' => '%', 'id' => '#');
			$usersdb->prepare_query("UPDATE gallerypics SET userpic = # WHERE ^", USERPIC_FAILED, $usersdb->prepare_multikey($keys, $pics['n']));

			// Update the users table if they were using this image as
			// their sign pic or thumb pic
			foreach($pics['n'] as $pic) {
				list($uid, $id) = explode(":", $pic);
				$Ruby->Pics->update_user_info($uid);
				$cache->remove("userinfo-$uid");
			}

			//removePicPending($pics['n'], false);
		}

//pics that were accepted
		if(isset($pics['y']) && count($pics['y'])){

			$keys = array('userid' => '%', 'id' => '#');
			$result = $usersdb->prepare_query("SELECT id, userid, signpic FROM gallerypics WHERE ^",
				$usersdb->prepare_multikey($keys, $pics['y']));

			$uids = array();  // array(userids)
			$users = array(); // userid => array(picids)
			$numpics = array(); // userid => numpics
			$signpics = array();
			while($line = $result->fetchrow()){
				$uids[] = $line['userid'];
				if(!isset($users[$line['userid']]))
					$users[$line['userid']] = array();
				$users[$line['userid']][] = "$line[userid]:$line[id]";
				$numpics[$line['userid']] = 0;
				if($line['signpic'] == 'y')
				{
					if (!isset($signpics[$line['userid']]))
						$signpics[$line['userid']] = array();
					$signpics[$line['userid']] = $line['id'];
				}
			}

			$res = $usersdb->prepare_query("SELECT userid,count(*) as count FROM pics WHERE userid IN (%) GROUP BY userid", $uids);

			while($line = $res->fetchrow())
				$numpics[$line['userid']] = $line['count'];


			$gallerykeys = array('gallerypics.userid' => '%', 'gallerypics.id' => '#');
			$res = $usersdb->prepare_query("SELECT picbans.md5, picbans.userid FROM picbans,gallerypics WHERE ^ && picbans.md5=gallerypics.md5 && picbans.userid=gallerypics.userid",
				$usersdb->prepare_multikey($gallerykeys, $pics['y']));

			$unbans = array();
			while($line = $res->fetchrow())
				$unbans[] = "$line[userid]:$line[md5]";

			if(count($unbans))
			{
				$bankeys = array('userid' => '%', 'md5' => '?');
				$usersdb->prepare_query("DELETE FROM picbans WHERE ^",
					$usersdb->prepare_multikey($bankeys, $unbans));
			}

			// Update gallery pic table to indicate that a picture has failed the queue.
			$keys = array('userid' => '%', 'id' => '#');
			$usersdb->prepare_query("UPDATE gallerypics SET userpic = # WHERE ^", USERPIC_ACCEPTED, $usersdb->prepare_multikey($keys, $pics['y']));

			setFirstPic($uids);

			if(count($signpics))
				$this->newSplitItem(MOD_SIGNPICS, $signpics);

			foreach($pics['y'] as $key => $picid){
				$exp = explode(":", $picid);
				$id = $exp[1];
				enqueue( "Profile::UserPicture", "create", $uid, array($uid, (int)$id) );
			}
		}
	}

	function modsignpics($pics, $votes){
		global $usersdb, $usersdb, $cache;

		$keys = array('userid' => '%', 'id' => '#');

		if(count($pics['n']))
			$usersdb->prepare_query("UPDATE gallerypics SET signpic = # WHERE ^", SIGNPIC_FAILED,
				$usersdb->prepare_multikey($keys, $pics['n']));

		if(count($pics['y'])){
			$usersdb->prepare_query("UPDATE gallerypics SET signpic = # WHERE ^", SIGNPIC_ACCEPTED,
				$usersdb->prepare_multikey($keys, $pics['y']));


			$change = array();

			$keys = array('userid' => '%', 'gallerypicid' => '#');
			$res = $usersdb->prepare_query("SELECT userid, count(*) as count FROM pics WHERE ^ GROUP BY userid",
				$usersdb->prepare_multikey($keys, $pics['y']));
			while($line = $res->fetchrow())
				if ($line['count'] > 0)
					$change[]= $line['userid'];


			if (count($change) > 0)
				$usersdb->prepare_query("UPDATE users SET signpic = 'y' WHERE userid IN (%)", $change);

			foreach($change as $uid) {
				$cache->remove("userinfo-$uid");
			}
			foreach ($pics['y'] as $key) {
				$cache_id = implode('/', explode(':', $key));
				$cache->remove("Gallery::Pic-$cache_id");
			}
		}
	}

	function modgallery($pics, $votes){
		global $usersdb, $galleries, $rap_pagehandler;

		$rap_pagehandler->delete_gallery_pics($pics['n']);
	}

	function modgalleryabuse($pics, $votes){
		global $db, $usersdb, $rap_pagehandler;

		$db->prepare_query("DELETE FROM abuse WHERE type = # && itemid IN (#)", MOD_GALLERYABUSE, array_merge($pics['y'], $pics['n']));

		if(isset($pics['n']) && count($pics['n'])){
			$rap_pagehandler->delete_gallery_pics($pics['n']);
		}
	}

	function moduserabuse($pics, $votes){
		global $db, $userData, $abuselog;

		$res = $db->prepare_query("SELECT itemid, abuselogid FROM abuse WHERE type = # && itemid IN (#)", MOD_USERABUSE, array_merge($pics['y'], $pics['n']));

		$abuselogids = array();
		while($line = $res->fetchrow())
			$abuselogids[$line['itemid']][] = $line['abuselogid'];


		if(count($pics['y'])){
			$db->prepare_query("DELETE FROM abuse WHERE type = # && itemid IN (#)", MOD_USERABUSE, $pics['y']);

			foreach($pics['y'] as $id)
				foreach($abuselogids[$id] as $id2)
					$abuselog->db->prepare_query("UPDATE abuselog SET modid = # WHERE id = #", $userData['userid'], $id2);
//					$abuselog->addAbuseComment($id2, "Finished by " . $userData['username']);
		}

		if(count($pics['n'])){
			$db->prepare_query("UPDATE abuse SET type = #, reason = CONCAT(reason, ?) WHERE itemid IN (#)", MOD_USERABUSE_CONFIRM, "<br>\n<br>\nConfirmed by " . $userData['username'], $pics['n']);

			foreach($pics['n'] as $id)
				foreach($abuselogids[$id] as $id2)
					$abuselog->addAbuseComment($id2, "Confirmed by " . $userData['username']);

			$this->newItem(MOD_USERABUSE_CONFIRM, $pics['n']);
		}
	}

	function moduserabuseconfirm($pics, $votes){
		global $db, $abuselog, $userData;

		$res = $db->prepare_query("SELECT itemid, abuselogid FROM abuse WHERE type = # && itemid IN (#)", MOD_USERABUSE_CONFIRM, array_merge($pics['y'], $pics['n']));

		$abuselogids = array();
		while($line = $res->fetchrow())
			$abuselogids[] = $line['abuselogid'];

		foreach($abuselogids as $id)
			$abuselog->db->prepare_query("UPDATE abuselog SET modid = # WHERE id = #", $userData['userid'], $id);
//			$abuselog->addAbuseComment($id, "Finished by " . $userData['username']);

		$db->prepare_query("DELETE FROM abuse WHERE type = # && itemid IN (#)", MOD_USERABUSE_CONFIRM, array_merge($pics['n'], $pics['y']));
	}

	function modforumpost($pics, $votes){
		global $db;
		$db->prepare_query("DELETE FROM abuse WHERE type = # && itemid IN (#)", MOD_FORUMPOST, array_merge($pics['n'], $pics['y']));
	}

	function modforumban($pics, $votes){
	}

	function modvideo($pics, $votes){
		global $db;
		$db->prepare_query("DELETE FROM abuse WHERE type = # && itemid IN (#)", MOD_VIDEO, array_merge($pics['n'], $pics['y']));
	}

	function modarticles($pics, $votes){
		global $articlesdb, $messaging;

		if(count($pics['y'])){
			$result = $articlesdb->prepare_query("SELECT authorid, title FROM articles WHERE id IN (#)", $pics['y']);

			while($line = $result->fetchrow())
				$messaging->deliverMsg($line['authorid'],"Article Accepted", "Congrats! Your article '$line[title]' has been accepted and is now being featured in Nexopia's article section./n/nCheers,/n-- The Nex Team", 0, "Nexopia", 0);

			$articlesdb->prepare_query("UPDATE articles SET moded='y', time = # WHERE id IN (#)", time(), $pics['y']);
		}

		if(count($pics['n'])){
			$result = $articlesdb->prepare_query("SELECT authorid, title FROM articles WHERE id IN (#)", $pics['n']);

			while($line = $result->fetchrow())
				$messaging->deliverMsg($line['authorid'],"Article Denied", "Your article '$line[title]' has been rejected. Some of the reasons why articles are rejected are listed [url=/faq.php?q=31]here[/url].", 0, "Nexopia", 0);

			$articlesdb->prepare_query("DELETE FROM articles WHERE id IN (#)", $pics['n']);
		}
	}

	function modbanners($pics, $votes){

	}

	function modforumrank($pics, $votes){
		global $usersdb, $msgs, $cache;

		if(count($pics['n'])){
			$usersdb->prepare_query("UPDATE users SET forumrank = '' WHERE userid IN (%)", $pics['n']);

			foreach($pics['n'] as $uid)
				$cache->remove("userinfo-$uid");
		}

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
