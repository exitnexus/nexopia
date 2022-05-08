<?

class pair_sql_db extends sql_base { //for multi-master config

	public $host1;
	public $host2;
	
	public $db;

	public $master;
	public $memcache;

	function __construct($databases){
		$hosts = $databases['hosts'];
		
		$this->host1 = new sql_db( $hosts["host1"] );
		$this->host2 = new sql_db( $hosts["host2"] );

		$this->db = null; //has a reference to host1 or host2 from above if one of them has been chosen

		$this->master = $databases['master'];
		$this->memcache = $databases['memcache']; //might be taken from the dbname later?
	}

	function describe(){
		return "pair-" . $this->master . ":" . $this->db->describe();
	}

	function getSplitDBs(){
		return array($this);
	}

	function connect(){ //connects are done as needed
//		$this->db->connect();
	}

	function choosehost(){ //specific to pair_sql_db
		if(!$this->db){
			if(!$this->master){
				global $cache;

				$numtries = 20;

				while($numtries--){
					$host = $cache->get("db-" . $this->memcache);

					if($host){
						$this->master = $host;
						break;
					}

					usleep(300000); //300ms
				}

				if(!$this->master)
					trigger_error("No master database selected for " . $this->memcache, E_USER_ERROR);
			}

			$this->db = & $this->{$this->master};
		}
	}

	function close(){
		$this->host1->close();
		$this->host2->close();
	}

	function massage_query($query){
		return $this->host1->massage_query($query); //doesn't matter which it's sent to
	}

	function query($query, $buffered = true){
		$this->choosehost();

		$this->clearresult();

		$result = $this->db->query($query, $buffered);

		if ($result)
			return $this->setresult($result);
		else
			return false;
	}

	function begin(){
		$this->choosehost();
		return $this->db->begin();
	}

	function commit(){
		$this->choosehost();
		return $this->db->commit();
	}

	function rollback(){
		$this->choosehost();
		return $this->db->rollback();
	}

	function getSeqID($id, $area, $start = false){
		$this->choosehost();
		return $this->db->getSeqID($id, $area, $start);
	}

	function listtables(){
		$this->choosehost();
		return $this->db->listtables();
	}

	function nextAuto($table){
		$this->choosehost();
		return $this->db->nextAuto($table);
	}

	function escape($str){
		$this->choosehost();
		return $this->db->escape($str);
	}

	function explain($query){
		$this->choosehost();
		return $this->db->explain($query);
	}

	function outputQueries($name){
		echo "<table>";
		echo "<tr><td class=header colspan=2><b>$name</b> - pair - " . ($this->master ? $this->master : 'Unknown') . "</td></tr>";
		echo "<tr><td class=body valign=top>host1</td><td class=body>";
		$this->host1->outputQueries("$name");
		echo "</td></tr>";
		echo "<tr><td class=body valign=top>host2</td><td class=body>";
		$this->host2->outputQueries("$name");
		echo "</td></tr>";
		echo "</table>";

/*
		if($this->db || $this->master){
			$this->choosehost();
			$this->db->outputQueries("$name");
		}else{
			echo "DB move in progress, but not needed, didn't ask which to use.";
		}
*/
	}

	function backup($basedir,$status=1){
		$this->choosehost();
		return $this->db->backup($basedir, $status);
	}

	function analyze($status = 1, $skip = array()){
		$this->choosehost();
		return $this->db->analyze($status, $skip);
	}

	function optimize($status = 1, $skip = array()){
		$this->choosehost();
		return $this->db->optimize($status, $skip);
	}

	function setslowtime($time){
		$this->host1->setslowtime($time);
		$this->host2->setslowtime($time);
	}
	function getnumqueries(){
		return $this->host1->getnumqueries() + $this->host2->getnumqueries();
	}

	function getquerytime(){
		return $this->host1->getquerytime() + $this->host2->getquerytime();
	}
}

