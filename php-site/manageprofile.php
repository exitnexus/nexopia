<?

	$login=0.5;

	require_once("include/general.lib.php");

	$isProfileAdmin = $mods->isAdmin($userData['userid'],'editprofile');
	$isSigAdmin = $mods->isAdmin($userData['userid'],'editsig');

	$uid = ($isProfileAdmin || $isSigAdmin ? getREQval('uid', 'int', $userData['userid']) : $userData['userid']);


	$res = $usersdb->prepare_query("SELECT userid, age, sex, premiumexpiry > # AS plus, dob, forumrank, posts FROM users WHERE userid = %", time(), $uid);
	$user = $res->fetchrow();
	
	if(!$user)
		die("Bad User");

	$maxlengths = array(
		'tagline'   => 300,
		'signiture' =>1000,
		'numProfileBlocks' => 3,
		'blockLength' => ($user['plus'] ? 20000 : 10000),
		);

	$section = getREQval('section');

	switch($section){
		case 'details':

			if($action)
				updateDetails();
			editDetails();



		case 'forums':
			if($action)
				updateForumDetails();
			editForumDetails();


		case 'interests':
			if($action)
				updateInterests();
			editInterests();

		case 'skins':

			$data = getPOSTval('data', 'array');
			$id = getREQval('id','int');

			switch($action){
				case 'Create':
					insertSkin($data);
					break;

				case 'Update':
					updateSkin($id, $data);
					break;

				case 'deleteskin':
					if($id && checkKey($id, getREQval('k')))
						deleteSkin($id);
					break;

				case 'chooseskins':
					chooseskins($data);
					break;

				case 'Cancel':
					$id = 0;
					$data = array();
					//yes, fall through

				case 'Test':
				case 'editskin':
				default:
					editSkins($id, $data); //exit
			}

			editSkins(); //exit


		case 'basics':
		default:
			if($action)
				updateBasics();

			editBasics();
	}

//////////////////

function getMenu($current){
	global $uid, $userData, $user, $isProfileAdmin;

	$items = array(
		'basics' => "Basics",
		'details' => "Details",
		'skins' => "Skins",
		'interests' => "Interests",
		'forums' => "Forums",
		'groups' => "Social Groups"
		);

	if($uid != $userData['userid'])
		unset($items['skins'], $items['interests']);
	if(!$user['plus'])
		unset($items['skins']);

	$str = "";

	foreach($items as $section => $name){
		if($str)
			$str .= " | ";

		if($section == $current)
			$str .= "<b>$name</b>";
		// Temporary hack to go through to the Ruby side for the groups edit pane.
		else if ($section == 'groups')
		{
			if($userData['userid'] == $uid)
			{
				$str .= "<a class=body href='/my/groups/edit'><b>$name</b></a>";
			}
			else if ($isProfileAdmin)
			{
				$str .= "<a class=body href='/admin/self/".getUserName($user['userid'])."/groups/edit'><b>$name</b></a>";
			}
		}
		else
			$str .= "<a class=body href=$_SERVER[PHP_SELF]?section=$section" . ($uid == $userData['userid'] ? '' : "&uid=$uid") . "><b>$name</b></a>";
	}

	return $str;
}


function editBasics(){
	global $userData, $uid, $db, $usersdb, $profile, $maxlengths, $isProfileAdmin, $config, $abuselog, $configdb, $reporev;

	if($userData['userid'] != $uid && !$isProfileAdmin)
		die("You don't have permission to do this");

	$locations = new category( $configdb, "locs");

	$result = $usersdb->prepare_query("SELECT dob, loc, firstname, lastname FROM users WHERE userid = %", $uid);
	$user = $result->fetchrow();

	$result = $usersdb->prepare_query("SELECT icq, msn, yahoo, aim, tagline, skin, profile, firstnamevisibility, lastnamevisibility FROM profile WHERE userid = %", $uid);
	$user += $result->fetchrow();

	$prof = decodeProfile($user['profile']);

	for($i=1;$i<=12;$i++)
		$months[$i] = gmdate("F", gmmktime(0,0,0,$i,1,0));


	$profile = getProfileQuestions();

	$template = new template('profiles/manageprofile/editBasics');
	
	$template->set('jsurl', $config['jsloc']);
	$template->set('reporev', $reporev);

	$template->set('menu', getMenu('basics'));
	$template->set('uid', $uid);
	$template->set('user', $user);
	$template->set('firstnamevisibility', makeVisibilitySelectOptions($user['firstnamevisibility']));
	$template->set('lastnamevisibility', makeVisibilitySelectOptions($user['lastnamevisibility'], false));
	$template->set('selectMonth', make_select_list_key($months,gmdate("m",$user['dob'])));
	$template->set('selectDay', make_select_list(range(1,31),gmdate("j",$user['dob'])));
	$template->set('selectYear', make_select_list(array_reverse(range(gmdate("Y")-$config['maxAge'],gmdate("Y")-$config['minAge'])),gmdate("Y",$user['dob'])));
	$template->set('selectLocation', makeCatSelect($locations->makeBranch(),$user['loc']));

	foreach($profile as $qnum => $val)
		$selectAnswers[$qnum] = make_select_list_key($val['answers'], $prof[$qnum]);

	$template->set('profile', $profile);
	$template->set('selectAnswers', $selectAnswers);

	$template->set('taglineLength', strlen($user['tagline']));
	$template->set('maxlengths', $maxlengths);

	if($isProfileAdmin && $uid != $userData['userid']){
		$template->set('displayAdmin', true);
		$template->set('selectAbuseReason', make_select_list_key($abuselog->reasons));
	} else {
		$template->set('displayAdmin', false);
	}
	$template->display();

	exit;
}


function updateBasics(){
	global $uid, $user, $userData, $isProfileAdmin, $usersdb, $configdb, $db, $cache, $mods, $abuselog, $msgs, $config, $maxlengths, $google;

	if($uid != $userData['userid'] && !$isProfileAdmin)
		return;

	if(!($data = getPOSTval('data', 'array')))
		return;

	if(!($prof = getPOSTval('prof', 'array')))
		return;

	$profile = getProfileQuestions();
	$locations = new category( $configdb, "locs");

//update the users stuff
	$commands = array();

	if(isset($data['firstname']))
		$commands[] = $usersdb->prepare("firstname = ?", removeHTML($data['firstname']) );
	if(isset($data['lastname']))
		$commands[] = $usersdb->prepare("lastname = ?", removeHTML($data['lastname']) );

	if(!isset($data['month']) || !is_numeric($data['month']) || $data['month']<=0 || $data['month']>12)
		$msgs->addMsg("Invalid Month");
	elseif(!isset($data['day']) || !is_numeric($data['day']) || $data['day']<=0 || $data['day']>31)
		$msgs->addMsg("Invalid Day");
	elseif(!isset($data['year']) || !is_numeric($data['year']))
		$msgs->addMsg("Invalid Year: $data[year]");
	else{
		$dob = my_gmmktime(0,0,0, $data['month'],$data['day'],$data['year']);
		$age = getAge($dob);
		$user['age'] = $age;

		if($age < $config['minAge'] || $age > $config['maxAge']){
			$msgs->addMsg("Invalid Year");
		}else{
			if($dob != $user['dob']){
				$commands[] = $usersdb->prepare("dob = #", $dob);
				$commands[] = $usersdb->prepare("age = #", $age);
			}
		}
	}

	if(isset($data['loc']) && $locations->isValidCat($data['loc']))
		$commands[] = $usersdb->prepare("loc = ?", $data['loc'] );

	if(is_array($prof) && count($prof) == count($profile)){
		$commands[] = $usersdb->prepare("single = ?", (in_array($prof[3], array('1','2','6')) ? 'y' : 'n') );
		$commands[] = $usersdb->prepare("sexuality = #", $prof[2] );
	}

	$usersdb->query("UPDATE users SET " . implode(", ", $commands) . $usersdb->prepare(" WHERE userid = %", $uid));


//update the profile stuff
	$commands = array();

	if(isset($data['firstnamevisibility']))
	{
		$commands[] = $db->prepare("firstnamevisibility = #", $data['firstnamevisibility']);
	}
	if(isset($data['lastnamevisibility']))
	{
		if (isset($data['firstnamevisibility']))
		{
			if ($data['firstnamevisibility'] < $data['lastnamevisibility'])
			{
				$visibilityOptions = getVisibilityOptions();
				$data['lastnamevisibility'] = $data['firstnamevisibility'];
				$msgs->addMsg("Your last name cannot be more visible than your first name. Last name has been set to <i>".
					$visibilityOptions[$data['firstnamevisibility']]."</i>");
			}	
		}
		
		$commands[] = $db->prepare("lastnamevisibility = #", $data['lastnamevisibility']);
	}
	
	if(is_array($prof) && count($prof) == count($profile))
		$commands[] = $usersdb->prepare("profile = ?", encodeProfile($prof) );

	if(isset($data['tagline'])){
		$tagline = substr(trim($data['tagline']), 0, $maxlengths['tagline']);       //limit number of characters
		$tagline = trim(implode("\n", array_slice(explode("\n", $tagline), 0, 5))); //limit to 5 lines

		$ntagline = nl2br(wrap(smilies(removeHTML($tagline))));

		$commands[] = $db->prepare("tagline = ?", $tagline);
		$commands[] = $db->prepare("ntagline = ?", $ntagline);
	}

	if(isset($data['icq']))
		$commands[] = $db->prepare("icq = #", removeHTML($data['icq']));
	if(isset($data['yahoo']))
		$commands[] = $db->prepare("yahoo = ?", removeHTML($data['yahoo']));
	if(isset($data['msn']))
		$commands[] = $db->prepare("msn = ?", removeHTML($data['msn']));
	if(isset($data['aim']))
		$commands[] = $db->prepare("aim = ?", removeHTML($data['aim']));

	$commands[] = $db->prepare("profileupdatetime = #", time());

	$usersdb->query("UPDATE profile SET " . implode(", ", $commands) . $usersdb->prepare(" WHERE userid = %", $uid));

//update newestprofile
	$db->prepare_query("INSERT IGNORE INTO newestprofile SET userid = %, username = ?, time= #, age = #, sex = ?", $user['userid'], getUserName($uid), time(), $user['age'], $user['sex']);

//fix cache
	$cache->remove("profile-$uid");
	$cache->remove("userinfo-$uid");
	$cache->remove("userprefs-$uid");
	$cache->remove("tagline-$uid");

	$google->updateHash();

//admin stuff
	if($uid != $userData['userid']){
		$reportaction = ABUSE_ACTION_PROFILE_EDIT;
		$reportreason = getPOSTval('reportreason', 'int');
		$reportsubject= getPOSTval('reportsubject');
		$reporttext   = getPOSTval('reporttext');

		$abuselog->addAbuse($uid, $reportaction, $reportreason, $reportsubject, $reporttext);

		$mods->adminlog("update profile", "Update user profile: userid $uid");
		header("location: /profile.php?uid=$uid");
		exit;
	}

	$msgs->addMsg("Updated. Check <a class=body href='/profile.php?uid=$uid'>your profile</a> to see the changes");
}






function editDetails(){
	global $userData, $configdb, $uid, $profile, $maxlengths, $isProfileAdmin, $abuselog;

	if($userData['userid'] != $uid && !$isProfileAdmin)
		die("You don't have permission to do this");

	$template = new template('profiles/manageprofile/editDetails');


	$template->setMultiple(array(
		'uid'			=> $uid,
		'userData'		=> $userData,
		'maxlengths'	=> $maxlengths,
		'menu'			=> getMenu('details'),
	));

	$profBlocks = new profileBlocks($uid);

	$blocks = $profBlocks->getBlocks();
	$maxnum = ($maxnum = end($blocks)) === false ? 0 : $maxnum['blockorder'];
	reset($blocks);

	$prefBlocks = array();
	foreach (range(1,  max(count($blocks), $maxlengths['numProfileBlocks'])) as $count) {
		$block = count($blocks) ? array_shift($blocks) : array(
			'blockid' => '0' . uniqid(), 'blocktitle' => '', 'blockcontent' => '', 'blockorder' => ++$maxnum, 'permission' => 'anyone'
		);

		$block['contentLength'] = strlen($block['blockcontent']);
		$block['blocktitle'] = str_replace('&lt;', '<', $block['blocktitle']);
		$block['editBox'] = editBoxStr(str_replace('&lt;', '<', $block['blockcontent']), "blockTxt_{$block['blockid']}", "block_{$block['blockid']}", 300, 700, $maxlengths['blockLength']);
		$prefBlocks[ $block['blockid'] ] = $block;
	}
	$template->set('blocks', $prefBlocks);

	if($uid != $userData['userid']){
		$template->set('displayAdmin', true);
		$template->set('selectAbuseReason', make_select_list_key($abuselog->reasons));
	}else{
		$template->set('displayAdmin', false);
	}

	$template->display();
	exit;

}

function updateDetails(){
	global $uid, $userData, $user, $isProfileAdmin, $usersdb, $config, $maxlengths, $cache, $mods, $abuselog, $msgs, $google;

	if($uid != $userData['userid'] && !$isProfileAdmin)
		return;

	//if the user is using the fck editor they need more characters
	//to account for all the hidden html.
	$length_offset = 0;


	$titles = getPOSTval('blockTitle', 'array', array());
	$permissions = getPOSTval('permission', 'array', array());
	$positions = getPOSTval('blockOrder', 'array', array());

	$newBlocks = array();
	foreach ($titles as $blockid => $title) {
		$title = removeHTML(trim($title));
		$content = getPOSTval("blockTxt_${blockid}", 'string', '');
		$content = substr(trim($content), 0, $maxlengths['blockLength'] + $length_offset);
		$position = isset($positions[$blockid]) ? $positions[$blockid] : 0;
		$permission = isset($permissions[$blockid]) ? $permissions[$blockid] : 'anyone';
		if (! in_array($permission, array('anyone', 'loggedin', 'friends')))
			$permission = 'anyone';

		$newBlocks[] = array(
			'blockid'		=> $blockid,
			'title'			=> $title,
			'content'		=> $content,
			'position'		=> $position,
			'permission'	=> $permission
		);
	}

	sortCols($newBlocks, SORT_ASC, SORT_NUMERIC, 'position');
	$blockPos = 0;

	$profBlocks = new profileBlocks($uid);

	foreach ($newBlocks as $block) {
		$existingBlocks = $profBlocks->getBlocks();

		// this is a new block (since blockid begins with zero)
		if ($block['blockid']{0} == '0') {
			// no content or maximum blocks reached; skip
			if (! strlen($block['content']) || count($existingBlocks) >= $maxlengths['numProfileBlocks'])
				continue;

			// have content, so add block
			$profBlocks->saveBlocks(array(array(
				'blocktitle'	=> strlen($block['title']) ? $block['title'] : 'Untitled',
				'blockcontent'	=> $block['content'],
				'blockorder'	=> ++$blockPos,
				'permission'	=> $block['permission']
			)));
			scan_string_for_notables($block['content']);
		}

		// this is an update to an existing block
		else {
			// block does not exist; skip
			if (! isset($existingBlocks[$block['blockid']]))
				continue;

			// no content or maximum blocks reached; delete the block
			if (! strlen($block['content']) || $blockPos >= $maxlengths['numProfileBlocks'])
				$profBlocks->delBlocks(array($block['blockid']));

			// have content, so save new content
			else {
				$profBlocks->saveBlocks(array(array(
					'blockid'		=> $block['blockid'],
					'blocktitle'	=> strlen($block['title']) ? $block['title'] : 'Untitled',
					'blockcontent'	=> $block['content'],
					'blockorder'	=> ++$blockPos,
					'permission'	=> $block['permission']
				)));
				scan_string_for_notables($block['content']);
			}
		}
	}

	$usersdb->prepare_query("UPDATE profile SET profileupdatetime = # WHERE userid = %", time(), $uid);

	$cache->remove("profile-$uid");

	$google->updateHash();

	if($uid != $userData['userid']){
		$reportaction = ABUSE_ACTION_PROFILE_EDIT;
		$reportreason = getPOSTval('reportreason', 'int');
		$reportsubject= getPOSTval('reportsubject');
		$reporttext   = getPOSTval('reporttext');

		$abuselog->addAbuse($uid, $reportaction, $reportreason, $reportsubject, $reporttext);

		$mods->adminlog("update profile", "Update user profile: userid $uid");

		header("location: /profile.php?uid=$uid");
		exit;
	}

	$msgs->addMsg("Updated. Check <a class=body href='/profile.php?uid=$uid'>your profile</a> to see the changes");
}






function editForumDetails(){
	global $userData, $user, $uid, $usersdb, $forums, $profile, $maxlengths, $forums, $isSigAdmin, $abuselog;

	if($userData['userid'] != $uid && !$isSigAdmin)
		die("You don't have permission to do this");


	$res = $usersdb->prepare_query("SELECT enablesignature, nsigniture, signiture FROM profile WHERE userid = %", $uid);
	$user += $res->fetchrow();

	$template = new template('profiles/manageprofile/editForumDetails');

	$template->set('menu', getMenu('forums'));
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
	$template->display();
	exit;
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

		$signiture = removeHTML(trim(substr($data['signiture'], 0, $maxlengths['signiture'])));
		$nsigniture = nl2br(wrap(parseHTML(smilies($signiture))));
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

		$abuselog->addAbuse($uid, $reportaction, $reportreason, $reportsubject, $reporttext);

		$reportreminder = getPOSTval('reportreminder', 'int');

		if($reportreminder){
			$message = 	"[url=manageprofile.php?section=forums&uid=$uid]Check/re-enable[/url] the signature for [url=profile.php?uid=$uid]" . getUserName($uid) . "[/url].\n\n" .
						"The report was for " . $abuselog->reasons[$reportreason] . ": $reportsubject\n[quote]" . $reporttext. "[/quote]";

			$usernotify->newNotify($userData['userid'], time() + $reportreminder, 'Signature Checkup', $message);
		}

		$mods->adminlog("update signature", "Update user signature: userid $uid");
	}

	$msgs->addMsg("Updated");
}






function editInterests(){
	global $userData, $uid, $usersdb, $configdb, $isProfileAdmin, $config;

	if($userData['userid'] != $uid)
		die("You don't have permission to do this");

	$interests = new category( $configdb, "interests");

	$res = $usersdb->prepare_query("SELECT interestid FROM userinterests WHERE userid = %", $uid);

	$userinterests = array();
	while($line = $res->fetchrow())
		$userinterests[$line['interestid']] = $line['interestid'];

	$cols = 5;

	$cats = $interests->makebranch(0,1); //only main categories

	$index = -1;
	foreach($cats as $item){
		$index++;
		$first = false;

		$i = 0;

		$subcats = $interests->makebranch($item['id'],1); //only main categories

		$n = count($subcats);
		$rows = ceil($n/$cols);
		$total = $rows * $cols;

		$str = "";
		for($i = 0; $i < $total; $i++){
			$col = $i%$cols;
			$row = floor($i/$cols);

			$j = $col*$rows + $row;

			if( $i % $cols == 0)
				$str .= "<tr>";

			if($j < $n){
				$item = $subcats[$j];

				$str .= "<td class=body>";
				$str .= makeCheckBox("check[$item[id]]", $item['name'], isset($userinterests[$item['id']]));
				$str .= "</td>";
			}

			if($i % $cols == $cols - 1)
				$str .= "</tr>\n";
		}
		if($i > 0)
			$str .= "</tr>\n";

		$checkList[$index] = $str;
	}

	$template = new template('profiles/manageprofile/editInterests');
	$template->set('menu', getMenu('interests'));
	$template->set('uid', $uid);
	$template->set('cols', $cols);
	$template->set('cats', $cats);
	$template->set('checkList', $checkList);
	$template->display();
	exit;
}

function updateInterests(){
	global $uid, $userData, $configdb, $usersdb, $cache, $msgs, $rubydb, $google;

	if($uid != $userData['userid'])
		return;

	if(!($check = getPOSTval('check', 'array')))
		return;

	if(!isset($check[0])) //purely to test if it is actually POSTed
		return;

	unset($check[0]);

	$interests = new category( $configdb, "interests");

	$usersdb->prepare_query("DELETE FROM userinterests WHERE userid = %", $uid);

	$set = array();
	$ids = array();
	foreach($check as $id => $v){
		$root = $interests->makeroot($id, false);

		foreach($root as $row){
			$set[$row['id']] = $usersdb->prepare("(%,#)", $uid, $row['id']);
			$ids[$row['id']] = $row['id'];
		}
	}

	if(count($set))
		$usersdb->query("INSERT IGNORE INTO userinterests (userid, interestid) VALUES " . implode(", ", $set));

	$cache->remove("userinterests-$uid");

	$google->updateHash();

	$userData['interests'] = implode(',', $ids);

	$msgs->addMsg("Updated");

	enqueue("Profile::Profile", "edit", $uid, array($uid));
}



function editSkins($editid = 0, $data = array()){
	global $uid, $userData, $user, $usersdb, $db, $config;

	if(!$user['plus'])
		return;

	$skintypes = array(
		'skin' => "Profile",
		'commentskin' => "Comments",
		'blogskin' => "Blog",
		'friendskin' => "Friends",
		'galleryskin' => "Gallery",
		);

	$parts = array(
		'Header',
		'headerbg' => 'Background',
		'headertext' => 'Text',
		'headerlink' => 'Link',
		'headerhover' => 'Link Hover',
		'Body',
		'bodybg' => 'Background',
		'bodybg2' => 'Alternating Background',
		'bodytext' => 'Text',
		'bodylink' => 'Link',
		'bodyhover' => 'Link Hover',
		'Other',
		'online' => 'Online',
		'offline' => 'Offline',
		);

//get skin choices
	$res = $usersdb->prepare_query("SELECT commentskin, blogskin, friendskin, galleryskin FROM users WHERE userid = %", $uid);
	$userskins = $res->fetchrow();

	$res = $usersdb->prepare_query("SELECT skin FROM profile WHERE userid = %", $uid);
	$userskins += $res->fetchrow();


//get current skins
	$res = $usersdb->prepare_query("SELECT * FROM profileskins WHERE userid = % ORDER BY name", $uid);

	$skins = array();
	while($line = $res->fetchrow()){
		$line['skindata'] = decodeSkin($line);
		$skins[$line['id']] = $line;
	}


//init data
	if($editid && isset($skins[$editid])){
		$editaction = "Update";
		$editname = $skins[$editid]['name'];
		$title = "Update";
		$editskin = $skins[$editid]['skindata'];
		$showcss = true;
	}else{
		$editaction = "Create";
		$editname = '';
		$title = "Create a";
		$editskin = array(
			'headerlink' => '',
			'headerhover' => '',
			'headerbg' => '',
			'headertext' => '',
			'bodylink' => '',
			'bodyhover' => '',
			'bodybg' => '',
			'bodybg2' => '',
			'bodytext' => '',
			'online' => '',
			'offline' => '',
			);
		$showcss = false;
	}

//overwrite if testing
	if($data){
		$editname = htmlentities($data['name']);
		$showcss = true;

		foreach($editskin as $k => $v){
			if(preg_match("/^[0-9A-Fa-f]{6}$/", $data[$k]))
				$editskin[$k] = $data[$k];
		}
	}

	incHeader();

//output the styles
	echo "<style>\n";

	foreach($skins as $id => $line)
		echo getCSS('#skin' . $id, $line['skindata']);

	if($showcss)
		echo getCSS('#skineditor', $editskin);

	echo "</style>\n\n";



	echo "<table align=center width=750>";

	echo "<tr><td class=body colspan=2>";
	echo getMenu('skins');
	echo "</td></tr>";

	echo "<tr><td class=header colspan=2 align=center>Skins</td></tr>";
	echo "<tr><td class=body valign=top>";

//choose skins
	echo "<table width=350>";
	echo "<tr><td class=header colspan=2 align=center>Choose Skins</td></tr>";

	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<input type=hidden name=section value=skins>";
	echo "<input type=hidden name=action value=chooseskins>";



	foreach($skintypes as $type => $name){
		echo "<tr>";
		echo "<td class=body>Choose a $name skin:</td>";
		echo "<td class=body>";
		echo "<select class=body name=data[$type]><option value=0>User Default" . make_select_list_col_key($skins, 'name', $userskins[$type]) . "</select>";
		echo "</td>";
		echo "</tr>";
	}
	echo "<tr><td class=body colspan=2 align=center><input class=body type=submit value='Update'></td></tr>";
	echo "</form>";
	echo "</table>";


	echo "<br><br>";

//manage skins
	echo "<table width=350>";
	echo "<tr><td class=header colspan=2 align=center>";
	echo "<table cellspacing=0 cellpadding=0 width=100%><tr>";
	echo "<td class=header width=33%></td>";
	echo "<td class=header width=33% align=center>Manage Skins</td>";
	echo "<td class=header width=33% align=right><a class=header href=$_SERVER[PHP_SELF]?section=skins>New Skin</a></td>";
	echo "</tr></table>";
	echo "</td></tr>";
	echo "</table>";

	echo "<div style=\"height: 540; width: 350; overflow: auto;\">";

	echo "<table width=100%>";

	foreach($skins as $skin){
		echo "<tr><td class=header>";
		echo "<table width=100% cellspacing=0 cellpadding=0><tr><td class=header>";
		echo "<a class=header href=$_SERVER[PHP_SELF]?section=skins&action=editskin&id=$skin[id]><b>$skin[name]</b></a>";
		echo "</td><td class=header align=right>";
		echo "<a class=header href=$_SERVER[PHP_SELF]?section=skins&action=editskin&id=$skin[id]>Edit</a> | ";
		echo "<a class=header href=\"javascript: confirmLink('$_SERVER[PHP_SELF]?section=skins&action=deleteskin&id=$skin[id]&k=" . makeKey($skin['id']) . "', 'delete this skin');\">Delete</a>";
		echo "</td></tr></table>";
		echo "</td></tr>";

		echo "<tr><td class=body align=right>";

	//preview each skin
		echo "<table id=skin$skin[id] width=100% cellspacing=0>";
		echo "<tr><td class=header>Header Preview:</td><td class=header>Text <a class=header href=#>Link</a></td>";
		echo "<td rowspan=3 class=body align=center>";
		echo "<table cellspacing=0>";
		echo "<tr><td class=online>Online</td></tr>";
		echo "<tr><td class=offline>Offline</td></tr>";
		echo "</table>";
		echo "</td>";
		echo "</tr>";
		echo "<tr><td class=body>Body Preview:</td><td class=body>Text <a class=body href=#>Link</a></td></tr>";
		echo "<tr><td class=body2>Alternative Body Preview:</td><td class=body2>Text <a class=body href=#>Link</td></tr>";
		echo "</table>";

		echo "</td></tr>";

		echo "<tr><td class=body>&nbsp;</td></tr>";

	}
	echo "</table>";

	echo "</div>";

	echo "</td>";

//new column
	echo "<td class=body>";

//skin editor!
	echo "<table id=skineditor align=center width=362 cellspacing=0>";
	echo "<form action=$_SERVER[PHP_SELF] method=post>";

//colour chooser
	echo "<tr><td class=body colspan=2 valign=top height=300>";
	echo "<div style=\"position:relative;\">";
	echo "<script src=$config[jsloc]color.js></script>";
	echo "<script>displayColors();</script>";
	echo "</div>";
	echo "</td></tr>";

	echo "<tr><td id=actiontitle class=header colspan=2 align=center>$title Skin</td></tr>";

//name
	echo "<tr><td class=body>Skin title:</td>";
	echo "<td class=body><input type=text class=body name=data[name] value=\"" . htmlentities($editname) . "\" maxlength=32></td></tr>";
	echo "<tr><td class=body colspan=2>";
	echo "All colours must be in RGB HEX values. They may include, but are not limited to, any colour generated by the colour chart above.";
	echo "</td></tr>\n";

//colours
	foreach($parts as $k => $v){
		if(is_int($k)){
			echo "<tr><td class=header colspan=2 align=center>$v</td></tr>\n";
		}else{
			echo "<tr><td class=body>$v:</td>";
			echo "<td class=body>";
			echo "<input type=text class=body name=data[$k] value=\"" . htmlentities($editskin[$k]) . "\" maxlength=6 size=7 onFocus=\"registerFocus(this, 'edit$k');\" onChange=\"if(/^[0-9A-Fa-f]{6}$/.test(this.value)) document.getElementById('edit$k').style.backgroundColor = '#' + this.value;\">&nbsp;";
			echo "<span id=edit$k style=\"width: 50px; height: 20px; background-color: " . htmlentities($editskin[$k]) . ";\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>";
			echo "</td></tr>\n";
		}
	}

	echo "<tr><td class=body colspan=2>&nbsp;</td></tr>";

//preview
	echo "<tr><td class=header>Header Preview:</td><td class=header>Text <a class=header href=#>Link</a></td></tr>";
	echo "<tr><td class=body>Body Preview:</td><td class=body>Text <a class=body href=#>Link</a></td></tr>";
	echo "<tr><td class=body2>Alternative Body Preview:</td><td class=body2>Text <a class=body href=#>Link</td></tr>";
	echo "<tr><td class=online>Online</td><td class=offline>Offline</td></tr>";

//actions
	echo "<tr><td class=body align=center colspan=2>";
	echo "<input type=hidden name=id value=$editid>";
	echo "<input type=hidden name=section value=skins>";
	echo "<input class=body type=submit name=action value=Test>";
	echo "<input class=body type=submit name=action value=$editaction>";
	echo "<input class=body type=submit name=action value=Cancel>";
	echo "</td></tr>";

	echo "</form>";
	echo "</table>";

	echo "</td></tr>";
	echo "</table>";

	incFooter();
	exit;
}

function chooseskins($data){
	global $uid, $userData, $user, $usersdb, $db, $config, $cache, $msgs;

	if($uid != $userData['userid'] || !$user['plus'])
		return;

	$skintypes = array(
		'skin' => 0,
		'commentskin' => 0,
		'blogskin' => 0,
		'friendskin' => 0,
		'galleryskin' => 0,
		);

	$data = setDefaults($data, $skintypes);

	$res = $usersdb->prepare_query("SELECT id FROM profileskins WHERE id IN (#) && userid = %", $data, $uid);

	$validskins = array();
	while($line = $res->fetchrow())
		$validskins[$line['id']] = $line['id'];

	foreach($data as $k => $v)
		if(!isset($validskins[$v]))
			$data[$k] = 0;

	$usersdb->prepare_query("UPDATE profile SET skin = # WHERE userid = %", $data['skin'], $uid);
	$usersdb->prepare_query("UPDATE users SET commentskin = #, blogskin = #, friendskin = #, galleryskin = # WHERE userid = %", $data['commentskin'], $data['blogskin'], $data['friendskin'], $data['galleryskin'], $uid);

	$cache->remove("userinfo-$uid");
	$cache->remove("profile-$uid");

	$msgs->addMsg("Skin choices updated.");
}

function insertSkin($data){
	global $uid, $usersdb, $msgs;

	$data2 = encodeSkin($data);

	if(!$data2){
		$msgs->addMsg("You must fill out all boxes with valid RGB HEX colors");
		editSkins(0, $data); //exit
	}

	if(empty($data['name'])){
		$msgs->addMsg("You must give it a name");
		editSkins(0, $data); //exit
	}
	

	$id = $usersdb->getSeqID($uid, DB_AREA_PROFILE_SKINS, DB_AREA_PROFILE_SKINS_START);

	$params[] = $uid;
	$params[] = $id;
	$params[] = $data['name'];
	foreach ($data2 as $key => $value) {
		$set[] = "$key = ?";
		$params[] = $value;
	}
	
	$usersdb->prepare_array_query("INSERT INTO profileskins SET userid = %, id = #, name = ?, " . implode(', ', $set), $params);
}

function updateSkin($id, $data){
	global $uid, $usersdb, $msgs, $cache;

	$data2 = encodeSkin($data);

	if(!$data2){
		$msgs->addMsg("You must fill out all boxes with valid RGB HEX colors");
		editSkins($id, $data); //exit
	}

	if(empty($data['name'])){
		$msgs->addMsg("You must give it a name");
		editSkins($id, $data); //exit
	}

	$params[] = $data['name'];
	foreach ($data2 as $key => $value) {
		$set[] = "$key = ?";
		$params[] = $value;
	}
	$params[] = $uid;
	$params[] = $id;
	
	$usersdb->prepare_array_query("UPDATE profileskins SET name = ?, " . implode(', ', $set) . " WHERE userid = % && id = #", $params);
	
	$cache->remove("profileskin-$uid/$id");
}

function deleteskin($id){
	global $db, $uid, $cache;

	$db->prepare_query("DELETE FROM profileskins WHERE id = # && userid = #", $id, $uid);
	$cache->remove("profileskin-$uid/$id");
}


function makeVisibilitySelectOptions($visibility, $allowShowAll=true)
{
	$options = getVisibilityOptions();
	$html = "";
	foreach($options as $value => $description)
	{
		if ($value == VISIBILITY_ALL && !$allowShowAll)
			continue;
			
		if ($value == $visibility)
			$selected = "selected";
		else
			$selected = "";
		$html .= "<option value='$value' $selected>$description</option>";
	}
	
	return $html;
}
