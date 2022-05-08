<?

define('DB_NUM', MYSQL_NUM);
define('DB_ASSOC', MYSQL_ASSOC);
define('DB_BOTH', MYSQL_BOTH);



class multiple_sql_db{

	var $selectdb;
	var $insertdb;
	var $query_result;
	var $num_queries = 0;
	var $in_transaction = 0;
	var $time = 0;

/*
	function multiple_sql_db($databases)
	function close()
	function prepare($query)
	function prepare_array($query)
	function prepare_array_query($query)
	function query($query)
	function unbuffered_query($query)
	function begin()
	function commit()
	function numrows($query_id = 0)
	function affectedrows()
	function numfields($query_id = 0)
	function fieldname($offset, $query_id = 0)
	function fieldtype($offset, $query_id = 0)
	function fetchrow($query_id = 0, $type = DB_ASSOC)
	function fetchrowset($query_id = 0, $type = DB_ASSOC)
	function fetchfield($field=0, $rownum = -1, $query_id = 0)
	function rowseek($rownum, $query_id = 0)
	function insertid()
	function freeresult($query_id = 0)
	function nextAuto($table)
	function error()
	function escape($str)
	function explain($query)
*/

	function multiple_sql_db($databases){
		$this->insertdb = & new sql_db(	$databases["insert"]["host"],
										$databases["insert"]["login"],
										$databases["insert"]["passwd"],
										$databases["insert"]["db"]
								);

		$ids = array();
		foreach($databases['select'] as $id => $db)
			for($i=0;$i<$db['weight'];$i++)
				$ids[] = $id;

		$id = $ids[rand(0,count($ids)-1)];

		if(	$databases["insert"]["host"] 	== $databases["select"][$id]["host"] &&
			$databases["insert"]["login"] 	== $databases["select"][$id]["login"] &&
			$databases["insert"]["passwd"]	== $databases["select"][$id]["passwd"] &&
			$databases["insert"]["db"]		== $databases["select"][$id]["db"] ){

			$this->selectdb = & $this->insertdb;
		}else{
			$this->selectdb = & new sql_db(	$databases["select"][$id]["host"],
											$databases["select"][$id]["login"],
											$databases["select"][$id]["passwd"],
											$databases["select"][$id]["db"]
									);
		}
	}

	function connect(){
		$this->selectdb->connect();
		$this->insertdb->connect();
	}

	function close(){
		$this->selectdb->close();
		$this->insertdb->close();
	}

	function query($query, $buffered = true){
		if(strtoupper(substr($query,0,5)) == "BEGIN" || strtoupper(substr($query,0,17)) == "START TRANSACTION"){
			$this->in_transaction = true;
			return;
		}

		if(strtoupper(substr($query,0,6)) == "COMMIT" || strtoupper(substr($query,0,8)) == "ROLLBACK"){
			$this->in_transaction = false;
			return;
		}

		if(strtoupper(substr($query,0,4)) == "LOCK")
			$this->in_transaction = true;

		if(strtoupper(substr($query,0,6)) == "UNLOCK")
			$this->in_transaction = false;

		if(strtoupper(substr($query,0,6)) == "SELECT" && $this->in_transaction == false)
			$this->query_result = array("con" => "s", "result" => $this->selectdb->query($query, $buffered));
		else
			$this->query_result = array("con" => "i", "result" => $this->insertdb->query($query, $buffered));


		return $this->query_result;
	}

	function unbuffered_query($query){
		return $this->query($query, false);
	}

	function begin(){
		$this->in_transaction = true;
	}

	function commit(){
		$this->in_transaction = false;
	}

	function prepare(){
		$arg_list = func_get_args();

		$query = call_user_func_array(array(&$this->insertdb, 'prepare'), $arg_list);

		return $query;
	}

	function prepare_array_query($query, $args){

		$arg_list = array_merge( array($query), $args);

		$query = call_user_func_array(array(&$this, 'prepare'), $arg_list);

		return $this->query($query);
	}

	function prepare_array($query, $args){

		$arg_list = array_merge( array($query), $args);

		return call_user_func_array(array(&$this, 'prepare'), $arg_list);
	}

	function prepare_query(){
		$arg_list = func_get_args();

//		$queryskeleton = $arg_list[0];
//		$hash = md5($queryskeleton);

		$query = call_user_func_array(array(&$this, 'prepare'), $arg_list);

//		$start = gettime();

//		$ret =
		return $this->query($query);

/*		$end = gettime();

		$difftime = $end - $start;

//must change, inteferes with insertid();
//		if($this->queries[$this->num_queries-1]['time'] > $this->slowtime){
			mysql_query($this->prepare("INSERT IGNORE INTO querylog SET hash = ?, query = ?, avgtime = ?, count = 1", $hash, $queryskeleton, $difftime), $this->insertdb->db_connect_id);
			if(mysql_affected_rows($this->insertdb->db_connect_id) == 0)
				mysql_query($this->prepare("UPDATE querylog SET avgtime = ((avgtime * count) + ?)/(count+1), count = count+1 WHERE hash = ?", $difftime, $hash), $this->insertdb->db_connect_id);
//		}

//	echo $queryskeleton;

		return $ret;

		CREATE TABLE `querylog` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `hash` varchar(32) NOT NULL default '',
  `query` text NOT NULL,
  `avgtime` double NOT NULL default '0',
  `count` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `hash` (`hash`)
) TYPE=MyISAM;

*/
	}

	function repeatquery($query,$limit=1000){
		do{
			$this->query("$query LIMIT $limit");
			usleep(100);
		}while($this->affectedrows() >= $limit);
	}

	function numrows($query_id = 0){
		if(!$query_id)
			$query_id = $this->query_result;

		if($query_id['con']=="s")
			return $this->selectdb->numrows($query_id['result']);
		elseif($query_id['con']=="i")
			return $this->insertdb->numrows($query_id['result']);
		else
			trigger_error("Bad query id $query_id", E_USER_ERROR);
	}

	function affectedrows(){
		$query_id = $this->query_result;

		if($query_id['con']=="s")
			return $this->selectdb->affectedrows();
		elseif($query_id['con']=="i")
			return $this->insertdb->affectedrows();
		else
			trigger_error("Bad query id $query_id", E_USER_ERROR);
	}

	function numfields($query_id = 0){
		if(!$query_id)
			$query_id = $this->query_result;

		if($query_id['con']=="s")
			return $this->selectdb->numfields($query_id['result']);
		elseif($query_id['con']=="i")
			return $this->insertdb->numfields($query_id['result']);
		else
			trigger_error("Bad query id $query_id", E_USER_ERROR);
	}

	function fieldname($offset, $query_id = 0){
		if(!$query_id)
			$query_id = $this->query_result;

		if($query_id['con']=="s")
			return $this->selectdb->fieldname($offset, $query_id['result']);
		elseif($query_id['con']=="i")
			return $this->insertdb->fieldname($offset, $query_id['result']);
		else
			trigger_error("Bad query id $query_id", E_USER_ERROR);
	}

	function fieldtype($offset, $query_id = 0){
		if(!$query_id)
			$query_id = $this->query_result;

		if($query_id['con']=="s")
			return $this->selectdb->fieldtype($offset,$query_id['result']);
		elseif($query_id['con']=="i")
			return $this->insertdb->fieldtype($offset,$query_id['result']);
		else
			trigger_error("Bad query id $query_id", E_USER_ERROR);
	}

	function fetchrow($query_id = 0, $type = DB_ASSOC){
		if(!$query_id)
			$query_id = $this->query_result;

		if($query_id['con']=="s")
			return $this->selectdb->fetchrow($query_id['result'], $type);
		elseif($query_id['con']=="i")
			return $this->insertdb->fetchrow($query_id['result'], $type);
		else
			trigger_error("Bad query id $query_id", E_USER_ERROR);
	}

	function fetchrowset($query_id = 0, $type = DB_ASSOC){
		if(!$query_id)
			$query_id = $this->query_result;

		if($query_id['con']=="s")
			return $this->selectdb->fetchrowset($query_id['result'], $type);
		elseif($query_id['con']=="i")
			return $this->insertdb->fetchrowset($query_id['result'], $type);
		else
			trigger_error("Bad query id $query_id", E_USER_ERROR);
	}

	function fetchfield($field=0, $rownum = -1, $query_id = 0){
		if(!$query_id)
			$query_id = $this->query_result;

		if($query_id['con']=="s")
			return $this->selectdb->fetchfield($field, $rownum, $query_id['result']);
		elseif($query_id['con']=="i")
			return $this->insertdb->fetchfield($field, $rownum, $query_id['result']);
		else
			trigger_error("Bad query id $query_id", E_USER_ERROR);
	}

	function rowseek($rownum, $query_id = 0){
		if(!$query_id)
			$query_id = $this->query_result;

		if($query_id['con']=="s")
			return $this->selectdb->rowseek($rownum, $query_id['result']);
		elseif($query_id['con']=="i")
			return $this->insertdb->rowseek($rownum, $query_id['result']);
		else
			trigger_error("Bad query id $query_id", E_USER_ERROR);
	}

	function insertid(){
		$query_id = $this->query_result;

		if($query_id['con']=="s")
			return $this->selectdb->insertid();
		elseif($query_id['con']=="i")
			return $this->insertdb->insertid();
		else
			trigger_error("Bad query id $query_id", E_USER_ERROR);
	}

	function listtables(){
		$this->query_result = array("con" => "i", "result" => $this->insertdb->listtables());

		return $this->query_result;
	}

	function freeresult($query_id = 0){
		if(!$query_id)
			$query_id = $this->query_result;

		if($query_id['con']=="s")
			return $this->selectdb->freeresult($query_id['result']);
		elseif($query_id['con']=="i")
			return $this->insertdb->freeresult($query_id['result']);
		else
			trigger_error("Bad query id $query_id", E_USER_ERROR);
	}

	function nextAuto($table){
		return $this->insertdb->nextAuto($table);
	}

	function error(){
		$query_id = $this->query_result;

		if($query_id['con']=="s")
			return $this->selectdb->error();
		elseif($query_id['con']=="i")
			return $this->insertdb->error();
		else
			trigger_error("Bad query id $query_id", E_USER_ERROR);
	}

	function escape($str){
		return $this->insertdb->escape($str);
	}

	function explain($query){
		return $this->insertdb->explain($query);
	}
}





class sql_db{ //must be saved by reference

	var $db_connect_id;
	var $query_result;
	var $row = array();
	var $rowset = array();
	var $num_queries = 0;
	var $in_transaction = 0;
	var $time = 0;
	var $queries = array();
	var $slowtime = 10000; //in 1/10000 seconds
	var $lastquery = "";

	var $debug = false;

/*
	function sql_db($sqlserver, $sqluser, $sqlpassword, $database, $persistency = false)
	function close()
	function query($query)
	function unbuffered_query($query)
	function begin()
	function commit()
	function prepare()
	function prepare_array($query)
	function prepare_array_query($query)
	function prepare_query()
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
*/


	function sql_db($sqlserver, $sqluser, $sqlpassword, $database, $persistency = false){
		$this->server = $sqlserver;
		$this->user = $sqluser;
		$this->password = $sqlpassword;
		$this->dbname = $database;
		$this->persistency = $persistency;

	}

	function connect(){
		if($this->db_connect_id)
			return;

		if($this->persistency){
			$this->db_connect_id = @mysql_pconnect($this->server, $this->user, $this->password, true);
		}else{
			$this->db_connect_id = @mysql_connect($this->server, $this->user, $this->password, true);
			register_shutdown_function(array(&$this, "close"));
		}

		if(!$this->db_connect_id)
			die("Connection Failed");
//			return false;

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

		return $result;
	}

	function query($query, $buffered = true){
		$this->connect();

		unset($this->query_result);

		$starttime = gettime();

		$explain = array();
		if($this->debug && strtoupper(substr($query, 0, 6)) == "SELECT"){
			$explainresult = mysql_query("EXPLAIN $query", $this->db_connect_id);
			while($line = mysql_fetch_assoc($explainresult)){
				$explain[] = $line;
				if(isset($line['table']) && $line['type'] == 'ALL')
					trigger_error("SQL warning: No Index used on table $line[table]. Query: $query", E_USER_NOTICE);
			}
		}

		$this->lastquery = $query;

		if($buffered)
			$this->query_result = mysql_query($query, $this->db_connect_id);
		else
			$this->query_result = mysql_unbuffered_query($query, $this->db_connect_id);

		$endtime = gettime();

		$querytime = $endtime - $starttime;

		$this->queries[$this->num_queries] = array('time' => $querytime, 'query' => $query, 'explain' => $explain);

		if($querytime > $this->slowtime)
			trigger_error("SQL warning: took " . number_format($querytime/10000,4) . " secs. Query: $query", E_USER_NOTICE);

		$this->time += $querytime;

		$this->num_queries++;

		if($this->query_result){
			unset($this->row[$this->query_result]);
			unset($this->rowset[$this->query_result]);

			return $this->query_result;
		}else{
			$error = $this->error();
			trigger_error("SQL error: $error[code] : $error[message]. Query: $query", E_USER_ERROR);

			return false;
		}
	}

	function unbuffered_query($query){
		$this->query($query,false);
	}

	function begin(){
		$this->in_transaction = true;
	}

	function commit(){
		$this->in_transaction = false;
	}

	function prepare_query(){
		$arg_list = func_get_args();

		$query = call_user_func_array(array(&$this, 'prepare'), $arg_list);

		return $this->query($query);
	}

	function prepare_array_query($query, $args){
		$arg_list = array_merge( array($query), $args);

		$query = call_user_func_array(array(&$this, 'prepare'), $arg_list);

		return $this->query($query);
	}

	function prepare_array($query, $args){

		$arg_list = array_merge( array($query), $args);

		return call_user_func_array(array(&$this, 'prepare'), $arg_list);
	}

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

//useful for huge deletes or updates
	function repeatquery($query,$limit=1000){
		do{
			$this->query("$query LIMIT $limit");
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
			case MYSQL_ASSOC:	$this->row[$query_id] = mysql_fetch_assoc($query_id);	break;
			case MYSQL_NUM:		$this->row[$query_id] = mysql_fetch_row($query_id);		break;
			case MYSQL_BOTH:	$this->row[$query_id] = mysql_fetch_array($query_id);	break;
		}
		return $this->row[$query_id];
	}

/*
	function fetchrowset($query_id = 0, $type = MYSQL_ASSOC){
		if(!$query_id)
			$query_id = $this->query_result;

		if(!$query_id)
			return false;

		unset($this->rowset[$query_id]);
		unset($this->row[$query_id]);

		while($this->rowset[$query_id] = mysql_fetch_array($query_id, $type))
			$result[] = $this->rowset[$query_id];

		return $result;
	}
*/

	function fetchfield($field=0, $rownum = -1, $query_id = 0){
		if(!$query_id)
			$query_id = $this->query_result;

		if(!$query_id)
			return false;

		$result = false;

		if($rownum > -1){
			$result = mysql_result($query_id, $rownum, $field);
		}else{
			if(empty($this->row[$query_id]) && empty($this->rowset[$query_id])){
				if($this->fetchrow()){
					if($field)
						$result = $this->row[$query_id][$field];
					else
						$result = current($this->row[$query_id]);
				}
			}else{
				if(!empty($this->rowset[$query_id])){
					if($field)
						$result = $this->rowset[$query_id][$field];
					else
						$result = current($this->rowset[$query_id]);
				}elseif(!empty($this->row[$query_id])){
					if($field)
						$result = $this->row[$query_id][$field];
					else
						$result = current($this->row[$query_id]);
				}else
					trigger_error("both empty, but not",E_USER_WARNING);
			}
		}

		return $result;
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

		$this->query_result = mysql_list_tables($this->dbname,$this->db_connect_id);

		return $this->query_result;
	}

	function freeresult($query_id = 0){
		if(!$query_id)
			$query_id = $this->query_result;

		if(!$query_id)
			return false;

		unset($this->row[$query_id]);
		unset($this->rowset[$query_id]);

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

		$result = $this->query("EXPLAIN $query");

		$str .= "<table border=1 bgcolor=#FFFFFF>";

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
		echo "<table border=1 bgcolor=#FFFFFF cellspacing=0>";
		echo "<tr><td>$name Database host</td><td>" . $this->server . "</td></tr>";
		echo "<tr><td>Queries</td><td>" . $this->num_queries . "</td></tr>";
		echo "<tr><td>Total Query time</td><td>" .number_format($this->time/10,3) . " milliseconds</td></tr>";

		echo "<tr><td colspan=2>Query list:";
		echo "<table border=1 cellspacing=0 cellpadding=2>";
		foreach($this->queries as $row){
			echo "<tr><td align=right nowrap>" . number_format($row['time']/10,3) . " ms</td><td>" . $row['query'] . "</td></tr>";
			if(true && count($row['explain'])){
				$lengths = $row['explain'][0];
				foreach($lengths as $name => $val){
					$length = strlen($name)+1;
					foreach($row['explain'] as $explain)
						if(strlen($explain[$name]) > $length)
							$length = strlen($explain[$name]);
					$lengths[$name] = $length;
				}

				echo "<tr><td></td><td>";
				echo "<table border=1>";
				echo "<tr>";
				foreach($lengths as $name => $length)
					echo "<td>$name</td>";
				echo "</tr>";
				foreach($row['explain'] as $explain){
					echo "<tr>";
					foreach($explain as $name => $val){
						if($val == NULL)
							$val = "NULL";
						echo "<td>$val</td>";
					}
					echo "</tr>";
				}
				echo "</table>";
				echo "</td></tr>";
			}
		}
		echo "</table>";
		echo "</td></tr></table>";
	}
//*/

	function format_values_backup(&$value, $key){
		$value = str_replace(",","\,",$value);
		$value = str_replace("\n","\\n",$value);
		$value = str_replace("\"","\\\"",$value);
		$value = "\"$value\"";
	}

	function backup($basedir,$status=1){
		set_time_limit(0);

//		$this->check($status);

		$time0 = time();

		$this->debugoutput("Starting backup<br>\n", $status);

		$cp = fopen("$basedir/dbcreation.sql","w");

		$tables = array();

	    $tableresult = $this->listtables();
	    while (list($name) = $this->fetchrow($tableresult,DB_NUM))
			$tables[] = $name;

		foreach($tables as $name){
	        fwrite($cp,"# starting table $name\n");

			$this->debugoutput("Starting table $name ... ", $status);

			$time1 = time();
/*
			$result = $this->query("CHECK TABLE `$name` MEDIUM");
			$check = $this->fetchrow($result);

			if($check['Msg_text']!='OK' && $check['Msg_text']!='Table is already up to date'){
				$this->debugoutput("check msg: $check[Msg_type] : $check[Msg_text], repairing ... ", $status);;
				$result = $this->query("REPAIR TABLE `$name`");
				$repair = $this->fetchrow($result);

				if($repair['Msg_text']!='OK')
					die("Couldn't repair database $name: $repair[Msg_text]\n<br>");
			}
*/
			fwrite($cp, "DROP `$name`;\n");

			$result = $this->query("SHOW CREATE TABLE `$name`");
			fwrite($cp, $this->fetchfield(1,0,$result) . ";\n\n");

			$fp = fopen("$basedir/$name.dump", "w");

			$result = $this->unbuffered_query("SELECT * FROM `$name`");

			$output="";
	        while($line = $this->fetchrow($result,DB_NUM)){
	        	array_walk($line,array(&$this,'format_values_backup'));
	        	$output .= implode(",", $line) . "\n";
	        	if(strlen($output) > 256*1024){ //256kb
	        		fwrite($fp,$output);
	        		$output="";
	        	}
	        }

	        fwrite($fp,$output);
			$output="";
	        fclose($fp);

			$time2 = time();

			$this->debugoutput("Created file $name.dump .. " . ($time2 - $time1) . "s<br>\n", $status);
		}

		fclose($cp);

		$time2 = time();

		$this->debugoutput("dumping took: " . ($time2 - $time0) . " seconds<br>\n", $status);

	/*	$this->debugoutput("tarring ... ", $status);


		if(file_exists("$basedir/backup/endb.old.tar.bz2"))
			unlink("$basedir/backup/endb.old.tar.bz2");

		if(file_exists("$basedir/backup/endb.tar.bz2"))
			rename("$basedir/backup/endb.tar.bz2","$basedir/backup/endb.old.tar.bz2");

		$time1=time();

		exec("tar cfj $basedir/backup/endb.tar.bz2 $basedir/backup/db");

		$time2 = time();

		$this->debugoutput("tarring took " . ($time2 - $time1) . " seconds", $status);

	*/

	}

	function optimize($status = 1){
		set_time_limit(0);

		$this->debugoutput("Starting optimization<br>\n", $status);

		$tables = array();

		$tableresult = $this->listtables();
		while (list($name) = $this->fetchrow($tableresult,DB_NUM))
			$tables[] = $name;

		foreach($tables as $name){
			$this->debugoutput("Starting table $name ... ", $status);
			$time1 = time();
			$this->query("OPTIMIZE TABLE `$name`");
			$this->query("ANALYZE TABLE `$name`");
			$time2 = time();
			$this->debugoutput("Finished in " . ($time2 - $time1) . "secs<br>", $status);
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
					die("Couldn't repair table $name: $repair[Msg_text]\n<br>");
			}

			$time2 = time();

			$this->debugoutput("Finished in " . ($time2 - $time1) . " secs<br>", $status);
		}
	}

	function debugoutput($text, $verbocity){
		if($verbocity){
			echo $text;
			if($verbocity >= 2)
				zipflush();
		}
	}

} //end sql_db class


/*
function sqlSafe(	$a0,
					$a1=NULL,
					$a2=NULL,
					$a3=NULL,
					$a4=NULL,
					$a5=NULL,
					$a6=NULL,
					$a7=NULL,
					$a8=NULL,
					$a9=NULL,
					$a10=NULL,
					$a11=NULL,
					$a12=NULL,
					$a13=NULL,
					$a14=NULL,
					$a15=NULL,
					$a16=NULL,
					$a17=NULL,
					$a18=NULL,
					$a19=NULL){
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

function getEnumValues($table, $field ){
	global $db;
	$result = $db->query( "SHOW COLUMNS FROM $table");
	$nr = $db->numrows( $result );

	while( list( $name, $type ) = $db->fetchrow( $result , DB_NUM)) {
		if( $name == $field ) {
			if( ereg( '^enum\(.*\)$', $type ))
				$type = substr( $type, 6,-2 );
			else if( ereg( '^set\(.*\)$', $type ))
				$type = substr( $type, 5, -2);
			else
				return( false );

			return( explode( "','", $type ) );

			break;
		}
	}
	return false;
}



