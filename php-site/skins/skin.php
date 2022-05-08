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

function incHeader($incCenter=true,$incBlocks=false){
	global $userData,$skindata,$config,$skindir,$siteStats,$PHP_SELF, $mods;

	updateStats();

	$skindata['incCenter']=$incCenter;


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
	if($PHP_SELF == "/index.php"){
		echo "<meta name=\"description\" content=\"$config[metadescription]\">\n";
		echo "<meta name=\"keywords\" content=\"$config[metakeywords]\">\n";
	}

	echo "</head>\n";
	echo "<body " . ( $skindata['backgroundpic'] == "" ? "" : "background='$skindir/$skindata[backgroundpic]' " ) . "onLoad='init()'>\n";

echo "<table cellspacing=0 cellpadding=0 width=100%>";

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
			$banner = banner('468x60');
			if($banner!=""){
				echo "<table cellspacing=$skindata[banneroffset] cellpadding=$skindata[bannerborder]><tr><td bgcolor=$skindata[bannerbordercolor]>";
				echo $banner;
				echo "</td></tr></table>";
			}
			if($skindata['floatinglogo']!=""){
				echo "<img src='$skindir/$skindata[floatinglogo]' align=$skindata[floatinglogovalign]>";
			}
		echo "</td>";

		if($skindata['rightBorderSize'] > 0){
			if(substr($skindata['rightBorder'],0,1)=="#")
				echo "<td rowspan=$skindata[rows] width=$skindata[rightBorderSize] bgcolor=$skindata[rightBorder]></td>";
			else
				echo "<td rowspan=$skindata[rows] width=$skindata[rightBorderSize] background='$skindir/$skindata[rightBorder]'></td>";
		}

	echo "</tr>";
	echo "<tr>";
		if(substr($skindata['menupic'],0,1)=="#")
			echo "<td height=$skindata[menuheight] bgcolor=$skindata[menupic]>";
		else
			echo "<td height=$skindata[menuheight] background='$skindir/$skindata[menupic]'>";


//start menu
			echo "<table cellspacing=0 cellpadding=2 width=100%><tr><td class=menu align=left>&nbsp;&nbsp;&nbsp;&nbsp;";

			global $mainMenu, $personalMenu;


			$menu = array();
			foreach($mainMenu->getMenu() as $item)
				$menu[] = "<a class=menu href='$item[addr]'" . ($item['target']=='' ? "" : " target='$item[target]'" ) . ">$item[name]</a>";
			echo implode($skindata['menudivider'],$menu);

			echo "</td><td class=menu align=right>";

			if($userData['loggedIn'])
				echo "<a class=menu href='friends.php?sortd=DESC&sortt=online'>Friends Online $siteStats[friends]</a> | ";
			echo "<a class=menu href='/profile.php?sort[online]=y&sort[list]=y'>Users Online $siteStats[online]</a> | Guests Online $siteStats[guests] &nbsp;";

			echo "</td></tr></table>";
//end menu
		echo "</td>";
	echo "</tr>";

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
				echo "<table cellspacing=0 cellpadding=2 width=100%><tr><td class=menu align=left>&nbsp;&nbsp;&nbsp;&nbsp;";

				$menu = array();
				foreach($personalMenu->getMenu() as $item)
					$menu[] = "<a class=menu href='$item[addr]'" . ($item['target']=='' ? "" : " target='$item[target]'" ) . ">$item[name]</a>";
				echo implode($skindata['menudivider'],$menu);

				echo "</td><td class=menu align=right>";

				echo "<a class=menu href='messages.php'>Messages</a>";
				if($siteStats['newmsgs'])
					echo "<a class=menu href=messages.php?action=viewnew> $siteStats[newmsgs] New</a>";
				echo " | <a class=menu href='usercomments.php?id=$userData[userid]'>Comments $userData[newcomments]</a> &nbsp;";

				echo "</td></tr></table>";
	//end menu
			echo "</td>";
		echo "</tr>";
	}
//end personal menu

	echo "<tr>";
		echo "<td class=header2 " . ($skindata['mainbg'] == "" ? "" : "background='$skindir/$skindata[mainbg]'") . ">";
			echo "<table cellpadding=0 cellspacing=$skindata[cellspacing] width=100%>";
				echo "<tr>";

	// incBlocks
		if($incBlocks){
				echo "<td width=$skindata[sideWidth] valign=top>";

					echo "<table width=100% cellpadding=0 cellspacing=0>\n";

					foreach($incBlocks as $funcname)
						$funcname('l');

					echo "</table>\n";

				echo "</td>";
		}
	// end incBlocks

					echo "<td valign=top>";
						if($skindata['incCenter'])
							openCenter($skindata['incCenter']);

	global $msgs;
    $msgs->display();

	echo "\n\n\n";
}

function incFooter($incBlocks=false){
	global $userData,$skindata,$skindir,$siteStats,$config, $debuginfousers;

	echo "\n\n\n";

						if($skindata['incCenter'])
							closeCenter();
					echo "</td>";

//start right bar
						if($incBlocks || ($userData['loggedIn'] && $userData['showrightblocks']=='y')){


							echo "<td width=$skindata[sideWidth] height=100% valign=top>\n";

								echo "<table cellpadding=0 cellspacing=0>\n";

								if($incBlocks)
									foreach($incBlocks as $funcname)
										$funcname('r');

								blocks('r');

								echo "</table>\n";

							echo "</td>\n";
						}
//end right bar

				echo "</tr>";
			echo "</table>";
		echo "</td>";
	echo "</tr>";

//start admin menu
if($skindata['admin']){
	echo "<tr>";
		if(substr($skindata['menupic'],0,1)=="#")
			echo "<td height=$skindata[menuheight] bgcolor=$skindata[menupic]>";
		else
			echo "<td height=$skindata[menuheight] background='$skindir/$skindata[menupic]'>";

			echo "<table cellspacing=0 cellpadding=2 width=100%><tr><td class=menu align=left>&nbsp;&nbsp;&nbsp;&nbsp;";

			global $adminMenu;


			$menu = array();
			foreach($adminMenu->getMenu() as $item)
				$menu[] = "<a class=menu href='$item[addr]'" . ($item['target']=='' ? "" : " target='$item[target]'" ) . ">$item[name]</a>";
			echo implode($skindata['menudivider'],$menu);

			echo "</td></tr></table>";
		echo "</td>";
	echo "</tr>";
	if($skindata['menuspacersize'] > 0){
		if(substr($skindata['menuspacer'],0,1)=="#")
			echo "<tr><td height=$skindata[menuspacersize] bgcolor=$skindata[menuspacer]></td></tr>";
		else
			echo "<tr><td height=$skindata[menuspacersize] background='$skindir/$skindata[menuspacer]'></td></tr>";
	}
}
//end admin menu



//start menu2
	echo "<tr>";
		if(substr($skindata['menupic'],0,1)=="#")
			echo "<td height=$skindata[menuheight] bgcolor=$skindata[menupic]>";
		else
			echo "<td height=$skindata[menuheight] background='$skindir/$skindata[menupic]'>";

			echo "<table cellspacing=0 cellpadding=2 width=100%><tr><td class=menu align=left>&nbsp;&nbsp;&nbsp;&nbsp;";

			global $menu2;


			$menu = array();
			foreach($menu2->getMenu() as $item)
				$menu[] = "<a class=menu href='$item[addr]'" . ($item['target']=='' ? "" : " target='$item[target]'" ) . ">$item[name]</a>";
			echo implode($skindata['menudivider'],$menu);

			echo "</td><td class=menu align=right>";

			echo "Hits $siteStats[hits]</a> | Users $siteStats[totalusers] &nbsp; ";

			echo "</td></tr></table>";
		echo "</td>";
	echo "</tr>";
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

	if($userData['loggedIn'] && in_array($userData['userid'],$debuginfousers)){
		global $startTime,$parseTime;
		$endTime = gettime();
		echo "\nParse time: " . number_format(($parseTime - $startTime)/10,3) . " milliseconds<br>";
		echo "Run time: " . number_format(($endTime - $startTime)/10,4) . " milliseconds\n";

		outputQueries();
	}
	echo "</body></html>";
}


