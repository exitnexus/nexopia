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
			if($cached){
				$this->data = $cache->hdget($name . "d", 0, array('function' => array(&$this, 'dumpData'), 'params' => array('data')));
				$this->child = $cache->hdget($name . "c", 0, array('function' => array(&$this, 'dumpData'), 'params' => array('child')));
				$this->parent = $cache->hdget($name . "p", 0, array('function' => array(&$this, 'dumpData'), 'params' => array('parent')));
			}else{
				$this->dumpData();
			}
			
			$catcache[$name] = & $this;
		}
	}

	function dumpData($ret = false){

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
