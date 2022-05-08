<?

class category{
	public $db;

	public $name;
	public $where;

	public $data = array();
	public $child = array();
	public $parent = array();

	function __construct( & $db, $name, $where = "", $cached = true){ //table of type id,parent,name
		global $cache;

		$this->name = $name;
		$this->where = $where;

		$this->db = & $db;

		static $catcache = array();
		
		if( isset($catcache[$name])){
			$this->data = & $catcache[$name]->data;
			$this->child = & $catcache[$name]->child;
			$this->parent = & $catcache[$name]->parent;
		}else{
			// We bump the memory up if any calls are made to dumpData, so we want to record what the limit
			// is currently at so we can set it back after.
			$initialMemoryLimit = ini_get("memory_limit");		
			
			if($cached){
				$this->data = $cache->hdget($name . "d", 0, array('function' => array(&$this, 'dumpData'), 'params' => array('data')));
				$this->child = $cache->hdget($name . "c", 0, array('function' => array(&$this, 'dumpData'), 'params' => array('child')));
				$this->parent = $cache->hdget($name . "p", 0, array('function' => array(&$this, 'dumpData'), 'params' => array('parent')));
			}else{
				$this->dumpData();
			}
			
			$catcache[$name] = & $this;
			
			// Set the memory limit back to what it was.
			ini_set("memory_limit", $initialMemoryLimit);
		}
	}

	function dumpData($ret = false){

		// Temporarily increase the memory size so that we can load potentially huge lists. The location list
		// is esepecially big now and needs this boost. The value will get cached, so we can eventaull set the
		// memory limit down to what it was. If this is not done, we'll get the following:
		//
		// PHP Fatal error: Allowed memory size of ######## bytes exhausted
		//
		// Hopefully if we get that error in the future (meaning we've got an even larger list to deal with),
		// a quick search of the error message will get you to this explanation.
		//
		// Note that a better way to get around this might be to grab the cached data on the ruby side. We
		// build the tree there as well, and theoretically we should be able to use the same data. Or maybe
		// we'll simply eliminate this part of the code during ruby rewriting and this problem will simply
		// go away. If it does have to be dealt with, though, consider grabbing the cached data from ruby
		// via a RAP call.
		//
		// TODO: It actually wouldn't be too hard to generate these arrays on the PHP side and simply memcache
		// them for access when needed. However, we'd likely want to do this for all category type stuff (not
		// just locations, where the problem is showing up) and that will be a task slightly beyond the scope
		// of getting new account creation stuff working, which led me here in the first place.
		$initialMemoryLimit = trim(ini_get("memory_limit"));
		if(strlen($initialMemoryLimit) > 1)
		{
	    	$initialMemoryLimitUnits = strtolower($initialMemoryLimit[strlen($initialMemoryLimit)-1]);
		    switch($initialMemoryLimitUnits) {
				case 'g':
					$initialMemoryLimit *= 1024;
				case 'm':
					$initialMemoryLimit *= 1024;
			}
		}
		else
		{
			// Just set it to something low if we couldn't parse the string correctly
			// so that we force the memory boost. It will get reset to what it actually
			// was later in the calling function.
			$initialMemoryLimit = 0;
		}
		// Let's only set the memory limit up to 60M if it is indeed below that limit in the first place.
		// Otherwise we'd be setting it down ;=).
		if($initialMemoryLimit < 60 * 1024)
		{	
			ini_set("memory_limit","60M");
		}
		
		$query = "SELECT id, parent, name FROM " . $this->name;
		if($this->where)
			$query .= " WHERE " . $this->where;

		$res = $this->db->query($query);

		while($line = $res->fetchrow()){
			$this->data[$line['id']] = $line['name'];
			$this->child[$line['parent']][$line['id']]=$line['name'];
			$this->parent[$line['id']][$line['parent']]=$line['name'];
		}
		if($ret)
			return $this->$ret;
	}

	function makebranch($parent=0, $maxdepth=0, $depth=1, $sort=true){
		if(!isset($this->child[$parent]))
			return array();
		$list=$this->child[$parent];
		if($sort)
			asort($list);

		// TODO: This can still cause a memory error. Technically, it might need memory boosting like in dumpData()
		// above. However, the locations are really the only thing right now that will cause a huge amount of memory
		// to be needed, and as of Saturday, February 28, 2009, I think I've found and eliminated the last place where
		// ->makebranch or ->makeroot needs to be called on location data (so long as RAP is enabled).
		$result=array();
		foreach($list as $key => $val){
			$result[] = array('id' => $key, 'depth' => $depth, 'name' => $val, 'parent' => $parent, 'isparent' => (isset($this->child[$key]) ? 1 : 0));
			if(isset($this->child[$key]) && (!$maxdepth || $maxdepth>$depth))
				$result = array_merge($result, $this->makebranch($key,$maxdepth,$depth+1) );
		}
		return $result;
	}

	function makeroot($catid,$defname="Home",$depth=0){
		if(!$catid){
			if($defname)
				return array(array('id' => 0, 'parent' => 0, 'depth'=>$depth,'name' => $defname));
			else
				return array();
		}
		$list= $this->parent[$catid];

		$key = key($list);
		$val = current($list);

		$ret = $this->makeroot($key,$defname,$depth-1);
		$ret[] = array('id' => $catid, 'parent' => $key, 'depth'=>$depth, 'name' => $val);
		return $ret;
	}

	function deleteBranch($id){
		global $cache;

		$branch = $this->makebranch($this->child,$id);

		$ids = array();
		foreach($branch as $item)
			$ids[] = $item['id'];
		$ids[] = $id;

		$this->db->prepare_query("DELETE FROM " . $this->name . " WHERE id IN (?)", $ids);

	}

	function isValidCat($cat){
		return isset($this->data[$cat]);
	}

	function getCatName($id){
		if(isset($this->data[$id]))
			return $this->data[$id];
		return false;
	}
}
