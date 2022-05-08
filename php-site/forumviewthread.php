<?

	$login=0;
	$userprefs = array('forumpostsperpage','autosubscribe','showsigs');

	require_once("include/general.lib.php");

	if(!($tid = getREQval('tid', 'int')))
		die("Bad Thread id");

	$thread = $cache->get(array($tid, "forumthread-$tid"));

	if($thread === false){
		$forums->db->prepare_query("SELECT forumid, moved, title, posts, sticky, locked, announcement, flag, pollid, time FROM forumthreads WHERE id = #", $tid);
		$thread = $forums->db->fetchrow();

		if($thread)
			$cache->put(array($tid, "forumthread-$tid"), $thread, 10800);
	}

	if(!$thread || $thread['moved'])
		die("Bad Thread id");

	$perms = $forums->getForumPerms($thread['forumid']);	//checks it's a forum, not a realm

	if(!$perms['view'])
		die("You don't have permission to view this forum");

	$isAdmin = $mods->isAdmin($userData['userid'],'listusers');

	$isMod = $perms['move'] || $perms['deletethreads'] || $perms['deleteposts'] || $perms['lock'] || $perms['stick'];

	$forumdata = $perms['cols'];


	$query = "UPDATE forumthreads SET reads=reads+1 ";
	if($thread['locked']=='n' && $forumdata['autolock'] > 0 && (time() - $thread['time']) > $forumdata['autolock'] ){
		$query .= ", locked='y' ";
		$thread['locked']='y';
	}
	$query .= $forums->db->prepare("WHERE id = #", $tid);
	$forums->db->query($query);

	if($userData['loggedIn']){
		switch($action){
			case "subscribe":
				$forums->subscribe($tid);
				$cache->remove(array($userData['userid'], "forumread-$userData[userid]-$tid"));
				break;
			case "unsubscribe":
				$forums->unsubscribe($tid);
				$cache->remove(array($userData['userid'], "forumread-$userData[userid]-$tid"));
				break;
			case "delete":
				if($perms['deleteposts']){
					if(!isset($checkID) || !is_array($checkID))
						break;

					foreach($checkID as $id)
						$forums->deletePost($id);
					$thread['posts'] -= count($checkID);

					$cache->remove(array($tid, "forumthread-$tid"));
				}
				break;
			case "deletethread":	//deletes the whole thread.
				if($perms['deletethreads']){
					$forums->deleteThread($tid);
					$cache->remove("forumthread-$tid");
					header("location: forumthreads.php?fid=$thread[forumid]");
					exit;
				}
				break;
			case "lock":
				if($perms['lock']){
					if($forums->lockThread($tid)){
						$thread['locked']='y';
						$cache->remove(array($tid, "forumthread-$tid"));
					}
				}
				break;
			case "unlock":
				if($perms['lock']){
					if($forums->unlockThread($tid)){
						$thread['locked']='n';
						$cache->remove(array($tid, "forumthread-$tid"));
					}
				}
				break;
			case "stick":
				if($perms['stick']){
					if($forums->stickThread($tid)){
						$thread['sticky']='y';
						$cache->remove(array($tid, "forumthread-$tid"));
					}
				}
				break;
			case "unstick":
				if($perms['stick']){
					if($forums->unstickThread($tid)){
						$thread['sticky']='n';
						$cache->remove(array($tid, "forumthread-$tid"));
					}
				}
				break;
			case "announce":
				if($perms['announce']){
					if($forums->announceThread($tid)){
						$thread['announcement']='y';
						$cache->remove(array($tid, "forumthread-$tid"));
					}
				}
				break;
			case "unannounce":
				if($perms['announce']){
					if($forums->unannounceThread($tid)){
						$thread['announcement']='n';
						$cache->remove(array($tid, "forumthread-$tid"));
					}
				}
				break;
			case "flag":
				if($perms['flag']){
					if($forums->flagThread($tid)){
						$thread['flag']='y';
						$cache->remove(array($tid, "forumthread-$tid"));
					}
				}
				break;
			case "unflag":
				if($perms['flag']){
					if($forums->unflagThread($tid)){
						$thread['flag']='n';
						$cache->remove(array($tid, "forumthread-$tid"));
					}
				}
				break;
		}
	}

	$subscribe = 'n';
	$oldposts = 0;
	$readtime = 0;

	if($userData['loggedIn']){
		$line = $cache->get(array($userData['userid'], "forumread-$userData[userid]-$tid"));

		if(!$line){
			$forums->db->prepare_query("SELECT subscribe, time, posts FROM forumread WHERE userid = # && threadid = #", $userData['userid'], $tid);
			$line = $forums->db->fetchrow();
		}

		if($line){
			$subscribe = $line['subscribe'];
			$oldposts = $line['posts'];
			$readtime = $line['time'];
		}
	}

	if($userData['loggedIn'])
		$postsPerPage = $userData['forumpostsperpage'];
	else
		$postsPerPage = 25;
	$numpages = ceil(($thread['posts']+1)/$postsPerPage);

	if(($page = getREQval('page', 'int', -1)) === -1)
		$page = floor($oldposts/$postsPerPage);

	if($page<0)
		$page=0;
	if($page>=$numpages)
		$page=$numpages-1;

	if($page <= $numpages/2){
		$sortd = 'ASC';
		$offset = $page*$postsPerPage;
		$limit = $postsPerPage;
	}else{
		$sortd = 'DESC';
		$offset = max(0,($thread['posts']+1) - ($page+1)*$postsPerPage);
		$limit = min($postsPerPage, ($thread['posts']+1) - $page*$postsPerPage);
		if($limit <= 0)
			die("Error, bad page $page");
		$limit = abs($limit);
	}


	$forums->db->prepare_query("SELECT id, author, authorid, time, nmsg, edit FROM forumposts WHERE threadid = # ORDER BY time $sortd LIMIT $offset, $limit", $tid);
	$postdata = array();
	$posterids = array();
	$posterdata = array();


	if($userData['loggedIn'] && ($thread['locked']=='n' || $isMod) && $page==$numpages-1)
		$posterids[$userData['userid']] = $userData['userid'];

	$lasttime=0;
	while($line = $forums->db->fetchrow()){
		$postdata[] = $line;
		if($sortd == 'ASC' || !$lasttime)
			$lasttime=$line['time'];

		if($line['authorid'])
			$posterids[$line['authorid']] = $line['authorid'];
	}

	if($sortd == 'DESC')
		$postdata = array_reverse($postdata);

	if(count($posterids)){
		$db->prepare_query("SELECT userid, online, age, sex, posts, firstpic, forumrank, showpostcount, '' as nsigniture, premiumexpiry, frozen FROM users WHERE userid IN (#)", $posterids);

		while($line = $db->fetchrow())
			$posterdata[$line['userid']] = $line;

		if($userData['loggedIn'] && $userData['showsigs'] == 'y'){
			$profiledb->prepare_query($posterids, "SELECT userid, nsigniture FROM profile WHERE userid IN (#) && enablesignature = 'y'", $posterids);

			while($line = $profiledb->fetchrow())
				$posterdata[$line['userid']]['nsigniture'] = $line['nsigniture'];
		}
	}


	if($userData['loggedIn']){
		if($readtime<$lasttime)
			$time = $lasttime+1;
		else
			$time = $readtime;

		$newoldposts = max($oldposts, $postsPerPage*$page + count($postdata) - 1);

		$curtime = time();

		if($readtime > 0)
			$forums->db->prepare_query("UPDATE forumread SET time = #, readtime = #, posts = # WHERE userid = # && threadid = #", $time, $curtime, $newoldposts, $userData['userid'], $tid);
		if($readtime == 0 || ($readtime > 0 && $forums->db->affectedrows()==0))
			$forums->db->prepare_query("INSERT IGNORE INTO forumread SET userid = #, threadid = #, time = #, readtime = #, posts = #", $userData['userid'], $tid, $time, $curtime, $newoldposts);

		$cache->put(array($userData['userid'], "forumread-$userData[userid]-$tid"), array('subscribe' => $subscribe, 'time' => $time, 'posts' => $newoldposts), 10800);
	}

	$poll=false;
	if($thread['pollid'] && $thread['locked'] == 'n'){
		if($action=='Vote' && isset($ans) && $userData['loggedIn'])
			$polls->votePoll($thread['pollid'],$ans);

		if($page==0){
			$poll = $polls->getPoll($thread['pollid'], false);
			$voted = $polls->pollVoted($thread['pollid']);
		}
	}


//	$locations = & new category( $db, "locs"); //include dyn loc stuff

	incHeader(false);

	echo "<table width=100% border=0 cellspacing=1 cellpadding=3>";// bordercolor=#666666 style=\"border-collapse: collapse\">\n";

	echo "<tr><td class=header colspan=2><table cellspacing=0 cellpadding=0 width=100%><tr><td class=header align=left>";
//	if($isMod && $thread['flag']=='y')	echo "<img src=$config[imageloc]flag.gif> ";
	if($thread['locked']=='y')			echo "<img src=$config[imageloc]locked.png> ";
	if($thread['sticky']=='y')			echo "<img src=$config[imageloc]up.png> ";
	if($thread['announcement']=='y')	echo "Announcement: ";

	if($forumdata['official']=='y')
		echo "<a class=header href=forums.php>Forums</a> > ";
	else
		echo "<a class=header href=forumsusercreated.php>User Created Forums</a> > ";
	echo "<a class=header href=forumthreads.php?fid=$thread[forumid]>$forumdata[name]</a> > ";
	echo "<a name=top class=header href=$_SERVER[PHP_SELF]?tid=$tid>$thread[title]</a> ";

	echo "</td><td align=right class=header>";
	if($userData['loggedIn']){
		if($subscribe=='n')
			echo "<a class=header href=\"$_SERVER[PHP_SELF]?action=subscribe&tid=$tid\">Subscribe</a>";
		else
			echo "<a class=header href=\"$_SERVER[PHP_SELF]?action=unsubscribe&tid=$tid\">Unsubscribe</a>";
	}
	echo "</td>";
	echo "<td class=header align=right>";

	echo pageList("$_SERVER[PHP_SELF]?tid=$tid",$page,$numpages,'header');

	echo "</td>";
	echo "</tr></table>";
	echo "</td></tr>\n";

	if($poll){
		echo "<tr><td colspan=2 class=body align=center>";

		if((!$userData['loggedIn'] || !$voted) && $thread['locked'] == 'n'){
			echo "<table>";
			if($userData['loggedIn']){
				echo "<form action=$_SERVER[PHP_SELF] method=get>";
				echo "<input type=hidden name=tid value=$tid>";
			}
			echo "<tr><td colspan=2 class=body><b>$poll[question]</b></td></tr>";
			foreach($poll['answers'] as $ans){
				echo "<tr><td class=body>";
				if($userData['loggedIn'])
					echo "<input type=radio name='ans' value='$ans[id]' id='ans$ans[id]'>";
				echo "</td><td class=body><label for='ans$ans[id]'>$ans[answer]</label></td></tr>";
			}
			if($userData['loggedIn']){
				echo "<tr><td></td><td class=body><input class=body type=submit name=action value='Vote'> <a class=body href=$_SERVER[PHP_SELF]?tid=$tid&ans=0&action=Vote>View Results</a></td></tr>";
				echo "</form>";
			}
			echo "</table>";
		}else{
			echo "<table>";
			echo "<tr><td class=body colspan=2><b>$poll[question]</b></td></tr>";

			$maxval=0;
			foreach($poll['answers'] as $ans)
				if($ans['votes']>$maxval)
					$maxval = $ans['votes'];

			foreach($poll['answers'] as $ans){
				$width = $poll['tvotes'] == 0 ? 0 : (int)$ans["votes"]*$config['maxpollwidth']/$maxval;
				echo "<tr><td class=body>$ans[answer]</td>";
				echo "<td class=body><img src='$config[imageloc]red.png' width='$width' height=10> $ans[votes] " . ($poll['tvotes'] == 0 ? '' : '(' . number_format($ans["votes"]/$poll['tvotes']*100, 1) . ' %)' ) . "</td></tr>";
			}
			echo "</table>";
		}
		echo "</td></tr>";
		echo "<tr><td class=header colspan=2>&nbsp;</td></tr>";
	}


	if($isMod)
		echo "<form method=post action=$_SERVER[PHP_SELF]>\n";

	$time = time();

	foreach($postdata as $line){
		echo "<tr>";
		echo "<td class=body valign=top nowrap>";
		echo "<a name=p$line[id]></a>";

		if(isset($posterdata[$line['authorid']]) && ($posterdata[$line['authorid']]['frozen'] == 'n' || $isAdmin)){
			echo "<a class=body href=profile.php?uid=$line[authorid]><b>$line[author]</b></a><br>";
			$data = $posterdata[$line['authorid']];

			if($isAdmin && $data['frozen'] == 'y')
				echo "<b>frozen account</b><br>";
		}else{
			echo "<b>$line[author]</b><br>deleted account<br>";
			$line['authorid']=0;
		}

		if($line['authorid']){
			if($data['forumrank']!="" && $data['premiumexpiry'] > $time)
				echo $data['forumrank'];
			else
				echo $forums->forumrank($data['posts']);
			echo "<br>";
			if($data['online'] == 'y')
				echo "- Online -<br>";
		}

		if($config['forumPic'] && $line['authorid'] && $data['firstpic']>0)
			echo "<a class=header href=profile.php?uid=$line[authorid]><img src=http://" . chooseImageServer($data['firstpic']) . $config['thumbdir'] . floor($data['firstpic']/1000) . "/$data[firstpic].jpg border=0></a><br>";

		if($line['authorid']){
			echo "<br>Age <i>$data[age]</i>, $data[sex]<br>";
//			echo "Loc: <i>" . $locations->getCatName($data['loc']) . "</i><br>";
			if($data['showpostcount'] == 'y')
				echo "Posts: <i>" . number_format($data['posts']) . "</i><br>";
		}

		echo "</td><td class=body valign=top width=100%>";
		echo $line['nmsg'] . "&nbsp;";

		if($line['edit'])
			echo "<br><br>[edited on " . userdate("F j, Y \\a\\t g:i a",$line['edit']) . "]";

		if($line['authorid'] && $data['nsigniture'] != "")
			echo "<br><br>________________________________<br>" . $data['nsigniture'] . "&nbsp;";

		echo "</td></tr>\n";
		echo "<tr>";

		echo "<td class=small colspan=2>";

		echo "<table width=100% cellspacing=0 cellpadding=0><tr><td class=small>";

		if($isMod){
			echo "<input type=checkbox name=checkID[] value=$line[id]>";
			echo "</td><td class=small>";
		}

		echo userdate("l F j, Y, g:i a",$line['time']) ."</td><td align=right class=small>";

/*		if($userData['loggedIn'] && $line['authorid']){
			echo "<a class=small href=\"profile.php?uid=$line[authorid]\"><img src=$skindir/forum/profile.jpg border=0></a>";
			echo "<a class=small href=\"messages.php?action=write&to=$line[authorid]\"><img src=$skindir/forum/pm.jpg border=0></a>";
			echo "<a class=small href=\"friends.php?action=add&id=$line[authorid]\"><img src=$skindir/forum/friend.jpg border=0></a>";
			echo "<img src=$skindir/forum/divider.jpg>";
		}
*/
		$links = array();

		if($userData['loggedIn']){
			if((($forumdata['edit']=='y' || $perms['editownposts']) && $userData['userid']==$line['authorid']) || $perms['editallposts'])
				$links[] = "<a class=small href=\"forumpostedit.php?action=edit&msgid=$line[id]\">Edit</a>"; // <img src=$skindir/forum/edit.jpg border=0>
			$links[] =  "<a class=small href=\"forumreply.php?action=quote&pid=$line[id]&tid=$tid\">Quote</a>"; //<img src=$skindir/forum/quote.jpg border=0>
			$links[] =  "<a class=small href=forumreply.php?action=reply&tid=$tid>Reply</a>"; //<img src=$skindir/forum/reply.jpg border=0>
			$links[] =  "<a class=small href=reportabuse.php?type=" . MOD_FORUMPOST . "&id=$line[id]>Report</a>";
		}
		$links[] =  "<a class=small href=#top>Top</a>"; // <img src=$skindir/forum/top.jpg border=0>

		echo implode(" &nbsp; &nbsp; ", $links);

		echo "</td></tr></table>";
		echo "</td></tr>";
		echo "<tr><td class=header2 colspan=2 height=2></td></tr>\n"; // <img src=$config[imageloc]empty.png>
	}



	echo "<tr><td class=header colspan=2><table cellspacing=0 cellpadding=0 width=100%><tr><td class=header align=left>";

//	if($isMod && $thread['flag']=='y')	echo "<img src=$config[imageloc]flag.gif> ";
	if($thread['locked']=='y')			echo "<img src=$config[imageloc]locked.png> ";
	if($thread['sticky']=='y')			echo "<img src=$config[imageloc]up.png> ";
	if($thread['announcement']=='y')	echo "Announcement: ";

	if($forumdata['official']=='y')
		echo "<a class=header href=forums.php>Forums</a> > ";
	else
		echo "<a class=header href=forumsusercreated.php>User Created Forums</a> > ";
	echo "<a class=header href=forumthreads.php?fid=$thread[forumid]>$forumdata[name]</a> > ";
	echo "<a name=top class=header href=$_SERVER[PHP_SELF]?tid=$tid>$thread[title]</a> ";

	echo "</td><td align=right class=header>";
	if($userData['loggedIn']){
		if($subscribe=='n')
			echo "<a class=header href=\"$_SERVER[PHP_SELF]?action=subscribe&tid=$tid\">Subscribe</a>";
		else
			echo "<a class=header href=\"$_SERVER[PHP_SELF]?action=unsubscribe&tid=$tid\">Unsubscribe</a>";
	}
	echo "</td>";
	echo "<td class=header align=right>";

	echo pageList("$_SERVER[PHP_SELF]?tid=$tid",$page,$numpages,'header');

	echo "</td>";
	echo "</tr></table>";
	echo "</td></tr>\n";




	if($isMod){
		echo "<input type=hidden name=tid value=$tid>";
		echo "<tr><td class=header2 colspan=2>";

		echo "<select class=body name=action><option value=cancel>Action:";
		if($perms['deleteposts'])
			echo "<option value=delete>Delete Selected Posts";
		if($perms['stick']){
			echo "<option value=stick>Stick";
			echo "<option value=unstick>Unstick";
		}
		if($perms['lock']){
			echo "<option value=lock>Lock";
			echo "<option value=unlock>Unlock";
		}
		if($perms['announce']){
			echo "<option value=announce>Announce";
			echo "<option value=unannounce>Unannounce";
		}
		if($perms['flag']){
			echo "<option value=flag>Flag";
			echo "<option value=unflag>UnFlag";
		}
		if($perms['deletethreads'])
			echo "<option value=deletethread>Delete Whole Thread";
		echo "</select> <input class=body type=submit value=Go>";

		echo "</td></tr></form>";
	}

	echo "<tr><td class=header2 colspan=2 align=center>";


	if($userData['loggedIn'] && $perms['post'] && ($thread['locked']=='n' || $perms['postlocked'])){
		if($page==$numpages-1){
			echo "<table align=center>";
			echo "<tr><td class=header2 colspan=2><a class=body name=reply>Post a reply:</a></td></tr>\n";
			echo "<form action=\"forumreply.php\" method=post enctype=\"application/x-www-form-urlencoded\" name=editbox>\n";
			echo "<input type=hidden name='tid' value='$tid'>\n";
			echo "<tr><td class=header2 colspan=2>";

			editBox("",true);

			echo "</td></tr>";
			echo "<tr><td class=header2 align=center colspan=2>";
			echo "<select class=body name=subscribe><option value=n>Don't Subscribe<option value=y" . (($userData['autosubscribe'] == 'y' || $subscribe=='y') ? ' selected' : '') . ">Subscribe</select>";
			echo "<input class=body name=action type=submit value='Preview'>";
			echo "<input class=body name=action type=submit value='Post' accesskey='s' onClick='checksubmit()'>";
			echo "</td></tr>\n";
			echo "</form>";
			echo "</table>";
		}else{
			echo "You must be on the last page of the topic to reply";
		}
	}elseif(!$userData['loggedIn']){
		echo "You must be logged in to Reply. <a class=header2 href='login.php?referer=" . urlencode($REQUEST_URI) . "'>Login</a>";
	}elseif(!$perms['post']){
		echo "You don't have permission to post in this forum";
	}else{
		echo "This thread is locked";
	}
	echo "</td></tr>";
	echo "</table>";

	incFooter();

