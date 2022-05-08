<?

define('DB_NUM',   MYSQL_NUM);
define('DB_ASSOC', MYSQL_ASSOC);
define('DB_BOTH',  MYSQL_BOTH);

class sql_db{ //must be saved by reference

	public $db_connect_id;
	public $query_result;
	public $num_queries = 0;
	public $in_transaction = 0;
	public $time = 0;
	public $queries = array();
	public $lastquerytime = 0;

	public $connectiontime = 0;
	public $connectioncreationtime = 0;

	public $server;
	public $user;
	public $password;
	public $dbname;

	public $slowtime = 10000; //in 1/10000 seconds
	public $debug = 0;

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
		$this->server 		= $options['host'];
		$this->user 		= $options['login'];
		$this->password 	= $options['passwd'];
		$this->dbname 		= $options['db'];

		$this->persistency 	= (isset($options['persistency']) 	? $options['persistency'] : false);

		$this->debug 		= (isset($options['debug']) 		? $options['debug'] : 1);
		$this->slowtime 	= (isset($options['slowtime']) 		? $options['slowtime'] : 10000);
		$this->needkey		= (isset($options['needkey']) 		? $options['needkey'] : DB_KEY_OPTIONAL);
	}
//*/
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

			$msg = "Failed to Connect to Database: " . $this->dbname . ". ";

			global $userData, $debuginfousers;

			if($userData['loggedIn'] && in_array($userData['userid'], $debuginfousers)){
				$error = $this->error();
				$msg .= "Error Code $error[code]: $error[message]";
			}

			echo $msg;

			trigger_error($msg, E_USER_NOTICE);

			exit;
//			return false;
		}

		if($this->debug)
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
			mysql_query("COMMIT", $this->db_connect_id);

		$result = mysql_close($this->db_connect_id);

		if($result)
			$this->db_connect_id = false;

		if($this->debug){
			$this->connectiontime += gettime() - $this->connectioncreationtime;
			$this->connectioncreationtime = 0;
		}

		return $result;
	}


	function parseargs(& $arg_list){ //takes the $key off the arg_list, if it exists. May throw errors if a key is expected and missing or found and unexpected

		if(is_null($arg_list[0])){ //this is an internal call, don't error check
			array_shift($arg_list);
			return;
		}

		if(is_numeric($arg_list[0]) || is_array($arg_list[0]) || is_bool($arg_list[0])){ //found
			array_shift($arg_list); //strip it, as this layer can't use it

			switch($this->needkey){
				case DB_KEY_OPTIONAL:
				case DB_KEY_REQUIRED:
					return;
				case DB_KEY_FORBIDDEN:
					trigger_error("Key supplied when not allowed for query: " . $arg_list[0], E_USER_ERROR);
			}
		}else{
			switch($this->needkey){
				case DB_KEY_OPTIONAL:
				case DB_KEY_FORBIDDEN:
					return;
				case DB_KEY_REQUIRED:
					trigger_error("Key not supplied when required for query: " . $arg_list[0], E_USER_ERROR);
			}
		}
	}


	function query(){ //$keys = false, $query, $buffered = true, $reconnect = true
		$arg_list = func_get_args();

		$this->parseargs($arg_list);

		$query = $arg_list[0];
		$buffered = isset($arg_list[1]) ? $arg_list[1] : true;
		$reconnect = isset($arg_list[2]) ? $arg_list[2] : true;

		$this->connect();

		unset($this->query_result);

		if($this->debug){
			$starttime = gettime();

			$explain = array();
			if($this->debug >= 2 && strtoupper(substr($query, 0, 6)) == "SELECT"){
				$explainresult = mysql_query("EXPLAIN $query", $this->db_connect_id);
				while($line = mysql_fetch_assoc($explainresult)){
					$explain[] = $line;
					if(isset($line['table']) && $line['type'] == 'ALL')
						trigger_error("SQL warning: No Index used on table $line[table]. Query: $query", E_USER_NOTICE);
				}
			}

			$this->lastquery = $query;
		}

		if($buffered)
			$this->query_result = mysql_query($query, $this->db_connect_id);
		else
			$this->query_result = mysql_unbuffered_query($query, $this->db_connect_id);

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

		if($this->query_result){
			return $this->query_result;
		}else{
			$error = $this->error();
			if(($error['code'] == 2013 || $error['code'] == 2006) && $reconnect){ //disconnect
				trigger_error("SQL error: $error[code], $error[message]. Query: $query", E_USER_WARNING);
				$this->close();
				return $this->query(null, $query, $buffered, false); //redo query, don't do a second time.
			}else
				trigger_error("SQL error: $error[code], $error[message]. Query: $query", E_USER_ERROR);

			return false;
		}
	}

	function unbuffered_query(){ //$keys = false, $query
		$arg_list = func_get_args();

		$this->parseargs($arg_list);

		$query = $arg_list[0];

		return $this->query(null, $query, false);
	}

	function begin(){
		$this->in_transaction = true;
	}

	function commit(){
		$this->in_transaction = false;
	}

	function rollback(){
		$this->in_transaction = false;
	}

	function prepare_query(){ //$keys = false, $query, $args...
		$arg_list = func_get_args();

		$this->parseargs($arg_list);

		$query = $arg_list[0];

		$query = call_user_func_array(array(&$this, 'prepare'), $arg_list);

		return $this->query(null, $query);
	}

	function prepare_array_query(){ //$keys=false, $query, $args
		$arg_list = func_get_args();

		$this->parseargs($arg_list);

		$query = $arg_list[0];
		$args = $arg_list[1];

		array_unshift($args, $query);

		$query = call_user_func_array(array(&$this, 'prepare'), $args);

		return $this->query(null, $query);
	}

	function prepare_array($query, $args){

		$arg_list = array_merge( array($query), $args);

		return call_user_func_array(array(&$this, 'prepare'), $arg_list);
	}

//*
	function prepare(){
		$numargs = func_num_args();
		$arg_list = func_get_args();
		if($numargs == 0){
			trigger_error("SQL error: prepare_query() called with 0 arguments.", E_USER_ERROR);
			return "";
		}
		if($numargs == 1)
			return $arg_list[0];

		$query = $arg_list[0];

		$parts = array();
		$tokens = array();
		$i = 0;
		do{
			if($query{$i} == '?' || $query{$i} == '#'){
				$parts[] = substr($query, 0, $i);
				$tokens[] = $query{$i};
				$query = substr($query, ($i+1));
				$i = 0;
			}else
				$i++;
		}while($i < strlen($query));
		$parts[] = $query;

		if(count($parts) != $numargs){
			trigger_error("SQL error: prepare() placeholder and argument count mismatch in " . $arg_list[0] . ". $arg_list[1]", E_USER_ERROR);
			return false;
		}

		$query = $parts[0];
		for($i = 1; $i < $numargs; $i++) {
			$var = $arg_list[$i];
		//arrays
			if(is_array($var)){

			//strings
				if($tokens[$i-1] == '?'){
					foreach($var as $k => $v){
						if(is_array($v))
							trigger_error("SQL error: Trying to escape: " . var_export($v), E_USER_ERROR);
						else
							$var[$k] = $this->escape($v);
					}
					$query .= "'" . implode("','", $var). "'";
			//int and null
				}else{ // == '#'
					foreach($var as $k => $v){
						if(is_array($v))
							trigger_error("SQL error: Trying to escape: " . var_export($v), E_USER_ERROR);
						elseif(is_null($v))
							$var[$k] = "NULL";
						else
							$var[$k] = intval($v);
					}
					$query .= implode(",", $var);
				}

		//scalars
			}else{
				if($tokens[$i-1] == '?'){
					$query .= "'" . $this->escape($var) . "'";
				}else{
					if(is_null($var))
						$query .= "NULL";
					else
						$query .= intval($var);
				}
			}
			$query .= $parts[$i];
		}
		return $query;
	}

/*/
	function prepare(){
		$numargs = func_num_args();
		$arg_list = func_get_args();
		if($numargs == 0){
			trigger_error("SQL error: prepare_query() called with 0 arguments.", E_USER_ERROR);
			return "";
		}
		if($numargs == 1)
			return $arg_list[0];

		$qp = explode("?", $arg_list[0]);
		if(count($qp) != $numargs){
			trigger_error("SQL error: prepare() placeholder and argument count mismatch in ".$arg_list[0].".", E_USER_ERROR);
			return "INVALID QUERY: " . $arg_list[0];
		}

		$query = $qp[0];
		for($x=1;$x<$numargs;$x++) {
			if(isset($arg_list[$x])){
				if(is_array($arg_list[$x])){
					foreach($arg_list[$x] as $n => $v){
						if(is_array($v))
							trigger_error("SQL error: Trying to escape: " . var_export($v), E_USER_ERROR);
						else
							$arg_list[$x][$n] = $this->escape($v);

					}
					$query.= "'" . implode("','", $arg_list[$x]). "'";
				}else{
					if(is_null($arg_list[$x]))
						$query.= "NULL";
					else
						$query.= "'" . $this->escape($arg_list[$x]) . "'";
				}
			}
			if(isset($qp[$x])) $query.= $qp[$x];

		}
		return $query;
	}
//*/

//useful for huge deletes or updates
	function repeatquery(){ //$keys = false, $query,$limit=1000){
		$arg_list = func_get_args();

		$this->parseargs($arg_list);

		$query = $arg_list[0];
		$limit = isset($arg_list[1]) ? $arg_list[1] : 1000;

		do{
			$this->query(null, "$query LIMIT $limit");
			usleep(100);
		}while($this->affectedrows() >= $limit);
	}

	function numrows($query_id = 0){
		if(!$query_id)
			$query_id = $this->query_result;

		return ($query_id ? mysql_num_rows($query_id) : false);
	}

	function affectedrows(){
		return ($this->db_connect_id ? mysql_affected_rows($this->db_connect_id) : false);
	}

	function numfields($query_id = 0){
		if(!$query_id)
			$query_id = $this->query_result;

		return ($query_id ? mysql_num_fields($query_id) : false);
	}

	function fieldname($offset, $query_id = 0){
		if(!$query_id)
			$query_id = $this->query_result;

		return ($query_id ? mysql_field_name($query_id, $offset) : false);
	}

	function fieldtype($offset, $query_id = 0){
		if(!$query_id)
			$query_id = $this->query_result;

		return ($query_id ? mysql_field_type($query_id, $offset) : false);
	}

	function fetchrow($query_id = 0, $type = MYSQL_ASSOC){
		if(!$query_id)
			$query_id = $this->query_result;

		if(!$query_id)
			return false;

		switch($type){
			case MYSQL_ASSOC:	return mysql_fetch_assoc($query_id);	break;
			case MYSQL_NUM:		return mysql_fetch_row($query_id);		break;
			case MYSQL_BOTH:	return mysql_fetch_array($query_id);	break;
		}
		return false;
	}


	function fetchrowset($query_id = 0, $type = MYSQL_ASSOC){
		if(!$query_id)
			$query_id = $this->query_result;

		if(!$query_id)
			return false;

		$result = array();
		while($line = mysql_fetch_array($query_id, $type))
			$result[] = $line;

		return $result;
	}


	function fetchfield($field = 0, $rownum = -1, $query_id = 0){
		if(!$query_id)
			$query_id = $this->query_result;

		if(!$query_id)
			return false;

		if($rownum == -1)
			$rownum = 0;

 		return mysql_result($query_id, $rownum, $field);
	}

	function rowseek($rownum, $query_id = 0){
		if(!$query_id)
			$query_id = $this->query_result;

		return ($query_id ? mysql_data_seek($query_id, $rownum) : false);
	}

	function insertid(){
		return ($this->db_connect_id ? mysql_insert_id($this->db_connect_id) : false);
	}

	function listtables(){
		$this->connect();

		if(!$this->db_connect_id)
			return false;

//		$this->query_result = mysql_list_tables($this->dbname,$this->db_connect_id);
		$this->query(null, "SHOW TABLE STATUS");

		return $this->query_result;
	}

	function freeresult($query_id = 0){
		if(!$query_id)
			$query_id = $this->query_result;

		if(!$query_id)
			return false;

		mysql_free_result($query_id);

		return true;
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
		$result['message'] = mysql_error($this->db_connect_id);
		$result['code'] = mysql_errno($this->db_connect_id);

		return $result;
	}

	function escape($str){
		return mysql_escape_string($str);
	}

	function explain($query){
		$str = "";

		$result = $this->query(null, "EXPLAIN $query");

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

		$this->query(null, "FLUSH TABLES WITH READ LOCK");

		$this->query(null, "SHOW SLAVE STATUS");
		$line = $this->fetchrow();

		if($line){
			fwrite($cp, "#Slave Status:\n\n");
			foreach($line as $k => $v)
				fwrite($cp, "# $k : $v\n");
			fwrite($cp, "\n\n");
		}

		$this->query(null, "SHOW TABLE STATUS");

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

			$result = $this->query(null, "SHOW CREATE TABLE `$name`");
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
			$result = $this->unbuffered_query(null, "SELECT * FROM `$name`");

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

		$this->query(null, "UNLOCK TABLES");

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
			$this->query(null, $query);
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

			$this->query(null, "LOAD DATA INFILE '$backupdbdir/$file' INTO TABLE `$name` FIELDS TERMINATED BY ',' ENCLOSED BY '\"' ESCAPED BY '\\\\' LINES TERMINATED BY '\\n'");
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
			$this->query(null, "ANALYZE TABLE `$name`");
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
			$this->query(null, "OPTIMIZE TABLE `$name`");
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

			$result = $this->query(null, "CHECK TABLE `$name` MEDIUM");
			$check = $this->fetchrow($result);

			if($check['Msg_text']!='OK' && $check['Msg_text']!='Table is already up to date'){
				$this->debugoutput("check msg: $check[Msg_type] : $check[Msg_text], repairing ... ", $status);

				$result = $this->query(null, "REPAIR TABLE `$name`");
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

	function setslowtime($time){
		$this->slowtime = $time;
	}

} //end sql_db class

/*
function sqlSafe(	$a0,
					$a1=NULL,	$a2=NULL,	$a3=NULL,	$a4=NULL,
					$a5=NULL,	$a6=NULL,	$a7=NULL,	$a8=NULL,
					$a9=NULL,	$a10=NULL,	$a11=NULL,	$a12=NULL,
					$a13=NULL,	$a14=NULL,	$a15=NULL,	$a16=NULL,
					$a17=NULL,	$a18=NULL,	$a19=NULL){
	global $db;
	if(func_num_args()>20)
		trigger_error("Too Many Args in sqlSafe",E_USER_ERROR);
	for($i=0;$i<func_num_args();$i++){
		$name="a" . $i;
		if(!isset($$name)) continue;
		if(is_array($$name)){
			foreach($$name as $n => $v){
				if(is_array($v))
					sqlSafe(&${$name}[$n]);
				elseif(isset($v))
					${$name}[$n] = $db->escape($v);
			}
		}else
			$$name = $db->escape($$name);
	}
}
*/

function getEnumValues( & $db, $table, $field ){
	$result = $db->query( "SHOW COLUMNS FROM $table");

	while( list( $name, $type ) = $db->fetchrow( $result , DB_NUM)) {
		if( $name == $field ) {
			if( ereg( '^enum\(.*\)$', $type ))
				$type = substr( $type, 6,-2 );
			elseif( ereg( '^set\(.*\)$', $type ))
				$type = substr( $type, 5, -2);
			else
				return( false );

			return( explode( "','", $type ) );
		}
	}
	return false;
}

