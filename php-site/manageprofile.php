<?

	$login=1;

	require_once("include/general.lib.php");

	$isAdmin = $mods->isAdmin($userData['userid'],'editprofile');

	$uid = ($isAdmin ? getREQval('uid', 'int', $userData['userid']) : $userData['userid']);

	$maxlengths['tagline'] = 300;
	$maxlengths['signiture'] = 1000;
	$maxlengths['numProfileBlocks'] = 3;
	$maxlengths['blockLength'] = 10000;


	$locations = new category( $configdb, "locs");
	$interests = new category( $configdb, "interests");

	$section = getREQval('section');


	$result = $usersdb->prepare_query("SELECT userid, age, sex, premiumexpiry, dob, forumrank FROM users WHERE userid=%", $uid);
	$user = $result->fetchrow();
	$user['username'] = getUserName($uid);

	if(!$user)
		die("Bad User");


	$plus = $user['premiumexpiry'] > time();

	if($plus){
//		$maxlengths['numProfileBlocks'] = 5;
		$maxlengths['blockLength'] = 20000;
	}

	if($action && $section && (!isset($HTTP_REFERER) || strpos($HTTP_REFERER, $_SERVER['PHP_SELF']) !== false)){

		switch($section){
			case "basics":
				if(!($data = getPOSTval('data', 'array')))
					break;

				if(!($prof = getPOSTval('prof', 'array')))
					break;

				$commands = array();

				if(!isset($data['month']) || !is_numeric($data['month']) || $data['month']<=0 || $data['month']>12)
					$msgs->addMsg("Invalid Month");
				elseif(!isset($data['day']) || !is_numeric($data['day']) || $data['day']<=0 || $data['month']>31)
					$msgs->addMsg("Invalid Day");
				elseif(!isset($data['year']) || !is_numeric($data['year']))
					$msgs->addMsg("Invalid Year");
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

				$set = array();

				if($plus){
					$profileskin = getPOSTval('profileskin', 'int');
					$commentskin = getPOSTval('commentskin', 'int');
					$blogskin = getPOSTval('blogskin', 'int');
					$friendskin = getPOSTval('friendskin', 'int');
					$galleryskin = getPOSTval('galleryskin', 'int');

					$profileskins = array(&$profileskin, &$commentskin, &$blogskin, &$friendskin, &$galleryskin);

					if($profileskins){

						$res = $db->prepare_query("SELECT DISTINCT id FROM profileskins WHERE id IN (#) && userid IN (0,#)", $profileskins, $uid);

						$validskins = array();
						while ($result = $res->fetchrow())
							$validskins[] = $result['id'];

						foreach ($profileskins as &$skinid)
						{
							if (!in_array($skinid, $validskins))
								$skinid = 0;
						}
					}

					// ugly, but set goes to profile and commands goes to users.
					$set[] = $usersdb->prepare("skin = #", $profileskin);
					$commands[] = $usersdb->prepare("commentskin = #, blogskin = #, friendskin = #, galleryskin = #", $commentskin, $blogskin, $friendskin, $galleryskin);
				}



				$usersdb->query("UPDATE users SET " . implode(", ", $commands) . $usersdb->prepare(" WHERE userid=%", $uid));


				$db->prepare_query("INSERT IGNORE INTO newestprofile SET userid = %, username = ?, time= #, age = #, sex = ?", $user['userid'], $user['username'], time(), $user['age'], $user['sex']);

				if(is_array($prof) && count($prof) == count($profile))
					$set[] = $usersdb->prepare("profile = ?", encodeProfile($prof) );

				if(isset($data['tagline'])){

					$tagline = removeHTML(trim(substr($data['tagline'], 0, $maxlengths['tagline'])));

					$pos = 0;
					for($i=0;$i<5;$i++)
						if($pos+1 < strlen($tagline))
							$pos = strpos($tagline, "\n", $pos+1);
					if($pos)
						$tagline = substr($tagline, 0, $pos-1);

					$ntagline = nl2br(wrap(smilies($tagline)));

					$set[] = $db->prepare("tagline = ?", $tagline);
					$set[] = $db->prepare("ntagline = ?", $ntagline);
				}

				if(isset($data['icq'])){
					$data['icq'] = removeHTML($data['icq']);
					$set[] = $db->prepare("icq = #", $data['icq']);
				}
				if(isset($data['yahoo'])){
					$data['yahoo'] = removeHTML($data['yahoo']);
					$set[] = $db->prepare("yahoo = ?", $data['yahoo']);
				}
				if(isset($data['msn'])){
					$data['msn'] = removeHTML($data['msn']);
					$set[] = $db->prepare("msn = ?", $data['msn']);
				}
				if(isset($data['aim'])){
					$data['aim'] = removeHTML($data['aim']);
					$set[] = $db->prepare("aim = ?", $data['aim']);
				}

				$set[] = $db->prepare("profileupdatetime = ?", time());


				$usersdb->query("UPDATE profile SET " . implode(", ", $set) . $usersdb->prepare(" WHERE userid=%", $uid));

				if($isAdmin && $uid != $userData['userid']){
					$reportaction = ABUSE_ACTION_PROFILE_EDIT;
					$reportreason = getPOSTval('reportreason', 'int');
					$reportsubject= getPOSTval('reportsubject');
					$reporttext   = getPOSTval('reporttext');

					$abuselog->addAbuse($uid, $reportaction, $reportreason, $reportsubject, $reporttext);
				}

				$cache->remove("profile-$uid");
				$cache->remove("userinfo-$uid");
				$cache->remove("userprefs-$uid");
				$cache->remove("tagline-$uid");

				if($uid != $userData['userid']){
					$mods->adminlog("update profile", "Update user profile: userid $uid");
					header("location: profile.php?uid=$uid");
					exit;
				}

				$msgs->addMsg("Updated. Check <a class=body href='/profile.php?uid=$uid'>your profile</a> to see the changes");

				break;

			case "details":
				//if the user is using the fck editor they need more characters
				//to account for all the hidden html.
				$length_offset = 0;
				$oFCKeditor = new FCKeditor('blank') ;
				if(!$userData['bbcode_editor'] && $oFCKeditor->IsCompatible())
					$length_offset = 2000;

				$titles = getPOSTval('blockTitle', 'array', array());
				$permissions = getPOSTval('permission', 'array', array());
				$positions = getPOSTval('blockOrder', 'array', array());

				$newBlocks = array();
				foreach ($titles as $blockid => $title) {
					$title = removeHTML(trim($title));
					$content = getPOSTval("blockTxt_${blockid}", 'string', '');
					$content = html_sanitizer::sanitize(substr(trim($content), 0, $maxlengths['blockLength'] + $length_offset));
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
				$existingBlocks = $profBlocks->getBlocks();

				foreach ($newBlocks as $block) {
					// this is a new block (since blockid begins with zero)
					if ($block['blockid']{0} == '0') {
						// no content or maximum blocks reached; skip
						if (! strlen($block['content']) || count($existingBlocks) == $maxlengths['numProfileBlocks'])
							continue;

						// have content, so add block
						$profBlocks->saveBlocks(array(array(
							'blocktitle'	=> strlen($block['title']) ? $block['title'] : 'Untitled',
							'blockcontent'	=> $block['content'],
							'blockorder'	=> ++$blockPos,
							'permission'	=> $block['permission']
						)));
						$existingBlocks = $profBlocks->getBlocks();
					}

					// this is an update to an existing block
					else {
						// block does not exist; skip
						if (! isset($existingBlocks[$block['blockid']]))
							continue;

						// no content, delete the block
						if (! strlen($block['content'])) {
							$profBlocks->delBlocks(array($block['blockid']));
							continue;
						}

						// have content, so save new content
						else {
							$profBlocks->saveBlocks(array(array(
								'blockid'		=> $block['blockid'],
								'blocktitle'	=> strlen($block['title']) ? $block['title'] : 'Untitled',
								'blockcontent'	=> $block['content'],
								'blockorder'	=> ++$blockPos,
								'permission'	=> $block['permission']
							)));
							$existingBlocks = $profBlocks->getBlocks();
						}
					}
				}

				$usersdb->prepare_query("UPDATE profile SET profileupdatetime = # WHERE userid = %", time(), $uid);

				if($isAdmin && $uid != $userData['userid']){
					$reportaction = ABUSE_ACTION_PROFILE_EDIT;
					$reportreason = getPOSTval('reportreason', 'int');
					$reportsubject= getPOSTval('reportsubject');
					$reporttext   = getPOSTval('reporttext');

					$abuselog->addAbuse($uid, $reportaction, $reportreason, $reportsubject, $reporttext);
				}

				$cache->remove("profile-$uid");

				if($uid != $userData['userid']){
					$mods->adminlog("update profile", "Update user profile: userid $uid");
					header("location: profile.php?uid=$uid");
					exit;
				}

				$msgs->addMsg("Updated. Check <a class=body href='/profile.php?uid=$uid'>your profile</a> to see the changes");

				break;

			case "interests":
				if($uid != $userData['userid'])
					break;

				if(!($check = getPOSTval('check', 'array')))
					break;

				if(!isset($check[0])) //purely to test if it is actually POSTed
					break;

				unset($check[0]);

				$usersdb->prepare_query("DELETE FROM userinterests WHERE userid=%", $uid);

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

				$userData['interests'] = implode(',', $ids);

				$msgs->addMsg("Updated");

				break;

			case "forums":
				if(!($data = getPOSTval('data', 'array')))
					break;

				if(isset($data['signiture'])){
					if($isAdmin)
						$set[] = $db->prepare("enablesignature = ?", (getPOSTval('enablesignature', 'bool') ? "y" : "n") );

					$signiture = removeHTML(trim(substr($data['signiture'], 0, $maxlengths['signiture'])));
					$nsigniture = nl2br(wrap(parseHTML(smilies($signiture))));
					$set[] = $db->prepare("signiture = ?", $signiture);
					$set[] = $db->prepare("nsigniture = ?", $nsigniture);


					$usersdb->query("UPDATE profile SET " . implode(", ", $set) . $usersdb->prepare(" WHERE userid=%", $uid));


					$cache->remove("forumusersigs-$uid");
				}

				if($plus){

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
							break;
					}

					$cache->remove("userinfo-$uid");
				}

				if($isAdmin && $uid != $userData['userid']){
					$reportaction = ABUSE_ACTION_SIG_EDIT;
					$reportreason = getPOSTval('reportreason', 'int');
					$reportsubject= getPOSTval('reportsubject');
					$reporttext   = getPOSTval('reporttext');

					$abuselog->addAbuse($uid, $reportaction, $reportreason, $reportsubject, $reporttext);

					$reportreminder = getPOSTval('reportreminder', 'int');

					if($reportreminder){

						$message = 	"[url=manageprofile.php?section=forums&uid=$uid]Check/re-enable[/url] the signature for [url=profile.php?uid=$uid]" . $user['username'] . "[/url].\n\n" .
									"The report was for " . $abuselog->reasons[$reportreason] . ": $reportsubject\n[quote]" . $reporttext. "[/quote]";

						$usernotify->newNotify($userData['userid'], time() + $reportreminder, 'Signature Checkup', $message);
					}
				}

				if($uid != $userData['userid'])
					$mods->adminlog("update signature", "Update user signature: userid $uid");

				$msgs->addMsg("Updated");

				break;
		}
	}



	switch($section){
		case 'details':		editDetails();
		case 'forums':		editForumDetails();
		case 'interests':	editInterests();

		case 'basics':
		default:			editBasics();
	}



function editBasics(){
	global $userData, $uid, $db, $usersdb, $locations, $profile, $maxlengths, $isAdmin, $config, $abuselog, $config;

	$result = $usersdb->query("SELECT dob, loc, forumrank, posts, premiumexpiry, blogskin, commentskin, friendskin, galleryskin FROM users" . $usersdb->prepare(" WHERE userid=%", $uid));
	$user = $result->fetchrow();

	$result = $usersdb->query("SELECT icq, msn, yahoo, aim, tagline, skin, profile FROM profile" . $usersdb->prepare(" WHERE userid=%", $uid));
	$user += $result->fetchrow();

	$prof = decodeProfile($user['profile']);

	for($i=1;$i<=12;$i++)
		$months[$i] = date("F", mktime(0,0,0,$i,1,0));




	$template = new template('profiles/manageprofile/editBasics');
	$template->set('jsSetLength', "<script>
function setLength(field, maxlimit, output) {
	if(field.value.length > maxlimit)
		field.value = field.value.substring(0, maxlimit);
	putinnerHTML(output, \"Length: \" + field.value.length + \" / \" + maxlimit );
}
</script>");

	$template->set('uidGetString', ($uid != $userData['userid'] ? "&uid=$uid" : ""));
	$template->set('uid', $uid);
	$template->set('user', $user);
	$template->set('selectMonth', make_select_list_key($months,gmdate("m",$user['dob'])));
	$template->set('selectDay', make_select_list(range(1,31),gmdate("j",$user['dob'])));
	$template->set('selectYear', make_select_list(array_reverse(range(date("Y")-$config['maxAge'],date("Y")-$config['minAge'])),gmdate("Y",$user['dob'])));
	$template->set('selectLocation', makeCatSelect($locations->makeBranch(),$user['loc']));
	$template->set('profile', $profile);
	foreach ($profile as $qnum => $val) {
		$selectAnswers[$qnum] = make_select_list_key($val['answers'], $prof[$qnum]);
	}
	$template->set('selectAnswers', $selectAnswers);
	$template->set('taglineLength', strlen($user['tagline']));
	$template->set('maxlengths', $maxlengths);
	$template->set('time', time());
	$template->set('userData', $userData);

	if($user['premiumexpiry'] > time()){

		$res = $db->prepare_query("SELECT id, name FROM profileskins WHERE userid = %", $uid); // userid IN (0,#) ORDER BY userid, name
		$profileskins = array();
		while($line = $res->fetchrow())
			$profileskins[$line['id']] = $line['name'];

		sortCols($profileskins, SORT_ASC, SORT_CASESTR, 'name');//, SORT_ASC, SORT_NUMERIC, 'userid'

		$template->set('selectSkin', make_select_list_key($profileskins, $user['skin']));
		$template->set('selectCommentsSkin', make_select_list_key($profileskins, $user['commentskin']));
		$template->set('selectBlogSkin', make_select_list_key($profileskins, $user['blogskin']));
		$template->set('selectFriendsSkin', make_select_list_key($profileskins, $user['friendskin']));
		$template->set('selectGallerySkin', make_select_list_key($profileskins, $user['galleryskin']));
	}
	if($isAdmin && $uid != $userData['userid']){
		$template->set('displayAdmin', true);
		$template->set('selectAbuseReason', make_select_list_key($abuselog->reasons));
		$template->set('abuseJavaScript', "if(document.editbox.reportreason.selectedIndex==0 || document.editbox.reportsubject.value==''){alert('You must specify a reason.'); return false;}");
	} else {
		$template->set('displayAdmin', false);
	}
	$template->display();

	exit;
}

function editDetails(){
	global $userData, $uid, $locations, $profile, $maxlengths, $isAdmin, $abuselog;

	$template = new template('profiles/manageprofile/editDetails');

	$template->set('jsSetLength', "<script>
function setLength(field, maxlimit, output) {
	if(field.value.length > maxlimit)
		field.value = field.value.substring(0, maxlimit);
	putinnerHTML(output, \"Length: \" + field.value.length + \" / \" + maxlimit );
}
</script>");

	$template->setMultiple(array(
		'uid'			=> $uid,
		'userData'		=> $userData,
		'maxlengths'	=> $maxlengths
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
		$block['editBox'] = editBoxStr($block['blockcontent'], "blockTxt_{$block['blockid']}", "block_{$block['blockid']}", 300, 700, $maxlengths['blockLength']);
		$prefBlocks[ $block['blockid'] ] = $block;
	}
	$template->set('blocks', $prefBlocks);

	if ($isAdmin && $uid != $userData['userid']) {
		$template->set('displayAdmin', true);
		$template->set('selectAbuseReason', make_select_list_key($abuselog->reasons));
		$template->set('abuseJavaScript', "if(document.editbox.reportreason.selectedIndex==0 || document.editbox.reportsubject.value==''){alert('You must specify a reason.'); return false;}");
	}
	else
		$template->set('displayAdmin', false);

	$template->display();
	exit;

}


function editForumDetails(){
	global $userData, $uid, $usersdb, $forums, $locations, $profile, $maxlengths, $forums, $isAdmin, $abuselog;


	$res = $usersdb->prepare_query("SELECT forumrank, posts, premiumexpiry FROM users WHERE userid = %", $uid);
	$user = $res->fetchrow();

	$res = $usersdb->prepare_query("SELECT enablesignature, nsigniture, signiture FROM profile WHERE userid = %", $uid);
	$user += $res->fetchrow();

	$template = new template('profiles/manageprofile/editForumDetails');

	$template->set('jsSetLength', "<script>
function setLength(field, maxlimit, output) {
	if(field.value.length > maxlimit)
		field.value = field.value.substring(0, maxlimit);
	putinnerHTML(output, \"Length: \" + field.value.length + \" / \" + maxlimit );
}
</script>");
	$template->set('uidGetString', ($uid != $userData['userid'] ? "&uid=$uid" : ""));
	$template->set('uid', $uid);
	$template->set('user', $user);
	$template->set('userData', $userData);
	$template->set('isAdmin', $isAdmin);
	$template->set('checkEnableSignature', makeCheckBox("enablesignature", "Enable Signature", $user['enablesignature'] == 'y'));
	$template->set('allowedSignature', ($user['enablesignature'] == 'y' || $isAdmin));
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
	$template->set('time', time());

	if($isAdmin && $uid != $userData['userid']){
		$template->set('displayAdmin', true);
		
		$reminders = $forums->mutelength;
		unset($reminders[0]);

		$template->set('selectReminder', make_select_list_key($reminders));
		$template->set('selectAbuseReason', make_select_list_key($abuselog->reasons));
		$template->set('abuseJavaScript', "if(document.editbox.reportreason.selectedIndex==0 || document.editbox.reportsubject.value==''){alert('You must specify a reason.'); return false;}");
	} else {
		$template->set('displayAdmin', false);
	}
	$template->display();
	exit;
}


function editInterests(){
	global $userData, $uid, $usersdb, $interests, $isAdmin, $config;

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

		ob_start();
		for($i = 0; $i < $total; $i++){
			$col = $i%$cols;
			$row = floor($i/$cols);

			$j = $col*$rows + $row;

			if( $i % $cols == 0)
				echo "<tr>";

			if($j < $n){
				$item = $subcats[$j];

				echo "<td class=body>";
				echo makeCheckBox("check[$item[id]]", $item['name'], isset($userinterests[$item['id']]));
				echo "</td>";
			}

			if($i % $cols == $cols - 1)
				echo "</tr>\n";
		}
		if($i > 0)
			echo "</tr>\n";
		$checkList[$index] = ob_get_contents();
		ob_end_clean();
	}

//*/
	$template = new template('profiles/manageprofile/editInterests');
	$template->set('uidGetString', ($uid != $userData['userid'] ? "&uid=$uid" : ""));
	$template->set('uid', $uid);
	$template->set('userData', $userData);
	$template->set('cols', $cols);
	$template->set('cats', $cats);
	$template->set('checkList', $checkList);
	$template->display();
	exit;
}

