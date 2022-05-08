<?

	$login = 0.5;

	require_once("include/general.lib.php");

	class managepicturepage extends pagehandler
	{
		function __construct()
		{
			$this->registerSubHandler('/managepicture.php/uploaded/legacy',
				new varsubhandler($this, array('uploadedPictures', 1), REQUIRE_HALFLOGGEDIN,
					varargs('signpic', 'integer', 'post', false, false),
					varargs('uploadid', 'integer', 'post')
				)
			);

			$this->registerSubHandler('/managepicture.php/uploaded',
				new varsubhandler($this, array('uploadedPictures', 0), REQUIRE_HALFLOGGEDIN,
					varargs('signpic', 'integer', 'post', false, false),
					varargs('uploadid', 'integer', 'post')
				)
			);

			$this->registerSubHandler(__FILE__,
				new varsubhandler($this, 'editPicture',
										 array(REQUIRE_HALFLOGGEDIN, 'editpictures'),
					varargs('uid', 'integer', 'post', false, false),
					varargs('pictureid', 'integer', 'post'),
					varargs('description', 'string', 'post'),
					varargs('signpic', 'y', 'post', false, 'n'),
					varargs('action', 'Update Picture', 'post')
				)
			);
			$this->registerSubHandler(__FILE__,
				new varsubhandler($this, 'deletePicture',
										 array(REQUIRE_HALFLOGGEDIN, 'editpictures'),
					varargs('uid', 'integer', 'request', false, false),
					varargs('pictureid', 'integer', 'request'),
					varargs('k', 'string', 'request'),
					varargs('action', 'deletepicture', 'request')
				)
			);
			$this->registerSubHandler(__FILE__,
				new varsubhandler($this, 'priorityPicture', REQUIRE_HALFLOGGEDIN,
					varargs('pictureid', 'integer', 'request'),
					varargs('direction', 'integer', 'request'),
					varargs('k', 'string', 'request'),
					varargs('action', 'picturepriority', 'request')
				)
			);

			$this->registerSubHandler(__FILE__,
				new varsubhandler($this, 'uploadPictures', REQUIRE_HALFLOGGEDIN,
					varargs('type', 'upload', 'request')
				)
			);
			$this->registerSubHandler(__FILE__,
				new varsubhandler($this, 'deletePending', array(REQUIRE_HALFLOGGEDIN, 'editpictures'),
					varargs('uid', 'integer', 'post', false, false),
					varargs('commit', array('t'), 'post'),
					varargs('type', 'pending', 'post'),
					varargs('action', 'Delete', 'post')
				)
			);
			$this->registerSubHandler(__FILE__,
				new varsubhandler($this, 'savePending', REQUIRE_HALFLOGGEDIN,
					varargs('commit', array('t'), 'post'),
					varargs('description', array('string'), 'post'),
					varargs('type', 'pending', 'post'),
					varargs('action', 'Save Descriptions', 'post')
				)
			);

			$this->registerSubHandler(__FILE__,
				new varsubhandler($this, 'editPictures',
				                         array(REQUIRE_HALFLOGGEDIN, 'editpictures'),
					varargs('uid', 'integer', 'request', false)
				)
			);

			$this->registerSubHandler('/manage/pictures',
				new urisubhandler($this, 'editPicturesUsername',
				                         array(REQUIRE_HALFLOGGEDIN, 'editpictures'),
					uriargs('username', 'string')
				)
			);
		}

		function editPicture($uid, $pictureid, $description, $signpic)
		{
			global $userData, $msgs, $cache, $usersdb, $google;
			if (!$uid)
				$uid = $userData['userid'];

			$isAdmin = ($this->getActualLevel() == REQUIRE_LOGGEDIN_ADMIN);

			if ($uid != $userData['userid'] && !$isAdmin)
				$uid = $userData['userid'];

			$user = getUserInfo($uid);

			$signpicupdate = '';
			if ($isAdmin && $signpic == 'n')
				$signpicupdate = "signpic = 'n',";

			$usersdb->prepare_query("UPDATE pics SET $signpicupdate description = ? WHERE userid = % AND id = #", removeHTML(trim(str_replace("\n", ' ', $description))), $uid, $pictureid);

			$cache->remove("pics-$uid");
			
			$google->updateHash($uid);

			$msgs->addMsg("Update Complete");

			header("Status: 301 Redirect");
			header("Location: /manage/pictures/$user[username]");
			return true;
		}

		function priorityPicture($pictureid, $direction, $k)
		{
			global $userData, $usersdb, $google;
			$uid = $userData['userid'];

			if (checkKey($pictureid, $k))
			{
				if ($direction < 0)
					decreasepriority($usersdb, "pics", $pictureid, $usersdb->prepare("userid = %", $uid), true);
				else
					increasepriority($usersdb, "pics", $pictureid, $usersdb->prepare("userid = %", $uid), true);

				setFirstPic($uid);
				$google->updateHash($uid);
			}
			header("Status: 301 Redirect");
			header("Location: /manage/pictures/$userData[username]");
			return true;
		}

		function deletePicture($uid, $pictureid, $k)
		{
			global $usersdb, $userData, $mods, $google;
			if (!$uid)
				$uid = $userData['userid'];

			if ($uid != $userData['userid'] && $this->getActualLevel() != REQUIRE_LOGGEDIN_ADMIN)
				$uid = $userData['userid'];

			$user = getUserInfo($uid);

			if ($user && checkKey($pictureid, $k))
			{
				$res = $usersdb->prepare_query("SELECT signpic FROM pics WHERE userid = % AND id = #", $uid, $pictureid);
				$line = $res->fetchrow();
				if($line){
					removePic($uid, $pictureid);

					setFirstPic($uid);
					if($uid != $userData['userid'])
						$mods->adminlog("delete picture", "Delete user picture: userid $uid");
				}
				$google->updateHash($uid);
			}

			header("Status: 301 Redirect");
			header("Location: /manage/pictures/$user[username]");
			return true;
		}

		function editPictures($uid)
		{
			global $usersdb, $userData, $config, $cache, $wwwdomain;

			if (!$uid)
				$uid = $userData['userid'];
			
			//http redirect to the new picture management in ruby-site
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: http://". $wwwdomain . "/my/gallery");
			exit;
			
			$isAdmin = ($this->getActualLevel() == REQUIRE_LOGGEDIN_ADMIN);

			if ($uid != $userData['userid'] && !$isAdmin)
				$uid = $userData['userid'];

			$user = getUserInfo($uid);

			if($uid == $userData['userid']){

				$userfirstpic = $userData['firstpic'];
				$usersignpic = $userData['signpic'];

				$maxpics = ($userData['premium'] ? $config['maxpicspremium'] : $config['maxpics']);
				if($usersignpic == 'y')
					$maxpics++;
			}else{
				$res = $usersdb->prepare_query("SELECT firstpic, signpic FROM users WHERE userid = %", $uid);
				$user = $res->fetchrow(MYSQL_NUM);

				if(!$user)
					die("This user doesn't exist");

				list($userfirstpic, $usersignpic) = $user;
			}

			$numpics = 0;
			$signpic = 'n';
			$firstpic = 0;

			$res = $usersdb->prepare_query("SELECT id, description, priority, signpic FROM pics WHERE userid = %", $uid);
			$pics = $res->fetchrowset();
			$numpics = count($pics);

			sortCols($pics, SORT_ASC, SORT_NUMERIC, 'priority');

			$template = new template('pictures/managepicture/editPictures');

			ob_start();
			$this->pendingPictures($uid, $uid != $userData['userid']);
			$template->set('pendingPictures', ob_get_contents());
			ob_end_clean();

			$i=1;
			$ids = array();
			$index = -1;
			$imageDirectory = array();
			$k = array();
			foreach($pics as $line){
				$index++;
				if($line['priority'] != $i){
					$ids[$line['id']] = $i;
					$line['priority'] = $i;
				}

				if($i == 1)
					$firstpic = $line['id'];

				$i++;
				if($line['signpic'] == 'y')
					$signpic = 'y';

				$id = $line['id'];
				$imageDirectory[$index] = floor($uid/1000) . "/" . weirdmap($uid);
				$k[$index] = makekey($id);
			}


			$template->set('pics', $pics);
			$template->set('config', $config);
			$template->set('uid', $uid);
			$template->set('imageDirectory', $imageDirectory);
			$template->set('k', $k);
			$template->set('userData', $userData);
			$template->set('isAdmin', $isAdmin);

			if(count($ids)){
				foreach($ids as $id => $i)
					$usersdb->prepare_query("UPDATE pics SET priority = # WHERE userid = % AND id = #", $i, $uid, $id);
				$cache->remove("pics-$uid");
			}
			if($signpic != $usersignpic || $firstpic != $userfirstpic){
				$usersdb->prepare_query("UPDATE users SET signpic = ?, firstpic = # WHERE userid = %", $signpic, $firstpic, $uid);
				$cache->remove("userinfo-$uid");
			}

			$template->display();

			return true;
		}

		function editPicturesUsername($username)
		{
			$uid = getUserId($username);
			if (!$uid)
				return false;

			return $this->editPictures($uid);
		}
		function pendingPictures($uid, $isAdmin)
		{
			global $usersdb, $userData, $config, $reporev;

			$maxpics = ($userData['premium'] ? $config['maxpicspremium'] : $config['maxpics']);
			if($userData['signpic'] == 'y')
				$maxpics++;

			$res = $usersdb->prepare_query("SELECT count(*) FROM pics WHERE userid = %", $userData['userid']);
			$num = $res->fetchfield();
			$res = $usersdb->prepare_query("SELECT count(*) FROM picspending WHERE userid = %", $userData['userid']);
			$num += $res->fetchfield();

			$reachedMax = ($num >= $maxpics);

			$res = $usersdb->prepare_query("SELECT id, description FROM picspending WHERE userid = %", $uid);
			$pics = $res->fetchrowset();
			$numpics = count($pics);

			$legacy = false;
			if (!isset($_SERVER['HTTP_USER_AGENT']) ||stripos($_SERVER['HTTP_USER_AGENT'], 'Opera')
				|| stripos($_SERVER['HTTP_USER_AGENT'], 'MSIE 4.') || stripos($_SERVER['HTTP_USER_AGENT'], 'MSIE 5.0')
				|| stripos($_SERVER['HTTP_USER_AGENT'], 'Firefox/1.0'))
			{
				$legacy = true;
			}

			$template = new template('pictures/managepicture/pendingPictures');
			$template->set('config', $config);
			$template->set('uid', $uid);
			$template->set('isAdmin', $isAdmin);
			$template->set('reporev', $reporev);
			$template->set('numpics', $numpics);
			$template->set('legacy', $legacy? '1' : '0');
			$template->set('reachedMax', $reachedMax);
			$template->set('maxPics', $maxpics);

			$jsAddPending = array();
			if ($numpics)
			{
				sortCols($pics, SORT_ASC, SORT_NUMERIC, 'id');

				$i = -1;
				foreach ($pics as $pic)
				{
					$i++;
					$jsAddPending[$i] = "addPending($pic[id], '" . htmlentities(addslashes($pic['description'])) . "', '$config[thumbloc]" . floor($uid/1000) . "/" . weirdmap($uid) . "/$pic[id].jpg');";
				}
			}
			$template->set('jsAddPending', $jsAddPending);
			$template->set('pics', $pics);

			$template->display();

			return true;
		}

		function deletePending($uid, $commit)
		{
			global $usersdb, $userData, $mods;
			if (!$uid)
				$uid = $userData['userid'];

			if ($uid != $userData['userid'] && $this->getActualLevel() != REQUIRE_LOGGEDIN_ADMIN)
				$uid = $userData['userid'];

			$user = getUserInfo($uid);

			$result = $usersdb->prepare_query("SELECT id FROM picspending WHERE userid = % AND id IN (#)", $uid, array_keys($commit));
			while ($line = $result->fetchrow())
			{
				removePicPending("$uid:$line[id]");

				if($uid != $userData['userid'])
					$mods->adminlog("delete pending picture", "Delete pending user picture: userid $uid");
			}

			// and send us off to the page we were on
			header("Status: 301 Redirect");
			header("Location: /manage/pictures/$user[username]");
			return true;
		}

		function savePending($commit, $description)
		{
			global $usersdb, $userData, $msgs;
			$uid = $userData['userid'];

			foreach ($commit as $id => $yes)
			{
				$usersdb->prepare_query("UPDATE picspending SET description = ? WHERE userid = % AND id = #", removeHTML(trim(str_replace("\n", ' ', $description[$id]))), $uid, $id);

				$msgs->addMsg("Update Complete");
			}

			header("Status: 301 Redirect");
			header("Location: /manage/pictures/$userData[username]");
			return true;
		}

		function uploadedPictures($redir, $signpic, $uploadid)
		{
			global $userData, $config, $usersdb, $msgs, $skinloc, $skindata;

			$userfirstpic = $userData['firstpic'];

			// file stuff is not built into the new system, so hack it for now. It's optional,
			// so no big deal.
			$userfiles = getFILEval('userfile');
			$picid = 0;
			$reachedMax = false;
			if (!empty($userfiles['tmp_name']))
			{
				$description = preg_replace('/(\.[jJ][pP][gG])$/', '', $userfiles['name']);
				$picid = addPic($userfiles['tmp_name'], $description, $signpic, $reachedMax);
				$description = htmlentities($description);
			} else {
				$msgs->addMsg("No file received, it may have been too large.");
			}

			if ($redir)
				return $this->editPictures($userData['userid']);

			$jsVariableSet = "
				var uploadid = {$uploadid};
				var uploadedpic = 0;
				var uploadeddesc = '';
				var uploadedurl = '';";
			if ($picid)
				$jsVariableSet .= "uploadedpic = $picid; uploadeddesc = '" . addslashes($description) . "'; uploadedurl = '" . floor($userData['userid']/1000) . "/" . weirdmap($userData['userid']) . "/$picid.jpg';";
			$jsNotifyLoaded = "
				function notifyLoaded()
				{
					if (parent && parent.uploadDone && parent.maxUploads)
					{
						parent.uploadDone(uploadid, uploadedpic, uploadeddesc, '$config[thumbloc]' + uploadedurl, document.getElementById('messages').innerHTML);
						" . ($reachedMax? "parent.maxUploads();" : "") . "
					}
				}";
			$template = new template('pictures/managepicture/uploadedPictures');
			$template->set('jsVariableSet', $jsVariableSet);
			$template->set('jsNotifyLoaded', $jsNotifyLoaded);
			$template->set('skinloc', $skinloc);
			$template->set('skindata', $skindata);
			$template->set('messages', $msgs->get());
			$template->display();

			return true;
		}

		function uploadPictures()
		{
			global $userData, $config, $usersdb, $msgs, $skinloc, $skindata;

			$userfirstpic = $userData['firstpic'];
			$usersignpic = $userData['signpic'];

			$maxpics = ($userData['premium'] ? $config['maxpicspremium'] : $config['maxpics']);
			if($usersignpic == 'y')
				$maxpics++;

			$target = '';
			$redir = '';
			if (stripos($_SERVER['HTTP_USER_AGENT'], 'Opera') || stripos($_SERVER['HTTP_USER_AGENT'], 'MSIE 4.') ||
				stripos($_SERVER['HTTP_USER_AGENT'], 'MSIE 5.0'))
			{
				$target = 'target="_parent"';
				$redir = '<input type=hidden name=redir value=1 />';
			}


			$jsNotifyLoaded = "
			function notifyLoaded()
			{
				if (parent && parent.resizeFrame)
				{
					parent.resizeFrame(document.getElementById('main'), this.frameElement? this.frameElement : null);
				}
			}";
			$jsSubmitFile = "
			function submitfile()
			{
				var formobj = document.getElementById('sendfile');
				if (parent && parent.submitfile)
				{
					parent.submitfile(formobj);
				}
			}";

			$template = new template('pictures/managepicture/uploadPictures');
			$template->set('messages', $msgs->display());
			$template->set('skinloc', $skinloc);
			$template->set('skindata', $skindata);
			$template->set('redir', $redir);
			$template->set('target', $target);
			$template->set('jsNotifyLoaded', $jsNotifyLoaded);
			$template->set('jsSubmitFile', $jsSubmitFile);
			$template->display();

			return true;
		}
	}

	$managepicturepage = new managepicturepage();
	$managepicturepage->runPage();
