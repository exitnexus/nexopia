<?php
	$login = 1;

	require_once "include/general.lib.php";

	$fid = getREQval('fid', 'integer', 0);
	if (empty($fid))
		die("Bad Forum ID");

	$perms = $forums->getForumPerms($fid);	//checks it's a forum, not a realm, users permissions here, and column info

	if(!$perms['view'])
		die("You don't have permission to subscribe this forum");

	$personalcat = $forums->getForumCategory($perms['cols'], $userData['userid'], false);;
	$publiccat = $perms['cols']['categoryid'];
	$cats = $forums->getCategories($userData['userid']);

	$catid = getPOSTval('catid', 'integer', -1);
	$catname = getPOSTval('catname');
	if ($catname)
	{
		$catid = -1;
		$catname = htmlentities($catname);
		// see if there is one by this name already
		foreach ($cats as $key => $val)
		{
			if (strcasecmp($val['name'], $catname) == 0)
			{
				$catid = $val['id'];
				break;
			}
		}
		if ($catid == -1) // did not find a category above
		{
			$catid = $forums->createCategory($catname, false, $userData['userid'], 0);
			$forums->invite($userData['userid'], $fid, $catid);
			header("Location: /forumthreads.php?fid=$fid");
			exit();
		}
	}
	if ($catid != -1)
	{
		// check that the category is valid and can be used.
		if (isset($cats[$catid]) && $cats[$catid]['official'] != 'y')
		{
			$forums->invite($userData['userid'], $fid, $catid);
			header("Location: /forumthreads.php?fid=$fid");
			exit();
		}
		$msgs->addMsg("Category specified does not exist");
	}
	$outputcats = array(0 => 'Use Owner Set Category: ' . $cats[$publiccat]['name']);
	foreach ($cats as $catid => $cat)
	{
		if ($cat['official'] != 'y' && $catid != 0)
			$outputcats[$catid] = $cat['name'];
	}

	$template = new template('forums/forumsubscribe');
	$template->set('categorySelectList', make_select_list_key($outputcats, $personalcat));
	$template->set('perms', $perms);
	$template->set('fid', $fid);
	$template->display();
