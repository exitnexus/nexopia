<?

define("WEBLOG_PUBLIC",		1);
define("WEBLOG_LOGGEDIN",	2);
define("WEBLOG_FRIENDS",	3);
define("WEBLOG_PRIVATE",	4);

class weblog {

	public $db;
	public $scopes;
	public $entriesPerPage;

	function __construct( & $db ){
		$this->db = & $db;

 		$this->scopes = array( 	WEBLOG_PUBLIC	=> "Public",
 								WEBLOG_LOGGEDIN	=> "Logged-in only",
								WEBLOG_FRIENDS	=> "Friends only",
								WEBLOG_PRIVATE	=> "Private" );

		$this->entriesPerPage = 25;
	}

	function invalidateNewReplies($uid, $ids)
	{
		global $cache;

		if (is_array($ids) && !$ids)
			return 0;

		if (!is_array($ids))
			$ids = array($ids);

		$this->db->prepare_query("DELETE FROM blogcommentsunread WHERE userid = % AND blogid IN (#)", $uid, $ids);
		$deleted = $this->db->affectedrows();
		if ($deleted)
		{
			foreach ($ids as $id)
				$cache->decr("weblog-newreplies-$uid-$id", $deleted);
			$cache->decr("weblog-newreplies-$uid-total", $deleted);
		}
		return $deleted;
	}
}

class blogpost
{
	// memcache keys used by this class:
    // weblog-parsed-$postid
    //  the parsed message text of blog post $postid
    // weblog-entry-$postid
    //  the blogpost data for a particular entry (not including the parsed text)
    // weblog-entry-replycount-$postid
    //  the number of replies to $postid
    // weblog-entry-replies-$postid-toplevel
    //  the reply ids of all of the top level items for $postid.
    // weblog-entry-replies-$postid-$rootid
    //  a tree of reply ids to the given root item id.
    // weblog-newreplies-$uid-$postid
    //  the number of replies in $postid that are somehow in reply to $uid
    // weblog-newreplies-$uid-total
    //  the number of replies in total that are somehow in reply to $uid. (also used by userblog)

	public $userblog;
	public $db;
	public $uid;

	public $entryid; // false if new
	public $title;
	public $time;
	public $scope;
	public $allowcomments;
	public $msg;

	public $originalscope;
	public $fieldschanged;

	// if $entrydata is not an array, it is a postid to fetch from the database
	function __construct($userblog, $entrydata, $userData)
	{
		$this->db = $userblog->db;

		if (!is_array($entrydata))
		{
			$entrydata = self::getRawBlogPosts($userblog, array("$entrydata"));
			$entrydata = array_shift($entrydata);
		}

		if (!isset($entrydata['userid']))
		{
			$this->uid = $userblog->uid;
			$this->userblog = $userblog;
		} else if ($userblog->uid != $entrydata['userid']) {
			$this->uid = $entrydata['userid'];
			$this->userblog = new userblog($userblog->weblog, $this->uid);
		} else {
			$this->uid = $userblog->uid;
			$this->userblog = $userblog;
		}

		$this->entryid = isset($entrydata['id']);
		if ($this->entryid)
			$this->entryid = $entrydata['id'];

		$this->title = '';
		$this->setTitle(isset($entrydata['title'])? truncate($entrydata['title'], 128) : '');

		$this->time = isset($entrydata['time'])? $entrydata['time'] : time();
		$this->scope = isset($entrydata['scope'])? $entrydata['scope'] : 1;
		$this->allowcomments = isset($entrydata['allowcomments'])? $entrydata['allowcomments'] : 'y';

		$this->msg = new textparser(isset($entrydata['msg'])? $entrydata['msg'] : '');

		$this->originalscope = $this->scope;
		$this->fieldschanged = array();
	}

    // $weblog can be any object with a $db member
	private static function getRawBlogPosts($weblog, $postids)
	{
		global $cache;
		if (!$postids)
			return array();

		$posts = $cache->get_multi($postids, 'weblog-post-');
		$missing = array_diff($postids, array_keys($posts));
		if ($missing)
		{
			$keys = array('userid' => '%', 'id' => '#');
			$result = $weblog->db->prepare_query("SELECT * FROM blog WHERE ^",
				$weblog->db->prepare_multikey($keys, $missing));

			while ($data = $result->fetchrow())
			{
				$posts["$data[userid]:$data[id]"] = $data;
				$cache->put("weblog-post-$data[userid]:$data[id]", $data, 24*60*60);
			}
		}
		return $posts;
	}

	// $weblog MUST be the real $weblog object
	public static function getBlogPosts($weblog, $postids)
	{
		global $userData;
		$posts = self::getRawBlogPosts($weblog, $postids);
		foreach ($posts as $id => $obj)
		{
			$posts[$id] = new blogpost( new userblog($weblog, $obj['userid']), $obj, $userData);
		}
		return $posts;
	}

	function setTitle($newtitle)
	{
		$newtitle = removeHTML($newtitle);
		$newtitle = truncate($newtitle, 128);
		if ($this->title != $newtitle)
		{
			$this->title = $newtitle;
			$this->fieldschanged['title'] = $newtitle;
			return true;
		}
		return false;
	}

	function setScope($scope)
	{
		if ($this->scope != $scope)
		{
			$this->scope = $scope;
			$this->fieldschanged['scope'] = $scope;
		}
	}

	static function getParsedTextMulti($items)
	{
		$msgitems = array();
		foreach ($items as $id => $item)
		{
			$msgitems[$id] = &$item->msg;
		}
		return textparser::getParsedTextMulti($msgitems, 'weblog-parsed-');
	}

	function getParsedText()
	{
		return $this->msg->getParsedText("{$this->uid}:{$this->entryid}", 'weblog-parsed-');
	}
	function getText()
	{
		return $this->msg->getText();
	}

	// need to
	function setText($msg)
	{
		if ($this->msg->setText($msg))
		{
			$this->fieldschanged['msg'] = $this->msg->getText();
			return true;
		}
		return false;
	}


	function setTime($newtime)
	{
		if ($newtime != $this->time)
		{
			$this->time = $newtime;
			$this->fieldschanged['time'] = $this->time;
			$this->fieldschanged['year'] = gmdate("Y", $this->time);
			$this->fieldschanged['month'] = gmdate("m", $this->time);
			return true;
		}
		return false;
	}

	function setAllowComments($yesno)
	{
		if ($yesno != 'y' && $yesno != 'n')
			$yesno = ($yesno? 'y' : 'n');

		if ($yesno != $this->allowcomments)
		{
			$this->allowcomments = $yesno;
			$this->fieldschanged['allowcomments'] = $this->allowcomments;
		}
		return false;
	}


	function invalidate()
	{
		global $cache;
		$cache->remove("weblog-parsed-{$this->uid}:{$this->entryid}");
		$cache->remove("weblog-post-{$this->uid}:{$this->entryid}");
	}

	function replyCountChange($delta, $rootids)
	{
		global $cache;

		if (!is_array($rootids))
			$rootids = array($rootids);
		foreach ($rootids as $rootid)
		{
			$cache->remove("weblog-entry-replies-{$this->uid}:{$this->entryid}-" . ($rootid? $rootid : 'toplevel'));
		}

		return $cache->incr("weblog-entry-replycount-{$this->uid}:{$this->entryid}", $delta);
	}

	static function getReplyCountMulti($weblog, $blogentries)
	{
		global $cache;

		$ids = array();
		foreach ($blogentries as $entry)
		{
			$ids[] = $id = array($entry->uid, $entry->entryid);
		}
		$missing = array();
		$counts = $cache->get_multi_missing($ids, "weblog-entry-replycount-", $missing);
		if ($missing)
		{
			// get the ones that actually have comments.
			$keys = array('bloguserid' => '%', 'blogid' => '#');
			$res = $weblog->db->prepare_query("SELECT bloguserid, blogid, COUNT(*) AS count FROM blogcomments WHERE ^ GROUP BY blogid ORDER BY blogid",
				$weblog->db->prepare_multikey($keys, $missing));

			while ($row = $res->fetchrow())
			{
				$counts["$row[bloguserid]:$row[blogid]"] = $row['count'];
				$cache->put("weblog-entry-replycount-$row[bloguserid]:$row[blogid]", $row['count'], 7*24*60*60);
			}
			// get the ones that don't.
			foreach ($ids as $id)
			{
				$flatid = implode(':',$id);
				if (!isset($counts[$flatid]))
				{
					$counts[$flatid] = 0;
					$cache->put("weblog-entry-replycount-$flatid", 0, 7*24*60*60);
				}
			}
		}
		return $counts;
	}

	function getReplyCount()
	{
		$arr = self::getReplyCountMulti($this->userblog->weblog, array($this));
		return array_shift($arr);
	}

	function notifyReply($replyto, $comment)
	{
		global $cache;

		$notifyuids = array();
		if ($comment->userid != $this->uid)
		{
			$notifyuids[] = $this->uid;
		}
		if ($replyto && $replyto->userid != $comment->userid)
			$notifyuids[] = $replyto->userid;

		$replytoid = ($replyto? $replyto->commentid : 0);

		foreach (array_unique($notifyuids) as $uid)
		{
			$cache->incr("weblog-newreplies-{$uid}-total");
			$cache->incr("weblog-newreplies-{$uid}-{$this->entryid}");
			$this->db->prepare_query("INSERT INTO blogcommentsunread (userid, bloguserid, blogid, replytoid, commentid, time) VALUES (%, #, #, #, #, #)",
				$uid, $this->uid, $this->entryid, $replytoid, $comment->commentid, $comment->time);
		}
	}

	static function getNewReplyCountMulti($uid, $blogentries)
	{
		global $cache;

		$entryids = array();
		foreach ($blogentries as $entry)
		{
			$entryids[] = $entry->entryid;
		}

		$entryreplies = $cache->get_multi($entryids, "weblog-newreplies-$uid-");
		$missing = array_diff($entryids, array_keys($entryreplies));
		if ($missing)
		{
			$res = $this->db->prepare_query("SELECT blogid, COUNT(*) AS count FROM blogcommentsunread WHERE userid = %, blogid IN (#) GROUP BY blogid", $uid, $missing);
			while ($result = $res->fetchrow())
			{
				$entryreplies[ $result['blogid'] ] = $result['count'];
			}
			foreach ($missing as $put)
			{
				$cache->put("weblog-newreplies-$uid-$put", (isset($entryreplies[$put])? $entryreplies[$put] : 0), 24*60*60);
			}
		}
		return $entryreplies;
	}

	function getNewReplyCount($uid)
	{
		$replycount = self::getNewReplyCountMulti($uid, array($this));
		if (isset($replycount[$this->entryid]))
			return $replycount[$this->entryid];
		else
			return 0;
	}

	function getNewReplies($uid)
	{
		$pagesize = $this->userblog->weblog->entriesPerPage;
		$res = $this->db->prepare_query("SELECT replytoid, commentid FROM blogcommentsunread WHERE userid = % AND blogid = # ORDER BY time ASC LIMIT #", $uid, $this->entryid, $pagesize);
		$commenttree = array(0 => array());
		while ($result = $res->fetchrow())
		{
			$replytoid = $result['replytoid'];
			$commentid = $result['commentid'];

			if (!isset($commenttree[$replytoid]))
				$commenttree[$replytoid] = array();

			if ($replytoid)
				$commenttree[0][] = $replytoid;
			$commenttree[$replytoid][] = $commentid;
		}

		return $commenttree;
	}

	function invalidateNewReplies($uid, $ids = false)
	{
		global $cache;

		if (is_array($ids) && !$ids)
			return 0;

		if (is_array($ids))
		{
			$this->db->prepare_query("DELETE FROM blogcommentsunread WHERE userid = % AND blogid = # AND commentid IN (#)", $uid, $this->entryid, $ids);
		} else {
			$this->db->prepare_query("DELETE FROM blogcommentsunread WHERE userid = % AND blogid = #", $uid, $this->entryid);
		}
		$deleted = $this->db->affectedrows();
		if ($deleted)
		{
			$cache->decr("weblog-newreplies-$uid-{$this->entryid}", $deleted);
			$cache->decr("weblog-newreplies-$uid-total", $deleted);
		}
		return $deleted;
	}

	function getTopLevelReplies()
	{
		global $cache;
		$replies = $cache->get("weblog-entry-replies-{$this->uid}:{$this->entryid}-toplevel");
		if (!is_array($replies))
		{
			$res = $this->db->prepare_query("SELECT id FROM blogcomments WHERE bloguserid = % AND blogid = # AND rootid = 0 ORDER BY time ASC", $this->uid, $this->entryid);

			$replies = array();
			while ($reply = $res->fetchrow())
			{
				$replies[] = $reply['id'];
			}
			$cache->put("weblog-entry-replies-{$this->uid}:{$this->entryid}-toplevel", $replies, 24*60*60);
		}
		return $replies;
	}

	// array returned is of the format:
    // array($rootid =>
	//  array($parentid => array($childid...)
    //        ...,
    //        'allchildren' => array($childid...)
    //  )
    //  ...
    //  'allchildren' => array($childid...)
    // )
    // The children are always in order by id.
	function getReplyTrees($toplevelids)
	{
		global $cache;
		$replies = $cache->get_multi($toplevelids, "weblog-entry-replies-{$this->uid}:{$this->entryid}-");
		$missing = array_diff($toplevelids, array_keys($replies));
		if ($missing)
		{
			$this->db->prepare_query("SELECT id, rootid, parentid FROM blogcomments WHERE bloguserid = % AND blogid = # AND rootid IN (#) ORDER BY time ASC", $this->uid, $this->entryid, $toplevelids);

			$replies = array('allchildren' => array());
			while ($row = $this->db->fetchrow())
			{
				$parentid = $row['parentid'];
				$rootid = $row['rootid'];
				$id = $row['id'];

				if (isset($replies[$rootid]) && isset($replies[$rootid][$parentid]))
					$replies[$rootid][$parentid][] = $id;
				else if (isset($replies[$rootid]))
					$replies[$rootid][$parentid] = array($id);
				else
					$replies[$rootid] = array($parentid => array($id), 'allchildren' => array());

				$replies[$rootid]['allchildren'][] = $id;
				$replies['allchildren'][] = $id;
			}
			foreach ($missing as $rootid)
			{
				$tree = array();
				if (isset($replies[$rootid]))
					$tree = $replies[$rootid];
				$cache->put("weblog-entry-replies-{$this->uid}:{$this->entryid}-$rootid", $tree, 24*60*60);
			}
		}
		return $replies;
	}

	function commit()
	{
		global $cache, $google;
		$affected = 0;
		if ($this->entryid === false) // insert
		{
			$entryid = $this->db->getSeqId($this->uid, DB_AREA_WEBLOG_ENTRY);

			$this->db->prepare_query("INSERT INTO blog (id, userid, time, year, month, title, scope, allowcomments, msg) VALUES (#, %, #, #, #, ?, #, ?, ?)",
				$entryid, $this->uid, $this->time, gmdate('Y', $this->time), gmdate('m', $this->time), $this->title, $this->scope, $this->allowcomments, $this->msg->getText());
			$affected = $this->db->affectedrows();

			$this->entryid = $entryid;

			$this->userblog->invalidatePages($this->scope, $this->time);
			$this->userblog->postCountChange(1, $this->scope);
			

		} else { // update
			$blogsets = array();
			foreach ($this->fieldschanged as $field => $val)
			{
				$blogsets[] = $this->db->prepare("$field = ?", $val);
			}
			if ($blogsets)
			{
				$this->db->prepare_query("UPDATE blog SET " . implode(',',$blogsets) . $this->db->prepare(" WHERE userid = % AND id = #", $this->uid, $this->entryid));
				$affected = $this->db->affectedrows();
				$cache->remove("weblog-post-{$this->uid}:{$this->entryid}");
				$cache->remove("weblog-parsed-{$this->uid}:{$this->entryid}");
			}

			if ($this->scope != $this->originalscope || isset($this->fieldschanged['time']))
			{
				$this->userblog->invalidatePages(min($this->scope, $this->originalscope), $this->time);
				$this->userblog->postCountChange(false, false);
			}
		}
		$this->originalscope = $this->scope;
		$google->updateHash($this->uid);
		return $affected;
	}

	// deleting a post does not need to be commited, it happens
    // immediately and a further commit will actually cause it to be
    // readded. If this were to become a problem, it should be flagged and throw
    // an error.
	function delete()
	{
		return self::deleteMulti($this->userblog, array(&$this));
	}
	static function deleteMulti($userblog, $posts)
	{
		$lowestscope = 5; // FIXME: Magic numbers bad
		$postids = array();
		$times = array();
		$deleted = 0;
		foreach ($posts as $post)
		{
			if ($post->entryid !== false)
			{
				$post->invalidate();
				$postids[] = $post->entryid;
				$post->entryid = false;
				$lowestscope = min($lowestscope, $post->scope);
				$times[] = $post->time;
			}
		}
		if ($postids)
		{
			$userblog->db->prepare_query("DELETE FROM blog WHERE userid = % AND id IN (#)", $userblog->uid, $postids);
			$deleted = $userblog->db->affectedrows();
			$userblog->postCountChange(-$deleted, $lowestscope);
			$userblog->invalidatePages($lowestscope, $times);

			$userblog->db->prepare_query("DELETE FROM blogcomments WHERE bloguserid = % AND blogid IN (#)", $userblog->uid, $postids);
		}
		return $deleted;
	}
}

class blogcomment
{
	// memcache keys used by this class:
    // weblog-comment-$commentid
    //  The table row for $commentid
    // weblog-comment-parsed-$commentid
    //  The parsed comment text of $commentid

	public $blogpost;
	public $bloguid;
	public $db;

	public $commentid;
	public $rootid;
	public $parentid;
	public $userid;
	public $time;
	public $deleted;

	public $fieldschanged;

	// if $commentdata is not an array, it is an id of a comment to load from
	function __construct($blogpost, $commentdata)
	{
		$this->blogpost = $blogpost;
		$this->bloguid = $blogpost->uid;
		$this->db = $blogpost->db;

		if (!is_array($commentdata))
		{
			$commentdata = self::getRawBlogComments($blogpost, array($commentdata));
			$commentdata = array_shift($commentdata);
		}

		$this->commentid = isset($commentdata['id']);
		if ($this->commentid)
			$this->commentid = $commentdata['id'];

		$this->rootid = isset($commentdata['rootid'])? $commentdata['rootid'] : 0;
		$this->parentid = isset($commentdata['parentid'])? $commentdata['parentid'] : 0;
		$this->userid = $commentdata['userid'];
		$this->time = isset($commentdata['time'])? $commentdata['time'] : time();
		$this->deleted = isset($commentdata['deleted'])? $commentdata['deleted'] : 'f';

		$this->msg = new textparser(isset($commentdata['msg'])? $commentdata['msg'] : '');

		$fieldschanged = array();
	}

	static private function getRawBlogComments($blogpost, $commentids)
	{
		global $cache;

		if (!$commentids)
			return array();

		$comments = $cache->get_multi($commentids, "weblog-comment-{$blogpost->uid}-");
		$missing = array_diff($commentids, array_keys($comments));
		if ($missing)
		{
			$result = $blogpost->db->prepare_query("SELECT * FROM blogcomments WHERE bloguserid = % AND id IN (#)", $blogpost->uid, $missing);
			while ($data = $result->fetchrow())
			{
				$comments[ $data['id'] ] = $data;
				$cache->put("weblog-comment-{$blogpost->uid}-$data[id]", $data, 24*60*60);
			}
		}
		return $comments;
	}

	static function getBlogComments($blogpost, $commentids)
	{
		$comments = self::getRawBlogComments($blogpost, $commentids);
		foreach ($comments as $id => $obj)
		{
			$comments[$id] = new blogcomment($blogpost, $obj);
		}
		return $comments;
	}

	function getParsedText()
	{
		return $this->msg->getParsedText($this->commentid, "weblog-comment-parsed-{$this->bloguid}-");
	}

	function getText()
	{
		return $this->msg->getText();
	}

	function setText($msg)
	{

		if ($this->msg->setText($msg))
		{
			$this->fieldschanged['msg'] = $this->msg;
			$this->parseMsg();
			return true;
		}
		return false;
	}


	function invalidate()
	{
		global $cache;
		$cache->remove("weblog-comment-{$this->blogpost->uid}-{$this->commentid}");
		$cache->remove("weblog-comment-parsed-{$this->blogpost->uid}-{$this->commentid}");
	}

	function commit()
	{
		global $cache;
		if ($this->commentid === false) // insert
		{
			$commentid = $this->db->getSeqId($this->bloguid, DB_AREA_WEBLOG_COMMENT);
			$this->db->prepare_query("INSERT INTO blogcomments (bloguserid, id, blogid, rootid, parentid, userid, time, deleted, msg) VALUES (%, #, #, #, #, #, #, ?, ?)",
				$this->bloguid, $commentid, $this->blogpost->entryid, $this->rootid, $this->parentid, $this->userid, $this->time, $this->deleted, $this->getText());

			$this->commentid = $commentid;

			$this->blogpost->replyCountChange(1, $this->rootid);

		} else { // update
			$sets = array();
			foreach ($this->fieldschanged as $field => $val)
			{
				$sets[] = $this->db->prepare("$field = ?", $val);
			}
			if ($sets)
			{
				$this->db->query("UPDATE blogcomments SET " . implode(',',$sets) . $this->db->prepare(" WHERE bloguserid = % AND id = #", $this->bloguid, $this->commentid));

				$this->invalidate();
			}
		}
	}

	// deleting a comment does not need to be committed, and the object
    // remains valid and still points to the same comment.
	function delete()
	{
		return self::deleteMulti($this->blogpost, array(&$this));
	}
	static function deleteMulti($blogpost, $comments)
	{
		$commentids = array();
		$rootids = array();
		$deleted = 0;
		foreach ($comments as $comment)
		{
			if ($comment->commentid !== false && $comment->deleted != 't')
			{
				$commentids[] = $comment->commentid;
				$rootids[] = $comment->rootid;
				$comment->deleted = 't';
			}
		}
		if ($commentids)
		{
			$blogpost->db->prepare_query("UPDATE blogcomments SET deleted = 't' WHERE bloguserid = % AND id IN (#)", $blogpost->uid, $commentids);


			$deleted = $blogpost->db->affectedrows();
			$blogpost->replyCountChange(-$deleted, array_unique($rootids));
		}
		foreach ($comments as $comment)
		{
			$comment->invalidate();
		}
		return $deleted;
	}
}

class userblog
{
	// memcache keys used by this class:
    // weblog-entries-$maxscope-$userid-$pagenum
    //  - array of ($entryid => $createtime) entries in $scope by $userid
    //  - up to 2 pages, all invalidated on blog post
    // weblog-entries-bymonth-$maxscope-$userid-$year-$month
    //  - array of ($entryid => $createtime) entries in $scope by $userid during $month of $year
    // weblog-firstpost-$maxscope-$userid
    //  - array($entryid=>$createtime) of the oldest post in the user's blog
    // weblog-entries-count-$userid
    //  - number of blog entries for $userid
    // weblog-lastread-$userid-readtime
    //  - last time $userid read their friendslist
    // weblog-lastread-$userid-postcount
    //  - number of posts since the user last read their friendslist.
    // weblog-newreplies-$uid-total
    //  - the number of replies in total that are somehow in reply to $uid. (also used by blogpost)

	public $weblog; // weblog object
	public $db; // database object
	public $uid; // userid of blog
	public $cachepages; // number of pages to cache

	function __construct($weblog, $uid)
	{
		$this->weblog = $weblog;
		$this->db = $weblog->db;
		$this->uid = $uid;

		$this->cachepages = 2;
	}

	function getPostList($pagenum, $maxscope, $skipcache = false)
	{
		global $cache;

		$postlist = false;
		if ($pagenum < $this->cachepages && !$skipcache)
			$postlist = $cache->get("weblog-entries-$maxscope-{$this->uid}-$pagenum");

		if (!is_array($postlist))
		{
			$postlist = array();

			$result = $this->db->prepare_query("SELECT id, time FROM blog WHERE userid = % AND scope IN (#) ORDER BY time DESC LIMIT #,#",
				$this->uid, range(1, $maxscope), $pagenum * $this->weblog->entriesPerPage, $this->weblog->entriesPerPage);
			while ($entry = $result->fetchrow())
			{
				$postlist["{$this->uid}:$entry[id]"] = $entry['time'];
			}
			if ($pagenum < $this->cachepages)
				$cache->put("weblog-entries-$maxscope-{$this->uid}-$pagenum", $postlist, 24*60*60);
		}
		return $postlist;
	}

	function getMonthPostList($maxscope, $year, $months)
	{
		global $cache;

		$wantarray = true;
		if (!is_array($months))
		{
			$wantarray = false;
			$months = array($months);
		}

		$postmonths	= $cache->get_multi($months, "weblog-entries-bymonth-$maxscope-{$this->uid}-$year-");
		$missing = array_diff($months, array_keys($postmonths));
		if ($missing)
		{
			$result = $this->db->prepare_query("SELECT month, id, time FROM blog WHERE userid = % AND year = # AND scope IN (#) AND month IN (#) ORDER BY time",
				$this->uid, $year, range(1, $maxscope), $missing);
			while ($entry = $result->fetchrow())
			{
				$month = $entry['month'];
				$id = "{$this->uid}:$entry[id]";
				$time = $entry['time'];

				if (!isset($postmonths[$month]))
					$postmonths[$month] = array();

				$postmonths[ $month ][ $id ] = $time;
			}
			foreach ($missing as $put)
			{
				$postlist = (isset($postmonths[$put])? $postmonths[$put] : array());
				$cache->put("weblog-entries-bymonth-$maxscope-{$this->uid}-$year-$put", $postlist, 24*60*60);
			}
		}

		foreach ($postmonths as $month => $posts)
		{
			if (!$posts)
				unset($postmonths[$month]);
		}

		if ($wantarray) {
			ksort($postmonths);
			return $postmonths;
		} else {
			if (!$postmonths)
				return false;
			else
				return array_shift($postmonths);
		}
	}

	static function getPostListMulti($userblogs, $pages, $scopes, $defaultscope, &$count)
	{
		global $cache;

		$cacheparams = array();
		foreach ($userblogs as $userblog)
		{
			foreach ($pages[$userblog->uid] as $page)
			{
				$scope = $defaultscope;
				if (isset($scopes[$userblog->uid]))
					$scope = $scopes[$userblog->uid];

				$cacheparams[] = "$scope-{$userblog->uid}-$page";
			}
		}
		$result = array();
		$postlists = $cache->get_multi($cacheparams, "weblog-entries-");
		foreach ($cacheparams as $cacheparam)
		{
			$elems = explode('-', $cacheparam);
			$scope = $elems[0]; // 1st element is the scope
			$uid = $elems[1]; // 2nd element is the userid
            $pagenum = $elems[2]; // 3rd element is the page number.

			$results = array();
			if (isset($postlists[$cacheparam]))
			{
				// note: relies on pagenumbers being in order in $cacheparams.
				$results = $postlists[$cacheparam];
			} else {
				// get it from the db
                $results = $userblogs[$uid]->getPostList($pagenum, $scope, true);
			}

			if ($results)
			{
				if (!isset($result[$uid]))
					$result[$uid] = array();
				$result[$uid] += $results;
				$count += count($results);
			}
		}
		return $result;
	}

	// convenience function, but could be expanded to cache independently of the post list
    // primarily useful for generating a 'recently updated blogs' list. Returns false if never
    // updated at that scope.
	function getLastPostTime($maxscope)
	{
		$postlist = $this->getPostList(0, $maxscope);
		if (!$postlist)
			return false;

		return array_shift($postlist);
	}

	// returns false if never posted.
    // Note that calling this when there are no blog posts does not cache the negative
    // answer. This is because this is a difficult thing to invalidate, and
    // is fairly unlikely to be called when the user has no visible posts.
	function getFirstPostTime($maxscope)
	{
		global $cache;
		$firstpost = $cache->get("weblog-firstpost-$maxscope-{$this->uid}");
		if (!$firstpost)
		{
			$firstpost = 0;
			$res = $this->db->prepare_query("SELECT MIN(time) AS firstpost FROM blog WHERE userid = % AND scope IN (#)", $this->uid, range(1, $maxscope));
			$row = $res->fetchrow();
			if ($row)
			{
				$firstpost = $row['firstpost'];
				$cache->put("weblog-firstpost-$maxscope-{$this->uid}", $firstpost, 7*24*60*60);
			}
		}
		return $firstpost;
	}

	function invalidatePages($scope, $timestamps)
	{
		// delete all pages for all scopes equal to or greater than the scope being invalidated
        global $cache;

		if (!is_array($timestamps))
			$timestamps = array($timestamps);

		$months = array();
		foreach ($timestamps as $timestamp)
		{
			$year = gmdate("Y", $timestamp);
			$month = gmdate("m", $timestamp);
			$months["$year-$month"] = 1;
		}

        foreach ($this->weblog->scopes as $scopeid => $scopename)
		{
			if ($scopeid >= $scope)
			{
				for ($i = 0; $i < $this->cachepages; $i++)
				{
					$cache->remove("weblog-entries-$scopeid-{$this->uid}-$i");
				}
				foreach (array_keys($months) as $month)
				{
					$cache->remove("weblog-entries-bymonth-$scopeid-{$this->uid}-$month");
				}
			}
		}

		// also invalidate the blog visibility level since this happens when a post
        // is either added or changes scope.
        $cache->remove("weblog-visibility-{$this->uid}");
	}

	function getVisibleScope()
	{
		global $cache;
		$visibility = $cache->get("weblog-visibility-{$this->uid}");
		if (!$visibility)
		{
			$res = $this->db->prepare_query("SELECT count(*) as count, MIN(scope) as scope FROM blog WHERE userid = %", $this->uid);
			$line = $res->fetchrow();
			$visibility = ($line['count'] ? $line['scope'] : -1);
			$cache->put("weblog-visibility-{$this->uid}", $visibility, 7*24*60*60);
		}
		return ($visibility > 0? $visibility : 0); // filter -1 as 0
	}

	function isVisible($loggedIn, $isFriend)
	{
		$visiblescope = $this->getVisibleScope();

		return ($visiblescope == WEBLOG_PUBLIC ||
			($visiblescope == WEBLOG_LOGGEDIN && $loggedIn) ||
			($visiblescope == WEBLOG_FRIENDS && $isFriend));
	}

	function postCountChange($delta, $scope)
	{
		global $cache;
		$deleting = ($delta === false || $scope === false);

		foreach ($this->weblog->scopes as $level => $name)
		{
			if ($deleting)
				$cache->remove("weblog-entries-count-{$this->uid}-$level");
			else if ($level >= $scope)
				$cache->incr("weblog-entries-count-{$this->uid}-$level", $delta);
		}
	}

	function getPostCount($scope)
	{
		global $cache;
		$count = $cache->get("weblog-entries-count-{$this->uid}-$scope");
		if (!is_numeric($count))
		{
			$res = $this->db->prepare_query("SELECT COUNT(*) FROM blog WHERE userid = % AND scope IN (#)", $this->uid, range(1, $scope));
			$count = $res->fetchfield();
			$cache->put("weblog-entries-count-{$this->uid}-$scope", $count, 7*24*60*60);
		}
		return $count;
	}

	// returns an array('readtime' => $lastreadtime, 'postcount' => $postssince) indicating when this user
    // last read their friends list, and how many posts have been made to it since
    // then.
	function getLastRead()
	{
		global $cache;
		$subkeys = array('readtime', 'postcount');
		$result = $cache->get_multi($subkeys, "weblog-lastread-{$this->uid}-");
		$missing = array_diff($subkeys, array_keys($result));

		if ($missing)
		{
			$res = $this->db->prepare_query("SELECT " . implode(',', $subkeys) . " FROM bloglastreadfriends WHERE userid = %", $this->uid);
			$dbresult = $res->fetchrow();
			if ($dbresult) {
				foreach ($missing as $put)
				{
					$cache->put("weblog-lastread-{$this->uid}-$put", $dbresult[$put], 24*60*60);
					$result[$put] = $dbresult[$put];
				}
			} else {
				$result = array('readtime' => 0, 'postcount' => 0);
			}
		}
		return $result;
	}

	function setLastReadTime()
	{
		global $cache;
		$time = time();
		$cache->put("weblog-lastread-{$this->uid}-readtime", $time, 24*60*60);
		$cache->put("weblog-lastread-{$this->uid}-postcount", 0, 24*60*60);
		$this->db->prepare_query("UPDATE bloglastreadfriends SET readtime = #, postcount = postcount - postcount WHERE userid = %", $time, $this->uid);
		if (!$this->db->affectedrows())
		{
			$this->db->prepare_query("INSERT IGNORE INTO bloglastreadfriends (userid, readtime, postcount) VALUES (%, #, #)", $this->uid, $time, 0);
		}
	}

	function incrementLastReadPosts($delta)
	{
		global $cache;
		$cache->incr("weblog-lastread-{$this->uid}-postcount", $delta);
		$this->db->prepare_query("UPDATE bloglastreadfriends SET postcount = postcount + # WHERE userid = %", $delta, $this->uid);
		if (!$this->db->affectedrows())
		{
			$this->db->prepare_query("INSERT IGNORE INTO bloglastreadfriends (userid, readtime, postcount) VALUES (%, #, #)", $this->uid, 0, ($delta>0?$delta:0));
		}
	}

	// always pass the original time of the post into this function, not the current time.
	static function incrementLastReadPostsMulti($weblog, $unreadsince, $existingpost, $userids)
	{
		global $cache;
		if (!$userids)
			return;

		$uidlastreads = array();
		foreach ($userids as $userid)
			$uidlastreads[] = "$userid-readtime";
		$lastreads = $cache->get_multi($uidlastreads, "weblog-lastread-");
		// note that we don't try and get this from the DB. Instead we just
		// delete the corresponding postcount from the cache if it's there.

		$operator = ($existingpost? '>' : '<');
		$weblog->db->prepare_query("UPDATE bloglastreadfriends SET postcount = postcount + 1 WHERE userid IN (%) AND readtime $operator #", $userids, $unreadsince);
		foreach ($userids as $userid)
		{
			if (isset($lastreads[$userid]) &&
			   (($existingpost && $lastreads[$userid] > $unreadsince) ||
			    (!$existingpost && $lastreads[$userid] < $unreadsince)))
			{
				$cache->incr("weblog-lastread-{$userid}-postcount", 1);
			} else {
				$cache->remove("weblog-lastread-{$userid}-postcount");
			}
		}
	}

	static function decrementLastReadPostsMulti($weblog, $unreadsince, $userids)
	{
		global $cache;
		if (!$userids)
			return;

		$uidlastreads = array();
		foreach ($userids as $userid)
			$uidlastreads[] = "$userid-readtime";
		$lastreads = $cache->get_multi($uidlastreads, "weblog-lastread-");

		$weblog->db->prepare_query("UPDATE bloglastreadfriends SET postcount = postcount - 1 WHERE userid IN (%) AND readtime < #", $userids, $unreadsince);
		foreach ($userids as $userid)
		{
			if (isset($lastreads[$userid]) && $lastreads[$userid] < $unreadsince)
				$cache->incr("weblog-lastread-{$userid}-postcount", -1);
			else
				$cache->remove("weblog-lastread-{$userid}-postcount");
		}
	}

	function getNewReplyCountTotal()
	{
		global $cache;

		$replycount = $cache->get("weblog-newreplies-{$this->uid}-total");
		if (!is_numeric($replycount))
		{
			$res = $this->db->prepare_query("SELECT COUNT(*) AS count FROM blogcommentsunread WHERE userid = %", $this->uid);
			$result = $res->fetchrow();
			$replycount = $result['count'];
			$cache->put("weblog-newreplies-{$this->uid}-total", $replycount, 24*60*60);
		}
		return $replycount;
	}

	function getFirstUnreadReplyPost()
	{
		$res = $this->db->prepare_query("SELECT userid, bloguserid, blogid, MIN( time ) AS time FROM blogcommentsunread WHERE userid = % GROUP BY userid", $this->uid);
		$result = $res->fetchrow();
		return ($result? $result : false);
	}

	function deleteBlog()
	{
		global $userData;
		// naive version that gets all blog entries and then deletes all blog entries
		$objs = array();
		$result = $this->db->prepare_query("SELECT * FROM blog WHERE userid = %", $this->uid);
		while ($data = $result->fetchrow())
		{
			$objs[] = new blogpost($this, $data, $userData);
		}
		blogpost::deleteMulti($this, $objs);

		$this->db->prepare_query("SELECT * FROM bloglastreadfriends WHERE userid = %", $this->uid);
	}
}

// this could (and should) be optimized further by a lot.
class multiuserblog
{
	public $weblog;
	public $totalitems; // total number of items that can be consumed from this object.
	public $itempages; // array of userid=>postlist
	public $items; // array of interpolated postid=>timestamp entries consumed to date.

	public $readeruid;
	public $friendoflist;
	public $defaultscope;

	// userblogs must map uid=>userblog
	function __construct($weblog, $userblogs, $readeruid, $friendoflist, $defaultscope)
	{
		$this->weblog = $weblog;
		$this->totalitems = 0;
		$this->itempages = array();
		$this->items = array();

		$this->readeruid = $readeruid;
		$this->friendoflist = $friendoflist;
		$this->defaultscope = $defaultscope;

		$scopes = array();
		$pages = array();

		foreach ($userblogs as $userblog)
		{
			if ($readeruid == $userblog->uid)
				$scopes[$userblog->uid] = WEBLOG_PRIVATE;
			else if (isset($friendoflist[$userblog->uid]))
				$scopes[$userblog->uid] = WEBLOG_FRIENDS;

			$pages[$userblog->uid] = range(0, $userblog->cachepages - 1);
		}
		$this->itempages = userblog::getPostListMulti($userblogs, $pages, $scopes, $defaultscope, $this->totalitems);
	}

	// maxscope is ignored, and is only here for interface compatibility with the single
    // blog version. Later it may scan for it.
	function getPostList($pagenum, $maxscope = 0)
	{
		// figure out how many items we actually need.
        $first = $this->weblog->entriesPerPage * $pagenum;
		$count = $this->weblog->entriesPerPage;
        $need = $first + $count;
		if ($need > $this->getPostCount())
			$need = $this->getPostCount();

		// we need to consume $need - count($items) more
		while ($need > count($this->items))
		{
			// find the most recent post on all friends lists
			$times = array();
			$hightime = 0;
			foreach ($this->itempages as $uid => &$postpages)
			{
				// get the topmost item
				foreach ($postpages as $itemid => $time)
				{
					break;
				}
				$times[$time] = array($uid, $itemid);
				if ($time > $hightime)
					$hightime = $time;
			}
			// consume that item from the itempages array into the items array
			$uid = $times[$hightime][0];
			$itemid = $times[$hightime][1];
			$this->items[$itemid] = $hightime;
			unset($this->itempages[$uid][$itemid]);
			if (count($this->itempages[$uid]) == 0)
				unset($this->itempages[$uid]);
		}
		return array_slice($this->items, $first, $count, true);
	}

	// scope is ignored, and is only here for interface compatibility with the single
    // blog version. Later it may scan for it.
	function getPostCount($scope = 0)
	{
		return $this->totalitems;
	}
}
