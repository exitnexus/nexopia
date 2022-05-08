<?

	$login=2;

	require_once("include/general.lib.php");

	$isAdmin = $mods->isAdmin($userData['userid'],'viewrecentvisitors');

	if(!$isAdmin || !($uid = getREQval('uid', 'int')))
		$uid = $userData['userid'];
	else
		$mods->adminlog("recent visitors","View Recent Visitors to user: $uid");

	$user = getUserInfo($uid);

	$page = getREQval('page', 'int');

	$mode = getREQval('mode', 'int', 1);
	if($mode != 2 || !$userData['loggedIn'] || $uid!=$userData['userid'])
		$mode=1;


	$showThumbs = false;
	$showAnon = true;
	if($userData['loggedIn']){

		if($userData['recentvisitlistthumbs'] == 'y'){
			$showThumbs = true;
		}
		if($userData['recentvisitlistanon'] == 'n'){
			$showAnon = false;
		}
	}
	$anonQuery = 'anonymous = 0';
	if ($isAdmin || ($showAnon && $mode != 2))
		$anonQuery = 'anonymous IN (0, 1)';

//split, load balanced solution
	if($mode == 2)
		$res = $usersdb->prepare_query("SELECT userid as viewuserid, time, anonymous FROM profileviews WHERE viewuserid = # ORDER BY time DESC LIMIT #, #", $uid, ($page*$config['linesPerPage']), $config['linesPerPage']); // deliberately all servers
	else
		$res = $usersdb->prepare_query("SELECT SQL_CALC_FOUND_ROWS viewuserid, time, anonymous FROM profileviews WHERE userid = % AND $anonQuery ORDER BY time DESC LIMIT #, #", $uid, ($page*$config['linesPerPage']), $config['linesPerPage']);

	$users = array();
	while($line = $res->fetchrow()){
		$users[$line['viewuserid']] = $line;
	}
	sortCols($users, SORT_DESC, SORT_NUMERIC, 'time');

	$numrows = $res->totalrows();
	if ($mode == 1)
		$numpages = ceil($numrows / $config['linesPerPage']);
	else
		$numpages = 1;

	$isfriendof = array();

	if(count($users)){

		$userinfo = getUserInfo(array_keys($users));

		foreach($userinfo as $line){
			$users[$line['userid']] += $line;
			$users[$line['userid']]['mytime'] = 0;
			$users[$line['userid']]['deleted'] = false;
		}

		$missing = array_diff(array_keys($users), array_keys($userinfo));
		$usernames = getUserName($missing);
		foreach ($usernames as $userid => $username){
			$users[$userid]['mytime'] = 0;
			$users[$userid]['deleted'] = true;

			$users[$userid] += array(
				'username' => $username,
				'age' => '&nbsp;',
				'sex' => '&nbsp;',
				'online' => false,
				'firstpic' => 0,
			);

		}

		if($mode == 1)
			$res = $usersdb->prepare_query("SELECT userid, time, anonymous FROM profileviews WHERE viewuserid = # && userid IN (%)", $uid, array_keys($users));
		else
			$res = $usersdb->prepare_query("SELECT viewuserid as userid, time, anonymous FROM profileviews WHERE userid = % && viewuserid IN (#) AND $anonQuery", $uid, array_keys($users));

		while($line = $res->fetchrow()){
			$users[$line['userid']]['mytime'] = $line['time'];
		}
	}


	if($userData['loggedIn'] && $uid==$userData['userid']){
		$showHeader = true;
	}

	$locations = new category( $configdb, "locs");

	$time = time();

	$classes = array('body','body2');
	$i=-1;

	$showUser = array();
	$location = array();
	$isOnline = array();
	$thumbnails = array();

	$lines = array();
	foreach($users as $user){
		$line = $user;
		$line['showUser'] = true;
		if ($user['anonymous'] && !$isAdmin && $mode == 1)
		{
			$line['showUser'] = false;
			$lines[] = $line;
			continue;
		}

		if (!$line['deleted'])
			$line['location'] = $locations->getCatName($user['loc']);
		else
			$line['location'] = '&nbsp;';

		$line['isOnline'] = ($user['online'] == 'y'? 'Online' : '');

		if($showThumbs)
		{
			if($line['firstpic'] == 0)
				$line['thumbnail'] = 0;
			else
				$line['thumbnail'] = $config['thumbloc'] . floor($line['userid']/1000) . "/" . weirdmap($line['userid']) . "/{$line['firstpic']}.jpg";
		}
		$lines[] = $line;
	}


	$template = new template('profiles/profileviews');
	$template->set('users', $users);
	$template->set('mode', $mode);
	$template->set('pageList', pageList("$_SERVER[PHP_SELF]?uid=$uid",$page,$numpages,'header'));
	$template->set('showHeader', $showHeader);
	$template->set('uid', $uid);
	$template->set('userData', $userData);
	$template->set('numrows', $numrows);
	$template->set('showthumbs',$showThumbs);
	$template->set('lines', $lines);
	$template->display();
