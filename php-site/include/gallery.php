<?

class galleries
{
	public $db;

	public static $perms = array("anyone" => "Visible to Anyone", "loggedin" => "Logged In Users Only", "friends" => "Friends Only");
	public static $regexperms = 'anyone|loggedin|friends';

	function __construct($gallerydb)
	{
		$this->db = $gallerydb;
	}

	function getImageURL($uid, $id, $type = 'normal')
	{
		global $config;

		if (!$id)
			return false;

		if ($type == 'thumb')
			$loc = $config['gallerythumbloc'];
		else if ($type == 'full')
			$loc = $config['galleryfullloc'];
		else
			$loc = $config['gallerypicloc'];

		$uidbase = floor($uid / 1000);
		$uidhide = weirdmap($uid);

		return "$loc$uidbase/$uidhide/{$id}.jpg";
	}
	function clearpending()
	{
		global $sourcepictures, $cache;

		$deleteitems = array();
		$queueitems = array();
		$uids = array();
		// select in one big chunk.
		$result = $this->db->prepare_query("SELECT sourceid, userid FROM gallerypending WHERE uploadtime > #", time() - 24*60*60); // runs on all servers
		while ($row = $result->fetchrow())
		{
			$deleteitems[] = "$row[userid]:$row[sourceid]";
			$queueitems[] = array($row['userid'], $row['sourceid']);
		}

		// delete pending images in groups of 100
		$deleteitems = array_chunk($deleteitems, 100);
		foreach ($deleteitems as $subdeleteitems)
		{
			$keys = array('userid' => '%', 'sourceid' => '#');
			// shameless code duplication from usergalleries
			$this->db->prepare_query('DELETE FROM gallerypending WHERE ^', $this->db->prepare_multikey($keys, $subdeleteitems));

			$sourceobjs = $sourcepictures->getSourcePictures($subdeleteitems);
			if ($sourceobjs)
				sourcepicture::deleteMulti($sourcepictures->db, $sourceobjs);
			
		}

		// delete memcache entries for pending pictures
		foreach ($uids as $uid)
		{
			$cache->remove("gallery-pics-pending-$uid");
		}
		
		foreach ($queueitems as $itemkey){
			enqueue("Gallery::Pic", "create", $itemkey[0], $itemkey);
		}
	}
}

class usergalleries
{
	private $galleries;
	private $uid;
	public $db;

	function __construct($galleries, $uid)
	{
		$this->galleries = $galleries;
		$this->uid = $uid;
		$this->db = $galleries->db;
	}

	function getAccessLevel($userData)
	{
		$access = array('anyone');
		if ($userData['loggedIn'])
		{
			$access[] = 'loggedin';
			if ($userData['userid'] == $this->uid || isFriend($userData['userid'], $this->uid))
				$access[] = 'friends';
		}
		return $access;
	}

	function getGalleryList($accesslevel)
	{
		global $cache;
		$list = $cache->get("gallerylist-{$this->uid}-" . $accesslevel[count($accesslevel)-1]);
		if (!is_array($list))
		{
			$list = array();
			$result = $this->db->prepare_query("SELECT id FROM gallery WHERE ownerid = % AND permission IN (?)", $this->uid, $accesslevel);
			while ($id = $result->fetchrow())
			{
				$list[] = "{$this->uid}:$id[id]";
			}
			$cache->put("gallerylist-{$this->uid}-" . $accesslevel[count($accesslevel)-1], $list, 24*60*60);
		}
		return $list;
	}

	function addPending($sourceid, $description, $md5)
	{
		global $cache;

		// Remove this once the pending image list can have the md5 index made unique
		$res = $this->db->prepare_query("SELECT count(*) AS count FROM gallerypending WHERE userid = % AND md5 = ?", $this->uid, $md5);
		if ($res->fetchfield())
			return false;

		$this->db->prepare_query("INSERT IGNORE INTO gallerypending (userid, sourceid, description, uploadtime, md5) VALUES (%, #, ?, #, ?)", $this->uid, $sourceid, $description, time(), $md5);
		if ($this->db->affectedrows() == 0)
			$this->db->prepare_query("UPDATE gallerypending SET description = ? WHERE userid = % AND sourceid = #", $description, $this->uid, $sourceid);

		if ($this->db->affectedrows() == false)
			return false; // this means the image had already been uploaded.

		$cache->remove("gallery-pics-pending-{$this->uid}");
		return true;
	}

	function getPendingList()
	{
		global $cache;

		$list = $cache->get("gallery-pics-pending-{$this->uid}");
		if (!is_array($list))
		{
			$list = array();
			$result = $this->db->prepare_query("SELECT sourceid, description FROM gallerypending WHERE userid = %", $this->uid);
			while ($id = $result->fetchrow())
			{
				$list[ "{$this->uid}:$id[sourceid]" ] = $id['description'];
			}
			$cache->put("gallery-pics-pending-{$this->uid}", $list, 24*60*60);
		}
		return $list;
	}

	function clearPending($pendingids)
	{
		global $cache, $sourcepictures;

		$keys = array('userid' => '%', 'sourceid' => '#');
		$this->db->prepare_query('DELETE FROM gallerypending WHERE ^', $this->db->prepare_multikey($keys, $pendingids));
		$cache->remove("gallery-pics-pending-{$this->uid}");

		$sourceobjs = $sourcepictures->getSourcePictures($pendingids);
		if ($sourceobjs)
			sourcepicture::deleteMulti($sourcepictures->db, $sourceobjs);
	}

	function getGalleries($ids)
	{
		$template = new gallery($this, $this->galleries);
		$objs = $template->getObjects($ids);
		$result = array();
		foreach ($objs as $id => $obj)
		{
			if ($obj->ownerid == $this->uid)
				$result[$id] = $obj;
		}
		return $result;
	}
	function getGallery($id)
	{
		$template = new gallery($this, $this->galleries);
		$obj = $template->getObject($id);
		if ($obj && $obj->ownerid == $this->uid)
			return $obj;
		else
			return false;
	}

	function getGalleryPics($galleryid, $ids)
	{
		$template = new gallerypic($this->galleries);
		$objs = $template->getObjects($ids);
		$result = array();
		foreach ($objs as $id => $obj)
		{
			if ($galleryid === null || $obj->galleryid == $galleryid)
				$result[$id] = $obj;
		}
		return $result;
	}

	function getGalleryPic($galleryid, $id)
	{
		$template = new gallerypic($this->galleries);
		$obj = $template->getObject($id);
		if ($obj && ($galleryid === null || $obj->galleryid == $galleryid))
			return $obj;
		else
			return false;
	}

	function getPictureCount()
	{
		global $cache;

		$count = $cache->get("gallery-pics-{$this->uid}-count");
		if (!is_numeric($count))
		{
			$result = $this->db->prepare_query("SELECT count(id) AS count FROM gallerypics WHERE userid = %", $this->uid);
			$row = $result->fetchrow();
			$count = (int)$row['count'];

			$cache->put("gallery-pics-{$this->uid}-count", $count, 24*60*60);
		}
		return $count;
	}
}

class gallery extends databaseobject
{
	private $usergalleries;
	private $galleries;
	private $db;

	function __construct($usergalleries, $galleries)
	{
		parent::__construct($galleries->db, 'gallery', DB_AREA_GALLERY, 'gallery-',
			array(
				'ownerid' => '%',
				'id' => '!',
				'name' => '?',
				'permission' => '?',
				'previewpicture' => '#',
				'description' => '?'
			)
		);
		$this->permission = 'anyone';

		$this->usergalleries = $usergalleries;
		$this->galleries = $galleries;
		$this->db = $galleries->db;
	}

	function invalidateCache($type)
	{
		global $cache, $mods, $usersdb;
		parent::invalidateCache($type);
		if ($type == 'delete' || $type == 'create')
		{
			foreach (galleries::$perms as $perm => $desc)
				$cache->remove("gallerylist-{$this->ownerid}-$perm");

			if ($type == 'delete')
			{
				$pics = $this->getPictureList();
				$picobjs = $this->getPics($pics);
				gallerypic::deleteMulti($this->db, $picobjs);
			}
		}

		if ($type == 'create' || $this->hasChanged('permission'))
		{
			// figure out what levels create a need for setting a new level in the
			// user table
			$setwhen = array('none');
			$found = false;
			foreach (galleries::$perms as $perm => $desc)
			{
				if ($found)
					$setwhen[] = $perm;
				if ($perm == $this->permission)
					$found = true;
			}

			$usersdb->prepare_query("UPDATE users SET gallery = ? WHERE userid = % AND gallery IN (?)", $this->permission, $this->ownerid, $setwhen);
			if ($usersdb->affectedrows())
				$cache->remove("userinfo-{$this->ownerid}");
		}

		if ($type == 'delete')
		{
			$allaccess = array_keys(galleries::$perms);
			$galpics = $this->usergalleries->getGalleryList($allaccess);
			if (!count($galpics))
			{
				$usersdb->prepare_query("UPDATE users SET gallery = 'none' WHERE userid = %", $this->ownerid);
				$cache->remove("userinfo-{$this->ownerid}");
			}
		}
	}

	// don't pass arguments to just get the whole list.
	function getPictureList($offset = false, $count = false)
	{
		global $cache;

		$list = $cache->get("gallery-pics-{$this->ownerid}:{$this->id}");
		if (!is_array($list))
		{
			$list = array();
			$result = $this->db->prepare_query("SELECT id FROM gallerypics WHERE userid = % AND galleryid = # ORDER BY priority", $this->ownerid, $this->id);
			while ($id = $result->fetchrow())
			{
				$list[] = "{$this->ownerid}:$id[id]";
			}
			$cache->put("gallery-pics-{$this->ownerid}:{$this->id}", $list, 24*60*60);
		}
		if ($offset !== false && $count !== false)
		{
			$list = array_slice($list, $offset, $count);
		}

		return $list;
	}

	function getPictureCount()
	{
		global $cache;

		$count = $cache->get("gallery-pics-gallery-{$this->ownerid}:{$this->id}-count");
		if (!is_numeric($count))
		{
			$result = $this->db->prepare_query("SELECT count(id) AS count FROM gallerypics WHERE userid = % AND galleryid = #", $this->ownerid, $this->id);
			$row = $result->fetchrow();
			$count = (int)$row['count'];

			$cache->put("gallery-pics-gallery-{$this->ownerid}:{$this->id}-count", $count, 24*60*60);
		}
		return $count;
	}

	function getImageURL($type)
	{
		return $this->galleries->getImageURL($this->ownerid, $this->previewpicture, $type);
	}

	function hasAccess($accesslevel)
	{
		return in_array($this->permission, $accesslevel);
	}

	function getPics($ids)
	{
		return $this->usergalleries->getGalleryPics($this->id, $ids);
	}
	function getPic($id)
	{
		return $this->usergalleries->getGalleryPic($this->id, $id);
	}

	function fixFirstPic()
	{
		$piclist = $this->getPictureList();
		if ($piclist)
		{
			$first = explode(':', array_shift($piclist));
			$first = $first[1];
			$this->previewpicture = $first;
		} else {
			$this->previewpicture = 0;
		}
	}
	static function fixPreviewPictures($objs)
	{
		global $galleries;
		
		if(count($objs) == 0)
			return;

		
		$zeropics = array();
		foreach ($objs as $obj)
		{
			$zeropics["{$obj->ownerid}:{$obj->id}"] = false;
		}
		$res = $galleries->db->prepare_query("SELECT userid, galleryid, id FROM gallerypics WHERE ^ AND priority = 0",
			$galleries->db->prepare_multikey(array("userid" => "%", "galleryid" => "#"), array_keys($zeropics)));
		while ($row = $res->fetchrow())
		{
			$zeropics["$row[userid]:$row[galleryid]"] = $row["id"];
		}
		foreach ($objs as $obj)
		{
			$zeropic = $zeropics["{$obj->ownerid}:{$obj->id}"];
			if ($zeropic)
			{
				$obj->previewpicture = $zeropic;
			}
		}
	}
}

class gallerypic extends databaseobject
{
	private $galleries;
	private $db;

	function __construct($galleries)
	{
		parent::__construct($galleries->db, 'gallerypics', DB_AREA_GALLERYPIC, 'gallerypics-',
			array(
				'userid' => '%',
				'id' => '!',
				'galleryid' => '#',
				'priority' => '#',
				'sourceid' => '#',
				'description' => '?'
			)
		);

		$this->galleries = $galleries;
		$this->db = $galleries->db;
	}

	function invalidateCache($type)
	{
		global $cache, $mods, $filesystem, $mogfs;
		parent::invalidateCache($type);

		if ($type == 'delete')
		{
			// we should really also clear the cache for this, but priority is never explicitly used anywhere so it can be ignored
			// If that ever changes, this will have to be made less efficient.
			$this->db->prepare_query("UPDATE gallerypics SET priority = priority - 1 WHERE userid = % AND galleryid = # AND priority > #", $this->userid, $this->galleryid, $this->priority);

			$normal = $this->getImagePath('normal');
			if (file_exists($normal))
			{
				@unlink($normal);
				$filesystem->delete($normal);
				$mogfs->delete(FS_GALLERY, "{$this->userid}/{$this->id}.jpg");
			}
			$full = $this->getImagePath('full');
			if (file_exists($full))
			{
				@unlink($full);
				$filesystem->delete($full);
				$mogfs->delete(FS_GALLERYFULL, "{$this->userid}/{$this->id}.jpg");
			}
			$thumb = $this->getImagePath('thumb');
			if (file_exists($thumb))
			{
				@unlink($thumb);
				$filesystem->delete($thumb);
				$mogfs->delete(FS_GALLERYTHUMB, "{$this->userid}/{$this->id}.jpg");
			}

			$mods->deleteItem(MOD_GALLERY,$this->id);
			$mods->deleteItem(MOD_GALLERYABUSE,$this->id);

			if ($this->galleryid)
			{
				$cache->remove("gallery-pics-{$this->userid}:{$this->galleryid}");
				$cache->decr("gallery-pics-gallery-{$this->userid}:{$this->galleryid}-count");
				$cache->decr("gallery-pics-{$this->userid}-count");
			}
		}

		if ($type == 'create' && $this->galleryid)
		{
			$cache->remove("gallery-pics-{$this->userid}:{$this->galleryid}");
			$cache->incr("gallery-pics-gallery-{$this->userid}:{$this->galleryid}-count");
			$cache->incr("gallery-pics-{$this->userid}-count");
			$mods->newSplitItem(MOD_GALLERY,array($this->userid => $this->id));
		}
	}

	function commit()
	{
		// if we're committing this item for the first time, and the priority is not
		// set, set it.
		if (!$this->id && !$this->priority)
		{
			$this->priority = getMaxPriority($this->db, 'gallerypics', $this->db->prepare('userid = % AND galleryid = #', $this->userid, $this->galleryid));
		}
		if (strlen($this->description) > 1024)
			$this->description = substr($this->description, 0, 1024);

		return parent::commit();
	}

	function delete()
	{
		global $sourcepictures;
		$deleted = parent::delete();
		if ($deleted && $this->sourceid)
		{
			$sourcepic = $sourcepictures->getSourcePicture("{$this->userid}:{$this->sourceid}");
			if ($sourcepic)
				$sourcepic->delete();
		}
		return $deleted;
	}

	static function deleteMulti($db, $objs)
	{
		global $sourcepictures;
		if ($deleted = parent::deleteMulti($db, $objs))
		{
			$sourcepicids = array();
			$userid = null;
			foreach ($objs as $obj)
			{
				if ($obj->sourceid)
					$sourcepicids[] = "{$obj->userid}:{$obj->sourceid}";
			}
			$sourcepics = $sourcepictures->getSourcePictures($sourcepicids);
			if ($sourcepics)
				sourcepicture::deleteMulti($sourcepictures->db, $sourcepics);
		}
		return $deleted;
	}

	// ugly as it is, this function does not require committing. It autocommits specifically the change to the priority field.
	function increasePriority($by)
	{
		global $cache;

		$affected = false;
		if ($by < 0)
			$affected = decreasePriority($this->db, 'gallerypics', $this->id, $this->db->prepare('userid = % AND galleryid = #', $this->userid, $this->galleryid), true, $this->userid);
		else
			$affected = increasePriority($this->db, 'gallerypics', $this->id, $this->db->prepare('userid = % AND galleryid = #', $this->userid, $this->galleryid), true, $this->userid);

		if ($affected)
		{
			foreach ($affected as $id)
				$cache->remove("gallerypics-{$this->userid}:{$this->id}");
			$cache->remove("gallery-pics-{$this->userid}:{$this->galleryid}");
		}
	}

	function getImagePath($type = 'normal')
	{
		global $staticRoot, $config;

		$path = $staticRoot;
		if ($type == 'full')
			$path .= $config['galleryfulldir'];
		else if ($type == 'thumb')
			$path .= $config['gallerythumbdir'];
		else
			$path .= $config['gallerypicdir'];

		$uidbase = floor($this->userid / 1000);
		$uidhide = weirdmap($this->userid);

		$path .= "{$uidbase}/{$uidhide}/{$this->id}.jpg";

		return $path;
	}

	// sourceobj is a sourcepicture object.
	function generatePictures($sourceobj, $addtag = true)
	{
		global $staticRoot, $config, $mogfs;

		set_time_limit(60); // reset the timeout counter to 1 minute for every image processed.

		if (!$this->id)
			return false;

		umask(0);

		$picdir = $this->getImagePath('normal');
		if(!is_dir(dirname($picdir)))
			@mkdir(dirname($picdir),0777,true);
		$thumbdir = $this->getImagePath('thumb');
		if(!is_dir(dirname($thumbdir)))
			@mkdir(dirname($thumbdir),0777,true);
		$fulldir = $this->getImagePath('full');
		if(!is_dir(dirname($fulldir)))
			@mkdir(dirname($fulldir),0777,true);

		$generate = array(
			array($picdir, $config['maxGalleryPicWidth'], $config['maxGalleryPicHeight'], true),
			array($thumbdir, $config['thumbWidth'], $config['thumbHeight'], false),
			array($fulldir, $config['maxFullPicWidth'], $config['maxFullPicHeight'], $addtag)
		);

		$sourceobj->duplicateImage($generate);
		$this->sourceid = $sourceobj->id;

		$mogfs->add(FS_GALLERY, "{$this->userid}/{$this->id}.jpg", file_get_contents($picdir));
		$mogfs->add(FS_GALLERYFULL, "{$this->userid}/{$this->id}.jpg", file_get_contents($thumbdir));
		$mogfs->add(FS_GALLERYTHUMB, "{$this->userid}/{$this->id}.jpg", file_get_contents($fulldir));

		return true;
	}

	function getImageURL($type = 'normal')
	{
		return $this->galleries->getImageURL($this->userid, $this->id, $type);
	}

	function parseDescription($js = false)
	{
		$filterdesc = $this->description;
		if (!$js)
			$filterdesc = htmlentities($filterdesc);
		$filterdesc = nl2br($filterdesc);
		if ($js)
			$filterdesc = preg_replace("/(\r\n|\n|\r)/", "", $filterdesc);
		$filterdesc = wrap($filterdesc);
		if ($js)
			$filterdesc = addslashes($filterdesc);
		return $filterdesc;
	}
}

/*
$galleries = new galleries($gallerydb);
$objs = $galleries->getGalleries(array(4, 5));
$newobj = new gallery($galleries);
$newobj->ownerid = 175;
$newobj->name = "hello";
$newobj->description = "testing";
$newobj->commit();
$objs[] = $newobj;
print_r($objs);
foreach ($objs as $obj)
{
	print("<p>{$obj->id}, {$obj->ownerid}, {$obj->name}, {$obj->description}</p>");
}
$newobj->delete();
exit();*/
