<?

/*

constructor:
- cache($basedir) - $basedir of hdcache

public:
- prime($name) - add $name to the list of values to get in next db hit

- get($name, $refresh, $callback, $default) - get $name, if refresh, etc. set, wrap in expiry array. Wrapping gives atomicity and default values at the cost of speed
- put($name, $value, $refresh, $wrap) - put $value in cache under $name for $refresh seconds, if $wrap, wrap in expiry array
- incr($name, $value) - increments the value of $name by $value, only works with numbers, returns the result, won't work for wrapped values
- decr($name, $value) - decrements the value of $name by $value, only works with numbers, returns the result, won't work for wrapped values
- remove($name) - remove $name from cache
- cleanup() - remove all expired caches, leave flags

- hdget($name, $callback, $default) - check local cache, set check flag
- hdput($name, $value, $resetflag) - put to local cache
- hdremove($name) - remove from local cache

- resetFlag($name) - reset flag time

private:
- flag($name, $time, $callback, $hdcache) - if cached time for $name is greater than $time, do $callback
- doFlag($name) - do the callback, hdcache if needed

*/

class cache{
	var $basedir;
	var $cachedb;

	var $fetch;
	var $values;
	var $flags;

	var $memcache;

	var $actions = array();
	var $time = 0;

	function cache( & $memcache, $basedir){
		$this->fetch = array();
		$this->values = array();
		$this->flags = array();

		$this->basedir = $basedir;

		$this->memcache = & $memcache;
	}

	function prime($expected){
		if(is_array($expected)){
			foreach($expected as $val)
				if(!isset($this->values[$val]))
					$this->fetch[$val] = $val;
		}else{
			if(!isset($this->values[$expected]))
				$this->fetch[$expected] = $expected;
		}
	}

	function get($name, $refresh = 0, $callback = false, $default = array()){

		$time1 = gettime();

		if(is_array($name)){
			if(strpos($name[1], ' ') !== false)
				trigger_error("cache name: '$name[1]' has a space", E_USER_ERROR);
		}else{
			if(strpos($name, ' ') !== false)
				trigger_error("cache name: '$name' has a space", E_USER_ERROR);
		}

		if(!$refresh){
			$ret = $this->memcache->get($name);

			$this->action("get", $name, gettime() - $time1);

			return $ret;
		}

		if(isset($this->values[$name])){
			$line = $this->values[$name];
		}else{
			$line = $this->memcache->get($name);

			$this->action("getback", $name, gettime() - $time1);

			if($line){
				$this->values[$name] = $line;
			}else{
				$this->values[$name] = $line = array('time' => 0, 'value' => serialize($default));
			}
		}

		$rows = unserialize($line['value']);

		if($line['time'] == 0 || time() >= $line['time']){ //expired
			if($callback && $refresh){
				if($this->memcache->add("lock-$name","locked",5)){
					if(is_array($callback) && isset($callback['function']))
						$rows = call_user_func_array($callback['function'],$callback['params']);
					else
						$rows = call_user_func($callback);

					$this->put($name, $rows, $refresh, true);

					$this->remove("lock-$name");
				}
			}else{
				$rows = false;
			}
		}

		return $rows;
	}

	function put($name, $value, $refresh, $wrap = false){
		$time1 = gettime();
		if($wrap){
			$this->memcache->set($name, array('time' => time()+$refresh, 'value' => serialize($value)), $refresh+60);
			$this->action("putback", $name, gettime() - $time1);
		}else{
			$this->memcache->set($name, $value, $refresh);
			$this->action("put", $name, gettime() - $time1);
		}
	}

	function incr($name, $value = 1){
		$time1 = gettime();
		$ret = $this->memcache->incr($name, $value);
		$this->action("incr", $name, gettime() - $time1);
		if($ret === 'NOT_FOUND')
			$ret = false;

		return $ret;
	}

	function decr($name, $value = 1){
		$time1 = gettime();
		$ret = $this->memcache->decr($name, $value);
		$this->action("decr", $name, gettime() - $time1);
		if($ret === 'NOT_FOUND')
			$ret = false;

		return $ret;
	}

	function remove($name){
		$time1 = gettime();
		$this->memcache->delete($name);
		$this->action("del", $name, gettime() - $time1);
	}

	function cleanup(){
	}

	function action($action, $key, $time){
		$this->actions[] = array('action' => $action, 'key' => (is_array($key) ? $key[1] : $key), 'time' => $time);
		$this->time += $time;
	}

	function outputActions(){
		echo "<table border=0 cellspacing=1 cellpadding=2>";
		echo "<tr><td class=header>MemCache</td><td class=header colspan=2>" . count($this->actions) . " actions</td></tr>";
		echo "<tr><td class=header>Total Time</td><td class=header colspan=2>" . number_format($this->time/10, 3) . " ms</td></tr>";

		$class = 'body2';

		foreach($this->actions as $row)
			echo "<tr><td class=$class align=right>" . number_format($row['time']/10, 3) . " ms</td><td class=$class>$row[action]</td><td class=$class>$row[key]</td></tr>";

		echo "</table>";
	}


	function hdget($name, $refresh, $callback = false, $default = false){
//		if(strlen($name) > 12)
//			trigger_error("cache name: '$name' is too long", E_USER_ERROR);

		if(isset($this->values[$name])){
			$line = $this->values[$name];
		}else{
			if(file_exists($this->basedir . "/$name.php")){
				$this->values[$name] = $line = include_once($this->basedir . "/$name.php");
			}else{
				$this->values[$name] = $line = array('time' => 0, 'value' => $default);
			}
		}

		$rows = $line['value'];

		if($callback){
			$this->flag($name, $line['time'], $callback, true);

			if($rows === false && $default === false){
				$this->doFlag($name);
				$rows = $this->values[$name]['value'];
			}
		}

		return $rows;
	}

	function hdput($name, $value, $resetflag = true){
//		echo "hdput: $name\n";
		$dump = array('time' => time(), 'value' => $value);

		$this->values[$name] = $dump;

		$output = "<?\nreturn ";

		ob_start();
		var_export($dump);
		$output .= ob_get_clean();

		$output .=";\n";

		flockwrite($output, $this->basedir . "/$name.php");

		if($resetflag)
			$this->resetFlag($name);
	}

	function hdremove($name){
		if(file_exists($this->basedir . "/$name.php"))
			unlink($this->basedir . "/$name.php");
	}

	function flag($name, $time, $callback, $hdcache = false){
//		return;

		$this->fetch[$name] = $name;
		$this->flags[$name] = array('time' => $time, 'callback' => $callback, 'hdcache' => $hdcache);
	}

	function resetFlag($name){
//		return;

//		$this->put($name, "", 0);

		unset($this->flags[$name]);
	}

	function doFlag($name){
//		return;

//		echo "doflag: $name\n";
		if(is_array($this->flags[$name]['callback']) && isset($this->flags[$name]['callback']['function']))
			$result = call_user_func_array($this->flags[$name]['callback']['function'],$this->flags[$name]['callback']['params']);
		else
			$result = call_user_func($this->flags[$name]['callback']);

		if($this->flags[$name]['hdcache'])
			$this->hdput($name, $result, false);

		unset($this->flags[$name]);
	}
}

function flockwrite($str, $file){

	clearstatcache();

	$fh = fopen($file, 'w');
	if( !$fh )
		exit;

	$i=20;
	while($i--){
		clearstatcache();
		$lf = @flock($fh, LOCK_EX);

		if($lf)
			break;
		usleep(rand(5,50));
	}

	if( !$lf ){  //something failed
		fclose($fh);
		return false;
	}

	fwrite($fh, $str);
	fflush($fh);
	flock($fh, LOCK_UN);
	fclose($fh);

	return true;
}

