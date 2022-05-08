<?

function incAdminBlock($side){
}

function incPollBlock($side){
	global $userData, $config, $db, $cache, $polls;

	if(!$userData['loggedIn'])
		return;

	$poll = $polls->getPoll();

	if(!$poll)
		return;

	$voted = $polls->pollVoted($poll['id']);

	openBlock('Polls',$side);

	if(!$voted){
		echo "<table border=0 cellspacing=0 cellpadding=2 width=100%><form action=poll.php method=get>";
		echo "<input type=hidden name=pollid value=$poll[id]>";
		echo "<tr><td colspan=2 class=header>$poll[question]</td></tr>";
		foreach($poll['answers'] as $ans)
			echo "<tr><td class=side width=20><input type=radio name='ans' value='$ans[id]' id='ans$ans[id]'></td><td class=side><label for='ans$ans[id]'>$ans[answer]</label></td></tr>";
		echo "<tr><td class=side></td><td class=side><input type=submit name=action value='Vote'> <a href=poll.php?pollid=$poll[id]&ans=0&action=Vote>Results</a></td></tr>";
		echo "<tr><td colspan=2 class=side align=center><a href=poll.php?action=list>List polls</a> | <a href=poll.php?action=add>Suggest a Poll</a></td></tr>";
		echo "</form></table>";
	}else{
		echo "<table width=100%>";
		echo "<tr><td class=header>$poll[question]</td></tr>";

		$maxval=0;
		foreach($poll['answers'] as $ans)
			if($ans['votes']>$maxval)
				$maxval = $ans['votes'];

		foreach($poll['answers'] as $ans){
			$width = $poll['tvotes']==0 ? 1 : (int)$ans['votes']*$config['maxpollwidth']/$maxval;
			$percent = number_format($poll['tvotes']==0 ? 0 : $ans["votes"]/$poll['tvotes']*100,1);
			echo "<tr><td class=side>$ans[answer]:</td></tr>";
			echo "<tr><td class=side><img src='$config[imageloc]red.png' width='$width' height=10> $ans[votes]</td></tr>";//$percent%
		}
		echo "<tr><td class=side>$poll[tvotes] votes | <a href=poll.php?pollid=$poll[id]>Results</a></td></tr>";
		echo "<tr><td class=side><a href=poll.php?action=list>List polls</a> | <a href=poll.php?action=add>Suggest a Poll</a></td></tr>";
		echo "</table>";
	}
	closeBlock();
}

function incBookmarksBlock($side){
	global $userData,$config,$db;

	if(!$userData['loggedIn'])
		return;

    $db->prepare_query("SELECT id,name,url FROM bookmarks WHERE userid = # ORDER BY name", $userData['userid']);

	openBlock('Bookmarks',$side);

	echo "<table width=100%>\n";
	echo "<tr><td class=header><b><a href=\"bookmarks.php\">Bookmarks</a></b></td></tr>";
	while($line = $db->fetchrow())
		echo "<tr><td class=side><a href=\"$line[url]\" target=_blank>$line[name]</a></td></tr>\n";
	echo "</table>\n";

	closeBlock();
}

function incSortBlock($side){
	global $userData, $sort, $config, $db;

	$user = '';
	$loc = '0';
	$interest = '0';
	$active = 1;
	$pic = 1;
	$sexuality = 0;
	$single = 0;

	if($userData['loggedIn']){
		$sex = $userData['defaultsex'];
		$minage = $userData['defaultminage'];
		$maxage = $userData['defaultmaxage'];
	}else{
		$sex = 'Both';
		$minage = 14;
		$maxage = 30;
	}

	if(isset($sort) && is_array($sort))
		extract($sort);

	$locations = & new category( $db, "locs");
	$interests = & new category( $db, "interests");

	openBlock('User Search',$side);

	echo "<table align=center width=98 cellspacing=0 cellpadding=0><tr><td class=side align=right>";
	if($config['votingenabled']){
		echo "<b>Top:</b> <a href='profile.php?sort[mode]=top&sort[sex]=Female'>Girls</a> | <a href='profile.php?sort[mode]=top&sort[sex]=Male'>Guys</a><br>";
		echo "<b>Rate:</b> <a href='profile.php?sort[mode]=rate&sort[sex]=Female'>Girls</a> | <a href='profile.php?sort[mode]=rate&sort[sex]=Male'>Guys</a><br>";
	}

	echo "<b>New:</b> <a href='profile.php?sort[mode]=newest&sort[sex]=Female'>Girls</a> | <a href='profile.php?sort[mode]=newest&sort[sex]=Male'>Guys</a><br>";
	echo "<b>Online:</b> <a href='profile.php?sort[active]=2&sort[sex]=Female&sort[list]=y'>Girls</a> | <a href='profile.php?sort[active]=2&sort[sex]=Male&sort[list]=y'>Guys</a><br>";
	echo "<b>B-day:</b> <a href='profile.php?sort[mode]=bday&sort[sex]=Female'>Girls</a> | <a href='profile.php?sort[mode]=bday&sort[sex]=Male'>Guys</a><br>";

	echo "</td></tr></table>";

	echo "<hr>";

	echo "<table align=center cellpadding=0 cellspacing=1 border=0>";

	echo "<form action=profile.php name=profilesort>";

/*
	echo "<tr><td class=side>&nbsp;Age <input name=sort[minage] value='$minage' size=1 style=\"width:36px\" maxlength=2> to <input class=side name=sort[maxage] value='$maxage' size=1 style=\"width:36px\" maxlength=2></td></tr>";
	echo "<tr><td class=side>&nbsp;<select style=\"width:110px\" name=sort[sex]><option value=Both>Sex" . make_select_list(array("Male","Female"), $sex) . "</select></td></tr>";
	echo "<tr><td class=side>&nbsp;<select style=\"width:110px\" name=sort[loc]><option value=0>Location" . makeCatSelect($locations->makeBranch(), $loc) . "</select></td></tr>"; //<script src=http://images.nexopia.com/include/dynconfig/locs.js></script>
	echo "<tr><td class=side>&nbsp;<select style=\"width:110px\" name=sort[interest]><option value=0>Interests" . makeCatSelect($interests->makeBranch(), $interest) . "</select></td></tr>"; //<script src=http://images.nexopia.com/include/dynconfig/interests.js></script>
	echo "<tr><td class=side>&nbsp;<select style=\"width:110px\" name=sort[active]>" . make_select_list_key(array(0 => "All Users", 1 => "Active Recently", 2 => "Online"), $active) . "</select></td></tr>";
	echo "<tr><td class=side>&nbsp;<select style=\"width:110px\" name=sort[pic]>" . make_select_list_key(array(0 => "All Users", 1 => "With Pictures", 2 => "With a Verified Picture"), $pic) . "</select></td></tr>";
	echo "<tr><td class=side>&nbsp;<select style=\"width:110px\" name=sort[sexuality]>" . make_select_list_key(array('Sexuality',"Heterosexual","Homosexual","Bisexual/Open-Minded"), $sexuality) . "</select></td></tr>";
	echo "<tr><td class=side>" . makeCheckBox('sort[single]', 'Single Users Only', !empty($single)) . "</td></tr>";
	echo "<tr><td class=side>" . makeCheckBox('sort[list]', 'Show List', !empty($list)) . "</td></tr>";
/*/
	echo "<tr><td class=side>";
	echo "Age <input name=sort[minage] value='$minage' size=1 style=\"width:39px\" maxlength=2> to <input name=sort[maxage] value='$maxage' size=1 style=\"width:39px\" maxlength=2><br>";
	echo "<select style=\"width:116px\" name=sort[sex]><option value=Both>Sex" . make_select_list(array("Male","Female"), $sex) . "</select><br>";
	echo "<select style=\"width:116px\" name=sort[loc]><option value=0>Location" . makeCatSelect($locations->makeBranch(), $loc) . "</select><br>"; //<script src=http://images.nexopia.com/include/dynconfig/locs.js></script>
	echo "<select style=\"width:116px\" name=sort[interest]><option value=0>Interests" . makeCatSelect($interests->makeBranch(), $interest) . "</select><br>"; //<script src=http://images.nexopia.com/include/dynconfig/interests.js></script>
	echo "<select style=\"width:116px\" name=sort[active]>" . make_select_list_key(array(0 => "All Users", 1 => "Active Recently", 2 => "Online"), $active) . "</select><br>";
	echo "<select style=\"width:116px\" name=sort[pic]>" . make_select_list_key(array(0 => "All Users", 1 => "With Pictures", 2 => "With a Verified Picture"), $pic) . "</select><br>";
	echo "<select style=\"width:116px\" name=sort[sexuality]>" . make_select_list_key(array('Sexuality',"Heterosexual","Homosexual","Bisexual/Open-Minded"), $sexuality) . "</select><br>";
	echo makeCheckBox('sort[single]', 'Single Users Only', !empty($single)) . "<br>";
	echo makeCheckBox('sort[list]', 'Show List', !empty($list));
	echo "</td></tr>";
//*/

/*
	if(!empty($loc)){
		$branch = $categories->makeBranch();
		$i=1;
		foreach($branch as $cat){
			if($cat['id']==$loc)
				break;
			$i++;
		}
		echo "<script> document.profilesort['sort[loc]'].selectedIndex = $i; </script>";
	}
*/

	echo "<tr><td class=side align=center><input type=submit name=sort[mode] value=\"Search\">";
	if($userData['loggedIn'] && $userData['premium'])
		echo " <a href=/profile.php?action=advanced>Advanced</a>";
	echo "</td></tr>";
	echo "</form>";
	echo "</table>";

	echo "<hr>";

	echo "<table align=center><form action='/profile.php' method=get>";
	echo "<tr><td class=side>Search by Username:<br><input type=text name=uid size=8 style=\"width:80px\" value='$user'><input type=submit value=Go style=\"width:35px\"></td></tr>";
	echo "</form></table>";

	closeBlock();
}

function incMsgBlock($side){
	global $userData, $messaging, $cache;

	if(!$userData['loggedIn'])
		return;

	openBlock('Messages',$side);

	if($userData['newmsgs']>0){

		$newmsgs = $cache->get(array($userData['userid'], "newmsglist-$userData[userid]"));

		if($newmsgs === false){
//			$messaging->db->prepare_query("SELECT msgs.id, msgheader.from, msgheader.fromname, msgheader.subject, msgheader.date, msgs.msgheaderid FROM msgs, msgheader WHERE msgs.msgheaderid=msgheader.id && msgs.userid = # && msgs.folder = # && msgheader.to = # && msgheader.new='y'", $userData['userid'], MSG_INBOX, $userData['userid']);
			$messaging->db->prepare_query("SELECT id, `from`, fromname, subject, date FROM msgs WHERE userid = # && folder = # && status='new'", $userData['userid'], MSG_INBOX);

			$newmsgs = array();
			while($line = $messaging->db->fetchrow())
				$newmsgs[] = $line;

			$cache->put(array($userData['userid'], "newmsglist-$userData[userid]"), $newmsgs, 3600);
		}

		if(count($newmsgs)){
			echo "<table width=100%>\n";
			echo "<tr><td class=side colspan=3><b>" . count($newmsgs) . " new <a href='messages.php'>message(s)</a></b></td></tr>\n";

			echo "<tr><td class=side>From</td><td class=side>Subject</td></tr>";
			foreach($newmsgs as $line){
				echo "<tr><td class=side>";
				if($line['from'])
					echo "<a href=\"profile.php?uid=$line[from]\">$line[fromname]</a>";
				else
					echo "$line[fromname]";
				echo "</td>";

				if(strlen($line['subject']) <= 20)
					$subject = $line['subject'];
				else
					$subject = substr($line['subject'],0,18) . "...";

				echo "<td class=side><a href=\"messages.php?action=view&id=$line[id]\">$subject</a></td>";
				echo "</tr>";
			}
			echo "</table>\n";
		}else
			echo "&nbsp;<b>0 new <a href='messages.php'>message(s)</a></b>";
	}else{
		echo "&nbsp;<b>0 new <a href='messages.php'>message(s)</a></b>";
	}


	closeBlock();
}


function incFriendsBlock($side){
	global $userData,$config,$db;

	if(!$userData['loggedIn'])
		return;

	openBlock('Friends',$side);

	if($userData['friendsonline']>0){
		if(!isset($userData['friends'])){
			$db->prepare_query("SELECT friendid,username FROM friends,users WHERE friends.userid = # && friendid=users.userid && online = 'y'", $userData['userid']);

			$online = $db->numrows();
			$userData['friends'] = array();
			while($line = $db->fetchrow())
				$userData['friends'][$line['friendid']] = $line['username'];
		}else
			$online = $userData['friendsonline'];

		uasort($userData['friends'],'strcasecmp');

		echo "&nbsp;<b>$online <a href=friends.php>friend(s)</a> online</b><br>";
		foreach($userData['friends'] as $userid => $username)
			echo "&nbsp;<a href=\"profile.php?uid=$userid\">$username</a><br>";
	}else{
		echo "&nbsp;<b>0 <a href=friends.php>friend(s)</a> online</b>";
	}


	closeBlock();
}

function incModBlock($side){
	global $userData, $mods, $cache;

	if(!$userData['loggedIn'])
		return;

//	print_r(isMod($userData['userid']));

	if(!$mods->isMod($userData['userid']))
		return;

	function getAdminsOnline(){
		global $db, $mods;

		$moduids = $mods->getAdmins('visible');

		$db->prepare_query("SELECT userid, username FROM users WHERE userid IN (#) && online = 'y'", $moduids);

		$rows = array();

		while($line = $db->fetchrow())
			$rows[$line['userid']] = $line['username'];

		uasort($rows, 'strcasecmp');

		return $rows;
	}

	function getNumModsOnline(){
		global $db, $mods;

		$moduids = $mods->getMods();

		$db->prepare_query("SELECT count(*) FROM users WHERE userid IN (#) && online = 'y'", $moduids);

		return $db->fetchfield();
	}

	function getNumForumModsOnline(){
		global $forums, $db;

		$forums->db->prepare_query("SELECT DISTINCT userid FROM forummods WHERE official='y'");

		$uids = array();
		while($line = $forums->db->fetchrow())
			$uids[] = $line['userid'];

		$db->prepare_query("SELECT count(*) FROM users WHERE userid IN (#) && online = 'y'", $uids);

		return $db->fetchfield();
	}

	$adminsonline = $cache->get('adminsonline',60,'getAdminsOnline', 0);
	$modsonline = $cache->get('modsonline',60,'getNumModsOnline', 0) - count($adminsonline);
//	$forummodsonline = $cache->get('fmodsonline',60,'getNumForumModsOnline');

	$moditemcounts = $mods->getModItemCounts();

	openBlock('Moderator',$side);

	$types = array();
	foreach($moditemcounts as $type => $num)
		if($num > 0)
			$types[$type] = $num;

	echo "<table width=100%><tr><td class=side>";
	if(count($types)==0){
		echo "No requests<br>";
	}else{
		foreach($types as $type => $num)
			echo str_repeat("&nbsp; ", 5-strlen($num)) . "$num <a href=moderate.php?mode=$type>" . $mods->modtypes[$type] . "</a><br>";
	}
	echo "<hr>";
	echo "<a href=modprefs.php>Preferences</a><br>";
	echo "<hr>";

	echo "<b>$modsonline Mod(s) online</b><br>";
//	echo "$forummodsonline Forum Mod(s) online<br>";
	echo "<b>" . count($adminsonline) . " Admin(s) online</b><br>";
	foreach($adminsonline as $uid => $username)
		echo "<a href=profile.php?uid=$uid>$username</a><br>";

	echo "</td></tr></table>";


	closeBlock();
}


function incTopGirls($side){
	global $config, $db, $userData, $cache;

	if(!$config['votingenabled'])
		return;

	$minage = ($userData['loggedIn'] ? $userData['defaultminage'] : 14);
	$maxage = ($userData['loggedIn'] ? $userData['defaultmaxage'] : 30);

	$rows = $cache->get("top5f:$minage-$maxage",1800,array('function' => 'getTopPics', 'params' => array('Female',$minage, $maxage)));

	openBlock('Top Girls',$side);

	foreach($rows as $id => $username)
		echo "&nbsp;<a href=profile.php?picid=$id>$username</a><br>";

	closeBlock();
}

function incTopGuys($side){
	global $config, $db, $userData, $cache;

	if(!$config['votingenabled'])
		return;

	$minage = ($userData['loggedIn'] ? $userData['defaultminage'] : 14);
	$maxage = ($userData['loggedIn'] ? $userData['defaultmaxage'] : 30);

	$rows = $cache->get("top5m:$minage-$maxage",1800,array('function' => 'getTopPics', 'params' => array('Male',$minage, $maxage)));

	openBlock('Top Guys',$side);

	foreach($rows as $id => $username)
		echo "&nbsp;<a href=profile.php?picid=$id>$username</a><br>";

	closeBlock();
}

function incLoginBlock($side){
	global $userData;

	if($userData['loggedIn'])
		return;

	openBlock('Login',$side);

	echo "<table align=center border=0 cellspacing=0>";
	echo "<form action='login.php' method='post' target=_top>";
	echo "<tr><td class=side>User:</td><td class=side><input type=text name=username style=\"width:90px\"></td></tr>";
	echo "<tr><td class=side>Pass:</td><td class=side><input type=password name=password style=\"width:90px\"></td></tr>";
	echo "<tr><td class=side colspan=2>" . makeCheckBox('lockip', " Secure Session", false) . " (<a href=faq.php?q=68> ? </a>)</td></tr>";
	echo "<tr><td class=side colspan=2>" . makeCheckBox('cachedlogin', " Remember Me",  false) . "</td></tr>";

	echo "<tr><td class=side colspan=2 align=center><input type=submit value=Login style=\"width=60px\"><input type=button onClick=\"location.href='create.php'\" value=Join style=\"width=60px\"></td></tr>";
	echo "</form>";
	echo "</table>";

	closeBlock();
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
	global $db,$cache;

	function getActiveForumThreads(){
		global $forums;
		$forums->db->query("SELECT forumthreads.id,forumthreads.title FROM forumthreads,forums WHERE forums.id=forumthreads.forumid && forums.official='y' && forumthreads.time > '" . (time() - 1800) . "' ORDER BY forumthreads.time DESC LIMIT 5");

		$rows = array();
		while($line = $forums->db->fetchrow())
			$rows[$line['id']] = $line['title'];
		return $rows;
	}

	$rows = $cache->get('activethread',30,'getActiveForumThreads');

	openBlock('Recent Posts',$side);

	echo "<table><tr><td class=side>";
	foreach($rows as $id => $title)
		echo "- <a href='forumviewthread.php?tid=$id'>" . wrap($title,20) . "</a><br>";
	echo "</td></tr></table>";

	closeBlock();
}

function incScheduleBlock($side){
	global $db;
	$query = "SELECT title,timeoccur FROM schedule WHERE timeoccur > '" . time() . "' && scope='global' && moded='y' ORDER BY timeoccur DESC LIMIT 5";
	$result = $db->query($query);

	openBlock('Events',$side);

	echo "<table>";

	while($line = $db->fetchrow($result))
		echo "<tr><td class=side><a href='schedule.php?action=showday&month=" . gmdate('n',$line['timeoccur']) . "&year=" . gmdate('Y',$line['timeoccur']) . "&day=" . gmdate('j',$line['timeoccur']) . "&calsort[scope]=global'>$line[title]</a></td></tr>";

	echo "</table>";
	closeBlock();
}

function incPrevVoteBlock($side){
	global $config, $db, $userData, $cache;

	if(!$config['votingenabled'])
		return;

	if(!$userData['loggedIn'])
		return;

	$prev = $cache->get(array($userData['userid'], "lastpicvote-$userData[userid]"));

	if($prev === false){
		$db->prepare_query("SELECT picid, votehist.vote, score, votes FROM votehist, pics WHERE votehist.userid = # && pics.id=picid ORDER BY time DESC LIMIT 1", $userData['userid']);
		$prev = $db->fetchrow();

		if(!$prev)
			$prev = 0;

		$cache->put(array($userData['userid'], "lastpicvote-$userData[userid]"), $prev, 10800);
	}

	if($prev){
		if($prev['votes'])
			$score = scoreCurve((double)$prev['score']);
		else
			$score=0;

		openBlock('Previous Vote',$side);

		echo "<table align=center><tr><td class=side>";
		echo "<a href=profile.php?picid=$prev[picid]><img src=\"http://" . chooseImageServer($prev['picid']) . $config['thumbdir'] . floor($prev['picid']/1000) . "/$prev[picid].jpg\" border=0></a><br>";
		echo "Score: <b>$score</b><br>";
		echo "Votes: $prev[votes]<br>";
		echo "Your Vote: $prev[vote]";
		echo "</td></tr></table>";

		closeBlock();
	}
}

function incPrivScheduleBlock($side){
	global $userData,$db;

	if(!$userData['loggedIn'])
		return;

	$query = "SELECT title,timeoccur FROM schedule WHERE timeoccur > " . time() . " && (scope='private' || scope='public') && authorid='$userData[userid]' ORDER BY timeoccur DESC";
	$result = $db->query($query);

	openBlock('Schedule',$side);

	echo "<table>";

	if($db->numrows($result)==0)
		echo "<tr><td class=side>No private items</td></tr>";
	else
		while($line = $db->fetchrow($result))
			echo "<tr><td class=side><a href='schedule.php?action=showday&calsort[scope]=private&month=" . date('n',$line['timeoccur']) . "&year=" . date('Y',$line['timeoccur']) . "&day=" . date('j',$line['timeoccur']) . "&calsort[scope]=global'>$line[title]</a></td></tr>";

	echo "</table>";
	closeBlock();
}

function incSubscribedThreadsBlock($side){
	global $userData, $forums;

	if(!$userData['loggedIn'] || $userData['posts'] == 0)
		return;

	openBlock('Subscriptions',$side);

	$forums->db->prepare_query("SELECT forumthreads.id,forumthreads.title FROM forumread,forumthreads WHERE forumread.subscribe='y' && forumread.userid = # && forumread.threadid=forumthreads.id && forumread.time < forumthreads.time", $userData['userid']);

	if($forums->db->numrows()==0)
		echo "&nbsp;No updates";

	while($line = $forums->db->fetchrow())
		echo "&nbsp;- <a href='forumviewthread.php?tid=$line[id]'>" . wrap($line['title'],20) . "</a><br>";

	closeBlock();
}

function incNewestMembersBlock($side){
	global $userData,$db, $cache;

	function getNewestMembers($sex, $minage, $maxage){
		global $db;

		$db->prepare_query("SELECT userid, username FROM newestusers WHERE sex IN (?) && age IN (#) ORDER BY id DESC LIMIT 5",$sex, range($minage, $maxage));

		$rows = array();
		while($line = $db->fetchrow())
			$rows[$line['userid']] = $line['username'];
		return $rows;
	}

	if($userData['loggedIn']){
		$sexes = $userData['defaultsex'];
		$sex = ($sexes == 'Male' ? 'm' : 'f');
		$minage = $userData['defaultminage'];
		$maxage = $userData['defaultmaxage'];
	}else{
		$sexes = array("Male","Female");
		$sex = 'b';
		$minage = 14;
		$maxage = 30;
	}

	$rows = $cache->get("new5$sex:$minage-$maxage",180,array('function' => 'getNewestMembers', 'params' => array($sexes, $minage, $maxage)));

	openBlock('New Members',$side);

	foreach($rows as $userid => $username)
		echo "&nbsp;<a href='profile.php?uid=$userid'>$username</a><br>";

	closeBlock();
}

function incRecentUpdateProfileBlock($side){
	global $userData,$db, $cache;

	function getRecentUpdateProfile($sex, $minage, $maxage){
		global $db;

		$db->prepare_query("SELECT userid, username FROM newestprofile WHERE sex IN (?) && age IN (#) ORDER BY id DESC LIMIT 5",$sex, range($minage, $maxage));

		$rows = array();
		while($line = $db->fetchrow())
			$rows[$line['userid']] = $line['username'];
		return $rows;
	}

	if($userData['loggedIn']){
		$sexes = $userData['defaultsex'];
		$sex = ($sexes == 'Male' ? 'm' : 'f');
		$minage = $userData['defaultminage'];
		$maxage = $userData['defaultmaxage'];
	}else{
		$sexes = array("Male","Female");
		$sex = 'b';
		$minage = 14;
		$maxage = 30;
	}
	$rows = $cache->get("updt5$sex:$minage-$maxage",180,array('function' => 'getRecentUpdateProfile', 'params' => array($sexes, $minage, $maxage)));

	openBlock('Updated Profiles',$side);

	foreach($rows as $userid => $username)
		echo "&nbsp;<a href='profile.php?uid=$userid'>$username</a><br>";

	closeBlock();
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

//	if($userData['limitads'])
		return;

	$bannertext = $banner->getbanner(BANNER_SKY120);

	if($bannertext == "")
		return;

	openBlock("Sponsor",$side);

	echo "<br><center>$bannertext</center><br>";

	closeBlock();
}

function incModsOnlineBlock($side){
	return;

	global $userData,$config,$db, $mods, $cache,$mods;

	if(!$userData['loggedIn'])
		return;

	if(!$mods->isMod($userData['userid']))
		return;


	function getModsOnline(){
		global $db, $mods;

		$moduids = $mods->getMods();

		$db->prepare_query("SELECT userid, username FROM users WHERE userid IN (#) && online='y'", $moduids);

//		$db->query("SELECT mods.userid,username FROM mods,users WHERE mods.userid=users.userid && mods.type='pics' && online = 'y'");

		$rows = array();
		while($line = $db->fetchrow())
			$rows[$line['userid']] = $line['username'];

		uasort($rows,'strcasecmp');

		return $rows;
	}

	$rows = $cache->get('modsonline',60,'getModsOnline');

	openBlock('Mods Online',$side);

	$online = count($rows);

	echo "&nbsp;<b>$online Mods online</b><br>";
	foreach($rows as $userid => $username)
		echo "&nbsp;<a href=profile.php?uid=$userid>$username</a><br>";


	closeBlock();
}

function incShoppingCartMenu($side){
	global $mods, $userData;

	openBlock("Shopping Cart",$side);

	echo "&nbsp;<a href=cart.php>Shopping Cart</a><br>";
	echo "&nbsp;<a href=checkout.php>Checkout</a><br>";
	echo "&nbsp;<a href=invoicelist.php>Invoice List</a><br>";
	echo "&nbsp;<a href=product.php?id=1>Nexopia Plus</a><br>";
	echo "&nbsp;<a href=paymentinfo.php>Payment Info</a>";

	if($userData['loggedIn'] && $mods->isAdmin($userData['userid'],'viewinvoice')){
		echo "<hr>";
		echo "&nbsp;<a href=invoicereport.php>Reports</a><br>";
		echo "<center>";

		echo "<form action=invoice.php>";
		echo "Invoice ID:<br><input type=text size=10 name=id>";
		echo "<input type=submit value=Go>";
		echo "</form>";

		echo "<form action=profile.php>";
		echo "Show Username:<br><input type=text size=10 name=uid>";
		echo "<input type=submit value=Go>";
		echo "</form>";

		echo "</center>";
	}

	closeBlock();
}

function msgFoldersBlock($side){
	global $db, $userData;

	openBlock("Message Folders",$side);

	echo "&nbsp;<a href=messages.php?action=folders>Manage Folders</a><br>";

	$folders = getMsgFolders();

	foreach($folders as $id => $name)
		echo "&nbsp;- <a href=messages.php?folder=$id>$name</a><br>";

	closeBlock();
}

function incPlusBlock($side){
	global $userData;

	if($userData['limitads'])
		return;

	openBlock("Nexopia Plus",$side);

//- <b>Faster Server</b><br>
?>
<table><tr><td class=side>
- <b>No Ads</b><br>
- <b>Recent Visitors List</b><br>
- <b>Large Gallery</b><br>
- Eligible for the Spotlight<br>
- Hide Profile<br>
- View Profiles Anonymously<br>
- Extra pictures<br>
- Profile Skins<br>
- Longer Profiles<br>
- Advanced User Search<br>
- Friends list notifications<br>
- Get off friends lists<br>
- Longer friends list<br>
- File hosting<br>
- Priority picture approval<br>
- Sent Message Status<br>
- Create a custom forum<br>
- Custom forum rank<br>
- Reset picture votes<br>
<center><a href=product.php?id=1><b>Click For Details</b></a></center>
</td></tr></table>
<?

	closeBlock();
}

function incGoogleBlock($side){
	global $userData;

	if($userData['loggedIn'])
		return;

	openBlock("Sponsors",$side);

?><br><center>
<script type="text/javascript"><!--
google_ad_client = "pub-3720840712640771";
google_ad_width = 120;
google_ad_height = 240;
google_ad_format = "120x240_as";
google_ad_channel ="";
//--></script>
<script type="text/javascript"
  src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script></center><br>
<?

	closeBlock();
}

function incSpotlightBlock($side){
	global $cache, $config;

	function getSpotlight(){//$sex, $minage, $maxage){
		global $db, $cache;

		$spotlightmax = $cache->get("spotlightmax");

		if(!$spotlightmax){
			$db->query("SELECT count(*) FROM spotlight");

			$spotlightmax = $db->fetchfield();

			$cache->put("spotlightmax", $spotlightmax, 86400);
		}

		randomize();
		do{
//			$db->prepare_query("SELECT users.userid, users.username, users.firstpic as pic, users.age, users.sex FROM spotlight, users WHERE spotlight.userid = users.userid && users.firstpic > 0 && spotlight.id = #", rand(1,$spotlightmax));

			$db->prepare_query("SELECT users.userid, users.username, pics.id as pic, users.age, users.sex FROM spotlight, users, pics WHERE spotlight.userid = users.userid && pics.itemid = users.userid && users.firstpic > 0 && spotlight.id = # ORDER BY rand() LIMIT 1", rand(1,$spotlightmax));
		}while(!$db->numrows());

		return $db->fetchrow();
	}

	$user = $cache->get("spotlight",300,'getSpotlight');

	if(!$user)
		return;

	openBlock("Plus Spotlight",$side);


	echo "<table width=100%><tr><td class=side align=center>";
	echo "<a href=profile.php?uid=$user[userid]>$user[username]</a><br>Age $user[age], $user[sex]<br>";
	if($user['pic'])
		echo "<a href=profile.php?uid=$user[userid]><img src=\"http://" . chooseImageServer($user['pic']) . $config['thumbdir'] . floor($user['pic']/1000) . "/$user[pic].jpg\" border=0></a><br><br>";
	echo "</td></tr></table>";

	closeBlock();
}

