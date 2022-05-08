<?

/*

Try to implement a connection sharing mechanism if several databases are on the same server.
This attempt works by sending a selectdb command before each query to make sure it's using the right server.
The mysql layer below would take care of actually swapping dbs if needed, etc.
The setup section would create one real mysql object and pass it to several of these objects. 
The db handles that the code uses would refer to the shared objects, not the real connection.

The problems arrise when you want to display only the queries that were done to this db, or when you have open transactions.
If a transaction is open, then another transaction is opened by a different handle that shares the connection, they'd conflict.
If you were to block in that case, you'd likely get deadlocks. The better solution is to open a new connection, 
but that introduces alot more complexity.

*/

class shared_sql_db extends sql_base { //connection sharing

	public $db;
	public $dbname

	public $num_queries = 0;
	public $in_transaction = 0;
	public $time = 0;
	public $needkey;

	function __construct(& $db, $dbname){
		$this->db = & $db;
		$this->dbname = $dbname;
	}

	function getSplitDBs(){
		return array($this);
	}

	function connect(){ //connections are created at query time
	}

	function close(){ //do nothing as it may still be used by the others sharing this connection
	}

	function massage_query($query){
		return $this->db->massage_query($query);
	}

	function query(){ //$keys=false, $query, $buffered = true
		$arg_list = func_get_args();

		if(!count($arg_list))
			trigger_error("not enough params", E_USER_ERROR);

		$this->insertdb->parseargs($arg_list);

		$query = $arg_list[0];
		$buffered = (isset($arg_list[1]) ? $arg_list[1] : true);

		$this->clearresult();

		$this->db->selectdb($this->dbname);

		unset($this->query_result);

		$result = $this->db->query($query, $buffered);

		if($result)
			return $this->setresult($result);
		else
			return false;
	}

	function begin(){
		$this->db->selectdb($this->dbname);
		return $this->db->begin();
	}

	function commit(){
		$this->db->selectdb($this->dbname);
		return $this->db->commit();
	}

	function rollback(){
		$this->db->selectdb($this->dbname);
		return $this->db->rollback();
	}

	function getSeqID($id, $area, $start = false){
		$this->db->selectdb($this->dbname);
		return $this->db->getSeqID($id, $area, $start);
	}

	function listtables(){
		$this->db->selectdb($this->dbname);
		return $this->db->listtables();
	}

	function nextAuto($table){
		$this->db->selectdb($this->dbname);
		return $this->db->nextAuto($table);
	}

	function escape($str){
		return $this->db->escape($str);
	}

	function explain($query){
		$this->db->selectdb($this->dbname);
		return $this->db->explain($query);
	}

	function outputQueries($name){
		echo "<table>";
		echo "<tr><td class=header colspan=2><b>$name</b></td></tr>";
		echo "<tr><td class=body valign=top>ins</td><td class=body>";

		$this->db->outputQueries("$name");

		echo "</td></tr>";
		echo "</table>";
	}

	function backup($basedir,$status=1){
		$this->db->selectdb($this->dbname);
		return $this->db->backup($basedir, $status);
	}

	function analyze($status = 1, $skip = array()){
		$this->db->selectdb($this->dbname);
		return $this->db->analyze($status, $skip);
	}

	function optimize($status = 1, $skip = array()){
		$this->db->selectdb($this->dbname);
		return $this->db->optimize($status, $skip);
	}

	function setslowtime($time){
		$this->db->setslowtime($time);
	}
	function getnumqueries(){
		return $this->db->getnumqueries();
	}

	function getquerytime(){
		return $this->db->getquerytime();
	}
}
