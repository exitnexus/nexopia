<?

function openCenter($width = true){
	echo "<table cellpadding=3 cellspacing=0 width=" . ($width === true ? "100%" : "$width align=center" ) . " style=\"border-collapse: collapse; margin: auto;\" border=1 bordercolor=#000000>";
	echo "<tr><td class=body>";
}

function closeCenter(){
	echo "</td></tr></table>";
}

function incHeader($incCenter=true, $incLeftBlocks=false, $incRightBlocks=false, $skeleton="NullSkeleton", $scripts=array(), $userskinpath=false){
	global $userData, $skindata, $cache, $config, $skinloc, $siteStats, $mods, $banner, $menus, $_RUBY;

	timeline('start header');

	updateStats();

	timeline('- done stats');

	$menus = $cache->hdget("menus", 0, 'makeMenus');
	
	if(!isset($scripts) || count($scripts) == 0)
	{
		$scripts = $_RUBY["scripts"];
	}
	
	$skindata['scripts'] = $scripts;
	$skindata['skeleton'] = $skeleton;
	$skindata['userskinpath'] = $userskinpath;
	$skindata['incCenter'] = $incCenter;
	$skindata['rightblocks'] = $incRightBlocks;

	$skindata['admin']=false;
	if($userData['loggedIn']=='y')
		$skindata['admin'] = $mods->isAdmin($userData['userid']);


	ob_start();
	


	echo "<table id=sitebody cellspacing=0 cellpadding=0 width=$skindata[skinWidth]" . ($skindata['skinWidth'] == '100%' ? "" : " align=center") . ">";



	echo "<tr>";
		echo "<td class=header2" . ($skindata['mainbg'] == "" ? "" : ($skindata['mainbg']{0} == '#' ? "" : " background='$skinloc$skindata[mainbg]'")) . " " .(array_key_exists('mainbgnorepeat', $skindata) ? ($skindata['mainbgnorepeat'] ? "style=\"background-repeat: no-repeat;\"" : "") : "")." >"; // bgcolor=$skindata[mainbg], is the correct way, but skins don't expect it.
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

						if($skindata['incCenter'])
							openCenter($skindata['incCenter']);

	global $msgs;
    echo $msgs->get();

	timeline('end header');

	echo "\n\n\n";
}

function incFooter(){
	global $userData,$skindata,$skinloc,$siteStats,$config, $debuginfousers, $banner, $menus, $weblog, $reporev, $staticimgdomain, $staticdomain, $staticbasedomain, $wwwdomain;
	echo "\n\n\n";

	timeline('start footer');

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

			global $menu2;


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
		echo "<td class=footer align=center" . ($skindata['mainbg'] == "" ? "" : ($skindata['mainbg']{0} == "#" ? "" : (array_key_exists('mainbgnorepeat', $skindata) ? ($skindata['mainbgnorepeat'] ? "" : " background='$skinloc$skindata[mainbg]'") : " background='$skinloc$skindata[mainbg]'"))) . ">";
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

	$bodytext = ob_get_clean();


	$headtext = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
	$headtext.= "\n<html><head>\n";
	$headtext.= "<meta name=\"verify-v1\" content=\"/88/A5pFXNbF4qL57WPM0suJJt9lcMU5iAflUN/6nzc=\" />";
	$headtext.= "<title>$config[title]</title>";
	$headtext.= "<link rel=stylesheet href='$skinloc" . "default.css'>\n";
	$headtext .= "<link rel=\"stylesheet\" href=\"$config[yuiloc]build/container/assets/container.css\"/>";
	$headtext .= "<link rel=\"stylesheet\" href=\"$config[yuiloc]/yui_nexopia.css\"/>";
	
	foreach ($skindata['scripts'] as $script) {
		$headtext .= "<script type=\"text/javascript\" src=\"$script\"></script>";
	}
	
	if ($skindata['skeleton']) {
		$structure_name = $skindata['skeleton'];
		$skin_name = $skindata['name'];
		$headtext .= "<link rel=\"stylesheet\" href=\"http://$staticbasedomain/style/$structure_name/$skin_name.css\"/>";
		$headtext .= "<link rel=\"apple-touch-icon\" href=\"http://$staticbasedomain/files/$structure_name/nexopia_iphone.png\"/>";
	}

	if ($skindata['userskinpath']) {
		$headtext .= "<link rel=\"stylesheet\" href=\"$skindata[userskinpath]\"/>";
	}

	if($_SERVER['PHP_SELF'] == "/index.php"){
		$headtext.= "<meta name=\"description\" content=\"$config[metadescription]\">\n";
		$headtext.= "<meta name=\"keywords\" content=\"$config[metakeywords]\">\n";
	}
	$headtext.= "<script src=$config[jsloc]general.js></script>\n";
	$headtext.= "</head>\n";


	echo $headtext;

	$cachekey = makekey("cache-$_SERVER[REQUEST_URI]");

	$bodyname = strtoupper(substr(base_convert(md5(gettime()), 16, 36), 0, 8));

	global $pagecache;

	$pagecache->put("page-cache-$cachekey", "$headtext<body>$bodytext</body></html>", 60);

	$newreplies = 0;
	if ($userData['loggedIn'])
	{
		$userblog = new userblog($weblog, $userData['userid']);
		$newreplies = $userblog->getNewReplyCountTotal();
	}

	echo "<script>";

//try to update the header stats
	echo "try{";
	//if the site stats failed, don't output invalid javascript
		if(!isset($siteStats['onlineusers']))
			$siteStats['onlineusers'] = 0;
		if(!isset($siteStats['onlineguests']))
			$siteStats['onlineguests'] = 0;

		$replies = ($newreplies? "'$newreplies Repl" . ($newreplies==1? 'y' : 'ies') . "'" : "''");

		if($userData['loggedIn']) {
			$commentscount = getGalleryComments($userData['userid']) + $userData['newcomments'];
			echo "top.head.updateStats($userData[friendsonline],$siteStats[onlineusers],$siteStats[onlineguests],$userData[newmsgs],$replies," . ($userData['enablecomments'] == 'y' ? $commentscount : '-1') . "," . $banner->getpageid() . ");" ;
		} else
			echo "top.head.updateStats(-1,$siteStats[onlineusers],$siteStats[onlineguests],-1,-1,-1," . $banner->getpageid() . ");" ;
	echo "}catch(err){";
	//can fail because there is no header, or because it's a different website and not allowed to cross domains
		echo "if(self == top){";
			$menuheight = $skindata['menuheight'] + $skindata['menuguttersize'];
			if($userData['halfLoggedIn'])
				$menuheight += $skindata['menuheight'] + $skindata['menuspacersize'];

			if($userData['limitads'] && $userData['premium']){ //plus always has the small header
				echo "h=60;";
			}else{
				echo "s=getWindowSize();";
				echo "h=(s[0]>900&&s[1]>500?90:60);"; //show big or small?
			}
			echo "document.write('";
				echo "<frameset rows=\"'+($menuheight+h)+',*\" frameborder=0 border=0>";
				echo "<frame id=\"headerframe\" src=\"/header.php?bodyname=$bodyname&height='+h+'&pageid=" . $banner->getpageid() . "\" name=head scrolling=no noresize marginwidth=0 marginheight=0>";
				echo "<frame src=\"" . addslashes($_SERVER['REQUEST_URI']) . (strpos($_SERVER['REQUEST_URI'], '?') ? '&' : '?') . "cachekey=$cachekey\" name=\"$bodyname\" marginwidth=0 marginheight=0>";
				echo "</frameset>";
			echo "');";
		echo "}else{";
			echo "top.location.href = self.location.href;";
		echo "}";
	echo "}";

	echo "</script>\n";

	echo "<body onLoad='initFrames()'>\n";
	$tags = array("div", "form", "h1", "h2", "h3", "h4", "h5", "h6", "ol", "p", "pre", "table", "ul", "li", "td", "tbody", "th", "tr");

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

	echo "<noscript><style>$block_tags { display:block; } $block_tags2 { display:none; } .noscript { display:inline;} .script { display:none;}
		thead.noscript {display:table-header-group;}
		tfoot.noscript {display:table-footer-group;}
		tr.noscript {display:table-row;}
		table.noscript {display:table;}
		thead.script, tfoot.script, tr.script, table.script {display:none;}
	</style></noscript>";

	echo $bodytext;

	// Delayed loading of Google analytics script.
	echo '<script type="text/javascript">
		YAHOO.util.Event.addListener(this, "load", function() {
			var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
			var objTransaction = YAHOO.util.Get.script(gaJsHost + "google-analytics.com/ga.js", {
				onSuccess: function() {
					var pageTracker = _gat._getTracker("UA-5204531-1");
					pageTracker._trackPageview();
				}
			});
		});
	</script>';

	// Others Online script
	echo '<script type="text/javascript">
		YAHOO.util.Event.addListener(this, "load", function() {
			var head = document.getElementsByTagName("head")[0];
			var script = document.createElement("script");
			script.src = "http://www.othersonline.com/partner/scripts/nexopia/alice.js?autorun=true";
			head.appendChild(script);
		});
	</script>';

	echo "</body>\n";

	echo "</html>";
}

function createHeader($size, $bodyname, $pageid){
	global $skinloc, $skindata, $config, $siteStats, $userData, $banner, $menus, $cache, $weblog, $reporev, $_RUBY, $staticimgdomain, $staticbasedomain;
	
	$bodyname = preg_replace("/[^_0-9a-zA-Z]+/", "", $bodyname);

	$menus = $cache->hdget("menus", 0, 'makeMenus');
	
	$scripts = array();
	if(!isset($skindata['scripts']))
	{
		if(isset($_RUBY))
		{
			$scripts = $_RUBY['scripts'];
		}
	}
	
	updateStats();

	closeAllDBs();

	echo "<html><head><title>$config[title]</title><script src=$config[jsloc]general.js></script>";
	echo "<link rel=stylesheet href='$skinloc" . "default.css'>";
	/*
	if (isset($skindata['skeleton'])) {
		$structure_name = $skindata['skeleton'];
	}
	else {
		$structure_name = "NullSkeleton";
	}
	
	$skin_name = $skindata['name'];
	echo "<link rel=\"stylesheet\" href=\"http://$staticbasedomain/style/$structure_name/$skin_name.css\"/>";
	*/
	echo "<meta name=\"verify-v1\" content=\"/88/A5pFXNbF4qL57WPM0suJJt9lcMU5iAflUN/6nzc=\" />";
	
	$headtext = "";
/*
	foreach ($scripts as $script) {
		$headtext .= "<script type=\"text/javascript\" src=\"$script\"></script>";
	}
	$headtext .= "<link rel=\"stylesheet\" href=\"/static/$reporev/files/Yui/build/container/assets/container.css\"/>";
	*/
	
	echo $headtext;
	echo "<style>";
	echo "#interstitial_display_head_mask{opacity:0.80;*filter:alpha(opacity=80);background-color: #252525;}";
	echo "#interstitial_display_head{display:none;}";
	echo "</style>";
	echo "</head>\n";
	echo "<body" . ($skindata['backgroundpic'] ? " background='$skinloc$skindata[backgroundpic]' " : "" ) . ">\n";

	echo "<table id=sitebody nuts=boo cellspacing=0 cellpadding=0 width=$skindata[skinWidth]" . ($skindata['skinWidth'] == '100%' ? "" : " align=center") . ">";


	echo "<tr>";


		if($size == 90)
			$header = $skindata['headerbig'];
		else
			$header = (($userData['limitads'] && $userData['premium']) ? $skindata['headerplus'] : $skindata['headersmall']);

		

		echo "<td bgcolor=".(array_key_exists('headerbackgroundcolor', $skindata) ? $skindata['headerbackgroundcolor'] : "#000000") ." background='$skinloc$header' align=right height=$size valign=top ".(array_key_exists('headernorepeat', $skindata) ? ($skindata['headernorepeat'] == "" ? "":"style=\"".$skindata['headernorepeat']."\"") : "")." >";
			if(!($userData['limitads'] && $userData['premium'])){
				echo "<iframe src='/bannerview.php?size=" . ($size == 90 ? BANNER_LEADERBOARD : BANNER_BANNER) . "&pageid=$pageid' marginHeight=0 marginWidth=0 frameborder=0 name=banner scrolling=no ";
				echo ($size == 90 ? "width=728 height=90" : "width=468 height=60");
				echo " allowtransparency=true background-color=\"transparent\"></iframe>";
			}

		echo "</td>";


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
				$menu[] = "<a href='$item[addr]'" . ($item['target']=='' ? " target='$bodyname'" : " target='$item[target]'" ) . ">$item[name]</a>";
			echo implode($skindata['menudivider'],$menu);

			echo "</td><td class=menu align=right>";

			echo "Online: ";
			if($userData['halfLoggedIn'])
				echo "<a href='/users/" . urlencode($userData['username']) . "/friends' target='$bodyname'>Friends <span id=friends>$userData[friendsonline]</span></a>" . $skindata['menudivider'];
			echo "<a href='/profile.php?requestType=onlineByPrefs' target='$bodyname'>Users <span id=users>$siteStats[onlineusers]</span></a>" . $skindata['menudivider'] . "Guests <span id=guests>$siteStats[onlineguests]</span> &nbsp;";

			echo "</td>";
			if(!empty($skindata['menuends']))
				echo "<td class=menu align=right width=1><img src='$skinloc" . "right$skindata[menuends]'></td>";
			echo "</tr></table>";
//end menu
		echo "</td>";
	echo "</tr>\n";

//personal menu
	if($userData['halfLoggedIn']){
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
					$menu[] = "<a href='$item[addr]'" . ($item['target']=='' ? " target='$bodyname'" : " target='$item[target]'" ) . ">$item[name]</a>";
				echo implode($skindata['menudivider'],$menu);

				echo "</td><td class=menu align=right>";

				if ($userData['loggedIn'])
					echo "<a href='/messages.php' target='$bodyname'>Messages</a><a href=/messages.php?action=viewnew target='$bodyname'> <span id=msgs>$userData[newmsgs]</span> New</a>" . $skindata['menudivider']; //&k=" . makekey('newmsgs') . "

				$userblog = new userblog($weblog, $userData['userid']);
				$newreplies = $userblog->getNewReplyCountTotal();
				$ending = ($newreplies==1? 'y' : 'ies');
				// echo "<img border='0' src='http://$staticimgdomain/icons/new_icon.gif'/>&nbsp;";
				echo "<a href='/users/" . urlencode($userData['username']) . "/blog' target='$bodyname'>Blog</a> <a href='/my/blog/new/replies' target='$bodyname'>";
				$replies = ($newreplies ? "$newreplies Repl$ending" : '');
				echo "<span id=replies>$replies</span></a>";

				if($userData['loggedIn'] && $userData['enablecomments'] == 'y') {
					$commentscount = getGalleryComments($userData['userid']) + $userData['newcomments'];
					echo $skindata['menudivider'] . "<a href='/users/". urlencode($userData['username']) ."/comments' target='$bodyname'>Comments <span id=comments>$commentscount</span></a>";
				}
				echo " &nbsp;";

				echo "</td>";
				if(!empty($skindata['menuends']))
					echo "<td class=menu align=right width=1><img src='$skinloc" . "right$skindata[menuends]'></td>";
				echo "</tr></table>";
	//end menu
			echo "</td>";
		echo "</tr>\n";
	}

	if($skindata['menuguttersize'] > 0){
		if(substr($skindata['menugutter'],0,1)=="#")
			echo "<tr><td height=$skindata[menuguttersize] bgcolor=$skindata[menugutter]></td></tr>";
		else
			echo "<tr><td height=$skindata[menuguttersize] background='$skinloc$skindata[menugutter]'></td></tr>";
	}

	echo "</table>";
	echo "<div id='interstitial_head' minion_name='interstitial_head'></div>";
	
	echo "<script>";
	echo "var timeout=-1,";
	echo "running=false,";
	echo "inactive=0,";
	echo "pageid=" . $pageid . ";";
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
			echo "top.head.banner.location.replace('/bannerview.php?size=" . ($size == 90 ? BANNER_LEADERBOARD : BANNER_BANNER) . "&pageid=' + pageid);";
//			echo "top.head.banner.location.href='/bannerview.php?size=" . ($size == 90 ? BANNER_LEADERBOARD : BANNER_BANNER) . "&pageid=' + pageid;";
		echo "}";
	echo "}";

	echo "function updateStats(friends,users,guests,msgs,replies,comments,npageid){";
		echo "if(friends>=0)putinnerHTML('friends',friends);";
		echo "if(users>=0)putinnerHTML('users',users);";
		echo "if(guests>=0)putinnerHTML('guests',guests);";
		echo "if(msgs>=0)putinnerHTML('msgs',msgs);";
		echo "if(replies!=-1)putinnerHTML('replies',replies);";
		echo "if(comments>=0)putinnerHTML('comments',comments);";

		echo "pageid=npageid;";

		echo "inactive = 0;";
		echo "if(!running)";
			echo "starttimer();";
	echo "}";

	echo "</script>";

	echo "</body>";
	echo "</html>";
//end personal menu
}


