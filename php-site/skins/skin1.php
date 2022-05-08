<?

function openCenter($width = true){
	echo "<table cellpadding=3 cellspacing=0 width=" . ($width === true ? "100%" : "$width align=center" ) . " style=\"border-collapse: collapse\" border=1 bordercolor=#000000>";
	echo "<tr><td class=body>";
}

function closeCenter(){
	echo "</td></tr></table>";
}

function openBlock($header,$side){
	global $skindir,$skindata;

	timeline("- $header");

	echo "<tr><td align=center width=$skindata[sideWidth]>";
	echo "<table cellpadding=0 cellspacing=0 border=0 width=100%>";
	echo "<tr><td colspan=3 background=$skindir/" . ($side=='l' ? "left" : "right") . "$skindata[blockheadpic] height=$skindata[blockheadpicsize] class=sideheader valign=bottom align=" . ($side=='l' ? "left" : "right") . ">&nbsp;&nbsp;<b>$header</b>&nbsp;&nbsp;</td></tr>";
	echo "<tr>";
	if($skindata['blockBorder']>0)
		echo "<td width=$skindata[blockBorder] class=border></td>";
	echo "<td class=side valign=top width=" . ($skindata['sideWidth'] - 2*$skindata['blockBorder']) . ">";
}

function closeBlock(){
	global $skindata,$skindata;

	echo "</td>";
	if($skindata['blockBorder']>0)
		echo "<td width=$skindata[blockBorder] class=border></td>";
	echo "</tr>";
	if($skindata['blockBorder']>0)
		echo "<tr><td colspan=3 height=$skindata[blockBorder] class=border></td></tr>";
	echo "</table>";
	echo "</td></tr>";
	echo "<tr><td height=$skindata[cellspacing]></td></tr>\n";
}

function incHeader($incCenter=true, $incLeftBlocks=false, $incRightBlocks=false){
	global $userData, $skindata, $config, $skindir, $siteStats, $mods, $banner, $menus;

	timeline('start header');

	updateStats();

	timeline('- done stats');

	$skindata['incCenter'] = $incCenter;
	$skindata['rightblocks'] = $incRightBlocks;

	$skindata['admin']=false;
	if($userData['loggedIn']=='y')
		$skindata['admin'] = $mods->isAdmin($userData['userid']);

	$skindata['rows'] = 5;
	if($userData['loggedIn']) //user menu
		$skindata['rows']++;
	if($skindata['admin']) //admin menu
		$skindata['rows']++;

	echo "<html><head><title>$config[title]</title><script src=$config[imgserver]/skins/general.js></script>";
	echo "<link rel=stylesheet href='$skindir/default.css'>";
	if($_SERVER['PHP_SELF'] == "/index.php"){
		echo "<meta name=\"description\" content=\"$config[metadescription]\">\n";
		echo "<meta name=\"keywords\" content=\"$config[metakeywords]\">\n";
	}

	echo "</head>\n";
	echo "<body " . ($skindata['backgroundpic'] ? "background='$skindir/$skindata[backgroundpic]' " : "" ) . "onLoad='init()'>\n";

	echo "<table cellspacing=0 cellpadding=0 width=$skindata[skinWidth]" . ($skindata['skinWidth'] == '100%' ? "" : " align=center") . ">";

	if($skindata['topBorderSize'] > 0){
		$colspan = 1;
		if($skindata['leftBorderSize'] > 0)
			$colspan++;
		if($skindata['rightBorderSize'] > 0)
			$colspan++;
		if(substr($skindata['topBorder'],0,1)=="#")
			echo "<tr><td height=$skindata[topBorderSize] bgcolor=$skindata[topBorder] colspan=$colspan></td></tr>";
		else
			echo "<tr><td height=$skindata[topBorderSize] background='$skindir/$skindata[topBorder]' colspan=$colspan></td></tr>";
	}

	echo "<tr>";

		if($skindata['leftBorderSize'] > 0){
			if(substr($skindata['leftBorder'],0,1)=="#")
				echo "<td rowspan=$skindata[rows] width=$skindata[leftBorderSize] bgcolor=$skindata[leftBorder]></td>";
			else
				echo "<td rowspan=$skindata[rows] width=$skindata[leftBorderSize] background='$skindir/$skindata[leftBorder]'></td>";
		}

		echo "<td bgcolor=#000000 background='$skindir/$skindata[headerpic]' align=right height=$skindata[headerheight] valign=$skindata[bannervalign]>";
			if($skindata['floatinglogo']!="")
				echo "<img src='$skindir/$skindata[floatinglogo]' align=$skindata[floatinglogovalign]>";
/*
			$bannertext = $banner->getbanner(BANNER_BANNER);//'468x60');
//			$bannertext = $banner->getbanner('728x90');
			if($bannertext!=""){
				echo "<table cellspacing=$skindata[banneroffset] cellpadding=$skindata[bannerborder]><tr><td bgcolor=$skindata[bannerbordercolor]>";
				echo $bannertext;
				echo "</td></tr></table>";
			}
//*/
		echo "</td>";

		if($skindata['rightBorderSize'] > 0){
			if(substr($skindata['rightBorder'],0,1)=="#")
				echo "<td rowspan=$skindata[rows] width=$skindata[rightBorderSize] bgcolor=$skindata[rightBorder]></td>";
			else
				echo "<td rowspan=$skindata[rows] width=$skindata[rightBorderSize] background='$skindir/$skindata[rightBorder]'></td>";
		}

	echo "</tr>\n";

	echo "<tr>";
		if(substr($skindata['menupic'],0,1)=="#")
			echo "<td height=$skindata[menuheight] bgcolor=$skindata[menupic]>";
		else
			echo "<td height=$skindata[menuheight] background='$skindir/$skindata[menupic]'>";


//start menu
			echo "<table cellspacing=0 cellpadding=0 width=100%><tr>";
			if(!empty($skindata['menuends']))
				echo "<td class=menu align=left width=1><img src='$skindir/left$skindata[menuends]'></td>";

			echo "<td class=menu align=left>&nbsp;&nbsp;&nbsp;&nbsp;";


			$menu = array();
			foreach($menus['main']->getMenu() as $item)
				$menu[] = "<a href='$item[addr]'" . ($item['target']=='' ? "" : " target='$item[target]'" ) . ">$item[name]</a>";
			echo implode($skindata['menudivider'],$menu);

			echo "</td><td class=menu align=right>";
//*
			echo "Online: ";
			if($userData['loggedIn'])
				echo "<a href=/friends.php>Friends $userData[friendsonline]</a> | ";
			echo "<a href='/profile.php?sort[active]=2&sort[list]=y'>Users $siteStats[online]</a> | Guests $siteStats[guests] &nbsp;";

/*/
			if($userData['loggedIn'])
				echo "<a href=/friends.php>Friends Online $userData[friendsonline]</a> | ";
			echo "<a href='/profile.php?sort[online]=y&sort[list]=y'>Users Online $siteStats[online]</a> | Guests Online $siteStats[guests] &nbsp;";
//*/
			echo "</td>";
			if(!empty($skindata['menuends']))
				echo "<td class=menu align=right width=1><img src='$skindir/right$skindata[menuends]'></td>";
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
				echo "<tr><td height=$skindata[menuspacersize] background='$skindir/$skindata[menuspacer]'></td></tr>";
		}

		echo "<tr>";
			if(substr($skindata['menupic'],0,1)=="#")
				echo "<td height=$skindata[menuheight] bgcolor=$skindata[menupic]>";
			else
				echo "<td height=$skindata[menuheight] background='$skindir/$skindata[menupic]'>";


	//start menu
				echo "<table cellspacing=0 cellpadding=0 width=100%><tr>";
				if(!empty($skindata['menuends']))
					echo "<td class=menu align=left width=1><img src='$skindir/left$skindata[menuends]'></td>";
				echo "<td class=menu align=left>&nbsp;&nbsp;&nbsp;&nbsp;";

				$menu = array();
				foreach($menus['personal']->getMenu() as $item)
					$menu[] = "<a href='$item[addr]'" . ($item['target']=='' ? "" : " target='$item[target]'" ) . ">$item[name]</a>";
				echo implode($skindata['menudivider'],$menu);

				echo "</td><td class=menu align=right>";

				echo "<a href='messages.php'>Messages</a><a href=messages.php?action=viewnew> $userData[newmsgs] New</a>"; //&k=" . makekey('newmsgs') . "
				if($userData['enablecomments'] == 'y')
					echo " | <a href='usercomments.php'>Comments $userData[newcomments]</a>";
				echo " &nbsp;";

				echo "</td>";
				if(!empty($skindata['menuends']))
					echo "<td class=menu align=right width=1><img src='$skindir/right$skindata[menuends]'></td>";
				echo "</tr></table>";
	//end menu
			echo "</td>";
		echo "</tr>\n";
	}
//end personal menu

	echo "<tr>";
		echo "<td class=header2" . ($skindata['mainbg'] == "" ? "" : " background='$skindir/$skindata[mainbg]'") . ">";
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
		if(!$incBlocks)
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
    $msgs->display();

	timeline('end header');

	echo "\n\n\n";
}

function incFooter(){
	global $userData,$skindata,$skindir,$siteStats,$config, $debuginfousers, $banner, $menus;

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
			echo "<td height=$skindata[menuheight] background='$skindir/$skindata[menupic]'>";

			echo "<table cellspacing=0 cellpadding=0 width=100%><tr>";
			if(!empty($skindata['menuends']))
				echo "<td class=menu align=left width=1><img src='$skindir/left$skindata[menuends]'></td>";

			echo "<td class=menu align=left>&nbsp;&nbsp;&nbsp;&nbsp;";


			$menu = array();
			foreach($menus['admin']->getMenu() as $item)
				$menu[] = "<a href='$item[addr]'" . ($item['target']=='' ? "" : " target='$item[target]'" ) . ">$item[name]</a>";
			echo implode($skindata['menudivider'],$menu);

			echo "</td>";
			if(!empty($skindata['menuends']))
				echo "<td class=menu align=right width=1><img src='$skindir/right$skindata[menuends]'></td>";
			echo "</tr></table>";
		echo "</td>";
	echo "</tr>";
	if($skindata['menuspacersize'] > 0){
		if(substr($skindata['menuspacer'],0,1)=="#")
			echo "<tr><td height=$skindata[menuspacersize] bgcolor=$skindata[menuspacer]></td></tr>";
		else
			echo "<tr><td height=$skindata[menuspacersize] background='$skindir/$skindata[menuspacer]'></td></tr>";
	}
	echo "\n";
}
//end admin menu



//start menu2
	echo "<tr>";
		if(substr($skindata['menupic'],0,1)=="#")
			echo "<td height=$skindata[menuheight] bgcolor=$skindata[menupic]>";
		else
			echo "<td height=$skindata[menuheight] background='$skindir/$skindata[menupic]'>";

			echo "<table cellspacing=0 cellpadding=0 width=100%><tr>";
			if(!empty($skindata['menuends']))
				echo "<td class=menu align=left width=1><img src='$skindir/left$skindata[menuends]'></td>";

			echo "<td class=menu align=left>&nbsp;&nbsp;&nbsp;&nbsp;";


			$menu = array();
			foreach($menus['bottom']->getMenu() as $item)
				$menu[] = "<a href='$item[addr]'" . ($item['target']=='' ? "" : " target='$item[target]'" ) . ">$item[name]</a>";
			echo implode($skindata['menudivider'],$menu);

			echo "</td><td class=menu align=right>";

			echo "Hits " . number_format($siteStats['hitstotal']) . " | Users " . number_format($siteStats['userstotal']) . " &nbsp; ";

			echo "</td>";
			if(!empty($skindata['menuends']))
				echo "<td class=menu align=right width=1><img src='$skindir/right$skindata[menuends]'></td>";

			echo "</tr></table>";
		echo "</td>";
	echo "</tr>\n";
//end menu2

	echo "<tr>";
		echo "<td class=footer align=center" . ($skindata['mainbg'] == "" ? "" : " background='$skindir/$skindata[mainbg]'") . ">";
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
			echo "<tr><td height=$skindata[bottomBorderSize] background='$skindir/$skindata[bottomBorder]' colspan=$colspan></td></tr>";
	}

echo "</table>\n";

	debugOutput();

	echo "</body></html>";
}


