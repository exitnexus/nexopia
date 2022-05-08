<?
	$login = 0.5;

	require_once("include/general.lib.php");

	class managegallerypage extends pagehandler
	{
		function __construct()
		{
			$this->registerSubHandler('/managegallery.php/uploaded/legacy',
				new varsubhandler($this, array('uploadedPictures', 1), REQUIRE_HALFLOGGEDIN,
					varargs('galleryid', 'integer', 'post', false, false),
					varargs('uploadid', 'integer', 'post')
				)
			);
			$this->registerSubHandler('/managegallery.php/uploaded',
				new varsubhandler($this, array('uploadedPictures', 0), REQUIRE_HALFLOGGEDIN,
					varargs('galleryid', 'integer', 'post', false, false),
					varargs('uploadid', 'integer', 'post')
				)
			);
			$this->registerSubHandler(__FILE__,
				new varsubhandler($this, 'editGallery',
								         array(REQUIRE_HALFLOGGEDIN, 'editgallery'),
					varargs('uid', 'integer', 'post', false, false),
					varargs('galleryid', 'integer', 'post'),
					varargs('name', 'string', 'post'),
					varargs('description', 'string', 'post'),
					varargs('permission', galleries::$regexperms, 'post'),
					varargs('action', 'Update Gallery', 'post')
				)
			);
			$this->registerSubHandler(__FILE__,
				new varsubhandler($this, 'createGallery', REQUIRE_HALFLOGGEDIN,
					varargs('name', 'string', 'post'),
					varargs('description', 'string', 'post'),
					varargs('permission', galleries::$regexperms, 'post'),
					varargs('action', 'Create Gallery', 'post')
				)
			);
			$this->registerSubHandler(__FILE__,
				new varsubhandler($this, 'deleteGallery', array(REQUIRE_HALFLOGGEDIN, 'editgallery'),
					varargs('uid', 'integer', 'request', false, false),
					varargs('galleryid', 'integer', 'request'),
					varargs('k', 'string', 'request'),
					varargs('action', 'deletegallery', 'request')
				)
			);
			$this->registerSubHandler(__FILE__,
				new varsubhandler($this, 'editPicture',
										 array(REQUIRE_HALFLOGGEDIN, 'editgallery'),
					varargs('uid', 'integer', 'post', false, false),
					varargs('galleryid', 'integer', 'post'),
					varargs('pictureid', 'integer', 'post'),
					varargs('description', 'string', 'post'),
					varargs('action', 'Update Picture', 'post')
				)
			);
			$this->registerSubHandler(__FILE__,
				new varsubhandler($this, 'deletePicture',
										 array(REQUIRE_HALFLOGGEDIN, 'editgallery'),
					varargs('uid', 'integer', 'request', false, false),
					varargs('galleryid', 'integer', 'request'),
					varargs('pictureid', 'integer', 'request'),
					varargs('k', 'string', 'request'),
					varargs('action', 'deletepicture', 'request')
				)
			);
			$this->registerSubHandler(__FILE__,
				new varsubhandler($this, 'priorityPicture', REQUIRE_HALFLOGGEDIN,
					varargs('galleryid', 'integer', 'request'),
					varargs('pictureid', 'integer', 'request'),
					varargs('direction', 'integer', 'request'),
					varargs('k', 'string', 'request'),
					varargs('action', 'picturepriority', 'request')
				)
			);
			$this->registerSubHandler(__FILE__,
				new varsubhandler($this, 'deletePending', array(REQUIRE_HALFLOGGEDIN, 'editgallery'),
					varargs('uid', 'integer', 'post', false, false),
					varargs('commit', array('t'), 'post'),
					varargs('galleryid', 'integer', 'post', false, false),
					varargs('type', 'pending', 'post'),
					varargs('action', 'Delete', 'post')
				)
			);
			$this->registerSubHandler(__FILE__,
				new varsubhandler($this, 'savePending', REQUIRE_HALFLOGGEDIN,
					varargs('commit', array('t'), 'post'),
					varargs('description', array('string'), 'post'),
					varargs('galleryid', 'integer', 'post'),
					varargs('addtags', array('t'), 'post', false, array()),
					varargs('type', 'pending', 'post'),
					varargs('action', 'Save', 'post')
				)
			);

			$this->registerSubHandler(__FILE__,
				new varsubhandler($this, 'editPictures',
				                         array(REQUIRE_HALFLOGGEDIN, 'editgallery'),
					varargs('uid', 'integer', 'request'),
					varargs('gallery', 'integer', 'request')
				)
			);
			$this->registerSubHandler(__FILE__,
				new varsubhandler($this, 'editGalleries',
				                         array(REQUIRE_HALFLOGGEDIN, 'editgallery'),
					varargs('uid', 'integer', 'request', false)
				)
			);

			$this->registerSubHandler('/manage/galleries',
				new urisubhandler($this, 'editPicturesUsername',
				                         array(REQUIRE_HALFLOGGEDIN, 'editgallery'),
					uriargs('username', 'string'),
					uriargs('galleryid', 'integer')
				)
			);
			$this->registerSubHandler('/manage/galleries',
				new urisubhandler($this, 'editGalleriesUsername',
				                         array(REQUIRE_HALFLOGGEDIN, 'editgallery'),
					uriargs('username', 'string')
				)
			);
		}

		function editGallery($uid, $galleryid, $name, $description, $permission)
		{
			global $galleries, $userData, $msgs, $mods;

			if (!$uid)
				$uid = $userData['userid'];

			if ($uid != $userData['userid'] && $this->getActualLevel() != REQUIRE_LOGGEDIN_ADMIN)
				$uid = $userData['userid'];

			if (!trim($name))
			{
				$msgs->addMsg('Did not specify a name for the gallery');
				return $this->editGalleries($uid);
			}

			$user = getUserInfo($uid);

			$userGalleries = new usergalleries($galleries, $uid);
			$galleryobj = $userGalleries->getGallery("$uid:$galleryid");

			if ($galleryobj && $galleryobj->ownerid == $uid)
			{
				$galleryobj->name = removeHTML($name);
				$galleryobj->description = removeHTML($description);
				$galleryobj->permission = $permission;
				$galleryobj->commit();

				enqueue("Gallery::Gallery", "edit", $uid, array($uid, $galleryid));

				if ($userData['userid'] != $uid)
					$mods->adminlog("edit gallery", "Edit user Gallery: userid $uid");
			}
			header("Status: 301 Redirect");
			header("Location: /manage/galleries/$user[username]");
			return true;
		}

		function editPicture($uid, $galleryid, $pictureid, $description)
		{
			global $galleries, $userData, $mods;
			if (!$uid)
				$uid = $userData['userid'];

			if ($uid != $userData['userid'] && $this->getActualLevel() != REQUIRE_LOGGEDIN_ADMIN)
				$uid = $userData['userid'];

			$user = getUserInfo($uid);
			$userGalleries = new usergalleries($galleries, $uid);
			$galleryobj = $userGalleries->getGallery("$uid:$galleryid");

			if ($galleryobj && $galleryobj->ownerid == $uid)
			{
				$pic = $galleryobj->getPic("$uid:$pictureid");
				if ($pic)
				{
					$pic->description = removeHTML($description);
					$pic->commit();

					enqueue("Gallery::Pic", "edit", $uid, array($uid, $pictureid));

					if ($userData['userid'] != $uid)
						$mods->adminlog("edit gallery picture", "Edit user Gallery: userid $uid");
				}
			}
			header("Status: 301 Redirect");
			header("Location: /manage/galleries/$user[username]/$galleryid");
			return true;
		}

		function priorityPicture($galleryid, $pictureid, $direction, $k)
		{
			global $galleries, $userData;
			$uid = $userData['userid'];

			if (checkKey($pictureid, $k))
			{
				$userGalleries = new usergalleries($galleries, $uid);
				$galleryobj = $userGalleries->getGallery("$uid:$galleryid");

				if ($galleryobj && $galleryobj->ownerid == $uid)
				{
					$pic = $galleryobj->getPic("$uid:$pictureid");
					if ($pic)
					{
						$pic->increasePriority($direction);
						$galleryobj->fixFirstPic();
						$galleryobj->commit();
					}
				}
			}
			header("Status: 301 Redirect");
			header("Location: /manage/galleries/$userData[username]/$galleryid");
			return true;
		}

		function deleteGallery($uid, $galleryid, $k)
		{
			global $galleries, $userData, $mods;
			if (!$uid)
				$uid = $userData['userid'];

			if ($uid != $userData['userid'] && $this->getActualLevel() != REQUIRE_LOGGEDIN_ADMIN)
				$uid = $userData['userid'];

			if (checkKey($galleryid, $k))
			{
				$user = getUserInfo($uid);

				$userGalleries = new usergalleries($galleries, $uid);
				$galleryobj = $userGalleries->getGallery("$uid:$galleryid");

				if ($galleryobj)
				{
					$galleryobj->delete();
					if ($userData['userid'] != $uid)
						$mods->adminlog("delete gallery", "Delete user Gallery: userid $uid");
				}
			}

			header("Status: 301 Redirect");
			header("Location: /manage/galleries/$user[username]");
			return true;
		}
		function deletePicture($uid, $galleryid, $pictureid, $k)
		{
			global $galleries, $userData, $mods;
			if (!$uid)
				$uid = $userData['userid'];

			if ($uid != $userData['userid'] && $this->getActualLevel() != REQUIRE_LOGGEDIN_ADMIN)
				$uid = $userData['userid'];

			$user = getUserInfo($uid);

			if ($user && checkKey($pictureid, $k))
			{
				$userGalleries = new usergalleries($galleries, $uid);
				$galleryobj = $userGalleries->getGallery("$uid:$galleryid");
				if ($galleryobj && $galleryobj->ownerid == $uid)
				{
					$pic = $galleryobj->getPic("$uid:$pictureid");
					if ($pic)
					{
						$pic->delete();
						$galleryobj->fixFirstPic();
						$galleryobj->commit();

						if ($userData['userid'] != $uid)
							$mods->adminlog("delete gallery picture", "Delete user Gallery picture: userid $uid");
					}
				}
			}

			header("Status: 301 Redirect");
			header("Location: /manage/galleries/$user[username]/$galleryid");
			return true;
		}

		function createGallery($name, $description, $permission)
		{
			global $usergalleries, $galleries, $userData, $msgs;
			$uid = $userData['userid'];

			if (!trim($name))
			{
				$msgs->addMsg('Did not specify a name for the gallery');
				return $this->editGalleries($uid);
			}

			$userGalleries = new usergalleries($galleries, $uid);
			$galleryobj = new gallery($usergalleries, $galleries);

			$galleryobj->ownerid = $uid;
			$galleryobj->name = removeHTML($name);
			$galleryobj->description = removeHTML($description);
			$galleryobj->permission = $permission;
			$galleryobj->commit();

			enqueue("Gallery::Gallery", "create", $uid, array($uid, $galleryobj->id));

			header("Status: 301 Redirect");
			header("Location: /manage/galleries/$userData[username]");
			return true;
		}

		function editGalleries($uid = false)
		{
			global $galleries, $userData, $config;

			if (!$uid)
				$uid = $userData['userid'];

			if ($uid != $userData['userid'] && $this->getActualLevel() != REQUIRE_LOGGEDIN_ADMIN)
				$uid = $userData['userid'];

			$user = getUserInfo($uid);
			$thisurl = '/manage/galleries/' . urlencode($user['username']);

			$template = new template('pictures/managegallery/editGalleries');

			$userGalleries = new usergalleries($galleries, $uid);
			$galleryaccess = $userGalleries->getAccessLevel($userData);
			$galleryids = $userGalleries->getGalleryList($galleryaccess);
			$galleryobjs = $userGalleries->getGalleries($galleryids);
			gallery::fixPreviewPictures($galleryobjs);

			$template->set('selectPermissions', make_select_list_key(galleries::$perms));

			ob_start();
			$this->pendingPictures($uid, $uid != $userData['userid']);
			$template->set('pendingPictures', ob_get_clean());


			$template->set('thisurl', $thisurl);
			$template->set('user', $user);
			$template->set('userData', $userData);
			$template->set('uid', $uid);

			$template->set('config', $config);
			$template->set('galleryids', $galleryids);
			$template->set('galleryobjs', $galleryobjs);
			$i = 0;
			$index=-1;

			$key = array();
			$class = array();
			$permsByLine = array();
			$previewurl = array();
			$filtername = array();
			$filterdesc = array();
			$selectLinePermission = array();

			foreach($galleryids as $id){
				$index++;
				$line = $galleryobjs[$id];

				$i = ($i + 1) % 2;
				$class[$index] = 'body' . ($i? '' : '2');

				$previewurl[$index] = $line->getImageURL('thumb');

				$filtername[$index] = htmlentities($line->name);
				$filterdesc[$index] = htmlentities($line->description);
				$key[$index] = makekey($line->id);
				$selectLinePermission[$index] = make_select_list_key(galleries::$perms, $line->permission);
				$permsByLine[$index] = galleries::$perms[$line->permission];

			}
			$template->set('key', $key);
			$template->set('class', $class);
			$template->set('permsByLine', $permsByLine);
			$template->set('previewurl', $previewurl);
			$template->set('filtername', $filtername);
			$template->set('filterdesc', $filterdesc);
			$template->set('selectLinePermission', $selectLinePermission);
			$template->display();


			return true;
		}
		function editGalleriesUsername($username)
		{
			$uid = getUserId($username);
			if (!$uid)
				return false;

			return $this->editGalleries($uid);
		}
		function editPictures($uid, $cat)
		{
			global $galleries, $userData, $config;

			if (!$uid)
				$uid = $userData['userid'];

			if ($uid != $userData['userid'] && $this->getActualLevel() != REQUIRE_LOGGEDIN_ADMIN)
				$uid = $userData['userid'];

			$user = getUserInfo($uid);

			$userGalleries = new usergalleries($galleries, $uid);
			$galleryaccess = $userGalleries->getAccessLevel($userData);
			$galleryobj = $userGalleries->getGallery("$uid:$cat");

			if (!$galleryobj || $galleryobj->ownerid != $uid)
				return $this->editGalleries($uid);

			$ids = $galleryobj->getPictureList();
			$pics = $galleryobj->getPics($ids);


			$i = 0;
			$index = -1;
			$filterdesc = array();
			$class = array();
			$thumbnail = array();
			$k = array();
			foreach($ids as $id){
				$index++;
				$line = $pics[$id];
				$filterdesc[$index] = $line->parseDescription();

				$i = ($i + 1) % 2;
				$class[$index] = 'body' . ($i? '' : '2');
				$thumbnail[$index] = $line->getImageURL('thumb');
				$k[$index] = makekey($line->id);
			}

			$template = new template('pictures/managegallery/editPictures');
			$template->set('user', $user);
			$template->set('cat', $cat);
			$template->set('galleryobj', $galleryobj);
			ob_start();
			$this->pendingPictures($uid, $uid != $userData['userid'], $cat);
			$template->set('pendingPictures', ob_get_clean());
			$template->set('filterdesc', $filterdesc);
			$template->set('config', $config);
			$template->set('userData', $userData);
			$template->set('uid', $uid);
			$template->set('class', $class);
			$template->set('k', $k);
			$template->set('thumbnail', $thumbnail);
			$template->set('ids', $ids);
			$template->set('pics', $pics);

			$template->display();
			return true;
		}
		function editPicturesUsername($username, $galleryid)
		{
			$uid = getUserId($username);
			if (!$uid)
				return false;

			return $this->editPictures($uid, $galleryid);
		}
		function pendingPictures($uid, $isAdmin = false, $galleryid = false)
		{
			global $sourcepictures, $galleries, $userData, $config, $reporev;
			$user = getUserInfo($uid);
			$usergal = new usergalleries($galleries, $uid);

			$maxpics = $userData['premium']? $config['maxgallerypicspremium'] : $config['maxgallerypics'];
			$countpics = $usergal->getPictureCount();
			$countpending = count($usergal->getPendingList());

			$reachedmax = ($countpics + $countpending) >= $maxpics;

			$legacy = false;
			if (!isset($_SERVER['HTTP_USER_AGENT']) ||stripos($_SERVER['HTTP_USER_AGENT'], 'Opera')
				|| stripos($_SERVER['HTTP_USER_AGENT'], 'MSIE 4.') || stripos($_SERVER['HTTP_USER_AGENT'], 'MSIE 5.0')
				|| stripos($_SERVER['HTTP_USER_AGENT'], 'Firefox/1.0'))
			{
				$legacy = true;
			}

			$template = new template('pictures/managegallery/pendingPictures');
			$template->set('config', $config);
			$template->set('reporev', $reporev);
			$template->set('isAdmin', $isAdmin);
			$template->set('userData', $userData);
			$template->set('user', $user);
			$template->set('galleryid', $galleryid);
			$template->set('uid', $uid);
			$template->set('legacy', $legacy? '1' : '0');
			$template->set('reachedMax', $reachedmax);
			$template->set('maxPics', $maxpics);
			$pendinglist = $usergal->getPendingList();

			$level = $usergal->getAccessLevel($userData);
			$gals = $usergal->getGalleryList($level);
			$galname = array();
			if (!$galleryid)
			{
				$galobjs = $usergal->getGalleries($gals);
				foreach ($galobjs as $id => $obj)
				{
					list($galuid,$id) = explode(':', $id);
					$galname[$id] = $obj->name;
				}
			}
			$pendingpics = array();
			$sourcepics = $sourcepictures->getSourcePictures(array_keys($pendinglist));
			foreach ($pendinglist as $id => $name)
			{
				if (isset($sourcepics[$id]))
				{
					$sourcepic = $sourcepics[$id];
					$pendingpics[$sourcepic->id] = array('name' => $name, 'source' => $sourcepic);
				}
			}

			$template->set('pendingpics', $pendingpics);
			$template->set('selectGallery', make_select_list_key($galname, $galleryid));
			$template->display();
			return true;
		}

		function deletePending($uid, $commit, $galleryid)
		{
			global $sourcepictures, $galleries, $userData;
			if (!$uid)
				$uid = $userData['userid'];

			if ($uid != $userData['userid'] && $this->getActualLevel() != REQUIRE_LOGGEDIN_ADMIN)
				$uid = $userData['userid'];

			$user = getUserInfo($uid);

			// clear the pending status of the ids given
			$usergal = new usergalleries($galleries, $uid);

			$todelete = array();
			foreach ($commit as $id => $t)
			{
				$todelete[] = "$uid:$id";
			}

			// we only want pending ids that the user actually owns, and are actually
			// pending
			$ownership = $usergal->getPendingList();
			$todelete = array_intersect($todelete, array_keys($ownership));

			if ($todelete)
			{
				$usergal->clearPending($todelete);

				// now, since they were pending and are thus not used elsewhere,
				// we can delete them permanently.
				$sourceobjs = $sourcepictures->getSourcePictures($todelete);
				sourcepicture::deleteMulti($sourcepictures->db, $sourceobjs);
			}

			// and send us off to the page we were on
			header("Status: 301 Redirect");
			header("Location: /manage/galleries/$user[username]" . ($galleryid? '/'.$galleryid : ''));
			return true;
		}

		function savePending($commit, $description, $galleryid, $addtags)
		{
			global $sourcepictures, $galleries, $userData;

			// get the gallery
			$usergal = new usergalleries($galleries, $userData['userid']);
			$galleryobj = $usergal->getGallery("$userData[userid]:$galleryid");

			$tosave = array();
			foreach ($commit as $id => $t)
			{
				$tosave[] = "$userData[userid]:$id";
			}

			// we only want pending ids that the user actually owns, and are actually
			// pending
			$ownership = $usergal->getPendingList();
			$tosave = array_intersect($tosave, array_keys($ownership));

			if ($galleryobj && $tosave)
			{
				$sourceobjs = $sourcepictures->getSourcePictures($tosave);

				foreach ($sourceobjs as $obj)
				{
					$galpic = new gallerypic($galleries);
					$galpic->userid = $userData['userid'];
					$galpic->description = removeHTML((isset($description[$obj->id])? $description[$obj->id] : ''));
					$galpic->galleryid = $galleryobj->id;
					$galpic->commit();

					$addtag = (isset($addtags[$obj->id])? true : false);
					$galpic->generatePictures($obj, $userData['premium'] && $addtag);
					$galpic->commit();

					$galleryobj->fixFirstPic();
					$galleryobj->commit();
				}
				// images have been saved and put in as gallery images, so clear them out.
				$usergal->clearPending($tosave);
			}
			header("Status: 301 Redirect");
			header("Location: /manage/galleries/$userData[username]/$galleryid");
			return true;
		}

		function uploadedPictures($redir, $galleryid, $uploadid)
		{
			global $userData, $sourcepictures, $galleries, $msgs, $config;

			$usergal = new usergalleries($galleries, $userData['userid']);
			$maxpics = $userData['premium']? $config['maxgallerypicspremium'] : $config['maxgallerypics'];
			$countpics = $usergal->getPictureCount();
			$countpending = count($usergal->getPendingList());

			$reachedmax = ($countpics + $countpending) >= $maxpics;

			$picid = 0;
			$description = "";
			if (!$reachedmax)
			{
				// file stuff is not built into the new system, so hack it for now. It's optional,
				// so no big deal.
				$userfiles = getFILEval('userfile');
				if (!empty($userfiles['tmp_name']))
				{
					$sourcepic = new sourcepicture($sourcepictures->db);
					$sourcepic->userid = $userData['userid'];
					$description = removeHTML(preg_replace('/(\.[jJ][pP][gG])$/', '', $userfiles['name']));
					$md5 = md5_file($userfiles['tmp_name']);
					if ($sourcepic->uploadPic($userfiles['tmp_name'], false))
					{
						if ($usergal->addPending($sourcepic->id, $description, $md5))
							$picid = $sourcepic->id;
						else {
							$msgs->clearMsgs(); // clear out "Picture uploaded successfuly" message
							$msgs->addMsg("Image already uploaded.");
							$sourcepic->delete();
						}
					}
				} else {
					$msgs->addMsg("No file was received. It may have been too large.");
				}
			} else
				$msgs->addMsg("You have reached the maximum number of gallery images ($maxpics)");

			if ($redir)
				return $this->editPictures($userData['userid'], $galleryid);

			$template = new template('pictures/managegallery/uploadedPictures');
			$template->set('picid', $picid);
			$template->set('slashedDescription', addslashes($description));
			$template->set('slashedUserName', addslashes($userData['username']));
			$template->set('messages', $msgs->get());
			$template->set('uploadid', $uploadid);
			$template->set('reachedMax', $reachedmax);
			$template->display();
			return true;
		}
	}

	$managegallerypage = new managegallerypage();
	return $managegallerypage->runPage();
