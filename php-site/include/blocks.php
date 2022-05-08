<?
function incGoogleBlock($side){
	global $skinloc, $skin;

	switch($skin){
		case "azure":     $color = "GALT:#004481;GL:1;DIV:#D6E3EE;VLC:004481;AH:center;BGC:D6E3EE;LBGC:D6E3EE;ALC:0E66B6;LC:0E66B6;T:000000;GFNT:004481;GIMP:004481;"; break;
		case "aurora":    $color = "GALT:#287C4E;GL:1;DIV:#DFDED9;VLC:287C4E;AH:center;BGC:DFDED9;LBGC:DFDED9;ALC:41A56E;LC:41A56E;T:215670;GFNT:287C4E;GIMP:287C4E;"; break;
		case "black":     $color = "GALT:#888888;GL:1;DIV:#DFDED9;VLC:888888;AH:center;BGC:2A2A2B;LBGC:2A2A2B;ALC:B4B4B4;LC:B4B4B4;T:BCBEC0;GFNT:888888;GIMP:888888;"; break;
		case "carbon":    $color = "GALT:#004481;GL:1;DIV:#DFDED9;VLC:004481;AH:center;BGC:E9E9E9;LBGC:E9E9E9;ALC:0767BC;LC:0767BC;T:000000;GFNT:004481;GIMP:004481;"; break;
		case "crush":     $color = "GALT:#D6E7FA;GL:1;DIV:#5E739B;VLC:D6E7FA;AH:center;BGC:5E739B;LBGC:5E739B;ALC:F6DEEE;LC:F6DEEE;T:FFFFFF;GFNT:D6E7FA;GIMP:D6E7FA;"; break;
		case "flowers":   $color = "GALT:#FF7FA4;GL:1;DIV:#FFF1F6;VLC:FF7FA4;AH:center;BGC:FFF1F6;LBGC:FFF1F6;ALC:E0567E;LC:E0567E;T:FF6991;GFNT:FF7FA4;GIMP:FF7FA4;"; break;
		case "greenx":    $color = "GALT:#628241;GL:1;DIV:#272727;VLC:628241;AH:center;BGC:272727;LBGC:272727;ALC:84C14A;LC:84C14A;T:878787;GFNT:628241;GIMP:628241;"; break;
		case "halloween": $color = "GALT:#C24701;GL:1;DIV:#000000;VLC:C24701;AH:center;BGC:000000;LBGC:000000;ALC:FFFFFF;LC:FFFFFF;T:F2984C;GFNT:C24701;GIMP:C24701;"; break;
		case "megaleet":  $color = "GALT:#7995E5;GL:1;DIV:#000000;VLC:7995E5;AH:center;BGC:000000;LBGC:000000;ALC:4C4CCF;LC:4C4CCF;T:FFFFFF;GFNT:7995E5;GIMP:7995E5;"; break;
		default:
		case "newblue":   $color = "GALT:#0353A2;GL:1;DIV:#FFFFFF;VLC:0353A2;AH:center;BGC:FFFFFF;LBGC:FFFFFF;ALC:1874CF;LC:1874CF;T:323237;GFNT:0353A2;GIMP:0353A2;"; break;
		case "newyears":  $color = "GALT:#9060BE;GL:1;DIV:#313030;VLC:9060BE;AH:center;BGC:313030;LBGC:313030;ALC:B89A48;LC:B89A48;T:B8B2CD;GFNT:9060BE;GIMP:9060BE;"; break;
		case "orange":    $color = "GALT:#BF0E00;GL:1;DIV:#E9E9E9;VLC:BF0E00;AH:center;BGC:E9E9E9;LBGC:E9E9E9;ALC:FE9900;LC:FE9900;T:000000;GFNT:BF0E00;GIMP:BF0E00;"; break;
		case "pink":      $color = "GALT:#FF1493;GL:1;DIV:#DFDED9;VLC:FF1493;AH:center;BGC:DFDED9;LBGC:DFDED9;ALC:C41775;LC:C41775;T:000000;GFNT:FF1493;GIMP:FF1493;"; break;
		case "rushhour":  $color = "GALT:#626262;GL:1;DIV:#D3D3D3;VLC:626262;AH:center;BGC:D3D3D3;LBGC:D3D3D3;ALC:242424;LC:242424;T:000000;GFNT:626262;GIMP:626262;"; break;
		case "solar":     $color = "GALT:#FE9800;GL:1;DIV:#666666;VLC:FE9800;AH:center;BGC:666666;LBGC:666666;ALC:FDAB30;LC:FDAB30;T:FFFFFF;GFNT:FE9800;GIMP:FE9800;"; break;
		case "splatter":  $color = "GALT:#D74256;GL:1;DIV:#231F20;VLC:D74256;AH:center;BGC:231F20;LBGC:231F20;ALC:E36677;LC:E36677;T:BCBEC0;GFNT:D74256;GIMP:D74256;"; break;
		case "vagrant":   $color = "GALT:#5F5F5F;GL:1;DIV:#FEFEFE;VLC:5F5F5F;AH:center;BGC:FEFEFE;LBGC:FEFEFE;ALC:888888;LC:888888;T:000000;GFNT:5F5F5F;GIMP:5F5F5F;"; break;
		case "verypink":  $color = "GALT:#636466;GL:1;DIV:#F7F8F8;VLC:636466;AH:center;BGC:F7F8F8;LBGC:F7F8F8;ALC:F27FC2;LC:F27FC2;T:6F6F6F;GFNT:636466;GIMP:636466;"; break;
		case "winter":    $color = "GALT:#1F6199;GL:1;DIV:#E7F2FB;VLC:1F6199;AH:center;BGC:E7F2FB;LBGC:E7F2FB;ALC:4A88BC;LC:4A88BC;T:000000;GFNT:1F6199;GIMP:1F6199;"; break;
		case "wireframe": $color = "GALT:#D5CE72;GL:1;DIV:#403F40;VLC:D5CE72;AH:center;BGC:403F40;LBGC:403F40;ALC:E6DD6C;LC:E6DD6C;T:BCBEC0;GFNT:D5CE72;GIMP:D5CE72;"; break;
		case "abacus":    $color = "GALT:#ADA083;GL:1;DIV:#FFFFFF;VLC:ADA083;AH:center;BGC:FFFFFF;LBGC:FFFFFF;ALC:00A8D8;LC:00A8D8;T:808080;GFNT:ADA083;GIMP:ADA083;"; break;
		case "bigmusic":  $color = "GALT:#FF6666;GL:1;DIV:#FFCCCC;VLC:FF6666;AH:center;BGC:FFCCCC;LBGC:FFCCCC;ALC:FF6666;LC:FF6666;T:CC6666;GFNT:FF6666;GIMP:FF6666;"; break;
		case "cabin":     $color = "GALT:#A8662C;GL:1;DIV:#FFFFFF;VLC:DDDDDD;AH:center;BGC:FFFFFF;LBGC:FFFFFF;ALC:A8662C;LC:A8662C;T:000000;GFNT:A8662C;GIMP:A8662C;"; break;
		case "candy":     $color = "GALT:#D9757A;GL:1;DIV:#E3EBA6;VLC:81939A;AH:center;BGC:E3EBA6;LBGC:E3EBA6;ALC:648489;LC:648489;T:000000;GFNT:D9757A;GIMP:D9757A;"; break;
		case "earth":     $color = "GALT:#145F79;GL:1;DIV:#E1D5B8;VLC:145F79;AH:center;BGC:E1D5B8;LBGC:E1D5B8;ALC:B45C15;LC:B45C15;T:000000;GFNT:145F79;GIMP:145F79;"; break;
		case "friends":   $color = "GALT:#FFFFFF;GL:1;DIV:#FF7777;VLC:FFFFFF;AH:center;BGC:FF7777;LBGC:FF7777;ALC:FFFFFF;LC:FFFFFF;T:000000;GFNT:FFFFFF;GIMP:FFFFFF;"; break;
		case "newflowers":$color = "GALT:#C7C1FE;GL:1;DIV:#E630AE;VLC:C7C1FE;AH:center;BGC:E630AE;LBGC:E630AE;ALC:C7C1FE;LC:C7C1FE;T:FDE6FA;GFNT:C7C1FE;GIMP:C7C1FE;"; break;
		case "nextacular":$color = "GALT:#F837BB;GL:1;DIV:#0D0D0D;VLC:F837BB;AH:center;BGC:0D0D0D;LBGC:0D0D0D;ALC:F0D30E;LC:F0D30E;T:E5E5E5;GFNT:F837BB;GIMP:F837BB;"; break;
		case "rockstar":  $color = "GALT:#8C6E89;GL:1;DIV:#D9DADC;VLC:000000;AH:center;BGC:D9DADC;LBGC:D9DADC;ALC:8C6E89;LC:8C6E89;T:000000;GFNT:8C6E89;GIMP:8C6E89;"; break;
		case "schematic": $color = "GALT:#7C7C7C;GL:1;DIV:#FFFFFF;VLC:7C7C7C;AH:center;BGC:FFFFFF;LBGC:FFFFFF;ALC:5C9664;LC:5C9664;T:323237;GFNT:7C7C7C;GIMP:7C7C7C;"; break;
		case "somber":    $color = "GALT:#959899;GL:1;DIV:#F2F2F2;VLC:959899;AH:center;BGC:F2F2F2;LBGC:F2F2F2;ALC:000000;LC:000000;T:000000;GFNT:959899;GIMP:959899;"; break;
		case "twilight":  $color = "GALT:#2F5FAC;GL:1;DIV:#00003B;VLC:2F5FAC;AH:center;BGC:00003B;LBGC:00003B;ALC:FFFFFF;LC:FFFFFF;T:CCCCCC;GFNT:2F5FAC;GIMP:2F5FAC;"; break;
	}

	$template = new template('include/blocks/googlesearch');
	$template->set('googleimg', "${skinloc}google.png");
	$template->set('color', $color);
	$contents = $template->toString();

	blockContainer('Web Search', $side, $contents);
}

function incJobloftBlock($side){
	global $skinloc;
	$str = "";
	$str .= "<table align=center cellspacing=0 cellpadding=0>";
	$str .= "<form action=jobloft.php>";
	$str .= "<tr><td class=side align=center>";
	$str .= "Postal Code: <input class=side name=postalcode size=6 maxlength=6><br>";
	$str .= "<input type=image style=\"border: none;\" src=${skinloc}jobloft.png>";
	$str .= "</td></tr>";
	$str .= "</form>";
	$str .= "</table>";

	blockContainer('Jobs', $side, $str);
}

function incMyTreatBlock($side){
	global $config, $userData;

	if($userData['limitads'])
		return;

	blockContainer('My Treat', $side, "<center><a href=/wiki/UserGuides/Plus/MyTreat><img src=$config[imageloc]/mytreatfront.png border=0 style=\"padding-bottom: 5px\"></a></center>");
}

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


	$str = "<table width=100%>\n";
	$str .= "<tr><td class=header><b><a href=\"/bookmarks.php\">Bookmarks</a></b></td></tr>";
	while($line = $res->fetchrow())
		$str .= "<tr><td class=side><a href=\"$line[url]\" target=_blank>$line[name]</a></td></tr>\n";
	$str .= "</table>\n";

	blockContainer('Bookmarks', $side, $str);
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
	$template->set("is_logged_in",$userData['loggedIn']);
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
	if(!$userData['halfLoggedIn'])
		return;

	$friends = $userData['friends'];
	$friendnames = getUserName($friends);


	natcasesort($friendnames);

	$template->set('friendsonline', $userData['friendsonline']);
	$template->set('friends', $friendnames);
	$template->set('current_username', getUserName($userData['userid']));
	$block_contents = $template->toString();
	blockContainer('Friends',$side, $block_contents);
}

function incModBlock($side){
	global $userData, $mods, $cache;

	if(!$userData['loggedIn'])
		return;

	if(!$mods->isMod($userData['userid']))
		return;

	function getAdminsOnline(){
		global $mods;

		$admins = $mods->admins; //BAD!

		$users = getUserInfo(array_keys($admins));

		$rows = array('global' => array(), 'admin' => array());
		foreach($users as $line){
			if($line['online'] == 'n')
				continue;

			$uid = $line['userid'];

			if(!isset($admins[$uid]['visible']) || !$admins[$uid]['visible'])
				continue;

			$role = (in_array('Global', $admins[$uid]['roles']) ? 'global' : 'admin');

			$rows[$role][$uid] = $line['username'];
		}

		uasort($rows['admin'], 'strcasecmp');
		uasort($rows['global'], 'strcasecmp');

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

	$adminsonline = $cache->get('adminsonline',30,'getAdminsOnline', 0);
	$modsonline = $cache->get('modsonline',30,'getNumModsOnline', 0);


	$moditemcounts = $mods->getModItemCounts();

	$template =  new template("include/blocks/mod_block");

	$types = array();
	foreach ($moditemcounts as $type => $num) {
//		if ($num > 0)
			$types[$type] = array(
				'nbsp'		=> str_repeat('&nbsp;&nbsp;',	5 - strlen($num)),
				'num'		=> $num,
				'modtype'	=> $mods->modtypes[$type]
			);
	}

	$template->set('types_count', count($types));
	$template->set('types', $types);

	$template->set('modsonline', $modsonline);
	$template->set('adminsonline_count', count($adminsonline['admin']));
	$template->set('adminsonline', $adminsonline['admin']);
	$template->set('globalmodsonline', $adminsonline['global']);
	$template->set('globalmodsonline_count', count($adminsonline['global']));
	$block_contents = $template->toString();
	blockContainer('Moderator',$side, $block_contents);
}

function incLoginBlock($side){
	global $userData;

	if($userData['halfLoggedIn'])
		return;
	$template = new template('include/blocks/login_block');
	$template->set('secure_session_checkbox', makeCheckBox('lockip', " Secure Session", true));
	$template->set('remember_me_checkbox', makeCheckBox('cachedlogin', " Remember Me", false));
	$block_content = $template->toString();
	blockContainer('Login', $side, $block_content);
}

function incSkinBlock($side){
	global $skins,$skin;

	$str = "<table><form action=$_SERVER[PHP_SELF] method=post>";
	$str .= "<tr><td class=side><select name=newskin>" . make_select_list_key($skins,$skin) ."</select><input type=submit name=chooseskin value=Go></td></tr>";
	$str .= "</form></table>";
	blockContainer('Skin', $side, $str);
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

	if(!$userData['halfLoggedIn'])
		return;

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
	$mcparams[] = 'age:' . $userData['defaultminage'] . '-' . $userData['defaultmaxage'];

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

	if(!$userData['halfLoggedIn'])
		return;

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
	$mcparams[] = 'age:' . $userData['defaultminage'] . '-' . $userData['defaultmaxage'];

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
	return;

	global $banner, $userData;

	if($userData['limitads'])
		return;

	$banner->linkclass = 'sidelink';
	$bannertext = $banner->getbanner(BANNER_BUTTON60);

	if($bannertext == "")
		return;

	$str = "<table width=100%><tr><td class=side>";
	$str .= $bannertext;
	$str .= "</td></tr></table>";

	blockContainer('Great Links', $side, $str);
}


function incSkyAdBlock($side){
	global $banner, $userData;

	if($userData['limitads'])
		return;

	$bannertext = $banner->getbanner(BANNER_SKY120);

	if($bannertext == "")
		return;

	$str = "<br><center>$bannertext</center><br>";

	blockContainer('Sponsor', $side, $str);
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

	$str = "&nbsp;<a href=/cart.php>Shopping Cart</a><br>";
	$str .= "&nbsp;<a href=/checkout.php>Checkout</a><br>";
	$str .= "&nbsp;<a href=/invoicelist.php>Invoice List</a><br>";
	$str .= "&nbsp;<a href=/plus.php>Nexopia Plus</a><br>";
	$str .= "&nbsp;<a href=/paymentinfo.php>Payment Info</a>";

	if($userData['loggedIn'] && $mods->isAdmin($userData['userid'],'viewinvoice')){
		$str .= "<hr>";
		$str .= "&nbsp;<a href=/invoicereport.php>Reports</a><br>";
		$str .= "<center>";

		$str .= "<form action=/invoice.php>";
		$str .= "Invoice ID:<br><input type=text size=10 name=id>";
		$str .= "<input type=submit value=Go>";
		$str .= "</form>";

		$str .= "<form action=/profile.php>";
		$str .= "Show Username:<br><input type=text size=10 name=uid>";
		$str .= "<input type=submit value=Go>";
		$str .= "</form>";

		$str .= "</center>";
	}

	blockContainer('Shopping Cart', $side, $str);
}


function msgFoldersBlock($side){
	global $userData;

	$str = "&nbsp;<a href=/messages.php?action=folders>Manage Folders</a><br>";

	$folders = getMsgFolders();

	foreach($folders as $id => $name)
		$str .= "&nbsp;- <a href=/messages.php?folder=$id>$name</a><br>";

	blockContainer('Message Folders', $side, $str);
}

function incScheduleBlock($side){
	global $db;

	$res = $db->prepare_query("SELECT title,timeoccur FROM schedule WHERE timeoccur > # && scope='global' && moded='y' ORDER BY timeoccur DESC LIMIT 5", time());

	$str = "<table>";

	while($line = $res->fetchrow())
		$str .= "<tr><td class=side><a href='/schedule.php?action=showday&month=" . gmdate('n',$line['timeoccur']) . "&year=" . gmdate('Y',$line['timeoccur']) . "&day=" . gmdate('j',$line['timeoccur']) . "&calsort[scope]=global'>$line[title]</a></td></tr>";

	$str .= "</table>";
	blockContainer('Events', $side, $str);
}



function blockContainer($header, $side, $blockContents)
{
	global $skinloc, $skindata;
	$template =  new template("include/blocks/block_container");
	$template->set('background', "$skinloc/" . ($side=='l' ? "left" : "right") . "$skindata[blockheadpic]");
	$template->set('align', ($side=='l' ? "left" : "right"));
	$template->set('valign', (isset($skindata['valignsideheader']) ? $skindata['valignsideheader'] : 'bottom'));
	$template->set('header', $header);
	$template->set('block_contents', $blockContents);
	$template->set('skindata', $skindata);
	$template->set('width', ($skindata['sideWidth'] - 2*$skindata['blockBorder']));
	$template->display();

}

