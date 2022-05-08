<?

define('DB_NUM',   PDO::FETCH_NUM);
define('DB_ASSOC', PDO::FETCH_ASSOC);
define('DB_BOTH',  PDO::FETCH_BOTH);

class sql_db extends sql_base { //must be saved by reference

	public $engine;
	public $db_connect_id;
	public $query_result;
	public $num_queries = 0;
	public $in_transaction = 0;
	public $time = 0;
	public $queries = array();
	public $lastquerytime = 0;
	public $found_rows = 0;

	public $connectiontime = 0;
	public $connectioncreationtime = 0;

	public $server;
	public $user;
	public $password;
	public $dbname;

	public $slowtime = 10000; //in 1/10000 seconds
	public $debug = 0;
	public $transactions;
	public $seqtable = false;

	public $needkey;

/*
	function sql_db($sqlserver, $sqluser, $sqlpassword, $database, $persistency = false)
	function connect()
	function close()
	function query($query)
	function unbuffered_query($query)
	function begin()
	function commit()
	function rollback()
	function prepare($query, ....)
	function prepare_query($query, ....)
	function prepare_array($query, $args)
	function prepare_array_query($query, $args)
	function repeatquery($query,$limit=1000)
	function numrows($query_id = 0)
	function affectedrows()
	function numfields($query_id = 0)
	function fieldname($offset, $query_id = 0)
	function fieldtype($offset, $query_id = 0)
	function fetchrow($query_id = 0, $type = MYSQL_ASSOC)
	function fetchrowset($query_id = 0, $type = MYSQL_ASSOC)
	function fetchfield($field=0, $rownum = -1, $query_id = 0)
	function rowseek($rownum, $query_id = 0)
	function insertid()
	function freeresult($query_id = 0)
	function nextAuto($table)
	function error()
	function escape($str)
	function explain($query)
	function outputQueries($name)
	function format_values_backup(&$value, $key)
	function backup($basedir,$status=1)
	function analyze($status = 1)
	function optimize($status = 1)
	function check($status = 1)
	function restore($backupdbdir, $status = 1, $tables = array())
	function debugoutput($text, $verbocity)
	function setslowtime($time)
*/

/*
	function sql_db($sqlserver, $sqluser, $sqlpassword, $database, $persistency = false){
		$this->server = $sqlserver;
		$this->user = $sqluser;
		$this->password = $sqlpassword;
		$this->dbname = $database;
		$this->persistency = $persistency;
	}
/*/
	function __construct($options){
		$this->engine		= (isset($options['engine'])		? $options['engine'] : 'mysql');
		$this->server 		= $options['host'];
		$this->user 		= $options['login'];
		$this->password 	= $options['passwd'];
		$this->dbname 		= $options['db'];

		$this->persistency 	= (isset($options['persistency']) 	? $options['persistency'] : false);

		$this->debug 		= (isset($options['debug']) 		? $options['debug'] : 1);
		$this->slowtime 	= (isset($options['slowtime']) 		? $options['slowtime'] : 10000);
		$this->needkey		= (isset($options['needkey']) 		? $options['needkey'] : DB_KEY_OPTIONAL);
		$this->transactions	= (isset($options['transactions'])	? $options['transactions'] : false);
		$this->seqtable     = (isset($options['seqtable'])	    ? $options['seqtable'] : false);

		$this->in_transaction = false;

		parent::__construct($options);
	}
//*/
	function connect(){
		if($this->db_connect_id){
//			if(time() - $this->lastquerytime > 10)
//				return mysql_ping($this->db_connect_id); TODO: NO EQUIV?
			return true;
		}

		try {
			$dsn = "";
			if ($this->engine == 'mysql')
				$dsn = "mysql:host={$this->server};dbname={$this->dbname}";
			else if ($this->engine == 'pgsql')
				$dsn = "pgsql:host={$this->server} dbname={$this->dbname}";

			$this->db_connect_id = new PDO($dsn, $this->user, $this->password, array(PDO::ATTR_PERSISTENT => $this->persistency));

			if ($this->persistency)
				register_shutdown_function(array(&$this, "close"));

		} catch (PDOException $dberr) {
			$msg = "Failed to Connect to Database: " . $this->dbname . ". ";

			global $userData, $debuginfousers;

			if($userData['loggedIn'] && in_array($userData['userid'], $debuginfousers)){
				$error = $this->error();
				$msg .= "Error Code " . $dberr->getCode() . ": " . $dberr->getMessage();
			}

			echo $msg;

			trigger_error($msg, E_USER_NOTICE);

			exit;
//			return false;
		}

		if($this->debug)
			$this->connectioncreationtime = gettime();
	}

	function close(){
		if(!$this->db_connect_id)
			return false;

		if($this->in_transaction)
			$this->commit();

		$this->db_connect_id = null;

		if($this->debug){
			$this->connectiontime += gettime() - $this->connectioncreationtime;
			$this->connectioncreationtime = 0;
		}

		return $result;
	}

	function normalize_insert($query)
	{
		$qmatch = array();
		if (preg_match('/^(INSERT [^ ]* ?INTO [^ ]+ )SET /', $query, $qmatch))
		{
			$matches = array();
			preg_match_all('/(([^ ]+) *= *([^, ]+),? *)/', $query, $matches);

			$out = "(";
			foreach ($matches[2] as $col)
			{
			 $out .= "$col, ";
			}
			$out = substr($out, 0, -2);
			$out .= ") VALUES (";
			foreach ($matches[3] as $val)
			{
			 $out .= "$val, ";
			}
			$out = substr($out, 0, -2);
			$out .= ")";

			return $qmatch[1] . $out;
		}
		return $query;
	}

	function massage_query($query)
	{
		if ($this->engine == 'pgsql')
		{
			$query = str_replace('`', '"', $query);
			$query = str_replace('&&', 'AND', $query);
			$query = str_replace('||', 'OR', $query);
			$query = $this->normalize_insert($query);
			$query = preg_replace('/USE KEY\([^\)]+\)/', '', $query);
			$query = preg_replace('/ISNULL\(([^\)]+)\)/', '\1 IS NULL', $query);
			// The following is better done with a function of the following form:
			/*
				CREATE OR REPLACE FUNCTION IF(boolean, integer, numeric)
				RETURNS numeric AS
				'
				  SELECT CASE WHEN $1 THEN $2 ELSE $3 END;
				'
				LANGUAGE 'sql';
			*/
			//$query = preg_replace('/IF\(([^,]+),([^,]+),([^)]+)\)/', 'CASE WHEN \1 THEN \2 ELSE \3 END', $query);
		}
		return $query;
	}

	// rewrites the query in place if it's calc_found_rows, and returns an
	// array($offset, $limit) if it is one. Otherwise, returns false.
	// The action needs to be wrapped in a transaction until rows are fetched.
	function pgsql_rewrite_calc_rows_found(&$query)
	{
		$matches = array();
		if ($this->engine == 'pgsql' &&
		    preg_match('/^SELECT SQL_CALC_FOUND_ROWS (.*) LIMIT ([0-9]+) OFFSET ([0-9]+)$/', $query, $matches))
		{
			$query = "DECLARE calc_found_rows CURSOR FOR SELECT $matches[1]";
			return array($matches[3], $matches[2]);
		}
		return false;
	}

	// returns true if an error on this query should be ignored
	function pgsql_rewrite_insert_ignore(&$query)
	{
		if ($this->engine == 'pgsql')
		{
			$newquery = preg_replace('/^INSERT IGNORE /', 'INSERT ', $query);
			if ($newquery != $query)
			{
				$query = $newquery;
				return true;
			}
		}
		return false;
	}

	function query(){ //$keys = false, $query, $buffered = true, $reconnect = true
		$arg_list = func_get_args();

		$this->parseargs($arg_list);

		$query = $arg_list[0];
		$buffered = isset($arg_list[1]) ? $arg_list[1] : true;
		$reconnect = isset($arg_list[2]) ? $arg_list[2] : true;
		$isselect = strtoupper(substr($query, 0, 6)) == "SELECT";

		$this->connect();

		$query = preg_replace('/LIMIT ([0-9]+), *([0-9]+)$/', 'LIMIT \2 OFFSET \1', $query);
		$range = $this->pgsql_rewrite_calc_rows_found($query);
		$ignore = $this->pgsql_rewrite_insert_ignore($query);

		if ($this->engine == 'pgsql' && $query == 'SELECT FOUND_ROWS()')
			$query = "SELECT " . ($this->found_rows? $this->found_rows : '0') . ' -- found_rows()';

		if($this->debug){
			$starttime = gettime();

			$explain = array();
			if($this->debug >= 2 && $isselect){
				$explainstr = "EXPLAIN ";
				if ($range)
					$explainstr .= substr($query, strlen("DECLARE calc_found_rows CURSOR FOR "));
				else
					$explainstr .= $query;

				$explaininfo = $this->db_connect_id->query($explainstr);
				if ($explaininfo)
				{
					$explaininfo->setFetchMode(DB_ASSOC);
					foreach ($explaininfo as $line)
					{
						$explain[] = $line;
						if(isset($line['table']) && $line['type'] == 'ALL')
							trigger_error("SQL warning: No Index used on table $line[table]. Query: $query", E_USER_NOTICE);
					}
				}
			}

			$this->lastquery = $query;
		}
		$this->clearresult();

		if ($range && !$this->in_transaction)
			$this->db_connect_id->beginTransaction();

		$query_result = $this->db_connect_id->query($query);

		if($this->debug){
			$endtime = gettime();

			$querytime = $endtime - $starttime;

			$this->queries[$this->num_queries] = array('time' => $querytime, 'query' => $query, 'explain' => $explain, 'result' => $this->query_result);

			if($querytime > $this->slowtime)
				trigger_error("SQL warning: took " . number_format($querytime/10000,4) . " secs. Query: $query", E_USER_NOTICE);

			$this->time += $querytime;

			$this->lastquerytime = floor($endtime / 10000); // save a time() call
		}else
			$this->lastquerytime = time();

		$this->num_queries++;

		if($query_result){

			if (!$buffered)
				return $this->setresult(new pdo_result($this, $query_result));
			else if ($range)
			{
				$result = new pdo_pgsql_calc_found_rows($this, $query_result, $range);
				$this->found_rows = $result->totalrows;
				return $this->setresult($result);
			} else
				return $this->setresult(new pdo_buffered_result($this, $query_result));

		}else{
			if ($ignore)
			{
				return $this->setresult(new pdo_empty_result());
			}

			$error = $this->error();
			if(($error['code'] == 2013 || $error['code'] == 2006) && $reconnect){ //disconnect
				trigger_error("SQL error: $error[code], $error[message]. Query: $query", E_USER_WARNING);
				$this->close();

				$result = $this->query($query, $buffered, false);
				return $result; //redo query, don't do a second time.
			}else
				trigger_error("SQL error: $error[code], $error[message]. Query: $query", E_USER_ERROR);

			return false;
		}
	}

	function getSeqID($id, $area){ //eg: $userid, messages
		$this->connect();
		if(!$this->seqtable)
			trigger_error("Call getSeqID ($id, $area) without a seqtable defined for this db", E_USER_ERROR);

		$inid = false;
		if ($this->engine == 'mysql')
		{
			$this->prepare_query("UPDATE " . $this->seqtable . " SET max = LAST_INSERT_ID(max+1) WHERE id = # && area = #", $id, $area);

			$inid = $this->insertid();
		} else {
			if (!($in_transaction = $this->in_transaction))
				$this->db_connect_id->beginTransaction();

			$result = $this->prepare_query("SELECT max FROM " . $this->seqtable . " WHERE id = # AND area = # FOR UPDATE", $id, $area);
			if ($row = $result->fetchrow())
			{
				$inid = $row['max'] + 1;
				$this->prepare_query("UPDATE " . $this->seqtable . " SET max = max+1 WHERE id = # AND area = #", $id, $area);
			}
			if (!$in_transaction)
				$this->db_connect_id->commit();
		}

		if($inid)
			return $inid;

		$this->prepare_query("INSERT IGNORE INTO " . $this->seqtable . " SET max = 1, id = #, area = #", $id, $area);

		if($this->affectedrows())
			return 1;
		else
			return $this->getSeqID($id, $area);
	}

	function begin(){
		if($this->transactions){
			$this->connect();
			$this->in_transaction = true;
			return $this->db_connect_id->beginTransaction();
		}
		return false;
	}

	function commit(){
		if($this->transactions && $this->db_connect_id){ //don't commit if no connection exists
			$this->in_transaction = false;
			return $this->db_connect_id->commit();
		}
		return false;
	}

	function rollback(){
		if($this->transactions && $this->db_connect_id){ //don't rollback if no connection exists
			$this->in_transaction = false;
			return $this->db_connect_id->rollBack();
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

	function error(){
		$errorInfo = $this->db_connect_id->errorInfo();
		$result['message'] = $errorInfo[2];
		$result['code'] = "$errorInfo[0]+$errorInfo[1]";

		return $result;
	}

	function escape($str){
		$this->connect();
		return substr($this->db_connect_id->quote($str), 1, -1); // pdo::quote also adds quotes, which we don't want (though maybe we should).
	}

	function explain($query){
		$str = "";

		$result = $this->query("EXPLAIN $query");

		$str .= "<table border=1 cellspacing=0 cellpadding=2 bgcolor=#FFFFFF>";

		$first = true;
		while($line = $this->fetchrow($result)){
			if($first){
				$str .= "<tr>";
				foreach($line as $name => $item)
					$str .= "<td>$name</td>";
				$str .= "</tr>\n";
			}

			$str .= "<tr>";
			foreach($line as $item)
				$str .= "<td>$item</td>";
			$str .= "</tr>\n";

			$first = false;
		}
		$str .= "</table>";
		return $str;
	}

/*
	function outputQueries($name){
		echo "<!- $name Database host: " . $this->server . " >\n";
		echo "<!- Queries: " . $this->num_queries . " >";
		echo "<!- Total Query time: " .number_format($this->time/10,3) . " milliseconds >";

		echo "\n<!-- Query list: \n";
		foreach($this->queries as $row){
			echo str_pad(number_format($row['time']/10,3),10,' ',STR_PAD_LEFT) . " milliseconds\t" . $row['query'] . "\n";
			if(isset($row['explain'])){
				$lengths = $row['explain'][0];
				foreach($lengths as $name => $val){
					$length = strlen($name)+1;
					foreach($row['explain'] as $explain)
						if(strlen($explain[$name]) > $length)
							$length = strlen($explain[$name]);
					$lengths[$name] = $length;
				}

				echo str_repeat(" ", 15);
				foreach($lengths as $name => $length)
					echo str_pad($name, $length+2,' ', STR_PAD_RIGHT);
				echo "\n";
				foreach($row['explain'] as $explain){
					echo str_repeat(" ", 15);
					foreach($explain as $name => $val){
						if($val == NULL)
							$val = "NULL";
						echo str_pad($val, $lengths[$name]+2,' ', STR_PAD_RIGHT);
					}
					echo "\n";
				}
			}
		}
		echo "-->\n";

	}
/*/

	function outputQueries($name){
		echo "<table border=0 cellspacing=1 cellpadding=2>";
		echo "<tr><td class=header nowrap colspan=2><b>$name</b> - $this->server - $this->num_queries " . ($this->num_queries == 1 ? "Query" : "Queries" ) . "</td></tr>";

		if($this->debug && count($this->queries)){
			foreach($this->queries as $row){
				$class='body2';
				if($row['time'] > $this->slowtime)
					$class='header';
				echo "<tr><td align=right nowrap class=$class>" . number_format($row['time']/10, 2) . " ms</td><td class=$class>" . htmlentities($row['query']) . "</td></tr>";
				if(count($row['explain'])){
					$lengths = $row['explain'][0];
					foreach($lengths as $name => $val){
						$length = strlen($name)+1;
						foreach($row['explain'] as $explain)
							if(strlen($explain[$name]) > $length)
								$length = strlen($explain[$name]);
						$lengths[$name] = $length;
					}

					echo "<tr><td class=body></td><td class=body>";
					echo "<table border=0 cellspacing=1 cellpadding=2>";
					echo "<tr>";
					foreach($lengths as $name => $length)
						echo "<td class=header>$name</td>";
					echo "</tr>";
					foreach($row['explain'] as $explain){
						echo "<tr>";
						foreach($explain as $name => $val){
							if($val == NULL)
								$val = "NULL";
							echo "<td class=body>$val</td>";
						}
						echo "</tr>";
					}
					echo "</table>";
					echo "</td></tr>";
				}
			}
			echo "<tr><td class=header nowrap align=right>" .number_format($this->time/10, 2) . " ms</td><td class=header>Total Query time</td></tr>";
			echo "<tr><td class=header nowrap align=right>" .number_format(($this->connectiontime + ($this->connectioncreationtime ? gettime() - $this->connectioncreationtime : 0 ) )/10, 2) . " ms</td><td class=header>Total Connection time</td></tr>";
		}
		echo "</table>";
//		echo "<br>\n";
	}
//*/

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

	function debugoutput($text, $verbocity){
		if($verbocity){
			echo $text;
			if($verbocity >= 2)
				zipflush();
		}
	}

	function getnumqueries(){
		return $this->num_queries;
	}

} //end sql_db class

function bool_to_enum($in)
{
	if (is_array($in))
	{
		$out = array();
		foreach ($in as $key => $item)
		{
			$out[$key] = bool_to_enum($item);
		}
		return $out;
	} else if (is_bool($in)) {
		return ($in? 'y' : 'n');
	} else {
		return $in;
	}
}

class pdo_result {
	public $db;
	public $result;

	function __construct( & $db, & $result){
		$this->db = & $db;
		$this->result = & $result;
	}

	function __destruct()
	{
		$this->freeresult();
	}

	function getresult(){
		return $this->result;
	}

/*	function numrows(){
		// this is not necessarily portable.
		return $this->rowCount();
	}*/

	function affectedrows(){
		return $this->result->rowCount();
	}

	function numfields(){
		return $this->result->columnCount();
	}

	function fieldname($offset){
		$meta = $this->result->getColumnMeta($offset);
		return $meta['name'];
	}

	function fieldtype($offset){
		$meta = $this->result->getColumnMeta($offset);
		return $meta['decl_type'];
	}

	function fetchrow($type = DB_ASSOC){
		return bool_to_enum($this->result->fetch($type));
	}


	function fetchrowset($type = DB_ASSOC){
		return bool_to_enum($this->result->fetchAll($type));
	}


	function fetchfield($field = 0, $rownum = -1){
		if($rownum > 0)
			die("TODO: Support rownum other than -1 or 0");

 		return bool_to_enum($this->result->fetchColumn($field));
	}

	function rowseek($rownum){
		die("TODO: Support seeking rows");
	}

	function insertid($name){
		return $this->db->db_connect_id->lastInsertId($name);
	}

	function freeresult(){
		$this->result->closeCursor();

		return true;
	}
}

class pdo_buffered_result {
	public $db;
	public $result;
	public $result_arr;

	function __construct( & $db, & $result){
		$this->db = & $db;
		$this->result = & $result;
		$this->result_arr = $result->fetchAll(DB_ASSOC);
	}

	function __destruct()
	{
		$this->freeresult();
	}

	function getresult(){
		return $this->result;
	}

/*	function numrows(){
		// this is not necessarily portable.
		return count($this->result_arr);
	}*/

	function affectedrows(){
		return $this->result->rowCount();
	}

	function numfields(){
		return $this->result->columnCount();
	}

	function fieldname($offset){
		$meta = $this->result->getColumnMeta($offset);
		return $meta['name'];
	}

	function fieldtype($offset){
		$meta = $this->result->getColumnMeta($offset);
		return $meta['decl_type'];
	}

	function fetchrow($type = DB_ASSOC){
		$row = each($this->result_arr);
		if ($type == DB_NUM)
			return bool_to_enum(array_values($row['value']));
		else if ($type == DB_BOTH)
			return bool_to_enum(array_merge($row['value'], array_values($row['value'])));
		else
			return bool_to_enum($row['value']);
	}


	function fetchrowset($type = DB_ASSOC){
		if ($type == DB_ASSOC)
			return bool_to_enum($this->result_arr); // this one's easy

		$arr = array();
		while ($row = $this->fetchrow($type))
			$arr[] = $row;

		return $arr;
	}


	function fetchfield($field = 0, $rownum = -1){
		$row = array_values($rownum > -1? $this->result_arr[$rownum] : current($this->result_arr));
		if ($field < count($row))
			return bool_to_enum($row[$field]);
		else
			return false;
	}

	function rowseek($rownum){
		die("TODO: Support seeking rows");
	}

	function insertid($name){
		return $this->db->db_connect_id->lastInsertId($name);
	}

	function freeresult(){
		$this->result_arr = array();
		$this->result->closeCursor();

		return true;
	}
}

class pdo_pgsql_calc_found_rows extends pdo_buffered_result
{
	public $totalrows;
	function __construct($db, $result, $range)
	{
		$db_con = $db->db_connect_id;
		$moved = 0;
		if ($range[0])
		{
			$offset_result = $db_con->query("MOVE $range[0] IN calc_found_rows");
			$moved = $offset_result->rowCount();
			$offset_result->closeCursor();
		}
		if ($moved == $range[0] && $range[1])
		{
			$range_result = $db_con->query("FETCH $range[1] IN calc_found_rows");
			parent::__construct($db, $range_result);
		} else {
			parent::__construct($db, $result); // empty
			$this->totalrows = $moved;
			$db_con->exec("ROLLBACK");
			return;
		}
		$end = 0;
		if (count($this->result_arr) == $range[1])
		{
			$end_result = $db_con->query("MOVE ALL IN calc_found_rows");
			$end = $end_result->rowCount();
			$end_result->closeCursor();
		}
		$this->totalrows = $moved + count($this->result_arr) + $end;
		if (!$db->in_transaction)
			$db_con->rollBack();
	}
}


class pdo_empty_result {
	function getresult(){
		return null;
	}

/*	function numrows(){
		// this is not necessarily portable.
		return 0;
	}*/

	function affectedrows(){
		return 0;
	}

	function numfields(){
		return 0;
	}

	function fieldname($offset){
		return false;
	}

	function fieldtype($offset){
		return false;
	}

	function fetchrow($type = DB_ASSOC){
		return false;
	}

	function fetchrowset($type = DB_ASSOC){
		return array();
	}
	function fetchkeyedrowset($key){
		return array();
	}
	function fetchfield($field = 0, $rownum = -1){
		return false;
	}

	function fetchfields($keycol){
		return array();
	}

	function rowseek($rownum){
		return false;
	}

	function insertid($name){
		return false;
	}

	function freeresult(){
		return true;
	}
}
