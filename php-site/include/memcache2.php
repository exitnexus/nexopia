<?

/*

constructor:
- cache($memcache, $basedir) - $basedir of hdcache

public:

- get($name, $refresh, $callback, $default) - get $name, if refresh, etc. set, wrap in expiry array. Wrapping gives atomicity and default values at the cost of speed
- put($name, $value, $refresh, $wrap) - put $value in cache under $name for $refresh seconds, if $wrap, wrap in expiry array
- incr($name, $value) - increments the value of $name by $value, only works with numbers, returns the result, won't work for wrapped values
- decr($name, $value) - decrements the value of $name by $value, only works with numbers, returns the result, won't work for wrapped values
- remove($name) - remove $name from cache

- hdget($name, $refresh, $callback, $default) - check local cache, if not, check memcache, if still not, put up a lock, put in memcache, and local, then unlock


*/

class cache{
	var $basedir;
	var $cachedb;

	var $values; //hdget values
	var $locksuccess;

	var $memcache;

	var $actions = array();
	var $time = 0;

	function cache( & $memcache, $basedir){
		$this->values = array();
		$this->memexpire = false;

		$this->basedir = $basedir;

		$this->memcache = & $memcache;
	}


	function get($key, $refresh = false, $callback = false, $default = array()){

		$time1 = gettime();

		$name = (is_array($key) ? $key[1] : $key);

		if(strpos($name, ' ') !== false)
			trigger_error("cache name: '$name' has a space", E_USER_ERROR);

		if($refresh === false){
			$ret = $this->memcache->get($key);

			$this->action("get", $name, gettime() - $time1);

			return $ret;
		}


		$line = $this->memcache->get($key);

		$this->action("getback", $name, gettime() - $time1);

		$time = time();

		if($line){
			$line['value'] = unserialize($line['value']);
		}else{
			$line = array('time' => 0, 'expire' => 0, 'value' => $default);
		}

		$rows = $line['value'];

		$this->memexpire = $line['expire'];

		if($line['expire'] == 0 || $time >= $line['expire']){ //expired
			if($callback){
				if($this->memcache->add("lock-$name", "locked", 5)){
					$this->memexpire = $time + $refresh;

					if(is_array($callback) && isset($callback['function']))
						$rows = call_user_func_array($callback['function'], $callback['params']);
					else
						$rows = call_user_func($callback);

					$this->put($key, $rows, $refresh, true);

					$this->remove("lock-$name");
				}
			}else{
				$rows = false;
			}
		}

		return $rows;
	}

	function put($key, $value, $refresh, $wrap = false){
		$time1 = gettime();
		$name = (is_array($key) ? $key[1] : $key);
		if($wrap){
			$time = time();
			$this->memcache->set($key, array('time' => $time, 'expire' => $time + $refresh, 'value' => serialize($value)), $refresh + 60);
			$this->action("putback", $name, gettime() - $time1);
		}else{
			$this->memcache->set($key, $value, $refresh);
			$this->action("put", $name, gettime() - $time1);
		}
	}

	function incr($key, $value = 1){
		$time1 = gettime();
		$name = (is_array($key) ? $key[1] : $key);
		$ret = $this->memcache->incr($key, $value);
		$this->action("incr", $name, gettime() - $time1);
		if($ret === 'NOT_FOUND')
			$ret = false;

		return $ret;
	}

	function decr($key, $value = 1){
		$time1 = gettime();
		$name = (is_array($key) ? $key[1] : $key);
		$ret = $this->memcache->decr($key, $value);
		$this->action("decr", $name, gettime() - $time1);
		if($ret === 'NOT_FOUND')
			$ret = false;

		return $ret;
	}

	function remove($key){
		$time1 = gettime();
		$name = (is_array($key) ? $key[1] : $key);
		$this->memcache->delete($key);
		$this->action("del", $name, gettime() - $time1);
	}

	function cleanup(){
	}

	function action($action, $name, $time){
		$this->actions[] = array('action' => $action, 'key' => $name, 'time' => $time);
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




	function hdget($name, $refresh, $callback, $default = false){
//		if(strlen($name) > 12)
//			trigger_error("cache name: '$name' is too long", E_USER_ERROR);

		if(isset($this->values[$name])){
			$line = $this->values[$name];
		}else{
			if(file_exists($this->basedir . "/$name.php")){
				$this->values[$name] = $line = include_once($this->basedir . "/$name.php");
			}else{
				$this->values[$name] = $line = array('time' => 0, 'expire' => 1, 'value' => $default);
			}
		}

		$result = $line['value'];

		$time = time();

		if(!$line['time'] || ($line['expire'] && $time >= $line['expire'])){
			if($refresh == 0)	$memrefresh = 60; 		//save for 60 seconds for the other servers to grab it too
			else				$memrefresh = $refresh; //else only save it as long as it is valid

			$result = $this->get($name, $memrefresh, $callback, $default); //sets $this->memexpire to the expiry date

			if($this->memexpire > $time){
				if($refresh == 0)			$expire = 0;
				elseif($refresh < 30*86400)	$expire = $this->memexpire;
				else						$expire = $refresh;

				$dump = array(	'time' => $time,
								'expire' => $expire,
								'value' => $result);

				$this->values[$name] = $dump;

				$output = "<?\nreturn " . var_export($dump, true) . ";\n";

				flockwrite($output, $this->basedir . "/$name.php");
			}
		}

		return $result;
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

