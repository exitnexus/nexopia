<?

	$login=1;

	require_once("include/general.lib.php");
	$uid = getREQval('id', 'int', ($userData['loggedIn'] ? $userData['userid'] : die("Bad User")));
	
	$isAdmin = false;
	if($userData['loggedIn']){
		if($userData['userid'] == $uid)
			$isAdmin = 1;
		else
			$isAdmin = ($mods->isAdmin($userData['userid'], 'deletecomments') ? 2 : false);
	}

	$data = getUserInfo($uid);
	if(!$userData['loggedIn'] || $userData['userid'] != $uid){

		if(!$data || ($data['state'] == 'frozen' && !$mods->isAdmin($userData['userid'], 'listusers')))
			die("Bad user");
	}

	if($data['enablecomments']=='n' && $isAdmin != 2){
		header("location: /users/".urlencode(getUserName($uid)));
		exit;
	}

	$data['plus'] = $data['premiumexpiry'] > time();

	if($data['hideprofile'] == 'y' && isIgnored($uid, $userData['userid'], false, 0, true)){
		incHeader();

		echo "This user is ignoring you.";

		incFooter();
		exit;
	}

	if($userData['loggedIn']){
		switch($action){
			case "Delete":
				if($isAdmin && ($checkID = getPOSTval('checkID', 'array'))){
					$usercomments->delete($uid, $checkID);

					$google->updateHash($uid);

//					$cache->remove("comment-$insertid");
//					$cache->remove("commentids-$uid");
				}
				break;

			case "Preview":
				$msg = getPOSTval('msg');

				addUserComment($uid, $msg, true);

			case "Post":

				$limit = $cache->get("commentsratelimit-$userData[userid]");

				if($limit){
					$cache->put("commentsratelimit-$userData[userid]", 1, 15); //block for another 15 seconds
					$msgs->addMsg("You can only send one comment per second");
					break;
				}


				$msg = getPOSTval('msg');

				if(!empty($msg)){
					if($ignoreid = isIgnored($uid, $userData['userid'], 'comments', $userData['age'])){
						if($ignoreid == 1)
							$msgs->addMsg("This user only accepts comments from friends.");
						else
							$msgs->addMsg("This user is ignoring you.");
					}else{
						if(!$usercomments->postUserComment($uid, $msg, $userData['userid']))
							addUserComment($uid, $msg, true);
						else
							scan_string_for_notables($msg);
						$google->updateHash($uid);
					}

					$cache->put("commentsratelimit-$userData[userid]", 1, 5); //block for 5 seconds
				}
		}

		if($userData['userid']==$uid && $userData['newcomments']>0){
			$usersdb->prepare_query("UPDATE users SET newcomments = 0 WHERE userid = %", $userData['userid']);
			$userData['newcomments']=0;
//			$cache->put("newcomments-$userData[userid]", 0, $config['maxAwayTime']);
		}
	}

	$page = getREQval('page', 'int');

	listComments($page);
///////////////////////////////


function listComments($page){
	global $usercomments, $userData, $data, $uid, $config, $isAdmin, $cache, $weblog, $wwwdomain;
	
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: http://". $wwwdomain . "/users/". urlencode($data["username"]) . "/comments");
	exit;

	$template =  new template("usercomments/listcomments");
	$comments = array();
	$authorids = array();

	$res = $usercomments->db->prepare_query("SELECT SQL_CALC_FOUND_ROWS id, authorid, time, nmsg FROM usercomments WHERE userid = % ORDER BY time DESC LIMIT #, #", $uid, $page*$config['linesPerPage'], $config['linesPerPage']);

	while($line = $res->fetchrow()){
		$comments[$line['id']] = $line;
		$authorids[$line['authorid']] = $line['authorid'];
	}

	$numrows = $res->totalrows();
	$numpages =  ceil($numrows / $config['linesPerPage']);

	$authors = array();
	$authornames = array();
	if(count($authorids))
	{
		$authornames = getUserName($authorids);
		$authors = getUserInfo($authorids);
		foreach($authorids as $id)
			if(!isset($authors[$id]))
				$authors[$id] = array('state' => 'deleted', 'userid' => $id, 'username' => $authornames[$id]);
	}

	$template->set("skin", injectSkin($data, 'comment'));

	$isFriend = $userData['loggedIn'] && ($userData['userid']==$uid || isFriend($userData['userid'], $uid));


	$template->set("uid", $uid);
	$template->set("profilehead", incProfileHead($data));
	$template->set("username", $data['username']);
	$template->set("pagelist",pageList("$_SERVER[PHP_SELF]?id=$uid",$page,$numpages,'header'));
	$template->set("no_comments", count($comments) == 0);
	$template->set("userData_uid", $userData['userid']);
	$template->set("isAdmin", $isAdmin);
	if(count($comments) != 0){
		$keys = array();
		foreach($comments as &$line){
			$author = $authors[$line['authorid']];

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
				$line['author_is_user'] = ($userData['userid'] == $uid);
				if($userData['userid'] == $uid){
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
	$template->set("comments", $comments);
	$template->set('loggedin', $userData['loggedIn']);
	$template->set('only_friends_comments', !$isAdmin && ($data['onlyfriends'] == 'both' || $data['onlyfriends'] == 'comments') && !$isFriend);
	$template->set('only_same_age', !$isAdmin && ($data['ignorebyage'] == 'both' || $data['ignorebyage'] == 'comments') && $userData['age'] && ($userData['age'] < $data['defaultminage'] || $userData['age'] > $data['defaultmaxage']) && !$isFriend);
	$template->set('is_ignored', !$isAdmin && isIgnored($uid,$userData['userid'],'comments', $userData['age'], true));
	$template->set('editbox', editBoxStr(""));

	$template->display();
	exit;
}

function addUserComment($uid, $msg, $preview){
	$template = new Template("usercomments/addusercomment");
	$template->set("preview", $preview);

	if($preview){
		$msg = trim($msg);

		$nmsg = cleanHTML($msg);
		$nmsg2 = parseHTML($nmsg);
		$nmsg3 = smilies($nmsg2);
		$nmsg3 = wrap($nmsg3);

		$template->set("msg", $nmsg3);
	}

	$template->set("uid", $uid);
	$template->set("editbox", editBoxStr($msg) );
	$template->display();
	exit;
}

