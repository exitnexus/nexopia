<?

	$login=0;
	$accepttype = false;

	require_once("include/general.lib.php");

	class gallerypage extends pagehandler
	{
		function __construct()
		{
			$this->registerSubHandler(__FILE__,
				new varsubhandler($this, 'filmStripThumbs', REQUIRE_ANY,
					varargs('uid', 'integer', 'request'),
					varargs('cat', 'integer', 'request'),
					varargs('type', 'filmstrip', 'request')
				)
			);
			$this->registerSubHandler(__FILE__,
				new varsubhandler($this, 'allThumbs', REQUIRE_ANY,
					varargs('uid', 'integer', 'request'),
					varargs('cat', 'integer', 'request')
				)
			);
			$this->registerSubHandler(__FILE__,
				new varsubhandler($this, 'listCats', REQUIRE_ANY,
					varargs('uid', 'integer', 'request')
				)
			);

			$this->registerSubHandler('/galleries',
				new urisubhandler($this, 'showFullPicture', REQUIRE_ANY,
					uriargs('username', 'string'),
					uriargs('galleryid', 'integer'),
					uriargs('galleryname', 'string'),
					uriargs('picid', 'integer'),
					uriargs('full', 'full')
				)
			);
			$this->registerSubHandler('/galleries',
				new urisubhandler($this, 'filmStripThumbsUsername', REQUIRE_ANY,
					uriargs('username', 'string'),
					uriargs('galleryid', 'integer'),
					uriargs('galleryname', 'string'),
					uriargs('picid', 'integer')
				)
			);

			$this->registerSubHandler('/galleries',
				new urisubhandler($this, 'allThumbsUsername', REQUIRE_ANY,
					uriargs('username', 'string'),
					uriargs('galleryid', 'integer'),
					uriargs('galleryname', 'string'),
					uriargs('all', 'all')
				)
			);
			$this->registerSubHandler('/galleries',
				new urisubhandler($this, 'filmStripThumbsUsername', REQUIRE_ANY,
					uriargs('username', 'string'),
					uriargs('galleryid', 'integer'),
					uriargs('galleryname', 'string')
				)
			);
			$this->registerSubHandler('/galleries',
				new urisubhandler($this, 'listCatsUsername', REQUIRE_ANY,
					uriargs('username', 'string')
				)
			);
			$this->registerSubHandler('/galleries/pending',
				new urisubhandler($this, 'showPending', array(REQUIRE_LOGGEDIN, 'editgallery'),
					uriargs('username', 'string'),
					uriargs('type', 'thumb|full'),
					uriargs('sourceid', 'integer')
				)
			);
		}

		function startPage($user){
			incHeader(false, array('incSortBlock','incNewestMembersBlock'));

			echo injectSkin($user, 'gallery');

			echo "<table width=100%>";
			echo "<tr><td class=header2>" . incProfileHead($user) . "</td></tr>";
			echo "<tr><td class=body>";
		}

		function endPage(){
			echo "</td></tr></table>";
			incFooter();
		}

		function listCats($uid){
			global $userData, $config, $galleries, $weblog, $wwwdomain;

			$user = getUserInfo($uid);
			
			//http redirect to the new galleries in ruby-site	
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: http://". $wwwdomain . "/users/" . urlencode($user['username']) . "/gallery/");
			exit;
			
			if(!$user)
				die("Bad User");

			$userGalleries = new usergalleries($galleries, $uid);
			$galleryaccess = $userGalleries->getAccessLevel($userData);
			$galleryids = $userGalleries->getGalleryList($galleryaccess);
			$galleryobjs = $userGalleries->getGalleries($galleryids);
			gallery::fixPreviewPictures($galleryobjs);

			$this->startPage($user);

			$template = new template('pictures/gallery/listCats');
			$i = -1;
			$class = array('body', 'body2');

			$previewurl = array();
			foreach($galleryids as $id){
				$i++;
				$line = $galleryobjs[$id];
				if ($i>1)
					$class[$i] = $class[$i%2];

				$previewurl[$i] = $line->getImageURL('thumb');
			}
			$template->set('config', $config);
			$template->set('user', $user);
			$template->set('previewurl', $previewurl);
			$template->set('class', $class);
			$template->set('galleryids', $galleryids);
			$template->set('galleryobjs', $galleryobjs);
			$template->display();

			$this->endPage();

			return true;
		}

		function listCatsUsername($username)
		{
			global $wwwdomain;
			//http redirect to the new galleries in ruby-site	
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: http://". $wwwdomain . "/users/" . urlencode($username) . "/gallery/");
			exit;
			
			$uid = getUserId($username);
			if (!$uid)
				return false;

			return $this->listCats($uid);
		}

		function allThumbs($uid, $cat){
			global $userData, $db, $galleries, $config, $weblog, $reporev, $wwwdomain;

			$user = getUserInfo($uid);
			//http redirect to the new galleries in ruby-site	
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: http://". $wwwdomain . "/users/" . urlencode($user['username']) . "/gallery/");
			exit;
			
			
			$userGalleries = new usergalleries($galleries, $uid);
			$galleryaccess = $userGalleries->getAccessLevel($userData);
			$galleryobj = $userGalleries->getGallery("$uid:$cat");

			if (!$galleryobj || !$galleryobj->hasAccess($galleryaccess))
				return; // throw back to user's gallery main page?

			

			if(!$user)
				die("Bad User");

			$linkbase = "/galleries/" . urlencode($user['username']) . "/$cat/" . urlencode($galleryobj->name);

			$this->startPage($user);

			$template = new template('pictures/gallery/allThumbs');
			$template->set('jsurl', $config['jsloc']);
			$template->set('reporev', $reporev);
			$template->set('linkbase', $linkbase);
			$template->set('slashedGalleryName', addslashes($galleryobj->name));
			$template->set('user', $user);

			$ids = $galleryobj->getPictureList();
			$pics = $galleryobj->getPics($ids);

			$i=-1;
			$tmplids = array();
			foreach ($ids as $id) {
				$i++;
				$line = $pics[$id];
				$tmplids[$i] = $line->id;
				$fullurl[$i] = ($user['premiumexpiry'] < time()? '' : $linkbase . "/{$line->id}/full");
				$filterdesc[$i] = addslashes($line->parseDescription(true));
				$imageURL[$i] = $line->getImageURL();
				$imageURLThumb[$i] = $line->getImageURL('thumb');
			}
			$template->set('ids', $tmplids);
			$template->set('fullurl', $fullurl);
			$template->set('filterdesc', $filterdesc);
			$template->set('imageURL', $imageURL);
			$template->set('imageURLThumb', $imageURLThumb);
			$template->display();
			$this->endPage();

			return true;
		}
		function allThumbsUsername($username, $cat, $galleryname)
		{
			global $wwwdomain;
			//http redirect to the new galleries in ruby-site	
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: http://". $wwwdomain . "/users/" . urlencode($username) . "/gallery/" . $cat);
			exit;
			
			$uid = getUserId($username);
			if (!$uid)
				return false;

			return $this->allThumbs($uid, $cat);
		}

		function showFullPicture($username, $cat, $galleryname, $picid)
		{
			global $userData, $db, $galleries, $config, $weblog, $wwwdomain;
			
			//http redirect to the new galleries in ruby-site	
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: http://". $wwwdomain . "/users/" . urlencode($username) . "/gallery/" . $cat);
			exit;
			
			$uid = getUserId($username);
			if (!$uid)
				return false;

			$userGalleries = new usergalleries($galleries, $uid);
			$galleryaccess = $userGalleries->getAccessLevel($userData);
			$galleryobj = $userGalleries->getGallery("$uid:$cat");

			if (!$galleryobj || !$galleryobj->hasAccess($galleryaccess))
				return false; // throw back to user's gallery main page?

			$picobj = $galleryobj->getPic("$uid:$picid");
			if (!$picobj)
				return false;

			$user = getUserInfo($uid);

			if(!$user)
				die("Bad User");

			if($user['premiumexpiry'] < time())
				return false;

			$this->startPage($user);

			$linkbase = "/galleries/" . urlencode($user['username']) . "/$cat/" . urlencode($galleryobj->name);
			echo "<center><a href=\"$linkbase/$picid\">";
			echo "<img border=0 src=\"" . $picobj->getImageURL('full') . "\" />";
			echo "</a></center>";

			$this->endPage();

			return true;
		}

		function filmStripThumbs($uid, $cat, $id)
		{
			global $userData, $db, $galleries, $config, $weblog, $reporev, $wwwdomain;
			
			$user = getUserInfo($uid);
			
			//http redirect to the new galleries in ruby-site	
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: http://". $wwwdomain . "/users/" . urlencode($user['username']) . "/gallery/");
			exit;
			
			$userGalleries = new usergalleries($galleries, $uid);
			$galleryaccess = $userGalleries->getAccessLevel($userData);
			$galleryobj = $userGalleries->getGallery("$uid:$cat");

			if (!$galleryobj || !$galleryobj->hasAccess($galleryaccess))
				return; // throw back to user's gallery main page?

			

			if(!$user)
				die("Bad User");

			$this->startPage($user);

			$thumbcount = 5;
			$colcount = $thumbcount + 2;
			$iframeheight = $config['maxGalleryPicHeight'];

			$ids = $galleryobj->getPictureList();
			$pics = $galleryobj->getPics($ids);

			$linkbase = "/galleries/" . urlencode($user['username']) . "/$cat/" . urlencode($galleryobj->name);

			if ($id && isset($pics["$uid:$id"]))
			{
				$line = $pics["$uid:$id"];
			} else {
				foreach ($pics as $id => $line)
				{
					// $id and $line set by loop construct
					$id = $line->id;
					break;
				}
			}
			$template = new template('pictures/gallery/filmStripThumbs');
			if (isset($line)) {
				$template->set('line', $line);
				$fullurl = ($user['premiumexpiry'] < time()? '' : $linkbase . "/$id/full");
				$initialpic = "/imgframe.php?picid=$id&imgurl=" . $line->getImageURL() . "&fullurl=$fullurl";
				$template->set('initialpic', $initialpic);
				$template->set('jsurl', $config['jsloc']);
				$template->set('reporev', $reporev);
				$template->set('linkbase', $linkbase);
				$items = array();
				foreach ($ids as $id) {
					$line = $pics[$id];
					$items[$line->id] = array(
						'obj' => $line,
						'fullurl' => ($user['premiumexpiry'] < time()? '' : $linkbase . "/{$line->id}/full"),
						'desc' => $line->parseDescription(),
						'imageURL' => $line->getImageURL(),
						'imageURLThumb' => $line->getImageURL('thumb')
					);
				}
				$template->set('pics', $items);
				$template->set('colcount', $colcount);
				$template->set('user', $user);
				$template->set('galleryname', $galleryobj->name);
				$template->set('config', $config);
				for ($i = 0; $i < $thumbcount; $i++)
					$thumbcountarray[$i] = $i;
				$template->set('thumbcountarray', $thumbcountarray);
				$template->set('thumbcount', $thumbcount);
				$template->set('intialpic', $initialpic);
			} else {
				$template->set('line', false);
			}
			$template->display();

			$this->endPage();

			return true;
		}
		function filmStripThumbsUsername($username, $cat, $galleryname, $picid = false)
		{
			global $wwwdomain;
			//http redirect to the new galleries in ruby-site	
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: http://". $wwwdomain . "/users/" . urlencode($username) . "/gallery/" . $cat);
			exit;
			
			$uid = getUserId($username);
			if (!$uid)
				return false;

			return $this->filmStripThumbs($uid, $cat, $picid);
		}

		function showPending($username, $type, $sourceid)
		{
			global $sourcepictures, $userData;
			$uid = getUserId($username);
			$user = getUserInfo($uid);

			$sourcePic = $sourcepictures->getSourcePicture("$uid:$sourceid");
			if ($sourcePic && ($sourcePic->userid == $userData['userid'] || $this->getActualLevel() == REQUIRE_LOGGEDIN_ADMIN))
			{
				$path = $sourcePic->getPicPath($type == 'full');
				if (!file_exists($path))
					return false;

				header('Content-Type: image/jpeg');
				readfile($path);
				return true;
			}
			return false;
		}
	}

	$gallerypage = new gallerypage();
	return $gallerypage->runPage();
