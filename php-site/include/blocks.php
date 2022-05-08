<?

function incAdminBlock($side){
}

function incPollBlock($side){
	global $PHP_SELF,$userData,$config,$db,$cache;

	if(!$userData['loggedIn'])
		return;

	$poll = $cache->get('poll',30,'getPoll',false);

	if(!$poll)
		return;

	$query = "SELECT id FROM pollvotes WHERE userid='$userData[userid]' && pollid='$poll[id]'";
	$result = $db->query($query);

	openBlock('Polls',$side);

	if($db->numrows($result)==0){
		echo "<table border=0 cellspacing=0 cellpadding=2 width=100%><form action=poll.php method=get>";
		echo "<input type=hidden name=pollid value=$poll[id]>";
		echo "<tr><td colspan=2 class=header>$poll[question]</td></tr>";
		foreach($poll['answers'] as $ans)
			echo "<tr><td class=side width=20><input class=body type=radio name='ans' value='$ans[id]' id='ans$ans[id]'></td><td class=side><label for='ans$ans[id]'>$ans[answer]</label></td></tr>";
		echo "<tr><td class=side></td><td class=side><input class=body type=submit name=action value='Vote'> <a class=side href=poll.php?pollid=$poll[id]&ans=0&action=Vote>Results</a></td></tr>";
		echo "<tr><td colspan=2 class=side align=center><a class=side href=poll.php?action=list>List of polls</a> | <a class=side href=poll.php?action=add>Suggest a Poll</a></td></tr>";
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
		echo "<tr><td class=side>Total: $poll[tvotes] votes</td></tr>";
		echo "<tr><td class=side><a class=side href=poll.php?action=list>List of polls</a> | <a class=side href=poll.php?action=add>Suggest a Poll</a></td></tr>";
		echo "</table>";
	}
	closeBlock();
}

function incBookmarksBlock($side){
	global $PHP_SELF,$userData,$config,$db;

	if(!$userData['loggedIn'])
		return;

	$query = "SELECT id,name,url FROM bookmarks WHERE userid = '$userData[userid]' ORDER BY name";
    $result = $db->query($query);

	openBlock('Bookmarks',$side);

	echo "<table width=100%>\n";
	echo "<tr><td class=header><b><a href=\"bookmarks.php\">Bookmarks</a></b></td></tr>";
	while($line = $db->fetchrow($result))
		echo "<tr><td class=side><a class=side href=\"$line[url]\" target=_blank>$line[name]</a></td></tr>\n";
	echo "</table>\n";

	closeBlock();
}

function incSortBlock($side){
	global $userData,$sort,$config;

	$user = '';
	$loc='0';

	if($userData['loggedIn']){
		$sex = $userData['defaultsex'];
		$minage = $userData['defaultminage'];
		$maxage = $userData['defaultmaxage'];
	}else{
		$sex = 'Both';
		$minage = 14;
		$maxage = 30;
	}

	if(!isset($sort) || !is_array($sort))
		$sort = array();

	extract($sort);

	$locations = & new category("locs");

	openBlock('Users',$side);

	echo "<table align=center><tr><td class=side align=right>";
	if($config['votingenabled']){
		echo "<b>Top:</b> <a class=side href='profile.php?sort[mode]=top&sort[sex]=Female'>Girls</a> | <a class=side href='profile.php?sort[mode]=top&sort[sex]=Male'>Guys</a>&nbsp;&nbsp;<br>";
		echo "<b>Rate:</b> <a class=side href='profile.php?sort[mode]=rate&sort[sex]=Female'>Girls</a> | <a class=side href='profile.php?sort[mode]=rate&sort[sex]=Male'>Guys</a>&nbsp;&nbsp;<br>";
	}

	echo "<b>New:</b> <a class=side href='profile.php?sort[mode]=newest&sort[sex]=Female'>Girls</a> | <a class=side href='profile.php?sort[mode]=newest&sort[sex]=Male'>Guys</a>&nbsp;&nbsp;<br>";
	echo "<b>Online:</b> <a class=side href='profile.php?sort[online]=y&sort[sex]=Female&sort[list]=y'>Girls</a> | <a class=side href='profile.php?sort[online]=y&sort[sex]=Male&sort[list]=y'>Guys</a>&nbsp;&nbsp;<br>";

	echo "</td></tr></table>";

	echo "<hr>";

	echo "<table align=center cellpadding=0 cellspacing=1>";

	echo "<form action=profile.php name=profilesort>";

	echo "<tr><td class=side>&nbsp;Age <input class=side name=sort[minage] value='$minage' size=1 style=\"width:35px\"> to <input class=side name=sort[maxage] value='$maxage' size=1 style=\"width:35px\"></td></tr>";
	echo "<tr><td class=side>&nbsp;<select class=side name=sort[sex] style=\"width:110px\"><option value=Both>Sex" . make_select_list(array("Male","Female"),$sex) . "</select></td></tr>";
	echo "<tr><td class=side>&nbsp;<select class=side name=sort[loc] style=\"width:110px\"><option value=0>Location" . makeCatSelect($locations->makeBranch(),$loc) . "</select></td></tr>"; //<script src=http://images.nexopia.com/include/dynconfig/locs.js></script>

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

	echo "<tr><td class=side colspan=2>" . makeCheckBox('sort[online]', 'Online Users Only', 'body', !empty($online)) . "</td></tr>";
	echo "<tr><td class=side colspan=2>" . makeCheckBox('sort[nopics]', 'Incl. Users w/o Pics', 'body', !empty($nopics)) . "</td></tr>";
	echo "<tr><td class=side colspan=2>" . makeCheckBox('sort[list]', 'Show List', 'body', !empty($list)) . "</td></tr>";

	echo "<tr><td class=side align=center><input class=side type=submit name=sort[mode] value=\"Search\">";
	if($userData['loggedIn'] && $userData['premium'])
		echo " <a class=side href=/profile.php?action=advanced>Advanced</a>";
	echo "</td></tr>";
	echo "</form>";
	echo "</table>";

	echo "<hr>";

	echo "<table cellspacing=3 align=center><form action='/profile.php' method=get>";
	echo "<tr><td class=side>Search by Username:<br><input class=side type=text name=sort[user] size=8 style=\"width:90px\" value='$user'><input class=side type=submit name=sort[mode] value=Go></td></tr>";
	echo "</form></table>";



	closeBlock();
}

function incMsgBlock($side){
	global $userData,$db;

	if(!$userData['loggedIn'])
		return;

	openBlock('Messages',$side);

	if($userData['newmsgs']>0){

		$db->prepare_query("SELECT msgs.id, msgheader.from, msgheader.fromname, msgheader.subject, msgheader.date, msgs.msgheaderid FROM msgs, msgheader WHERE msgs.msgheaderid=msgheader.id && msgs.userid = ? && msgs.folder = ? && msgs.userid = msgheader.to && msgheader.new='y'", $userData['userid'], MSG_INBOX);

		$newmsgs = $db->numrows();

		if($newmsgs){

			$newmsgs = array();
			while($line = $db->fetchrow())
				$newmsgs[$line['msgheaderid']] = $line;

			echo "<table width=100%>\n";
			echo "<tr><td class=side colspan=3><b>" . count($newmsgs) . " new <a class=side href='messages.php'>message(s)</a></b></td></tr>\n";

			echo "<tr><td class=side>From</td><td class=side>Subject</td></tr>";
			foreach($newmsgs as $line){
				echo "<tr><td class=side>";
				if($line['from'])
					echo "<a class=side href=\"profile.php?uid=$line[from]\">$line[fromname]</a>";
				else
					echo "$line[fromname]";
				echo "</td>";

				if(strlen($line['subject']) <= 20)
					$subject = $line['subject'];
				else
					$subject = substr($line['subject'],0,18) . "...";

				echo "<td class=side><a class=side href=\"messages.php?action=view&id=$line[id]\">$subject</a></td>";
				echo "</tr>";
			}
			echo "</table>\n";
		}else
			echo "&nbsp;<b>0 new <a class=side href='messages.php'>message(s)</a></b>";
	}else{
		echo "&nbsp;<b>0 new <a class=side href='messages.php'>message(s)</a></b>";
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
			$query = $db->prepare("SELECT friendid,username FROM friends,users WHERE friends.userid = ? && friendid=users.userid && online = 'y'", $userData['userid']);
			$result = $db->query($query);

			$online = $db->numrows($result);
			$userData['friends'] = array();
			while($line = $db->fetchrow($result))
				$userData['friends'][$line['friendid']] = $line['username'];
		}else
			$online = $userData['friendsonline'];

		uasort($userData['friends'],'strcasecmp');

		echo "&nbsp;<b>$online <a class=side href=friends.php>friend(s)</a> online</b><br>";
		foreach($userData['friends'] as $userid => $username)
			echo "&nbsp;<a class=side href=\"profile.php?uid=$userid\">$username</a><br>";
	}else{
		echo "&nbsp;<b>0 <a class=side href=friends.php>friend(s)</a> online</b>";
	}


	closeBlock();
}

function incModBlock($side){
	global $PHP_SELF,$userData, $mods;

	if(!$userData['loggedIn'])
		return;

//	print_r(isMod($userData['userid']));

	if(!$mods->isMod($userData['userid']))
		return;

	$moditemcounts = $mods->getModItemCounts();

	openBlock('Moderator',$side);

	$types = array();
	foreach($moditemcounts as $type => $num)
		if($num > 0)
			$types[$type] = $num;

	if(count($types)==0){
		echo "&nbsp;No requests<br>";
	}else{
		echo "<table>";
		foreach($types as $type => $num)
			echo "<tr><td class=side><a class=side href=moderate.php?mode=$type>" . $mods->modtypes[$type] . "</a>:</td><td class=side>$num</td></tr>";
		echo "</table>";
	}
	echo "&nbsp;<a class=side href=modprefs.php>Preferences</a>";

	closeBlock();
}


function incTopGirls($side){
	global $config,$db, $userData,$cache;

	if(!$config['votingenabled'])
		return;

	$minage = ($userData['loggedIn'] ? $userData['defaultminage'] : 14);
	$maxage = ($userData['loggedIn'] ? $userData['defaultmaxage'] : 30);

	$rows = $cache->get("top5f:$minage-$maxage",600,array('function' => 'getTopPics', 'params' => array('Female',$minage, $maxage)));

	openBlock('Top Girls',$side);

	foreach($rows as $id => $username)
		echo "&nbsp;<a class=side href=profile.php?picid=$id>$username</a><br>";

	closeBlock();
}

function incTopGuys($side){
	global $config,$db, $userData,$cache;

	if(!$config['votingenabled'])
		return;

	$minage = ($userData['loggedIn'] ? $userData['defaultminage'] : 14);
	$maxage = ($userData['loggedIn'] ? $userData['defaultmaxage'] : 30);

	$rows = $cache->get("top5m:$minage-$maxage",600,array('function' => 'getTopPics', 'params' => array('Male',$minage, $maxage)));

	openBlock('Top Guys',$side);

	foreach($rows as $id => $username)
		echo "&nbsp;<a class=side href=profile.php?picid=$id>$username</a><br>";

	closeBlock();
}

function incLoginBlock($side){
	global $userData;

	if($userData['loggedIn'])
		return;

	openBlock('Login',$side);

	echo "<table align=center border=0 cellspacing=0>";
	echo "<form action='login.php' method='post'>";
	echo "<tr><td class=side>User:</td><td class=side><input class=body type=text name=username style=\"width:100px\"></td></tr>";
	echo "<tr><td class=side>Pass:</td><td class=side><input class=body type=password name=password style=\"width:100px\"></td></tr>";
	echo "<tr><td class=side colspan=2><input type=checkbox name=cachedlogin value=y> <label for='cachedlogin'>Remember Me</label></td></tr>";
	echo "<tr><td class=side colspan=2 align=center><input class=side type=submit value=Login style=\"width=60px\"><input type=button class=side onClick=\"location.href='create.php'\" value=Join style=\"width=60px\"></td></tr>";
	echo "</form>";
	echo "</table>";

	closeBlock();
}

function incSkinBlock($side){
	global $skins,$skin,$PHP_SELF;

	openBlock('Skin',$side);
	echo "<table><form action=$PHP_SELF method=post>";
	echo "<tr><td class=side><select class=body name=newskin>" . make_select_list_key($skins,$skin) ."</select><input class=body type=submit name=chooseskin value=Go></td></tr>";
	echo "</form></table>";
	closeBlock();
}

function incActiveForumBlock($side){
	global $db,$cache;

	function getActiveForumThreads(){
		global $db;
		$db->query("SELECT forumthreads.id,forumthreads.title FROM forumthreads,forums WHERE forums.id=forumthreads.forumid && forums.official='y' && forumthreads.time > '" . (time() - 1800) . "' ORDER BY forumthreads.time DESC LIMIT 5");

		$rows = array();
		while($line = $db->fetchrow())
			$rows[$line['id']] = $line['title'];
		return $rows;
	}

	$rows = $cache->get('activethread',30,'getActiveForumThreads');

	openBlock('Recent Posts',$side);

	echo "<table>";
	echo "<tr><td class=side>";
	foreach($rows as $id => $title)
		echo "- <a class=side href='forumviewthread.php?tid=$id'>" . wrap($title,20) . "</a><br>";
	echo "</td></tr>";
	echo "</table>";

	closeBlock();
}

function incScheduleBlock($side){
	global $db;
	$query = "SELECT title,timeoccur FROM schedule WHERE timeoccur > '" . time() . "' && scope='global' && moded='y' ORDER BY timeoccur DESC LIMIT 5";
	$result = $db->query($query);

	openBlock('Events',$side);

	echo "<table>";

	while($line = $db->fetchrow($result))
		echo "<tr><td class=side><a class=side href='schedule.php?action=showday&month=" . gmdate('n',$line['timeoccur']) . "&year=" . gmdate('Y',$line['timeoccur']) . "&day=" . gmdate('j',$line['timeoccur']) . "&calsort[scope]=global'>$line[title]</a></td></tr>";

	echo "</table>";
	closeBlock();
}

function incPrevVoteBlock($side){
	global $config,$db,$userData;

	if(!$config['votingenabled'])
		return;

	if(!$userData['loggedIn'])
		return;

	$query = "SELECT picid,pics.id,votehist.vote,score,votes FROM votehist, pics WHERE votehist.userid='$userData[userid]' && pics.id=picid ORDER BY time DESC LIMIT 1";
	$result= $db->query($query);


	if($db->numrows($result)==1){
		$prev = $db->fetchrow($result);

		$score=0;
		if($prev['votes']!=0)
			$score = scoreCurve((double)$prev['score']);

		openBlock('Previous Vote',$side);

		echo "<table align=center>";
		echo "<tr><td class=side>";
		echo "<a class=side href=profile.php?picid=$prev[picid]><img src=\"$config[thumbloc]" . floor($prev['id']/1000) . "/$prev[id].jpg\" border=0></a><br>";
		echo "Score: <b>$score</b><br>";
		echo "Votes: $prev[votes]<br>";
		echo "Your Vote: $prev[vote]";
		echo "</td></tr>";
		echo "</table>";

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
			echo "<tr><td class=side><a class=side href='schedule.php?action=showday&calsort[scope]=private&month=" . date('n',$line['timeoccur']) . "&year=" . date('Y',$line['timeoccur']) . "&day=" . date('j',$line['timeoccur']) . "&calsort[scope]=global'>$line[title]</a></td></tr>";

	echo "</table>";
	closeBlock();
}

function incSubscribedThreadsBlock($side){
	global $userData,$db;

	if(!$userData['loggedIn'] || $userData['posts'] == 0)
		return;

	$query = "SELECT forumthreads.id,forumthreads.title FROM forumread,forumthreads WHERE forumread.subscribe='y' && forumread.userid='$userData[userid]' && forumread.threadid=forumthreads.id && forumread.time < forumthreads.time";// ORDER BY forumthreads.time DESC";
	$result = $db->query($query);

	openBlock('Subscriptions',$side);

	if($db->numrows($result)==0)
		echo "&nbsp;No updates";

	while($line = $db->fetchrow($result))
		echo "&nbsp;- <a class=side href='forumviewthread.php?tid=$line[id]'>" . wrap($line['title'],20) . "</a><br>";

	closeBlock();
}

function incNewestMembersBlock($side){
	global $userData,$db, $siteStats,$cache;

	function getNewestMembers($sex, $minage, $maxage){
		global $db;

//		$db->prepare_query("SELECT userid,username FROM users WHERE activated='y' && userid >= ? && sex IN (?) && age IN (?) ORDER BY userid DESC LIMIT 5", $siteStats['maxuserid'] - 1000, $sex, range($minage,$maxage));

		$db->prepare_query("SELECT userid, username FROM newestusers WHERE sex IN (?) && age IN (?) ORDER BY id DESC LIMIT 5",$sex, range($minage, $maxage));

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
		echo "&nbsp;<a class=side href='profile.php?uid=$userid'>$username</a><br>";

	closeBlock();
}

function incRecentUpdateProfileBlock($side){
	global $userData,$db, $siteStats,$cache;

	function getRecentUpdateProfile($sex, $minage, $maxage){
		global $db;

		//$db->prepare_query("SELECT userid,username FROM users WHERE sex IN (?) && age IN (?) ORDER BY profileupdatetime DESC LIMIT 5", $sex, range($minage,$maxage));

		$db->prepare_query("SELECT userid, username FROM newestprofile WHERE sex IN (?) && age IN (?) ORDER BY id DESC LIMIT 5",$sex, range($minage, $maxage));

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
		echo "&nbsp;<a class=side href='profile.php?uid=$userid'>$username</a><br>";

	closeBlock();
}

function incTextAdBlock($side){

	$banner = banner('text','sidelink');

	if($banner == "")
		return;

	openBlock("Great Links",$side);

	echo "<table><tr><td class=side>";
	echo $banner;
	echo "</td></tr></table>";

	closeBlock();
}


function incSideAdBlock($side){


	$banner = banner('120x60');

	if($banner == "")
		return;

	openBlock("Sponsor",$side);

	echo "<br><center>$banner</center><br>";

	closeBlock();
}


function incModsOnlineBlock($side){
	global $userData,$config,$db, $mods, $cache,$mods;

	if(!$userData['loggedIn'])
		return;

	if(!$mods->isMod($userData['userid']))
		return;


	function getModsOnline(){
		global $db, $mods;

		$moduids = $mods->getMods();

		$db->prepare_query("SELECT userid, username FROM users WHERE userid IN (?) && online='y'", $moduids);

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
		echo "&nbsp;<a class=side href=profile.php?uid=$userid>$username</a><br>";


	closeBlock();
}

function incShoppingCartMenu($side){

	openBlock("Shopping Cart",$side);

	echo "&nbsp;<a class=side href=cart.php>Shopping Cart</a><br>";
	echo "&nbsp;<a class=side href=checkout.php>Checkout</a><br>";
	echo "&nbsp;<a class=side href=invoicelist.php>Invoice List</a><br>";
	echo "&nbsp;<a class=side href=product.php?id=1>Nexopia Plus</a>";

	closeBlock();
}

function msgFoldersBlock($side){
	global $db, $userData;

	openBlock("Message Folders",$side);

	echo "&nbsp;<a class=side href=messages.php?action=folders>Manage Folders</a><br>";

	$folders = getMsgFolders();

	foreach($folders as $id => $name)
		echo "&nbsp;- <a class=side href=messages.php?folder=$id>$name</a><br>";

	closeBlock();
}

function incPlusBlock($side){
	global $userData;

//	if($userData['premium'])
//		return;

	openBlock("Nexopia Plus",$side);

?>
<table><tr><td class=side>
- <b>Faster Server</b><br>
- <b>Recent Visitors List</b><br>
- <b>Large Gallery</b><br>
- View Profiles Anonymously<br>
- Extra pictures<br>
- Profile Skins<br>
- Advanced User Search<br>
- Friends list notifications<br>
- Get off other's friends list<br>
- File Uploads and Hosting<br>
- Priority picture approval<br>
- See Sent Message status<br>
- Create a forum<br>
- Custom forum rank<br>
- Reset picture votes<br>
<center><a class=side href=product.php?id=1><b>Click For Details</b></a></center>
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
