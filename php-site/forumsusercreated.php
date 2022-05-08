<?

	$login=0;

	require_once("include/general.lib.php");

	$default = 0;
	if($userData['loggedIn'])
		$default = 1;

	$forumdata = array();
	$forumids = array();
	$forumtypes = array('invite' => array(),
						'latest' => array(),
						'most' => array() 	);

//invite
	if($userData['loggedIn']){
		$res = $forums->db->prepare_query("SELECT forums.*,$default as new FROM forums,foruminvite WHERE forums.id=foruminvite.forumid && foruminvite.userid = ?", $userData['userid']);

		while($line = $res->fetchrow()){
			$forumdata[$line['id']] = $line;
			$forumids[$line['id']] = $line['id'];
			$forumtypes['invite'][$line['name']] = $line['id'];
		}
		uksort($forumtypes['invite'],'strcasecmp');
	}

//latest
	$res = $forums->db->prepare_query("SELECT forums.*,$default as new FROM forums USE KEY (time) WHERE official='n' && public='y' && time >= ? ORDER BY time DESC LIMIT 10", time() - 10800);

	while($line = $res->fetchrow()){
		$forumdata[$line['id']] = $line;
		$forumids[$line['id']] = $line['id'];
		$forumtypes['latest'][] = $line['id'];
	}

//most
	$res = $forums->db->query("SELECT forums.*,$default as new FROM forums WHERE official='n' && public='y' ORDER BY posts DESC LIMIT 10");

	while($line = $res->fetchrow()){
		$forumdata[$line['id']] = $line;
		$forumids[$line['id']] = $line['id'];
		$forumtypes['most'][] = $line['id'];
	}

	if($userData['loggedIn']){
		$res = $forums->db->prepare_query("SELECT forumid,time FROM forumupdated WHERE userid = ? && forumid IN (?)", $userData['userid'], $forumids);

		while($line = $res->fetchrow())
			$forumdata[$line['forumid']]['new'] = (int)($line['time'] < $forumdata[$line['forumid']]['time']);
	}

	
	$showInvite = false;
	$showLatest = false;
	$showMost = false;
	
	if(count($forumtypes['invite']) > 0){
		$showInvite = true;
	}

	if(count($forumtypes['latest']) > 0){
		$showLatest = true;
	}

	if(count($forumtypes['most']) > 0){
		$showMost = true;
	}
	
	$template = new template('forums/forumsusercreated');
	$template->set('numonline', $forums->forumsNumOnline());
	$template->set('showInvite', $showInvite);
	$template->set('showLatest', $showLatest);
	$template->set('showMost', $showMost);
	$template->set('userdata', $userdata);
	$template->set('forumdata', $forumdata);
	$template->set('forumtypes', $forumtypes);
	$template->display();

