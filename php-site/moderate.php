<?

	$login=1;

	set_time_limit(300);

	require_once("include/general.lib.php");


	if(!$mods->isMod($userData['userid']))
		die("Permission denied");

	$mode = getREQval('mode', 'int');

//	if ($mode == MOD_PICS || $mode == MOD_SIGNPICS || $mode == MOD_QUESTIONABLEPICS)
//		die("Picture moderation temporarily disabled. Please try again later.");

	$id = getREQval('id', 'int');
	$for_uid = ($mods->isAdmin($userData['userid'])? getREQval('uid', 'int', null) : null);

	$vote = getREQval('vote');

	if(!empty($vote)){
//		echo "mode: $mode<br>";

		$checkID = getPOSTval('checkID', 'array');
		$newCheckID = array();
		foreach ($checkID as $id => $a)
			$newCheckID[$id] = $a;
		$checkID = $newCheckID;

		switch($mode){
			case MOD_PICS:
			case MOD_PICABUSE:
			case MOD_SIGNPICS:
			case MOD_FORUMRANK:
			case MOD_FORUMPOST:
			case MOD_FORUMBAN:
			case MOD_GALLERY:
			case MOD_GALLERYABUSE:
			case MOD_USERABUSE:
			case MOD_USERABUSE_CONFIRM:
			case MOD_QUESTIONABLEPICS:
			case MOD_VIDEO:
//		print_r($checkID);


				if(count($checkID))
					$mods->vote($checkID,$mode);
				break;

			case MOD_ARTICLE:
//			case MOD_BANNER:
			case MOD_POLL:

//				echo "vote: $vote, id: $id";

				if(count($checkID)){
					$mods->vote($checkID,$mode);
					break;
				}

				if($vote == "Accept") 	$vote = 'y';
				elseif($vote == "Deny")	$vote = 'n';
				else			break;

				$mods->vote(array($id => $vote),$mode);
				$id = 0;
				break;
		}
	}

	$moditemcounts = $mods->getModItemCounts();

	$stopModding = getREQval('stopModding');
	if ($stopModding == 'y')
		displayTypes($moditemcounts); // exit

	switch($mode){
		case MOD_PICS:				if(isset($moditemcounts[MOD_PICS]))				displayRubySite($mode);						break;
		case MOD_SIGNPICS:			if(isset($moditemcounts[MOD_SIGNPICS]))			displayRubySite($mode);						break;
		case MOD_QUESTIONABLEPICS:	if(isset($moditemcounts[MOD_QUESTIONABLEPICS]))	displayRubySite($mode);						break;
		case MOD_FORUMRANK:			if(isset($moditemcounts[MOD_FORUMRANK]))		displayRubySite($mode);						break;
		case MOD_FORUMPOST:			if(isset($moditemcounts[MOD_FORUMPOST]))		forumPostAbuse();							break;
		case MOD_FORUMBAN:			if(isset($moditemcounts[MOD_FORUMBAN]))			forumBans();								break;
		case MOD_GALLERY:			if(isset($moditemcounts[MOD_GALLERY]))			displayRubySite($mode);						break;
		case MOD_GALLERYABUSE:		if(isset($moditemcounts[MOD_GALLERYABUSE]))		displayRubySite($mode);						break;
		case MOD_USERABUSE:			if(isset($moditemcounts[MOD_USERABUSE]))		displayUserAbuse(MOD_USERABUSE);			break;
		case MOD_USERABUSE_CONFIRM:	if(isset($moditemcounts[MOD_USERABUSE_CONFIRM]))displayUserAbuse(MOD_USERABUSE_CONFIRM);	break;
		case MOD_ARTICLE:			if(isset($moditemcounts[MOD_ARTICLE]))			displayRubySite($mode);						break;
		case MOD_POLL:				if(isset($moditemcounts[MOD_POLL]))				displayRubySite($mode);						break;
	}
	displayTypes($moditemcounts); // exit

///////////////

function displayTypes($moditemcounts){
	global $mods;


	$template = new template('moderate/displayTypes');

	$types = array();
	foreach($moditemcounts as $type => $num)
		if($num > 0)
			$types[$type] = $num;

	$template->set('types', $types);
	$template->set('noRequests', count($types)==0);
	$template->set('modtypes', $mods->modtypes);

	$template->display();

	exit;
}

function displayRubySite($type){// redirects to ruby queue
	header("Location: /moderate/queue/$type");
	exit();
}

function displayPic($type){ //pics or questionable
	global $config, $usersdb, $userData, $mods, $wwwdomain, $for_uid;

	$lvl = $mods->getModLvl($userData['userid'], $type);

	$prefs = $mods->getModPrefs($userData['userid'], $type);

	$ids = $mods->getModItems($type, $prefs['picsperpage'], 3, 120, true, $for_uid);

	if(!count($ids))
		return;

	$keys = array('userid' => '%', 'id' => '#');
	
	$res = $usersdb->prepare_query("SELECT id, userid, description, created AS time FROM gallerypics WHERE ^",
		$usersdb->prepare_multikey($keys, $ids));
	// $res = $usersdb->prepare_query("SELECT id, userid, description FROM gallerypics WHERE ^",
	// 	$usersdb->prepare_multikey($keys, $ids));
		
	$rows = array();
	$uids = array();

	while($line = $res->fetchrow()){
		$rows[] = $line;
		$uids[$line['userid']] = $line['userid'];
		unset($ids["$line[userid]:$line[id]"]);
	}

	if(count($ids) > 0)
		$mods->deleteSplitItem($type, $ids);

	if(count($rows) == 0)
		return;

	$users = getUserInfo($uids);

/*
//doesn't work, as the join fails, probably shouldn't be shown anyway
	if($type == MOD_QUESTIONABLEPICS){
		$res = $usersdb->prepare_query("SELECT modvoteslog.picid, modvoteslog.vote, mods.level FROM modvoteslog, mods WHERE mods.userid = modvoteslog.modid && modvoteslog.picid IN (?)", array_keys($rows));

		$votes = array();
		while($line = $res->fetchrow())
			$votes[$line['picid']][] = $line;
	}
*/

	$template = new template('moderate/displayPic');
	$template->set('type', $type);
	$template->set('prefs', $prefs);
	$picloc = $config['picloc'];
	$template->set('picloc', $picloc);
	$template->set('users', $users);

	$time = time();
	$i=1;
	$nextJump = array();
	foreach($rows as $line){
		$picdir[$i] = floor($line['userid']/1000) . "/" . weirdmap($line['userid']);

		$nextJump[$i] = $i+1;
		$waitTime[$i] = number_format(($time - $line['time'])/3600, 2);
		$i++;
	}
	$template->set('picdir', $picdir);
	$template->set('nextJump', $nextJump);
	$template->set('waitTime', $waitTime);
	$template->set('rows', $rows);
	$template->set('showUsername', $type == MOD_QUESTIONABLEPICS ? true : false);
	$template->display();
	exit;
}

function displaySignPics(){
	global $config, $usersdb, $userData, $mods, $wwwdomain;

	$lvl = $mods->getModLvl($userData['userid'], MOD_SIGNPICS);

	$prefs = $mods->getModPrefs($userData['userid'], MOD_SIGNPICS);

	$ids = $mods->getModItems(MOD_SIGNPICS, $prefs['picsperpage'], 3, 60, true);

	if(!count($ids))
		return;

	$keys = array('userid' => '%', 'id' => '#');
	$res = $usersdb->prepare_query("SELECT id, userid, description FROM gallerypics WHERE ^",
		$usersdb->prepare_multikey($keys, $ids));

	$rows = array();
	$uids = array();

	while($line = $res->fetchrow()){
		$rows[] = $line;
		$uids[$line['userid']] = $line['userid'];
		unset($ids["$line[userid]:$line[id]"]);
	}

	if(count($ids) > 0)
		$mods->deleteSplitItem(MOD_SIGNPICS, $ids);

	$users = getUserInfo($uids, false);

	if(count($rows) == 0)
		return;

	//currently signed and normal pics use the same template, this can be changed
	//later if desired.
	$template = new template('moderate/displayPic');
	$template->set('prefs', $prefs);
	//$picloc = $config['picloc'];
	$picloc = $config['gallerypicloc'];
	$template->set('picloc', $picloc);
	$template->set('users', $users);
	$template->set('type', MOD_SIGNPICS);
	$time = time();
	$i=1;
	foreach($rows as $line){
		$picdir[$i] = floor($line['userid']/1000) . "/" . weirdmap($line['userid']);

		$nextJump[$i] = $i+1;
		$waitTime[$i] = '';//number_format(($time - $line['time'])/3600, 2);
		$i++;
	}
	$template->set('picdir', $picdir);
	$template->set('nextJump', $nextJump);
	$template->set('waitTime', $waitTime);
	$template->set('rows', $rows);
	$template->set('showUsername', true);
	$template->display();
	exit;
}

function displayGallery(){
	global $config, $usersdb, $userData, $mods, $wwwdomain;

	$lvl = $mods->getModLvl($userData['userid'], MOD_GALLERY);

	$prefs = $mods->getModPrefs($userData['userid'], MOD_GALLERY);

	$ids = $mods->getModItems(MOD_GALLERY, $prefs['picsperpage'], 3, 60, true);

	if(!count($ids))
		return;

	$rows = array();
	$uids = array();

	$keys = array('userid' => '%', 'id' => '#');
	$res = $usersdb->prepare_query("SELECT id, userid, description FROM gallerypics WHERE ^",
		$usersdb->prepare_multikey($keys, $ids));

	while($line = $res->fetchrow()){
		$rows[] = $line;
		$uids[$line['userid']] = $line['userid'];
		unset($ids["$line[userid]:$line[id]"]);
	}

	if(count($ids))
		$mods->deleteSplitItem(MOD_GALLERY, $ids);

	if(!count($rows))
		return;

	$users = getUserInfo($uids);

	$template = new template('moderate/displayGallery');

	$picloc = $config['gallerypicloc'];
	$template->set('picloc', $picloc);
	$template->set('users', $users);
	$template->set('type', MOD_GALLERY);
	$template->set('prefs', $prefs);
	$time = time();
	$i=1;
	foreach($rows as $line){
		$picdir[$i] = floor($line['userid']/1000) . '/' . $line['userid'];//floor($line['id']/1000);

		$nextJump[$i] = $i+1;
		$i++;
	}
	$template->set('picdir', $picdir);
	$template->set('nextJump', $nextJump);
	$template->set('rows', $rows);
	$template->display();
	exit;
}

function displayGalleryAbuse(){
	global $config, $db, $usersdb, $userData, $mods, $wwwdomain;

	$lvl = $mods->getModLvl($userData['userid'], MOD_GALLERYABUSE);

	$prefs = $mods->getModPrefs($userData['userid'], MOD_GALLERYABUSE);

	$ids = $mods->getModItems(MOD_GALLERYABUSE, $prefs['picsperpage'], 20, 60, true);

	if(!count($ids))
		return;

	$rows = array();
	$uids = array();
	$keys = array('userid' => '%', 'id' => '#');
	$res = $usersdb->prepare_query("SELECT id, userid, description FROM gallerypics WHERE ^",
		$usersdb->prepare_multikey($keys, $ids));

	while($line = $res->fetchrow()){
		$line['abuseid'] = "$line[userid]:$line[id]";
		$rows["$line[userid]:$line[id]"] = $line;
		$uids[$line['userid']] = $line['userid'];
		unset($ids["$line[userid]:$line[id]"]);
	}

	if(count($ids))
		$mods->deleteSplitItem(MOD_GALLERYABUSE, $ids);

	if(!count($rows))
		return;

	$abusekeys = array('userid' => '#', 'itemid' => '#');
	$res = $db->prepare_query("SELECT itemid, userid, reason, time FROM abuse WHERE type = # && ^", MOD_GALLERYABUSE,
		$db->prepare_multikey($abusekeys, array_keys($rows)));

	$abuses = array();
	while($line = $res->fetchrow()){
		$abuses["$line[userid]:$line[itemid]"][] = $line;
		$uids[$line['userid']] = $line['userid'];
	}

	$users = getUserInfo($uids);

	$template = new template('moderate/displayGalleryAbuse');
	$template->set('type', MOD_GALLERYABUSE);
	$template->set('prefs', $prefs);
	$picloc = $config['gallerypicloc'];
	$template->set('picloc', $picloc);
	$template->set('users', $users);
	$template->set('rows', $rows);
	$template->set('abuses', $abuses);
	$i=0;
	$nextJump = array();
	foreach($rows as $line){
		$picdir[$i] = floor($line['userid']/1000) . '/' . $line['userid'];
		$nextJump[$i] = $i + 1;
		$i++;
	}
	$template->set('nextJump', $nextJump);
	$template->set('picdir', $picdir);
	$template->display();
	exit;
}

function displayVideos(){
	global $config, $db, $usersdb, $userData, $mods, $wwwdomain, $videodb;
	$lvl = $mods->getModLvl($userData['userid'], MOD_VIDEO);

	$prefs = $mods->getModPrefs($userData['userid'], MOD_VIDEO);

	$ids = $mods->getModItems(MOD_VIDEO, $prefs['picsperpage'], 180, 300, false);

	if(!count($ids))
		return;

	$res = $videodb->prepare_query("SELECT id, title, description, embed FROM video WHERE id IN (#)", $ids);

	$videos = array();
	$uids = array();
	while ($line = $res->fetchrow()){
		$videos[$line['id']] = $line;
		unset($ids[$line['id']]);
	}

	if(count($ids))
		$mods->deleteItem(MOD_VIDEO, $ids);

	$res = $db->prepare_query("SELECT itemid, userid, reason, time FROM abuse WHERE type = # && itemid IN (#)", MOD_VIDEO, array_keys($videos));
	while ($line = $res->fetchrow()){
		if (!isset($videos[$line['itemid']]['abuses']))
			$videos[$line['itemid']]['abuses'] = array();
		$videos[$line['itemid']]['abuses'][] = $line;
		$uids[] = $line['userid'];
	}

	$users = getUserInfo($uids);

	$template = new template('moderate/displayVideo');
	$template->set('videos', $videos);
	$template->set('type', MOD_VIDEO);
	$template->set('users', $users);
	$template->set('type', MOD_VIDEO);
	$template->display();

	exit;


/*	while($line = $res->fetchrow()){
		$line['abuseid'] = "$line[userid]:$line[id]";
		$rows["$line[userid]:$line[id]"] = $line;
		$uids[$line['userid']] = $line['userid'];
		unset($ids["$line[userid]:$line[id]"]);
	}*/

//	if(count($ids))
//		$mods->deleteSplitItem(MOD_VIDEO, $ids);

}

function displayUserAbuse($type){ //type = MOD_USERABUSE or MOD_USERABUSE_CONFIRM
	global $config, $db, $usersdb, $configdb, $userData, $mods, $abuselog;
	$lvl = $mods->getModLvl($userData['userid'], $type);

	$prefs = $mods->getModPrefs($userData['userid'], $type);

	$ids = $mods->getModItems($type, $prefs['picsperpage'], 180, 300);

	if(!count($ids))
		return;

	$rows = array();
	$uids = array();

	$result1 = $usersdb->prepare_query("SELECT userid as id, age, sex, loc, firstpic, state FROM users WHERE userid IN (%)", $ids);

	$result2 = $abuselog->db->prepare_query("SELECT id, userid, reportuserid, modid, action, reason, time, subject FROM abuselog WHERE userid IN (#) ORDER BY time DESC", $ids);

	while($line = $result1->fetchrow()){
		$rows[$line['id']] = $line;
		$rows[$line['id']]['abuse'] = array();

		$uids[$line['id']] = $line['id'];

		unset($ids[$line['id']]);
	}

	while($line = $result2->fetchrow()){
		if(isset($rows[$line['userid']])){
			$rows[$line['userid']]['abuse'][] = $line;

			$uids[$line['userid']] = $line['userid'];
			$uids[$line['modid']] = $line['modid'];
			$uids[$line['reportuserid']] = $line['reportuserid'];
		}
	}
	unset($uids[0]);

	if(count($ids))
		$mods->deleteItem($type, $ids);

	if(!count($rows))
		return;

	$res = $db->prepare_query("SELECT itemid, userid, reason, time FROM abuse WHERE type = # && itemid IN (#)", $type, array_keys($rows));

	$abuses = array();
	while($line = $res->fetchrow()){
		$abuses[$line['itemid']][] = $line;
		$uids[$line['userid']] = $line['userid'];
	}

	$users = getUserInfo($uids);

	$locations = new category( $configdb, "locs");

	$template = new template('moderate/displayUserAbuse');
	$template->set('type', $type);
	$template->set('rows', $rows);
	$template->set('isAdminListUsers', $mods->isAdmin($userData['userid'],'listusers'));
	$template->set('isAdminEditProfile', $mods->isAdmin($userData['userid'],"editprofile"));
	$template->set('isAdminEditPictures', $mods->isAdmin($userData['userid'],"editpictures"));
	$template->set('isAdminAbuseLog', $mods->isAdmin($userData['userid'],'abuselog'));
	$template->set('show', 15);
	$template->set('confirmedAbuse', $type == MOD_USERABUSE_CONFIRM ? true : false);
	$template->set('abuselog', $abuselog);
	$template->set('config', $config);
	$template->set('users', $users);

	$picdir = array();
	$i = -1;

	$abusepicdir = array();
	$abuseLoc = array();
	$abuseCount = array();
	$location = array();
	$key = array();

	foreach($rows as $line){
		$i++;

		$location[$i] = $locations->getCatName($line['loc']);
		if($line['firstpic']){
			$picdir[$i] = floor($line['id']/1000) . "/" . weirdmap($line['id']);
		}
		$key[$i]=makeKey($line['id']);
		$abuseCount[$i] = count($line['abuse']);

		if(isset($abuses[$line['id']])){
			$j = -1;
			foreach($abuses[$line['id']] as $abuse){
				$j++;
				$abusepicdir[$i][$j] = floor($abuse['userid']/1000) . "/" . weirdmap($abuse['userid']);

				$abuseLoc[$i][$j] = $locations->getCatName($users[$abuse['userid']]['loc']);
			}
		}else{
			$abuses[$line['id']] = array();
		}
	}
	$template->set('abuses', $abuses);
	$template->set('location', $location);
	$template->set('picdir', $picdir);
	$template->set('key', $key);
	$template->set('abuseCount', $abuseCount);
	$template->set('abusepicdir', $abusepicdir);
	$template->set('abuseLoc', $abuseLoc);
	$template->display();

	exit;
}

function displayBanners(){
	global $config, $userData, $mods, $banner;

	$lvl = $mods->getModLvl($userData['userid'], MOD_BANNER);

	$prefs = $mods->getModPrefs($userData['userid'], MOD_BANNER);

	$ids = $mods->getModItems(MOD_BANNER, 1, 20);

	$id = current($ids);

	$template = new template('moderate/displayBanners.html');

	$template->set('type', MOD_BANNER);
	$template->set('id', $id);
	$template->set('bannerID', $banner->getbannerId($id));
	$template->display();
	exit;
}

function displayForumRanks(){
	global $config, $forums, $usersdb, $userData, $mods;

	$lvl = $mods->getModLvl($userData['userid'], MOD_FORUMRANK);

	$prefs = $mods->getModPrefs($userData['userid'], MOD_FORUMRANK);

	$ids = $mods->getModItems(MOD_FORUMRANK, $prefs['picsperpage'], 5, 60);

	if(!count($ids))
		return;

	$rows = array();
	$res = $usersdb->prepare_query("SELECT userid, forumrank FROM users WHERE userid IN (%)", $ids);

	$rows = array();
	while($line = $res->fetchrow()){
		$rows[$line['userid']] = $line;
		unset($ids[$line['userid']]);
	}

	if(count($ids))
		$mods->deleteItem(MOD_FORUMRANK, $ids);

	if(!count($rows))
		return;

	$usernames = getUserName(array_keys($rows));
	foreach($rows as $k => $v)
		$rows[$k]['username'] = $usernames[$k];

	$template = new template('moderate/displayForumRanks');

	$template->set('prefs', $prefs);
	$template->set('type', MOD_FORUMRANK);
	$template->set('rows', $rows);

	$i=0;
	foreach($rows as $line){
		$nextJump[$i++] = $i;
	}

	$template->set('nextJump', $nextJump);
	$template->display();
	exit;
}

function forumPostAbuse(){
	global $config, $db, $usersdb, $userData, $mods, $forums;

	$lvl = $mods->getModLvl($userData['userid'], MOD_FORUMPOST);

	$prefs = $mods->getModPrefs($userData['userid'], MOD_FORUMPOST);

	$ids = $mods->getModItems(MOD_FORUMPOST, $prefs['picsperpage'], 60, 180);

	if(!count($ids))
		return;

	$rows = array();
	$posterids = array();
	$posterdata = array();
	$res = $forums->db->prepare_query("SELECT forumposts.id, threadid, title, msg, forumposts.authorid, forumposts.time, forumthreads.locked, forumthreads.flag, forumthreads.sticky, forumthreads.announcement, forums.name, forums.official, forumthreads.forumid, forumposts.edit FROM forumposts, forumthreads, forums WHERE forumposts.threadid=forumthreads.id && forumthreads.forumid=forums.id && forumposts.id IN (#)", $ids);

	while($line = $res->fetchrow()){
		$rows[$line['id']] = $line;
		$posterids[$line['authorid']] = $line['authorid'];
		unset($ids[$line['id']]);
	}

	if(count($ids))
		$mods->deleteItem(MOD_FORUMPOST, $ids);

	if(!count($rows))
		return;

	if(count($posterids)){
		$res = $usersdb->prepare_query("SELECT userid, online, age, sex, posts, firstpic, forumrank, showpostcount, '' as nsigniture, premiumexpiry, state, abuses FROM users WHERE userid IN (%)", $posterids);

		while($line = $res->fetchrow())
			$posterdata[$line['userid']] = $line;


		$res = $usersdb->prepare_query("SELECT userid, nsigniture FROM profile WHERE userid IN (%) && enablesignature = 'y'", $posterids);

		while($line = $res->fetchrow())
			$posterdata[$line['userid']]['nsigniture'] = $line['nsigniture'];

		$usernames = getUserName($posterids);

		foreach($rows as $k => $v)
			$rows[$k]['author'] = $usernames[$v['authorid']];
	}

	$res = $db->prepare_query("SELECT itemid, userid, reason, time FROM abuse WHERE type = # && itemid IN (#)", MOD_FORUMPOST, array_keys($rows));

	$abuses = array();
	$uids = array();
	while($line = $res->fetchrow()){
		$abuses[$line['itemid']][] = $line;
		$uids[$line['userid']] = $line['userid'];
	}

	$users = getUserInfo($uids);

	$time = time();


	$page = array();
	$forumTrail = array();
	$forumRank = array();
	$imgDir = array();
	$postCount = array();
	$parsedPost = array();
	$showSig = array();
	$implodedLinks = array();
	$abuseImgDir = array();


	$template = new template('moderate/forumPostAbuse');

	$template->set('type', MOD_FORUMPOST);
	$template->set('rows', $rows);
	$postsPerPage = $userData['forumpostsperpage'];

	$i = -1;
	foreach($rows as $line){
		$i++;
		$res = $forums->db->prepare_query("SELECT count(*) FROM forumposts WHERE threadid = # && time < #", $line['id'], $line['time']);
		$postnum = $res->fetchfield();

		$page[$i] = floor($postnum / $postsPerPage);

		$forumobj = $forums->getForums($line['forumid']);
		$forumTrail[$i] = $forums->getForumTrail($forumobj, 'header');
		$data = $posterdata[$line['authorid']];

		if($line['authorid']){
			if($data['forumrank']!="" && $data['premiumexpiry'] > $time)
				$forumRank[$i] = $data['forumrank'];
			else
				$forumRank[$i] = $forums->forumrank($data['posts']);
		}

		if($config['forumPic'] && $line['authorid'] && $data['firstpic']>0) {
			$imgDir[$i] = floor($line['authorid']/1000) . "/" . weirdmap($line['authorid']);
		}

		if($line['authorid']){
			if($data['showpostcount'] == 'y')
				$postCount[$i] = number_format($data['posts']);
		}

		$parsedPost[$i] = $forums->parsePost($line['msg']);

		$showSig[$i] = ($line['authorid'] && $data['nsigniture']!="");

		$links = array();

		$links[] = "<a class=small href=\"/messages.php?action=write&to=$line[authorid]\">Send Message</a>";
		if($mods->isAdmin($userData['userid'],"editsig"))
			$links[] = "<a class=small href=\"/manageprofile.php?uid=$line[authorid]&section=forums\">Edit Sig</a>";

		$implodedLinks[$i] = implode(" &nbsp; &nbsp; ", $links);

		$j = -1;
		if(isset($abuses[$line['id']])){
			$j++;
			foreach($abuses[$line['id']] as $abuse){
				if($users[$abuse['userid']]['firstpic']){
					$abuseImgDir[$i][$j] = floor($abuse['userid']/1000) . "/" . weirdmap($abuse['userid']);
				}
			}
		}else{
			$abuses[$line['id']] = array();
		}
	}
	$template->set('abuses', $abuses);
	$template->set('page', $page);
	$template->set('forumTrail', $forumTrail);
	$template->set('forumRank', $forumRank);
	$template->set('config', $config);
	$template->set('imgDir', $imgDir);
	$template->set('postCount', $postCount);
	$template->set('parsedPost', $parsedPost);
	$template->set('showSig', $showSig);
	$template->set('links', $implodedLinks);
	$template->set('abuseImgDir', $abuseImgDir);
	$template->set('posterdata', $posterdata);
	$template->set('users', $users);
	$template->display();
	exit;
}

function forumBans(){
	global $config, $forums, $userData, $mods, $mutelength;

	$lvl = $mods->getModLvl($userData['userid'], MOD_FORUMBAN);

	$prefs = $mods->getModPrefs($userData['userid'], MOD_FORUMBAN);

	$ids = $mods->getModItems(MOD_FORUMBAN, $prefs['picsperpage'], 20, 60);

	if(!count($ids))
		return;

	$rows = array();

	$res = $forums->db->prepare_query("SELECT	forummute.id,
										forummute.userid,
										mutetime,
										unmutetime,
										forummutereason.modid,
										reasonid,
										reason,
										forummute.forumid,
										threadid,
										globalreq,
										forums.name as forumname,
										forumthreads.title as threadname
								FROM 	forummute
									INNER JOIN forummutereason
									LEFT JOIN forums ON forummute.forumid = forums.id
									LEFT JOIN forumthreads ON forummutereason.threadid = forumthreads.id
								WHERE 	forummute.id = forummutereason.id &&
										forummute.id IN (#)", $ids);

	$uids = array();
	$users = array();
	while($line = $res->fetchrow()){
		$rows[$line['id']] = $line;
		$uids[$line['userid']] = $line['userid'];
		$uids[$line['modid']] = $line['modid'];
		unset($ids[$line['id']]);
	}

	if(count($ids))
		$mods->deleteItem(MOD_FORUMBAN, $ids);

	if(!count($rows))
		return;

	$mutes = array();
	$res = $forums->db->prepare_query("SELECT	forummute.userid,
										mutetime,
										unmutetime,
										forummutereason.modid,
										reason,
										reasonid,
										forummute.forumid,
										threadid,
										globalreq,
										forums.name as forumname
								FROM 	forummute
									INNER JOIN forummutereason
									LEFT JOIN forums ON forummute.forumid = forums.id
								WHERE 	forummute.id = forummutereason.id &&
										forummute.userid IN (#)
								ORDER BY mutetime DESC", $uids);

	while($line = $res->fetchrow())
	{
		$mutes[$line['userid']][] = $line;
		$uids[$line['userid']] = $line['userid'];
		$uids[$line['modid']] = $line['modid'];
	}

	$users = getUserName($uids);

	$template = new template('moderate/forumBans');

	$template->set('type', MOD_FORUMBAN);
	$template->set('rows', $rows);
	$template->set('users', $users);
	$template->set('forums', $forums);
	$template->set('mutes', $mutes);
	$template->set('classes', array('body', 'body2'));

	$index = -1;
	foreach($rows as $line){
		$index++;
		$muteLength[$index] = $forums->mutelength[($line['unmutetime'] ? $line['unmutetime'] - $line['mutetime'] : 0)];
		$globalRequested[$index] = ($line['forumid'] && $line['globalreq'] == 'y');
	}
	$template->set('muteLength', $muteLength);
	$template->set('globalRequested', $globalRequested);
	$template->display();
	exit;
}

function displayPolls($id){
	global $userData,$mods, $polls;

	if($id){
		$poll = $polls->getPoll($id, false);

		$template = new template('moderate/displayPoll');
		$template->set('type', MOD_POLL);
		$template->set('id', $id);
		$template->set('poll', $poll);
		$template->display();
		exit;
	}else{
		$res = $polls->db->prepare_query("SELECT id, question FROM polls WHERE official = 'y' && moded = 'n' ORDER BY date ASC");

		$rows = array();
		while($line = $res->fetchrow())
			$rows[$line['id']] = $line['question'];

		if(!count($rows))
			return;

		$res = $polls->db->prepare_query("SELECT pollid, answer FROM pollans WHERE pollid IN (#)", array_keys($rows));

		$ans = array();
		while($line = $res->fetchrow())
			$ans[$line['pollid']][] = $line['answer'];

		$template = new template('moderate/displayPolls');
		$template->set('type', MOD_POLL);
		$template->set('id', $id);
		$template->Set('rows', $rows);
		foreach($rows as $qid => $q){
			$answers[$qid] = implode(", ", $ans[$qid]);
		}

		$template->set('answers', $answers);
		$template->display();
		exit;
	}
}

function displayArticles($id){
	global $userData,$articlesdb, $mods;

//DELETE articles FROM articles LEFT JOIN moditems ON articles.id=moditems.itemid WHERE articles.moded = 'n' && moditems.id IS NULL

	if($id){
		$res = $articlesdb->prepare_query("SELECT * FROM articles WHERE id = #", $id);
		$line = $res->fetchrow();
		$line['author'] = getUserName($line['authorid']);

		$template = new template('moderate/displayArticle');

		$template->set('type', MOD_ARTICLE);
		$template->set('id', $id);


		$cats = new category( $articlesdb, "cats");
		$root = $cats->makeroot($line['category']);

		$cats = array();
		foreach($root as $category)
			$cats[] = "$category[name]";

		$template->set('categories', implode(" > ",$cats));
		$template->set('line', $line);
		$template->set('isAdmin', $mods->isAdmin($userData['userid'],'articles'));
		$template->set('text', smilies(parseHTML($line['text'])));
		$template->display();
		exit;
	}else{
		$res = $articlesdb->prepare_query("SELECT id, submittime, authorid, title, category, LENGTH(text) as length FROM articles WHERE moded = 'n' ORDER BY submittime ASC");

		$rows = array();
		$uids = array();
		while($line = $res->fetchrow()){
			$rows[] = $line;
			$uids[$line['authorid']] = $line['authorid'];
		}

		if(count($uids)){
			$usernames = getUserName($uids);

			foreach($rows as $k => $v)
				$rows[$k]['author'] = $usernames[$v['authorid']];
		}


		$displayCategories = array();
		$categories = new category( $articlesdb, "cats");

		$template = new template('moderate/displayArticles');
		$template->set('classes', array('body2','body'));
		$template->set('rows', $rows);
		$template->set('type', MOD_ARTICLE);
		$num = 1;
		foreach($rows as $line){
			$root = $categories->makeroot($line['category']);

			$cats = array();
			foreach($root as $category)
				$cats[] = "$category[name]";

			$displayCategories[$num] = implode(" > ",$cats);

			$num++;
		}
		$template->set('categories', $displayCategories);
		$template->display();

		incFooter();
		exit;
	}
}

