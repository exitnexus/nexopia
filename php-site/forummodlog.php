<?

	$login=1;

	require_once("include/general.lib.php");

	if(!($fid = getREQval('fid', 'int')))
		die("Bad Forum id");

	if($fid){
		$perms = $forums->getForumPerms($fid);	//checks it's a forum, not a realm

		if(!$perms['modlog'])
			die("You don't have permission to mute people in this forum");

		$forumdata = $perms['cols'];
	}else{ //fid = 0
		if(!$mods->isAdmin($userData['userid'],'forums')){
			$res = $forums->db->prepare_query("SELECT modlog FROM forummods WHERE userid = ? && forumid = 0", $userData['userid']);
			$mod = $res->fetchrow();

			if(!$mod || $mod['modlog'] == 'n')
				die("You don't have permission to see the global mod log");
		}

		$forumdata = array("name" => "Global", 'official' => 'y');
	}


	$page = getREQval('page', 'int');
	$search = getREQval('search');

	if($search && ($searchuid = getUserId($search)))
		$res = $forums->db->prepare_query("SELECT SQL_CALC_FOUND_ROWS * FROM forummodlog WHERE forumid = # && userid = # ORDER BY time DESC LIMIT #, #", $fid, $searchuid, $page*$config['linesPerPage'], $config['linesPerPage']);
	else
		$res = $forums->db->prepare_query("SELECT SQL_CALC_FOUND_ROWS * FROM forummodlog WHERE forumid = # ORDER BY time DESC LIMIT #, #", $fid, $page*$config['linesPerPage'], $config['linesPerPage']);

	$rows = array();
	while($line = $res->fetchrow())
		$rows[] = $line;

	$numrows = $res->totalrows();
	$numpages =  ceil($numrows / $config['linesPerPage']);


	$threadids = array();
	$forumids = array();
	$userids = array();
	
	foreach($rows as $line){
		$userids[$line['userid']] = $line['userid'];
		if($line['threadid'])
			$threadids[$line['threadid']] = $line['threadid'];

		switch($line['action']){
			case "lock":
			case "unlock":
			case "stick":
			case "unstick":
			case "announce":
			case "unannounce":
			case "flag":
			case "unflag":
			case "deletethread":
				$userids[$line['var1']] = $line['var1'];
				$userids[$line['var2']] = $line['var2'];
				break;

			case "deletepost":
				$userids[$line['var2']] = $line['var2'];
				break;

			case "move":
				$forumids[$line['var1']] = $line['var1'];
				$forumids[$line['var2']] = $line['var2'];
				break;

			case "mute":
			case "unmute":
			case "editpost":
			case "addmod":
			case "editmod":
			case "removemod":
			case "invite":
			case "uninvite":
				$userids[$line['var1']] = $line['var1'];
				break;
		}
	}

	$userNames = getUserName($userids);
	$missingids = array_diff($userids, array_keys($userNames));
	foreach ($missingids as $id) {
		$userNames[$id] = "";
	}
	
	$threadnames = array();
	$threadDeleted = array();

	if(count($threadids)){
		$res = $forums->db->prepare_query("SELECT id, title FROM forumthreads WHERE id IN (?)", $threadids);
		
		while($line = $res->fetchrow()){
			$threadnames[$line['id']] = $line['title'];
			$threadDeleted[$line['id']] = false;
			unset($threadids[$line['id']]);
		}
		
		if(count($threadids)){
			$res = $forums->db->prepare_query("SELECT id, title FROM forumthreadsdel WHERE id IN (?)", $threadids);
			
			while($line = $res->fetchrow()){
				$threadnames[$line['id']] = $line['title'];
				$threadDeleted[$line['id']] = true;
			}
		}
	}
	

	$forumnames = array();
	
	if(count($forumids)){
		$res = $forums->db->prepare_query("SELECT id, name FROM forums WHERE id IN (#)", $forumids);
		
		while($line = $res->fetchrow())
			$forumnames[$line['id']] = $line['name'];
	}

	$template = new template('forums/forummodlog');
	$template->set('pageList', pageList("$_SERVER[PHP_SELF]?fid=$fid&search=" . urlencode($search),$page,$numpages,'header'));
	$template->set('forumdata', $forumdata);
	$template->set('fid', $fid);
	$template->set('userNames', $userNames);
	$template->set('rows', $rows);
	$template->set('threadDeleted', $threadDeleted);
	$template->set('threadnames', $threadnames);
	$template->set('forumnames', $forumnames);
	$template->set('search', $search);
	$template->display();
	
