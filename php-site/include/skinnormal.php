<?

function openCenter($width = true){
	echo "<table cellpadding=3 cellspacing=0 width=" . ($width === true ? "100%" : "$width align=center" ) . " style=\"border-collapse: collapse\" border=1 bordercolor=#000000>";
	echo "<tr><td class=body>";
}

function closeCenter(){
	echo "</td></tr></table>";
}

function createHeader(){ } //exists purely so a race condition at login time doesn't put stuff in the error log. It doesn't get used anyway.

function incHeader($incCenter=true, $incLeftBlocks=false, $incRightBlocks=false, $skeleton=false, $modules=array(), $userskinpath=false){
	global $userData, $skindata, $cache, $config, $skinloc, $siteStats, $mods, $banner, $menus, $weblog, $reporev, $staticimgdomain, $staticdomain;

	timeline('start header');

	updateStats();

	timeline('- done stats');

	$menus = $cache->hdget("menus", 0, 'makeMenus');

	$skindata['incCenter'] = $incCenter;
	$skindata['rightblocks'] = $incRightBlocks;
	$skindata['modules'] = $modules;
	$skindata['skeleton'] = $skeleton;


	$skindata['admin']=false;
	if($userData['loggedIn']=='y')
		$skindata['admin'] = $mods->isAdmin($userData['userid']);

	$skindata['rows'] = 5;
	if($userData['loggedIn']) //user menu
		$skindata['rows']++;
	if($skindata['admin']) //admin menu
		$skindata['rows']++;

	echo '	<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
	echo "<html><head><title>$config[title]</title><script src=$config[jsloc]general.js></script>";
	echo "<script src=/static/$reporev/files/Yui/build/yui.js></script>\n";
	echo "<script src=\"http://".$staticdomain."/Gallery/javascript/SWFUpload.js\"></script>";
	echo "<link rel=stylesheet href='$skinloc" . "default.css'>";
	foreach ($modules as $module) {
		$file_name = $module . '.js';
		echo "<script type=\"text/javascript\" src=\"/static/$reporev/script/$file_name\"></script>";
	}
	if ($skeleton) {
		$structure_name = $skeleton;
		$skin_name = $skindata['name'];
		echo "<link rel=\"stylesheet\" href=\"/static/$reporev/style/$structure_name/$skin_name.css\"/>";
	}
	if ($userskinpath) {
		echo "<link rel=stylesheet href='$userskinpath'/>";
	}
	if($_SERVER['PHP_SELF'] == "/index.php"){
		echo "<meta name=\"description\" content=\"$config[metadescription]\">\n";
		echo "<meta name=\"keywords\" content=\"$config[metakeywords]\">\n";
	}

	echo "</head>\n";
	echo "<body " . ($skindata['backgroundpic'] ? "background='$skinloc$skindata[backgroundpic]' " : "" ) . "onLoad='init()'>\n";

	$tags = array("div", "form", "h1", "h2", "h3", "h4", "h5", "h6", "ol", "p", "pre", "ul", "li", "td", "tbody", "th");
	
	$arr = array();
	foreach ($tags as $tag){
		array_push($arr, $tag . ".noscript");
	}
	$block_tags = implode(", ", $arr);
	
	$arr = array();
	foreach ($tags as $tag){
		array_push($arr, $tag . ".script");
	}
	$block_tags2 = implode(", ", $arr);
	
	echo "<noscript><style>
		$block_tags { display:block; } $block_tags2 { display:none; } .noscript { display:inline;} .script { display:none;}
		thead.noscript {display:table-header-group;}
		tfoot.noscript {display:table-footer-group;}
		tr.noscript {display:table-row;}
		table.noscript {display:table;}
		thead.script, tfoot.script, tr.script, table.script {display:none;}
	</style></noscript>";
	
	echo "<table id=sitebody cellspacing=0 cellpadding=0 width=" . ($skindata['skinWidth'] == '100%' ? $skindata['skinWidth'] : ($skindata['skinWidth']-50) . " align=center") . ">";

	if($skindata['topBorderSize'] > 0){
		$colspan = 1;
		if($skindata['leftBorderSize'] > 0)
			$colspan++;
		if($skindata['rightBorderSize'] > 0)
			$colspan++;
		if(substr($skindata['topBorder'],0,1)=="#")
			echo "<tr><td height=$skindata[topBorderSize] bgcolor=$skindata[topBorder] colspan=$colspan></td></tr>";
		else
			echo "<tr><td height=$skindata[topBorderSize] background='$skinloc$skindata[topBorder]' colspan=$colspan></td></tr>";
	}

	echo "<tr>";

		if($skindata['leftBorderSize'] > 0){
			if(substr($skindata['leftBorder'],0,1)=="#")
				echo "<td rowspan=$skindata[rows] width=$skindata[leftBorderSize] bgcolor=$skindata[leftBorder]></td>";
			else
				echo "<td rowspan=$skindata[rows] width=$skindata[leftBorderSize] background='$skinloc$skindata[leftBorder]'></td>";
		}

		echo "<td bgcolor=#000000 background='$skinloc$skindata[headerpic]' align=right height=$skindata[headerheight]>";
			if($skindata['floatinglogo']!="")
				echo "<img src='$skinloc$skindata[floatinglogo]' align=$skindata[floatinglogovalign]>";
		echo "</td>";

		if($skindata['rightBorderSize'] > 0){
			if(substr($skindata['rightBorder'],0,1)=="#")
				echo "<td rowspan=$skindata[rows] width=$skindata[rightBorderSize] bgcolor=$skindata[rightBorder]></td>";
			else
				echo "<td rowspan=$skindata[rows] width=$skindata[rightBorderSize] background='$skinloc$skindata[rightBorder]'></td>";
		}

	echo "</tr>\n";

	echo "<tr>";
		if(substr($skindata['menupic'],0,1)=="#")
			echo "<td height=$skindata[menuheight] bgcolor=$skindata[menupic]>";
		else
			echo "<td height=$skindata[menuheight] background='$skinloc$skindata[menupic]'>";


//start menu
			echo "<table cellspacing=0 cellpadding=0 width=100%><tr>";
			if(!empty($skindata['menuends']))
				echo "<td class=menu align=left width=1><img src='$skinloc" . "left$skindata[menuends]'></td>";

			echo "<td class=menu align=left>&nbsp;&nbsp;&nbsp;&nbsp;";


			$menu = array();
			foreach($menus['main']->getMenu() as $item)
				$menu[] = "<a href='$item[addr]'" . ($item['target']=='' ? "" : " target='$item[target]'" ) . ">$item[name]</a>";
			echo implode($skindata['menudivider'],$menu);

			echo "</td><td class=menu align=right>";

			echo "Online: ";
			if($userData['loggedIn'])
				echo "<a href='/users/" . urlencode($userData['username']) . "/friends'>Friends $userData[friendsonline]</a>" . $skindata['menudivider'];
			echo "<a href='/profile.php?requestType=onlineByPrefs'>Users $siteStats[onlineusers]</a>" . $skindata['menudivider'] . "Guests $siteStats[onlineguests] &nbsp;";

			echo "</td>";
			if(!empty($skindata['menuends']))
				echo "<td class=menu align=right width=1><img src='$skinloc" . "right$skindata[menuends]'></td>";
			echo "</tr></table>";
//end menu
		echo "</td>";
	echo "</tr>\n";

//personal menu
	if($userData['loggedIn']){
		if($skindata['menuspacersize'] > 0){
			if(substr($skindata['menuspacer'],0,1)=="#")
				echo "<tr><td height=$skindata[menuspacersize] bgcolor=$skindata[menuspacer]></td></tr>";
			else
				echo "<tr><td height=$skindata[menuspacersize] background='$skinloc$skindata[menuspacer]'></td></tr>";
		}

		echo "<tr>";
			if(substr($skindata['menupic'],0,1)=="#")
				echo "<td height=$skindata[menuheight] bgcolor=$skindata[menupic]>";
			else
				echo "<td height=$skindata[menuheight] background='$skinloc$skindata[menupic]'>";


	//start menu
				echo "<table cellspacing=0 cellpadding=0 width=100%><tr>";
				if(!empty($skindata['menuends']))
					echo "<td class=menu align=left width=1><img src='$skinloc" . "left$skindata[menuends]'></td>";
				echo "<td class=menu align=left>&nbsp;&nbsp;&nbsp;&nbsp;";

				$menu = array();
				foreach($menus['personal']->getMenu() as $item)
					$menu[] = "<a href='$item[addr]'" . ($item['target']=='' ? "" : " target='$item[target]'" ) . ">$item[name]</a>";
				echo implode($skindata['menudivider'],$menu);

				echo "</td><td class=menu align=right>";

				echo "<a href='/messages.php'>Messages</a><a href=/messages.php?action=viewnew> $userData[newmsgs] New</a>"; //&k=" . makekey('newmsgs') . "

				$userblog = new userblog($weblog, $userData['userid']);
				$newreplies = $userblog->getNewReplyCountTotal();
				$ending = ($newreplies==1? 'y' : 'ies');
				echo $skindata['menudivider'] . "<a href='/weblog.php?uid=$userData[userid]'>Blog</a>";
				if($newreplies)
					echo " <a href='/weblog.php?newreplies=1'>$newreplies Repl$ending</a>";

				if($userData['enablecomments'] == 'y')
					echo $skindata['menudivider'] . "<a href='/usercomments.php'>Comments $userData[newcomments]</a>";

				echo " &nbsp;";

				echo "</td>";
				if(!empty($skindata['menuends']))
					echo "<td class=menu align=right width=1><img src='$skinloc" . "right$skindata[menuends]'></td>";
				echo "</tr></table>";
	//end menu
			echo "</td>";
		echo "</tr>\n";
	}
//end personal menu

	if($skindata['menuguttersize'] > 0){
		if(substr($skindata['menugutter'],0,1)=="#")
			echo "<tr><td height=$skindata[menuguttersize] bgcolor=$skindata[menugutter]></td></tr>";
		else
			echo "<tr><td height=$skindata[menuguttersize] background='$skinloc$skindata[menugutter]'></td></tr>";
	}

	echo "<tr>";
		echo "<td class=header2" . ($skindata['mainbg'] == "" ? "" : ($skindata['mainbg']{0} == '#' ? "" : " background='$skinloc$skindata[mainbg]'")) . ">"; // bgcolor=$skindata[mainbg], is the correct way, but skins don't expect it.
			echo "<table cellpadding=0 cellspacing=$skindata[cellspacing] width=100%>";
				echo "<tr>";

	// incBlocks
		if($incLeftBlocks){
				echo "<td width=$skindata[sideWidth] valign=top>";

					echo "<table width=100% cellpadding=0 cellspacing=0>\n";

					foreach($incLeftBlocks as $funcname)
						$funcname('l');

					echo "</table>\n";

				echo "</td>\n";
		}
	// end incBlocks

					echo "<td valign=top>";
//leaderboard
//*
	if(!$userData['limitads'] && $_SERVER['PHP_SELF'] != '/index.php'){
		timeline('get banner');
		if(!$incLeftBlocks)
			$bannertext = $banner->getbanner(BANNER_LEADERBOARD);
		else
			$bannertext = $banner->getbanner(BANNER_BANNER);
		if($bannertext!="")
			echo "<table cellspacing=0 cellpadding=0 align=center><tr><td>$bannertext</td></tr><tr><td height=$skindata[cellspacing]></td></tr></table>";
	}
//*/

						if($skindata['incCenter'])
							openCenter($skindata['incCenter']);

	global $msgs;
    echo $msgs->get();

	timeline('end header');

	echo "\n\n\n";
}

function incFooter(){
	global $userData,$skindata,$skinloc,$siteStats,$config, $debuginfousers, $banner, $menus;

	timeline('start footer');


	echo "\n\n\n";

						if($skindata['incCenter'])
							closeCenter();
					echo "</td>";

//start right bar
						if($skindata['rightblocks'] || ($userData['loggedIn'] && $userData['showrightblocks']=='y')){


							echo "<td width=$skindata[sideWidth] height=100% valign=top>\n";

								echo "<table cellpadding=0 cellspacing=0>\n";

								if($skindata['rightblocks'])
									foreach($skindata['rightblocks'] as $funcname)
										$funcname('r');

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
if($skindata['admin']){
	echo "<tr>";
		if(substr($skindata['menupic'],0,1)=="#")
			echo "<td height=$skindata[menuheight] bgcolor=$skindata[menupic]>";
		else
			echo "<td height=$skindata[menuheight] background='$skinloc$skindata[menupic]'>";

			echo "<table cellspacing=0 cellpadding=0 width=100%><tr>";
			if(!empty($skindata['menuends']))
				echo "<td class=menu align=left width=1><img src='$skinloc" . "left$skindata[menuends]'></td>";

			echo "<td class=menu align=left>&nbsp;&nbsp;&nbsp;&nbsp;";


			$menu = array();
			foreach($menus['admin']->getMenu() as $item)
				$menu[] = "<a href='$item[addr]'" . ($item['target']=='' ? "" : " target='$item[target]'" ) . ">$item[name]</a>";
			echo implode($skindata['menudivider'],$menu);

			echo "</td>";
			if(!empty($skindata['menuends']))
				echo "<td class=menu align=right width=1><img src='$skinloc" . "right$skindata[menuends]'></td>";
			echo "</tr></table>";
		echo "</td>";
	echo "</tr>";
	if($skindata['menuspacersize'] > 0){
		if(substr($skindata['menuspacer'],0,1)=="#")
			echo "<tr><td height=$skindata[menuspacersize] bgcolor=$skindata[menuspacer]></td></tr>";
		else
			echo "<tr><td height=$skindata[menuspacersize] background='$skinloc$skindata[menuspacer]'></td></tr>";
	}
	echo "\n";
}
//end admin menu



//start menu2
	echo "<tr>";
		if(substr($skindata['menupic'],0,1)=="#")
			echo "<td height=$skindata[menuheight] bgcolor=$skindata[menupic]>";
		else
			echo "<td height=$skindata[menuheight] background='$skinloc$skindata[menupic]'>";

			echo "<table cellspacing=0 cellpadding=0 width=100%><tr>";
			if(!empty($skindata['menuends']))
				echo "<td class=menu align=left width=1><img src='$skinloc" . "left$skindata[menuends]'></td>";

			echo "<td class=menu align=left>&nbsp;&nbsp;&nbsp;&nbsp;";


			$menu = array();
			foreach($menus['bottom']->getMenu() as $item)
				$menu[] = "<a href='$item[addr]'" . ($item['target']=='' ? "" : " target='$item[target]'" ) . ">$item[name]</a>";
			echo implode($skindata['menudivider'],$menu);

			echo "</td><td class=menu align=right>";

			echo "Hits " . number_format($siteStats['hitstotal']) . $skindata['menudivider'] . "Users " . number_format($siteStats['userstotal']) . " &nbsp; ";

			echo "</td>";
			if(!empty($skindata['menuends']))
				echo "<td class=menu align=right width=1><img src='$skinloc" . "right$skindata[menuends]'></td>";

			echo "</tr></table>";
		echo "</td>";
	echo "</tr>\n";
//end menu2

	echo "<tr>";
		echo "<td class=footer align=center" . ($skindata['mainbg'] == "" ? "" : ($skindata['mainbg']{0} == "#" ? "" : " background='$skinloc$skindata[mainbg]'")) . ">";
			echo $config['copyright'];
		echo "</td>";
	echo "</tr>";

	if($skindata['bottomBorderSize'] > 0){
		$colspan = 1;
		if($skindata['leftBorderSize'] > 0)
			$colspan++;
		if($skindata['rightBorderSize'] > 0)
			$colspan++;
		if(substr($skindata['bottomBorder'],0,1)=="#")
			echo "<tr><td height=$skindata[bottomBorderSize] bgcolor=$skindata[bottomBorder] colspan=$colspan></td></tr>";
		else
			echo "<tr><td height=$skindata[bottomBorderSize] background='$skinloc$skindata[bottomBorder]' colspan=$colspan></td></tr>";
	}

echo "</table>\n";

	showNotification();
	
	
	debugOutput();

	echo "</body></html>";
}

