<?

function split_db_hash($dbobj, $keys, $writeop)
{
	$ids = array();

	foreach($keys as $key){
		$id = abs(intval($key)) % $dbobj->numdbs; //fix getSeqID as well when changing this to a new method
		$ids[$id] = $id;
	}
	return array_values($ids); //ie renumber from 0
}

function split_db_user($dbobj, $keys, $writeop){
	global $masterdb, $cache, $lockSplitWriteOps;

//local cache of mappings
	static $usermap = array(0 => 0); //special value -1 means user doesn't exit, -2 means user being moved

//keys to get
	$keys = array_combine($keys, $keys);

//ids to return
	$ids = array();

//grab from local cache if allowed
	if(!$lockSplitWriteOps){
		foreach($keys as $k){
			if(isset($usermap[$k])){
				if($usermap[$k] != -1)
					$ids[$k] = $usermap[$k];
				unset($keys[$k]);
			}
		}
	}

	if(!$keys)
		return $ids;

//grab from memcache

//spin on entries that are marked as moving.
//If they still aren't grabbed from memcache, fall back to the db, where it will wait for the lock
//Grabbing from the db will fix errors of entries being left as moving when they shouldn't be.
	$i = 20;
	while($i--){
		$moving = array();

		$cached = $cache->get_multi($keys, 'serverid-user-');

		foreach($cached as $uid => $serverid){
			if($serverid == -2){ //moving, try again
				$moving[$uid] = $uid;
				continue;
			}

			if($serverid != -1)
				$ids[$uid] = $serverid;
			$usermap[$uid] = $serverid;
			unset($keys[$uid]);
		}

		if(!$moving)
			break;

		usleep(100000); //sleep 100ms
	}

	if(!$keys)
		return $ids;

//grab from database
	if($lockSplitWriteOps)
		$masterdb->begin();

	$result = $masterdb->prepare_query("SELECT id, serverid FROM accounts WHERE id IN (#)" . ($lockSplitWriteOps ? " FOR UPDATE" : ''), $keys);
	while($row = $result->fetchrow()){
		$ids[$row['id']] = $row['serverid'];

		$usermap[$row['id']] = $row['serverid'];
		unset($keys[$row['id']]);

		$cache->put("serverid-user-$row[id]", $row['serverid'], 7*24*60*60);
	}
	if($lockSplitWriteOps)
		$masterdb->commit();

//don't exist, mark as such
	if($keys){
		foreach($keys as $uid){
			$usermap[$uid] = -1;
			$cache->put("serverid-user-$uid", -1, 7*24*60*60);
		}
	}

	return $ids;
}

class multiple_sql_db_split extends sql_base { //equiv to raid 0

/*
side effects:
-ORDER BY is by server, so could return: 0,2,4,1,3
-LIMIT is by server, so could return up to $numservers * limit, and of course the offset is useless
-GROUP BY won't group across servers
-count(*) type queries (agregates) will return one result per server if it is sent to more than one server
*/


	public $dbs;
	public $numdbs;
	public $num_queries = 0;
	public $in_transaction = 0;
	public $time = 0;
	public $splitfunc;

	function __construct($databases, $splitfunc = 'split_db_hash', $plus = false){
		$this->dbs = array();
		foreach($databases as $database){
			if(is_object($database)) //is already constructed
				$this->dbs[] = $database;
			else if(isset($database['roles'])) //is a multiple_sql_db db
				$this->dbs[] = new multiple_sql_db( $database );
			else //is an sql_db
				$this->dbs[] = new sql_db( $database );
		}
		$this->numdbs = count($databases);
		$this->splitfunc = $splitfunc;
	}

	function describe(){
		return "multi:" . $this->db->describe();
	}

	function getSplitDBs(){
		return $this->dbs;
	}

	function chooseSplitDBs($id, $writeop = false)
	{
		$idfunc = $this->splitfunc;
		return $this->dbs[$idfunc($this, $id, $writeop)];
	}

	function connect($id = false){ //empty because connections are created as needed at query time
/*		if($id === false){
			for($i = 0; $i < $this->numdbs; $i++)
				$this->dbs[$i]->connect();
		}else
			$this->dbs[$id]->connect();
*/	}

	function close(){
		for($i = 0; $i < $this->numdbs; $i++)
			$this->dbs[$i]->close();
	}

	function massage_query($query){
		return $this->dbs[0]->massage_query($query);
	}

	function query($query, $buffered = true){
		$keys = $this->getServerValues($query); //returns false if none found, ie run on all

		if(is_array($keys))
			$keys = array_unique($keys);

		return $this->squery($keys, $query, $buffered);
	}

	function squery($keys, $query, $buffered = true){
		if(func_num_args() < 2)
			trigger_error("Missing key for query: $query", E_USER_ERROR);

		if($keys === false || $keys === null){ //do for all
			if(strtoupper(substr($query,0,6)) == "INSERT" && !strpos($query, "SELECT")) //allow select, insert ... select, update, delete, analyze, optomize, alter table, etc, but not single row changing ops
				$this->debugerror("Cannot INSERT to all dbs", ": $query", true);

			$ids = array_keys($this->dbs);
			unset($ids[0]);

			$query .= " /**: all :**/";
		}else{
			$writeop = false;
			if(strtoupper(substr($query,0,6)) != "SELECT")
				$writeop = true;

			if(!is_array($keys))
				$keys = array($keys);

			$idfunc = $this->splitfunc;
			$ids = $idfunc($this, $keys, $writeop);

			if ($writeop)
				$query .= " /**: writeop :**/";
		}

		$ids = array_unique($ids);

		unset($this->query_result);

		$results = array();

		if(!count($ids))
			trigger_error("Query doesn't map to a server: $query", E_USER_NOTICE);

		foreach($ids as $id){
			if(!isset($this->dbs[$id])){
				trigger_error("Query maps to a server that doesn't exist: $query", E_USER_NOTICE);
				continue;
			}
			$result = $this->dbs[$id]->query($query, $buffered);
			if ($result)
				$results[$id] = $result;
		}

		if ($results)
			return $this->setresult(new multiple_db_result($results));
		else
			return $this->setresult(new sql_empty_result());
	}

	function unbuffered_query($query){
		return $this->query($query, false);
	}

	function repeatquery($query, $limit=1000){
		do{
			$this->query("$query LIMIT $limit");
			usleep(10);
		}while($this->affectedrows() >= $limit);
	}

	function getSeqID($id, $area, $start = false){

		$idfunc = $this->splitfunc;
		$serverids = $idfunc($this, array($id), true);

		if ($serverids)
			return $this->dbs[array_shift($serverids)]->getSeqID($id, $area, $start);
		else
			return false;
	}

	function begin(){
		$ids = array_keys($this->dbs);
		foreach($ids as $id)
			$this->dbs[$id]->begin();
	}

	function commit(){
		$ids = array_keys($this->dbs);
		foreach($ids as $id)
			$this->dbs[$id]->commit();
	}

	function rollback(){
		$ids = array_keys($this->dbs);
		foreach($ids as $id)
			$this->dbs[$id]->rollback();
	}

	function listtables(){
		return $this->dbs[0]->listtables();
	}

	function nextAuto($table){
		trigger_error("Cannot get next auto on a multi-db table", E_USER_ERROR);
	}

	function escape($str){
		return $this->dbs[0]->escape($str);
	}

	function explain($query){
		return $this->dbs[0]->explain($query);
	}

	function outputQueries($name){
		echo "<table><tr><td class=header colspan=2><b>$name</b></td></tr>";
		for($i = 0; $i < $this->numdbs; $i++){
			echo "<tr><td class=body valign=top>" . ($i+1) . "/$this->numdbs</td><td class=body>";
			$this->dbs[$i]->outputQueries("$name");
			echo "</td></tr>";
		}
		echo "</table>";
	}

	function backup($basedir,$status=1){
		for($i = 0; $i < $this->numdbs; $i++){
			$this->dbs[$i]->debugoutput("backup db $i", $status);
			$this->dbs[$i]->backup($basedir, $status, $i);
		}
	}

	function analyze($status = 1, $skip = array()){
		for($i = 0; $i < $this->numdbs; $i++){
			$this->dbs[$i]->debugoutput("Analyze db $i", $status);
			$this->dbs[$i]->analyze($status, $skip);
		}
	}

	function optimize($status = 1, $skip = array()){
		for($i = 0; $i < $this->numdbs; $i++){
			$this->dbs[$i]->debugoutput("optimize db $i", $status);
			$this->dbs[$i]->optimize($status, $skip);
		}
	}

	function setslowtime($time){
		foreach($this->dbs as $db)
			$db->setslowtime($time);
	}

	function getnumqueries(){
		$num = 0;

		foreach($this->dbs as $db)
			$num += $db->getnumqueries();

		return $num;
	}

	function getquerytime(){
		$time = 0;

		foreach($this->dbs as $db)
			$time += $db->getquerytime();

		return $time;
	}
}

class multiple_db_result
{
	private $results;

	function __construct($results)
	{
		$this->results = $results;
	}

/*	function numrows(){
		$num = 0;
		foreach($this->results as $result)
			$num += $result->numrows();

		return $num;
	}*/

	function affectedrows(){
		$num = 0;
		foreach($this->results as $result)
			$num += $result->affectedrows();

		return $num;
	}

	function totalrows(){
		$num = 0;
		foreach ($this->results as $result)
			$num += $result->totalrows();

		return $num;
	}

	function numfields(){
		foreach ($this->results as $first) break;
		return $first->numfields();
	}

	function fieldname($offset){
		foreach ($this->results as $first) break;
		return $first->fieldname($offset);
	}

	function fieldtype($offset){
		foreach ($this->results as $first) break;
		return $first->fieldtype($offset);
	}

	function fetchrow($type = DB_ASSOC){
		foreach($this->results as $id => $result){
			$ret = $result->fetchrow($type);
			if($ret)
				return $ret;
		}
		return false;
	}

	function fetchrowset($type = DB_ASSOC){
		$rows = array();
		foreach($this->results as $id => $result)
			$rows = array_merge($rows, $result->fetchrowset($type) );

		return $rows;
	}

	function fetchkeyedrowset($key){
		$rows = array();
		foreach($this->results as $id => $result)
			$rows = array_merge($rows, $result->fetchkeyedrowset($key) );

		return $rows;
	}

	function fetchfield($field=0, $rownum = -1){
		if(count($this->results) >= 2)
			trigger_error("Cannot fetchfield on a multi-db query", E_USER_ERROR);

		foreach ($this->results as $first) break;
		return $first->fetchfield($field, $rownum);
	}

	function fetchfields($keycol){
		$rows = array();
		foreach($this->results as $id => $result)
			$rows += $result->fetchfields($keycol); //use += instead of array_merge so that it doesn't get renumbered

		return $rows;
	}

	function rowseek($rownum){
		if(count($this->results) >= 2)
			trigger_error("Cannot rowseek on a multi-db query", E_USER_ERROR);

		foreach ($this->results as $first) break;
		return $first->rowseek($rownum);
	}

	function insertid(){
		if(count($this->results) >= 2)
			trigger_error("Cannot get insertid on a multi-db query", E_USER_ERROR);

		foreach ($this->results as $first) break;
		return $first->insertid();
	}

	function freeresult($query_id = 0){
		foreach($this->results as $result)
			$result->freeresult();
	}
}
