<?

class multiple_sql_db extends sql_base { //equiv to raid 1

	public $selectdb;
	public $insertdb;
	public $backupdb;
	public $num_queries = 0;
	public $in_transaction = 0;
	public $time = 0;
	public $needkey;

	function __construct($databases, $plus = false){
		$roles = $databases['roles'];
		$this->insertdb = new sql_db(	$roles["insert"] );

		$select = chooseRandomServer($roles['select'], $plus);

		if(	$roles["insert"]["host"] 	== $select["host"] &&
			$roles["insert"]["login"] 	== $select["login"] &&
			$roles["insert"]["passwd"]	== $select["passwd"] &&
			$roles["insert"]["db"]		== $select["db"] ){

			$this->selectdb = & $this->insertdb;
		}else{
			$this->selectdb = new sql_db(	$select );
		}

		$this->backupdb = new sql_db(	$roles["backup"] );

		$this->needkey = (isset($databases["needkey"]) ? $databases["needkey"] : DB_KEY_OPTIONAL);
	}

	function getSplitDBs()
	{
		return array($this);
	}

	function chooseSplitDBs($id)
	{
		return $this->insertdb;
	}

	function connect(){ //connections are created at query time
//		$this->selectdb->connect();
//		$this->insertdb->connect();
	}

	function close(){
		$this->selectdb->close();
		$this->insertdb->close();
		$this->backupdb->close();
	}

	function massage_query($query)
	{
		return $this->insertdb->massage_query($query);
	}

	function query(){ //$keys=false, $query, $buffered = true
		$arg_list = func_get_args();

		if(!count($arg_list))
			trigger_error("not enough params", E_USER_ERROR);

		$this->insertdb->parseargs($arg_list);

		$query = $arg_list[0];
		$buffered = (isset($arg_list[1]) ? $arg_list[1] : true);

		$this->clearresult();

		if(strtoupper(substr($query,0,5)) == "BEGIN" || strtoupper(substr($query,0,17)) == "START TRANSACTION")
			return $this->begin();
		if(strtoupper(substr($query,0,6)) == "COMMIT")
			return $this->commit();
		if(strtoupper(substr($query,0,8)) == "ROLLBACK")
			return $this->rollback();

		if(strtoupper(substr($query,0,4)) == "LOCK")
			$this->in_transaction = true;

		if(strtoupper(substr($query,0,6)) == "UNLOCK")
			$this->in_transaction = false;

		unset($this->query_result);

		if(strtoupper(substr($query,0,6)) == "SELECT" && $this->in_transaction == false){
			//$this->query_result = array("con" => "s", "result" => $this->selectdb->query($query, $buffered));
			$result = $this->selectdb->query($query, $buffered);
		}else{
			$result = $this->insertdb->query($query, $buffered);
		}
		if ($result)
			return $this->setresult($result);
		else
			return false;
	}

	function begin(){
		$this->in_transaction = true;
		return $this->insertdb->begin();
	}

	function commit(){
		$this->in_transaction = false;
		return $this->insertdb->commit();
	}

	function rollback(){
		$this->in_transaction = false;
		return $this->insertdb->rollback();
	}

	function getSeqID($id, $area, $start = false){
		return $this->insertdb->getSeqID($id, $area, $start);
	}

	function listtables(){
		return $this->insertdb->listtables();
	}

	function nextAuto($table){
		return $this->insertdb->nextAuto($table);
	}

	function escape($str){
		return $this->insertdb->escape($str);
	}

	function explain($query){
		return $this->insertdb->explain($query);
	}

	function outputQueries($name){
		echo "<table>";
		echo "<tr><td class=header colspan=2><b>$name</b></td></tr>";
		echo "<tr><td class=body valign=top>ins</td><td class=body>";
		$this->insertdb->outputQueries("$name");
		echo "</td></tr>";
		if($this->insertdb !== $this->selectdb){
			echo "<tr><td class=body valign=top>sel</td><td class=body>";
			$this->selectdb->outputQueries("$name");
			echo "</td></tr>";
		}
//*
		if($this->insertdb !== $this->backupdb){
			echo "<tr><td class=body valign=top>bak</td><td class=body>";
			$this->backupdb->outputQueries("$name");
			echo "</td></tr>";
		}
//*/
		echo "</table>";
	}

	function backup($basedir,$status=1){
		return $this->backupdb->backup($basedir, $status);
	}

	function analyze($status = 1){
		return $this->insertdb->analyze($status);
	}

	function optimize($status = 1){
		return $this->insertdb->optimize($status);
	}

	function setslowtime($time){
		$this->insertdb->setslowtime($time);
		$this->selectdb->setslowtime($time);
		$this->backupdb->setslowtime($time);
	}
	function getnumqueries(){
		return $this->insertdb->getnumqueries() + $this->selectdb->getnumqueries();
	}

	function getquerytime(){
		return $this->insertdb->getquerytime() + $this->selectdb->getquerytime();
	}
}

function split_db_hash($dbobj, $keys, $writeop)
{
	$ids = array();

	foreach($keys as $key){
		$id = abs(intval($key)) % $dbobj->numdbs; //fix getSeqID as well when changing this to a new method
		$ids[$id] = $id;
	}
	return array_values($ids); //ie renumber from 0
}

function split_db_user($dbobj, $keys, $writeop)
{
	global $masterdb, $cache, $lockSplitWriteOps;

	if (!$lockSplitWriteOps)
		$writeop = false; // ignore writeop if the config variable says not to

	if (!$writeop)
	{
		static $readusermap = array(0 => 0); // uid 0 is special and indicates the anon server
		$usermap = &$readusermap;
	} else {
		static $writeusermap = array(0 => 0); // use a different usermap to prevent bypassing the lock where a read op preceeds a write op.
		$usermap = &$writeusermap;
	}

	$keys = array_combine($keys, $keys);
	$ids = array();

	foreach($keys as $k){
		if(isset($usermap[$k])){
			if($usermap[$k] != -1)
				$ids[$k] = $usermap[$k];
			unset($keys[$k]);
		}
	}

	if($keys){
		$cached = array();
		if (!$writeop)
			$cached = $cache->get_multi(array_keys($keys), 'serverid-user-'); // only look in memcache if not a writeop

		foreach($cached as $k => $v){
			if($v != -1)
				$ids[$k] = $v;
			$usermap[$k] = $v;
			unset($keys[$k]);
		}

		if($keys){
			$forupdate = "";
			if ($writeop)
				$forupdate = "FOR UPDATE"; // if this is a writeop, use FOR UPDATE to make this wait for the release of a potential lock
			// server 0 is assumed to be an anonymous server, so we don't need to offset this.
			$result = $masterdb->prepare_query("SELECT id, serverid FROM accounts WHERE id IN (#) $forupdate", array_keys($keys));
			while($row = $result->fetchrow()){
				$ids[ $row['id'] ] = $row['serverid'];

				$usermap[$row['id']] = $row['serverid'];
				unset($keys[$row['id']]);

				$cache->put("serverid-user-$row[id]", $row['serverid'], 7*24*60*60);
			}

			if($keys){
				foreach($keys as $k){
					$usermap[$k] = -1;
					$cache->put("serverid-user-$k", -1, 7*24*60*60);
				}
			}
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
	public $needkey;
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
		$this->needkey = DB_KEY_REQUIRED;
		$this->splitfunc = $splitfunc;
	}

	function getSplitDBs()
	{
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

	function massage_query($query)
	{
		return $this->dbs[0]->massage_query($query);
	}

	function query(){ //$keys = true, $query, $buffered = true
		$arg_list = func_get_args();

		$keys = null;
//		if(is_numeric($arg_list[0]) || is_array($arg_list[0]) || is_bool($arg_list[0]) || $arg_list[0] === null){
		if(is_numeric($arg_list[0]) || !is_string($arg_list[0])){
			$keys = array_shift($arg_list);
//			trigger_error("multiple_sql_db_hash->query shouldn't take a real list of keys, use squery for that, or use the % placeholder", E_USER_NOTICE);
		}

		$query = $arg_list[0];
		$buffered = (isset($arg_list[1]) ? $arg_list[1] : true);

		if($keys === null){
			$keys = $this->getServerValues($query); //returns false if none found, ie run on all

			if(is_array($keys))
				$keys = array_unique($keys);
		}

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

		foreach($ids as $id)
		{
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

	function analyze($status = 1){
		for($i = 0; $i < $this->numdbs; $i++){
			$this->dbs[$i]->debugoutput("Analyze db $i", $status);
			$this->dbs[$i]->analyze($status);
		}
	}

	function optimize($status = 1){
		for($i = 0; $i < $this->numdbs; $i++){
			$this->dbs[$i]->debugoutput("optimize db $i", $status);
			$this->dbs[$i]->optimize($status);
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
