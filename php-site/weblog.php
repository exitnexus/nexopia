<?

	$login=0;

	require_once("include/general.lib.php");
	$template = new template("weblog/weblog_main");
	// If newreplies=1, basically rewrite the request to show the oldest comment first.
	$newreplies = 0;

	$uid = getREQval('uid', 'int', ($userData['loggedIn'] ? $userData['userid'] : 0));
	$id = getREQval('id', 'int');

	if ($userData['loggedIn'] && ($newreplies = getREQval('newreplies', 'integer')))
	{
		// get the oldest new reply comment.
		$userblog = new userblog($weblog, $userData['userid']);
		$post = $userblog->getFirstUnreadReplyPost();
		if ($post)
		{
			$uid = $post['bloguserid'];
			$id = $post['blogid'];
		} else {
			$newreplies = 0; // don't bother doing any further checking, nothing to find.
		}
	}

	if (!$uid)
		die("Bad user");


	$page = getREQval('page', 'int');

	$isFriendViewAdmin = $userData['loggedIn'] && $mods->isAdmin($userData['userid'], 'viewfriendblogs');
	$isRegularAdmin = $userData['loggedIn'] && $mods->isAdmin($userData['userid'], 'editjournal');

	$user = getUserInfo($uid);

	if($userData['halfLoggedIn'] && $userData['userid'] == $uid){
		$isFriend = true;
	}else{
		$hidden = ($user['hideprofile'] == 'y' && isIgnored($uid, $userData['userid'], false, 0, true));
		$gone = (!$user || ($user['state'] == 'frozen' && !$mods->isAdmin($userData['userid'], 'listusers')));
		if($gone || $hidden)
		{
			if ($newreplies)
			{
				$userblog = new userblog($weblog, $uid);
				$entry = new blogpost($userblog, array(), $userData);

				// $entry is not really valid at this point, but we'll hack it a bit.
				// XXX: this is evil.
				$entry->entryid = $id;
				$entry->invalidateNewReplies($userData['userid']);
			}

			if ($gone)
			{
				incHeader();
				echo "Bad user";
				incFooter();
				exit;
			} else {
				incHeader();
				echo "This user is ignoring you.";
				incFooter();
				exit;
			}
		}

		$user['plus'] = $user['premiumexpiry'] > time();

		$isFriend = $isFriendViewAdmin || ($userData['halfLoggedIn'] && isFriend($userData['userid'], $uid));
	}

	$scope = WEBLOG_PUBLIC;
	if($userData['halfLoggedIn'])
		$scope = WEBLOG_LOGGEDIN;
	if($isFriend || $isFriendViewAdmin)
		$scope = WEBLOG_FRIENDS;
	if($userData['halfLoggedIn'] && $uid == $userData['userid'])
		$scope = WEBLOG_PRIVATE;

	$userblog = new userblog($weblog, $uid);

	$friendsview = getREQval('friendsview', 'integer');
	$calendarview = getREQval('calendarview', 'integer');
	if ($friendsview && $uid == $userData['userid'])
		$userblog->setLastReadTime();

	$year = getREQval('year', 'integer');
	$month = getREQval('month', 'integer');
	$day = getREQval('day', 'integer');

	$key = getREQval('k');

	$entry = new blogpost($userblog, array(), $userData);
	$userinfo = false;
	if ($id)
	{
		$entry = new blogpost($userblog, "$uid:$id", $userData);
		if (!$entry->entryid || $entry->scope > $scope || $entry->uid != $uid)
		{
			if ($newreplies)
			{
				//XXX: entry is valid at this point, but this is still evil.
                $entry->entryid = $id;
				$entry->invalidateNewReplies($userData['userid']);
			} else {
				$entry = null;
				$id = 0;
			}
		}
		if ($entry && (!$userData['halfLoggedIn'] || $entry->uid != $userData['userid']))
		{
			$userinfo = getUserInfo($entry->uid);
		}
	}

	switch ($action)
	{
		case 'Delete':
			if (($uid == $userData['userid'] || $isRegularAdmin) && $id){
				if($check = getPOSTval('checkID', 'array'))
				{
					$comments = blogcomment::getBlogComments($entry, $check);
					$deleted = blogcomment::deleteMulti($entry, $comments);

					$msgs->addMsg("Deleted $deleted comments.");

					if ($uid != $userData['userid'])
						$mods->adminLog('blog comment delete', "deleted blog comment ids (" . implode(',', $check) . ") from entry {$entry->entryid}");

				} else if (checkKey($id, $key)) {
					$entry->delete();
					$id = 0;

					$msgs->addMsg("Deleted Post");

					if ($uid != $userData['userid'])
						$mods->adminLog('blog entry delete', "deleted blog entry {$entry->entryid}");
				}
			}
			break;
		case "New":
			if($uid == $userData['userid'] && !$id)
				addBlogEntry($entry); //exit

			break;

		case "Edit":
			if(($uid == $userData['userid']) || ($isRegularAdmin && $id) || ($isFriendViewAdmin && $id))
				addBlogEntry($entry, false, true); //exit

			break;
		case "Post":

            $data = getPOSTval('data', 'array');
			$data['msg'] = getPostval('msg');
			if (!$id)
			{
				$dupe = $cache->get("blogpostdupe-$uid"); //should block fast dupes, like bots, use a short time since it blocks ALL posts by that user

				if($dupe){
					header("location: weblog.php?uid=$uid");
					exit;
				}
			}

			if($uid == $userData['userid'] || ($isRegularAdmin && $id)) //can't post new entry in someone elses blog, but can edit if admin
            {
				$entry = updateBlogEntry($entry, $data, !$id);
				if (!$id)
					$cache->put("blogpostdupe-$uid", 1, 30); //block dupes for 30 seconds

				$entry->commit();

				scan_string_for_notables($entry->msg->getText());

				if ($uid == $userData['userid']){
					if ((!$id) && ($entry->scope != WEBLOG_PRIVATE)){
						enqueue( "Blog", "create", $uid, array($uid, $entry->entryid) );
					} //else {
					//	enqueue( "Blog", "edit", $uid, array($uid, $id) );
					//}
				}

				$id = $entry->entryid;
				if ($uid != $userData['userid'])
					$mods->adminLog('blog entry edit', "edited blog entry {$entry->entryid}");
			}
			break;
		case "Preview":
			$data = getPOSTval('data', 'array');
			$data['msg'] = getPOSTval('msg');
			$entry = updateBlogEntry($entry, $data, false);
			addBlogEntry($entry, true);

			break;

		case "Post Reply":
			$rootid = getREQval('rootid', 'integer', -1);
			$parentid = getREQval('parentid', 'integer', -1);
			$msg = getPOSTval('msg');

			if($entry && $userData['loggedIn'] && $msg && $rootid > -1 && $parentid > -1 &&
			   !isIgnored($uid, $userData['userid'], 'comments', $userData['age']))
			{
				postNewReply($entry, $rootid, $parentid, $msg);
			}
			break;
	}

	$replies = array();
	if ($id && $entry)
	{
		if (!$newreplies)
		{
			$page = getREQval('page', 'integer', -1);
			$commentids = $entry->getTopLevelReplies();
			$commentidpages = array_chunk($commentids, $weblog->entriesPerPage);
			$replies['numpages'] = count($commentidpages);
			if ($page >= $replies['numpages'] || $page == -1)
				$page = $replies['numpages'] - 1;
			$replies['commentids'] = array(); // maps id -> depth

			if (isset($commentidpages[$page]))
			{
				$pageids = $commentidpages[$page];
				$replies['commenttrees'] = $entry->getReplyTrees($pageids);

				foreach ($pageids as $pageid)
				{
					$replies['commentids'] += parseCommentTree($replies['commenttrees'][$pageid], $pageid, 0);
				}
			}
			$replies['allids'] = array_unique( array_keys($replies['commentids']) );
			$replies['page'] = $page;

		} else {
			$replies['commenttrees'] = $entry->getNewReplies($userData['userid']);
			$replies['commentids'] = array();
			foreach ($replies['commenttrees'][0] as $commentroot)
			{
				$replies['commentids'] += parseCommentTree($replies['commenttrees'], $commentroot, 0);
			}
			$replies['allids'] = array_unique( array_keys($replies['commentids']) );
			$replies['page'] = 0;
			$replies['numpages'] = 1;
		}
		if ($userData['loggedIn'])
			$entry->invalidateNewReplies($userData['userid'], $replies['allids']);
	} else {
		if ($id && $newreplies) // if we get here and $newreplies is set, the post the replies were from must have been removed.
			$weblog->invalidateNewReplies($userData['userid'], $id);
	}


	$template->set("skin", injectSkin($user, 'blog'));
	$template->set("profilehead", incProfileHead($user));

    $template->set("uid", $uid);

	$linkbar = array();
	if ($uid == $userData['userid'])
		$links[] = array("Post New Entry", "weblog.php?uid=$uid&action=New");
	$myblogname = "My";
	if ($uid != $userData['userid'])
		$myblogname = $user['username'] . "'s";
	$links[] = array("$myblogname Calendar", ($id || !$calendarview? "weblog.php?uid=$uid&calendarview=1" : ""));
	$links[] = array("$myblogname Blog Entries", ($id || $friendsview || $calendarview? "weblog.php?uid=$uid&friendsview=0" : ""));

	$numnew = "";
	if (!$friendsview && $uid == $userData['userid'])
	{
		$lastupdated = $userblog->getLastRead();
		if ($lastupdated['postcount'])
			$numnew = " ($lastupdated[postcount] New)";
	}
	$links[] = array("$myblogname Friends' Entries$numnew", ($id || !$friendsview? "weblog.php?uid=$uid&friendsview=1" : ""));
    $template->set("linkbar", makeLinkBar($links, 'header2'));

	$commententrystyle = "";
	if (!getREQval('showreply', 'integer'))
	{
		$commententrystyle = ' style="display: none;"';
	}
    $template->set("loggedin",$userData['halfLoggedIn'] );
    $template->set("commentstyle", $commententrystyle);
	$ignored = isIgnored($uid, $userData['userid'], 'comments', (isset($userData['age'])? $userData['age'] : 0));
    $template->set("ignored", $ignored);
    $template->set("id", ($id? $id : 0));

	$editbox = '';
    if($userData['halfLoggedIn'] && !$ignored)
        $editbox =  editBoxStr("");

	$smileypics = substr($editbox, strpos($editbox,'var'), strpos($editbox,';')+ 1 - strpos($editbox, 'var') );

	$editbox = str_replace( $smileypics, '',$editbox);
	$smileycodes = substr($editbox, strpos($editbox,'var'), strpos($editbox, ');')+ 2 - strpos($editbox,'var'));

	$editbox = str_replace( $smileycodes, '',$editbox);
	$smileyloc =  substr($editbox, strpos($editbox, 'var'), strpos($editbox,';')+ 1 - strpos($editbox, 'var'));

	$smilies_javascript = $smileypics . $smileycodes . $smileyloc;
	$editbox_javascript = substr($editbox, strpos($editbox, 'editBox'), strpos($editbox,')')+ 1 - strpos($editbox, 'editBox')) . ';';
	$template->set('smilies',$smilies_javascript);
	$template->set("editbox", $editbox_javascript);

	if($id)
    {
        $entry = showEntry($uid, $entry, $entry->getReplyCount(), $userinfo, true, $replies);
        $template->set("entry", $entry);
        $template->set("calendars","" );
        $template->set("entries_pagelist", "");
        $template->set("listentries", ""  );
    }
	else if ($calendarview)
    {
        $cal = showCalendars($uid, $year, $month);
         $template->set("calendars",$cal );
         $template->set("entries_pagelist", "");
         $template->set("listentries", ""  );
         $template->set("entry", "");
    }
	else
    {
         $entries_pagelist = "";
    	 $entries = listEntries($uid, $friendsview, $year, $month, $day, $entries_pagelist);
    	 $template->set("entries_pagelist", $entries_pagelist);
         $template->set("listentries", $entries  );
         $template->set("calendars",'' );
         $template->set("entry", "");
    }



    $template->display();




function splitItemsByDay($items)
{
	$days = array();
	foreach ($items as $itemid => $time)
	{
		$day = userdate('d', $time) + 0;
		if (!isset($days[$day]))
			$days[$day] = array();

		$days[$day][$itemid] = $time;
	}
	return $days;
}


function showCalendars($uid, $year, $month)
{
	global $weblog, $userblog, $scope;
    $template = new Template("weblog/weblog_calendar");
	$firstposttime = $userblog->getFirstPostTime($scope);
	$firstpostyear = gmdate('Y', $firstposttime);
	$lastposttime = $userblog->getLastPostTime($scope);
	$lastpostyear = gmdate('Y', $lastposttime);

	if (!$firstposttime || !$lastposttime)
		return;

	$validyears = range($firstpostyear, $lastpostyear);

	if (!in_array($year, $validyears))
	{
		$year = $lastpostyear;
	}

	$yearbar = array();

	foreach ($validyears as $validyear)
	{
		$yearbar[] = array($validyear, ($month || $validyear != $year? "$_SERVER[PHP_SELF]?uid=$uid&calendarview=1&year=$validyear" : ""));
	}
	$template->set("yearlinkbar" , makeLinkBar($yearbar, 'header2'));
	if ($month)
		$months = array($month);
	else
		$months = range(1, 12);

	$monthlists = $userblog->getMonthPostList($scope, $year, $months);
	$weekdays = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');

	$months = array();
	foreach ($monthlists as $month => $items)
	{
	    $value = array();
	    $value['month'] = $month;
	    $value['date'] = gmdate('F', gmmktime(0, 0, 0, $month, 1, $year));
	    $weekdays = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
        $value['weekdays'] =   $weekdays;
        $days = splitItemsByDay($items);

        $calendar = getCalendar($year, $month);
        foreach ($calendar as &$week)
        {
            foreach ($week as &$day)
            {
                $day_array = array();
                $day_array ['day'] = $day;
                $day_array['is_false']  = ($day === false);
                $day_array['class'] = (($day === false) ? 'header2' : 'body');

                if ($day !== false)
                {
                    $day_array['exists'] = isset($days[$day]);
                }
                $day = $day_array;
            }
	   }
	   $value['calendar'] = $calendar;
	   array_push($months, $value);

	}

	$template->set("year", $year);
    $template->set("months", $months);
    $template->set("uid", $uid);

	return $template->toString();
}


function listEntries($uid, $friendsview, $year, $month, $day, &$entries_pagelist){
	global $weblog, $userblog, $scope, $page, $user, $userData, $isFriend;

	if ($friendsview)
	{
		$flist = getFriendsListIDs($uid, USER_FRIENDS);
		// note, we want to know who has friended the READER, not the owner of the
        // blog being viewed.
		$mutualids = getFriendsListIDs($userData['userid'], USER_FRIENDOF);
		// get their userblog objects
        $userblogs = array();
		foreach ($flist as $friendid)
		{
			$userblogs[$friendid] = new userblog($weblog, $friendid);
		}
		$multiuserblog = new multiuserblog($weblog, $userblogs, $userData['userid'], $mutualids, WEBLOG_PUBLIC);

		$pageitems = $multiuserblog->getPostList($page);
		$uids = array_keys($flist);
		$userobjs = getUserInfo($uids);
		$total = $multiuserblog->getPostCount();
		$numpages = ceil($total / $weblog->entriesPerPage);

	} else if ($year && $month && $day) {

		$monthitems = $userblog->getMonthPostList($scope, $year, $month);
		$byday = splitItemsByDay($monthitems);

		if (isset($byday[$day]))
			$pageitems = $byday[$day];
		else
			$pageitems = array();
		$uids = array($uid);
		$userobjs = array($uid => false);
		$total = count($pageitems);
		$numpages = 1;

	} else {
		$pageitems = $userblog->getPostList($page, $scope);
		$userobjs = array($uid => false); // don't show it.
        $total = $userblog->getPostCount($scope);
		$numpages = ceil($total / $weblog->entriesPerPage);
	}

	$entries = blogpost::getBlogPosts($weblog, array_keys($pageitems));
	blogpost::getParsedTextMulti($entries);
	$replycounts = blogpost::getReplyCountMulti($weblog, $entries);
    $displaystring = "";
	foreach($pageitems as $postid => $time){
		if ($entries[$postid])
		{
			$userid = $entries[$postid]->uid;
			$displaystring .= showEntry($userid, $entries[$postid], $replycounts[$postid], (isset($userobjs[$userid])?$userobjs[$userid] : $userid), false);
		}
	}

	$entries_pagelist =  pageList("$_SERVER[PHP_SELF]?uid=$uid&friendsview=$friendsview", $page, $numpages, 'header');

	return $displaystring;
}

function parseCommentTree(&$commenttree, $currentid, $currentdepth)
{
	$result = array();
	$result[$currentid] = $currentdepth;
	if (isset($commenttree[$currentid]))
	{
		foreach ($commenttree[$currentid] as $childid)
		{
			$result += parseCommentTree($commenttree, $childid, $currentdepth + 1);
		}
	}
	return $result;
}

function getUserColumn($userid, &$authors, &$usernames, $showbloglink = false)
{
	global $config;

	$result = "<td class=body valign=top nowrap>";

	if(isset($authors[$userid])){
		$result .= "<a class=body href=/profile.php?uid={$userid}><b>" . $authors[$userid]['username'] . "</b></a>";
		if ($showbloglink)
			$result .= "<br/><a class=body href=/weblog.php?uid={$userid}>View Blog</a>";
		$author = $authors[$userid];
	}else{
		$result .= "<b>" . $usernames[$userid] . "</b><br>deleted account";
		$userid = 0;
	}

	$result .= "<br>";

	if($userid && $author['online'] == 'y')
		$result .= "- Online -<br>";

	if($userid && $author['firstpic'])
		$result .= "<a class=header href=/profile.php?uid={$userid}><img src=" . $config['thumbloc'] . floor($author['userid']/1000) . "/" . weirdmap($author['userid']) . "/$author[firstpic].jpg border=0></a><br>";

	if($userid)
		$result .= "<br>Age <i>$author[age]</i>, $author[sex]<br>";

	$result .= "</td>";
	return $result;
}

function showEntry($uid, $entry, $commentcount, $userinfo, $showcomments, $replies = array()){

    global $weblog, $userblog, $scope, $user, $userData, $action, $isRegularAdmin, $isFriendViewAdmin, $config, $isFriend, $msgs, $cache, $newreplies;
    $template = new template("weblog/weblog_entry");
	$id = $entry->entryid;
	$key = makeKey($id);

	if(!$entry)
		die("Bad Entry");

	$authorids = array();
	$authors   = array();
	$usernames = array();
	$comments = array();
	if ($showcomments)
	{
		extract($replies);
		$comments = blogcomment::getBlogComments($entry, $allids);

		if(count($comments)){
			foreach($comments as $line)
			{
				if (!$userinfo || $line->userid != $uid)
					$authorids[$line->userid] = $line->userid;
			}
		}
	}

	if ($authorids)
		$authors = getUserInfo($authorids);

	$missing = array_diff($authorids, array_keys($authors));
	if ($missing)
		$usernames = getUserName($missing);
	if (is_array($userinfo))
		$authors[$userinfo['userid']] = $userinfo;
	else if ($userinfo)
		$usernames[$userinfo] = getUserName($userinfo);
    $entry_array = array();
    $template->set("is_curr_user", $uid == $userData['userid']);
    $template->set("uid",$uid);
    $template->set("id", $id);
    $template->set("loggedin",$userData['halfLoggedIn'] );
    $template->set("user_is_admin", $uid == $userData['userid'] || $isRegularAdmin);

    $template->set("mod_userabuse", MOD_USERABUSE);
    $entry_array['key'] = $key;
    $entry_array['userinfo'] = $userinfo;
    $entry_array['title'] = $entry->title;


	if ($userinfo){

        $entry_array['usercolumn'] = getUserColumn($uid, $authors, $usernames, true);
    }
    else
        $entry_array['usercolumn'] = '';

	$entry_array['entrytext'] = $entry->getParsedText();
    $entry_array['entryid'] = $entry->entryid;
    $entry_array['scope']   = $weblog->scopes[$entry->scope] ;
    $entry_array['time']    = $entry->time;
    $entry_array['allow_comments' ] = $userData['loggedIn'] && ($entry->allowcomments == 'y');
    $entry_array['uid'] = $entry->uid;
    $entry_array['show_comments'] = $showcomments;
    $entry_array['comment_count']= 0;
	if (!$showcomments && $entry->allowcomments == 'y')
	{
		if ($commentcount)
		  $entry_array['comment_count']= $commentcount;
    }


	$entry_array['count_commentids'] = count($comments);
	if($entry->allowcomments == 'y' && $showcomments)
    {
        $entry_array['count_commentids'] = count($comments);
        $entry_array['show_comments']= true;
        $entry_array['comments_newreplies'] = $newreplies;

        if(count($commentids)){
			$pagelist = pageList($_SERVER['PHP_SELF']."?uid=$uid&id=$id", $page, $numpages, 'header');
            $entry_array['comment_pagelist'] = $pagelist;
            $comments_array = array();

			foreach($commentids as $commentid => $depth){
 				if (!isset($comments[$commentid]))
					continue;
                $line = $comments[$commentid];
                $comment_array = array();
                $comment_array['commentid'] = $line->commentid;
                $width = 100 - ($depth * 2);
				if ($width < 50) $width = 50;
				$comment_array['width'] = $width;
				$comment_array['ignorekey'] = makeKey($line->userid);


				if ($line->deleted == 'f')
				{
				    $comment_array['deleted'] = false;
				    $comment_array['user_column'] = getUserColumn($line->userid, $authors, $usernames) ;
                    $comment_array['text'] = $line->getParsedText();
				} else {
					$comment_array['deleted'] = true;
				    $comment_array['user_column'] = "";
                    $comment_array['text'] = "";
				}
				if ($line->deleted == 'f')
				{
                    $comment_array['time'] = $line->time;

					$links = array();

					if($userData['halfLoggedIn'] && $line->userid){
					   $comment_array['show_user_links'] = true;
						$rootid = $line->rootid? $line->rootid : $line->commentid; // if no rootid set, use the commentid
						$comment_array['rootid'] = $rootid ;
                        $comment_array['userid'] = $line->userid;
					}
					else
					{
                        $comment_array['show_user_links'] = false;
                        $comment_array['rootid'] = "";
                        $comment_array['userid'] = "";
                    }

				} else {
					$comment_array['time'] = $line->time;
					$comment_array['show_user_links'] = false;
					$comment_array['rootid'] = "";
                    $comment_array['userid'] = "";
				}
				array_push($comments_array, $comment_array);
			}
			$entry_array['comments'] = $comments_array;
		}
	}else {
		$entry_array['comments'] = array();
		$entry_array['showcomments'] = $showcomments;
		$entry_array['count_commentids'] = 0;
        $entry_array['comments_newreplies'] = $newreplies;
        $entry_array['comment_pagelist'] = "";
	}

	$template->set('entry' , $entry_array);

	return $template->toString();
}

function addBlogEntry($line, $preview = false, $edit=false){
	global $uid, $userData, $weblog, $mods;
    if (!$line) {
    	incHeader();
    	echo "Invalid blog entry.";
    	incFooter();
    	exit;
    }
    $template = new Template("weblog/add_weblogentry");
	$title = "";
	$msg = "";
	$scope = 0;
	$category = 0;
	$allowcomments = 'n';

	$id = $line->entryid;

    $template->set("preview", $preview);

	if($preview)
    {
		$ntitle = $line->title;
		$nmsg = $line->getParsedText();
		$template->set("ntitle", $ntitle);
		$template->set('nmsg', $nmsg);
	}


	$template->set("id", $id);
	$template->set("line_entryid", $line->entryid);
	$template->set("uid", $uid);
	$template->set("is_curr_user", $uid == $userData['userid'] ||
		($edit && $mods->isAdmin($userData['userid'], "editjournal")) ||
		$mods->isAdmin($userData['userid'], "viewfriendblogs")
	);
    $template->set("line_title", $line->title);
    $template->set("select_list_scopes" ,make_select_list_key($weblog->scopes, $line->scope));
    $template->set("checkbox_resettime", makeCheckBox('data[time]', 'Reset Time'));
    $template->set("checkbox_allowcomments", makeCheckBox('data[allowcomments]', 'Allow Comments', ($line->allowcomments != 'n')));

    $template->set("editbox", editBoxStr($line->getText()));
    $template->display();
    exit;
}

function updateBlogEntry($blogentry, $data, $notify){
	global $weblog, $uid, $msgs, $userblog;

	$defaults = Array(
		'title' => "",
		'msg' => "",
		'scope' => 0,
		'time' => false,
		'allowcomments' => false,
	);

	$flistdelta = 0;
	$existingpost = false;

	$iserror = false;

	extract(setDefaults($data, $defaults));

	if(!isset($weblog->scopes[$scope]))
		$scope = WEBLOG_PUBLIC;

	$ntitle = trim($title);

	if($ntitle == ""){
		$msgs->addMsg("You must insert a title");
		$iserror = true;
	}


	$nmsg = trim($msg);

	if(!$iserror && $nmsg == ""){
		$msgs->addMsg("You must input some text");
		$iserror = true;
	}

	if (!$iserror && strlen($nmsg) > 100000)
	{
		$msgs->addMsg("Post Body was too long");
		$iserror = true;
	}
	if (!$iserror && !spamfilter($ntitle))
	{
		$msgs->addMsg("Post Title failed spam check");
		$iserror = true;
	}

	if($blogentry){
        $blogentry->setTitle($ntitle);
		$blogentry->setText($nmsg);
		if (isset($scope))
			$blogentry->setScope($scope);
		if (isset($time) && $time)
		{
			$blogentry->setTime(time());
			$flistdelta = 1;
			$existingpost = true;
		}
		if (isset($allowcomments))
			$blogentry->setAllowComments($allowcomments);

		if (!$blogentry->entryid)
			$flistdelta = 1;
	}else{
		$blogentry = new blogpost($userblog, array(
			'title' => $ntitle,
			'msg' => $nmsg,
			'scope' => $scope,
			'allowcomments' => ($allowcomments? 'y' : 'n')), $userData
		);
		$flistdelta = 1;
	}

	if ($iserror)
		addBlogEntry($blogentry, true); //exit

	if ($notify && $flistdelta && $scope < WEBLOG_PRIVATE)
	{
		$mutualids = getFriendsListIDs($uid, USER_FRIENDOF);
		if ($scope == WEBLOG_FRIENDS)
		{
			$flist = array_keys(getFriendsListIDs($uid, USER_FRIENDS));
			$mutualids = array_intersect($mutualids, $flist);
		}

		userblog::incrementLastReadPostsMulti($weblog, $blogentry->time, $existingpost, $mutualids);
	}

	return $blogentry;
}

function postNewReply($entry, $rootid, $parentid, $msg)
{
	global $userData, $uid, $cache, $msgs;
	$dupe = $cache->get("blogpostdupe-$uid-{$entry->entryid}"); //should block fast dupes, like bots, use a short time since it blocks ALL posts by that user

	if($dupe){
		$msgs->addMsg("Too many replies too quickly.");
	} else if (!spamfilter($msg)) {

		$msgs->addMsg("Reply Body failed spam check");
	} else {
		$newcomment = new blogcomment($entry, array(
			'rootid' => $rootid,
			'parentid' => $parentid,
			'userid' => $userData['userid'],
			'time' => time(),
			'msg' => $msg)
		);
		$cache->put("blogpostdupe-$uid-{$entry->entryid}", 1, 10); // block dupes for 5 seconds.
		$newcomment->commit();

		$replytocomment = false;
		if ($parentid)
			$replytocomment = new blogcomment($entry, $parentid);

		scan_string_for_notables($newcomment->msg->getText());

		$entry->notifyReply($replytocomment, $newcomment);
	}
}
