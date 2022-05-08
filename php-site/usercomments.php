<?

	$login=1;

	require_once("include/general.lib.php");
	$uid = getREQval('id', 'int', ($userData['loggedIn'] ? $userData['userid'] : die("Bad User")));

	$isAdmin = false;
	if($userData['loggedIn']){
		if($userData['userid'] == $uid)
			$isAdmin = 1;
		else
			$isAdmin = $mods->isAdmin($userData['userid'], 'deletecomments');
	}

	$data = getUserInfo($uid);
	if(!$userData['loggedIn'] || $userData['userid'] != $uid){

		if(!$data || ($data['state'] == 'frozen' && !$mods->isAdmin($userData['userid'], 'listusers')))
			die("Bad user");
	}

	if($data['enablecomments']=='n'){
		header("location: /profile.php?uid=$uid");
		exit;
	}

	$data['plus'] = $data['premiumexpiry'] > time();

	if($data['plus'] && $data['hideprofile'] == 'y' && isIgnored($uid, $userData['userid'], false, 0, true)){
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

//					$cache->remove("comment-$insertid");
//					$cache->remove("commentids-$uid");
				}
				break;

			case "Preview":
				$msg = getPOSTval('msg');

				$parse_bbcode = getPOSTval('parse_bbcode');

				addUserComment($uid, $msg, true, $parse_bbcode);

			case "Post":
				$msg = getPOSTval('msg');

				$parse_bbcode = getPOSTval('parse_bbcode');
				if(!empty($msg)){
					if(isIgnored($uid, $userData['userid'], 'comments', $userData['age'])){
						$msgs->addMsg("This user is ignoring you. You cannot leave a comment");
					}else{
						if(!$usercomments->postUserComment($uid, $msg, $userData['userid'], $parse_bbcode))
							addUserComment($uid, $msg, true,  $parse_bbcode);
					}
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
	global $usercomments, $userData, $data, $uid, $config, $isAdmin, $cache, $weblog;


	$template =  new template("usercomments/listcomments", true);
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

	ob_start();
	injectSkin($data, 'comment');
	$skin = ob_get_contents();
	ob_end_clean();

	$template->set("skin", $skin);

	$isFriend = $userData['loggedIn'] && ($userData['userid']==$uid || isFriend($userData['userid'], $uid));

	$cols=3;
	$userblog = new userblog($weblog, $uid);

	if ($userblog->isVisible($userData['loggedIn'], $isFriend))
		$cols++;
	if($data['gallery']=='anyone' || ($data['gallery']=='loggedin' && $userData['loggedIn']) || ($data['gallery']=='friends' && $isFriend))
		$cols++;

	$width = round(100.0/$cols);
	$template->set("width", $width);
	$template->set("uid", $uid);
	$template->set("can_see_gallery", $data['gallery']=='anyone' || ($data['gallery']=='loggedin' && $userData['loggedIn']) || ($data['gallery']=='friends' && $isFriend));
	$template->set("can_see_blog", $userblog->isVisible($userData['loggedIn'], $isFriend));
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

/*	if(!isset($parse_bbcode))
		$template->set("checkbox_parsebbcode", makeCheckBox('parse_bbcode', 'Parse BBcode', $userData['parse_bbcode']));
	else
		$template->set("checkbox_parsebbcode", makeCheckBox('parse_bbcode', 'Parse BBcode', $parse_bbcode));
*/
	$template->set("checkbox_parsebbcode", '<input type="hidden" name="parse_bbcode" value="y"/>');


	ob_start();
	editBox("");
	$template->set('editbox', ob_get_contents());
	ob_end_clean();

	$template->display();
	exit;
}

function addUserComment($uid, $msg, $preview,  $parse_bbcode){
	$template = new Template("usercomments/addusercomment");
	$template->set("preview", $preview);


	if($preview){
		$msg = trim($msg);

		$nmsg = html_sanitizer::sanitize($msg);

		if($parse_bbcode)
		{
			$nmsg2 = parseHTML($nmsg);
			$nmsg3 = smilies($nmsg2);
			$nmsg3 = wrap($nmsg3);
		}
		else
			$nmsg3 = $nmsg;

		$template->set("msg", nl2br($nmsg3));
	}

	$template->set("uid", $uid);

/*	if(!isset($parse_bbcode))
		$template->set("checkbox_parsebbcode", makeCheckBox('parse_bbcode', 'Parse BBcode', $userData['parse_bbcode']));
	else
		$template->set("checkbox_parsebbcode", makeCheckBox('parse_bbcode', 'Parse BBcode', $parse_bbcode));*/
	$template->set("checkbox_parsebbcode", '<input type="hidden" name="parse_bbcode" value="y"/>');



	ob_start();
	editBox($nmsg);
	$template->set("editbox",ob_get_contents() );
	ob_end_clean();
	$template->display();
	exit;
}

