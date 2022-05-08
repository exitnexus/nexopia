<?
	$login = 0;

	require_once "include/general.lib.php";

	$template = new template('forums/forums');

	$collapse = getREQval('collapse','array');
	$key = getREQval('k');
	$validkey = checkKey($userData['userid'], $key);

	if($userData['loggedIn'] && count($collapse) && $validkey){
		$forums->setCollapseCategories($userData['userid'], $collapse);
		exit;
	}

	$globalmodpowers = $forums->getModPowers($userData['userid'], array(0));
	$viewall = $mods->isAdmin($userData['userid'],'forums') || (isset($globalmodpowers[0]) && $globalmodpowers[0]['view'] == 'y');

	$filter = getREQval('filter', 'string', '');
	$filterstr = "";
	if (!empty($filter))
	{
		$filterstr = "&filter=" . urlencode($filter);
	}
	$pagenum = getREQval('page', 'integer', 0);

	$catid = getREQval('catid', 'integer', -1);
	if ($catid == -1)
	{
		$catid = null;
		$catstr = "";
	} else {
		$catstr = "&catid=$catid";
	}
	$setprefs = array();

	$onlysubspref = !empty($userData['onlysubscribedforums']) && $userData['onlysubscribedforums']=='y';
	$onlysubs = getREQval('onlysubs', 'integer', $onlysubspref);

	if ($onlysubs != $onlysubspref)
	{
		$setprefs[] = $usersdb->prepare(" onlysubscribedforums = ? ", $onlysubs? 'y' : 'n');
	}
	$orderbypref = !empty($userData['orderforumsby'])? $userData['orderforumsby'] : 'mostactive';
	$orderby = getREQval('orderby', 'string', $orderbypref);
	if ($orderby != $orderbypref && ($orderby == 'mostactive' || $orderby == 'mostrecent' || $orderby == 'alphabetic'))
	{
		$setprefs[] = $usersdb->prepare(" orderforumsby = ? ", $orderby);
	}

	if ($setprefs && $validkey)
	{
		$usersdb->prepare_query("UPDATE users SET " . implode(',', $setprefs) . " WHERE userid = %", $userData['userid']);
		$cache->remove("userprefs-$userData[userid]");
	}

	$forumlist = array('forums' => array(), 'totals' => array(), 'categories' => array());
	if (!$onlysubs || isset($catid) || $filter)
	{
		if (isset($catid))
		{
			$total = 0;
			$forumids = $forums->getCategoryForums($total, $catid, $orderby, $filter, $pagenum, 25, $viewall);
			$forumlist['forums'] = $forumids;
			$forumlist['totals'][$catid] = $total;
			$forumlist['categories'][$catid] = $forumids;
			$numpages =  ceil($total / 25);
		} else {
			$forumlist = $forums->getPublicForumList($filter, $orderby, $viewall);
		}
	}

	$subforumlist = array('categories' => array(), 'totals' => array(), 'forums' => array());
	$subforumobjs = array();
	$uid = false;
	if ($userData['loggedIn'])
	{
		$uid = $userData['userid'];
		if ($pagenum == 0)
		{
			$subforumlist = $forums->getSubscribedForumList($uid, $filter);
			$subforumobjs = $forums->getForums($subforumlist['forums']);
			$forums->categorizeDefaultCategories($subforumlist, $subforumobjs);

			// if we're looking at all categories, remove the ones that aren't it.
			if (isset($catid))
			{
				if (!isset($subforumlist['categories'][$catid]))
				{
					$subforumlist = array('categories' => array(), 'forums' => array());
				} else {
					foreach ($subforumlist['categories'] as $subcatid => $stuff)
					{
						if ($subcatid != $catid) unset($subforumlist['categories'][$subcatid]);
					}
					$subforumlist['forums'] = $subforumlist['categories'][$catid];
				}
			}
		}
	}

	$categories = $forums->getCategories($uid);
	$forumobjs = $subforumobjs + $forums->getForums($forumlist['forums']);
	$forums->getForumNewStatus($forumobjs);

function replaceArgsLink($newargs) // takes current page URL and GET args, overrides any set in $newargs, and returns a URL to that.
{
	global $filter, $catid;

	$args = array();
	if (!empty($filter))
		$args['filter'] = $filter;
	if (isset($catid))
		$args['catid'] = $catid;

	$args = array_merge($args, $newargs);

	$implode = array();
	foreach ($args as $name => $value)
	{
		$implode[] = "$name=" . urlencode($value);
	}
	return $_SERVER['PHP_SELF'] . "?" . implode("&", $implode);
}

	$k = makeKey($userData['userid']);

	$thislink = replaceArgsLink(array());

	$otherlinks = array();
	if($userData['loggedIn'] && $userData['premium'])
	{
		$otherlinks[] = array("Create Forum", "forumcreateforum.php" . (isset($catid)? "?catid=$catid" : ""));
	}
	if ($userData['loggedIn'] && !isset($catid) && !$filter)
	{
		if ($onlysubs)
		{
			$otherlinks[] = array("Show All Forums", replaceArgsLink(array('onlysubs' => 0, 'k' => $k)));
		} else {
			$otherlinks[] = array("Show Only Subscriptions", replaceArgsLink(array('onlysubs' => 1, 'k' => $k)));
		}
	}
	if ($filter)
	{
		$otherlinks[] = array("Stop Filtering", replaceArgsLink(array('filter' => '')));
	}

function sortForumListing(&$catlist, $catid, $subscribed, $orderby)
{
	global $forums, $forumobjs, $userData;

	if (!isset($catlist[$catid]))
		return array();

	$forumlines = array_flip($catlist[$catid]);
	foreach ($forumlines as $fid => $fval)
	{
		if (isset($forumobjs[$fid])) // if forum no longer exists, drop it.
		{
			$forumlines[$fid] = $forumobjs[$fid];
			$forumlines[$fid]['subscribed'] = $subscribed;
		} else {
			unset($forumlines[$fid]);
			$forums->unInvite($userData['userid'], $fid);
		}
	}
	$forums->sortForums($forumlines, $orderby);
	return $forumlines;
}

	$orderbyoptions = array();
	$orderbyoptions[] = array('By Most Active', ($orderby != 'mostactive')? replaceArgsLink(array('orderby' => 'mostactive', 'k' => $k)) : '');
	$orderbyoptions[] = array('By Most Recent', ($orderby != 'mostrecent')? replaceArgsLink(array('orderby' => 'mostrecent', 'k' => $k)) : '');
	$orderbyoptions[] = array('By Name', ($orderby != 'alphabetic')? replaceArgsLink(array('orderby' => 'alphabetic', 'k' => $k)) : '');

	$singlecat = isset($catid);
	$template->set('catid', $catid);

	$collapsedcats = $forums->getCollapsedCategories($userData['userid']);

	$totalstr = array();
	$ajaxurl = array();
	$plusminus = array();
	$listCategoryLink = array();
	$categoryName = array();
	$forumlines = array();
	$official = array();
	$private = array();
	$class = array();
	$displayLine = array();
	$iscollapsed = array();
	$listForumsLink = array();

	foreach ($categories as $catid => $catinfo)
	{
		$displayCatID[$catid] = true;
		if (!isset($subforumlist['categories'][$catid]) && !isset($forumlist['categories'][$catid])) {
			$displayCatID[$catid] = false;
			continue;
		}

		$subforumlines = sortForumListing($subforumlist['categories'], $catid, 'y', $orderby);
		$forumlines[$catid] = sortForumListing($forumlist['categories'], $catid, 'n', $orderby);

		$forumlines[$catid] = $subforumlines + $forumlines[$catid];

		if (isset($forumlist['categories'][$catid]))
		{
			$shown = count($forumlines[$catid]);
			$total = $forumlist['totals'][$catid];
		} else {
			$shown = count($forumlines[$catid]);
			$total = $shown;
		}
		$totalstr[$catid] = "(Showing " . $shown . " of " . $total . ")";

		$ajaxurl[$catid] = "";
		if ($userData['loggedIn'])
			$ajaxurl[$catid] = $_SERVER['PHP_SELF'] . "?k=" . makeKey($userData['userid']);

		$iscollapsed[$catid] = !$singlecat && in_array($catid, $collapsedcats);
		$plusminus[$catid] = $iscollapsed[$catid]? "[+]" : "[-]";

		//only used when displaying a single category
		$listForumsLink = replaceArgsLink(array('catid' => -1));
		//links to the specific categories
		$listCategoryLink[$catid] = replaceArgsLink(array('catid' => $catid));

		$categoryName[$catid] = $categories[$catid]['name'];

		foreach ($forumlines[$catid] as $line)
		{
			$displayLine[$catid][$line['id']] = true;
			if ($line['subscribed'] == 'n' && in_array($line['id'], $subforumlist['forums'])) {
				$displayLine[$catid][$line['id']] = false;
				continue;
			}

			$official[$catid][$line['id']] = ($line['official'] == 'y'? "Official: " : "");
			$private[$catid][$line['id']] = ($line['public'] == 'n'? "Private: " : "");
			$class[$catid][$line['id']] = ($line['subscribed'] == 'y' ? 'body2' : 'body');
		}
	}

	$numonline = $forums->forumsNumOnline(0);


	$numpagesExists = isset($numpages);
	$pageList = array();
	if($numpagesExists){
		$pageList = pageList($thislink, $pagenum, $numpages, 'header');
	}

	$template->setMultiple(array(
		'thislink' => $thislink,
		'onlysubs' => $onlysubs,
		'filter' => $filter,
		'orderByOptionsLinkBar' => makeLinkBar($orderbyoptions, 'header2'),
		'otherLinksLinkBar' => makeLinkBar($otherlinks,'header2'),
		'totalstr' => $totalstr,
		'ajaxurl' => $ajaxurl,
		'plusminus' => $plusminus,
		'categories' => $categories,
		'listCategoryLink' => $listCategoryLink,
		'categoryName' => $categoryName,
		'forumlines' => $forumlines,
		'official' => $official,
		'private' => $private,
		'class' => $class,
		'displayCatID' => $displayCatID,
		'displayLine' => $displayLine,
		'iscollapsed' => $iscollapsed,
		'listForumsLink' => $listForumsLink,
		'numpagesExists' => $numpagesExists,
		'pageList' => $pageList,
		'numonline' => $numonline,
		'singlecat' => $singlecat
	));
	$template->display();
