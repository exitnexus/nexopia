<?

	$login=1;

	require_once("include/general.lib.php");

	if(!($fid = getREQval('fid', 'int')))
		die("Bad Forum id");

	$perms = $forums->getForumPerms($fid);	//checks it's a forum, not a realm

	if(!$perms['invite'])
		die("You don't have permission to mute people in this forum");

	$forumdata = $perms['cols'];

//	$res = $forums->db->prepare_query("SELECT name, official, ownerid FROM forums WHERE id = #", $fid);
//	$forumdata = $res->fetchrow();


	if($forumdata['official'] == 'y' && $forumdata['public'] == 'y')
		die("You can't invite to official public forums");

	switch($action){
		case "Invite":
			if(!($username = getPOSTval('username')))
				break;

			if(empty($username) || trim($username) == "")
				break;

			$uid = getUserId($username);

			if($uid == $username)
				break;

			$forums->invite($uid, $fid);
/*

			$forums->db->prepare_query("INSERT IGNORE INTO foruminvite SET userid = ?, forumid = ?", $uid, $fid);

			if($forums->db->affectedrows() == 0)
				break;

			$forums->modLog('invite',$fid,0,$uid);

			$messaging->deliverMsg($uid,"Forum Invite","You have been invited to join the forum [url=forumthreads.php?fid=$fid]" . $forumdata['name'] . "[/url]. Click [url=forumthreads.php?fid=$fid&action=withdraw]here[/url] to withdraw from the forum.");

			$cache->put("foruminvite-$uid-$fid", 1, 10800);
*/

			$msgs->addMsg("User Invited");

			break;
		case "delete":
		case "Uninvite":

			if(!($deleteID = getPOSTval('deleteID', 'array')))
				break;

			$forums->unInvite($deleteID, $fid);

/*
			$forums->db->prepare_query("DELETE FROM foruminvite WHERE userid IN (#) && forumid = #", $deleteID, $fid);

			foreach($deleteID as $uid){
				$forums->modLog('uninvite',$fid,0,$uid);
				$cache->put("foruminvite-$uid-$fid", 0, 10800);
			}
*/

			$msgs->addMsg("User(s) Uninvited");
			break;
	}


	$res = $forums->db->prepare_query("SELECT userid FROM foruminvite WHERE forumid = #", $fid);

	$uids = array();
	while($line = $res->fetchrow())
		$uids[] = $line['userid'];

	$rows = array();

	if($uids)
		$rows = getUserName($uids);

	natcasesort($rows);

	$template = new template('forums/foruminvite');
	$template->set('forumdata', $forumdata);
	$template->set('rows', $rows);
	$template->set('forumTrail', $forums->getForumTrail($forumdata, "body"));
	$template->set('fid', $fid);
	$template->display();
