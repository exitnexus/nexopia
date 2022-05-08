<?

	$login=0;

	require_once("include/general.lib.php");

	if(empty($tid))
		die("Bad Thread id");

	$db->prepare_query("SELECT forumthreads.*, forums.name as forumname, official,pollid,edit,autolock FROM forumthreads,forums WHERE forumthreads.id = ? && forumthreads.forumid=forums.id", $tid);
	$thread = $db->fetchrow();

	if(!$thread)
		die("Bad Thread id");

	if($thread['moved'])
		die("Bad Thread id");

	$perms = getForumPerms($thread['forumid']);	//checks it's a forum, not a realm

	if(!$perms['view'])
		die("You don't have permission to view this forum");

	$isMod = $perms['move'] || $perms['deletethreads'] || $perms['deleteposts'] || $perms['lock'] || $perms['stick'];



	$query = "UPDATE forumthreads SET reads=reads+1 ";
	if($thread['locked']=='n' && $thread['autolock'] > 0 && (time() - $thread['time']) > $thread['autolock'] ){
		$query .= ", locked='y' ";
		$thread['locked']='y';
	}
	$query .= $db->prepare("WHERE id = ?", $tid);
	$db->query($query);

	if($userData['loggedIn']){
		switch($action){
			case "subscribe":
				subscribe($tid);
				break;
			case "unsubscribe":
				unsubscribe($tid);
				break;
			case "delete":
				if($perms['deleteposts']){
					if(!isset($checkID) || !is_array($checkID))
						break;

					foreach($checkID as $id)
						deletePost($id);
					$thread['posts'] -= count($checkID);
				}
				break;
			case "deletethread":	//deletes the whole thread.
				if($perms['deletethreads']){
					deleteThread($tid);
					header("location: forumthreads.php?fid=$thread[forumid]");
					exit;
				}
				break;
			case "lock":
				if($perms['lock'])
					if(lockThread($tid))
						$thread['locked']='y';
				break;
			case "unlock":
				if($perms['lock'])
					if(unlockThread($tid))
						$thread['locked']='n';
				break;
			case "stick":
				if($perms['stick'])
					if(stickThread($tid))
						$thread['sticky']='y';
				break;
			case "unstick":
				if($perms['stick'])
					if(unstickThread($tid))
						$thread['sticky']='n';
				break;
			case "announce":
				if($perms['announce'])
					if(announceThread($tid))
						$thread['announcement']='y';
				break;
			case "unannounce":
				if($perms['announce'])
					if(unannounceThread($tid))
						$thread['announcement']='n';
				break;
		}
	}

	if($userData['loggedIn']){
		$db->prepare_query("SELECT subscribe,time FROM forumread WHERE userid = ? && threadid = ?", $userData['userid'], $tid);
		if($db->numrows()==0){
			$readtime=0;
			$subscribe='n';
		}else{
			$line = $db->fetchrow();
			$subscribe = $line['subscribe'];
			$readtime = $line['time'];
		}
	}else{
		$subscribe='n';
		$readtime=0;
	}


	if($userData['loggedIn']){
		$db->prepare_query("SELECT forumpostsperpage,autosubscribe,showsigs FROM users WHERE userid = ?", $userData['userid']);
		$user = $db->fetchrow();

		$postsPerPage = $user['forumpostsperpage'];
	}else
		$postsPerPage = 25;
	$numpages = ceil(($thread['posts']+1)/$postsPerPage);

	if(!isset($page) || $page==-1){
		if($readtime > 0){
			$db->prepare_query("SELECT count(*) FROM forumposts WHERE threadid = ? && time <= ?", $tid, $readtime);
			$oldposts = $db->fetchfield();
			if($oldposts>0)	$oldposts--;
		}else{
			$oldposts = 0;
		}
		$page = floor($oldposts/$postsPerPage);
	}else{
		if($page<0)
			$page=0;
		if($page>=$numpages)
			$page=$numpages-1;
	}

	if($page <= $numpages/2){
		$sortd = 'ASC';
		$offset = $page*$postsPerPage;
		$limit = $postsPerPage;
	}else{
		$sortd = 'DESC';
		$offset = max(0,($thread['posts']+1) - ($page+1)*$postsPerPage);
		$limit = min($postsPerPage, ($thread['posts']+1) - $page*$postsPerPage);
	}


	$db->prepare_query("SELECT id, author, authorid, time, nmsg, edit FROM forumposts WHERE threadid = ? ORDER BY time $sortd LIMIT $offset, $limit", $tid);
	$postdata = array();
	$posterids = array();
	$posterdata = array();


	if($userData['loggedIn'] && ($thread['locked']=='n' || $isMod) && $page==$numpages-1)
		$posterids[$userData['userid']] = $userData['userid'];

	$lasttime=0;
	while($line = $db->fetchrow()){
		$postdata[] = $line;
		if($sortd == 'ASC' || !$lasttime)
			$lasttime=$line['time'];

		if($line['authorid'])
			$posterids[$line['authorid']] = $line['authorid'];
	}

	if($sortd == 'DESC')
		$postdata = array_reverse($postdata);

	if(count($posterids)>0){
		if(!$userData['loggedIn'] || $user['showsigs'] == 'y')
			$db->prepare_query("SELECT users.userid, online, age, posts, firstpic, forumrank, showpostcount, nsigniture, enablesignature FROM users,profile WHERE users.userid=profile.userid && users.userid IN (?)", $posterids);
		else
			$db->prepare_query("SELECT userid, online, age, posts, firstpic, forumrank, showpostcount, '' as nsigniture, 'n' as enablesignature FROM users WHERE userid IN (?)", $posterids);

		while($line = $db->fetchrow())
			$posterdata[$line['userid']] = $line;
	}


	if($userData['loggedIn']){
		if($readtime<$lasttime)
			$time = $lasttime+1;
		else
			$time = $readtime;

		if($readtime > 0)
			$db->prepare_query("UPDATE forumread SET time = ?, readtime = ?, notified='n' WHERE userid = ? && threadid = ?", $time, time(), $userData['userid'], $tid);
		if($readtime == 0 || ($readtime > 0 && $db->affectedrows()==0))
			$db->prepare_query("INSERT IGNORE INTO forumread SET userid = ?, threadid = ?, time = ?, readtime = ?", $userData['userid'], $tid, $time, time() );
	}

	$poll=false;
	if($thread['pollid']){
		if($action=='Vote' && isset($ans) && $userData['loggedIn'])
			votePoll($thread['pollid'],$ans);
		if($page==0)
			$poll = getPoll($thread['pollid'], false);
	}


//	$locations = & new category("locs"); //include dyn loc stuff

	incHeader(false);

/*
	$query = "SELECT count(DISTINCT userid) FROM forumread WHERE threadid='$tid' && readtime >= " . (time() - 300);
	$result = $db->query($query);
	$num = $db->fetchfield();

	echo "Users in this topic: $num";
*/

	echo "<table width=100% border=0 cellspacing=1 cellpadding=3>";// bordercolor=#666666 style=\"border-collapse: collapse\">\n";

	echo "<tr><td class=header colspan=2><table cellspacing=0 cellpadding=0 width=100%><tr><td class=header align=left>";
	if($thread['locked']=='y')			echo "<img src=$config[imageloc]locked.png> ";
	if($thread['sticky']=='y')			echo "<img src=$config[imageloc]up.png> ";
	if($thread['announcement']=='y')	echo "Announcement: ";

	if($thread['official']=='y')
		echo "<a class=header href=forums.php>Forums</a> > ";
	else
		echo "<a class=header href=forumsusercreated.php>User Created Forums</a> > ";
	echo "<a class=header href=forumthreads.php?fid=$thread[forumid]>$thread[forumname]</a> > ";
	echo "<a name=top class=header href=$PHP_SELF?tid=$tid>$thread[title]</a> ";

	echo "</td><td align=right class=header>";
	if($userData['loggedIn']){
		if($subscribe=='n')
			echo "<a class=header href=\"$PHP_SELF?action=subscribe&tid=$tid\">Subscribe</a>";
		else
			echo "<a class=header href=\"$PHP_SELF?action=unsubscribe&tid=$tid\">Unsubscribe</a>";
	}
	echo "</td>";
	echo "<td class=header align=right>";

	echo pageList("$PHP_SELF?tid=$tid",$page,$numpages,'header');

	echo "</td>";
	echo "</tr></table>";
	echo "</td></tr>\n";

	if($poll){
		if($userData['loggedIn']){
			$db->prepare_query("SELECT id FROM pollvotes WHERE userid = ? && pollid = ?", $userData['userid'], $poll['id']);
		}

		echo "<tr><td colspan=2 class=body align=center>";

		if(!$userData['loggedIn'] || $db->numrows()==0){
			echo "<table>";
			if($userData['loggedIn']){
				echo "<form action=$PHP_SELF method=get>";
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
				echo "<tr><td></td><td class=body><input class=body type=submit name=action value='Vote'> <a class=body href=$PHP_SELF?tid=$tid&ans=0&action=Vote>View Results</a></td></tr>";
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
				$width = $poll['tvotes']==0 ? 0 : (int)$ans["votes"]*$config['maxpollwidth']/$maxval;
				$percent = number_format($poll['tvotes']==0 ? 1 : $ans["votes"]/$poll['tvotes']*100,1);
				echo "<tr><td class=body>$ans[answer]</td>";
				echo "<td class=body><img src='$config[imageloc]red.png' width='$width' height=10> $ans[votes] ($percent %)</td></tr>";
			}
			echo "</table>";
		}
		echo "</td></tr>";
		echo "<tr><td class=header colspan=2>&nbsp;</td></tr>";
	}


	if($isMod)
		echo "<form method=post action=\"$PHP_SELF\">\n";

	foreach($postdata as $line){
		if(!isset($posterdata[$line['authorid']]))
			$line['authorid']=0;

		echo "<tr>";

		echo "<td class=body valign=top nowrap>";

		echo "<a name=p$line[id]></a>";

		if($line['authorid']){
			echo "<a class=body href=profile.php?uid=$line[authorid]><b>$line[author]</b></a>";
			$data = $posterdata[$line['authorid']];
		}else{
			echo "<b>$line[author]</b><br>deleted account";
		}

		echo "<br>";

		if($line['authorid']){
			if($data['forumrank']!="")
				echo $data['forumrank'];
			else
				echo forumrank($data['posts']);
			echo "<br>";
			if($data['online'] == 'y')
				echo "- Online -<br>";
		}

		if($config['forumPic'] && $line['authorid'] && $data['firstpic']>0)
			echo "<a class=header href=profile.php?uid=$line[authorid]><img src=$config[thumbloc]" . floor($data['firstpic']/1000) . "/$data[firstpic].jpg border=0></a><br>";

		if($line['authorid']){
			echo "<br>Age: <i>$data[age]</i><br>";
//			echo "Loc: <i>" . $locations->getCatName($data['loc']) . "</i><br>";
			if($data['showpostcount'] == 'y')
				echo "Posts: <i>$data[posts]</i><br>";
		}

		echo "</td><td class=body valign=top width=100%>";
		echo $line['nmsg'] . "&nbsp;";

		if($line['edit'])
			echo "<br><br>[edited on " . userdate("F j, Y \\a\\t g:i a",$line['edit']) . "]";

		if($line['authorid'] && $data['nsigniture']!="" && $data['enablesignature'] == 'y')// && ($userData['loggedIn'] || $user['showsigs'] == 'y'))
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
			if($data['icq']!=0)
				echo "<a class=small href=\"http://www.icq.com/scripts/search.dll?to=$data[icq]\"><img src=$skindir/forum/icq.jpg border=0></a>";
			if($data['msn']!="")
				echo "<a class=small href=\"profile.php?uid=$line[authorid]\"><img src=$skindir/forum/msn.jpg border=0></a>";
			if($data['aim']!="")
				echo "<a class=small href=\"aim:goim?screenname=$data[aim]&message=Hello+Are+you+there?\"><img src=$skindir/forum/aim.jpg border=0></a>";
			if($data['yahoo']!="")
				echo "<a class=small href=\"http://edit.yahoo.com/config/send_webmesg?.target=$data[yahoo]&.src=pg\"><img src=$skindir/forum/yim.jpg border=0></a>";

			if($data['showemail']=='y' || $mods->isAdmin($userData['userid'],'listusers'))
				echo "<a class=small href=\"mailto:$data[email]\"><img src=$skindir/forum/email.jpg border=0></a>";
			echo "<a class=small href=\"messages.php?action=write&to=$line[authorid]\"><img src=$skindir/forum/pm.jpg border=0></a>";
			echo "<a class=small href=\"friends.php?action=add&id=$line[authorid]\"><img src=$skindir/forum/friend.jpg border=0></a>";
			echo "<img src=$skindir/forum/divider.jpg>";
		}
*/
		$links = array();

		if($userData['loggedIn']){
			if((($thread['edit']=='y' || $perms['editownposts']) && $userData['userid']==$line['authorid']) || $perms['editallposts'])
				$links[] = "<a class=small href=\"forumpostedit.php?action=edit&msgid=$line[id]\">Edit</a>"; // <img src=$skindir/forum/edit.jpg border=0>
			$links[] =  "<a class=small href=\"forumreply.php?action=quote&pid=$line[id]&tid=$tid\">Quote</a>"; //<img src=$skindir/forum/quote.jpg border=0>
			$links[] =  "<a class=small href=forumreply.php?action=reply&tid=$tid>Reply</a>"; //<img src=$skindir/forum/reply.jpg border=0>
			$links[] =  "<a class=small href=reportabuse.php?type=forumpost&id=$line[id]>Report</a>";
		}
		$links[] =  "<a class=small href=#top>Top</a>"; // <img src=$skindir/forum/top.jpg border=0>

		echo implode(" &nbsp; &nbsp; ", $links);

		echo "</td></tr></table>";
		echo "</td></tr>";
		echo "<tr><td class=header2 colspan=2 height=2></td></tr>\n"; // <img src=$config[imageloc]empty.png>
	}



	echo "<tr><td class=header colspan=2><table cellspacing=0 cellpadding=0 width=100%><tr><td class=header align=left>";

	if($thread['locked']=='y')			echo "<img src=$config[imageloc]locked.png> ";
	if($thread['sticky']=='y')			echo "<img src=$config[imageloc]up.png> ";
	if($thread['announcement']=='y')	echo "Announcement: ";

	if($thread['official']=='y')
		echo "<a class=header href=forums.php>Forums</a> > ";
	else
		echo "<a class=header href=forumsusercreated.php>User Created Forums</a> > ";
	echo "<a class=header href=forumthreads.php?fid=$thread[forumid]>$thread[forumname]</a> > ";
	echo "<a name=top class=header href=$PHP_SELF?tid=$tid>$thread[title]</a> ";

	echo "</td><td align=right class=header>";
	if($userData['loggedIn']){
		if($subscribe=='n')
			echo "<a class=header href=\"$PHP_SELF?action=subscribe&tid=$tid\">Subscribe</a>";
		else
			echo "<a class=header href=\"$PHP_SELF?action=unsubscribe&tid=$tid\">Unsubscribe</a>";
	}
	echo "</td>";
	echo "<td class=header align=right>";

	echo pageList("$PHP_SELF?tid=$tid",$page,$numpages,'header');

	echo "</td>";
	echo "</tr></table>";
	echo "</td></tr>\n";




	if($isMod){
		echo "<input type=hidden name=tid value=$tid>";
		echo "<tr><td class=header2 colspan=2>";

		echo "<select class=header name=action><option value=cancel>Action:";
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
		if($perms['deletethreads'])
			echo "<option value=deletethread>Delete Whole Thread";
		echo "</select> <input type=submit value=Go>";

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
			echo "<select class=body name=subscribe><option value=n>Don't Subscribe<option value=y" . (($user['autosubscribe'] == 'y' || $subscribe=='y') ? ' selected' : '') . ">Subscribe</select>";
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
