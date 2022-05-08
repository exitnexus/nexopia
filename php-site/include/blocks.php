<?
function incPollBlock($side){
	global $userData, $config, $cache, $polls;

	if(!$userData['loggedIn'])
		return;

	$poll = $polls->getPoll();

	if(!$poll)
		return;

	$voted = $polls->pollVoted($poll['id']);
	$template =  new template("include/blocks/poll_block");

	if(!$voted){
		$poll['key'] = makeKey($poll['id']);

	}else{
		$maxval=0;
		foreach($poll['answers'] as $ans)
			if($ans['votes']>$maxval)
				$maxval = $ans['votes'];

		foreach($poll['answers'] as &$ans){
			$width = $poll['tvotes']==0 ? 1 : (int)$ans['votes']*$config['maxpollwidth']/$maxval;
			$percent = number_format($poll['tvotes']==0 ? 0 : $ans["votes"]/$poll['tvotes']*100,1);
			$ans['width'] = $width;
		}

	}

	$template->set('voted', $voted);
	$template->set("poll", $poll);
	$template->set("config", $config);
	$block_contents = $template->toString();
	blockContainer('Polls',$side, $block_contents);
}

function incBookmarksBlock($side){
	global $userData,$config,$db;

	if(!$userData['loggedIn'])
		return;

	$res = $db->prepare_query("SELECT id,name,url FROM bookmarks WHERE userid = # ORDER BY name", $userData['userid']);

	openBlock('Bookmarks',$side);

	echo "<table width=100%>\n";
	echo "<tr><td class=header><b><a href=\"/bookmarks.php\">Bookmarks</a></b></td></tr>";
	while($line = $res->fetchrow())
		echo "<tr><td class=side><a href=\"$line[url]\" target=_blank>$line[name]</a></td></tr>\n";
	echo "</table>\n";

	closeBlock();
}

function incSortBlock($side){
	global $userData, $sort, $config, $usersdb, $configdb, $requestType, $requestParams;

	// get values for the output template for all the user search options
	$menuOptions = new userSearchMenuOptions(true, 'incSortBlock', $requestType, $requestParams);

	$template =  new template("include/blocks/sort_block");
	$template->set('minage', $menuOptions->searchMinAge);
	$template->set('maxage', $menuOptions->searchMaxAge);
	$template->set('user', $menuOptions->searchName);
	$template->set('sex_select_list', $menuOptions->sexSelect);
	$template->set("loc_select_list", $menuOptions->locationSelect);
	$template->set("interest_select_list", $menuOptions->interestSelect);
	$template->set("activity_select_list",  $menuOptions->activitySelect);
	$template->set("picture_select_list", $menuOptions->pictureSelect);
	$template->set("sexuality_select_list", $menuOptions->sexualitySelect);
	$template->set("single_only_checkbox", $menuOptions->singleCheck);
	$template->set("show_list_checkbox", $menuOptions->listCheck);
	$template->set("has_plus",$userData['loggedIn'] && $userData['premium'] );
	$block_contents = $template->toString();

	blockContainer('User Search', $side, $block_contents);
}

function incMsgBlock($side){
	global $userData, $messaging, $cache;
	$template =  new template("include/blocks/msg_block");
	if(!$userData['loggedIn'])
		return;

	$newmsgs = array();
	if($userData['newmsgs']>0){

		$newmsgs = $cache->get("newmsglist-$userData[userid]");

		if($newmsgs === false){

			$res = $messaging->db->prepare_query("SELECT id, `from`, fromname, subject, date FROM msgs WHERE userid = % && folder = # && status='new'", $userData['userid'], MSG_INBOX);

			$newmsgs = array();
			while($line = $res->fetchrow())
				$newmsgs[] = $line;

			$cache->put("newmsglist-$userData[userid]", $newmsgs, 3600);
		}

		if(count($newmsgs)){
			foreach($newmsgs as &$line){
				if(strlen($line['subject']) <= 20)
					$subject = $line['subject'];
				else
					$subject = substr($line['subject'],0,18) . "...";
				$line['subject'] = $subject;

			}
		}
	}
	$template->set('newmsg_count', count($newmsgs));
	$template->set('newmsgs', $newmsgs);

	$block_contents = $template->toString();
	blockContainer('Messages',$side, $block_contents);

}


function incFriendsBlock($side){
	global $userData,$config;
	$template =  new template("include/blocks/friends_block");
	if(!$userData['loggedIn'])
		return;

	$friends = $userData['friends'];
	$friendnames = getUserName($friends);


	natcasesort($friendnames);

	$template->set('friendsonline', $userData['friendsonline']);
	$template->set('friends', $friendnames);
	$block_contents = $template->toString();
	blockContainer('Friends',$side, $block_contents);
}

function incModBlock($side){
	global $userData, $mods, $cache;

	if(!$userData['loggedIn'])
		return;

//	print_r(isMod($userData['userid']));

	if(!$mods->isMod($userData['userid']))
		return;

	function getAdminsOnline(){
		global $mods;

		$moduids = $mods->getAdmins('visible');

		$users = getUserInfo($moduids);

		$rows = array();
		foreach($users as $line)
			if($line['online'] == 'y')
				$rows[$line['userid']] = $line['username'];

		uasort($rows, 'strcasecmp');

		return $rows;
	}

	function getNumModsOnline(){
		global $mods;

		$moduids = $mods->getMods();


		$users = getUserInfo($moduids);

		$count = 0;
		foreach($users as $line)
			if($line['online'] == 'y')
				$count++;

		return $count;
	}

	function getGlobalModsOnline(){
		global $forums, $cache, $mods;

		$uids = $cache->get("globalmods");

		if(!$uids){
			$res = $forums->db->prepare_query("SELECT userid FROM forummods WHERE forumid = 0");

			$uids = array();
			while($line = $res->fetchrow())
				$uids[$line['userid']] = $line['userid'];

			$cache->put("globalmods", $uids, 3600);
		}

		$adminuids = $mods->getAdmins('visible');

		foreach($adminuids as $id)
			if(isset($uids[$id]))
				unset($uids[$id]);

		$users = getUserInfo($uids);

		$rows = array();
		foreach($users as $line)
			if($line['online'] == 'y')
				$rows[$line['userid']] = $line['username'];
		uasort($rows, 'strcasecmp');

		return $rows;
	}

	$adminsonline = $cache->get('adminsonline',60,'getAdminsOnline', 0);
	$modsonline = $cache->get('modsonline',60,'getNumModsOnline', 0) - count($adminsonline);
	$globalmodsonline = $cache->get('gmodsonline',60,'getGlobalModsOnline');

	foreach($adminsonline as $uid => $username)
		unset($globalmodsonline[$uid]);

	$moditemcounts = $mods->getModItemCounts();

	$template =  new template("include/blocks/mod_block");

	$types = array();
	foreach ($moditemcounts as $type => $num) {
//		if ($num > 0)
			$types[$type] = array(
				'nbsp'		=> str_repeat('&nbsp;',	5 - strlen($num)),
				'num'		=> $num,
				'modtype'	=> $mods->modtypes[$type]
			);
	}

	$template->set('types_count', count($types));
	$template->set('types', $types);

	$template->set('modsonline', $modsonline);
	$template->set('adminsonline_count', count($adminsonline));
	$template->set('adminsonline', $adminsonline);
	$template->set('globalmodsonline', $globalmodsonline);
	$template->set('globalmodsonline_count', count($globalmodsonline));
	$block_contents = $template->toString();
	blockContainer('Moderator',$side, $block_contents);
}

function incLoginBlock($side){
	global $userData;

	if($userData['loggedIn'])
		return;
	$template = new template('include/blocks/login_block');
	$template->set('secure_session_checkbox', makeCheckBox('lockip', " Secure Session", false));
	$template->set('secure_session_checkbox',  makeCheckBox('lockip', " Secure Session", false));
	$template->set('remember_me_checkbox',makeCheckBox('cachedlogin', " Remember Me",  false) );
	$block_content = $template->toString();
	blockContainer('Login', $side, $block_content);
}

function incSkinBlock($side){
	global $skins,$skin;

	openBlock('Skin',$side);
	echo "<table><form action=$_SERVER[PHP_SELF] method=post>";
	echo "<tr><td class=side><select name=newskin>" . make_select_list_key($skins,$skin) ."</select><input type=submit name=chooseskin value=Go></td></tr>";
	echo "</form></table>";
	closeBlock();
}

function incActiveForumBlock($side){
	global $cache;

	function getActiveForumThreads(){
		global $forums;
		$res = $forums->db->prepare_query("SELECT forumthreads.id,forumthreads.title FROM forumthreads,forums WHERE forums.id=forumthreads.forumid && forums.official='y' && forums.public = 'y' && forumthreads.time > '" . (time() - 1800) . "' ORDER BY forumthreads.time DESC LIMIT 5");

		$rows = array();
		while($line = $res->fetchrow())
			$rows[$line['id']] = $line['title'];
		return $rows;
	}

	$rows = $cache->hdget('activethread',30,'getActiveForumThreads');

	$template = new template('include/blocks/active_forum_block');

	foreach($rows as $id => &$title)
	 	$title = wrap($title,15);
	$template->set('rows', $rows);
	$block_contents = $template->toString();

	blockContainer('Recent Posts', $side, $block_contents);
}


function incSubscribedThreadsBlock($side){
	global $userData, $forums;

	if(!$userData['loggedIn'] || $userData['posts'] == 0)
		return;



	$res = $forums->db->prepare_query("SELECT forumthreads.id,forumthreads.title FROM forumread,forumthreads WHERE forumread.subscribe='y' && forumread.userid = # && forumread.threadid=forumthreads.id && forumread.time < forumthreads.time", $userData['userid']);
	$lines = $res->fetchrowset();

	$template = new template('include/blocks/subscriptions_block');
	foreach ($lines as &$line)
		$line['title'] = wrap($line['title'],20);
	$template->set('lines', $lines);
	$block_contents = $template->toString();
	blockContainer('Subscriptions', $side, $block_contents);

}

function incNewestMembersBlock($side){
	global $userData, $cache, $db;

	$queryParts = Array();
	$queryParams = Array();
	$mcparams = array();
	$query = "SELECT userid FROM newestusers";
	$queryParams[0] = &$query;

	$queryParts[0] = "sex = ?";
	$queryParams[1] = $userData['defaultsex'];
	$mcparams[0] = "sex:{$userData['defaultsex']}";

	$queryParts[] = "age IN (#)";
	$queryParams[] = range($userData['defaultminage'], $userData['defaultmaxage']);
	$mcparams[] = 'age:' . implode(',', range($userData['defaultminage'], $userData['defaultmaxage']));

	$query .= " WHERE " . implode(" && ", $queryParts);

	$query .= " ORDER BY id DESC LIMIT 5";

	$cachekey = 'search-new-user-' . implode('-', $mcparams);
	if (!($resultSet = $cache->get($cachekey)))
	{
		$query = call_user_func_array(Array(&$db, 'prepare'), $queryParams);

		$queryResult = $db->query($query);
		$resultSet = Array();
		while($row = $queryResult->fetchrow())
			$resultSet[] = $row['userid'];

		$cache->put($cachekey, $resultSet, 30);
	}

	$users = getUserName($resultSet);

	$template = new template('include/blocks/list_users_block');
	$template->set('users', $users);
	$block_contents = $template->toString();
	blockContainer('New Members', $side, $block_contents);
}

function incRecentUpdateProfileBlock($side){
	global $userData, $cache, $db;

	$queryParts = Array();
	$queryParams = Array();
	$mcparams = array();
	$query = "SELECT userid FROM newestprofile";
	$queryParams[0] = &$query;

	$queryParts[0] = "sex = ?";
	$queryParams[1] = $userData['defaultsex'];
	$mcparams[0] = "sex:{$userData['defaultsex']}";

	$queryParts[] = "age IN (#)";
	$queryParams[] = range($userData['defaultminage'], $userData['defaultmaxage']);
	$mcparams[] = 'age:' . implode(',', range($userData['defaultminage'], $userData['defaultmaxage']));

	$query .= " WHERE " . implode(" && ", $queryParts);

	$query .= " ORDER BY id DESC LIMIT 5";

	$cachekey = 'search-new-profile-' . implode('-', $mcparams);
	if (!($resultSet = $cache->get($cachekey)))
	{
		$query = call_user_func_array(Array(&$db, 'prepare'), $queryParams);

		$queryResult = $db->query($query);
		$resultSet = Array();
		while($row = $queryResult->fetchrow())
			$resultSet[] = $row['userid'];

		$cache->put($cachekey, $resultSet, 30);
	}

	$users = getUserName($resultSet);

	$template = new template('include/blocks/list_users_block');
	$template->set('users', $users);
	$block_contents = $template->toString();
	blockContainer('Updated Profiles', $side, $block_contents);
}

function incTextAdBlock($side){
	global $banner, $userData;

	if($userData['limitads'])
		return;

	$banner->linkclass = 'sidelink';
	$bannertext = $banner->getbanner(BANNER_BUTTON60);

	if($bannertext == "")
		return;

	openBlock("Great Links",$side);

	echo "<table width=100%><tr><td class=side>";
	echo $bannertext;
	echo "</td></tr></table>";

	closeBlock();
}


function incSkyAdBlock($side){
	global $banner, $userData;

	if($userData['limitads'])
		return;

	$bannertext = $banner->getbanner(BANNER_SKY120);

	if($bannertext == "")
		return;

	openBlock("Sponsor",$side);

	echo "<br><center>$bannertext</center><br>";

	closeBlock();
}

function incPlusBlock($side){
	global $userData;

	if($userData['limitads'])
		return;

	$template = new template('include/blocks/plus_block');
	$block_contents = $template->toString();
	blockContainer('Nexopia Plus', $side, $block_contents);
}

function incSpotlightBlock($side){
	global $cache, $config;

	$user = $cache->hdget("spotlight",300,'getSpotlight');

	if(!$user)
		return;


	$user['pic_url'] = $config['thumbloc'] . floor($user['userid']/1000) . "/" . weirdmap($user['userid']) . "/$user[pic].jpg";

	$template = new template('include/blocks/spotlight_block');
	$template->set('user', $user);
	$block_contents = $template->toString();
	blockContainer('Plus Spotlight', $side, $block_contents);

}


/*********************** PROBABLY NOT USED ******************/
function incShoppingCartMenu($side){
	global $mods, $userData;

	openBlock("Shopping Cart",$side);

	echo "&nbsp;<a href=/cart.php>Shopping Cart</a><br>";
	echo "&nbsp;<a href=/checkout.php>Checkout</a><br>";
	echo "&nbsp;<a href=/invoicelist.php>Invoice List</a><br>";
	echo "&nbsp;<a href=/plus.php>Nexopia Plus</a><br>";
	echo "&nbsp;<a href=/paymentinfo.php>Payment Info</a>";

	if($userData['loggedIn'] && $mods->isAdmin($userData['userid'],'viewinvoice')){
		echo "<hr>";
		echo "&nbsp;<a href=/invoicereport.php>Reports</a><br>";
		echo "<center>";

		echo "<form action=/invoice.php>";
		echo "Invoice ID:<br><input type=text size=10 name=id>";
		echo "<input type=submit value=Go>";
		echo "</form>";

		echo "<form action=/profile.php>";
		echo "Show Username:<br><input type=text size=10 name=uid>";
		echo "<input type=submit value=Go>";
		echo "</form>";

		echo "</center>";
	}

	closeBlock();
}


function msgFoldersBlock($side){
	global $userData;

	openBlock("Message Folders",$side);

	echo "&nbsp;<a href=/messages.php?action=folders>Manage Folders</a><br>";

	$folders = getMsgFolders();

	foreach($folders as $id => $name)
		echo "&nbsp;- <a href=/messages.php?folder=$id>$name</a><br>";

	closeBlock();
}

function incScheduleBlock($side){
	global $db;

	$res = $db->prepare_query("SELECT title,timeoccur FROM schedule WHERE timeoccur > # && scope='global' && moded='y' ORDER BY timeoccur DESC LIMIT 5", time());

	openBlock('Events',$side);

	echo "<table>";

	while($line = $res->fetchrow())
		echo "<tr><td class=side><a href='/schedule.php?action=showday&month=" . gmdate('n',$line['timeoccur']) . "&year=" . gmdate('Y',$line['timeoccur']) . "&day=" . gmdate('j',$line['timeoccur']) . "&calsort[scope]=global'>$line[title]</a></td></tr>";

	echo "</table>";
	closeBlock();
}



function blockContainer($header, $side, $blockContents)
{
	global $skinloc, $skindata;
	$template =  new template("include/blocks/block_container");
	$template->set('background', "$skinloc/" . ($side=='l' ? "left" : "right") . "$skindata[blockheadpic]");
	$template->set('align', ($side=='l' ? "left" : "right"));
	$template->set('header', $header);
	$template->set('block_contents', $blockContents);
	$template->set('skindata', $skindata);
	$template->set('width', ($skindata['sideWidth'] - 2*$skindata['blockBorder']));
	$template->display();

}

