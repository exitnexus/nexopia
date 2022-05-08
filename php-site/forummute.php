<?

	$login=1;

	require_once("include/general.lib.php");

	$fid = getREQval('fid', 'int');
	$tid = getREQval('tid', 'int');

	if($fid){
		$perms = $forums->getForumPerms($fid);	//checks it's a forum, not a realm

		if(!$perms['mute'])
			die("You don't have permission to mute people in this forum");

		$forumdata = $perms['cols'];
	}else{ //fid = 0
		if(!$mods->isAdmin($userData['userid'],'forums')){
			$res = $forums->db->prepare_query("SELECT mute FROM forummods WHERE userid = # && forumid = 0", $userData['userid']);
			$mod = $res->fetchrow();

			if(!$mod || $mod['mute'] == 'n')
				die("You don't have permission to mute people in this forum");
		}

		$forumdata = array("name" => "Global", 'official' => 'y');
		$perms['mute'] = false;
		$perms['globalmute'] = true;
	}

	switch($action){
		case "add":
			addMute($fid, $tid, getREQval('username'));
			break;
		case "Mute":
			if(!($uid = trim(getPOSTval('username'))))
				break;

			$uid = getUserId($uid);

			if(!$uid){
				$msgs->addMsg("Must select a valid user");
				addMute($fid, $tid, getREQval('username'));
			}

			$time = getPOSTval('time', 'int', -1);
			$globaltime = getPOSTval('globaltime', 'int', -1);

			if($fid && $perms['mute'] && $time >= 0 && !isset($forums->mutelength[$time])){
				$msgs->addMsg("Must select a valid length");
				addMute($fid, $tid, getREQval('username'));
			}

			if($perms['globalmute'] && $globaltime >= 0 && !isset($forums->mutelength[$globaltime])){
				$msgs->addMsg("Must select a valid length");
				addMute($fid, $tid, getREQval('username'));
			}

			if($time == -1 && $globaltime == -1){
				$msgs->addMsg("Must select a valid length");
				addMute($fid, $tid, getREQval('username'));
			}

			if(!($reasonid = getPOSTval('reasonid', 'int')) || !isset($forums->reasons[$reasonid])){
				$msgs->addMsg("Must specify a category");
				addMute($fid, $tid, getREQval('username'));
			}

			if(!($reason = getPOSTval('reason'))){
				$msgs->addMsg("Must specify a reason");
				addMute($fid, $tid, getREQval('username'));
			}

			$globalreq = ($perms['globalmute'] ? false : getPOSTval('globalreq', 'bool'));

			if($perms['mute'] && $fid && $time >= 0)
				$forums->forumMuteUser($uid, $fid, $tid, $time, $reasonid, $reason, $globalreq, $forumdata['name'], ($forumdata['official'] == 'y'));

			if($perms['globalmute'] && $globaltime >= 0)
				$forums->forumMuteUser($uid, 0, $tid, $globaltime, $reasonid, $reason, $globalreq, "Global", true);

			break;
		case "delete":
			if(($uid = getREQval('uid', 'int')) && ($k = getREQval('k')) && checkKey("$fid:$uid", $k))
				$forums->forumUnmuteUser($uid, $fid);

			break;
	}


	listMutes();

//////////////////////


function addMute($fid, $tid, $username){
	global $forums, $perms;

	$globalMuteCheck = '';
	if($fid){
		if(!$perms['globalmute'] && $perms['cols']['official'] == 'y'){
			$globalMuteCheck = makeCheckBox("globalreq", "Request Global Mute");
		}
	}

	$selectMuteLength = make_select_list_key($forums->mutelength, -1);
	$selectMuteReasons = make_select_list_key($forums->reasons);

	$template = new template('forums/forummute/addMute');
	$template->set('fid', $fid);
	$template->set('selectMuteReasons', $selectMuteReasons);
	$template->set('selectMuteLength', $selectMuteLength);
	$template->set('globalMuteCheck', $globalMuteCheck);
	$template->set('perms', $perms);
	$template->set('username', $username);
	$template->set('tid', $tid);
	$template->display();
	exit;
}

function listMutes(){
	global $forums, $db, $fid, $cache, $forumdata, $config;

//get mutes
	$res = $forums->db->prepare_query("SELECT forummute.id, userid, mutetime, unmutetime, reasonid, modid, reason, threadid FROM forummute, forummutereason WHERE forummute.id = forummutereason.id && forummute.forumid = #", $fid);

	$muterows = array();
	$uids = array();
	while($line = $res->fetchrow()){
		$muterows[] = $line;
		$uids[$line['userid']] = $line['userid'];
		$uids[$line['modid']] = $line['modid'];
	}

//print_r($muterows);

//get usernames of users and mods, also used to delete/unmute users that don't exist anymore
	$userrows = array();
	if(count($uids))
		$userrows = getUserName($uids);

	$unmute = array();
	$delete = array();
	$time = time();

//join the two tables, keep track of those to unmute
	$rows = array();
	foreach($muterows as $line){
		if(!isset($userrows[$line['userid']])){ //user deleted
			$delete[] = $line['userid'];
		}elseif($line['unmutetime'] && $line['unmutetime'] < $time){ //exists, but should be unmuted
			$unmute[$line['userid']] = $line['unmutetime'];
			$delete[] = $line['userid'];
		}else{
			$rows[$userrows[$line['userid']]] = $line;
			$rows[$userrows[$line['userid']]]['username'] = $userrows[$line['userid']];
			if(isset($userrows[$line['modid']]))
				$rows[$userrows[$line['userid']]]['modname'] = $userrows[$line['modid']];
		}
	}

//unmute them, log those where the accounts still exist and uncache the mutes
	if(count($delete))
		$forums->db->prepare_query("DELETE forummute, forummutereason FROM forummute, forummutereason WHERE forummute.id = forummutereason.id && userid IN (#) && forumid = #", $delete, $fid);

	if(count($unmute)){
		foreach($unmute as $uid => $unmutetime){
			$forums->modLog('unmute', $fid, 0, $uid, $unmutetime, 0);
			$cache->remove("forummutes-$uid-$fid");
		}
	}


	uksort($rows, 'strcasecmp');


	$classes = array('body', 'body2');

	$i = -1;
	$key = array();
	foreach($rows as $line){
		$i++;
		$key[$fid][$line['userid']] = makeKey("$fid:$line[userid]");
		if ($i > 1) {
			$classes[$i] = $classes[$i%2];
		}
	}

	$template = new template('forums/forummute/listMutes');
	$template->set('fid', $fid);
	$template->set('rows', $rows);
	$template->set('classes', $classes);
	$template->set('key', $key);
	$template->set('forums', $forums);
	$template->set('config', $config);
	$template->set('forumdata', $forumdata);
	$template->display();
	exit;
}

