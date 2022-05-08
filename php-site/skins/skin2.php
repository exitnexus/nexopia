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
	global $userData, $skindata, $config, $skindir, $siteStats, $mods, $banner;

	updateStats();

	$skindata['incCenter']=$incCenter;


	$skindata['admin']=false;
	if($userData['loggedIn']=='y')
		$skindata['admin'] = $mods->isAdmin($userData['userid']);


	ob_start();

	echo "<table cellspacing=0 cellpadding=0 width=$skindata[skinWidth]" . ($skindata['skinWidth'] == '100%' ? "" : " align=center") . ">";



	echo "<tr>";
		echo "<td class=header2" . ($skindata['mainbg'] == "" ? "" : " background='$skindir/$skindata[mainbg]'") . ">";
			echo "<table cellpadding=0 cellspacing=$skindata[cellspacing] width=100%>";
				echo "<tr>";

	// incBlocks
		if($incBlocks){
				echo "<td width=$skindata[sideWidth] valign=top>";

					echo "<table width=100% cellpadding=0 cellspacing=0>\n";

					foreach($incBlocks as $funcname)
						$funcname('l');

					echo "</table>\n";

				echo "</td>\n";
		}
	// end incBlocks

					echo "<td valign=top>";

						if($skindata['incCenter'])
							openCenter($skindata['incCenter']);

	global $msgs;
    $msgs->display();

	echo "\n\n\n";
}

function incFooter($incBlocks=false, $showright=true){
	global $userData,$skindata,$skindir,$siteStats,$config, $debuginfousers, $banner;

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

								if($showright)
									blocks('r');

								echo "</table>\n";

							echo "</td>\n";
						}
//end right bar

				echo "</tr>";
			echo "</table>";
		echo "</td>";
	echo "</tr>\n";

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

			global $adminMenu;


			$menu = array();
			foreach($adminMenu->getMenu() as $item)
				$menu[] = "<a class=menu href='$item[addr]'" . ($item['target']=='' ? "" : " target='$item[target]'" ) . ">$item[name]</a>";
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

			global $menu2;


			$menu = array();
			foreach($menu2->getMenu() as $item)
				$menu[] = "<a class=menu href='$item[addr]'" . ($item['target']=='' ? "" : " target='$item[target]'" ) . ">$item[name]</a>";
			echo implode($skindata['menudivider'],$menu);

			echo "</td><td class=menu align=right>";

			echo "Hits " . number_format($siteStats['hitstotal']) . "</a> | Users " . number_format($siteStats['userstotal']) . " &nbsp; ";

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

$banner->updateBannerHits();

	debugOutput();

	$bodytext = ob_get_clean();


	$headtext = "<html><head><title>$config[title]</title>\n";
	$headtext.= "<link rel=stylesheet href='$skindir/default.css'>\n";
	if($_SERVER['PHP_SELF'] == "/index.php"){
		$headtext.= "<meta name=\"description\" content=\"$config[metadescription]\">\n";
		$headtext.= "<meta name=\"keywords\" content=\"$config[metakeywords]\">\n";
	}
	$headtext.= "</head>\n";

	$scripttext = "<script src=$config[imgserver]/skins/general.js></script>\n";


	preg_match_all("(<script src=(.*)></script>)", $bodytext, $matches);

	foreach($matches[0] as $match){
		$scripttext .= "$match\n";
		$bodytext = str_replace($match, '', $bodytext);
	}


//	$headtext .= $script;

	echo $headtext;

//	echo $script;

//	$bodytext = $script . $bodytext;

	echo "<script>\n";


	echo "var headtext = '';\n";
	echo "var scripttext = '';\n";
	echo "var bodytext = '';\n";

	$headtext = explode("\n", str_replace("\r", "", trim($headtext)));
	foreach($headtext as $val)
		echo "headtext += \"" . str_replace("script", 'scr" + "ipt', addslashes($val)) . '\\n' . "\";\n";

	$scripttext = explode("\n", str_replace("\r", "", trim($scripttext)));
	foreach($scripttext as $val)
		echo "scripttext += \"" . str_replace("script", 'scr" + "ipt', addslashes($val)) . '\\n' . "\";\n";

	$bodytext = explode("\n", str_replace("\r", "", trim($bodytext)));
	foreach($bodytext as $val)
		echo "bodytext += \"" . str_replace("script", 'scr" + "ipt', addslashes($val)) . '\\n' . "\";\n";

	echo "var menuheight = " . ($skindata['menuheight'] + ($userData['loggedIn'] ? ($skindata['menuheight'] + $skindata['menuspacersize'] ) : 0 ) ) . ";\n";
	echo "var bannerheight = 60;\n";
	echo "var plus = " . ($userData['premium'] ? 'true' : 'false') . ";\n";

?>
if(self == top){

	alert("creating new frameset");

	var screenwidth = 0;
	var screenheight = 0;

	if(self.innerHeight){
		screenwidth = self.innerWidth;
		screenheight = self.innerHeight;
	}else if(document.documentElement && document.documentElement.offsetHeight){
		screenwidth = document.documentElement.offsetWidth;
		screenheight = document.documentElement.offsetHeight;
	}else if(document.body){
		screenwidth = document.body.offsetWidth;
		screenheight = document.body.offsetHeight;
	}

	if(!plus && screenheight > 500 && screenwidth > 900)
		bannerheight = 90;

	document.write('<frameset rows=' + (menuheight + bannerheight) + ',* frameborder=0 border=0><frame src="header.php?height=' + bannerheight + '" name=head scrolling=no noresize marginwidth=0 marginheight=0><frame name=body marginwidth=0 marginheight=0></frameset>');

	top.body.document.open();
//alert("1");
	top.body.document.write(headtext);
//alert("2");
	top.body.document.write(scripttext);
//alert("3");
	top.body.document.write('<body>');
//alert("4");
	top.body.document.write(bodytext);
//alert("5");
	top.body.document.write('</body>');
//alert("6");
	top.body.document.close();
//alert("7");

}else{
//	var x;

//	document.write('<body>');
	document.write(scripttext);

	top.head.updateStats(<? echo "$userData[friendsonline], $siteStats[online], $siteStats[guests], $userData[newmsgs], $userData[newcomments]"; ?>);

//	document.write(bodytext);
//	document.write('</body>');
}

<?


	echo "</script>\n";

	echo "<body>\n";
	echo "<script>document.write(bodytext);</script>\n";
	echo "</body>\n";

	echo "</html>";
}

function createHeader($size){
	global $skindir, $skindata, $config, $siteStats, $userData;

	updateStats();

	echo "<html><head><title>$config[title]</title><script src=$config[imgserver]/skins/general.js></script>";
	echo "<link rel=stylesheet href='$skindir/default.css'>";

	echo "</head>\n";
	echo "<body " . ($skindata['backgroundpic'] ? "background='$skindir/$skindata[backgroundpic]' " : "" ) . ">\n";

	echo "<table cellspacing=0 cellpadding=0 width=$skindata[skinWidth]" . ($skindata['skinWidth'] == '100%' ? "" : " align=center") . ">";


	echo "<tr>";


		if($size == 90)
			$header = $skindata['headerbig'];
		else
			$header = ($userData['loggedIn'] && $userData['premium'] ? $skindata['headerplus'] : $skindata['headersmall']);



		echo "<td bgcolor=#000000 background='$skindir/$header' align=right height=$size valign=$skindata[bannervalign]>";
			if($skindata['floatinglogo']!="")
				echo "<img src='$skindir/$skindata[floatinglogo]' align=$skindata[floatinglogovalign]>";
		echo "</td>";


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

			global $mainMenu, $personalMenu;


			$menu = array();
			foreach($mainMenu->getMenu() as $item)
				$menu[] = "<a class=menu href='$item[addr]'" . ($item['target']=='' ? " target='body'" : " target='$item[target]'" ) . ">$item[name]</a>";
			echo implode($skindata['menudivider'],$menu);

			echo "</td><td class=menu align=right>";
//*
			echo "Online: ";
			if($userData['loggedIn'])
				echo "<a class=menu href=/friends.php target='body'>Friends <span id=friends>$userData[friendsonline]</span></a> | ";
			echo "<a class=menu href='/profile.php?sort[online]=y&sort[list]=y' target='body'>Users <span id=users>$siteStats[online]</span></a> | Guests <span id=guests>$siteStats[guests]</span> &nbsp;";

/*/
			if($userData['loggedIn'])
				echo "<a class=menu href=/friends.php target='body'>Friends Online $userData[friendsonline]</a> | ";
			echo "<a class=menu href='/profile.php?sort[online]=y&sort[list]=y' target='body'>Users Online $siteStats[online]</a> | Guests Online $siteStats[guests] &nbsp;";
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
				foreach($personalMenu->getMenu() as $item)
					$menu[] = "<a class=menu href='$item[addr]'" . ($item['target']=='' ? " target='body'" : " target='$item[target]'" ) . ">$item[name]</a>";
				echo implode($skindata['menudivider'],$menu);

				echo "</td><td class=menu align=right>";

				echo "<a class=menu href='messages.php' target='body'>Messages</a><a class=menu href=messages.php?action=viewnew target='body'> <span id=msgs>$userData[newmsgs]</span> New</a>";
				if($userData['enablecomments'] == 'y')
					echo " | <a class=menu href='usercomments.php' target='body'>Comments <span id=comments>$userData[newcomments]</span></a>";
				echo " &nbsp;";

				echo "</td>";
				if(!empty($skindata['menuends']))
					echo "<td class=menu align=right width=1><img src='$skindir/right$skindata[menuends]'></td>";
				echo "</tr></table>";
	//end menu
			echo "</td>";
		echo "</tr>\n";
	}
	echo "</table>";
?>
<script>

function updateStats(friends, users, guests, msgs, comments){
	putinnerHTML('friends',friends);
	putinnerHTML('users',users);
	putinnerHTML('guests',guests);
	putinnerHTML('msgs',msgs);
	putinnerHTML('comments',comments);
}


</script>
<?


	echo "</body>";
	echo "</html>";
//end personal menu
}


