<?

class forums {


	var $sorttimes;
	var $mutelength;

	var $forumranks = array(
				100 => "Newbie",
				500 => "Member",
				1000 => "Regular",
				2000 => "Veteran",
				5000 => "Addict",
				10000 => "Junkie",
				25000 => "Bleeds Nexopia",
				50000 => "Nexopian Faithful",
				100000 => "Great Nexopian",
				200000 => "Nexopian Elder"
			);

	var $reasons;

	var $db;

	function forums( & $db ) {
		$this->db = & $db;

		$this->sorttimes  = array(
				0 => 'All Topics',
				1 => '1 Day',
				3 => '3 Days',
				7 => '1 Week',
				14 => '2 Weeks',
				30 => '1 Month',
				60 => '2 Months',
				180 => '6 Months',
				365 => '1 Year'
			);

		$this->mutelength = array(
				0 => "Indefinitely",
				3600 => "1 hour",
				3*3600 => "3 hours",
				12*3600 => "12 hours",
				86400 => "1 day",
				2*86400 => "2 days",
				3*86400 => "3 days",
				7*86400 => "1 week",
				14*86400 => "2 weeks",
				30*86400 => "1 month",
				60*86400 => "2 months"
			);

		$this->reasons = array( //must be a subset of $abuselog->reasons, as they are auto-added to the abuselog
				ABUSE_REASON_NUDITY 	=> 'Nudity/Porn',
				ABUSE_REASON_RACISM 	=> 'Racism',
				ABUSE_REASON_VIOLENCE 	=> 'Gore/Violence',
				ABUSE_REASON_SPAMMING 	=> 'Spamming',
				ABUSE_REASON_FLAMING 	=> 'Harassment',
				ABUSE_REASON_OTHER 		=> 'Other'
					);
	}

	function forumrank($posts){
		foreach($this->forumranks as $postmax => $name)
			if($posts < $postmax)
				return $name;

		return "";
	}

	function forumsNumOnline($fid = 0){
		global $config, $cache;

		$online = $cache->get(array($fid, "forumsonline-$fid"));

		if(!$online){
			if($fid)
				$this->db->prepare_query("SELECT count(DISTINCT userid) FROM forumupdated WHERE forumid = # && time >= #", $fid, (time() - $config['friendAwayTime']) );
			else
				$this->db->prepare_query("SELECT count(DISTINCT userid) FROM forumupdated WHERE time >= #", (time() - $config['friendAwayTime']) );

			$online = $this->db->fetchfield();

			$cache->put(array($fid, "forumsonline-$fid"), $online, 60);
		}
		return $online;
	}

	function subscribe($tid){
		global $userData;
		$this->db->prepare_query("UPDATE forumread SET subscribe='y' WHERE userid = # && threadid = #", $userData['userid'], $tid);
	}

	function unsubscribe($tid){
		global $userData;
		$this->db->prepare_query("UPDATE forumread SET subscribe='n' WHERE userid = # && threadid = #", $userData['userid'], $tid);
	}

	function modLog($action, $forumid, $threadid, $var1 = 0, $var2 = 0, $userid = 0){
		global $userData;
		if($userid == 0)
			$userid = $userData['userid'];

		$this->db->prepare_query("INSERT INTO forummodlog SET userid = #, action = ?, forumid = #, threadid = #, var1 = #, var2 = #, time = #",
			$userid, $action, $forumid, $threadid, $var1, $var2, time());

		$this->db->prepare_query("UPDATE forummods SET activetime = # WHERE userid = # && forumid IN (0,#)", time(), $userid, $forumid);
	}


	function getForumPerms($fid){
		global $userData, $mods, $cache, $config;

		static $forums = array();

		if(isset($forums[$fid]))
			return $forums[$fid];


		$perms = array( 'view' => false,
						'post' => false,
						'postglobal' => false,
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
						'flag' => false,
						'modlog' => false,
						'editmods' => false,
						'admin' => false,
						'invited' => false,
						'cols' => array()
						);

		$forumdata = $cache->get(array($fid, "forumdata-$fid"));

		if(!$forumdata){
			$this->db->prepare_query("SELECT public, ownerid, mute, edit, parent, official, name, autolock, public, sorttime, rules FROM forums WHERE id = #", $fid);

			if(!$this->db->numrows())
				die("Bad Forum id");

			$forumdata = $this->db->fetchrow();

			$cache->put(array($fid, "forumdata-$fid"), $forumdata, 3600);
		}

		if($forumdata['parent']==0 && $forumdata['official']=='y')
			die("Bad Forum id");

		$perms['cols'] = $forumdata;

		if($forumdata['public']=='y')
			$perms['view']=true;

		if($userData['loggedIn']){
			$isAdmin = $mods->isAdmin($userData['userid'],"forums");

			if($isAdmin || $userData['userid']==$forumdata['ownerid']){
				$perms['view']=true;
				$perms['post']=true;
				$perms['postglobal']=true;
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
				$perms['flag']=true;
				$perms['modlog']=true;
				$perms['editmods']=true;
				$perms['admin']=true;

				if($userData['userid'] != $forumdata['ownerid'] && $forumdata['official']=='n'){

					$invited = $cache->get(array($userData['userid'], "foruminvite-$userData[userid]-$fid"));

					if($invited === false){
						$this->db->prepare_query("SELECT userid FROM foruminvite WHERE userid = # && forumid = #", $userData['userid'], $fid);
						$invited = $this->db->numrows();

						$cache->put(array($userData['userid'], "foruminvite-$userData[userid]-$fid"), $invited, 10800);
					}

					$perms['invited'] = $invited;
				}
				if($isAdmin){
					$perms['globalmute'] = true;
					$perms['editallposts'] = true;
				}
			}else{

				$modpowers = $cache->get(array($userData['userid'], "forummods-$userData[userid]-$fid"));

				if($modpowers === false){
					$this->db->prepare_query("SELECT * FROM forummods WHERE userid = # && forumid IN (0, #)", $userData['userid'], $fid);

					$modpowers = array();
					while($line = $this->db->fetchrow())
						$modpowers[$line['forumid']] = $line;

					$cache->put(array($userData['userid'], "forummods-$userData[userid]-$fid"), $modpowers, 10800);
				}

				if(count($modpowers)){
					$modperms = array();

					foreach($modpowers as $line){
						foreach($line as $n => $v){
							if(!isset($modperms[$n]))
								$modperms[$n] = $v;
							elseif($v == 'y') // useful for adding extra permissions by forum
								$modperms[$n] = 'y';
						}
					}

					if(isset($modpowers[0])){ //are you global?
						$perms['globalmute'] = ($modpowers[0]['mute'] == 'y');
					}else{ //CANNOT edit everyones posts unless global
						$modperms['editposts'] = 'n';
					}

					$perms['view'] |= 			($modperms['view']			== 'y');
					$perms['post'] |= 			($modperms['post']			== 'y');
					$perms['postglobal'] |=		($modperms['post']			== 'y' && isset($modpowers[0]));
					$perms['postlocked'] |= 	($modperms['postlocked']	== 'y');
					$perms['editownposts'] |= 	($modperms['editownposts']	== 'y');
					$perms['editallposts'] |= 	($modperms['editposts']		== 'y');
					$perms['move'] |= 			($modperms['move']			== 'y');
					$perms['deleteposts'] |= 	($modperms['deleteposts']	== 'y');
					$perms['deletethreads'] |= 	($modperms['deletethreads']	== 'y');
					$perms['lock'] |= 			($modperms['lock']			== 'y');
					$perms['stick'] |= 			($modperms['stick']			== 'y');
					$perms['mute'] |= 			($modperms['mute']			== 'y');
					$perms['invite'] |= 		($modperms['invite']		== 'y');
					$perms['announce'] |= 		($modperms['announce']		== 'y');
					$perms['flag'] |=			($modperms['flag']			== 'y');
					$perms['modlog'] |= 		($modperms['modlog']		== 'y');
					$perms['editmods'] |= 		($modperms['editmods']		== 'y');
				}else{
					$modperms = false;
				}

				if($forumdata['official']=='n'){

					$invited = $cache->get(array($userData['userid'], "foruminvite-$userData[userid]-$fid"));

					if($invited === false){
						$this->db->prepare_query("SELECT userid FROM foruminvite WHERE userid = # && forumid = #", $userData['userid'], $fid);
						$invited = $this->db->numrows();

						$cache->put(array($userData['userid'], "foruminvite-$userData[userid]-$fid"), $invited, 10800);
					}

					$perms['invited'] = $invited;

					if($forumdata['public']=='n')
						$perms['view'] |= $invited; // |= so globals can see everything
				}else
					$perms['view'] = true;

				if((!$modperms || $perms['postglobal'] == 'n') && $forumdata['mute']=='n'){

					$forummutes = array();
					$forummutes[$fid] = $cache->get(array($userData['userid'], "forummutes-$userData[userid]-$fid"));
					$forummutes[0] = $cache->get(array($userData['userid'], "forummutes-$userData[userid]-0"));

					if($forummutes[0] === false || $forummutes[$fid] === false){
						$forummutes = array(0 => 1, $fid => 1);

						$this->db->prepare_query("SELECT forumid, unmutetime FROM forummute WHERE userid = # && forumid IN (0, #)", $userData['userid'], $fid);

						while($line = $this->db->fetchrow())
							$forummutes[$line['forumid']] = $line['unmutetime'];

						$cache->put(array($userData['userid'], "forummutes-$userData[userid]-$fid"), $forummutes[$fid], 10800);
						$cache->put(array($userData['userid'], "forummutes-$userData[userid]-0"), $forummutes[0], 10800);
					}

					$maxunmutetime = 1;
					$time = time();

					foreach($forummutes as $forumid => $unmutetime){
						if($unmutetime == 1){ //not muted
							continue;
						}elseif($unmutetime == 0){ //muted indef
							$maxunmutetime = 0;
							break;
						}elseif($unmutetime < $time){
							$this->db->prepare_query("DELETE FROM forummute WHERE userid = # && forumid = #", $userData['userid'], $forumid);

							$this->modLog('unmute', $forumid, 0, $userData['userid'], $unmutetime, 0);

							$cache->remove(array($userData['userid'], "forummutes-$userData[userid]-$forumid"));
							continue;
						}
						if($unmutetime > $maxunmutetime)
							$maxunmutetime = $unmutetime;
					}

					$perms['post'] = ( $maxunmutetime > 0 && $maxunmutetime < $time );

				}

				$perms['editownposts'] |= ($forumdata['edit']=='y');
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

	function deleteThread($tid){
		global $msgs, $db, $userData;

		$this->db->prepare_query("SELECT forumid, authorid, lastauthorid, posts FROM forumthreads WHERE id = #", $tid);
		$thread = $this->db->fetchrow();

		$perms = $this->getForumPerms($thread['forumid']);

		if($perms['deletethreads']){
			$this->modLog('deletethread',$thread['forumid'],$tid,$thread['authorid'], $thread['lastauthorid']);

			$this->db->prepare_query("UPDATE forums SET posts = posts - # - 1, threads=threads-1 WHERE id = #", $thread['posts'], $thread['forumid']);

//*
//lower users post count
			$this->db->prepare_query("SELECT authorid, count(*) as count FROM forumposts WHERE threadid = # GROUP BY authorid", $tid);

			$counts = array();
			while($line = $this->db->fetchrow())
				if($line['authorid'])
					$counts[$line['count']][] = $line['authorid'];

			foreach($counts as $count => $authors)
				$db->prepare_query("UPDATE users SET posts = posts - # WHERE userid IN (#)", $count, $authors);
//*/

			$this->db->prepare_query("INSERT INTO forumthreadsdel SELECT * FROM forumthreads WHERE id = #", $tid);
			$this->db->prepare_query("INSERT INTO forumpostsdel SELECT * FROM forumposts WHERE threadid = #", $tid);

			$this->db->prepare_query("DELETE FROM forumthreads WHERE id = #", $tid);
			$this->db->prepare_query("DELETE FROM forumposts WHERE threadid = #", $tid);
			$this->db->prepare_query("DELETE FROM forumread WHERE threadid = #", $tid);

			$msgs->addMsg("Thread deleted");
			return true;
		}
		$msgs->addMsg("You don't have permission to mod this forum");
		return false;
	}

	function deletePost($pid){
		global $msgs,$db, $userData, $cache;

		$this->db->prepare_query("SELECT forumid,threadid,forumposts.authorid FROM forumthreads,forumposts WHERE forumposts.id = # && forumthreads.id=forumposts.threadid", $pid);
		$post = $this->db->fetchrow();

		$perms = $this->getForumPerms($post['forumid']);

		if($perms['deleteposts']){
			$this->modLog('deletepost',$post['forumid'],$post['threadid'],$pid,$post['authorid']);

			$this->db->prepare_query("INSERT INTO forumpostsdel SELECT * FROM forumposts WHERE id = #", $pid);

			$this->db->prepare_query("DELETE FROM forumposts WHERE id = #", $pid);

			$this->db->prepare_query("SELECT time, author, authorid FROM forumposts WHERE threadid = # ORDER BY time DESC LIMIT 1", $post['threadid']);
			$line = $this->db->fetchrow();

			if($line)
				$this->db->prepare_query("UPDATE forumthreads SET posts = posts - 1, time = #,lastauthor = ?, lastauthorid = # WHERE id = #", $line['time'], $line['author'], $line['authorid'], $post['threadid']);

			$this->db->prepare_query("UPDATE forums SET posts=posts-1 WHERE id = #", $post['forumid']);

			if($post['authorid'])
				$db->prepare_query("UPDATE users SET posts = posts -1 WHERE userid = #", $post['authorid']);

			$cache->remove(array($post['threadid'], "forumthread-$post[threadid]"));

			$msgs->addMsg("Post deleted");
			return true;
		}
		$msgs->addMsg("You don't have permission to mod this forum");
		return false;
	}

	function lockThread($tid){
		global $userData,$msgs, $cache;

		$this->db->prepare_query("SELECT forumid, authorid, lastauthorid FROM forumthreads WHERE id = #", $tid);
		$thread = $this->db->fetchrow();

		$perms = $this->getForumPerms($thread['forumid']);

		if($perms['lock']){
			$this->db->prepare_query("UPDATE forumthreads SET locked='y' WHERE id = #", $tid);

			$this->modLog('lock',$thread['forumid'],$tid,$thread['authorid'], $thread['lastauthorid']);

			$cache->remove(array($tid, "forumthread-$tid"));

			$msgs->addMsg("Thread locked");
			return true;
		}
		$msgs->addMsg("You don't have permission to lock threads in this forum");
		return false;
	}

	function unlockThread($tid){
		global $userData,$msgs, $cache;

		$this->db->prepare_query("SELECT forumid, authorid, lastauthorid FROM forumthreads WHERE id = #", $tid);
		$thread = $this->db->fetchrow();

		$perms = $this->getForumPerms($thread['forumid']);

		if($perms['lock']){
			$this->db->prepare_query("UPDATE forumthreads SET locked='n' WHERE id = #", $tid);

			$this->modLog('unlock',$thread['forumid'],$tid,$thread['authorid'], $thread['lastauthorid']);

			$cache->remove(array($tid, "forumthread-$tid"));

			$msgs->addMsg("Thread unlocked");
			return true;
		}
		$msgs->addMsg("You don't have permission to lock threads in this forum");
		return false;
	}

	function stickThread($tid){
		global $userData,$msgs, $cache;

		$this->db->prepare_query("SELECT forumid, authorid, lastauthorid FROM forumthreads WHERE id = #", $tid);
		$thread = $this->db->fetchrow();

		$perms = $this->getForumPerms($thread['forumid']);

		if($perms['stick']){
			$this->db->prepare_query("UPDATE forumthreads SET sticky='y' WHERE id = #", $tid);

			$this->modLog('stick',$thread['forumid'],$tid,$thread['authorid'], $thread['lastauthorid']);

			$cache->remove(array($tid, "forumthread-$tid"));

			$msgs->addMsg("Thread made sticky");
			return true;
		}
		$msgs->addMsg("You don't have permission to stick threads in this forum");
		return false;
	}

	function unstickThread($tid){
		global $userData,$msgs, $cache;

		$this->db->prepare_query("SELECT forumid, authorid, lastauthorid FROM forumthreads WHERE id = #", $tid);
		$thread = $this->db->fetchrow();

		$perms = $this->getForumPerms($thread['forumid']);

		if($perms['stick']){
			$this->db->prepare_query("UPDATE forumthreads SET sticky='n' WHERE id = #", $tid);

			$this->modLog('unstick',$thread['forumid'],$tid,$thread['authorid'], $thread['lastauthorid']);

			$cache->remove(array($tid, "forumthread-$tid"));

			$msgs->addMsg("Thread made unsticky");
			return true;
		}
		$msgs->addMsg("You don't have permission to stick threads in this forum");
		return false;
	}

	function announceThread($tid){
		global $userData,$msgs, $cache;

		$this->db->prepare_query("SELECT forumid, authorid, lastauthorid FROM forumthreads WHERE id = #", $tid);
		$thread = $this->db->fetchrow();

		$perms = $this->getForumPerms($thread['forumid']);

		if($perms['announce']){
			$this->db->prepare_query("UPDATE forumthreads SET announcement='y' WHERE id = #", $tid);

			$this->modLog('announce',$thread['forumid'],$tid,$thread['authorid'], $thread['lastauthorid']);

			$cache->remove(array($tid, "forumthread-$tid"));

			$msgs->addMsg("Thread announced");
			return true;
		}
		$msgs->addMsg("You don't have permission to announce threads in this forum");
		return false;
	}

	function unannounceThread($tid){
		global $userData,$msgs, $cache;

		$this->db->prepare_query("SELECT forumid, authorid, lastauthorid FROM forumthreads WHERE id = #", $tid);
		$thread = $this->db->fetchrow();

		$perms = $this->getForumPerms($thread['forumid']);

		if($perms['announce']){
			$this->db->prepare_query("UPDATE forumthreads SET announcement='n' WHERE id = #", $tid);

			$this->modLog('unannounce',$thread['forumid'],$tid,$thread['authorid'], $thread['lastauthorid']);

			$cache->remove(array($tid, "forumthread-$tid"));

			$msgs->addMsg("Thread unannounced");
			return true;
		}
		$msgs->addMsg("You don't have permission to announce threads in this forum");
		return false;
	}

	function flagThread($tid){
		global $userData,$msgs, $cache;

		$this->db->prepare_query("SELECT forumid, authorid, lastauthorid FROM forumthreads WHERE id = #", $tid);
		$thread = $this->db->fetchrow();

		$perms = $this->getForumPerms($thread['forumid']);

		if($perms['flag']){
			$this->db->prepare_query("UPDATE forumthreads SET flag='y' WHERE id = #", $tid);

			$this->modLog('flag',$thread['forumid'],$tid,$thread['authorid'], $thread['lastauthorid']);

			$cache->remove(array($tid, "forumthread-$tid"));

			$msgs->addMsg("Thread flagged");
			return true;
		}
		$msgs->addMsg("You don't have permission to flag threads in this forum");
		return false;
	}

	function unflagThread($tid){
		global $userData,$msgs, $cache;

		$this->db->prepare_query("SELECT forumid, authorid, lastauthorid FROM forumthreads WHERE id = #", $tid);
		$thread = $this->db->fetchrow();

		$perms = $this->getForumPerms($thread['forumid']);

		if($perms['flag']){
			$this->db->prepare_query("UPDATE forumthreads SET flag='n' WHERE id = #", $tid);

			$this->modLog('unflag',$thread['forumid'],$tid,$thread['authorid'], $thread['lastauthorid']);

			$cache->remove(array($tid, "forumthread-$tid"));

			$msgs->addMsg("Thread unflagged");
			return true;
		}
		$msgs->addMsg("You don't have permission to flag threads in this forum");
		return false;
	}

	function moveThread($tid,$dest){
		global $userData, $msgs, $cache;

		$this->db->prepare_query("SELECT * FROM forumthreads WHERE id = #", $tid);
		$thread = $this->db->fetchrow();

		if($thread['moved'])
			return;

		if($thread['forumid']==$dest)
			return;

		$perms = $this->getForumPerms($thread['forumid']);

		if(!$perms['move'])
			return false;

		$this->db->prepare_query("SELECT official FROM forums WHERE id = #", $dest);

		if(!$this->db->numrows() || $this->db->fetchfield() == 'n')
			return false;

		$this->modLog('move',$thread['forumid'],$tid,$thread['forumid'],$dest);
		$this->modLog('move',$dest,$tid,$thread['forumid'],$dest);

		$this->db->prepare_query("UPDATE forums SET posts = posts - # - 1, threads=threads-1 WHERE id = #", $thread['posts'], $thread['forumid']);

		$this->db->prepare_query("UPDATE forums SET posts = posts + # + 1, threads=threads+1, time = GREATEST(time, #) WHERE id = #", $thread['posts'], $thread['time'], $dest);

		$this->db->prepare_query("INSERT INTO forumthreads (forumid, moved, announcement, title, authorid, author, reads, posts, `time`, lastauthorid, lastauthor, sticky, locked, pollid) " .
								"SELECT forumid, #, 'n', title, authorid, author, reads, posts, time, lastauthorid, lastauthor, 'n', locked, pollid FROM forumthreads WHERE id = #", $tid, $tid);

		$this->db->prepare_query("UPDATE forumthreads SET forumid = # WHERE id = #", $dest, $tid);

		$cache->remove(array($tid, "forumthread-$tid"));

		$msgs->addMsg("Thread moved");
		return true;
	}

	function deleteForum($ids){
		global $cache;

		$this->db->prepare_query("SELECT id FROM forumthreads WHERE forumid IN (#)", $ids);

		$threads = array();
		while($line = $this->db->fetchrow())
			$threads[] = $line['id'];

		if(count($threads))
			$this->db->prepare_query("DELETE FROM forumposts WHERE threadid IN (#)", $threads);
		$this->db->prepare_query("DELETE FROM forumthreads WHERE forumid IN (#)", $ids);


		$this->db->prepare_query("SELECT id FROM forumthreadsdel WHERE forumid IN (#)", $ids);

		$threads = array();
		while($line = $this->db->fetchrow())
			$threads[] = $line['id'];

		if(count($threads))
			$this->db->prepare_query("DELETE FROM forumpostsdel WHERE threadid IN (#)", $threads);
		$this->db->prepare_query("DELETE FROM forumthreadsdel WHERE forumid IN (#)", $ids);


		$this->db->prepare_query("DELETE FROM forums WHERE id IN (#)", $ids);

		foreach($ids as $id)
			$cache->remove(array($id, "forumdata-$id"));
	}

	function pruneforums(){
		global $polls;

	//delete forums not posted in in 30 days
		$this->db->prepare_query("SELECT id FROM forums WHERE official='n' && time < #", time() - 86400*30);

		if($this->db->numrows() == 0)
			return;

		$forums = array();
		while($line = $this->db->fetchrow())
			$forums = $line['id'];
		$this->deleteForum($forums);

	//delete threads not posted in in 6 times the default view length (default 2*6 weeks)
		$this->db->prepare_query("SELECT forumthreads.id, forumthreads.pollid FROM forums,forumthreads WHERE forums.id=forumthreads.forumid && forumthreads.announcement = 'n' && forums.sorttime > 0 && forumthreads.time <= (# - forums.sorttime*6*86400)", time());

		$threadids = array();
		$pollids = array();
		$i=0;
		while($line = $this->db->fetchrow()){
			$threadids[floor($i/10)][] = $line['id'];
			$pollids[] = $line['pollid'];
			$i++;
		}

		foreach($threadids as $ids){
			$this->db->prepare_query("DELETE FROM forumthreads WHERE id IN (#)", $ids);
			$this->db->prepare_query("DELETE FROM forumposts WHERE threadid IN (#)", $ids);
			$this->db->prepare_query("DELETE FROM forumpostsdel WHERE threadid IN (#)", $ids);
			$this->db->prepare_query("DELETE FROM forumread WHERE threadid IN (#)", $ids);
		}

	//delete from deleted threads as well
		$this->db->prepare_query("SELECT forumthreadsdel.id, forumthreadsdel.pollid FROM forums,forumthreadsdel WHERE forums.id=forumthreadsdel.forumid && forumthreadsdel.announcement = 'n' && forums.sorttime > 0 && forumthreadsdel.time <= (# - forums.sorttime*7*86400)", time());

		$threadids = array();
		$i=0;
		while($line = $this->db->fetchrow()){
			$threadids[floor($i/10)][] = $line['id'];
			$pollids[] = $line['pollid'];
			$i++;
		}

		foreach($threadids as $ids){
			$this->db->prepare_query("DELETE FROM forumthreadsdel WHERE id IN (#)", $ids);
			$this->db->prepare_query("DELETE FROM forumpostsdel WHERE threadid IN (#)", $ids);
		}

		$polls->deletePoll($pollids);
	}

	function forumMuteUser($uid,$fid, $time, $reasonid, $reason, $forumname = "", $moditem = true){
		global $msgs, $userData, $mods, $cache, $messaging, $abuselog;

		if($time==0)
			$unmutetime = 0;
		else
			$unmutetime = time() + $time;

		$this->db->prepare_query("INSERT IGNORE INTO forummute SET userid = #, forumid = #, mutetime = #, unmutetime = #, reasonid = #", $uid, $fid, time(), $unmutetime, $reasonid);

		if($this->db->affectedrows()){
			$id = $this->db->insertid();

			$this->db->prepare_query("INSERT INTO forummutereason SET id = #, modid = #, reason = ?", $id, $userData['userid'], $reason);

			if($forumname=="" && $fid != 0){
				$this->db->prepare_query("SELECT name, official FROM forums WHERE id = #", $fid);

				$forumname = $this->db->fetchfield();
			}

			$msg = "Dear User,\n\nThis is to notify you that you have been banned ";
			if($fid == 0)	$msg .= "Globally (from all forums)";
			else			$msg .= "from '[url=forumthreads.php?fid=$fid]" . $forumname . "[/url]'";
			if($time > 0)	$msg .= " for";
			$msg .= " " . $this->mutelength[$time] . " with the reason '$reason'.\n\n";
			$msg .= "If you wish to dispute this action, feel free to reply.\n\n";
			$msg .= "Thanks";

			$messaging->deliverMsg($uid,"Forum Ban",$msg, 0, false, false, false); //not ignorable

			$this->modLog('mute',$fid,0,$uid,$unmutetime);

			if($moditem){
				$mods->newItem(MOD_FORUMBAN, $id);

				$subject = $this->mutelength[$time];
				$subject.= ($time ? " ban " : " banned ");
				$subject.= ($fid ? "from $forumname" : "Globally");

				$message = "Forum: " . ($fid ? "[url=forumthreads.php?fid=$fid]$forumname" . "[/url]" : "Global") . "\n";
				$message.= "Reason: $reason";

				$abuselog->addAbuse($uid, ABUSE_ACTION_FORUM_BAN, $reasonid, $subject, $message);
			}

			$cache->remove(array($uid, "forummutes-$uid-$fid"));

			$msgs->addMsg("User Muted");
		}else{
			$msgs->addMsg("User already Muted");
		}
	}

	function forumUnmuteUser($uid, $fid){
		global $msgs, $cache;

		$this->db->prepare_query("SELECT unmutetime FROM forummute WHERE userid = # && forumid = #", $uid, $fid);
		$line = $this->db->fetchrow();

		if(!$line)
			return;

		$this->modLog('unmute',$fid,0,$uid,$line['unmutetime']);

		$this->db->prepare_query("DELETE FROM forummute WHERE userid = # && forumid = #", $uid, $fid);

		$cache->remove(array($uid, "forummutes-$uid-$fid"));

		$msgs->addMsg("User UnMuted");
	}

	function invite($uids, $fid){
		global $cache, $messaging;

		if(!is_array($uids))
			$uids = array($uids);


		$this->db->prepare_query("SELECT name FROM forums WHERE id = #", $fid);
		$forumname = $this->db->fetchfield();

		foreach($uids as $uid){
			$this->db->prepare_query("INSERT IGNORE INTO foruminvite SET userid = #, forumid = #", $uid, $fid);

			if($this->db->affectedrows() == 0)
				continue;

			$this->modLog('invite',$fid,0,$uid);

			$cache->put(array($uid, "foruminvite-$uid-$fid"), 1, 10800);

			$messaging->deliverMsg($uid, "Forum Invite", "You have been invited to join the forum [url=forumthreads.php?fid=$fid]$forumname" . "[/url]. Click [url=forumthreads.php?fid=$fid&action=withdraw&k=" . makeKey($fid, $uid) . "]here[/url] to withdraw from the forum.");
		}
	}

	function unInvite($uids, $fid){
		global $cache;

		$this->db->prepare_query("DELETE FROM foruminvite WHERE userid IN (#) && forumid = #", $uids, $fid);

		foreach($uids as $uid){
			$this->modLog('uninvite',$fid,0,$uid);
			$cache->put(array($uid, "foruminvite-$uid-$fid"), 0, 10800);
		}
	}
}

