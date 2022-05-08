<?php

// this header defines a class that can be used as a general purpose threaded
// comment mechanism. It is largely based on the blog comment code.

class commentroot
{
	private $db;
	private $itemid;
	private $tablename;
	private $balancecolumn;
	private $balancevalue;
	private $roottype;
	private $extrafields;

	function __construct($db, $itemid, $tablename, $roottype, $balancecolumn, $balancevalue, $extrafields)
	{
		$this->db = $db;
		$this->itemid = $itemid;
		$this->tablename = $tablename;
		$this->balancecolumn = $balancecolumn;
		$this->balancevalue = $balancevalue;
		$this->extrafields = $extrafields;
	}

	function getTopLevelComments($pagesize = false, $pagenum = false)
	{
		global $cache;
		$replies = $cache->get("comment-{$this->roottype}-replies-{$this->itemid}-toplevel");
		if (!is_array($replies))
		{
			$res = $this->db->squery($this->uid, $this->db->prepare("SELECT id FROM {$this->tablename} WHERE itemid = # AND rootid = 0 ORDER BY time ASC", $this->itemid));
			$replies = array();
			while ($reply = $res->fetchrow())
			{
				$replies[] = $reply['id'];
			}
			$cache->put("comment-{$this->roottype}-replies-{$this->itemid}-toplevel", $replies, 24*60*60);
		}

		if ($pagesize !== false)
		{
			$chunkreplies = array_chunk($replies, $pagesize);
			if ($pagenum >= count($chunkreplies))
				$pagenum = count($chunkreplies) - 1;

			$replies = $chunkreplies[$pagenum];
		}
		return $replies;
	}

	// returns an array of reply trees for the toplevelids passed in.
	// TODO: Make this aspect more object oriented? commenttree object? For
	// now, KISS:
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
	function getCommentTrees($toplevelids)
	{
		global $cache;
		$replies = $cache->get_multi($toplevelids, "comment-{$this->roottype}-replytree-{$this->itemid}-");
		$missing = array_diff($toplevelids, array_keys($replies));
		if ($missing)
		{
			$res = $this->db->squery($this->balancevalue, $this->db->prepare("SELECT id, rootid, parentid FROM {$this->tablename} WHERE itemid = # AND rootid IN (#) ORDER BY time ASC", $this->itemid, $toplevelids));
			$replies = array('allchildren' => array());
			while ($row = $res->fetchrow())
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
				$cache->put("comment-{$this->roottype}-replytree-{$this->itemid}-$rootid", $tree, 24*60*60);
			}
		}

		return $replies;
	}

	// returns a list of ids to fetch up to $maxcount for a partial view of the tree
	// Not implemented yet, just returns all ids. Need to come up with a good way
	// of dealing with this.
	function limitByDepth(&$commenttree, $maxcount)
	{
		return $commenttree['allchildren'];
	}

	// this function builds a comment tree (as in getCommentTrees)
	// based on the array passed in, of the format: array($replyid => $replytoid).
	// It deals with intelligently figuring out when there's a whole string of replies
	// that should be treed together.
	// This is mainly used to display "new comment" pages for a particular root object.
	function makeTreeFromReplies($ids)
	{
		// first find all the ones without a parent. These are the roots.
		$allids = array_merge(array_keys($ids), array_values($ids));
		$rootitems = array();
		foreach ($allids as $id)
		{
			if (!isset($ids[$id]))
				$rootitems[] = $id;
		}

		// now we want to figure out which items are under what root.
		$itemroots = array();
		$idscopy = $ids;
		while (count($idscopy))
		{
			while (count($idscopy)) // this loop is broken whenever we figure out a root.
			{
				foreach ($ids as $replyid => $replytoid) break; // get the first key/value
				// if this is a reply directly to a root, we know exactly where it goes.
				if (in_array($replytoid, $rootitems))
				{
					$itemroots[$replyid] = $replytoid;
					array_shift($idscopy);
					break;
				}
				// if it's a reply to something that we've already determined the root of,
				// take its root
				if (isset($itemroots[$replytoid]))
				{
					$itemroots[$replyid] = $itemroots[$replytoid];
					array_shift($idscopy);
					break;
				}
				// otherwise, take it off the front and put it on the back
				// and move on to the next one.
				array_shift($idscopy);
				$idscopy[$replyid] = $replytoid;
			}
		}

		// now make our tree (warning: Next line is really evil)
		$replies = array_merge(array('allchildren' => $allids), array_combine($rootitems, array_fill(0, count($rootitems), array('allchildren' => array()))));
		foreach ($itemroots as $item => $root)
		{
			$parent = $ids[$item];

			if (!isset($replies[$root][$parent]))
				$replies[$root][$parent] = array();

			$replies[$root]['allchildren'][] = $item;
			$replies[$root][$parent][] = $item;
		}
		return $replies;
	}

	static function getReplyCountMulti($commentroots)
	{
		global $cache;

		$itemids = array();
		$balancevals = array();
		foreach ($commentroots as $item)
		{
			$itemids[] = $item->itemid;
			$balancevals[] = $item->balancevalue;
			$roottype = $item->roottype;
			$db = $item->db;
		}
		$counts = $cache->get_multi($itemids, "comment-{$roottype}-replycount-");
		$missing = array_diff($itemids, array_keys($counts));
		if ($missing)
		{
			// get the ones that actually have comments.
			$res = $db->squery($balancevals, $db->prepare("SELECT itemid, COUNT(*) AS count FROM {$this->table} WHERE itemid IN (#) GROUP BY itemid ORDER BY itemid", $itemids));
			while ($row = $res->fetchrow())
			{
				$counts[ $row['itemid'] ] = $row['count'];
				$cache->put("comment-{$roottype}-replycount-$row[blogid]", $row['count'], 7*24*60*60);
			}
			// get the ones that don't.
			$missing = array_diff($itemids, array_keys($counts));
			foreach ($missing as $id)
			{
				$counts[$id] = 0;
				$cache->put("comment-{$roottype}-replycount-$id", 0, 7*24*60*60);
			}
		}
		return $counts;
	}

	function getReplyCount()
	{
		$arr = self::getReplyCountMulti($this->db, array($this));
		return array_shift($arr);
	}

	function replyCountChange($delta)
	{
		global $cache;

		return $cache->incr("comment-{$this->roottype}-replycount-{$this->itemid}", $delta);
	}

	function getComments($commentids)
	{
		$template = new comment($this->db, $this->tablename, $this->roottype, $this->balancecolumn, $this->extrafields);
		$template->{$this->balancecolumn} = $this->balancevalue;
		return $template->getObjects($commentids);
	}
}

class comment extends databaseobject
{
	// the fields this object deals with by default are:
	// id, rootid, parentid, userid, time, deleted, and msg.
	// any other fields needed can be passed in through extrafields.
	function __construct($db, $tablename, $roottype, $balanceon, $extrafields)
	{
		$fields = array_merge($extrafields,
			array(
				'rootid' => 0,
				'parentid' => 0,
				'userid' => 0,
				'time' => time(),
				'deleted' => 'n',
				'msg' => ''
			)
		);
		parent::__construct($db, $tablename, $balanceon, "comment-$roottype-", $fields);
	}
}
