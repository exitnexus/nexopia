<?php

/*
class sql_base {
	function __construct($options)
	function connect()
	function close()
	function describe()

	function begin()
	function commit()
	function rollback()

	function query($query)
	function squery($keys, $query)
	function unbuffered_query($query)
	function prepare_query($query, ...)
	function prepare_squery($keys, $query, ...)
	function prepare_array_query($query, $params)
	function repeatquery($query, $limit)

	function prepare($query, $params)
	function prepare_array($query, $params)
	function prepare_multikey($keys, $values)
	function escape($val)

	function getSeqID($userid, $area, $start = false)
	function affectedrows()
	function insertid()

	function massage_query($query) - internal
	function getServerValues($query) - internal, to extract keys from query

	function listtables()
	function nextAuto($table)
	function error()

	function debug($query, $querytime)
	function debugerror($msg, $debugmsg, $die)
	function setslowtime($time)
	function getnumqueries()
	function getquerytime()
	function explain($query)
	function outputQueries()
	function debugoutput($text, $verbocity)

	function backup
	function restore
	function analyze
	function optimize
	function check
}

class db_result {
	function affectedrows()       - actually associated with the connection, not the result
	function insertid($name = '') - actually associated with the connection, not the result
	function totalrows() - if the prev SELECT had a SQL_CALC_FOUND_ROWS, it returns the result
	function numfields() - num columns in the result
	function fieldname($offset)
	function fieldtype($offset)
	function rowseek($rownum)
	function fetchrow($type = DB_ASSOC) - returns one row at a time
	function fetchrowset($type = DB_ASSOC) - returns an array of all rows
	function fetchkeyedrowset($key) - returns an array of all rows, with the key being the specified column
	function fetchfield($field = 0, $rownum = -1) - returns a single field (generally useful when a query is guaranteed to return one row with one column)
	function fetchfields($keycol) - return a single array mapping from $keycol to the other col. Useful for userid -> username type things
}
*/

class sql_base
{
	public $query_result;

/*
debuglevels:
0: no debug at all
1: store queries, explain on demand
2: explain for all queries, show debug errors for all users
*/
	public $debuglevel;
	public $debugtables;
	public $debugregex;
	public $slowtime;
	public $queries;

	function __construct($options)
	{
		$this->query_result = null;

		$this->debuglevel	= (isset($options['debuglevel'])  ? $options['debuglevel']  : 1);
		$this->debugtables	= (isset($options['debugtables']) ? $options['debugtables'] : array());
		$this->debugregex	= (isset($options['debugregex'])  ? $options['debugregex']  : '');
		$this->slowtime 	= (isset($options['slowtime'])    ? $options['slowtime']    : 100000);
		$this->queries = array();
	}

	// this function should be called before running a new query to prevent buffer overlaps
	function clearresult()
	{
		if (isset($this->query_result))
		{
			unset($this->query_result);
		}
	}


	function setresult($query_result){
		$this->query_result = $query_result;
		return $this->query_result;
	}

	function squery($keys, $query, $buffered=true){ //default, ignore $keys
		return $this->query($query, $buffered);
	}

	function unbuffered_query($query){
		return $this->query($query, false);
	}

	function prepare_query(){ //$query, $args...
		$arg_list = func_get_args();

		$query = call_user_func_array(array(&$this, 'prepare'), $arg_list);

		return $this->query($query);
	}

	function prepare_squery(){ //$keys, $query, $args...
		$arg_list = func_get_args();

		$keys = array_shift($arg_list);

		$query = call_user_func_array(array(&$this, 'prepare'), $arg_list);

		return $this->squery($keys, $query);
	}

	function prepare_array_query($query, $args){
		array_unshift($args, $query);

		$query = call_user_func_array(array(&$this, 'prepare'), $args);

		return $this->query($query);
	}

	function prepare_array($query, $args){

		$arg_list = array_merge( array($query), $args);

		return call_user_func_array(array(&$this, 'prepare'), $arg_list);
	}

	function massage_query($query){
		// derived by implementations
		return $query;
	}

//splitting and reconstructing is faster than matching and replacing in place
	function prepare(){
		$numargs = func_num_args();
		$arg_list = func_get_args();
		if($numargs == 0){
			trigger_error("SQL error: prepare() called with 0 arguments.", E_USER_ERROR);
			return "";
		}
		if($numargs == 1)
			return $arg_list[0];
//			return $this->massage_query($arg_list[0]);

//		$query = $this->massage_query($arg_list[0]);
		$query = $arg_list[0];

		$tokens = preg_split('/([\?\#%^])/', $query, -1, PREG_SPLIT_DELIM_CAPTURE);	

		$numtokens = count($tokens);

		if($numtokens != ($numargs*2 - 1)){
			trigger_error("SQL error: prepare() placeholder and argument count mismatch in " . $arg_list[0] . ". $arg_list[1]", E_USER_ERROR);
			return false;
		}

		$query = $tokens[0];
		for($i = 1; $i < $numargs; $i++) {
			$var = $arg_list[$i];

			if(!is_array($var))
				$var = array($var);

		//strings
			if($tokens[$i*2-1] == '?'){
				foreach($var as $k => $v){
					if(is_array($v))
						trigger_error("SQL error: Trying to escape: " . var_export($v, true), E_USER_ERROR);
					else
						$var[$k] = $this->escape($v);
				}
				$query .= "'" . implode("','", $var). "'";
		//already prepared by a subprepare function
			}else if($tokens[$i*2-1] == '^'){
				if (count($var) != 1 || is_array($var[0]) || !preg_match('|/\*\*\^\*\*/$|', $var[0])) // looking for /**^**/ at the end of the string
						trigger_error("SQL error: Trying to inject: " . var_export($v, true), E_USER_ERROR);
				$query .= $var[0];
		//int and null
			}else{ // == '#' | '%'
				foreach($var as $k => $v){
					if(is_array($v))
						trigger_error("SQL error: Trying to escape: " . var_export($v, true), E_USER_ERROR);
					elseif(is_null($v))
						$var[$k] = "NULL";
					else
						$var[$k] = intval($v);
				}
				$query .= implode(",", $var);
				if($tokens[$i*2-1] == '%')
					$query .= " /**%: " . implode(",", $var) . " :%**/";
			}

			$query .= $tokens[$i*2];
		}
		return $query;
	}

	// pass this into prepare() with the ^ (inject) symbol
	// $keys is an array of column names => prepare types (ie. uid=>'%', id=>'#')
	// $values is the actual values to pass to prepare
	function prepare_multikey($keys, $values){
		$clauses = array();
		foreach ($values as $item){
			if (!is_array($item))
				$item = explode(':', $item);

			if (count($keys) != count($item))
				trigger_error("Argument count mismatch in prepare_multikey: " . var_export($keys, true) . ", " . var_export($item, true) /*. ", " . var_export($values, true) */, E_USER_ERROR);

			// make it into a single array
			$item = array_combine(array_keys($keys), $item);
			// and then generate the inner clause
			$clause = array();
			foreach ($item as $key => $val)
				$clause[] = $this->prepare("$key = $keys[$key]", $val);
			$clauses[] = "(" . implode(' AND ', $clause) . ")";
		}
		return '(' . implode(' OR ', $clauses) . ') /**^**/';
	}

	function getServerValues(&$query){
		$ids = array();

		if(strpos($query, "\\") === false){ //simple way that works if nothing is escaped

			$output = preg_replace("/'.+?'/", "", $query); //take out all strings
			preg_match_all("/\/\*\*%: ([0-9,-]+) :%\*\*\//", $output, $matches); //parse out all the server key comments

			foreach($matches[1] as $match)
				$ids = array_merge($ids, explode(",", $match));

		}else{ //more correct, but slower, way
			$len = strlen($query);

			$quote = false;
			$escape = false;

			for($i = 0; $i < $len; $i++){
				$chr = $query{$i};

			//only look for the balance comment if it's not within a string
				if(!$quote){
					if($chr == '"' || $chr == "'"){
						$quote = $chr;
						continue;
					}

				//matches, cut it out of the string, and return its results
					if($chr == '/' && preg_match("/^(\/\*\*%: ([0-9,-]+) :%\*\*\/)/", substr($query, $i), $matches)){
						$ids = array_merge($ids, explode(",", $matches[2]));

						$replace = $matches[1];
						$query = substr($query, 0, $i) . substr($query, $i + strlen($replace));
						$len = strlen($query);
					}
				}else{ //look for the end of the string
					if(!$escape && $chr == '\\'){
						$escape = true;
					}else{
						if(!$escape && $chr == $quote) {
							$quote = false;
						}
						$escape = false;
					}
				}
			}
		}

		if(!count($ids))
			return false;

		return array_unique($ids);
	}

//useful for huge deletes or updates
	function repeatquery($query, $limit = 1000){
		do{
			$this->query("$query LIMIT $limit");
			usleep(100);
		}while($this->affectedrows() >= $limit);
	}

	function debug($query, $querytime){
		if(!$this->debuglevel)
			return;

		$explain = strtoupper(substr($query, 0, 6)) == "SELECT" &&
					(	$this->debuglevel >= 2 ||
						(count($this->debugtables) && preg_match("/SELECT[^;]+FROM[^;]*[,\.\s`](" . implode('|', $this->debugtables). ")([`\s]?|[`,\s][^;]*)/", $query)) ||
						($this->debugregex && preg_match($this->debugregex, $query))
					);

		array_add_max($this->queries, array('time' => $querytime, 'query' => $query, 'explain' => $explain), 1000);

		if($querytime > $this->slowtime)
			trigger_error("SQL query on " . $this->describe() . " took " . number_format($querytime/10000, 2) . "s. Query: $query", E_USER_NOTICE);
	}

	function debugerror($msg, $debugmsg, $die){
		global $userData, $debuginfousers;

		echo $msg;

		if($this->debuglevel >= 2 || ($userData['loggedIn'] && in_array($userData['userid'], $debuginfousers)))
			echo $debugmsg;

		trigger_error($msg . $debugmsg, E_USER_NOTICE); //use notice so the full message gets put in the error log, but not sent to the user
		if($die)
			die;
	}

	function setslowtime($time){
		$this->slowtime = $time;
	}

	function checkschema(& $schema, $skiptables = array()){ //pass in a null variable for $schema to bass it on the first
		$subdbs = $this->getSplitDBs();

		$error = "";

		if(count($subdbs) > 1){
			foreach($subdbs as &$db)
				$error .= $db->checkschema($schema, $skiptable);
		}else{
			$res = $this->listtables();

			$thisschema = array();
			while($line = $res->fetchrow()){
				$table = $line['Name'];

				if(!in_array($table, $skiptables)){
					$res2 = $this->query("SHOW CREATE TABLE `$table`");
					$row = $res2->fetchrow();
					
					$thisschema[$table] = preg_replace("/ AUTO_INCREMENT *= *[0-9]+/", '', $row['Create Table']);
				}
			}

			if(!$schema){
				$schema = $thisschema;
			}else{
				foreach($schema as $table => $val){
					if(!isset($thisschema[$table])){
						$error .= str_pad($table, 25) . " missing on       " . $this->describe() . "\n";
					}elseif($thisschema[$table] != $val){
						$error .= str_pad($table, 25) . " doesn't match on " . $this->describe() . "\n";
					}
				}
				foreach($thisschema as $table => $val)
					if(!isset($schema[$table]))
						$error .= str_pad($table, 25) . " unknown on       " . $this->describe() . "\n";
			}
		}

		return $error;
	}

	function explain($query){
		$str = "";

		$result = $this->query("EXPLAIN $query");

		$str .= "<table border=0 cellspacing=1 cellpadding=2>";

		$first = true;
		while($line = $result->fetchrow()){
			if($first){
				$str .= "<tr>";
				foreach($line as $name => $item)
					$str .= "<td class=header>$name</td>";
				$str .= "</tr>\n";
			}

			$str .= "<tr>";
			foreach($line as $item){
				if($item === NULL)
					$item = 'NULL';
				$str .= "<td class=body>$item</td>";
			}
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

		echo "<tr><td class=header nowrap colspan=2>";
		echo "<b>$name</b> - $this->server / $this->dbname ($this->user)- ";
		if(count($this->queries) == $this->num_queries)
			echo number_format($this->num_queries);
		else
			echo "Showing " . number_format(count($this->queries)) . " of " . number_format($this->num_queries);
		echo " " . ($this->num_queries == 1 ? "Query" : "Queries" );
		echo "</td></tr>";

		if($this->debuglevel && count($this->queries)){
			foreach($this->queries as $row){
				$class='body2';
				if($row['time'] > $this->slowtime || substr($row['query'], -13) == '/**: all :**/')
					$class='body';
				echo "<tr><td align=right nowrap class=$class>" . number_format($row['time']/10, 2) . " ms</td><td class=$class>" . htmlentities($row['query']) . "</td></tr>";
				if($row['explain']){
					echo "<tr><td class=body></td><td class=body>";

					echo $this->explain($row['query']);

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

	function debugoutput($text, $verbocity){
		if($verbocity){
			echo $text;
			if($verbocity >= 2)
				zipflush();
		}
	}

/*	function numrows($query_id = 0){
		$query_id = ($query_id ? $query_id : $this->query_result);

		return ($query_id ? $query_id->numrows($query_id) : false);
	}*/

	function affectedrows($query_id = 0){
		$query_id = ($query_id ? $query_id : $this->query_result);

		return ($query_id ? $query_id->affectedrows() : false);
	}

	function totalrows($query_id = 0){
		$query_id = ($query_id ? $query_id : $this->query_result);

		return ($query_id ? $query_id->totalrows() : false);
	}

	function numfields($query_id = 0){
		$query_id = ($query_id ? $query_id : $this->query_result);

		return ($query_id ? $query_id->numfields() : false);
	}

	function fieldname($offset, $query_id = 0){
		$query_id = ($query_id ? $query_id : $this->query_result);

		return ($query_id ? $query_id->fieldname($offset) : false);
	}

	function fieldtype($offset, $query_id = 0){
		$query_id = ($query_id ? $query_id : $this->query_result);

		return ($query_id ? $query_id->fieldtype($offset) : false);
	}

	function fetchrow($query_id = 0, $type = DB_ASSOC){
		$query_id = ($query_id ? $query_id : $this->query_result);

		return ($query_id ? $query_id->fetchrow($type) : false);
	}


	function fetchrowset($query_id = 0, $type = DB_ASSOC){
		$query_id = ($query_id ? $query_id : $this->query_result);

		return ($query_id ? $query_id->fetchrowset($type) : false);
	}

	function fetchkeyedrows($key, $query_id = 0){
		$query_id = ($query_id ? $query_id : $this->query_result);

		return ($query_id ? $query_id->fetchkeyedrows($key) : false);
	}

	function fetchfield($field = 0, $rownum = -1, $query_id = 0){
		$query_id = ($query_id ? $query_id : $this->query_result);

		return ($query_id ? $query_id->fetchfield($field, $rownum) : false);
	}

	function fetchfields($key, $query_id = 0){
		$query_id = ($query_id ? $query_id : $this->query_result);

		return ($query_id ? $query_id->fetchfields($key) : false);
	}

	function rowseek($rownum, $query_id = 0){
		$query_id = ($query_id ? $query_id : $this->query_result);

		return ($query_id ? $query_id->rowseek($rownum) : false);
	}

	function insertid($query_id = 0){
		$query_id = ($query_id ? $query_id : $this->query_result);

		return ($query_id ? $query_id->insertid() : false);
	}

	function freeresult($query_id = 0){
		$query_id = ($query_id ? $query_id : $this->query_result);

		return ($query_id ? $query_id->freeresult($rownum) : false);
	}
}

class sql_empty_result {
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

	function totalrows()
	{
		return 0;
	}
}

