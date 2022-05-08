<?

define('DB_NUM',   MYSQL_NUM);
define('DB_ASSOC', MYSQL_ASSOC);
define('DB_BOTH',  MYSQL_BOTH);

class sql_db extends sql_base { //must be saved by reference

	public $db_connect_id;
	public $num_queries = 0;
	public $in_transaction = 0;
	public $time = 0;
	public $lastquerytime = 0;

	public $connectiontime = 0;
	public $connectioncreationtime = 0;

	public $server;
	public $user;
	public $password;
	public $dbname;

	public $transactions;
	public $seqtable = false;

	public $needkey;

	function __construct($options){
		$this->server 		= $options['host'];
		$this->user 		= $options['login'];
		$this->password 	= $options['passwd'];
		$this->dbname 		= $options['db'];

		$this->persistency 	= (isset($options['persistency']) 	? $options['persistency'] : false);

		$this->needkey		= (isset($options['needkey']) 		? $options['needkey'] : DB_KEY_OPTIONAL);
		$this->transactions	= (isset($options['transactions'])	? $options['transactions'] : false);
		$this->seqtable     = (isset($options['seqtable'])	    ? $options['seqtable'] : false);

		parent::__construct($options);
	}

	function getSplitDBs()
	{
		return array($this);
	}

	function chooseSplitDBs($id)
	{
		return $this;
	}

	function connect(){
		if($this->db_connect_id){
			if(time() - $this->lastquerytime > 10)
				return mysql_ping($this->db_connect_id);
			return true;
		}

		if($this->persistency){
			$this->db_connect_id = @mysql_pconnect($this->server, $this->user, $this->password, true);
		}else{
			$this->db_connect_id = @mysql_connect($this->server, $this->user, $this->password, true);
			register_shutdown_function(array(&$this, "close"));
		}

		if(!$this->db_connect_id){
			$error = $this->error();
			$this->debugerror("Failed to Connect to Database: " . $this->dbname, " Error Code $error[code]: $error[message]", true);
		}

		$this->connectioncreationtime = gettime();

		if($this->dbname){
			$dbselect = mysql_select_db($this->dbname);

			if(!$dbselect){
				mysql_close($this->db_connect_id);
				$this->db_connect_id = $dbselect;
			}
		}
	}

	function close(){
		if(!$this->db_connect_id)
			return false;

		if($this->in_transaction)
			$this->commit();

		$result = mysql_close($this->db_connect_id);

		if($result)
			$this->db_connect_id = false;

		$this->connectiontime += gettime() - $this->connectioncreationtime;
		$this->connectioncreationtime = 0;

		return $result;
	}

	function query(){ //$keys = false, $query, $buffered = true, $reconnect = true
		$arg_list = func_get_args();

		$this->parseargs($arg_list);

		$query = $arg_list[0];
		$buffered = isset($arg_list[1]) ? $arg_list[1] : true;
		$reconnect = isset($arg_list[2]) ? $arg_list[2] : true;

		$this->connect();

		$this->clearresult();

		$starttime = gettime();
		$this->lastquery = $query;

		$calcfound = false;
		if (preg_match('/^SELECT\s+(DISTINCT\s+)?SQL_CALC_FOUND_ROWS/', $query))
		{
			$buffered = true;
			$calcfound = true;
		}

		if($buffered)
			$query_result = mysql_query($query, $this->db_connect_id);
		else
			$query_result = mysql_unbuffered_query($query, $this->db_connect_id);

		$endtime = gettime();
		$querytime = $endtime - $starttime;

		$this->time += $querytime;
		$this->lastquerytime = floor($endtime / 10000); // save a time() call
		$this->num_queries++;

		$this->debug($query, $querytime);


		if($query_result){

			if ($calcfound)
			{
				$calcfound_result = mysql_query("SELECT FOUND_ROWS()", $this->db_connect_id);
				$calcfound = mysql_result($calcfound_result, 0);
			}

			$result = new db_result( $this, $query_result, $calcfound);
			$this->setresult($result);

			return $result;

		}else{
			$error = $this->error();
			if(($error['code'] == 2013 || $error['code'] == 2006) && $reconnect){ //disconnect
				$this->debugerror("SQL Error", ": $error[code], $error[message]. Query: $query", false);
//				trigger_error("SQL error: $error[code], $error[message]. Query: $query", E_USER_WARNING);
				$this->close();

				$result = $this->query($query, $buffered, false);
				return $result; //redo query, don't do a second time.
			}else
				$this->debugerror("SQL Error", ": $error[code], $error[message]. Query: $query", true);
//				trigger_error("SQL error: $error[code], $error[message]. Query: $query", E_USER_ERROR);

			return false;
		}
	}

	function begin(){
		if($this->transactions){
			$this->in_transaction = true;
			$this->query("START TRANSACTION");
			return $this->query_result; //true/false
		}
		return false;
	}

	function commit(){
		if($this->transactions && $this->db_connect_id){ //don't commit if no connection exists
			$this->in_transaction = false;
			$this->query("COMMIT");
			return $this->query_result; //true/false
		}
		return false;
	}

	function rollback(){
		if($this->transactions && $this->db_connect_id){ //don't rollback if no connection exists
			$this->in_transaction = false;
			$this->query("ROLLBACK");
			return $this->query_result; //true/false
		}
		return false;
	}

	function listtables(){
/*
		$this->connect();

		if(!$this->db_connect_id)
			return false;

		$this->query_result = mysql_list_tables($this->dbname,$this->db_connect_id);
		return $this->query_result;
*/
		return $this->query("SHOW TABLE STATUS");
	}

	function nextAuto($table){
		$this->query("SHOW TABLE STATUS");

		while($line = $this->fetchrow()){
			if($line['Name'] != $table)
				continue;

			return $line['Auto_increment'];
		}
		return false;
	}

	function getSeqID($id, $area){ //eg: $userid, messages
		if(!$this->seqtable)
			trigger_error("Call getSeqID ($id, $area) without a seqtable defined for this db", E_USER_ERROR);

		$this->prepare_query("UPDATE " . $this->seqtable . " SET max = LAST_INSERT_ID(max+1) WHERE id = # && area = #", $id, $area);

		$inid = $this->insertid();

		if($inid)
			return $inid;

		$this->prepare_query("INSERT IGNORE INTO " . $this->seqtable . " SET max = 1, id = #, area = #", $id, $area);

		if($this->affectedrows())
			return 1;
		else
			return $this->getSeqID($id, $area);
	}

	function error(){
		$result['message'] = mysql_error($this->db_connect_id);
		$result['code'] = mysql_errno($this->db_connect_id);

		return $result;
	}

	function escape($str){
		return mysql_escape_string($str);
	}

	function format_values_backup(&$value, $key){
		$value = str_replace("\n","\\\n",$value);
		$value = str_replace('"','\"',$value);
	}

	function backup($basedir, $status=1, $namepostfix = ''){
		set_time_limit(0);

		$this->check($status);

		if($namepostfix)
			$namepostfix = "-$namepostfix";

		$time0 = time();

		$this->debugoutput("Starting backup<br>\n", $status);

		$cp = fopen("$basedir/dbcreation.sql","w");

		$tables = array();

		$this->query("FLUSH TABLES WITH READ LOCK");

		$this->query("SHOW SLAVE STATUS");
		$line = $this->fetchrow();

		if($line){
			fwrite($cp, "#Slave Status:\n\n");
			foreach($line as $k => $v)
				fwrite($cp, "# $k : $v\n");
			fwrite($cp, "\n\n");
		}

		$this->query("SHOW TABLE STATUS");

		$tablestatus = array();
		while($line = $this->fetchrow())
			$tablestatus[$line['Name']] = $line;

		$tableresult = $this->listtables();
		while (list($name) = $this->fetchrow($tableresult,DB_NUM))
			$tables[] = $name;

		foreach($tables as $name){
			fwrite($cp, "# starting table $name\n");

			$this->debugoutput("Starting table $name ... ", $status);

			$time1 = time();

			fwrite($cp, "DROP `$name`;\n");

			$result = $this->query("SHOW CREATE TABLE `$name`");
			fwrite($cp, $this->fetchfield(1,0,$result) . ";\n\n");
/*
//single file, fails if output file is bigger than 2gb
			$fp = fopen("$basedir/$name.dump", "a");

			$result = $this->unbuffered_query("SELECT * FROM `$name`");

			$writes=0;
			$rows = 0;
			$output="";
			while($line = $this->fetchrow($result,DB_NUM)){
				$rows++;
				array_walk($line,array(&$this,'format_values_backup'));
				$output .= '"' . implode("\",\"", $line) . "\"\n";
				if(strlen($output) > 256*1024){ //256kb
					fwrite($fp,$output);
					$output="";
					$writes++;
					if($writes % 20 == 0) //approx every 5mb
						$this->debugoutput($writes/4 . " Mb - " . number_format($rows/$tablestatus[$name]['Rows'], 2) . "% . ", $status);
				}
			}

			fwrite($fp,$output);
			$output="";
			fclose($fp);
*/

//*
			$result = $this->unbuffered_query("SELECT * FROM `$name`");

			$filenum = 0;

			$fp = fopen("$basedir/$name.dump.$filenum", "w");
			$this->debugoutput("Opening $name$namepostfix.dump.$filenum ... ", $status);

			$writes=0;
			$writesfile=0;
			$rows = 0;
			$output="";

			while(1){
				while($line = $this->fetchrow($result,DB_NUM)){
					$rows++;

//					array_walk($line, array(&$this,'format_values_backup'));
//					$output .= '"' . implode('","', $line) . "\"\n";

					$first = true;

					foreach($line as $val){
						if($first)
							$first = false;
						else
							$output .= ',';

						if($val === NULL)
							$output .= "\N";
						else
							$output .= '"' . str_replace('"','\"', str_replace("\n","\\\n", $val)) . '"';
					}

					$output .= "\n";

					if(strlen($output) > 256*1024){ //256kb, using strlen is faster than keeping track manually
						fwrite($fp, $output);
						$output="";
						$writes++;
						$writesfile++;
						if($status && $writes % 20 == 0) //approx every 5mb
							$this->debugoutput($writes/4 . " Mb - " . number_format($rows/$tablestatus[$name]['Rows'], 2) . "% . ", $status);

					   	if($writesfile >= 4000) //approx 1gb
							break;
					}
				}

				if(!$line)
					break;

				fclose($fp);
				$filenum++;

				$fp = fopen("$basedir/$name$namepostfix.dump.$filenum", "w");
				$this->debugoutput("Opening $name$namepostfix.dump.$filenum ... ", $status);

				$writesfile = 0;
			}

			$this->freeresult($result);

			fwrite($fp, $output);
			$output="";
			fclose($fp);
//*/

//			$this->query("SELECT * FROM `$name` INTO OUTFILE '$basedir/$name.dump' FIELDS TERMINATED BY ',' ENCLOSED BY '\"' ESCAPED BY '\\\\' LINES TERMINATED BY '\\n'");

			$time2 = time();

			$this->debugoutput("Dumped $name ... " . ($time2 - $time1) . "s<br>\n", $status);
		}

		fclose($cp);

		$this->query("UNLOCK TABLES");

		$time2 = time();

		$this->debugoutput("dumping took: " . ($time2 - $time0) . " seconds<br>\n", $status);
	}

	function restore($backupdbdir, $status = 1, $tables = array()){
		$filename = "$backupdbdir/dbcreation.sql";

		echo "Start file $filename<br>\n";
		flush();

		$fp = fopen($filename, 'r');

		$file = fread($fp, filesize($filename));

		$queries = array();
		PMA_splitSqlFile($queries,$file);

		foreach($queries as $query){
			if(substr($query, 0,4) == 'DROP')
				continue;
			$this->query($query);
			$error = $this->error();
			if($error['code']){
				print_r($error);
				flush();
			}
		}
		$this->debugoutput("End $filename<br>\n", $status);

		if (!($dir = @opendir($backupdbdir))){
			echo "Could not open $backupdbdir for reading";
			return;
		}

		$totalsize=0;
		while($file = readdir($dir)) {
			if($file[0] == "." || is_dir("$backupdbdir/$file") || substr($file,-5) != '.dump')
				continue;

			$name = substr($file,0,-5);

			if(count($tables) && !in_array($name, $tables))
				continue;

			$this->debugoutput("Starting table $name ... ", $status);

			$time1 = time();

			$this->query("LOAD DATA INFILE '$backupdbdir/$file' INTO TABLE `$name` FIELDS TERMINATED BY ',' ENCLOSED BY '\"' ESCAPED BY '\\\\' LINES TERMINATED BY '\\n'");
			$error = $this->error();
			if($error['code'])
				print_r($error);

			$time2 = time();
			$this->debugoutput("Finished in " . ($time2 - $time1) . " secs<br>\n", $status);
		}
	}

	function analyze($status = 1){
		set_time_limit(0);

		$this->debugoutput("Starting analysis<br>\n", $status);

		$tables = array();

		$tableresult = $this->listtables();
		while (list($name) = $this->fetchrow($tableresult,DB_NUM))
			$tables[] = $name;

		$time1 = time();
		foreach($tables as $name){
			$this->debugoutput("Starting table $name ... ", $status);
			$this->query("ANALYZE TABLE `$name`");
			$time2 = time();
			$this->debugoutput("Finished in " . ($time2 - $time1) . "secs<br>\n", $status);
			$time1 = $time2;
			sleep(1); //let it catch up before going to the next
		}
	}

	function optimize($status = 1){
		set_time_limit(0);

		$this->debugoutput("Starting optimization<br>\n", $status);

		$tables = array();

		$tableresult = $this->listtables();
		while (list($name) = $this->fetchrow($tableresult,DB_NUM))
			$tables[] = $name;

		$time1 = time();
		foreach($tables as $name){
			$this->debugoutput("Starting table $name ... ", $status);
			$this->query("OPTIMIZE TABLE `$name`");
			$time2 = time();
			$this->debugoutput("Finished in " . ($time2 - $time1) . "secs<br>\n", $status);
			$time1 = $time2;
			sleep(1); //let it catch up before going to the next
		}
	}

	function check($status = 1){
		set_time_limit(0);

		$this->debugoutput("Starting Check<br>\n", $status);

		$tableresult = $this->listtables();

		while(list($name) = $this->fetchrow($tableresult,DB_NUM)) {

			$this->debugoutput("Starting table $name ... ", $status);

			$time1 = time();

			$result = $this->query("CHECK TABLE `$name` MEDIUM");
			$check = $this->fetchrow($result);

			if($check['Msg_text']!='OK' && $check['Msg_text']!='Table is already up to date'){
				$this->debugoutput("check msg: $check[Msg_type] : $check[Msg_text], repairing ... ", $status);

				$result = $this->query("REPAIR TABLE `$name`");
				$repair = $this->fetchrow($result);

				if($repair['Msg_text']!='OK')
					die("Couldn't repair table $name: $repair[Msg_text]\n<br>\n");
			}

			$time2 = time();

			$this->debugoutput("Finished in " . ($time2 - $time1) . " secs<br>\n", $status);
			sleep(1); //let it catch up before going to the next
		}
	}

	function getnumqueries(){
		return $this->num_queries;
	}

	function getquerytime(){
		return $this->time;
	}
} //end sql_engine class

class db_result {
	public $db;
	public $result;
	public $totalrows;

	function __construct( & $db, & $result, $totalrows){
		$this->db = & $db;
		$this->result = & $result;
		$this->totalrows = $totalrows;
	}

	function __destruct()
	{
		if (is_resource($this->result))
			mysql_free_result($this->result);
	}

	function getresult(){
		return $this->result;
	}

/*	function numrows(){
		return mysql_num_rows($this->result);
	}*/

	function affectedrows(){
		return ($this->db->db_connect_id ? mysql_affected_rows($this->db->db_connect_id) : false);
	}

	function totalrows()
	{
		if ($this->totalrows !== false)
			return $this->totalrows;
		else
			return mysql_num_rows($this->result);
	}

	function numfields(){
		return mysql_num_fields($this->result);
	}

	function fieldname($offset){
		return mysql_field_name($this->result, $offset);
	}

	function fieldtype($offset){
		return mysql_field_type($this->result, $offset);
	}

	function fetchrow($type = MYSQL_ASSOC){
		switch($type){
			case MYSQL_ASSOC:	return mysql_fetch_assoc($this->result);	break;
			case MYSQL_NUM:		return mysql_fetch_row($this->result);		break;
			case MYSQL_BOTH:	return mysql_fetch_array($this->result);	break;
		}
		return false;
	}

	function fetchrowset($type = MYSQL_ASSOC){
		$result = array();
		while($line = mysql_fetch_array($this->result, $type))
			$result[] = $line;

		return $result;
	}

	function fetchkeyedrowset($key)
	{
		$result = array();
		while ($line = $this->fetchrow())
			$result[$line[$key]] = $line;

		return $result;
	}

	function fetchfield($field = 0, $rownum = -1){
		if($rownum == -1)
			$rownum = 0;

 		return mysql_result($this->result, $rownum, $field);
	}

	function fetchfields($keycol)
	{
		$result = array();
		while ($line = $this->fetchrow())
		{
			$key = $line[$keycol];
			unset($line[$keycol]);
			$result[$key] = array_pop($line);
		}

		return $result;
	}

	function rowseek($rownum){
		return mysql_data_seek($this->result, $rownum);
	}

	function insertid($name = ''){
		return ($this->db->db_connect_id ? mysql_insert_id($this->db->db_connect_id) : false);
	}

	function freeresult(){
		//mysql_free_result($this->result);

		return true;
	}
}

