<?

class multiple_sql_db extends sql_base { //equiv to raid 1

	public $selectdb;
	public $insertdb;
	public $backupdb;
	public $num_queries = 0;
	public $in_transaction = 0;
	public $time = 0;

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
	}

	function describe(){
		return "mirror-master:" . $this->insertdb->describe();
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

	function massage_query($query){
		return $this->insertdb->massage_query($query);
	}

	function query($query, $buffered = true){
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

	function analyze($status = 1, $skip = array()){
		return $this->insertdb->analyze($status, $skip);
	}

	function optimize($status = 1, $skip = array()){
		return $this->insertdb->optimize($status, $skip);
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
