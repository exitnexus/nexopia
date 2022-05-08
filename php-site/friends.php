<?

	$login=0;

	require_once("include/general.lib.php");
	$sortlist = array(  'friends.id' => "",
						'users.userid' => "",
						'username' => "users.username",
						'age' => "age",
						'sex' => "sex",
						'loc' => "loc",
						'online' => "online",
						'firstpic' => "",
						'friendscomments.comment' => ""
						);

	$uid = getREQval('uid', 'int', ($userData['loggedIn'] ? $userData['userid'] : 0));

	if(empty($uid))
		$auth->loginRedirect();

	$mode = getREQval('mode', 'int', 1);
	if($mode != 2 || !$userData['loggedIn'] || $uid!=$userData['userid'])
		$mode=1;

	$multiplyer = 1;
	if($userData['loggedIn'] && $uid == $userData['userid'] && $userData['premium'])
		$multiplyer = 2;


	if($userData['loggedIn'] && $userData['userid']==$uid){// && $mode==1){
		switch($action){
			case "add":
				if(!($id = getREQval('id', 'int')) || $mode == 2)
					break;

				if(!checkKey($id, getREQval('k')))
					break;

				$uid = $userData['userid'];

				$res = $usersdb->prepare_query("SELECT count(*) FROM friends WHERE userid = %", $userData['userid']);
				$count = $res->fetchfield();

				if($count >= $config['maxfriends'] * $multiplyer ){
					$msgs->addMsg("You have the reached the maximum amount of friends allowed, which is currently set at $config[maxfriends] or " . ($config['maxfriends']*2) . " for plus users.");
					break;
				}

				$line = getUserInfo($id);

				if(!$line){
					$msgs->addMsg("That user does not exist");
					break;
				}

				if(isIgnored($id, $userData['userid'], '', 0, true)){
					$msgs->addMsg("You may not add that user to your friends list.");
					break;
				}

				$usersdb->prepare_query("INSERT IGNORE INTO friends SET userid = %, friendid = #", $userData['userid'], $id);

				if($usersdb->affectedrows() == 0){
					$msgs->addMsg("He/She is already on your friends list");
				}else{
					$msgs->addMsg("Friend has been added to your friends list.");

					if($line['premiumexpiry'] > time() && $line['friendsauthorization'] == 'y')
						$messaging->deliverMsg($id, "Friends List Notification", "[user]$userData[username]" . "[/user] has added you to " . ($userData['sex'] == 'Male' ? "his" : "her") . " friends list. You may remove yourself by clicking [url=/friends.php?action=delete&mode=2&id=$userData[userid]&k=" . makekey($userData['userid'], $id) . "]here[/url], or add " . ($userData['sex'] == 'Male' ? "him" : "her") . " to yours by clicking [url=/friends.php?action=add&id=$userData[userid]&k=" . makekey($userData['userid'], $id) . "]here[/url].", 0, false, false, false);
				}

				$cache->remove("friendids" . USER_FRIENDS . "-$userData[userid]");
				$cache->remove("friendids" . USER_FRIENDOF . "-$id");

				$cache->remove("friendsonline-$userData[userid]");

				break;

			case "delete":
				if(($id = getREQval('id', 'int')) && checkKey($id, getREQval('k'))){
					if($mode == 1){
						$usersdb->prepare_query("DELETE FROM friends WHERE userid = % && friendid = #",$userData['userid'], $id);

						if($usersdb->affectedrows()){
							$line = getUserInfo($id);

							if($line['premiumexpiry'] > time() && $line['friendsauthorization'] == 'y')
								$messaging->deliverMsg($id, "Friends List Notification", "[user]$userData[username]" . "[/user] has removed you from " . ($userData['sex'] == 'Male' ? "his" : "her") . " friends list. You may remove " . ($userData['sex'] == 'Male' ? "him" : "her") . " from yours by clicking [url=/friends.php?action=delete&id=$userData[userid]&k=" . makekey($userData['userid'], $id) . "]Here[/url]", 0, false, false, false);
						}

						$cache->remove("friendids" . USER_FRIENDS . "-$userData[userid]");
						$cache->remove("friendids" . USER_FRIENDOF . "-$id");
						$cache->remove("friendsonline-$userData[userid]");
					}elseif($userData['premium']){
						$usersdb->prepare_query("DELETE FROM friends WHERE userid = % && friendid = #", $id, $userData['userid']);

						if($usersdb->affectedrows()){
							$line = getUserInfo($id);

							if($line['premiumexpiry'] > time() && $line['friendsauthorization'] == 'y')
								$messaging->deliverMsg($id, "Friends List Notification", "[user]$userData[username]" . "[/user] has removed " . ($userData['sex'] == 'Male' ? "himself" : "herself") . " from your friends list.", 0, false, false, false);
						}

						$cache->remove("friendids" . USER_FRIENDS . "-$id");
						$cache->remove("friendids" . USER_FRIENDOF . "-$userData[userid]");
						$cache->remove("friendsonline-$id");
					}

					$msgs->addMsg("Friend Deleted");
				}
				break;
			case "update":
				if($mode==1 && ($id = getREQval('id', 'int')) && isset($_REQUEST['comment']) && checkKey($id, getREQval('k'))){
					$comment = getREQval('comment');

					if($comment==""){
						$usersdb->prepare_query("DELETE FROM friendscomments WHERE userid = % && friendid = #", $userData['userid'], $id);
					}else{
						$usersdb->prepare_query("UPDATE friendscomments SET comment = ? WHERE userid = % && friendid = #", removeHTML($comment), $userData['userid'], $id);
						if($usersdb->affectedrows()==0)
							$usersdb->prepare_query("INSERT IGNORE INTO friendscomments SET comment = ?, userid = %, friendid = #", removeHTML($comment), $userData['userid'], $id);
					}
					$msgs->addMsg("Comment updated");
				}
				break;
		}
	}

	$user = getUserInfo($uid);
	if(!$userData['loggedIn'] || $uid != $userData['userid']){

		if(!$user || ($user['state'] == 'frozen' && !$mods->isAdmin($userData['userid'], 'listusers')))
			die("Bad user");
	}

	$user['plus'] = $user['premiumexpiry'] > time();

	if($user['plus'] && $user['hideprofile'] == 'y' && isIgnored($uid, $userData['userid'], false, 0, true)){
		incHeader();

		echo "This user is ignoring you.";

		incFooter();
		exit;
	}

	$friendids = getMutualFriendsList($uid, $mode); //grabs the userids


	$users = array();
	$comments = array();
	if($friendids){
		$users = getUserInfo(array_keys($friendids));


		if($mode == USER_FRIENDS){
			$res = $usersdb->prepare_query("SELECT friendid as id, comment FROM friendscomments WHERE userid = %", $uid);
		}else{
			$res = $usersdb->prepare_query("SELECT userid as id, comment FROM friendscomments WHERE friendid = #", $uid); //all servers
		}

		$comments = $res->fetchfields('id');
	}


	$rows = array();
	foreach($friendids as $id => $mutual)
		if ($users[$id])
			$rows[$id] = $users[$id] + array('mutual' => $mutual, 'comment' => (isset($comments[$id]) ? $comments[$id] : ''));


	$missing = array_diff(array_keys($rows), array_keys($users));
	foreach ($missing as $id)
	{
		$key = isset($rows[$id]['userid'])? 'userid' : 'id';
		$query = $usersdb->prepare_query("DELETE FROM friends WHERE userid = % AND friendid = #", $rows[$id][$key], $rows[$id]['friendid']);
		$cache->remove("friendids-" . $rows[$id][$key]);
		unset($rows[$id]);
	}

	sortCols($rows, SORT_ASC, SORT_CASESTR, 'username', SORT_DESC, SORT_STRING, 'online');

	$locations = new category( $configdb, "locs");

	if($userData['loggedIn'])	$minage = $userData['defaultminage'];
	else						$minage = 14;

	if($userData['loggedIn'])	$maxage = $userData['defaultmaxage'];
	else						$maxage = 30;

	if($userData['loggedIn']){
		$sexes = $userData['defaultsex'];
		if($sexes == 'Male') 	$sex = 'm';
		else					$sex = 'f';
	}else{
		$sexes = array("Male","Female");
		$sex = 'b';
	}

    ob_start();
	injectSkin($user, 'friend');
    $injectedSkin = ob_get_contents();
    ob_end_clean();

	$cols=6;
	$showThumbs = false;
	if($userData['loggedIn']){
		if($userData['userid']==$uid)
			$cols++;
		if($userData['friendslistthumbs'] == 'y'){
			$cols++;
			$showThumbs = true;
		}
	}



	$isFriend = $userData['loggedIn'] && ($userData['userid']==$uid || isFriend($userData['userid'],$uid));

	$cols2=2;
	if($user['enablecomments']=='y')
		$cols2++;
	$userblog = new userblog($weblog, $uid);
	if ($userblog->isVisible($userData['loggedIn'], $isFriend))
		$cols2++;
	if($user['gallery']=='anyone' || ($user['gallery']=='loggedin' && $userData['loggedIn']) || ($user['gallery']=='friends' && $isFriend))
		$cols2++;

	$width = 100.0/$cols2;

	$enabledComments = $user['enablecomments'] == 'y';
	$enabledGallery = $user['gallery']=='anyone' || ($user['gallery']=='loggedin' && $userData['loggedIn']) || ($user['gallery']=='friends' && $isFriend);
	$enabledBlog = $userblog->isVisible($userData['loggedIn'], $isFriend);

	$whosFriends = ($userData['loggedIn'] && $uid==$userData['userid']) ? 'ownFriends' : 'othersFriends';

	$friendsList = array();
	foreach($rows as $line){
		if($line['state'] == 'frozen')
			continue;

		$line['imagePath'] = $config['thumbloc'] . floor($line['userid']/1000) . "/" . weirdmap($line['userid']) . "/{$line['firstpic']}.jpg";
		$line['userLocation'] = $locations->getCatName($line['loc']);
		$line['userKey'] = makekey($line['userid']);
		$line['javascriptComment'] = (strpos($line['comment'],"'")===false && strpos($line['comment'],'"')===false && strpos($line['comment'],'\\')===false ? $line['comment'] : "");
		$line['canDeleteFriend'] = ($mode==1 || ($userData['loggedIn'] && $userData['premium']));
		$friendsList[] = $line;
	}

	$friendsCount = count($rows);
	$maxFriends = $config['maxfriends'] * $multiplyer;

	$template = new template('friends/index');
	$template->setMultiple(array(
		'injectedSkin'		=> $injectedSkin,
		'cols'				=> $cols,
		'width'				=> $width,
		'uid'				=> $uid,
		'enabledComments'	=> $enabledComments,
		'enabledGallery'	=> $enabledGallery,
		'enabledBlog'		=> $enabledBlog,
		'whosFriends'		=> $whosFriends,
		'mode'				=> $mode,
		'user'				=> $user,
		'showThumbs'		=> $showThumbs,
		'config'			=> $config,
		'friendsList'		=> $friendsList,
		'friendsCount'		=> $friendsCount,
		'maxFriends'		=> $maxFriends
	));
	$template->display();

