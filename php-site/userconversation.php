<?

	$login = 1;

	require_once("include/general.lib.php");

	$uid1 = getREQval('uid1', 'int');
	$uid2 = getREQval('uid2', 'int');

	$page = getREQval('page', 'int');

	$data = getUserInfo(array($uid1, $uid2));


	$isAdmin = $mods->isAdmin($userData['userid'], 'listusers');


//a user is deleted or frozen
	if(!$data || !isset($data[$uid1]) || !isset($data[$uid2]) || (!$isAdmin && ($data[$uid1]['state'] == 'frozen' || $data[$uid2]['state'] == 'frozen')))
		die("Bad user");

	if(!$isAdmin && ($data[$uid1]['enablecomments'] == 'n' || $data[$uid2]['enablecomments'] == 'n')){
		header("location: /profile.php?uid=$uid1");
		exit;
	}

	$data[$uid1]['plus'] = $data[$uid1]['premiumexpiry'] > time();
	$data[$uid2]['plus'] = $data[$uid2]['premiumexpiry'] > time();

	if( ($data[$uid1]['hideprofile'] == 'y' && isIgnored($uid1, $userData['userid'], false, 0, true)) ||
		($data[$uid2]['hideprofile'] == 'y' && isIgnored($uid2, $userData['userid'], false, 0, true))){

		incHeader();

		echo "One of these users is ignoring you.";

		incFooter();
		exit;
	}

	listComments($uid1, $uid2, $page);
///////////////////////////////


function listComments($uid1, $uid2, $page){
	global $usercomments, $userData, $data, $config, $isAdmin, $cache, $weblog;


	$comments = array();

	$res = $usercomments->db->prepare_query("SELECT userid, id, authorid, time, nmsg FROM usercomments WHERE userid = % && authorid = #", $uid1, $uid2);

	while($line = $res->fetchrow())
		$comments[] = $line;

	$res = $usercomments->db->prepare_query("SELECT userid, id, authorid, time, nmsg FROM usercomments WHERE userid = % && authorid = #", $uid2, $uid1);

	while($line = $res->fetchrow())
		$comments[] = $line;


	$numrows = count($comments);
	$numpages =  ceil($numrows / $config['linesPerPage']);

	sortCols($comments, SORT_NUMERIC, SORT_DESC, 'time');
	$comments = array_slice($comments, $page*$config['linesPerPage'], $config['linesPerPage']);

	$commentuid = 0;
	if($userData['loggedIn']){
		if($userData['userid'] == $uid1){
			$commentuid = $uid2;
		}elseif($userData['userid'] == $uid2){
			$commentuid = $uid1;
		}
	}

	if(count($comments) != 0){
		$keys = array();
		foreach($comments as &$line){
			$author = $data[$line['authorid']];

			if($author['state'] == 'active'){
				$line['userstate'] = 'active';
			}elseif($author['state'] == 'frozen'){
				$line['userstate'] = ($isAdmin ? 'frozen' : 'deleted');
			}else{
				$line['userstate'] = 'deleted';
			}

			$line['author'] = $author['username'];

			if($line['userstate'] == 'active' || $line['userstate'] == 'frozen'){
				$line['author_is_online'] = ($author['online'] == 'y');

				if($author['firstpic'])
					$line['author_first_pic'] = $config['thumbloc'] . floor($author['userid']/1000) . "/" . weirdmap($author['userid']) . "/$author[firstpic].jpg";
				else
					$line['author_first_pic'] = "";

				$line['author_age'] = $author['age'];
				$line['author_sex'] = $author['sex'];
			}

			if($userData['loggedIn'] && $line['authorid']){
				$line['user_is_logged_in']= true;
				$line['author_is_user'] = ($userData['userid'] == $uid1);
				if($userData['userid'] == $uid1){
					if(!isset($keys[$line['authorid']]))
						$keys[$line['authorid']] = makeKey($line['authorid']);
					$line['author_key'] = $keys[$line['authorid']];
				}
			} else {
				$line['user_is_logged_in'] = false;
				$line['author_is_user'] = false;
			}
		}
	}

	$template =  new template("usercomments/conversation");
	$template->set("skin", injectSkin($data[$uid1], 'comment'));
	$template->set("profilehead", incProfileHead($data[$uid1]));

	$template->set("uid", $uid1);
	$template->set("username", $data[$uid1]['username']);
	$template->set("pagelist",pageList("$_SERVER[PHP_SELF]?uid1=$uid1&uid2=$uid2",$page,$numpages,'header'));

	$template->set("comments", $comments);

	$template->set("commentuid", $commentuid);
	$template->set("editbox", editBoxStr(""));

	$template->display();
	exit;
}
