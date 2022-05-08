<?
function openCenter($width = true) {
	echo "<table cellpadding=0 cellspacing=0 width=" . ($width === true ? "100%" : "$width align=center") . " style=\"border-collapse: collapse\" border=0 bordercolor=#000000>";
	echo "<tr><td class=body>";
}

function closeCenter() {
	echo "</td></tr></table>";
}

function openBlock($header, $side) {
	global $skindir, $skindata;
	echo "<tr><td align=center width=$skindata[sideWidth]>";
	echo "<table cellpadding=0 cellspacing=0 border=0 width=100%>";
	echo "<tr><td colspan=3 background=$skindir/" . ($side == 'l' ? "left" : "right") . "$skindata[blockheadpic] height=$skindata[blockheadpicsize] class=sideheader valign=bottom align=" . ($side == 'l' ? "left" : "right") . ">&nbsp;&nbsp;<b>$header</b>&nbsp;&nbsp;</td></tr>";
	echo "<tr>";
	if ($skindata['blockBorder'] > 0)
		echo "<td width=$skindata[blockBorder] class=border></td>";
	echo "<td class=side valign=top width=" . ($skindata['sideWidth'] - 2 * $skindata['blockBorder']) . ">";
}

function closeBlock() {
	global $skindata, $skindata;
	echo "</td>";
	if ($skindata['blockBorder'] > 0)
		echo "<td width=$skindata[blockBorder] class=border></td>";
	echo "</tr>";
	if ($skindata['blockBorder'] > 0)
		echo "<tr><td colspan=3 height=$skindata[blockBorder] class=border></td></tr>";
	echo "</table>";
	echo "</td></tr>";
	echo "<tr><td height=$skindata[cellspacing]></td></tr>\n";
}

function incHeader($incCenter = true, $incLeftBlocks = false, $incRightBlocks = false) {
	global $userData, $skindata, $config, $skindir, $siteStats, $mods, $banner, $menus;

	timeline('start header');

	updateStats();

	timeline('- done stats');

	$skindata['incCenter'] = $incCenter;
	$skindata['rightblocks'] = $incRightBlocks;

	$skindata['admin'] = false;
	if ($userData['loggedIn'] == 'y')
		$skindata['admin'] = $mods->isAdmin($userData['userid']);

	ob_start();

	echo "<table cellspacing=0 cellpadding=0 width=$skindata[skinWidth]" . ($skindata['skinWidth'] == '100%' ? "" : " align=center") . ">";

	echo "<tr>";
	echo "<td class=header2" . ($skindata['mainbg'] == "" ? "" : ($skindata['mainbg'] { 0 }
	== '#' ? "" : " background='$skindir/$skindata[mainbg]'")) . ">"; // bgcolor=$skindata[mainbg], is the correct way, but skins don't expect it.
	echo "<table cellpadding=0 cellspacing=$skindata[cellspacing] width=100%>";
	echo "<tr>";

	// incBlocks
	if ($incLeftBlocks) {
		echo "<td width=$skindata[sideWidth] valign=top>";

		echo "<table width=100% cellpadding=0 cellspacing=0>\n";

		foreach ($incLeftBlocks as $funcname)
			$funcname ('l');

		echo "</table>\n";

		echo "</td>\n";
	}
	// end incBlocks

	echo "<td valign=top>";

	if ($skindata['incCenter'])
		openCenter($skindata['incCenter']);

	global $msgs;
	echo $msgs->get();

	timeline('end header');

	echo "\n\n\n";
}

function incFooter() {
	global $userData, $skindata, $skindir, $siteStats, $config, $debuginfousers, $banner, $menus, $weblog, $reporev;

	echo "\n\n\n";

	timeline('start footer');

	if ($skindata['incCenter'])
		closeCenter();
	echo "</td>";
/*
	//start right bar
	if ($skindata['rightblocks'] || ($userData['loggedIn'] && $userData['showrightblocks'] == 'y')) {

		echo "<td width=$skindata[sideWidth] height=100% valign=top>\n";

		echo "<table cellpadding=0 cellspacing=0>\n";

		if ($skindata['rightblocks'])
			foreach ($skindata['rightblocks'] as $funcname)
				$funcname ('r');

		blocks('r');

		echo "</table>\n";

		echo "</td>\n";
	}
	//end right bar

	echo "</tr>";
	echo "</table>";
	echo "</td>";
	echo "</tr>\n";

	closeAllDBs();

	//start admin menu
	if ($skindata['admin']) {
		echo "<tr>";
		if (substr($skindata['menupic'], 0, 1) == "#")
			echo "<td height=$skindata[menuheight] bgcolor=$skindata[menupic]>";
		else
			echo "<td height=$skindata[menuheight] background='$skindir","$skindata[menupic]'>";

		echo "<table cellspacing=0 cellpadding=0 width=100%><tr>";
		if (!empty ($skindata['menuends']))
			echo "<td class=menu align=left width=1><img src='$skindir","left$skindata[menuends]'></td>";

		echo "<td class=menu align=left>&nbsp;&nbsp;&nbsp;&nbsp;";

		$menu = array ();
		foreach ($menus['admin']->getMenu() as $item)
			$menu[] = "<a href='$item[addr]'" . ($item['target'] == '' ? "" : " target='$item[target]'") . ">$item[name]</a>";
		echo implode($skindata['menudivider'], $menu);

		echo "</td>";
		if (!empty ($skindata['menuends']))
			echo "<td class=menu align=right width=1><img src='$skindir","right$skindata[menuends]'></td>";
		echo "</tr></table>";
		echo "</td>";
		echo "</tr>";
		if ($skindata['menuspacersize'] > 0) {
			if (substr($skindata['menuspacer'], 0, 1) == "#")
				echo "<tr><td height=$skindata[menuspacersize] bgcolor=$skindata[menuspacer]></td></tr>";
			else
				echo "<tr><td height=$skindata[menuspacersize] background='$skindir/$skindata[menuspacer]'></td></tr>";
		}
		echo "\n";
	}
	//end admin menu
*/
	//start menu2
	echo "<tr>";
	if (substr($skindata['menupic'], 0, 1) == "#")
		echo "<td height=$skindata[menuheight] bgcolor=$skindata[menupic]>";
	else
		echo "<td height=$skindata[menuheight] background='$skindir/$skindata[menupic]'>";

	echo "<table cellspacing=0 cellpadding=0 width=100%><tr>";
	if (!empty ($skindata['menuends']))
		echo "<td class=menu align=left width=1><img src='$skindir/left$skindata[menuends]'></td>";

	echo "<td class=menu align=left>&nbsp;&nbsp;&nbsp;&nbsp;";

	global $menu2;

	$menu = array ();
	foreach ($menus['bottom']->getMenu() as $item)
		$menu[] = "<a href='$item[addr]'" . ($item['target'] == '' ? "" : " target='$item[target]'") . ">$item[name]</a>";
	echo implode($skindata['menudivider'], $menu);

	echo "</td><td class=menu align=right>";

	echo "Hits " . number_format($siteStats['hitstotal']) . " | Users " . number_format($siteStats['userstotal']) . " &nbsp; ";

	echo "</td>";
	if (!empty ($skindata['menuends']))
		echo "<td class=menu align=right width=1><img src='$skindir/right$skindata[menuends]'></td>";

	echo "</tr></table>";
	echo "</td>";
	echo "</tr>\n";
	//end menu2

	echo "<tr>";
	echo "<td class=footer align=center" . ($skindata['mainbg'] == "" ? "" : ($skindata['mainbg'] { 0 }
	== "#" ? "" : " background='$skindir/$skindata[mainbg]'")) . ">";
	echo $config['copyright'];
	echo "</td>";
	echo "</tr>";

	if ($skindata['bottomBorderSize'] > 0) {
		$colspan = 1;
		if ($skindata['leftBorderSize'] > 0)
			$colspan++;
		if ($skindata['rightBorderSize'] > 0)
			$colspan++;
		if (substr($skindata['bottomBorder'], 0, 1) == "#")
			echo "<tr><td height=$skindata[bottomBorderSize] bgcolor=$skindata[bottomBorder] colspan=$colspan></td></tr>";
		else
			echo "<tr><td height=$skindata[bottomBorderSize] background='$skindir/$skindata[bottomBorder]' colspan=$colspan></td></tr>";
	}

	echo "</table>\n";

	debugOutput();

	$bodytext = ob_get_clean();

	$headtext = "<html><head><title>$config[title]</title>\n";
	$headtext .= "<link rel=stylesheet href='$skindir"."default.css'>\n";
	if ($_SERVER['PHP_SELF'] == "/index.php") {
		$headtext .= "<meta name=\"description\" content=\"$config[metadescription]\">\n";
		$headtext .= "<meta name=\"keywords\" content=\"$config[metakeywords]\">\n";
	}
	$headtext .= "<script src=$config[imgserver]/skins/general.js?rev=$reporev></script>\n";
//	$headtext .= "</head>\n";

	echo $headtext;
	echo "</head>";
	
function block($b, $h){
	global $skindir, $skindata;
	$template1 = new template("include/blocks/block_container");
	$template1->set('background', "$skindir/" . ($side=='l' ? "left" : "right") . "$skindata[blockheadpic]");
	//$template1->set('align', ($side=='l' ? "left" : "right"));
	$template1->set('header', $h);
	$template1->set('skindata', $skindata);
	$template1->set('width', ($skindata['sideWidth'] - 2*$skindata['blockBorder']));
	$template2 = new template($b);
	$template1->set("block_contents", $template2->toString());
	return $template1->toString();
}

/*	$cachekey = makekey("cache-$_SERVER[REQUEST_URI]");

	$bodyname = strtoupper(substr(base_convert(md5(gettime()), 16, 36), 0, 8));

	global $pagecache;

	$pagecache->put("page-cache-$cachekey", "$headtext<body>$bodytext</body></html>", 60);
*/
	$newreplies = 0;
	if ($userData['loggedIn']) {
		$userblog = new userblog($weblog, $userData['userid']);
		$newreplies = $userblog->getNewReplyCountTotal();
	}
	echo "<script>";

	echo "var m=" . ($skindata['menuheight'] + $skindata['menuguttersize'] + ($userData['loggedIn'] ? ($skindata['menuheight'] + $skindata['menuspacersize']) : 0)) . ","; //menuheight
	echo "b=" . ($userData['limitads'] ? '0' : '1') . ";"; //show banner?

	echo "if(self==top){";
		echo "s=getWindowSize();";
		echo "h=(b&&s[0]>900&&s[1]>500?90:60);"; //show banner? if so, show big or small?
		echo "document.write('";
			//echo "<frameset rows=\"10,*\" frameborder=0 border=0>";
			echo "<div id=\"header\" style=\"position:fixed; left=50px; top=0px; " .
					"visibility:show; z-index:2;\">";
			echo "<table align=center width=800 border=0 cellpadding=0 cellspacing=0 " .
					"cellpadding=3 bgcolor=aaaaaa>";
			echo "<tr><td bgcolor=bbbbbb>";
			echo "<OBJECT TYPE=\"text/html\" " .
					"DATA=\"header.php?bodyname=$bodyname&height='+h+'\" " .
					"width=800 height=112 align=center>Bleh?</OBJECT>";
			echo "</td></tr>";
			echo "</table>";
			echo "</div>";
			echo "<object width=100% height=100% border=0 frameborder=no " .
					"type=\"text/html\" data=\"" . addslashes($_SERVER['REQUEST_URI']) . 
					(strpos($_SERVER['REQUEST_URI'], '?') ? '&' : '?') . 
					"cachekey=$cachekey\" name=\"$bodyname\" marginwidth=0 marginheight=0 " .
					"style=\"position:absolute; z-index:1; width=800;\" scrolling=no>";
			echo "bleh?</object>";
			
			//echo "<img src=\"http://www.w3schools.com/images/w3default80.jpg\">";
		echo "');";
	echo "}else{";

		$replies = ($newreplies ? "'$newreplies Repl" . ($newreplies == 1 ? 'y' : 'ies') . "'" : "''");
		//echo "	top.head.updateStats(" . ($userData['loggedIn'] ? "$userData[friendsonline]" : '-1') . ",$siteStats[onlineusers],$siteStats[onlineguests]," . ($userData['loggedIn'] ? "$userData[newmsgs],$replies," . ($userData['enablecomments'] == 'y' ? $userData['newcomments'] : '-1') : '-1,-1,-1') . ");";
	
	
	echo "}";
	echo "</script>\n";

	$userid = $userData['userid'];
	$pic = $userData['firstpic'];
	$imagePath = floor($userid/1000) . "/" . weirdmap($userid) . "/{$pic}.jpg";

	$template2 = new template("float_menu");
	if ($userData['loggedIn']){
		$text1 = "<table width=100 height=100><tr><td><img src='".$config['picdir'].$imagePath."' width=100></tr></td></table>";
	} else {
		$text1 = block("include/blocks/login_block", "Login");
	}
	$text2 = block("include/blocks/msg_block", "Messages");
	$text2 = $text2.block("include/blocks/friends_block", "Friends");
	$text2 = $text2.block("include/blocks/mod_block", "Moderator");
	$text2 = $text2;
	$template2->set("stuff1", $text1);
	$template2->set("stuff2", $text2);
	//$template2->display();


	//$template->set("extra", $template2->toString()."<script src=\"float_menu.js\"></script>");
	echo $template2->toString();
	echo "<script src=\"float_menu.js\"></script>";

	echo "<body>";
	echo "<div id=\"bodytext\">";
	echo "<table height=\"120px\" width=\"766px\"><tr><td>&nbsp</td></tr></table>";
	
			echo "<table align=center border=0 spacing=0 cellpadding=0 width=766 ><tr  border=0 spacing=0 cellpadding=0 ><td  border=0 spacing=0 cellpadding=0>";
	echo $bodytext;
			echo "</td></tr></table>";
	echo "</div>";
	echo "<script>";
	echo "if (self==top){" .
				"document.getElementById('bodytext').innerHTML='&nbsp';" .
			"} else {".
				"document.getElementById('ball').innerHTML='&nbsp';" .
			"}";
	echo "</script>";
	echo "</body>');";

	echo "</html>";
}

function createHeader($size, $bodyname) {
	global $skindir, $skindata, $config, $siteStats, $userData, $banner, $menus, $weblog, $reporev;

	updateStats();

	closeAllDBs();

	echo "<html><head><title>$config[title]</title><script src=$config[imgserver]/skins/general.js?rev=$reporev></script>";
	echo "<link rel=stylesheet href='$skindir"."default.css'>";

	echo "</head>\n";
	echo "<body" . ($skindata['backgroundpic'] ? " background='$skindir/$skindata[backgroundpic]' " : "") . ">\n";

	echo "<table cellspacing=0 cellpadding=0 width=$skindata[skinWidth]" . ($skindata['skinWidth'] == '100%' ? "" : " align=center") . ">";

	echo "<tr>";

	if ($size == 90)
		$header = $skindata['headerbig'];
	else
		$header = ($userData['limitads'] ? $skindata['headerplus'] : $skindata['headersmall']);

	echo "<td bgcolor=#000000 background='$skindir/$header' align=right height=$size valign=$skindata[bannervalign]>";

	if (!$userData['limitads']) {
		echo "<iframe src='/bannerview.php?size=" . ($size == 90 ? BANNER_LEADERBOARD : BANNER_BANNER) . "' marginHeight=0 marginWidth=0 frameborder=0 name=banner scrolling=no ";
		echo ($size == 90 ? "width=728 height=90" : "width=468 height=60");
		echo " allowtransparency=true background-color=\"transparent\"></iframe>";
	}

	echo "</td>";

	echo "</tr>\n";

	echo "<tr>";
	if (substr($skindata['menupic'], 0, 1) == "#")
		echo "<td height=$skindata[menuheight] bgcolor=$skindata[menupic]>";
	else
		echo "<td height=$skindata[menuheight] background='$skindir","$skindata[menupic]'>";

	//start menu
	echo "<table cellspacing=0 cellpadding=0 width=100%><tr>";
	if (!empty ($skindata['menuends']))
		echo "<td class=menu align=left width=1><img src='$skindir","left$skindata[menuends]'></td>";

	echo "<td class=menu align=left>&nbsp;&nbsp;&nbsp;&nbsp;";

	$menu = array ();
	foreach ($menus['main']->getMenu() as $item)
		$menu[] = "<a href='$item[addr]'" . ($item['target'] == '' ? " target='$bodyname'" : " target='$item[target]'") . ">$item[name]</a>";
	echo implode($skindata['menudivider'], $menu);

	echo "</td><td class=menu align=right>";

	srand((double) microtime() * 1000000);
	echo "| A totally random number:", rand(0, 100000), " | ";

	echo "Online: ";
	if ($userData['loggedIn'])
		echo "<a href=/friends.php target='$bodyname'>Friends <span id=friends>$userData[friendsonline]</span></a> | ";
	echo "<a href='/profile.php?requestType=onlineByPrefs' target='$bodyname'>Users <span id=users>$siteStats[onlineusers]</span></a> | Guests <span id=guests>$siteStats[onlineguests]</span> &nbsp;";

	echo "</td>";
	if (!empty ($skindata['menuends']))
		echo "<td class=menu align=right width=1><img src='$skindir/right$skindata[menuends]'></td>";
	echo "</tr></table>";
	//end menu
	echo "</td>";
	echo "</tr>\n";

	//personal menu
	if ($userData['loggedIn']) {
		if ($skindata['menuspacersize'] > 0) {
			if (substr($skindata['menuspacer'], 0, 1) == "#")
				echo "<tr><td height=$skindata[menuspacersize] bgcolor=$skindata[menuspacer]></td></tr>";
			else
				echo "<tr><td height=$skindata[menuspacersize] background='$skindir/$skindata[menuspacer]'></td></tr>";
		}

		echo "<tr>";
		if (substr($skindata['menupic'], 0, 1) == "#")
			echo "<td height=$skindata[menuheight] bgcolor=$skindata[menupic]>";
		else
			echo "<td height=$skindata[menuheight] background='$skindir","$skindata[menupic]'>";

		//start menu
		echo "<table cellspacing=0 cellpadding=0 width=100%><tr>";
		if (!empty ($skindata['menuends']))
			echo "<td class=menu align=left width=1><img src='$skindir","left$skindata[menuends]'></td>";
		echo "<td class=menu align=left>&nbsp;&nbsp;&nbsp;&nbsp;";

		$menu = array ();
		foreach ($menus['manage']->getMenu() as $item)
			$menu[] = "<a href='$item[addr]'" . ($item['target'] == '' ? " target='$bodyname'" : " target='$item[target]'") . ">$item[name]</a>";
		echo implode($skindata['menudivider'], $menu);

		echo "</td><td class=menu align=right>";

		echo "<a href='/messages.php' target='$bodyname'>Messages</a><a href=/messages.php?action=viewnew target='$bodyname'> <span id=msgs>$userData[newmsgs]</span> New</a>"; //&k=" . makekey('newmsgs') . "

		$userblog = new userblog($weblog, $userData['userid']);
		$newreplies = $userblog->getNewReplyCountTotal();
		$ending = ($newreplies == 1 ? 'y' : 'ies');
		echo " | <a href='/weblog.php?uid=$userData[userid]' target='$bodyname'>Blog</a> <a href='/weblog.php?uid=$userData[userid]&newreplies=1' target='$bodyname'>";
		$replies = ($newreplies ? "$newreplies Repl$ending" : '');
		echo "<span id=replies>$replies</span></a>";

		if ($userData['enablecomments'] == 'y')
			echo " | <a href='/usercomments.php' target='$bodyname'>Comments <span id=comments>$userData[newcomments]</span></a>";
		echo " &nbsp;";

		echo "</td>";
		if (!empty ($skindata['menuends']))
			echo "<td class=menu align=right width=1><img src='$skindir/right$skindata[menuends]'></td>";
		echo "</tr></table>";
		//end menu
		echo "</td>";
		echo "</tr>\n";
	}

	if ($skindata['menuguttersize'] > 0) {
		if (substr($skindata['menugutter'], 0, 1) == "#")
			echo "<tr><td height=$skindata[menuguttersize] bgcolor=$skindata[menugutter]></td></tr>";
		else
			echo "<tr><td height=$skindata[menuguttersize] background='$skindir/$skindata[menugutter]'></td></tr>";
	}

	echo "</table>";

	echo "<script>";
	echo "var timeout=-1,";
	echo "running=false,";
	echo "inactive=0;";
	echo "function settime(n){timeout=n;}";
	echo "function starttimer(){";
	echo "timeout--;";
	echo "inactive++;";
	echo "running=false;";

	echo "if(timeout > 0 && inactive <= 150){";
	echo "running = true;";
	echo "setTimeout('starttimer()', 1000);";
	echo "}else if(timeout == 0){";
	echo "timeout = -1;";
	echo "top.head.banner.location.reload(true);";
	echo "}";
	echo "}";

	echo "function updateStats(friends,users,guests,msgs,replies,comments){";
	echo "if(friends>=0)putinnerHTML('friends',friends);";
	echo "if(users>=0)putinnerHTML('users',users);";
	echo "if(guests>=0)putinnerHTML('guests',guests);";
	echo "if(msgs>=0)putinnerHTML('msgs',msgs);";
	echo "if(replies!=-1)putinnerHTML('replies',replies);";
	echo "if(comments>=0)putinnerHTML('comments',comments);";

	echo "inactive = 0;";
	echo "if(!running)";
	echo "starttimer();";
	echo "}";

	echo "</script>";
	echo "</body>";
	echo "</html>";
	//end personal menu
}