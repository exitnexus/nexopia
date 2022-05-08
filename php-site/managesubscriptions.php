<?

	$login=1;

	require_once("include/general.lib.php");

	$uid = getREQval('uid', 'int');

	$isAdmin = $mods->isAdmin($userData['userid'],'editprofile');
	if(empty($uid) || !$isAdmin)
		$uid = $userData['userid'];
	else
		$mods->adminlog("view subscriptions","View Subscriptions for userid: $uid");

	switch($action){
		case "Unsubscribe":
			if($deleteID = getPOSTval('deleteID', 'array')){
				$forums->db->prepare_query("UPDATE forumread SET subscribe='n' WHERE userid = ? && threadid IN (?)", $uid, $deleteID);
				$msgs->addMsg("Unsubscribed");
			}
		break;
	}

	$sortlist = array(  "threadtime" => "threadtime",
						"forumtitle" => "forumtitle",
						"threadtitle" => "threadtitle",
						"new" => "forumread.time < forumthreads.time"
						);

	$sortt = getREQval('sortt');
	$sortd = getREQval('sortt');

	isValidSortt($sortlist,$sortt);
	isValidSortd($sortd,'DESC');

	$page = getREQval('page', 'int');

	$res = $forums->db->prepare_query("SELECT DISTINCT SQL_CALC_FOUND_ROWS
						forumread.threadid,
						forumthreads.forumid,
						forumthreads.title as threadtitle,
						forums.name as forumtitle,
						forumread.time as readtime,
						forumthreads.time as threadtime,
						forumthreads.lastauthorid,
						forumthreads.authorid,
						forumthreads.locked,
						forumread.time < forumthreads.time AS new
			  FROM 		forumread,
						forumthreads,
						forums
			  WHERE 	forumread.userid = ? &&
						forumread.subscribe='y' &&
						forumread.threadid=forumthreads.id &&
						forumthreads.forumid=forums.id
			  ORDER BY $sortt $sortd LIMIT " . ($page*$config['linesPerPage']) . ", $config[linesPerPage]"
			, $uid);

	$rows = array();
	$uids = array();
	while($line = $res->fetchrow()){
		$rows[] = $line;
		$uids[$line['authorid']] = $line['authorid'];
		$uids[$line['lastauthorid']] = $line['lastauthorid'];
	}

	$numthreads = $res->totalrows();
	$numpages = ceil($numthreads / $config['linesPerPage']);

	$usernames = getUserName($uids);
	
	foreach($rows as $k => $v){
		$rows[$k]['author'] = ($v['authorid'] ? $usernames[$v['authorid']] : '');
		$rows[$k]['lastauthor'] = ($v['lastauthorid'] ? $usernames[$v['lastauthorid']] : '');
	}

	$template = new template('managesubscriptions/managesubscriptions');
	$template->set('sortHeaderThread', makeSortTableHeader("Thread Title","threadtitle"));
	$template->set('sortHeaderForum',  makeSortTableHeader("Forum Title","forumtitle"));
	$template->set('sortHeaderNew',    makeSortTableHeader("New Posts","new"));
	$template->set('sortHeaderTime',   makeSortTableHeader("Last Post","threadtime"));
	$template->set('rows', $rows);
	$template->set('uid', $uid);
	$template->set('config', $config);
	$template->set('userData', $userData);
	
	$classes = array('body','body2');
	$i=-1;
	foreach($rows as $line){
		$i++;
		if ($i > 1)
			$classes[$i] = $classes[$i%2];
	}
	
	$template->set('classes',$classes);
	$template->set('pageList', pageList("$_SERVER[PHP_SELF]?sortt=$sortt&sortd=$sortd",$page,$numpages,'header'));
	
	$template->display();
