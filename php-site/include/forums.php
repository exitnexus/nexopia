<?

class forums {
	public $sorttimes;
	public $mutelength;
	public $editlengths;

	public $forumranks = array(
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

	public $reasons;
	public $db;
	public $officialmods;

	function __construct( & $db ) {
		global $cache;

		$this->db = & $db;

		$this->sorttimes  = array(
				0 => 'All Threads',
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
				3600 => "1 hour",
				3*3600 => "3 hours",
				12*3600 => "12 hours",
				86400 => "1 day",
				2*86400 => "2 days",
				3*86400 => "3 days",
				5*86400 => "5 days",
				7*86400 => "1 week",
				14*86400 => "2 weeks",
				30*86400 => "1 month",
				60*86400 => "2 months",
				0 => "Indefinitely",
			);

		$this->editlengths = array(
				'y' => "Yes",
				'n' => "No",
				'5' => '5 Minutes',
				'15' => '15 Minutes',
				'60' => '1 Hour',
				);

		$this->reasons = array( //must be a subset of $abuselog->reasons, as they are auto-added to the abuselog
				ABUSE_REASON_NUDITY 	=> 'Nudity/Porn',
				ABUSE_REASON_RACISM 	=> 'Racism',
				ABUSE_REASON_VIOLENCE 	=> 'Gore/Violence',
				ABUSE_REASON_SPAMMING 	=> 'Spamming',
				ABUSE_REASON_FLAMING 	=> 'Harassment',
				ABUSE_REASON_OTHER 		=> 'Other'
					);

		$this->officialmods = $cache->hdget("forums-officialmods", 3600, array(&$this, 'getOfficialModsDump'));
	}

	function parsePost($nmsg){ //removeHTML should have been called first, hence nmsg
		$nmsg2 = parseHTML($nmsg);
		$nmsg3 = smilies($nmsg2);
		$nmsg3 = wrap($nmsg3);

		return $nmsg3;
	}

	function forumrank($posts){
		foreach($this->forumranks as $postmax => $name)
			if($posts < $postmax)
				return $name;

		return "";
	}

	function forumsNumOnline($fid = 0){
		global $config, $cache;

		$online = $cache->get("forumsonline-$fid");

		if(!$online){
			if($fid)
				$res = $this->db->prepare_query("SELECT count(DISTINCT userid) FROM forumupdated WHERE forumid = # && time >= #", $fid, (time() - $config['friendAwayTime']) );
			else
				$res = $this->db->prepare_query("SELECT count(DISTINCT userid) FROM forumupdated WHERE time >= #", (time() - $config['friendAwayTime']) );

			$online = $res->fetchfield();

			$cache->put("forumsonline-$fid", $online, 60);
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

	function getModPowers($userid, $fids)
	{
		global $cache;

		$modpowers = $cache->get_multi($fids, "forummodpowers-$userid-");
		$missing = array_diff($fids, array_keys($modpowers));
		if ($missing)
		{
			$res = $this->db->prepare_query("SELECT * FROM forummods WHERE userid = # && forumid IN (#)", $userid, $missing);

			while($line = $res->fetchrow())
			{
				$modpowers[$line['forumid']] = $line;
				$cache->put("forummodpowers-$userid-$line[forumid]", $line, 10800);
			}
		}
		// anything still missing is empty and should be set as such in the cache so we don't go back
		// to the database for missing forums, which would be likely given that most users are not mods.
		$missing = array_diff($fids, array_keys($modpowers));
		foreach ($missing as $fid)
		{
			$cache->put("forummodpowers-$userid-$fid", array(), 10800);
		}
		// now cull empty items from the modpowers list
		foreach ($modpowers as $fid => $powers)
		{
			if (!$powers)
				unset($modpowers[$fid]);
		}
		return $modpowers;
	}

	function getForumPerms($fid){
		global $userData, $mods, $cache, $config, $msgs;

		static $forums = array();

		if(isset($forums[$fid]))
			return $forums[$fid];

	//default all to off
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

	//fail if the forum doesn't exist
		$forumdata = $this->getForums($fid);
		if(!$forumdata)
			return $perms;

	//cache the forumdata
		$perms['cols'] = $forumdata;

	//everyone can see a public forum
		if($forumdata['public'] == 'y')
			$perms['view'] = true;

		if($userData['loggedIn']){
			$perms['post'] |= ($forumdata['mute'] == 'n');

			$isAdmin = $mods->isAdmin($userData['userid'],"forums");

			$modpowers = $this->getModPowers($userData['userid'], array(0, $fid));

		//admin and forum owner are special cases (which could be removed by adding mod entries)
			if($isAdmin || $userData['userid'] == $forumdata['ownerid']){
				$powers = array(
							'userid'        => $userData['userid'],
							'forumid'       => ($isAdmin ? 0 : $forumdata['ownerid']),
							'view'          => 'y',
							'post'          => 'y',
							'postlocked'    => 'y',
							'move'          => 'y',
							'editposts'     => 'y',
							'editownposts'  => 'y',
							'deleteposts'   => 'y',
							'deletethreads' => 'y',
							'lock'          => 'y',
							'stick'         => 'y',
							'mute'          => 'y',
							'invite'        => 'y',
							'announce'      => 'y',
							'flag'          => 'y',
							'modlog'        => 'y',
							'listmods'      => 'y',
							'editmods'      => 'y',
						);

				$perms['admin'] = true; //allowed to edit the forum
				$perms['invited'] |= ($userData['userid'] == $forumdata['ownerid']); //owners are automatically invited

				$modpowers[$powers['forumid']] = $powers;
			}

		//aggregate and normalize the powers
			if(count($modpowers)){
				$modperms = array();

			//powers can't be taken away, only added
				foreach($modpowers as $line){
					foreach($line as $n => $v){
						if(!isset($modperms[$n]))
							$modperms[$n] = $v;
						elseif($v == 'y') // useful for adding extra permissions by forum
							$modperms[$n] = 'y';
					}
				}

			//certain powers are special
				if(isset($modpowers[0])){ //are you global?
					$perms['globalmute'] = ($modpowers[0]['mute'] == 'y');
				}else{ //CANNOT edit everyones posts unless global
					$modperms['editposts'] = 'n';
				}

			//turn powers into permissions
				$perms['view']          |= ($modperms['view']          == 'y');
				$perms['post']          |= ($modperms['post']          == 'y');
				$perms['postglobal']    |= ($modperms['post']          == 'y' && isset($modpowers[0]));
				$perms['postlocked']    |= ($modperms['postlocked']    == 'y');
				$perms['editownposts']  |= ($modperms['editownposts']  == 'y');
				$perms['editallposts']  |= ($modperms['editposts']     == 'y');
				$perms['move']          |= ($modperms['move']          == 'y');
				$perms['deleteposts']   |= ($modperms['deleteposts']   == 'y');
				$perms['deletethreads'] |= ($modperms['deletethreads'] == 'y');
				$perms['lock']          |= ($modperms['lock']          == 'y');
				$perms['stick']         |= ($modperms['stick']         == 'y');
				$perms['mute']          |= ($modperms['mute']          == 'y');
				$perms['invite']        |= ($modperms['invite']        == 'y');
				$perms['announce']      |= ($modperms['announce']      == 'y');
				$perms['flag']          |= ($modperms['flag']          == 'y');
				$perms['modlog']        |= ($modperms['modlog']        == 'y');
				$perms['editmods']      |= ($modperms['editmods']      == 'y');
			}else{
				$modperms = false;
			}

		//figure out if they're invited
			$invited = false;
			if(!$perms['invited']){ //owners of forums are already invited
				$invited = $cache->get("foruminvite-$userData[userid]-$fid");

				if($invited === false){
					$res = $this->db->prepare_query("SELECT userid FROM foruminvite WHERE userid = # && forumid = #", $userData['userid'], $fid);
					$invited = ($res->fetchrow() != false);

					$cache->put("foruminvite-$userData[userid]-$fid", $invited, 10800);
				}

				$perms['invited'] = $invited;
			}

		//private forums need the user to be invited (or a global)
			if($forumdata['public'] == 'n')
				$perms['view'] |= $invited; // |= so globals can see everything

		//figure out if they're muted, unmute lazily
			if(!$perms['postglobal']){
				$forummutes = $cache->get_multi(array( 0, $fid), "forummutes-$userData[userid]-");

				if(count($forummutes) < 2){
					$forummutes = array(0 => 1, $fid => 1);

					$res = $this->db->prepare_query("SELECT forumid, unmutetime FROM forummute WHERE userid = # && forumid IN (0, #)", $userData['userid'], $fid);

					while($line = $res->fetchrow())
						$forummutes[$line['forumid']] = $line['unmutetime'];

					$cache->put("forummutes-$userData[userid]-$fid", $forummutes[$fid], 10800);
					$cache->put("forummutes-$userData[userid]-0", $forummutes[0], 10800);
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
						$this->db->prepare_query("DELETE forummute, forummutereason FROM forummute, forummutereason WHERE forummute.id = forummutereason.id && userid = # && forumid = #", $userData['userid'], $forumid);

						$this->modLog('unmute', $forumid, 0, $userData['userid'], $unmutetime, 0);

						$cache->remove("forummutes-$userData[userid]-$forumid");
						continue;
					}
					if($unmutetime > $maxunmutetime)
						$maxunmutetime = $unmutetime;
				}

				$perms['post'] &= ( $maxunmutetime > 0 && $maxunmutetime < $time );
			}

		//set some permissions based on the forum settings
			$perms['editownposts'] |= ($forumdata['edit']=='y');

			if(!$perms['editownposts'] && $forumdata['edit'] != 'y' && $forumdata['edit'] != 'n') //time limit
				$perms['editownposts'] = $forumdata['edit']*60;

			if($forumdata['official']=='n' || $forumdata['public']=='n')
				$perms['move']=false;

		//angus' proposed 'solution,' require that a day have passed between
		//account creation and first post.
			$oldaccount = ($userData['jointime'] < (time() - 24*60*60));
			if(!$oldaccount)
				$msgs->addMsg("You cannot post within 24 hours of creating a new account.");
			$perms['post'] &= $oldaccount;

		//can't edit, etc if you can't post
			if(!$perms['post']){
				$perms['postlocked'] = false;
				$perms['edit'] = false;
				$perms['editownposts'] = false;
			}
		}

	//cache it locally
		$forums[$fid] = $perms;

		return $perms;
	}

	function deleteThread($tid){
		global $msgs, $usersdb, $cache, $userData;

		$res = $this->db->prepare_query("SELECT forumid, authorid, lastauthorid, posts FROM forumthreads WHERE id = #", $tid);
		$thread = $res->fetchrow();

		$perms = $this->getForumPerms($thread['forumid']);

		if($perms['deletethreads']){
			$this->modLog('deletethread',$thread['forumid'],$tid,$thread['authorid'], $thread['lastauthorid']);

			$this->db->prepare_query("UPDATE forums SET posts = IF(posts > #, posts - # - 1, 0), threads=threads-1 WHERE id = #", $thread['posts'], $thread['posts'], $thread['forumid']);

//*
//lower users post count
			$res = $this->db->prepare_query("SELECT authorid, count(*) as count FROM forumposts WHERE threadid = # GROUP BY authorid", $tid);

			$counts = array();
			while($line = $res->fetchrow()){
				if($line['authorid']){
					$counts[$line['count']][] = $line['authorid'];
					$cache->decr("forumuserposts-$line[authorid]", $line['count']);
				}
			}

			foreach($counts as $count => $authors)
				$usersdb->prepare_query("UPDATE users SET posts = IF(posts <= #, 0, posts - #) WHERE userid IN (%)", $count, $count, $authors);
//*/

			$this->db->prepare_query("INSERT INTO forumthreadsdel SELECT * FROM forumthreads WHERE id = #", $tid);
			$this->db->prepare_query("INSERT INTO forumpostsdel SELECT * FROM forumposts WHERE threadid = #", $tid);

			$this->db->prepare_query("DELETE FROM forumthreads WHERE id = #", $tid);
			$this->db->prepare_query("DELETE FROM forumposts WHERE threadid = #", $tid);
			$this->db->prepare_query("DELETE FROM forumread WHERE threadid = #", $tid);
			
			$cache->remove("forumthread-$tid");

			$msgs->addMsg("Thread deleted");
			return true;
		}
		$msgs->addMsg("You don't have permission to mod this forum");
		return false;
	}

	function deletePost($pids, $tid, $fid){
		global $msgs, $usersdb, $cache, $userData;

		$res = $this->db->prepare_query("SELECT id, authorid FROM forumposts WHERE id IN (#) && threadid = #", $pids, $tid);
		$posts = $res->fetchfields('id');

		if(!$posts)
			return false;

		$perms = $this->getForumPerms($fid);

		if(!$perms['deleteposts']){
			$msgs->addMsg("You don't have permission to mod this forum");
			return false;
		}

		$pids = array_keys($posts);

		foreach($posts as $pid => $authorid)
			$this->modLog('deletepost', $fid, $tid, $pid, $authorid);

		$this->db->prepare_query("INSERT INTO forumpostsdel SELECT * FROM forumposts WHERE id IN (#)", $pids);

		$this->db->prepare_query("DELETE FROM forumposts WHERE id IN (#)", $pids);

		$res = $this->db->prepare_query("SELECT time, authorid FROM forumposts WHERE threadid = # ORDER BY time DESC LIMIT 1", $tid);
		$line = $res->fetchrow();

		if($line)
			$this->db->prepare_query("UPDATE forumthreads SET posts = IF(posts <= #, 0, posts - #), time = #, lastauthorid = # WHERE id = #", count($pids), count($pids), $line['time'], $line['authorid'], $tid);

		$this->db->prepare_query("UPDATE forums SET posts = IF(posts <= #, 0, posts - #) WHERE id = #", count($pids), count($pids), $fid);

		$authors = array();
		foreach($posts as $pid => $uid){
			if(!isset($authors[$uid]))
				$authors[$uid] = 0;
			$authors[$uid]++;
		}

		if($authors){
			foreach($authors as $uid => $num){
				$usersdb->prepare_query("UPDATE users SET posts = IF(posts <= #, 0, posts - #) WHERE userid = %", $num, $num, $uid);
				$cache->decr("forumuserposts-$uid", $num);
			}
		}

		$cache->remove("forumthread-$tid");

		$msgs->addMsg("Post deleted");
		return true;
	}

	function lockThread($tid){
		global $msgs, $cache;

		$res = $this->db->prepare_query("SELECT forumid, authorid, lastauthorid FROM forumthreads WHERE id = #", $tid);
		$thread = $res->fetchrow();

		$perms = $this->getForumPerms($thread['forumid']);

		if($perms['lock']){
			$this->db->prepare_query("UPDATE forumthreads SET locked='y' WHERE id = #", $tid);

			$this->modLog('lock',$thread['forumid'],$tid,$thread['authorid'], $thread['lastauthorid']);

			$cache->remove("forumthread-$tid");

			$msgs->addMsg("Thread locked");
			return true;
		}
		$msgs->addMsg("You don't have permission to lock threads in this forum");
		return false;
	}

	function unlockThread($tid){
		global $msgs, $cache;

		$res = $this->db->prepare_query("SELECT forumid, authorid, lastauthorid FROM forumthreads WHERE id = #", $tid);
		$thread = $res->fetchrow();

		$perms = $this->getForumPerms($thread['forumid']);

		if($perms['lock']){
			$this->db->prepare_query("UPDATE forumthreads SET locked='n' WHERE id = #", $tid);

			$this->modLog('unlock',$thread['forumid'],$tid,$thread['authorid'], $thread['lastauthorid']);

			$cache->remove("forumthread-$tid");

			$msgs->addMsg("Thread unlocked");
			return true;
		}
		$msgs->addMsg("You don't have permission to lock threads in this forum");
		return false;
	}

	function stickThread($tid){
		global $msgs, $cache;

		$res = $this->db->prepare_query("SELECT forumid, authorid, lastauthorid FROM forumthreads WHERE id = #", $tid);
		$thread = $res->fetchrow();

		$perms = $this->getForumPerms($thread['forumid']);

		if($perms['stick']){
			$this->db->prepare_query("UPDATE forumthreads SET sticky='y' WHERE id = #", $tid);

			$this->modLog('stick',$thread['forumid'],$tid,$thread['authorid'], $thread['lastauthorid']);

			$cache->remove("forumthread-$tid");

			$msgs->addMsg("Thread made sticky");
			return true;
		}
		$msgs->addMsg("You don't have permission to stick threads in this forum");
		return false;
	}

	function unstickThread($tid){
		global $msgs, $cache;

		$res = $this->db->prepare_query("SELECT forumid, authorid, lastauthorid FROM forumthreads WHERE id = #", $tid);
		$thread = $res->fetchrow();

		$perms = $this->getForumPerms($thread['forumid']);

		if($perms['stick']){
			$this->db->prepare_query("UPDATE forumthreads SET sticky='n' WHERE id = #", $tid);

			$this->modLog('unstick',$thread['forumid'],$tid,$thread['authorid'], $thread['lastauthorid']);

			$cache->remove("forumthread-$tid");

			$msgs->addMsg("Thread made unsticky");
			return true;
		}
		$msgs->addMsg("You don't have permission to stick threads in this forum");
		return false;
	}

	function announceThread($tid){
		global $msgs, $cache;

		$res = $this->db->prepare_query("SELECT forumid, authorid, lastauthorid FROM forumthreads WHERE id = #", $tid);
		$thread = $res->fetchrow();

		$perms = $this->getForumPerms($thread['forumid']);

		if($perms['announce']){
			$this->db->prepare_query("UPDATE forumthreads SET announcement='y' WHERE id = #", $tid);

			$this->modLog('announce',$thread['forumid'],$tid,$thread['authorid'], $thread['lastauthorid']);

			$cache->remove("forumthread-$tid");

			$msgs->addMsg("Thread announced");
			return true;
		}
		$msgs->addMsg("You don't have permission to announce threads in this forum");
		return false;
	}

	function unannounceThread($tid){
		global $msgs, $cache;

		$res = $this->db->prepare_query("SELECT forumid, authorid, lastauthorid FROM forumthreads WHERE id = #", $tid);
		$thread = $res->fetchrow();

		$perms = $this->getForumPerms($thread['forumid']);

		if($perms['announce']){
			$this->db->prepare_query("UPDATE forumthreads SET announcement='n' WHERE id = #", $tid);

			$this->modLog('unannounce',$thread['forumid'],$tid,$thread['authorid'], $thread['lastauthorid']);

			$cache->remove("forumthread-$tid");

			$msgs->addMsg("Thread unannounced");
			return true;
		}
		$msgs->addMsg("You don't have permission to announce threads in this forum");
		return false;
	}

	function flagThread($tid){
		global $msgs, $cache;

		$res = $this->db->prepare_query("SELECT forumid, authorid, lastauthorid FROM forumthreads WHERE id = #", $tid);
		$thread = $res->fetchrow();

		$this->db->prepare_query("UPDATE forumthreads SET flag='y' WHERE id = #", $tid);

		$this->modLog('flag',$thread['forumid'],$tid,$thread['authorid'], $thread['lastauthorid']);

		$cache->remove("forumthread-$tid");

		$msgs->addMsg("Thread flagged");
		return true;
	}

	function unflagThread($tid){
		global $msgs, $cache;

		$res = $this->db->prepare_query("SELECT forumid, authorid, lastauthorid FROM forumthreads WHERE id = #", $tid);
		$thread = $res->fetchrow();

		$perms = $this->getForumPerms($thread['forumid']);

		if($perms['flag']){
			$this->db->prepare_query("UPDATE forumthreads SET flag='n' WHERE id = #", $tid);

			$this->modLog('unflag',$thread['forumid'],$tid,$thread['authorid'], $thread['lastauthorid']);

			$cache->remove("forumthread-$tid");

			$msgs->addMsg("Thread unflagged");
			return true;
		}
		$msgs->addMsg("You don't have permission to flag threads in this forum");
		return false;
	}

	function moveThread($tid,$dest){
		global $msgs, $cache;

		$res = $this->db->prepare_query("SELECT * FROM forumthreads WHERE id = #", $tid);
		$thread = $res->fetchrow();

		if($thread['moved'])
			return;

		if($thread['forumid']==$dest)
			return;

		$perms = $this->getForumPerms($thread['forumid']);

		if(!$perms['move'])
			return false;

		$res = $this->db->prepare_query("SELECT official FROM forums WHERE id = #", $dest);
		$forum = $res->fetchrow();

		if(!$forum || $forum['official'] == 'n')
			return false;

		$this->modLog('move',$thread['forumid'],$tid,$thread['forumid'],$dest);
		$this->modLog('move',$dest,$tid,$thread['forumid'],$dest);

		$this->db->prepare_query("UPDATE forums SET posts = IF(posts > #, posts - # - 1, 0), threads=threads-1 WHERE id = #", $thread['posts'], $thread['posts'], $thread['forumid']);

		$this->db->prepare_query("UPDATE forums SET posts = posts + # + 1, threads=threads+1, time = GREATEST(time, #) WHERE id = #", $thread['posts'], $thread['time'], $dest);

		$this->db->prepare_query("INSERT INTO forumthreads (forumid, moved, announcement, title, authorid, `reads`, posts, `time`, lastauthorid, sticky, locked, pollid) " .
								"SELECT forumid, #, 'n', title, authorid, `reads`, posts, time, lastauthorid, 'n', locked, pollid FROM forumthreads WHERE id = #", $tid, $tid);

		$this->db->prepare_query("UPDATE forumthreads SET forumid = # WHERE id = #", $dest, $tid);

		$cache->remove("forumthread-$tid");

		$msgs->addMsg("Thread moved");
		return true;
	}

	function invalidateForums($ids)
	{
		global $cache;
		if (!is_array($ids))
			$ids = array($ids);

		foreach($ids as $id)
			$cache->remove("forum-$id");
		$cache->remove("publicforumlist-mostactive");
	}

	function deleteForum($ids){
		$res = $this->db->prepare_query("SELECT id FROM forumthreads WHERE forumid IN (#)", $ids);

		$threads = array();
		while($line = $res->fetchrow())
			$threads[] = $line['id'];

		if(count($threads))
			$this->db->prepare_query("DELETE FROM forumposts WHERE threadid IN (#)", $threads);
		$this->db->prepare_query("DELETE FROM forumthreads WHERE forumid IN (#)", $ids);


		$res = $this->db->prepare_query("SELECT id FROM forumthreadsdel WHERE forumid IN (#)", $ids);

		$threads = array();
		while($line = $res->fetchrow())
			$threads[] = $line['id'];

		if(count($threads))
			$this->db->prepare_query("DELETE FROM forumpostsdel WHERE threadid IN (#)", $threads);
		$this->db->prepare_query("DELETE FROM forumthreadsdel WHERE forumid IN (#)", $ids);


		$this->db->prepare_query("DELETE FROM forums WHERE id IN (#)", $ids);

		$this->invalidateForums($ids);
	}

	function pruneforums(){
		global $polls;

	//delete forums not posted in in 30 days
		$res = $this->db->prepare_query("SELECT id FROM forums WHERE official='n' && time < #", time() - 86400*30);

		$forums = array();
		while($line = $res->fetchrow())
			$forums = $line['id'];

		if (!$forums)
			return;

		$this->deleteForum($forums);

	//delete threads not posted in in 6 times the default view length (default 2*6 weeks)
		$res = $this->db->prepare_query("SELECT forumthreads.id, forumthreads.pollid FROM forums,forumthreads WHERE forums.id=forumthreads.forumid && forumthreads.announcement = 'n' && forums.sorttime > 0 && forumthreads.time <= (# - forums.sorttime*6*86400)", time());

		$threadids = array();
		$pollids = array();
		$i=0;
		while($line = $res->fetchrow()){
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
		$res = $this->db->prepare_query("SELECT forumthreadsdel.id, forumthreadsdel.pollid FROM forums,forumthreadsdel WHERE forums.id=forumthreadsdel.forumid && forumthreadsdel.announcement = 'n' && forums.sorttime > 0 && forumthreadsdel.time <= (# - forums.sorttime*7*86400)", time());

		$threadids = array();
		$i=0;
		while($line = $res->fetchrow()){
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

	function forumMuteUser($uid, $fid, $tid, $time, $reasonid, $reason, $globalreq = false, $forumname = "", $moditem = true){
		global $userData, $msgs, $mods, $cache, $messaging, $abuselog, $wwwdomain;

		if($time==0)
			$unmutetime = 0;
		else
			$unmutetime = time() + $time;

		$this->db->prepare_query("INSERT IGNORE INTO forummute SET userid = #, forumid = #, mutetime = #, unmutetime = #, reasonid = #", $uid, $fid, time(), $unmutetime, $reasonid);

		if($this->db->affectedrows()){
			$id = $this->db->insertid();

			$this->db->prepare_query("INSERT INTO forummutereason SET id = #, modid = #, reason = ?, threadid = #, globalreq = ?", $id, $userData['userid'], $reason, $tid, ($globalreq ? 'y' : 'n'));

			if($forumname=="" && $fid != 0){
				$res = $this->db->prepare_query("SELECT name, official FROM forums WHERE id = #", $fid);

				$forumname = $res->fetchfield();
			}

			$msg = "Dear User,\n\nThis is to notify you that you have been banned ";

			if($time > 0)	$msg .= " for";
			$msg .= " " . strtolower($this->mutelength[$time]) . " ";


			if($fid == 0)	$msg .= "from all of our forums";
			else			$msg .= "from '[url=http://" . $wwwdomain . "/forumthreads.php?fid=$fid]" . $forumname . "[/url]'";

			if($tid)		$msg .= " for your actions in [url=http://" . $wwwdomain . "/forumviewthread.php?tid=$tid]this thread[/url]";

			$msg .= " with the reason '$reason'.\n\n";
			$msg .= "If you wish to dispute this action, feel free to reply.\n\n";

			$messaging->deliverMsg($uid,"Forum Ban",$msg, 0, false, false, false); //not ignorable

			$this->modLog('mute',$fid,0,$uid,$unmutetime);

			if($moditem){
				$mods->newItem(MOD_FORUMBAN, $id);

				$subject = $this->mutelength[$time];
				$subject.= ($time ? " ban " : " banned ");
				$subject.= ($fid ? "from $forumname" : "Globally");
				if($globalreq && $fid)
					$subject .= " (Global Requested)";

				$message = "Forum: " . ($fid ? "[url=http://" . $wwwdomain . "/forumthreads.php?fid=$fid]$forumname" . "[/url]" : "Global") . "\n";
				if($tid)
					$message .= "Thread: [url=http://" . $wwwdomain . "/forumviewthread.php?tid=$tid]link[/url]\n";
				$message.= "Reason: $reason\n";
				$message.= "Length: " . $this->mutelength[$time] . "\n";


				$abuselog->addAbuse($uid, ABUSE_ACTION_FORUM_BAN, $reasonid, $subject, $message);
			}

			$cache->remove("forummutes-$uid-$fid");

			$msgs->addMsg("User Muted");
		}else{
			$msgs->addMsg("User already Muted");
		}
	}

	function forumUnmuteUser($uid, $fid){
		global $msgs, $cache;

		$res = $this->db->prepare_query("SELECT unmutetime FROM forummute WHERE userid = # && forumid = #", $uid, $fid);
		$line = $res->fetchrow();

		if(!$line)
			return;

		$this->modLog('unmute',$fid,0,$uid,$line['unmutetime']);

		$this->db->prepare_query("DELETE forummute, forummutereason FROM forummute, forummutereason WHERE forummute.id = forummutereason.id && userid = # && forumid = #", $uid, $fid);

		$cache->remove("forummutes-$uid-$fid");

		$msgs->addMsg("User UnMuted");
	}

	function invite($uids, $fid, $catid = 0){
		global $cache, $messaging, $userData, $wwwdomain;

		if(!is_array($uids))
			$uids = array($uids);

		$res = $this->db->prepare_query("SELECT name FROM forums WHERE id = #", $fid);
		$forumname = $res->fetchfield();

		foreach($uids as $uid){
			if($userData['userid'] == $uid) // users inviting other users should not overwrite existing categorization, but system reinvites (recategorization really) should.
				$this->db->prepare_query("DELETE FROM foruminvite WHERE userid = # AND forumid = #", $uid, $fid);
			$this->db->prepare_query("INSERT IGNORE INTO foruminvite SET userid = #, categoryid = #, forumid = #", $uid, $catid, $fid);

			if($this->db->affectedrows() == 0)
				continue;

			if($userData['userid'] != $uid) //only log invites, not self-subscriptions
				$this->modLog('invite',$fid,0,$uid);

			$cache->put("foruminvite-$uid-$fid", 1, 10800);
			$cache->remove("subforumlist-$uid");

			if($userData['userid'] != $uid)
				$messaging->deliverMsg($uid, "Forum Invite", "You've been invited to a forum called [url=http://" . $wwwdomain . "/forumthreads.php?fid=$fid]$forumname" . "[/url].\n\nNot interested?  Click [url=http://" . $wwwdomain . "/forumthreads.php?fid=$fid&action=withdraw&k=" . makeKey($fid, $uid) . "]here[/url] to withdraw from the forum.", 0, false, false, false);
		}
	}

	function unInvite($uids, $fid){
		global $cache, $userData;

		if(!is_array($uids))
			$uids = array($uids);

		$this->db->prepare_query("DELETE FROM foruminvite WHERE userid IN (#) && forumid = #", $uids, $fid);

		foreach($uids as $uid){
			if($userData['userid'] != $uid)
				$this->modLog('uninvite',$fid,0,$uid);
			$cache->put("foruminvite-$uid-$fid", 0, 10800);
			$cache->remove("subforumlist-$uid");
		}
	}

	// for internal use only.
	function getGlobalCategories()
	{
		$result = $this->db->prepare_query('SELECT id, ownerid, priority, official, name FROM forumcats WHERE ownerid = # ORDER BY priority ASC', 0);

		while ($row = $result->fetchrow())
		{
			$globalcats[ $row['id'] ] = $row;
		}
		$globalcats[0] = array(
						'id' => 0,
						'name' => 'Uncategorized',
						'ownerid' => 0,
						'priority' => 128,
						'official' => 'n'
					);
		return $globalcats;
	}

	function getCategories($userid = false) // pass a userid to include user-specified categories.
	{
		global $cache;

		// first get the global ones, with the first try being from the hd cache
		$globalcats = $cache->get('forumcategories', 60*60*24, array(&$this, 'getGlobalCategories'));

		// if a userid is passed in, try and get that user's list of categories from memcache
		$usercats = array();
		if ($userid)
		{
			$usercats = $cache->get("forumcategories-$userid");
			if (!is_array($usercats))
			{
				$result = $this->db->prepare_query('SELECT id, ownerid, priority, official, name FROM forumcats WHERE ownerid = # ORDER BY name ASC', $userid);
				$usercats = array();
				while ($row = $result->fetchrow())
				{
					$usercats[ $row['id'] ] = $row;
				}
				$cache->put("forumcategories-$userid", $usercats, 60*60);
			}
		}
		return $usercats + $globalcats;
	}

	function createCategory($name, $official, $ownerid, $priority)
	{
		global $cache;

		$commands = array();			$params = array();
		$commands[] = "name = ?";		$params[] = $name;
		$commands[] = "official = ?";   $params[] = $official;
		$commands[] = "priority = ?";	$params[] = $priority;
		$commands[] = 'ownerid = #';	$params[] = $ownerid;

		$query = "INSERT INTO forumcats SET " . implode(", ",$commands);
		$this->db->prepare_array_query($query, $params);

		if ($ownerid != 0)
			$cache->remove("forumcategories-$ownerid");

		return $this->db->insertid();
	}

	function invalidateCategory($id, $ownerid = 0)
	{
		global $cache;
		if ($ownerid != 0)
		{
			$cache->remove("forumcategories-$ownerid");
			$cache->remove("subforumlist-$ownerid");
		} else {
			$cache->remove("forumcategories");
			$cache->remove("publicforumlist-mostactive");
		}
	}

	function modifyCategory($id, $alter, $ownerid = 0)
	{
		$commands = array(); $params = array();
		foreach ($alter as $key => $val)
		{
			$commands[] = "$key = ?"; $params[] = $val;
		}
		$this->db->prepare_array_query("UPDATE forumcats SET " . implode(',',$commands) . " WHERE id = # AND ownerid = #", array_merge($params, array($id, $ownerid)));
		$this->invalidateCategory($id, $ownerid);

		return $this->db->affectedrows();
	}

	function deleteCategory($id, $ownerid = 0)
	{
		$this->db->prepare_query("DELETE FROM forumcats WHERE id = # AND ownerid = #", $id, $ownerid);
		$this->invalidateCategory($id, $ownerid);

		return $this->db->affectedrows();
	}

	// note that this adds the new column, which is defaulted to 0 if not logged in and 1 if logged in, but is not computed beyond that.
	// The callee is responsible for filling in correct values (probably with getForumNewStatus)
	function getForums($forumids)
	{
		global $cache, $userData;

		if (!$forumids)
			return array();

		$array = is_array($forumids);
		if(!$array)
			$forumids = array($forumids);

		// get the forums we can from memcache (only cached for a very short time)
		$forums = $cache->get_multi($forumids, 'forum-');
		$missing = array_diff($forumids, array_keys($forums));

		if($missing){
			$result = $this->db->prepare_query("SELECT * FROM forums WHERE id IN (#)", $missing);
			while($forum = $result->fetchrow()){
				$forum['new'] = $userData['loggedIn'];
				$forums[ $forum['id'] ] = $forum;
				$cache->put("forum-$forum[id]", $forum, 30);
			}
		}

		if($array)
			return $forums;
		else
			return array_pop($forums);
	}


	function getCollapsedCategories($userid)
	{
		global $cache;

		$collapselist = $cache->get("forum-collapsed-$userid");
		if (!is_string($collapselist))
		{
			$collapselist = array();
			$result = $this->db->prepare_query("SELECT categoryid FROM forumcatcollapse WHERE userid = #", $userid);
			while ($row = $result->fetchrow())
			{
				$collapselist[] = $row['categoryid'];
			}
			$cache->put("forum-collapsed-$userid", implode(',',$collapselist), 24*60*60);
			return $collapselist;
		} else {
			return explode(',', $collapselist);
		}
	}

	function setCollapseCategories($userid, $catcollapse) // array of $catid -> $collapsed
	{
		global $cache;

		$collapseids = array();
		$expandids = array();
		foreach ($catcollapse as $catid => $collapsed)
		{
			if ($collapsed)
			{
				$collapseids[] = $catid;
				$collapsequeries[] = $this->db->prepare("(#, #)", $userid, $catid);
			} else
				$expandids[] = $catid;
		}
		if ($expandids)
		{
			$this->db->prepare_query("DELETE FROM forumcatcollapse WHERE userid = # AND categoryid IN (#)", $userid, $expandids);
			$cache->remove("forum-collapsed-$userid");
		}
		if ($collapseids)
		{
			$this->db->query("INSERT IGNORE INTO forumcatcollapse (userid, categoryid) VALUES " . implode(',',$collapsequeries));
//			$cache->append("forum-collapsed-$userid", ',' . implode(',',$collapseids), 24*60*60);
			$cache->remove("forum-collapsed-$userid");
		}
	}

	// sets $forumobj[new] to 1 if unread
	function getForumNewStatus(&$forumobjs)
	{
		global $userData;

		if (count($forumobjs))
		{
			$fids = array_keys($forumobjs);
			$result = $this->db->prepare_query("SELECT forumid, time FROM forumupdated WHERE userid = # AND forumid IN (#)", $userData['userid'], $fids);
			while ($updated = $result->fetchrow())
			{
				$fid = $updated['forumid'];
				if ($forumobjs[$fid]['time'] > $updated['time'])
					$forumobjs[$fid]['new'] = 1;
				else
					$forumobjs[$fid]['new'] = 0;
			}
		}
	}

	function getCategoryForums(&$total, $catid, $orderby, $filter = false, $pagenum = 0, $perpage = 10, $viewall = false)
	{
		global $userData;
		$forums = array();

		$filterclause = "";
		if ($filter)
		{
			$filterescape = $this->db->escape($filter);
			$filterclause = "AND (name LIKE '%$filterescape%' OR description LIKE '%$filterescape%')";
		}
		if ($viewall)
			$public = "public IN ('y', 'n')";
		else
			$public = "public='y'";

		// get the list of forums that meet the requirements
		if ($orderby == 'mostactive')
		{
			$result = $this->db->query($this->db->prepare("SELECT SQL_CALC_FOUND_ROWS id FROM forums USE KEY(byposts)") . " WHERE $public AND " . $this->db->prepare("categoryid = #", $catid) . " $filterclause ORDER BY unofficial DESC, posts DESC " . $this->db->prepare("LIMIT #,#",
				$pagenum*$perpage, $perpage));
		} else if ($orderby == 'mostrecent') {
			$result = $this->db->query($this->db->prepare("SELECT SQL_CALC_FOUND_ROWS id FROM forums USE KEY(bytime)") . "  WHERE $public AND " . $this->db->prepare("categoryid = #", $catid) . " $filterclause ORDER BY unofficial DESC, time DESC " . $this->db->prepare("LIMIT #,#",
				$pagenum*$perpage, $perpage));
		} else if ($orderby == 'alphabetic') {
			$result = $this->db->query($this->db->prepare("SELECT SQL_CALC_FOUND_ROWS id FROM forums USE KEY(byname)") . "  WHERE $public AND " . $this->db->prepare("categoryid = #", $catid) . " $filterclause ORDER BY official ASC, name ASC " . $this->db->prepare("LIMIT #,#",
				$pagenum*$perpage, $perpage));
		} else {
			return $forums;
		}

		while ($forum = $result->fetchrow())
			$forums[] = $forum['id'];

		$total = $this->db->totalrows();

		return $forums;
	}

	function getPublicForumList($filter = false, $orderby = 'mostactive', $viewall = false)
	{
		global $cache;

		$cachetime = 24*60*60;
		$cachekey = 'publicforumlist';
		if ($orderby == 'mostactive')
		{
			$cachekey .= "-mostactive";
		} else if ($orderby == 'mostrecent') {
			$cachekey .= "-mostrecent";
			$cachetime = 60; // only want to cache this for a short time, as it's not invalidated.
		}
		if ($viewall)
			$cachekey .= "-viewall";

		$publicforumlist = $filter? false : $cache->get($cachekey);
		if (!is_array($publicforumlist))
		{
			$publicforumlist = array('categories' => array(), 'forums' => array());

			$catlist = $this->getCategories(); // no user categories for the public list
			foreach ($catlist as $cat)
			{
				$total = 0;
				$forumlist = $this->getCategoryForums($total, $cat['id'], $orderby, $filter, 0, 5, $viewall);
				if ($forumlist)
				{
					$publicforumlist['categories'][ $cat['id'] ] = $forumlist;
					$publicforumlist['totals'][ $cat['id'] ] = $total;
					$publicforumlist['forums'] = array_merge($publicforumlist['forums'], $forumlist);
				}
			}
			if (!$filter)
				$cache->put($cachekey, $publicforumlist, $cachetime);
		}
		return $publicforumlist;
	}

	function getSubscribedForumList($userid, $filter = false)
	{
		global $cache;

		$filterclause = "";
		$jointo = "";
		if ($filter)
		{
			$filterescape = $this->db->escape($filter);
			$filterclause = "AND (name LIKE '%$filterescape%' OR description LIKE '%$filterescape%') AND forums.id = foruminvite.forumid";
			$jointo = ", forums ";
		}

		$subforumlist = $filter? false : $cache->get("subforumlist-$userid");
		if (!is_array($subforumlist))
		{
			$subforumlist = array('categories' => array(), 'totals' => array(), 'forums' => array());

			$result = $this->db->query("SELECT foruminvite.forumid, foruminvite.categoryid FROM foruminvite$jointo WHERE " . $this->db->prepare("userid = #", $userid) . " $filterclause");
			while ($row = $result->fetchrow())
			{
				$categoryid = $row['categoryid'];

				$subforumlist['categories'][$categoryid][] = $row['forumid'];
				if (isset($subforumlist['totals'][$categoryid]))
					$subforumlist['totals'][$categoryid]++;
				else
					$subforumlist['totals'][$categoryid] = 1;
				$subforumlist['forums'][] = $row['forumid'];
			}

			if (!$filter)
				$cache->put("subforumlist-$userid", $subforumlist, 24*60*60);
		}

		return $subforumlist;
	}

	// looks in subforumlist uncategorized items, looks at their forum object.
	function categorizeDefaultCategories(&$subforumlist, &$forumobjs)
	{
		if (isset($subforumlist['categories'][0]))
		{
			$forumids = $subforumlist['categories'][0];
			$subforumlist['categories'][0] = array();
			foreach ($forumids as $idx => $id)
			{
				if (isset($forumobjs[$id]))
				{
					$subforumlist['categories'][ $forumobjs[$id]['categoryid'] ][] = $id;
				} else {
					$subforumlist['categories'][0][] = $id;
				}
			}
			if (!$subforumlist['categories'][0])
				unset($subforumlist['categories'][0]);
		}
	}

	function getOfficialForumList()
	{
		$subforumlist = array('categories' => array(), 'forums' => array());

		$result = $this->db->query("SELECT * FROM forums WHERE official = 'y'");
		while ($row = $result->fetchrow())
		{
			$categoryid = $row['categoryid'];

			$subforumlist['categories'][$categoryid][] = $row['id'];
			$subforumlist['forums'][] = $row['id'];
		}

		return $subforumlist;
	}

	function sortForums(&$forumobjs, $orderby = 'mostactive')
	{
		if ($orderby == 'mostactive')
			sortCols($forumobjs, SORT_DESC, SORT_NUMERIC, 'posts', SORT_DESC, SORT_REGULAR, 'official');
		else if ($orderby == 'mostrecent')
			sortCols($forumobjs, SORT_DESC, SORT_NUMERIC, 'time', SORT_DESC, SORT_REGULAR, 'official');
		else if ($orderby == 'alphabetic')
			sortCols($forumobjs, SORT_ASC, SORT_CASESTR, 'name', SORT_DESC, SORT_REGULAR, 'official');
	}

	function getForumCategory($forum, $userid = 0, $fallback = true) // whether to fall back on the forum's category or not.
	{
		if ($userid != 0)
		{
			// this is probably way too heavy handed, but it works and it uses existing caches
			$subforums = $this->getSubscribedForumList($userid, false);
			$forumobjs = array($forum['id'] => $forum);
			if ($fallback)
				$this->categorizeDefaultCategories($subforums, $forumobjs);

			foreach ($subforums['categories'] as $catid => $forums)
			{
				if (in_array($forum['id'], $forums))
					return $catid;
			}
		}
		return ($forum && $fallback)? $forum['categoryid'] : 0;
	}

	function getForumTrail($forum, $class)
	{
		global $userData;

		if ($userData['loggedIn'])
			$userid = $userData['userid'];
		else
			$userid = false;

		$defcat = $this->getForumCategory($forum, $userid);
		$cats = $this->getCategories($userid);

		$output = "<a class=$class href=/forums.php>Forums</a> > ";
		if ($defcat)
			$output .= "<a class=$class href=/forums.php?catid=$defcat>" . $cats[$defcat]['name'] . "</a> > ";
		$output .= "<a class=$class href=/forumthreads.php?fid=$forum[id]>$forum[name]</a>";

		return $output;
	}

	function getOfficialModsDump() {
		$officmods = array();

		$sth = $this->db->prepare_query("SELECT userid FROM forummods WHERE forumid = 0");

		while($line = $sth->fetchrow())
			$officmods[$line['userid']] = 1;


		$sth = $this->db->prepare_query("SELECT forummods.userid AS userid, COUNT(*) AS modcnt FROM forummods, forums WHERE forummods.forumid = forums.id AND forums.official = 'y' GROUP BY forummods.userid");

		while($line = $sth->fetchrow()){
			if(isset($officmods[$line['userid']]))
				$officmods[$line['userid']] += $line['modcnt'];
			else
				$officmods[$line['userid']] = $line['modcnt'];
		}

		return $officmods;
	}

	function isOfficialMod ($uid) {
		return isset($this->officialmods[$uid]);
	}
}

