<?

	$login=0;

	require_once("include/general.lib.php");

	addRefreshHeaders();


	if(!isset($fid) || $fid=="" || $fid==0 || !is_numeric($fid))
		die("Bad Forum id");

	$cache->prime("fo$fid");

	$perms = getForumPerms($fid,array('name','official','autolock','public','ownerid','sorttime'));	//checks it's a forum, not a realm, users permissions here, and column info

	if(!$perms['view'])
		die("You don't have permission to view this forum");

	$isMod = $perms['move'] || $perms['deletethreads'] || $perms['lock'] || $perms['stick'] || $perms['announce'];
	$showModBar = $perms['move'] || $perms['deletethreads'] || $perms['lock'] || $perms['stick'] || $perms['announce'] || $perms['mute'] || $perms['modlog'] || $perms['invite'] || $perms['editmods'];

	$forumdata = $perms['cols'];

	if($userData['loggedIn']){
		switch($action){
			case "markallread":
				$db->prepare_query("UPDATE forumupdated SET readalltime = ? WHERE userid = ? && forumid = ?", time(), $userData['userid'], $fid);
				if($db->affectedrows()==0){
					$db->prepare_query("INSERT IGNORE INTO forumupdated SET userid = ?, forumid = ?, time = ?, readalltime = ?", $userData['userid'], $fid, time(), time());
				}
				break;
			case "sortbythread":
				$db->prepare_query("UPDATE users SET forumsort='thread' WHERE userid = ?", $userData['userid']);

				break;
			case "sortbypost":
				$db->prepare_query("UPDATE users SET forumsort='post' WHERE userid = ?", $userData['userid']);

				break;
			case "delete":
				if($perms['deletethreads'] && isset($checkID) && is_array($checkID))
					foreach($checkID as $id)
						deleteThread($id);
				break;
			case "lock":
				if($perms['lock'] && isset($checkID) && is_array($checkID))
					foreach($checkID as $id)
						lockThread($id);
				break;
			case "unlock":
				if($perms['lock'] && isset($checkID) && is_array($checkID))
					foreach($checkID as $id)
						unlockThread($id);
				break;
			case "stick":
				if($perms['stick'] && isset($checkID) && is_array($checkID))
					foreach($checkID as $id)
						stickThread($id);
				break;
			case "unstick":
				if($perms['stick'] && isset($checkID) && is_array($checkID))
					foreach($checkID as $id)
						unstickThread($id);
				break;
			case "announce":
				if($perms['announce'] && isset($checkID) && is_array($checkID))
					foreach($checkID as $id)
						announceThread($id);
				break;
			case "unannounce":
				if($perms['announce'] && isset($checkID) && is_array($checkID))
					foreach($checkID as $id)
						unannounceThread($id);
				break;
			case "move":
				if($perms['move'] && isset($checkID) && is_array($checkID)){
					if(!isset($moveto) || $moveto<=0){
						$msgs->addMsg("You must specify a destination");
					}else{
						foreach($checkID as $id)
							moveThread($id,$moveto);
					}
				}
				break;
			case "withdraw":
				if($perms['invited'] && $userData['userid'] != $forumdata['ownerid']){
					$db->prepare_query("DELETE FROM foruminvite WHERE userid = ? && forumid = ?", $userData['userid'], $fid);
					$perms['invited']=false;
				}
				break;
			case "subscribe":
				if(!$perms['invited'] && $forumdata['official']=='n'){
					$db->prepare_query("INSERT IGNORE INTO foruminvite SET userid = ?, forumid = ?", $userData['userid'], $fid);
					$perms['invited']=true;
				}
				break;
		}
	}

	if($userData['loggedIn']){
		$db->prepare_query("SELECT readalltime FROM forumupdated WHERE userid = ? && forumid = ?", $userData['userid'], $fid);
		if($db->numrows()==0)
			$readalltime = 0;
		else
			$readalltime = $db->fetchfield();
	}else
		$readalltime = 0;

	if(!isset($page) || $page<0) $page=0;

	if(!isset($age) || $age < 0)
		$age = $forumdata['sorttime'];

	if($age==0)
		$startdate = 0;
	else
		$startdate = time() - ($age * 86400);


	if($userData['loggedIn']){
		$db->prepare_query("SELECT forumsort, forumpostsperpage FROM users WHERE userid = ?", $userData['userid']);
		$line = $db->fetchrow();
		extract($line);
	}else{
		$forumsort='post';
		$forumpostsperpage = 25;
	}

	$threaddata = array();
	$threadids = array();

	$select = "id,title,author,authorid,reads,posts,time,lastauthor,lastauthorid,locked,sticky,moved,announcement,pollid,'0' as new, '0' as subscribe";

	if($page == 0){
		$db->prepare_query("SELECT $select FROM  forumthreads USE INDEX (announcement) WHERE announcement='y' && forumid = ? ORDER BY time DESC", $fid);

		while($line = $db->fetchrow()){
			$threaddata[$line['id']] = $line;
			$threadids[$line['id']] = $line['id'];
		}
	}


	$db->prepare_query("SELECT SQL_CALC_FOUND_ROWS $select FROM forumthreads USE INDEX (forumid) WHERE forumid = ? && time >= ? && sticky IN ('y','n') && announcement='n' ORDER BY sticky DESC," . ($forumsort=='post' ? "time" : "id") . " DESC LIMIT " . ($page*$config['linesPerPage']) . ", $config[linesPerPage]", $fid, $startdate);

	while($line = $db->fetchrow()){
		$threaddata[$line['id']] = $line;
		$threadids[$line['id']] = $line['id'];
	}

	$db->query("SELECT FOUND_ROWS()");
	$numthreads = $db->fetchfield();
	$numpages =  ceil($numthreads / $config['linesPerPage']);

//all new: 2, some new: 1, no new: 0
	if($userData['loggedIn'] && count($threadids)>0){
		$subscribes = array();
		$db->prepare_query("SELECT threadid,time,subscribe FROM forumread WHERE userid='$userData[userid]' && threadid IN (?)", $threadids);

		while($line = $db->fetchrow())
			$subscribes[$line['threadid']] = $line;

		foreach($threaddata as $threadid => $data){
			if(!isset($subscribes[$threadid])){
				if($readalltime < $threaddata[$threadid]['time'])
					$threaddata[$threadid]['new'] = 2; //subscribe is already 0
			}else{
				$threaddata[$threadid]['new'] = (($readalltime < $data['time']) && ($subscribes[$threadid]['time'] < $data['time']) ? 1 : 0);
				$threaddata[$threadid]['subscribe'] = ($subscribes[$threadid]['subscribe'] == 'y');
			}
		}

		$db->prepare_query("UPDATE forumupdated SET time = ? WHERE userid = ? && forumid = ?", time(), $userData['userid'], $fid);
		if($db->affectedrows()==0)
			$db->prepare_query("INSERT IGNORE INTO forumupdated SET userid = ?, forumid = ?, time = ?", $userData['userid'], $fid, time());
	}

	$lockids = array();
	foreach($threaddata as $threadid => $data){
		if($forumdata['autolock'] > 0 && $data['locked']=='n' && (time() - $data['time']) > $forumdata['autolock']){
			$lockids[] = $threadid;
			$threaddata[$threadid]['locked'] = 'y';
		}
	}
	if(count($lockids) > 0)
		$db->prepare_query("UPDATE forumthreads SET locked='y' WHERE id IN (?)", $lockids);

	$numonline = forumsNumOnline($fid);

	incHeader(false);

	echo "<table width=100% border=0 cellpadding=3 cellspacing=1>";

	$cols=5;
	if($isMod)
		$cols++;

	echo "<tr><td class=header2 colspan=$cols>";
	echo "<table width=100%><tr>";
	echo "<td class=header2>";

	if($forumdata['official']=='y')
		echo "<a class=header2 href=forums.php>Forums</a> > ";
	else
		echo "<a class=header2 href=forumsusercreated.php>User Created Forums</a> > ";

	echo "<a class=header2 href=$PHP_SELF?fid=$fid>$forumdata[name]</a>";
	echo "</td><form><td class=header2 align=right>";

	if($userData['loggedIn'] && $perms['post'])
		echo "<a class=header2 href=forumcreatethread.php?fid=$fid>Create New Thread</a> | ";

	echo "Page: " . pageList("$PHP_SELF?fid=$fid&age=$age",$page,$numpages,'header2');

	echo "</td></form></tr></table></td></tr>\n";

	if($userData['loggedIn']){
		echo "<tr><td class=header2 colspan=$cols>";
		echo "<table width=100%><tr>";
	//	echo "<form action=forumsearch.php method=get><td class=header2><input class=body type=text name=search[text]><input type=hidden name=action value=Search><input class=body type=submit value=Search> <a href=forumsearch.php class=header2>Advanced Search</a></td></form>";

		echo "<td align=right class=header2>";
		if($perms['invited'] && $forumdata['ownerid']!=$userData['userid'])
			echo "<a class=header2 href=\"javascript:confirmLink('$PHP_SELF?fid=$fid&action=withdraw','withdraw from this forum?')\">Withdraw From Forum Topic</a> | ";
		elseif($forumdata['official'] == 'n' && $forumdata['ownerid']!=$userData['userid'])
			echo "<a class=header2 href=$PHP_SELF?fid=$fid&action=subscribe>Subscribe to Forum Topic</a> | ";
		echo "<a class=header2 href=$PHP_SELF?fid=$fid&action=markallread>Mark All as Read</a> | ";
		echo "<a class=header2 href=$PHP_SELF?fid=$fid&action=" . ($forumsort=="post" ? "sortbythread>Sort By Thread" : "sortbypost>Sort By Post") . "</a>";
		echo "</td></tr></table></td></tr>\n";
	}

	echo "<tr>";
	if($isMod)
		echo "<form action=\"$PHP_SELF\" method=post><td class=header width=20></td>";
	echo "<td class=header>Threads</td><td class=header align=center width=120>Author</td><td class=header width=40 align=center>Replies</td><td class=header width=40 align=center>Views</td><td class=header align=right width=120>Last Post</td></tr>\n";


	foreach($threaddata as $line){
		echo "<tr>";

		if($isMod)
			echo "<td class=body><input type=checkbox name=checkID[] value=$line[id]></td>";

		echo "<td class=body>";
		if($line['locked']=='y')
			echo "<img src=$config[imageloc]locked.png> ";
		if($line['sticky']=='y')
			echo "<img src=$config[imageloc]up.png> ";
		if($line['announcement']=='y')
			echo "Announcement: ";
		if($line['moved']){
			echo "Moved: ";
			$line['id'] = $line['moved'];
		}
		if($line['pollid'])
			echo "Poll: ";

		echo "<a class=forumlst$line[new] href=forumviewthread.php?tid=$line[id]>";
		if($line['subscribe'])
			echo "<b>$line[title]</b>";
		else
			echo "$line[title]";
		echo "</a>";

		if($line['posts'] > $forumpostsperpage){
//			echo "<br>Page: ";

			$last = ceil(($line['posts']+1) / $forumpostsperpage);
			$max = min( $last , $config['pagesInList']);
			$list = array();
			for($i=0; $i < $max; $i++)
				$list[] = "<a class=body href=forumviewthread.php?tid=$line[id]&page=$i>" . ($i+1) . "</a>";
			if($max < $last )
				$list[] = "... <a class=body href=forumviewthread.php?tid=$line[id]&page=$last>" . ($last+1) . "</a>";

			echo " &nbsp; [ " . implode(" ", $list) . " ]";
		}

		echo "</td><td class=body align=center>";
		if($line['authorid'])
			echo "<a class=body href=profile.php?uid=$line[authorid]>$line[author]</a>";
		else
			echo "$line[author]";
		echo "</td><td class=body align=center>$line[posts]</td><td class=body align=center>$line[reads]</td>";
		echo "<td class=body nowrap align=right>" . userdate("M j, y g:i a",$line['time']);

		echo "<br>by ";
		if($line['lastauthorid'])
			echo "<a class=body href=profile.php?uid=$line[lastauthorid]>$line[lastauthor]</a>";
		else
			echo "$line[lastauthor]";

		echo "</td></tr>\n";
	}


	if($showModBar){
		echo "<input type=hidden name=fid value=$fid>";
		echo "<tr><td class=header colspan=$cols>";

		if($isMod){
//			echo "<input class=body name=selectall type=checkbox value='Check All' onClick=\"this.value=check(this.form,'check')\">";
			echo "<select class=body name=action><option value=cancel>Action:";

			if($perms['deletethreads'])
				echo "<option value=delete>Delete Threads";
			if($perms['move'])
				echo "<option value=move>Move Threads";
			if($perms['stick']){
				echo "<option value=stick>Make Sticky";
				echo "<option value=unstick>Lose Stickyness";
			}
			if($perms['lock']){
				echo "<option value=lock>Lock";
				echo "<option value=unlock>Unlock";
			}
			if($perms['announce']){
				echo "<option value=announce>Announce";
				echo "<option value=unannounce>Unannounce";
			}
			echo "</select>";

			if($perms['move']){
				$forumcats = & new category("forums","official='y' ORDER BY priority ASC");

				$branch = $forumcats->makeBranch();

				echo "<select class=body name=moveto><option value=0>Move thread to:" . makeCatSelect($branch) . "</select>";
			}
			echo "<input class=body type=submit value=Go> &nbsp; ";
		}

		$links = array();

		if($perms['mute'])
			$links[] = "<a class=header href=forummute.php?fid=$fid>Mute Users</a>";

		if($perms['globalmute'])
			$links[] = "<a class=header href=forummute.php?fid=0>Mute Users Globally</a>";

		if($perms['invite'])
			$links[] = "<a class=header href=foruminvite.php?fid=$fid>Invite Users</a>";

		if($perms['modlog'])
			$links[] = "<a class=header href=forummodlog.php?fid=$fid>Mod Log</a>";

		if($perms['editmods'])
			$links[] = "<a class=header href=forummods.php?fid=$fid>Edit Mods</a>";

		if($perms['admin'] && $forumdata['official']=='n')
			$links[] = "<a class=header href=forumedit.php?id=$fid>Edit Forum</a>";

		echo implode(" | ", $links);

		echo "</td></tr></form>";
	}

	echo "<tr><td class=header2 colspan=$cols>";
	echo "<table width=100%><tr><form>";
	echo "<td class=header2>";

	if($forumdata['official']=='y')
		echo "<a class=header2 href=forums.php>Forums</a> > ";
	else
		echo "<a class=header2 href=forumsusercreated.php>User Created Forums</a> > ";

	echo "<a class=header2 href=$PHP_SELF?fid=$fid>$forumdata[name]</a>";
	echo "</td><form><td class=header2 align=center>";

	echo "Show topics from last: <select class=body name=age onChange=\"location.href='$PHP_SELF?fid=$fid&page=$page&age='+(this.options[this.selectedIndex].value)\">" . make_select_list_key(array(0 => 'All Topics', 1 => '1 Day', 3 => '3 Days', 7 => '1 Week', 14 => '2 Weeks', 30 => '1 Month', 60 => '2 Months', 180 => '6 Months', 365 => '1 Year'), $age) . "</select>";

	echo "</td><td class=header2 align=right>";

	if($userData['loggedIn'] && $perms['post'])
		echo "<a class=header2 href=forumcreatethread.php?fid=$fid>Create New Topic</a> | ";
	echo "Page: " . pageList("$PHP_SELF?fid=$fid&age=$age",$page,$numpages,'header2');

	echo "</td></form></tr></table></td></tr>\n";

	echo "<tr><td class=body colspan=$cols>";
	echo "<table>";
	echo "<tr><td class=header>Legend:</td>";
	echo "<td class=body><a class=forumlst0 href=#>No unread Posts,</a></td>";
	echo "<td class=body><a class=forumlst1 href=#>Some unread Posts,</a></td>";
	echo "<td class=body><a class=forumlst2 href=#>New Topic</a></td></tr>";
	echo "<tr><td class=header>Users online in this topic:</td><td class=body>$numonline</td></tr>";
	echo "</table>";
	echo "</td></tr>";

	echo "</table>\n";

	incFooter(false);
