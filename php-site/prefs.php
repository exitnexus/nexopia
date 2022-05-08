<?

	$login=1;

	require_once("include/general.lib.php");
    $template = new Template("prefs/prefs");
	$isAdmin = $mods->isAdmin($userData['userid'],'editpreferences');

	$uid = ($isAdmin ? getREQval('uid', 'int', $userData['userid']) : $userData['userid']);

	switch($action){
		case "Update Preferences":
			if($data = getPOSTval('data','array'))
				update($data);
			break;
		case "Change Password":
			if($data = getPOSTval('data','array'))
				changepass($data);
			break;
		case "Change Email":
			if($data = getPOSTval('data','array'))
				changeemail($data);
			break;
		case "Delete":
			$delpass = getPOSTval('delpass', 'string', false);
			$reason = getPOSTval('reason', 'string', false);
			if($userData['userid'] == $uid && $delpass && $reason)
				delete($delpass, $reason);
			break;
	}


function delete($delpass, $reason){
	global $userData, $msgs, $usersdb, $useraccounts, $auth;

	if($auth->checkpassword($userData['userid'], $delpass)){
		$useraccounts->delete($userData['userid'], $reason);
		$auth->destroySession($userData['userid'], $userData['sessionkey']);
		header("location: /");
		exit;
	}else{
		$msgs->addMsg("Wrong password. Account not deleted");
	}
}

function changepass($data){
	global $userData, $msgs, $uid, $usersdb, $mods, $cache;

	if($userData['userid'] != $uid && !$mods->isAdmin($userData['userid'],'editpassword'))
		return false;

	if(blank($data['newpass1'], $data['newpass2'], $data['oldpass'])){
		$msgs->addMsg("Your input was incomplete");
		return false;
	}

	$passmatch = (($userData['userid'] != $uid) || $auth->checkpassword($userid, $password));

	if($passmatch){
		if($data['newpass1']!=$data['newpass2']){
			$msgs->addMsg("New passwords don't match. Password not changed");
		}elseif(strlen($data['newpass1'])<4){
			$msgs->addMsg("New password is too short. Password not changed");
		}elseif(strlen($data['newpass1'])>16){
			$msgs->addMsg("New password is too long. Password not changed");
		}else{
			$auth->changePassword($uid, $password);

			$res = $usersdb->prepare_query("SELECT sessionid FROM sessions WHERE userid = % && sessionid != ?", $uid, $userData['sessionkey']);//log out all other of your sessions

			while($line = $res->fetchrow())
				$cache->remove("session-$uid-$line[sessionid]");

			$usersdb->prepare_query("DELETE FROM sessions WHERE userid = % && sessionid != ?", $uid, $userData['sessionkey']);

			$msgs->addMsg("Password Changed");
		}
	}else{
		$msgs->addMsg("Wrong password entered. Password not changed.");
	}
}

function changeemail($data){
	global $userData, $msgs, $config, $uid, $usersdb, $masterdb, $mods, $useraccounts, $auth;

	if($userData['userid'] != $uid && !$mods->isAdmin($userData['userid'],'editemail'))
		return false;

	if(blank($data['oldpass'], $data['email1'], $data['email2'])){
		$msgs->addMsg("Your input was incomplete");
		return false;
	}

	$data['email1'] = trim($data['email1']);
	$data['email2'] = trim($data['email2']);

	if($data['email1'] != $data['email2']){
		$msgs->addMsg("The email addresses didn't match.");
		return false;
	}

	$passmatch = (($userData['userid'] != $uid) || $auth->checkpassword($uid, $data['oldpass']));

	if($userData['userid']==$uid && !$passmatch){
		$msgs->addMsg("Password doesn't match");
		return false;
	}

	$res = $masterdb->prepare_query("SELECT email FROM useremails WHERE userid = % && active = 'y'", $uid);
	$line = $res->fetchrow();


	if($data['email1'] != $line['email']){
		if(isValidEmail($data['email1'])){
			if($useraccounts->changeEmail($uid, $data['email1'])){
				incHeader();
				echo "For this change to take effect, you'll have to click the link sent to your new email address.";
				incFooter();
				exit;
			}
		}else{
			$msgs->addMsg("Invalid email address");
		}
	}
}

function update($data){
	global $userData, $msgs, $config, $uid, $usersdb, $configdb, $cache;

    $locations = new category( $configdb, "locs");
	$res = $usersdb->prepare_query("SELECT premiumexpiry FROM users WHERE userid = %", $uid);
	$line = $res->fetchrow();

	$commands=array();

	$commands[]= "fwmsgs = " . (isset($data['fwmsgs'])? "'y'" : "'n'");
	$commands[]= "enablecomments = " . (isset($data['enablecomments'])? "'y'" : "'n'");

	//$commands[]= "parse_bbcode = " . (isset($data['parse_bbcode'])? "'y'" : "'n'");
	//$commands[]= "bbcode_editor = " . (isset($data['bbcode_editor'])? "'y'" : "'n'");

	if(isset($data['onlyfriendsmsgs']) && isset($data['onlyfriendscomments']))
		$commands[]= "onlyfriends = 'both'";
	elseif(isset($data['onlyfriendsmsgs']))
		$commands[]= "onlyfriends = 'msgs'";
	elseif(isset($data['onlyfriendscomments']))
		$commands[]= "onlyfriends = 'comments'";
	else
		$commands[]= "onlyfriends = 'neither'";

	if(isset($data['ignorebyagemsgs']) && isset($data['ignorebyagecomments']))
		$commands[]= "ignorebyage = 'both'";
	elseif(isset($data['ignorebyagemsgs']))
		$commands[]= "ignorebyage = 'msgs'";
	elseif(isset($data['ignorebyagecomments']))
		$commands[]= "ignorebyage = 'comments'";
	else
		$commands[]= "ignorebyage = 'neither'";

	if(!isset($data['forumpostsperpage']) || $data['forumpostsperpage'] < 10 || $data['forumpostsperpage']>100)
		$data['forumpostsperpage']=25;
	$commands[]= $usersdb->prepare("forumpostsperpage = #", $data['forumpostsperpage']);

	$commands[]= "showrightblocks = " . (isset($data['showrightblocks'])? "'y'" : "'n'");
	$commands[]= "showpostcount = " . (isset($data['showpostcount'])? "'y'" : "'n'");
	$commands[]= "showsigs = " . (isset($data['showsigs'])? "'y'" : "'n'");
	$userData['showrightblocks'] = (isset($data['showrightblocks'])? "y" : "n");				//update current page

	if($config['allowThreadUpdateEmails'])
		$commands[] = "threadupdates = " . (isset($data['threadupdates'])? "'y'" : "'n'");

	$commands[] = "replyjump = " . (isset($data['replyjump'])? "'forum'" : "'thread'");
	$commands[] = "autosubscribe = " . (isset($data['autosubscribe'])? "'y'" : "'n'");
	$commands[] = "forumjumplastpost = " . (isset($data['forumjumplastpost'])? "'y'" : "'n'");
	$commands[] = "friendslistthumbs = " . (isset($data['friendslistthumbs'])? "'y'" : "'n'");
	$commands[] = "recentvisitlistthumbs = " . (isset($data['recentvisitlistthumbs'])? "'y'" : "'n'");
	$commands[] = "recentvisitlistanon = " . (isset($data['recentvisitlistanon'])? "'y'" : "'n'");



	if(isset($data['defaultsex']) && in_array($data['defaultsex'], array("Male","Female")))
		$commands[] = $usersdb->prepare("defaultsex = ?", $data['defaultsex']);

    if(isset($data['defaultloc']) )
		$commands[] = $usersdb->prepare("defaultloc = #", $data['defaultloc']);

	if(!isset($data['defaultminage']) || $data['defaultminage'] < $config['minAge'])
		$data['defaultminage'] = $config['minAge'];
	if(!isset($data['defaultmaxage']) || $data['defaultmaxage'] > $config['maxAge'])
		$data['defaultmaxage'] = $config['maxAge'];
	if($data['defaultminage'] > $data['defaultmaxage']){
		$temp = $data['defaultmaxage'];
		$data['defaultmaxage'] = $data['defaultminage'];
		$data['defaultminage'] = $temp;
	}


	$commands[] = $usersdb->prepare("defaultminage = #", $data['defaultminage']);
	$commands[] = $usersdb->prepare("defaultmaxage = #", $data['defaultmaxage']);

	if(isset($data['forumsort']) && ($data['forumsort'] == 'thread' || $data['forumsort'] == 'post'))
		$commands[] = $usersdb->prepare("forumsort = ?", $data['forumsort']);

	$anonymousviews_options = array('Anyone' => 'n', 'Friends Only' => 'f', 'Nobody' => 'y');
	if($line['premiumexpiry'] > time()){

		if (isset($data['anonymousviews']) && in_array($data['anonymousviews'], array_keys($anonymousviews_options)))
			$commands[] = $usersdb->prepare("anonymousviews = ?", $anonymousviews_options[ $data['anonymousviews'] ]);
		$commands[] = "friendsauthorization = " . (isset($data['friendsauthorization'])? "'y'" : "'n'");
		$commands[] = "limitads = " . (isset($data['limitads']) ? "'y'" : "'n'");
		$userData['limitads'] = isset($data['limitads']);
		$commands[] = "spotlight = " . (isset($data['spotlight']) ? "'y'" : "'n'");
		$commands[]= "hideprofile = " . (isset($data['hideprofile'])? "'y'" : "'n'");
	}

	if(isset($data['timezone']) && gettimezones($data['timezone']) !== false){
		$commands[] = $usersdb->prepare("timeoffset = ?", $data['timezone']);
		$userData['timeoffset'] = $data['timezone'];
	}

	$commands[] = "trustjstimezone = " . (isset($data['trustjstimezone'])? "'y'" : "'n'");

	$usersdb->query("UPDATE users SET " . implode(", ", $commands) . $usersdb->prepare(" WHERE userid = %", $uid));


//profile settings
	$commands=array();

	$commands[]= "showactivetime = " . (isset($data['showactivetime'])? "'y'" : "'n'");
	$commands[]= "showprofileupdatetime = " . (isset($data['showprofileupdatetime'])? "'y'" : "'n'");
	$commands[]= "showjointime = " . (isset($data['showjointime'])? "'y'" : "'n'");
	$commands[]= "showbday = " . (isset($data['showbday'])? "'y'" : "'n'");
	$commands[]= "showlastblogentry = " . (isset($data['showlastblogentry'])? "'y'" : "'n'");

	if($line['premiumexpiry'] > time())
		$commands[] = "showpremium = " . (isset($data['showpremium']) ? "'y'" : "'n'");


	$query = "UPDATE profile SET " . implode(", ", $commands) . $usersdb->prepare(" WHERE userid = %", $uid);

	$usersdb->query($query);




	$cache->remove("userprefs-$uid");
	$cache->remove("profile-$uid");
	$cache->remove("userinfo-$uid");

	$msgs->addMsg("Update complete");
	return true;
}

    $locations = new category( $configdb, "locs");

	$res = $usersdb->prepare_query("SELECT * FROM users WHERE userid = %", $uid);
	$line = $res->fetchrow();

	$res = $usersdb->prepare_query("SELECT showactivetime, showprofileupdatetime, showjointime, showbday, showpremium, showlastblogentry FROM profile WHERE userid = %", $uid);
	$line2 = $res->fetchrow();

	foreach($line2 as $k => $v)
		$line[$k] = $v;

	$plus = $line['premiumexpiry'] > time();
    $template->set("uid", $uid);

    $template->set("select_list_gender", make_select_list(array("Male","Female"), $line['defaultsex']) );
    $template->set("select_list_locations", '<option value="0">Anywhere</option>' . makeCatSelect($locations->makeBranch(), $line['defaultloc']));
	$template->set("prefs", $line);
	$template->set("has_plus", $plus);
	if($plus){
	   $anonymousviews_options = array('Anyone' => 'n', 'Friends Only' => 'f', 'Nobody' => 'y');
	   $template->set("select_list_anon_options", make_select_list(array_keys($anonymousviews_options), array_search($line['anonymousviews'], $anonymousviews_options)));
	}

    $template->set("allowed_email_thread_notification", $config['allowThreadUpdateEmails']);
    $template->set("select_list_forum_posts_per_page", make_select_list(array(10,25,50,100),$line['forumpostsperpage']));
    $template->set("select_forum_sort", make_select_list_key(array('post' => "Most Recently Active", 'thread' => "Most Recently Created"),$line['forumsort']));

	$template->set("autodetect_timezone", jsdate("F j, Y, g:i a"));
	$timezones = gettimezones();
	$template->set("timezones", $timezones);
    $template->set("prefdate", prefdate("F j, Y, g:i a"));
    $template->set("checkbox_parsebbcode", makeCheckBox('data[parse_bbcode]', '', $line['parse_bbcode'] == 'y' ? true : false ));

    //$template->set("checkbox_bbcodeeditor", makeCheckBox('data[bbcode_editor]', '', $line['bbcode_editor'] == 'y' ? true : false ));

    $template->set("select_skins",make_select_list_col_key($skins,'name',$skin) );
    $template->set("expiry_days", ($line['premiumexpiry'] - time())/86400);
    $template->set("is_current_user", !($userData['userid'] != $uid));
    $template->set("can_edit_email", ($userData['userid'] == $uid || $mods->isAdmin($userData['userid'],'editemail')));
    $template->set("email", $useraccounts->getEmail($userData['userid']));
    $template->set("can_edit_password",($userData['userid'] == $uid || $mods->isAdmin($userData['userid'],'editpassword')));


    $template->display();
