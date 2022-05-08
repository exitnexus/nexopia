<?



class category{
	var $catdb;

	var $name;
	var $where;

	var $data = array();
	var $child = array();
	var $parent = array();

	function category($name, $where = ""){ //table of type id,parent,name
		global $db, $cache;

		$this->name = $name;
		$this->where = $where;

		$this->catdb = & $db;

		$this->data = $cache->hdget($name . "d", array('function' => array($this, 'dumpData'), 'params' => array('data')));
		$this->child = $cache->hdget($name . "c", array('function' => array($this, 'dumpData'), 'params' => array('child')));
		$this->parent = $cache->hdget($name . "p", array('function' => array($this, 'dumpData'), 'params' => array('parent')));

/*
		if(!$this->data || !$this->child || !$this->parent){
			$this->dumpData();

			$cache->hdput($name . "data", $this->data, false);
			$cache->hdput($name . "child", $this->child, false);
			$cache->hdput($name . "parent", $this->parent, false);
		}
*/
	}

	function dumpData($ret = false){

		$query = "SELECT id, parent, name FROM " . $this->name;
		if($this->where)
			$query .= " WHERE " . $this->where;

		$this->catdb->query($query);

		while($line = $this->catdb->fetchrow()){
			$this->data[$line['id']] = $line['name'];
			$this->child[$line['parent']][$line['id']]=$line['name'];
			$this->parent[$line['id']][$line['parent']]=$line['name'];
		}
		if($ret)
			return $this->$ret;
	}

	function makebranch($parent=0,$maxdepth=0,$depth=1, $sort=true){
		if(!isset($this->child[$parent]))
			return array();
		$list=$this->child[$parent];
		if($sort)
			asort($list);

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
		$ret[] = array('id' => $catid, 'parent' => $key, 'depth'=>$depth,'name' => $val);
		return $ret;
	}

	function deleteBranch($id){
		global $db, $cache;

		$branch = $this->makebranch($this->child,$id);

		$ids = array();
		foreach($branch as $item)
			$ids[] = $item['id'];
		$ids[] = $id;

		$this->catdb->prepare_query("DELETE FROM " . $this->name . " WHERE id IN (?)", $ids);

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
