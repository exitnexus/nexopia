<?

	$login=1;

	require_once("include/general.lib.php");

	$mod = $mods->isAdmin($userData['userid'],"forums");

	$res = $forums->db->prepare_query("SELECT forummods.userid, forums.name as forumname, forummods.forumid, forummods.activetime FROM forummods LEFT JOIN forums ON forummods.forumid=forums.id WHERE (forums.official='y' || forummods.forumid = 0)");

	$users = array();
	$forummods = array();
	while($line = $res->fetchrow()){
		$forummods[] = $line;
		if($line['userid'] == $userData['userid'])
			$mod = true;
		$users[$line['userid']] = $line['userid'];
	}

	if(!$mod)
		die("You don't have permission to see this");

	$users = getUserInfo($users);

/*
	if(empty($sortn))
		$sortn = "";

	$sortd = SORT_ASC;
	$sortt = SORT_CASESTR;
	switch($sortn){
		case 'forumname':
		case 'username':
			break;
		case 'activetime':
			$sortt = SORT_NUMERIC;
			$sortd = SORT_DESC;
			break;
		case 'online':
			$sortt = SORT_STRING;
			$sortd = SORT_DESC;
			break;
		default:
			$sortn = 'forumname';
			break;
	}
*/
	sortCols($forummods, SORT_ASC, SORT_CASESTR, 'forumname');//, SORT_ASC, SORT_CASESTR, 'username', $sortd, $sortt, $sortn);

	$template = new template('forums/forummodlist');
	$template->set('forummods', $forummods);
	$template->set('users', $users);
	$template->display();

