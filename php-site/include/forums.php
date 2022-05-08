<?

	$sorttimes = array(0 => 'All Topics', 1 => '1 Day', 3 => '3 Days', 7 => '1 Week', 14 => '2 Weeks', 30 => '1 Month', 60 => '2 Months', 180 => '6 Months', 365 => '1 Year');
	$mutelength = array( 0 => "Indefinately", 3600 => "1 hour", 3*3600 => "3 hours", 12*3600 => "12 hours", 86400 => "1 day", 2*86400 => "2 days", 3*86400 => "3 days", 7*86400 => "1 week", 14*86400 => "2 weeks", 30*86400 => "1 month", 60*86400 => "2 months");

function forumrank($posts){
	global $cache;

	$forumranks = $cache->hdget("forumranks", 'getForumRanks');

	foreach($forumranks as $postmax => $name)
		if($posts < $postmax)
			return $name;

	return "";
}

function getForumRanks(){
	global $db;

	$db->query("SELECT postmax,name FROM forumranks ORDER BY postmax ASC");

	$forumranks = array();
	while($line = $db->fetchrow())
		$forumranks[$line['postmax']] = $line['name'];

	return $forumranks;
}

function forumsNumOnline($fid = 0){
	global $db, $cache, $config;

	$online = $cache->get("fo$fid");

	if($online === false){
		if($fid)
			$db->prepare_query("SELECT count(DISTINCT userid) FROM forumupdated WHERE forumid = ? && time >= ?", $fid, (time() - $config['friendAwayTime']) );
		else
			$db->prepare_query("SELECT count(DISTINCT userid) FROM forumupdated WHERE time >= ?", (time() - $config['friendAwayTime']) );

		$online = $db->fetchfield();

		$cache->put("fo$fid", $online, 60);
	}

	return $online;
}

function subscribe($tid){
	global $userData,$db;
	$db->prepare_query("UPDATE forumread SET subscribe='y' WHERE userid = ? && threadid = ?", $userData['userid'], $tid);
}

function unsubscribe($tid){
	global $userData,$db;
	$db->prepare_query("UPDATE forumread SET subscribe='n' WHERE userid = ? && threadid = ?", $userData['userid'], $tid);
}

function modLog($action,$forumid,$threadid,$var1=0,$var2=0,$userid=0){
	global $userData,$db;
	if($userid==0)
		$userid=$userData['userid'];

	$db->prepare_query("INSERT INTO forummodlog SET userid = ?, action = ?, forumid = ?, threadid = ?, var1 = ?, var2 = ?, time = ?",
		$userid, $action, $forumid, $threadid, $var1, $var2, time());
}


function getForumPerms($fid,$cols = array()){
	global $userData, $db, $mods;

	static $forums = array();

	if(isset($forums[$fid]))
		return $forums[$fid];


	$perms = array( 'view' => false,
					'post' => false,
					'postlocked' => false,
					'move' => false,
					'editownposts' => false,
					'editallposts' => false,
					'deleteposts' => false,
					'deletethreads' => false,
					'lock' => false,
					'stick' => false,
					'mute' => false,
					'globalmute' => false,
					'invite' => false,
					'announce' => false,
					'modlog' => false,
					'editmods' => false,
					'admin' => false,
					'invited' => false,
					'cols' => array()
					);

	$selects = array('public','ownerid','mute','edit','parent','official');

	foreach($cols as $col)
		if(!in_array($col,$selects))
			$selects[] = $col;

	$db->prepare_query("SELECT " . implode(",", $selects) . " FROM forums WHERE id = ?", $fid);

	if($db->numrows()==0)
		die("Bad Forum id");

	$forumdata = $db->fetchrow();

	foreach($cols as $col)
		$perms['cols'][$col] = $forumdata[$col];


	if($forumdata['parent']==0 && $forumdata['official']=='y')
		die("Bad Forum id");


	if($forumdata['public']=='y')
		$perms['view']=true;

	if($userData['loggedIn']){
		$isAdmin = $mods->isAdmin($userData['userid'],"forums");

		if($isAdmin || $userData['userid']==$forumdata['ownerid']){
			$perms['view']=true;
			$perms['post']=true;
			$perms['editallposts']=true;
			$perms['postlocked']=true;
			$perms['move']=true;
			$perms['editownposts']=true;
			$perms['deleteposts']=true;
			$perms['deletethreads']=true;
			$perms['lock']=true;
			$perms['stick']=true;
			$perms['mute']=true;
			$perms['invite']=true;
			$perms['announce']=true;
			$perms['modlog']=true;
			$perms['editmods']=true;
			$perms['admin']=true;

			if($userData['userid']!=$forumdata['ownerid'] && $forumdata['official']=='n'){
				$db->prepare_query("SELECT id FROM foruminvite WHERE userid = ? && forumid = ?", $userData['userid'], $fid);

				$perms['invited'] = ($db->numrows() > 0);
			}
			if($isAdmin){
				$perms['globalmute'] = true;
			}
		}else{
			$db->prepare_query("SELECT * FROM forummods WHERE userid = ? && forumid IN (0, ?) ORDER BY forumid DESC", $userData['userid'], $fid);

			if($db->numrows() == 0){
				$modperms = false;
			}else{
				$modperms = array('global' => 'n');

				while($line = $db->fetchrow()){
					foreach($line as $n => $v){
						if(!isset($modperms[$n]))
							$modperms[$n] = $v;
						elseif($v == 'y') // useful for adding extra permissions by forum
							$modperms[$n] = 'y';
					}
					if($line['forumid']==0){
						$perms['globalmute'] = ($line['mute'] == 'y');
						$modperms['global'] = 'y';
					}
				}
			}

			if($forumdata['official']=='n'){
				$db->prepare_query("SELECT id FROM foruminvite WHERE userid = ? && forumid = ?", $userData['userid'], $fid);

				$perms['invited'] = ($db->numrows() > 0);

				if($forumdata['public']=='n')
					$perms['view'] = ($db->numrows() > 0);
			}else
				$perms['view'] = true;

			if($modperms){
				$perms['view']=true;
				$perms['post']=true;
			}elseif($forumdata['mute']=='n'){
				$result = $db->prepare_query("SELECT forumid, unmutetime FROM forummute WHERE userid = ? && forumid IN (0, ?)", $userData['userid'], $fid);

				if($db->numrows($result)){
					$unmutetime = 0;

					while($line = $db->fetchrow($result)){
						if($line['unmutetime'] > 0 && $line['unmutetime'] < time()){
							$db->prepare_query("DELETE FROM forummute WHERE userid = ? && forumid = ?", $userData['userid'], $line['forumid']);

							modLog('unmute', $line['forumid'], 0, $userData['userid'], $line['unmutetime'], 0);
						}
						if($line['unmutetime'] == 0){
							$unmutetime = 0;
							break;
						}
						if($line['unmutetime'] > $unmutetime)
							$unmutetime = $line['unmutetime'];
					}

					$perms['post'] = ( $unmutetime > 0 && $unmutetime < time() );
				}else{
					$perms['post']=true;
				}
			}


			if($forumdata['edit']=='y' || $modperms['editownposts'] == 'y')
				$perms['editownposts']=true;

			if($modperms){
				$perms['postlocked'] = true;
				$perms['editallposts']= 	($modperms['editposts']		=='y');
				$perms['move']= 			($modperms['move']			=='y');
				$perms['deleteposts']= 		($modperms['deleteposts']	=='y');
				$perms['deletethreads']= 	($modperms['deletethreads']	=='y');
				$perms['lock']= 			($modperms['lock']			=='y');
				$perms['stick']= 			($modperms['stick']			=='y');
				$perms['mute']= 			($modperms['mute']			=='y');
				$perms['invite']= 			($modperms['invite']		=='y');
				$perms['announce']= 		($modperms['announce']		=='y');
				$perms['modlog']= 			($modperms['modlog']		=='y');
				$perms['editmods']= 		($modperms['editmods']		=='y');
			}
		}
		if($forumdata['official']=='y'){
			$perms['invite']=false;
		}else{
			$perms['move']=false;
		}
	}

	$forums[$fid] = $perms;

	return $perms;
}
/*
//don't lower the users post count
function deleteThread($tid){
	global $msgs,$db, $userData;

	$db->prepare_query("SELECT forumid, authorid, posts FROM forumthreads WHERE id = ?", $tid);
	$thread = $db->fetchrow();

	$perms = getForumPerms($thread['forumid']);

	if($perms['deletethreads']){
		modLog('deletethread',$thread['forumid'],$tid,$thread['authorid']);

		$db->prepare_query("UPDATE forums SET posts = posts - ? - 1, threads=threads-1 WHERE id = ?", $thread['posts'], $thread['forumid']);
		$db->prepare_query("INSERT INTO forumthreadsdel SELECT * FROM forumthreads WHERE id = ?", $tid);
		$db->prepare_query("INSERT INTO forumpostsdel SELECT * FROM forumposts WHERE threadid = ?", $tid);

		$db->prepare_query("DELETE FROM forumthreads WHERE id = ?", $tid);
		$db->prepare_query("DELETE FROM forumposts WHERE threadid = ?", $tid);
		$db->prepare_query("DELETE FROM forumread WHERE threadid = ?", $tid);

		$msgs->addMsg("Thread deleted");
		return true;
	}
	$msgs->addMsg("You don't have permission to mod this forum");
	return false;
}
*/

function deleteThread($tid){
	global $msgs,$db, $userData;

	$db->prepare_query("SELECT forumid, authorid, posts FROM forumthreads WHERE id = ?", $tid);
	$thread = $db->fetchrow();

	$perms = getForumPerms($thread['forumid']);

	if($perms['deletethreads']){
		$db->prepare_query("UPDATE forums SET posts = posts - ? - 1, threads=threads-1 WHERE id = ?", $thread['posts'], $thread['forumid']);

		modLog('deletethread',$thread['forumid'],$tid,$thread['authorid']);

		$db->prepare_query("SELECT authorid,count(*) as count FROM forumposts WHERE threadid = ? GROUP BY authorid", $tid);

		$counts = array();
		while($line = $db->fetchrow())
			if($line['authorid'])
				$counts[$line['count']][] = $line['authorid'];

		foreach($counts as $count => $authors)
			$db->prepare_query("UPDATE users SET posts = posts - ? WHERE userid IN (?)", $count, $authors);

		$db->prepare_query("INSERT INTO forumthreadsdel SELECT * FROM forumthreads WHERE id = ?", $tid);
		$db->prepare_query("INSERT INTO forumpostsdel SELECT * FROM forumposts WHERE threadid = ?", $tid);

		$db->prepare_query("DELETE FROM forumthreads WHERE id = ?", $tid);
		$db->prepare_query("DELETE FROM forumposts WHERE threadid = ?", $tid);
		$db->prepare_query("DELETE FROM forumread WHERE threadid = ?", $tid);

		$msgs->addMsg("Thread deleted");
		return true;
	}
	$msgs->addMsg("You don't have permission to mod this forum");
	return false;
}

function deletePost($pid){
	global $msgs,$db, $userData;

	$db->prepare_query("SELECT forumid,threadid,forumposts.authorid FROM forumthreads,forumposts WHERE forumposts.id = ? && forumthreads.id=forumposts.threadid", $pid);
	$post = $db->fetchrow();

	$perms = getForumPerms($post['forumid']);

	if($perms['deleteposts']){
		modLog('deletepost',$post['forumid'],$post['threadid'],$pid,$post['authorid']);

		$db->prepare_query("INSERT INTO forumpostsdel SELECT * FROM forumposts WHERE id = ?", $pid);

		$db->prepare_query("DELETE FROM forumposts WHERE id = ?", $pid);

		$db->prepare_query("SELECT time,author,authorid FROM forumposts WHERE threadid = ? ORDER BY time DESC LIMIT 1", $post['threadid']);
		$line = $db->fetchrow();

		$db->prepare_query("UPDATE forumthreads SET posts = posts - 1, time = ?,lastauthor = ?, lastauthorid = ? WHERE id = ?", $line['time'], $line['author'], $line['authorid'], $post['threadid']);

		$db->prepare_query("UPDATE forums SET posts=posts-1 WHERE id = ?", $post['forumid']);

		if($post['authorid'])
			$db->prepare_query("UPDATE users SET posts = posts -1 WHERE userid = ?", $post['authorid']);

		$msgs->addMsg("Post deleted");
		return true;
	}
	$msgs->addMsg("You don't have permission to mod this forum");
	return false;
}

function lockThread($tid){
	global $userData,$msgs,$db;

	$db->prepare_query("SELECT forumid, authorid, lastauthorid FROM forumthreads WHERE id = ?", $tid);
	$thread = $db->fetchrow();

	$perms = getForumPerms($thread['forumid']);

	if($perms['lock']){
		$db->prepare_query("UPDATE forumthreads SET locked='y' WHERE id = ?", $tid);

		modLog('lock',$thread['forumid'],$tid,$thread['authorid'], $thread['lastauthorid']);

		$msgs->addMsg("Thread locked");
		return true;
	}
	$msgs->addMsg("You don't have permission to lock threads in this forum");
	return false;
}

function unlockThread($tid){
	global $userData,$msgs,$db;

	$db->prepare_query("SELECT forumid FROM forumthreads WHERE id = ?", $tid);
	$thread = $db->fetchrow();

	$perms = getForumPerms($thread['forumid']);

	if($perms['lock']){
		$db->prepare_query("UPDATE forumthreads SET locked='n' WHERE id = ?", $tid);

		modLog('unlock',$thread['forumid'],$tid);

		$msgs->addMsg("Thread unlocked");
		return true;
	}
	$msgs->addMsg("You don't have permission to lock threads in this forum");
	return false;
}

function stickThread($tid){
	global $userData,$msgs,$db;

	$db->prepare_query("SELECT forumid FROM forumthreads WHERE id = ?", $tid);
	$thread = $db->fetchrow();

	$perms = getForumPerms($thread['forumid']);

	if($perms['stick']){
		$db->prepare_query("UPDATE forumthreads SET sticky='y' WHERE id = ?", $tid);

		modLog('stick',$thread['forumid'],$tid);

		$msgs->addMsg("Thread made sticky");
		return true;
	}
	$msgs->addMsg("You don't have permission to stick threads in this forum");
	return false;
}

function unstickThread($tid){
	global $userData,$msgs,$db;

	$db->prepare_query("SELECT forumid FROM forumthreads WHERE id = ?", $tid);
	$thread = $db->fetchrow();

	$perms = getForumPerms($thread['forumid']);

	if($perms['stick']){
		$db->prepare_query("UPDATE forumthreads SET sticky='n' WHERE id = ?", $tid);

		modLog('unstick',$thread['forumid'],$tid);

		$msgs->addMsg("Thread made unsticky");
		return true;
	}
	$msgs->addMsg("You don't have permission to stick threads in this forum");
	return false;
}

function announceThread($tid){
	global $userData,$msgs,$db;

	$db->prepare_query("SELECT forumid FROM forumthreads WHERE id = ?", $tid);
	$thread = $db->fetchrow();

	$perms = getForumPerms($thread['forumid']);

	if($perms['announce']){
		$db->prepare_query("UPDATE forumthreads SET announcement='y' WHERE id = ?", $tid);

		modLog('announce',$thread['forumid'],$tid);

		$msgs->addMsg("Thread announced");
		return true;
	}
	$msgs->addMsg("You don't have permission to announce threads in this forum");
	return false;
}

function unannounceThread($tid){
	global $userData,$msgs,$db;

	$db->prepare_query("SELECT forumid FROM forumthreads WHERE id = ?", $tid);
	$thread = $db->fetchrow();

	$perms = getForumPerms($thread['forumid']);

	if($perms['announce']){
		$db->prepare_query("UPDATE forumthreads SET announcement='n' WHERE id = ?", $tid);

		modLog('unannounce',$thread['forumid'],$tid);

		$msgs->addMsg("Thread unannounced");
		return true;
	}
	$msgs->addMsg("You don't have permission to announce threads in this forum");
	return false;
}

function moveThread($tid,$dest){
	global $userData,$msgs,$db;

	$db->prepare_query("SELECT * FROM forumthreads WHERE id = ?", $tid);
	$thread = $db->fetchrow();

	if($thread['moved'])
		return;

	if($thread['forumid']==$dest)
		return;

	$perms = getForumPerms($thread['forumid']);

	if($perms['move']){
		modLog('move',$thread['forumid'],$tid,$thread['forumid'],$dest);
		modLog('move',$dest,$tid,$thread['forumid'],$dest);

		$db->prepare_query("UPDATE forums SET posts = posts - ? - 1, threads=threads-1 WHERE id = ?", $thread['posts'], $thread['forumid']);

		$db->prepare_query("UPDATE forums SET posts = posts + ? + 1, threads=threads+1, time = GREATEST(time, ?) WHERE id = ?", $thread['posts'], $thread['time'], $dest);

		$db->prepare_query("INSERT INTO forumthreads (forumid, moved, announcement, title, authorid, author, reads, posts, `time`, lastauthorid, lastauthor, sticky, locked, pollid) " .
								"SELECT forumid, ?, 'n', title, authorid, author, reads, posts, time, lastauthorid, lastauthor, 'n', locked, pollid FROM forumthreads WHERE id = ?", $tid, $tid);

		$db->prepare_query("UPDATE forumthreads SET forumid = ? WHERE id = ?", $dest, $tid);

		$msgs->addMsg("Thread moved");
		return true;
	}

	$msgs->addMsg("You don't have permission to mod this forum");
	return false;
}

function deleteForum($id){
	global $db;

	$db->prepare_query("SELECT id FROM forumthreads WHERE forumid = ?", $id);

	$threads = array();
	while($line = $db->fetchrow())
		$threads[] = $line['id'];

	$db->prepare_query("DELETE FROM forumposts WHERE threadid IN (?)", $threads);
	$db->prepare_query("DELETE FROM forumthreads WHERE forumid = ?", $id);

	$db->prepare_query("SELECT id FROM forumthreadsdel WHERE forumid = ?", $id);

	$threads = array();
	while($line = $db->fetchrow())
		$threads[] = $line['id'];

	$db->prepare_query("DELETE FROM forumpostsdel WHERE threadid IN (?)", $threads);
	$db->prepare_query("DELETE FROM forumthreadsdel WHERE forumid = ?", $id);

	$db->prepare_query("DELETE FROM forums WHERE id = ?", $id);
}

function pruneforums(){
	global $db;

//delete forums not posted in in 30 days
	$result = $db->prepare_query("SELECT id FROM forums WHERE official='n' && time < ?", time() - 86400*30);

	while($line = $db->fetchrow($result))
		deleteForum($line['id']);

//delete threads not posted in in 6 times the default view length (default 2*6 weeks)
	$db->prepare_query("SELECT forumthreads.id, forumthreads.pollid FROM forums,forumthreads WHERE forums.id=forumthreads.forumid && forumthreads.announcement = 'n' && forums.sorttime > 0 && forumthreads.time <= (? - forums.sorttime*6*86400)", time());

	$threadids = array();
	$polls = array();
	$i=0;
	while($line = $db->fetchrow()){
		$threadids[floor($i/10)][] = $line['id'];
		$polls[] = $line['pollid'];
		$i++;
	}

	foreach($threadids as $ids){
		$db->prepare_query("DELETE FROM forumthreads WHERE id IN (?)", $ids);
		$db->prepare_query("DELETE FROM forumposts WHERE threadid IN (?)", $ids);
		$db->prepare_query("DELETE FROM forumpostsdel WHERE threadid IN (?)", $ids);
		$db->prepare_query("DELETE FROM forumread WHERE threadid IN (?)", $ids);
	}

//delete from deleted threads as well
	$db->prepare_query("SELECT forumthreadsdel.id, forumthreadsdel.pollid FROM forums,forumthreadsdel WHERE forums.id=forumthreadsdel.forumid && forumthreadsdel.announcement = 'n' && forums.sorttime > 0 && forumthreadsdel.time <= (? - forums.sorttime*7*86400)", time());

	$threadids = array();
	$i=0;
	while($line = $db->fetchrow()){
		$threadids[floor($i/10)][] = $line['id'];
		$polls[] = $line['pollid'];
		$i++;
	}

	foreach($threadids as $ids){
		$db->prepare_query("DELETE FROM forumthreadsdel WHERE id IN (?)", $ids);
		$db->prepare_query("DELETE FROM forumpostsdel WHERE threadid IN (?)", $ids);
	}

	$db->prepare_query("DELETE FROM polls WHERE id IN (?)", $polls);
	$db->prepare_query("DELETE FROM pollans WHERE pollid IN (?)", $polls);
	$db->prepare_query("DELETE FROM pollvotes WHERE pollid IN (?)", $polls);
}

function forumMuteUser($uid,$fid, $time, $reason, $forumname=""){
	global $db, $msgs, $userData,$mutelength;

	if($time==0)
		$unmutetime = 0;
	else
		$unmutetime = time() + $time;

	$db->prepare_query("INSERT IGNORE INTO forummute SET userid = ?, forumid = ?, mutetime = ?, unmutetime = ?", $uid, $fid, time(), $unmutetime);

	if($db->affectedrows() == 1){
		$id = $db->insertid();

		$db->prepare_query("INSERT INTO forummutereason SET id = ?, modid = ?, reason = ?", $id, $userData['userid'], $reason);

		if($forumname=="" && $fid != 0){
			$db->prepare_query("SELECT name FROM forums WHERE id = ?", $fid);
			$forumname = $db->fetchfield();
		}

		$msg = "Dear User,\n\nThis is to notify you that you have been banned ";
		if($fid == 0)	$msg .= "Globally";
		else			$msg .= "from '[url=forumthreads.php?fid=$fid]" . $forumname . "[/url]'";
		if($time > 0)	$msg .= " for";
		$msg .= " " . $mutelength[$time] . " with the reason '$reason'.\n\n";
		$msg .= "If you wish to dispute this action, feel free to reply.\n\n";
		$msg .= "Thanks";

		deliverMsg($uid,"Forum Ban",$msg);

		modLog('mute',$fid,0,$uid,$unmutetime);

		$msgs->addMsg("User Muted");
	}else{
		$msgs->addMsg("User already Muted");
	}
}

function forumUnmuteUser($uid, $fid){
	global $db, $msgs;

	$db->prepare_query("SELECT id, unmutetime FROM forummute WHERE userid = ? && forumid = ?", $uid, $fid);
	$line = $db->fetchrow();

	if(!$line)
		return;

	modLog('unmute',$fid,0,$uid,$line['unmutetime']);

	$db->prepare_query("DELETE FROM forummute WHERE id = ?", $line['id']);

	$msgs->addMsg("User UnMuted");
}

