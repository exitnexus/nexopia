<?
	
	// Constants for "menu access".
	// Sets the accessibility for items in the menu bar on the 2.0 profiles.
	define("NONE", 0);
	define("FRIENDS", 1);
	define("FRIENDS_OF_FRIENDS", 2);
	define("LOGGED_IN", 3);
	define("ALL", 4);
	define("ADMIN", 5);

	$login=0.5;

	require_once("include/general.lib.php");
	$template = new Template("prefs/prefs");
	$isAdmin = $mods->isAdmin($userData['userid'],'editpreferences');
	$isSigAdmin = $mods->isAdmin($userData['userid'],'editsig');
	$maxlengths = array(
		'signiture' =>1000);	
	$uid = ($isAdmin ? getREQval('uid', 'int', $userData['userid']) : $userData['userid']);
	
	// To access the preference page you must be logged in
	if($uid < 1)
	{
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: http://". $wwwdomain . "/login.php?referer=/prefs.php");
		exit;
	}
	
	$res = $usersdb->prepare_query("SELECT userid, age, sex, premiumexpiry > # AS plus, dob, forumrank, posts FROM users WHERE userid = %", time(), $uid);
	$user = $res->fetchrow();

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
	global $userData, $msgs, $usersdb, $useraccounts, $auth, $Ruby;

	if($auth->checkpassword($userData['userid'], $delpass)){
		$Ruby->send('Orwell::SendEmail')->php_send($userData['userid'], 'Account Deletion', 'account_cancelation_plain', array('html_template' => 'account_cancelation', 'template_module' => 'orwell'));
		$useraccounts->delete($userData['userid'], $reason);
		$auth->destroySession($userData['userid'], $userData['sessionkey']);
		header("location: /");
		exit;
	}else{
		$msgs->addMsg("Wrong password. Account not deleted");
	}
}

function changepass($data){
	global $userData, $msgs, $uid, $usersdb, $mods, $cache, $auth;

	if($userData['userid'] != $uid && !$mods->isAdmin($userData['userid'],'editpassword'))
		return false;

	if(blank($data['newpass1'], $data['newpass2'], $data['oldpass'])){
		$msgs->addMsg("Your input was incomplete");
		return false;
	}

	$passmatch = (($userData['userid'] != $uid) || $auth->checkpassword($userData['userid'], $data['oldpass']));

	if($passmatch){
		if($data['newpass1']!=$data['newpass2']){
			$msgs->addMsg("New passwords don't match. Password not changed");
		}elseif(strlen($data['newpass1'])<4){
			$msgs->addMsg("New password is too short. Password not changed");
		}elseif(strlen($data['newpass1'])>16){
			$msgs->addMsg("New password is too long. Password not changed");
		}else{
			$auth->changePassword($uid, $data['newpass1']);

			$res = $usersdb->prepare_query("SELECT sessionid FROM sessions WHERE userid = % && sessionid != ?", $uid, $userData['sessionkey']);//log out all other of your sessions

			while($line = $res->fetchrow())
				$cache->remove("session-$uid-$line[sessionid]");

			$usersdb->prepare_query("DELETE FROM sessions WHERE userid = % && sessionid != ?", $uid, $userData['sessionkey']);

			$msgs->addMsg("Password Changed");

			$auth->loginlog($uid, 'changepass');
		}
	}else{
		$msgs->addMsg("Wrong password entered. Password not changed.");
		
		$auth->loginlog($uid, 'changepassfail');
	}
}

function changeemail($data){
	global $userData, $msgs, $config, $uid, $usersdb, $masterdb, $mods, $useraccounts, $auth, $cache;

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

	if(!$passmatch){
		$msgs->addMsg("Password doesn't match");
		$auth->loginlog($uid, 'changeemailfail');
		return false;
	}

	$res = $masterdb->prepare_query("SELECT email FROM useremails WHERE userid = % && active = 'y'", $uid);
	$line = $res->fetchrow();


	if($data['email1'] != $line['email']){
		if(isValidEmail($data['email1'])){
			if($useraccounts->changeEmail($uid, $data['email1'])){
				$auth->loginlog($uid, 'changeemail');
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
	$commands[]= "fwsitemsgs = " . (isset($data['fwsitemsgs'])? "'y'" : "'n'");
	$commands[]= "enablecomments = " . (isset($data['enablecomments'])? "'y'" : "'n'");
	if (isset($data['enablecomments'])) {
		if (isset($data['onlyfriendscomments'])){
			$commands[]= "commentsmenuaccess = " . FRIENDS;			
		} else {
			$commands[]= "commentsmenuaccess = " . LOGGED_IN;
		}
	} else {
		$commands[]= "commentsmenuaccess = " . NONE;
	}
	
	if(isset($data['onlyfriendsmsgs']) && isset($data['onlyfriendscomments'])) {
		$commands[]= "onlyfriends = 'both'";
	} elseif(isset($data['onlyfriendsmsgs'])) {
		$commands[]= "onlyfriends = 'msgs'";
	} elseif(isset($data['onlyfriendscomments'])) {
		$commands[]= "onlyfriends = 'comments'";
	} else {
		$commands[]= "onlyfriends = 'neither'";
	}

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
	$commands[] = "profilefriendslistthumbs = " . (isset($data['profilefriendslistthumbs'])? "'y'" : "'n'");
	$commands[] = "recentvisitlistthumbs = " . (isset($data['recentvisitlistthumbs'])? "'y'" : "'n'");
	$commands[] = "recentvisitlistanon = " . (isset($data['recentvisitlistanon'])? "'y'" : "'n'");



	if(isset($data['defaultsex']) && in_array($data['defaultsex'], array("Male","Female")))
		$commands[] = $usersdb->prepare("defaultsex = ?", $data['defaultsex']);

	if(isset($data['defaultloc']) )
		$commands[] = $usersdb->prepare("defaultloc = #", $data['defaultloc']);
	
	$data['defaultminage'] = (int)$data['defaultminage'];
	$data['defaultmaxage'] = (int)$data['defaultmaxage'];
	if(($data['defaultminage'] == 0) || ($data['defaultminage'] < (int)$config['minAge']))
		$data['defaultminage'] = (int)$config['minAge'];
	if($data['defaultminage'] > (int)$config['maxAge'])
		$data['defaultminage'] = (int)$config['minAge'];
	if(($data['defaultmaxage'] == 0) || ($data['defaultmaxage'] > (int)$config['maxAge']))
		$data['defaultmaxage'] = (int)$config['maxAge'];
	if($data['defaultmaxage'] < (int)$config['minAge'])
		$data['defaultmaxage'] = (int)$config['maxAge'];
	if($data['defaultminage'] > $data['defaultmaxage']){
		$temp = $data['defaultmaxage'];
		$data['defaultmaxage'] = $data['defaultminage'];
		$data['defaultminage'] = $temp;
	}


	$commands[] = $usersdb->prepare("defaultminage = #", $data['defaultminage']);
	$commands[] = $usersdb->prepare("defaultmaxage = #", $data['defaultmaxage']);
	$commands[]= "hideprofile = " . (isset($data['hideprofile'])? "'y'" : "'n'");
	$commands[]= "searchemail = " . (isset($data['searchemail'])? "'y'" : "'n'");
	$commands[] = "friendsauthorization = " . (isset($data['friendsauthorization'])? "'y'" : "'n'");

	if(isset($data['forumsort']) && ($data['forumsort'] == 'thread' || $data['forumsort'] == 'post'))
		$commands[] = $usersdb->prepare("forumsort = ?", $data['forumsort']);

	$anonymousviews_options = array('Anyone' => 'n', 'Friends Only' => 'f', 'Nobody' => 'y');
	if($line['premiumexpiry'] > time()){

		if (isset($data['anonymousviews']) && in_array($data['anonymousviews'], array_keys($anonymousviews_options)))
			$commands[] = $usersdb->prepare("anonymousviews = ?", $anonymousviews_options[ $data['anonymousviews'] ]);
		$commands[] = "limitads = " . (isset($data['limitads']) ? "'y'" : "'n'");
		$userData['limitads'] = isset($data['limitads']);
		$commands[] = "spotlight = " . (isset($data['spotlight']) ? "'y'" : "'n'");
		$commands[] = "hidehits = " . (isset($data['hidehits']) ? "'y'" : "'n'");
	}

	if(isset($data['timezone']) && gettimezones($data['timezone']) !== false){
		$commands[] = $usersdb->prepare("timeoffset = ?", $data['timezone']);
		$userData['timeoffset'] = $data['timezone'];
	}

	$commands[] = "trustjstimezone = " . (isset($data['trustjstimezone'])? "'y'" : "'n'");

	$usersdb->query("UPDATE users SET " . implode(", ", $commands) . $usersdb->prepare(" WHERE userid = %", $uid));


	// Profile settings
	$commands=array();

	if($line['premiumexpiry'] > time()) {
		$commands[] = "showpremium = " . (isset($data['showpremium']) ? "'y'" : "'n'");

		$query = "UPDATE profile SET " . implode(", ", $commands) . $usersdb->prepare(" WHERE userid = %", $uid);

		$usersdb->query($query);
	}

	// If the user disables profile comments then we should remove the Profile Comments block.
	if( !isset($data['enablecomments']) ) {
		$res = $usersdb->prepare_query("SELECT blockid FROM profiledisplayblocks WHERE userid = % AND path = 'comments'", $uid);
		$blockid = $res->fetchrow();
		$blockid = $blockid["blockid"];
		$cache->remove("Profile::ProfileDisplayBlock-$uid/$blockid");
		$usersdb->prepare_query("DELETE FROM profiledisplayblocks WHERE userid = % AND path = 'comments'", $uid);
	}


	$cache->remove("userprefs-$uid");
	$cache->remove("profile-$uid");
	$cache->remove("userinfo-$uid");
	
	updateForumDetails();
	
	$msgs->addMsg("Update complete");
	return true;
}


function editForumDetails($template){
	global $userData, $user, $uid, $usersdb, $forums, $profile, $maxlengths, $forums, $isSigAdmin, $abuselog;

	if($userData['userid'] != $uid && !$isSigAdmin)
		die("You don't have permission to do this");

	$res = $usersdb->prepare_query("SELECT enablesignature, nsigniture, signiture FROM profile WHERE userid = %", $uid);
	$user += $res->fetchrow();

	$template->set('uid', $uid);
	$template->set('user', $user);
	$template->set('userData', $userData);
	$template->set('isAdmin', $isSigAdmin);
	$template->set('checkEnableSignature', makeCheckBox("enablesignature", "Enable Signature", $user['enablesignature'] == 'y'));
	$template->set('allowedSignature', ($user['enablesignature'] == 'y' || $isSigAdmin));
	$template->set('maxlengths', $maxlengths);
	$template->set('signitureLength', strlen($user['signiture']));

	$maxwidth =600;
	$maxheight=200;
	$maxsize = "200 KB";

	$template->set('maxwidth', $maxwidth);
	$template->set('maxheight', $maxheight);
	$template->set('maxPreviewWidth', ($maxwidth + 4));
	$template->set('maxPreviewHeight', ($maxheight + 4));
	$template->set('maxsize', $maxsize);
	$template->set('forumRank', $forums->forumrank($user['posts']));

	if($isSigAdmin && $uid != $userData['userid']){
		$template->set('displayAdmin', true);

		$reminders = $forums->mutelength;
		unset($reminders[0]);

		$template->set('selectReminder', make_select_list_key($reminders));
		$template->set('selectAbuseReason', make_select_list_key($abuselog->reasons));
	} else {
		$template->set('displayAdmin', false);
	}
}


function updateForumDetails(){
	global $uid, $user, $userData, $isSigAdmin, $usersdb, $cache, $mods, $abuselog, $usernotify, $msgs, $maxlengths;

	if($uid != $userData['userid'] && !$isSigAdmin)
		return;

	if(!($data = getPOSTval('data', 'array')))
		return;

	if(isset($data['signiture'])){
		if($isSigAdmin)
			$set[] = $usersdb->prepare("enablesignature = ?", (getPOSTval('enablesignature', 'bool') ? "y" : "n") );

		$signiture = cleanHTML(trim(substr($data['signiture'], 0, $maxlengths['signiture'])));
		$nsigniture = wrap(parseHTML(smilies($signiture)));
		$set[] = $usersdb->prepare("signiture = ?", $signiture);
		$set[] = $usersdb->prepare("nsigniture = ?", $nsigniture);

		$usersdb->query("UPDATE profile SET " . implode(", ", $set) . $usersdb->prepare(" WHERE userid = %", $uid));

		$cache->remove("forumusersigs-$uid");
	}

	if($user['plus']){
		$forumrankchoice = getPOSTval('forumrankchoice');
		$forumrank = removeHTML(trim(getPOSTval('forumrank')));

		switch($forumrankchoice){
			case "current":
				break;

			case "default":
				$forumrank = '';

			case "new":
				$usersdb->prepare_query("UPDATE users SET forumrank = ? WHERE userid = %", $forumrank, $uid);

				if($forumrank == ""){
					$mods->deleteItem(MOD_FORUMRANK, $uid);
				}else{
					$mods->newItem(MOD_FORUMRANK, $uid);
				}
				$user['forumrank'] = $forumrank;
				break;
		}

		$cache->remove("userinfo-$uid");
	}

	if($uid != $userData['userid']){
		$reportaction = ABUSE_ACTION_SIG_EDIT;
		$reportreason = getPOSTval('reportreason', 'int');
		$reportsubject= getPOSTval('reportsubject');
		$reporttext   = getPOSTval('reporttext');

		$msgs->addMsg("REPORTTEXT:".$reporttext);
		$msgs->addMsg("SUBJECT:".$reportsubject);

		$abuselog->addAbuse($uid, $reportaction, $reportreason, $reportsubject, $reporttext);

		$reportreminder = getPOSTval('reportreminder', 'int');

		if($reportreminder){
			$message = 	"[url=/prefs.php?uid=$uid]Check/re-enable[/url] the signature for [url=/users/". urlencode(getUserName($uid)) ."]" . getUserName($uid) . "[/url].\n\n" .
						"The report was for " . $abuselog->reasons[$reportreason] . ": $reportsubject\n[quote]" . $reporttext. "[/quote]";

			$usernotify->newNotify($userData['userid'], time() + $reportreminder, 'Signature Checkup', $message);
		}

		$mods->adminlog("update signature", "Update user signature: userid $uid");
	}

	$msgs->addMsg("Updated");
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

	$locationAutocomplete = $rap_pagehandler->subrequest(null, "GetRequest", "/autocomplete/location/$line[defaultloc]", array("location_id_field_id"=>"data[defaultloc]"), "Public");

	$template->set("select_list_gender", make_select_list(array("Male","Female"), $line['defaultsex']) );
	$template->set("select_list_locations", $locationAutocomplete->get_reply_output());
	$template->set("prefs", $line);
	$template->set("has_plus", $plus);
	if($plus){
		$anonymousviews_options = array('Anyone' => 'n', 'Friends Only' => 'f', 'Nobody' => 'y');
		$template->set("select_list_anon_options", make_select_list(array_keys($anonymousviews_options), array_search($line['anonymousviews'], $anonymousviews_options)));
	}

	$template->set("allowed_email_thread_notification", $config['allowThreadUpdateEmails']);
	$template->set("select_list_forum_posts_per_page", make_select_list(array(10,25,50,100),$line['forumpostsperpage']));
	$template->set("select_forum_sort", make_select_list_key(array('post' => "Most Recently Active", 'thread' => "Most Recently Created"),$line['forumsort']));

	$timezones = gettimezones();
	$template->set("timezones", $timezones);
	$template->set("prefdate", prefdate("F j, Y, g:i a"));

	$template->set("expiry_days", ($line['premiumexpiry'] - time())/86400);
	$template->set("is_current_user", !($userData['userid'] != $uid));
	$template->set("can_edit_email", ($userData['userid'] == $uid || $mods->isAdmin($userData['userid'],'editemail')));
	$template->set("email", $useraccounts->getEmail($uid));
	$template->set("can_edit_password",($userData['userid'] == $uid || $mods->isAdmin($userData['userid'],'editpassword')));

	editForumDetails($template);

	$template->display();
