<?

	$login=1;

	require_once("include/general.lib.php");

	$isAdmin = $mods->isAdmin($userData['userid'],'editpreferences');

	$uid = ($isAdmin && isset($_REQUEST['uid']) ? $_REQUEST['uid'] : $userData['userid']);

	switch($action){
		case "Update Preferences":
			if(isset($_POST['data']))
				update($_POST['data']);
			break;
		case "Change Password":
			if(isset($_POST['data']))
				changepass($_POST['data']);
			break;
		case "Change Email":
			if(isset($_POST['data']))
				changeemail($_POST['data']);
			break;
		case "Delete":
			if($userData['userid'] == $uid && isset($_POST['delpass']) && isset($_POST['reason']))
				delete($_POST['delpass'], $_POST['reason']);
			break;
	}


function delete($delpass,$reason){
	global $userData, $msgs, $db;

	$db->prepare_query("SELECT (password = PASSWORD(?)) as passmatch FROM users WHERE userid = ?", $delpass, $userData['userid']);
	$passmatch = $db->fetchfield();

	if($passmatch){
		deleteAccount($userData['userid'], $reason);
		destroySession($userData['userid'], getCOOKIEval('key'));
		header("location: /");
		exit;
	}else{
		$msgs->addMsg("Wrong password. Account not deleted");
	}
}

function changepass($data){
	global $userData, $msgs, $uid, $db, $mods, $cache, $sessiondb;

	if($userData['userid'] != $uid && !$mods->isAdmin($userData['userid'],'editpassword'))
		return false;

	if(!isset($data['newpass1'], $data['newpass2'], $data['oldpass'])){
		$msgs->addMsg("Your input was incomplete");
		return false;
	}

	if(strlen($data['newpass1'])>1 || strlen($data['newpass2'])>1){
		if($userData['userid'] == $uid){
			$db->prepare_query("SELECT PASSWORD(?) = password as passmatch FROM users WHERE userid = ?", $data['oldpass'], $uid);
			$passmatch = $db->fetchfield();
		}

		if($userData['userid']!=$uid || $passmatch ){
			if($data['newpass1']!=$data['newpass2'])
				$msgs->addMsg("New passwords don't match. Password not changed");
			elseif(strlen($data['newpass1'])<4)
				$msgs->addMsg("New password is too short. Password not changed");
			elseif(strlen($data['newpass1'])>16)
				$msgs->addMsg("New password is too long. Password not changed");
			else{
				$db->prepare_query("UPDATE users SET password = PASSWORD(?) WHERE userid = ?", $data['newpass1'], $uid);

				$sessiondb->prepare_query($uid, "SELECT id, sessionid FROM sessions WHERE userid = # && sessionid != ?", $uid, $userData['sessionkey']);//log out all other of your sessions

				$ids = array();
				while($line = $sessiondb->fetchrow()){
					$cache->remove(array($uid, "session-$uid-$line[sessionid]"));
					$ids[] = $line['id'];
				}
				if(count($ids))
					$sessiondb->prepare_query($uid, "DELETE FROM sessions WHERE id IN (#)", $ids);

				$msgs->addMsg("Password Changed");
			}
		}else{
			$msgs->addMsg("Wrong password entered. Password not changed.");
		}
	}
}

function changeemail($data){
	global $userData,$msgs,$config,$uid,$db, $mods;

	if($userData['userid'] != $uid && !$mods->isAdmin($userData['userid'],'editemail'))
		return false;

	if(!isset($data['oldpass'], $data['email'], $data['oldpass'])){
		$msgs->addMsg("Your input was incomplete");
		return false;
	}

	$db->prepare_query("SELECT (password = PASSWORD(?)) as passmatch, email FROM users WHERE userid = ?", $data['oldpass'], $uid);
	$line = $db->fetchrow();

	if($userData['userid']==$uid && !$line['passmatch']){
		$msgs->addMsg("Password doesn't match");
		return false;
	}

	if($data['email']!=$line['email'] && $data['email']){
		if(isValidEmail($data['email'])){
			$db->prepare_query("SELECT userid FROM users WHERE email = ?", $data['email']);
	    	if($db->numrows()>0){
	        	$msgs->addMsg("Email already in use");
	        }else{
				$db->prepare_query("UPDATE users SET email = ? WHERE userid = ?", $data['email'], $uid);

				deactivateAccount($uid); //destroys session

				if($userData['userid']==$uid){
					header("location: /");
					exit;
				}
			}
		}else
			$msgs->addMsg("Invalid email address");
	}
}

function update($data){
	global $userData, $msgs, $config, $uid, $db, $cache;

	$db->prepare_query("SELECT premiumexpiry FROM users WHERE userid = ?", $uid);
	$line = $db->fetchrow();

	$commands=array();

	$commands[]= "fwmsgs = " . (isset($data['fwmsgs'])? "'y'" : "'n'");
	$commands[]= "showemail = " . (isset($data['showemail'])? "'y'" : "'n'");
	$commands[]= "enablecomments = " . (isset($data['enablecomments'])? "'y'" : "'n'");
	$commands[]= "showactivetime = " . (isset($data['showactivetime'])? "'y'" : "'n'");
	$commands[]= "showjointime = " . (isset($data['showjointime'])? "'y'" : "'n'");

	$commands[]= "showbday = " . (isset($data['showbday'])? "'y'" : "'n'");


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
	$commands[]= "forumpostsperpage = '$data[forumpostsperpage]'";

	$commands[]= "showrightblocks = " . (isset($data['showrightblocks'])? "'y'" : "'n'");
	$commands[]= "showpostcount = " . (isset($data['showpostcount'])? "'y'" : "'n'");
	$commands[]= "showsigs = " . (isset($data['showsigs'])? "'y'" : "'n'");
	$userData['showrightblocks'] = (isset($data['showrightblocks'])? "y" : "n");				//update current page

	if($config['allowThreadUpdateEmails'])
		$commands[] = "threadupdates = " . (isset($data['threadupdates'])? "'y'" : "'n'");

	$commands[] = "replyjump = " . (isset($data['replyjump'])? "'forum'" : "'thread'");
	$commands[] = "autosubscribe = " . (isset($data['autosubscribe'])? "'y'" : "'n'");
	$commands[] = "friendslistthumbs = " . (isset($data['friendslistthumbs'])? "'y'" : "'n'");

	if(isset($data['defaultsex']) && in_array($data['defaultsex'], array("Male","Female")))
		$commands[] = "defaultsex = '$data[defaultsex]'";

	if(!isset($data['defaultminage']) || $data['defaultminage'] < $config['minAge'])
		$data['defaultminage'] = $config['minAge'];
	if(!isset($data['defaultmaxage']) || $data['defaultmaxage'] > $config['maxAge'])
		$data['defaultmaxage'] = $config['maxAge'];
	if($data['defaultminage'] > $data['defaultmaxage']){
		$temp = $data['defaultmaxage'];
		$data['defaultmaxage'] = $data['defaultminage'];
		$data['defaultminage'] = $temp;
	}

	$commands[] = $db->prepare("defaultminage = ?", $data['defaultminage']);
	$commands[] = $db->prepare("defaultmaxage = ?", $data['defaultmaxage']);

	if($line['premiumexpiry'] > time()){
		$commands[] = "anonymousviews = " . (!isset($data['anonymousviews'])? "'y'" : "'n'");
		$commands[] = "friendsauthorization = " . (isset($data['friendsauthorization'])? "'y'" : "'n'");
		$commands[] = "showpremium = " . (isset($data['showpremium']) ? "'y'" : "'n'");
		$commands[] = "limitads = " . (isset($data['limitads']) ? "'y'" : "'n'");
		$userData['limitads'] = isset($data['limitads']);
		$commands[] = "spotlight = " . (isset($data['spotlight']) ? "'y'" : "'n'");
		$commands[]= "hideprofile = " . (isset($data['hideprofile'])? "'y'" : "'n'");
	}

	if(isset($data['timezone']) && gettimezones($data['timezone']) !== false){
		$commands[] = $db->prepare("timeoffset = ?", $data['timezone']);
		$userData['timeoffset'] = $data['timezone'];
	}

	$query = "UPDATE users SET " . implode(", ", $commands) . $db->prepare(" WHERE userid = ?", $uid);
	$db->query($query);

	$cache->remove(array($uid, "userprefs-$uid"));

	$msgs->addMsg("Update complete");
	return true;
}


	incHeader();

	if($action != "") $db->begin();

	$db->prepare_query("SELECT * FROM users WHERE userid = ?", $uid);
	$line = $db->fetchrow();

	$plus = $line['premiumexpiry'] > time();

	if($action != "") $db->commit();

	echo "<table align=center>";

	echo "<form action=$_SERVER[PHP_SELF] method=POST target=_top>";
	echo "<input type=hidden name=uid value=$uid>";
	echo "<tr><td class=header colspan=2 align=center>Preferences</td></tr>\n";

//searching
	echo "<tr><td class=header colspan=2>User Search</td></tr>\n";
	echo "<tr><td class=body>Default Sex:</td><td class=body><select class=body name=data[defaultsex]>" . make_select_list(array("Male","Female"), $line['defaultsex']) . "</td></tr>";
	echo "<tr><td class=body>Default Age Range:</td><td class=body><input class=body type=text size=1 name=data[defaultminage] value=$line[defaultminage]> to <input class=body type=text size=1 name=data[defaultmaxage] value=$line[defaultmaxage]></td></tr>";

//profile
	echo "<tr><td class=header colspan=2>Profile</td></tr>\n";
	echo "<tr><td class=body>Show your join date on your profile:</td><td class=body><input class=body type=checkbox name=data[showjointime]" . ($line['showjointime']=='y' ? " checked" : "" ) . "></td></tr>";
	echo "<tr><td class=body>Show your last active time on your profile:</td><td class=body><input class=body type=checkbox name=data[showactivetime]" . ($line['showactivetime']=='y' ? " checked" : "" ) . "></td></tr>";
	if($plus){
		echo "<tr><td class=body>Allow plus members to see that you visited their profile:</td><td class=body><input class=body type=checkbox name=data[anonymousviews]" . ($line['anonymousviews']=='n' ? " checked" : "" ) . "></td></tr>";
		echo "<tr><td class=body>Show that you are a plus member:</td><td class=body><input class=body type=checkbox name=data[showpremium]" . ($line['showpremium']=='y' ? " checked" : "" ) . "></td></tr>";
		echo "<tr><td class=body>Eligible for Spotlight:</td><td class=body><input class=body type=checkbox name=data[spotlight]" . ($line['spotlight']=='y' ? " checked" : "" ) . "></td></tr>";
		echo "<tr><td class=body>Hide Profile from guests and ignored users:</td><td class=body><input class=body type=checkbox name=data[hideprofile]" . ($line['hideprofile']=='y' ? " checked" : "" ) . "></td></tr>";
	}
	echo "<tr><td class=body>Show your Birthday on your profile:</td><td class=body><input class=body type=checkbox name=data[showbday]" . ($line['showbday']=='y' ? " checked" : "" ) . "></td></tr>";

//friends
	echo "<tr><td class=header colspan=2>Friends</td></tr>\n";
	if($plus)
		echo "<tr><td class=body>Notify you when someone adds you to their friends list:</td><td class=body><input class=body type=checkbox name=data[friendsauthorization]" . ($line['friendsauthorization']=='y' ? " checked" : "" ) . "></td></tr>";
	echo "<tr><td class=body>Show thumbnails on your friends list:</td><td class=body><input class=body type=checkbox name=data[friendslistthumbs]" . ($line['friendslistthumbs']=='y' ? " checked" : "" ) . "></td></tr>";

//messaging
	echo "<tr><td class=header colspan=2>Messaging</td></tr>\n";
	echo "<tr><td class=body>Forward Private Messages to Email:</td><td class=body><input class=body type=checkbox name=data[fwmsgs]" . ($line['fwmsgs']=='y' ? " checked" : "" ) . "></td></tr>";
	echo "<tr><td class=body>Ignore Messages From Users Outside your Age Range:</td><td class=body><input class=body type=checkbox name=data[ignorebyagemsgs]" . ($line['ignorebyage']=='both' || $line['ignorebyage']=='msgs' ? " checked" : "" ) . "></td></tr>";
	echo "<tr><td class=body>Only Accept Messages From Friends:</td><td class=body><input class=body type=checkbox name=data[onlyfriendsmsgs]" . ($line['onlyfriends']=='both' || $line['onlyfriends'] =='msgs' ? " checked" : "" ) . "></td></tr>";
//comments
	echo "<tr><td class=header colspan=2>Comments</td></tr>\n";
	echo "<tr><td class=body>Allow Comments:</td><td class=body><input class=body type=checkbox name=data[enablecomments]" . ($line['enablecomments']=='y' ? " checked" : "" ) . "></td></tr>";
	echo "<tr><td class=body>Ignore Comments From Users Outside your Age Range:</td><td class=body><input class=body type=checkbox name=data[ignorebyagecomments]" . ($line['ignorebyage']=='both' || $line['ignorebyage']=='comments' ? " checked" : "" ) . "></td></tr>";
	echo "<tr><td class=body>Only Accept Comments From Friends:</td><td class=body><input class=body type=checkbox name=data[onlyfriendscomments]" . ($line['onlyfriends']=='both' || $line['onlyfriends'] =='comments' ? " checked" : "" ) . "></td></tr>";

//forums
	echo "<tr><td class=header colspan=2>Forums</td></tr>\n";
	echo "<tr><td class=body>Return to topic listing after posting:</td><td class=body><input class=body type=checkbox name=data[replyjump]" . ($line['replyjump']=='forum' ? " checked" : "" ) . "></td></tr>";
	echo "<tr><td class=body>Automatically subscribe to topics you have posted in:</td><td class=body><input class=body type=checkbox name=data[autosubscribe]" . ($line['autosubscribe']=='y' ? " checked" : "" ) . "></td></tr>";
	echo "<tr><td class=body>Show your post count next to each post:</td><td class=body><input class=body type=checkbox name=data[showpostcount]" . ($line['showpostcount']=='y' ? " checked" : "" ) . "></td></tr>";
	echo "<tr><td class=body>Show users signatures:</td><td class=body><input class=body type=checkbox name=data[showsigs]" . ($line['showsigs']=='y' ? " checked" : "" ) . "></td></tr>";
	if($config['allowThreadUpdateEmails'])
		echo "<tr><td class=body>Email Thread Notification:</td><td class=body><input class=body type=checkbox name=data[threadupdates]" . ($line['threadupdates']=='y' ? " checked" : "" ) . "></td></tr>";
	echo "<tr><td class=body>Posts per page in the forums:</td><td class=body><select class=body name=data[forumpostsperpage]>" .  make_select_list(array(10,25,50,100),$line['forumpostsperpage']). "</select></td></tr>";
//general
	echo "<tr><td class=header colspan=2>General</td></tr>\n";
	echo "<tr><td class=body>Show status bar (right side, not suggested for 800x600 users):</td><td class=body><input class=body type=checkbox name=data[showrightblocks]" . ($line['showrightblocks']=='y' ? " checked" : "" ) . "></td></tr>";
	if($plus)
		echo "<tr><td class=body>Show fewer ads:</td><td class=body><input class=body type=checkbox name=data[limitads]" . ($line['limitads']=='y' ? " checked" : "" ) . "></td></tr>";
	echo "<tr><td class=body>Timezone:</td><td class=body><select class=body name=data[timezone]>";
		$timezones = gettimezones();
		foreach($timezones as $id => $val){
			echo "<option value=$id";
			if($line['timeoffset'] == $id)
				echo " selected";
			echo ">$val[0]";
		}
	echo "</select></td></tr>";
	echo "<tr><td class=body>Date and Time in Currently Selected Timezone:</td><td class=body>" . userDate("F j, Y, g:i a") . "</td></tr>";;

	echo "<tr><td class=body>Choose a skin: </td><td class=body><select class=body name=newskin>";
	echo make_select_list_col_key($skins,'name',$skin);
	echo "</td></tr>";

//update
	echo "<tr><td class=body align=center colspan=2><input class=body type=submit name=action value=\"Update Preferences\"></td></tr>";
	echo "</form>";

	if($line['premiumexpiry'] > time()){
		echo "<tr><td class=body colspan=2>&nbsp;</td></tr>";
		echo "<tr><td class=header colspan=2>Nexopia Plus:</td></tr>";
		echo "<tr><td class=body>Time remaining:</td><td class=body>" . number_format(($line['premiumexpiry'] - time())/86400,2) . " Days</td></tr>";
		echo "<tr><td class=body>Expiry Date:</td><td class=body>" . userDate("F j, Y, g:i a", $line['premiumexpiry']) . "</td></tr>";

		echo "<tr><td class=body colspan=2>&nbsp;</td></tr>";
	}

	if($userData['userid'] == $uid || $mods->isAdmin($userData['userid'],'editemail')){
		echo "<form action=$_SERVER[PHP_SELF] method=post>\n";
		if($userData['userid'] != $uid)
			echo "<input type=hidden name=uid value=$uid>";
		echo "<tr><td class=header colspan=2>Change your email:</td></tr>\n";
		echo "<tr><td class=body>Email:</td><td class=body><input class=body size=30 type=text name=\"data[email]\" value=\"$line[email]\"></td></tr>";
		echo "<tr><td class=body>Current Password:</td><td class=body><input class=body type=password name=\"data[oldpass]\"></td></tr>";
		echo "<tr><td class=body colspan=2>Changing email address will require reactivation and will log you out.</td></tr>";
		echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=\"Change Email\" onClick=\"alert('Changing email address will require reactivation and will log you out.'); return confirm('Do you want to continue?');\"></td></tr>";
		echo "</form>\n";

		echo "<tr><td class=body colspan=2>&nbsp;</td></tr>";
	}

	if($userData['userid'] == $uid || $mods->isAdmin($userData['userid'],'editpassword')){
		echo "<form action=$_SERVER[PHP_SELF] method=post>\n";
		if($userData['userid'] != $uid)
			echo "<input type=hidden name=uid value=$uid>";
		echo "<tr><td class=header colspan=2>Change your password:</td></tr>\n";
		echo "<tr><td class=body>Current Password:</td><td class=body><input class=body type=password name=\"data[oldpass]\"></td></tr>";
		echo "<tr><td class=body>New Password:</td><td class=body><input class=body type=password name=\"data[newpass1]\"></td></tr>";
		echo "<tr><td class=body>Retype new Password:</td><td class=body><input class=body type=password name=\"data[newpass2]\"></td></tr>";
		echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=\"Change Password\"></td></tr>";
		echo "</form>\n";

		echo "<tr><td class=body colspan=2>&nbsp;</td></tr>";
	}

	if($userData['userid'] == $uid){
		echo "<form action=$_SERVER[PHP_SELF] method=post>\n";
		echo "<tr><td class=header colspan=2>Delete your account:</td></tr>\n";
		echo "<tr><td class=body>Password:</td><td class=body><input class=body type=password name=delpass></td></tr>";
		echo "<tr><td class=body>Reason:</td><td class=body><input class=body size=30 maxlength=255 type=text name=reason></td></tr>";
		echo "<tr><td class=body colspan=2>This will delete your account, including your profile, your pictures, friends list, messages, etc.<br>Your forum posts, comments and messages in other users inbox will remain.</td></tr>";
		echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=\"Delete\"></td></tr>\n";
		echo "</form>\n";
	}

	echo "</table>";

	incFooter();

