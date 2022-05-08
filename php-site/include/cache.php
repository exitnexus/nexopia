<?

/*

constructor:
- cache($basedir) - $basedir of hdcache

public:
- prime($name) - add $name to the list of values to get in next db hit

- get($name, $refresh, $callback, $default) - get $name and all primed values, and check flags
- put($name, $value, $refresh) - put $value in cache under $name for $refresh seconds
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

	function cache($basedir){
		$this->fetch = array();
		$this->values = array();
		$this->flags = array();

		$this->basedir = $basedir;

		global $fastdb;
		$this->cachedb = & $fastdb;
	}

	function prime($expected){
		if(is_array($expected)){
			foreach($expected as $val)
				$this->prime($val);
		}else{
/*			if(function_exists('mmcache_get')){
				$val = mmcache_get($expected);
				if($val != null){
					if($val['time'] > time()) //hasn't expired yet
						$this->values[$expected] = $val;
//					echo "mmcache_get: $expected<br>\n";
				}
			}
			if(!isset($this->values[$expected]))
*/				$this->fetch[$expected] = $expected;
		}
	}

	function get($name, $refresh = 0, $callback = false, $default = array()){

		if(strlen($name) > 12)
			trigger_error("cache name: '$name' is too long", E_USER_ERROR);

		$this->prime($name);

		if(isset($this->values[$name])){
			$line = $this->values[$name];
		}else{
			$result = $this->cachedb->prepare_query("SELECT name,time,value FROM cache WHERE name IN (?)", $this->fetch);

			$this->fetch = array();

			while($line = $this->cachedb->fetchrow($result)){
				if(isset($this->flags[$line['name']])){ //flags
					if($this->flags[$line['name']]['time'] < $line['time']){
						$this->doFlag($line['name']);
/*						if(is_array($this->flags[$line['name']]['callback']))
							call_user_func_array($this->flags[$line['name']]['callback']['function'],$this->flags[$line['name']]['callback']['params']);
						else
							$this->flags[$line['name']]['callback']();
*/					}
				}else{ //caches
					$this->values[$line['name']] = $line;
/*					if(function_exists('mmcache_put')){
						mmcache_put($line['name'],$line,3600);
	//					echo "mmcache_put1: $line[name]<br>\n";
					}
*/
				}
			}

			if(isset($this->values[$name])){
				$line = $this->values[$name];
			}else{
				$line = array('time' => 0, 'value' => serialize($default));
				$this->values[$name] = $line;
			}
		}

		$rows = unserialize($line['value']);

		if($line['time'] == 0 || time() >= $line['time']){ //expired
			if($callback && $refresh){
				$this->cachedb->query("SELECT GET_LOCK('$name',0)");
				if($this->cachedb->fetchfield() == 1){

					if(is_array($callback))
						$rows = call_user_func_array($callback['function'],$callback['params']);
					else
						$rows = $callback();

					$this->put($name, $rows, $refresh);

					$this->cachedb->query("SELECT RELEASE_LOCK('$name')");
				}
			}else{
				return false;
			}
		}

		return $rows;
	}

	function put($name, $value, $refresh){
		$expiry = time() + $refresh;

		$serialized = serialize($value);

		$this->cachedb->prepare_query("UPDATE cache SET time = ?, value = ? WHERE name = ?", $expiry, $serialized, $name);

		if($this->cachedb->affectedrows() == 0)
			$this->cachedb->prepare_query("INSERT IGNORE INTO cache SET time = ?, value = ?, name = ?", $expiry, $serialized, $name);

/*		if(function_exists('mmcache_put')){
			mmcache_put($name,array('name'=>$name, 'time' => $expiry, 'value' => $serialized),3600);
//					echo "mmcache_put2: $name<br>\n";
		}
*/
	}

	function remove($name){
		$this->cachedb->prepare_query("DELETE FROM cache WHERE name IN (?)", $name);
/*
		if(function_exists('mmcache_rm')){
			if(is_array($name))
				foreach($name as $val)
					mmcache_rm($val);
			else
				mmcache_rm($name);
		}
*/	}

	function cleanup(){
		$this->cachedb->prepare_query("DELETE FROM cache WHERE time <= ? && value != ''", time()); //remove expired caches, leave flags
	}



	function hdget($name, $callback = false, $default = false){
		if(strlen($name) > 12)
			trigger_error("cache name: '$name' is too long", E_USER_ERROR);

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
		$this->fetch[$name] = $name;
		$this->flags[$name] = array('time' => $time, 'callback' => $callback, 'hdcache' => $hdcache);
	}

	function resetFlag($name){
//		echo "resetflag: $name\n";
		$this->cachedb->prepare_query("UPDATE cache SET time = ? WHERE name = ?", time(), $name);
		if($this->cachedb->affectedrows() == 0)
			$this->cachedb->prepare_query("INSERT IGNORE INTO cache SET time = ?, name = ?", time(), $name);

		unset($this->flags[$name]);
	}

	function doFlag($name){
//		echo "doflag: $name\n";
		if(is_array($this->flags[$name]['callback']))
			$result = call_user_func_array($this->flags[$name]['callback']['function'],$this->flags[$name]['callback']['params']);
		else
			$result = $this->flags[$name]['callback']();

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

