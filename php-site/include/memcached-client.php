<?
//
// +---------------------------------------------------------------------------+
// | memcached client, PHP                                                     |
// +---------------------------------------------------------------------------+
// | Copyright (c) 2003 Ryan T. Dean <rtdean@cytherianage.net>                 |
// | All rights reserved.                                                      |
// |                                                                           |
// | Redistribution and use in source and binary forms, with or without        |
// | modification, are permitted provided that the following conditions        |
// | are met:                                                                  |
// |                                                                           |
// | 1. Redistributions of source code must retain the above copyright         |
// |    notice, this list of conditions and the following disclaimer.          |
// | 2. Redistributions in binary form must reproduce the above copyright      |
// |    notice, this list of conditions and the following disclaimer in the    |
// |    documentation and/or other materials provided with the distribution.   |
// |                                                                           |
// | THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR      |
// | IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES |
// | OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.   |
// | IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,          |
// | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT  |
// | NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, |
// | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY     |
// | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT       |
// | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF  |
// | THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.         |
// +---------------------------------------------------------------------------+
// | Author: Ryan T. Dean <rtdean@cytherianage.net>                            |
// | Heavily influenced by the php memcached client by Ryan T. Dean            |
// |   Permission granted by Brad Fitzpatrick for relicense of ported Perl     |
// |   client logic under 2-clause BSD license.                                |
// +---------------------------------------------------------------------------+
//
// $TCAnet$
//

/**
 * This is the PHP client for memcached - a distributed memory cache daemon.
 * More information is available at http://www.danga.com/memcached/
 *
 * Usage example:
 *
 * require_once 'memcached.php';
 *
 * $mc = new memcached(array(
 *				  'servers' => array('127.0.0.1:10000',
 *											array('192.0.0.1:10010', 2),
 *											'127.0.0.1:10020'),
 *				  'debug'	=> false,
 *				  'compress_threshold' => 10240,
 *				  'persistant' => true));
 *
 * $mc->add('key', array('some', 'array'));
 * $mc->replace('key', 'some random string');
 * $val = $mc->get('key');
 *
 * @author  Ryan T. Dean <rtdean@cytherianage.net>
 * @package memcached-client
 * @version 0.1.2
 */

// {{{ requirements
// }}}

// {{{ constants
// {{{ flags

/**
 * Flag: indicates data is serialized
 */
define("MEMCACHE_SERIALIZED", 1<<0);

/**
 * Flag: indicates data is compressed
 */
define("MEMCACHE_COMPRESS", 1<<1);

// }}}

/**
 * Minimum savings to store data compressed
 */
define("COMPRESS_SAVINGS", 0.20);

// }}}

// {{{ class memcached
/**
 * memcached client class implemented using (p)fsockopen()
 *
 * @author  Ryan T. Dean <rtdean@cytherianage.net>
 * @package memcached-client
 */
class memcached {
	// {{{ properties
	// {{{ public

	/**
	 * Command statistics
	 *
	 * @var	  array
	 * @access  public
	 */
	var $stats;

	// }}}
	// {{{ private

	/**
	 * Cached Sockets that are connected
	 *
	 * @var	  array
	 * @access  private
	 */
	var $_cache_sock;

	/**
	 * Current debug status; 0 - none to 9 - profiling
	 *
	 * @var	  boolean
	 * @access  private
	 */
	var $_debug;

	/**
	 * Dead hosts, assoc array, 'host'=>'unixtime when ok to check again'
	 *
	 * @var	  array
	 * @access  private
	 */
	var $_host_dead;

	/**
	 * Is compression available?
	 *
	 * @var	  boolean
	 * @access  private
	 */
	var $_have_zlib;

	/**
	 * Do we want to use compression?
	 *
	 * @var	  boolean
	 * @access  private
	 */
	var $_compress_enable;

	/**
	 * At how many bytes should we compress?
	 *
	 * @var	  interger
	 * @access  private
	 */
	var $_compress_threshold;

	/**
	 * Are we using persistant links?
	 *
	 * @var	  boolean
	 * @access  private
	 */
	var $_persistant;

	/**
	 * If only using one server; contains ip:port to connect to
	 *
	 * @var	  string
	 * @access  private
	 */
	var $_single_sock;

	/**
	 * Array containing ip:port or array(ip:port, weight)
	 *
	 * @var	  array
	 * @access  private
	 */
	var $_servers;

	/**
	 * Our bit buckets
	 *
	 * @var	  array
	 * @access  private
	 */
	var $_buckets;

	/**
	 * Total # of bit buckets we have
	 *
	 * @var	  interger
	 * @access  private
	 */
	var $_bucketcount;

	/**
	 * # of total servers we have
	 *
	 * @var	  interger
	 * @access  private
	 */
	var $_active;

	// }}}
	// }}}
	// {{{ methods
	// {{{ public functions
	// {{{ memcached()

	/**
	 * Memcache initializer
	 *
	 * @param	array	 $args	 Associative array of settings
	 *
	 * @return  mixed
	 * @access  public
	 */
	function memcached ($args){
		$this->set_servers($args['servers']);
		$this->_debug = (isset($args['debug']) ? $args['debug'] : 0 );
		$this->stats = array(	'add' => 0,
								'set' => 0,
								'replace' => 0,
								'get' => 0,
								'get_multi' => 0,
								'get_stats' => 0,
								'delete' => 0,
								'incr' => 0,
								'decr' => 0
								);
		$this->_compress_threshold = (isset($args['compress_threshold']) ? $args['compress_threshold'] : 10240 );
		$this->_persistant = isset($args['persistant']) ? $args['persistant'] : false;
		$this->_compress_enable = $args['compress_threshold'] > 0;
		$this->_have_zlib = function_exists("gzcompress") && function_exists("gzuncompress");

		$this->_cache_sock = array();
		$this->_host_dead = array();
		register_shutdown_function(array(&$this, "disconnect_all"));
	}

	// }}}
	// {{{ add()

	/**
	 * Adds a key/value to the memcache server if one isn't already set with
	 * that key
	 *
	 * @param	string	$key	  Key to set with data
	 * @param	mixed	 $val	  Value to store
	 * @param	interger $exp	  (optional) Time to expire data at
	 *
	 * @return  boolean
	 * @access  public
	 */
	function add ($key, $val, $exp = 0){
		return $this->_set('add', $key, $val, $exp);
	}

	// }}}
	// {{{ decr()

	/**
	 * Decriment a value stored on the memcache server
	 *
	 * @param	string	$key	  Key to decriment
	 * @param	interger $amt	  (optional) Amount to decriment
	 *
	 * @return  mixed	 FALSE on failure, value on success
	 * @access  public
	 */
	function decr ($key, $amt=1){
		return $this->_incrdecr('decr', $key, $amt);
	}

	// }}}
	// {{{ delete()

	/**
	 * Deletes a key from the server, optionally after $time
	 *
	 * @param	string	$key	  Key to delete
	 * @param	interger $time	 (optional) How long to wait before deleting
	 *
	 * @return  boolean  TRUE on success, FALSE on failure
	 * @access  public
	 */
	function delete ($key, $time = 0){
		if (!$this->_active)
			return false;

		$sock = $this->get_sock($key);
		if (!is_resource($sock))
			return false;

		$key = is_array($key) ? $key[1] : $key;

		$this->stats['delete']++;
		$cmd = "delete $key $time\r\n";
		if(!fwrite($sock, $cmd, $this->_byte_count($cmd)))	{
			$this->_dead_sock($sock);
			return false;
		}
		$res = trim(fgets($sock));

		if ($this->_debug)
			printf("MemCache: delete %s (%s)\n", $key, $res);

		if ($res == "DELETED")
			return true;
		return false;
	}

	// }}}
	// {{{ disconnect_all()

	/**
	 * Disconnects all connected sockets
	 *
	 * @access  public
	 */
	function disconnect_all (){
		foreach ($this->_cache_sock as $sock)
			fclose($sock);

		$this->_cache_sock = array();
	}

	// }}}
	// {{{ enable_compress()

	/**
	 * Enable / Disable compression
	 *
	 * @param	boolean  $enable  TRUE to enable, FALSE to disable
	 *
	 * @access  public
	 */
	function enable_compress ($enable){
		$this->_compress_enable = $enable;
	}

	// }}}


	function flush($host = false){
		if($host){
			$sock = $this->sock_to_host($host);
			if (!is_resource($sock))
				return false;

			$cmd = "flush_all\r\n";
			if(!fwrite($sock, $cmd, $this->_byte_count($cmd)))	{
				$this->_dead_sock($sock);
				return false;
			}
			$res = trim(fgets($sock));

			if ($this->_debug)
				printf("MemCache: flush_all %s\n", $host);

			if ($res == "OK")
				return true;
			return false;
		}else{
			$ret = true;

			foreach ($this->_servers as $host)
				$ret &= $this->flush($host);

			return $ret;
		}
	}



	// {{{ forget_dead_hosts()

	/**
	 * Forget about all of the dead hosts
	 *
	 * @access  public
	 */
	function forget_dead_hosts (){
		$this->_host_dead = array();
	}

	// }}}
	// {{{ get()

	/**
	 * Retrieves the value associated with the key from the memcache server
	 *
	 * @param  string	$key	  Key to retrieve
	 *
	 * @return  mixed
	 * @access  public
	 */
	function get($key){
		if (!$this->_active)
			return false;

		$sock = $this->get_sock($key);

		if (!is_resource($sock))
			return false;

		$this->stats['get']++;

		$key = is_array($key) ? $key[1] : $key;

		$cmd = "get $key\r\n";
		if (!fwrite($sock, $cmd, $this->_byte_count($cmd))) {
			$this->_dead_sock($sock);
			return false;
		}

		$val = array();
		$this->_load_items($sock, $val);

		if($this->_debug)
			foreach($val as $k => $v)
				printf("MemCache: sock %s got %s => %s\r\n", $sock, $k, $v);

		if(isset($val[$key]))
			return $val[$key];
		else
			return false;
	}

	// }}}
	// {{{ get_multi()

	/**
	 * Get multiple keys from the server(s)
	 *
	 * @param	array	 $keys	 Keys to retrieve
	 *
	 * @return  array
	 * @access  public
	 */
	function get_multi ($keys) {
		if (!$this->_active)
			return false;

		if(!is_array($keys))
			$keys = array($keys);

		$this->stats['get_multi']++;

		foreach ($keys as $key) {
			$sock = $this->get_sock($key);
			if (!is_resource($sock)) continue;
			$key = is_array($key) ? $key[1] : $key;
			if (!isset($sock_keys[$sock])) {
				$sock_keys[$sock] = array();
				$socks[] = $sock;
			}
			$sock_keys[$sock][] = $key;
		}

		// Send out the requests
		foreach ($socks as $sock) {
			$cmd = "get";
			foreach ($sock_keys[$sock] as $key)
				$cmd .= " ". $key;
			$cmd .= "\r\n";

			if (fwrite($sock, $cmd, $this->_byte_count($cmd)))
				$gather[] = $sock;
			else
				$this->_dead_sock($sock);
		}

		// Parse responses
		$val = array();
		foreach ($gather as $sock)
			$this->_load_items($sock, $val);

		if ($this->_debug)
			foreach ($val as $k => $v)
				printf("MemCache: got %s => %s\r\n", $k, $v);

		return $val;
	}

	// }}}


	function get_stats(){
		if (!$this->_active)
			return false;

		$this->stats['get_stats']++;

		$val = array();

		foreach($this->_servers as $server) {
			$sock = $this->sock_to_host($server);

			if (!is_resource($sock)){
				$val[$server] = array();
			}else{
				$cmd = "stats\r\n";
				if(fwrite($sock, $cmd, $this->_byte_count($cmd))){
					$this->_load_items($sock, $val[$server]);
				}else{
					$this->_dead_sock($sock);
				}
			}
		}

		return $val;
	}



	// {{{ incr()

	/**
	 * Increments $key (optionally) by $amt
	 *
	 * @param	string	$key	  Key to increment
	 * @param	interger $amt	  (optional) amount to increment
	 *
	 * @return  interger New key value?
	 * @access  public
	 */
	function incr ($key, $amt=1) {
		return $this->_incrdecr('incr', $key, $amt);
	}

	// }}}
	// {{{ replace()

	/**
	 * Overwrites an existing value for key; only works if key is already set
	 *
	 * @param	string	$key	  Key to set value as
	 * @param	mixed	 $value	Value to store
	 * @param	interger $exp	  (optional) Experiation time
	 *
	 * @return  boolean
	 * @access  public
	 */
	function replace ($key, $value, $exp=0)	{
		return $this->_set('replace', $key, $value, $exp);
	}

	// }}}
	// {{{ run_command()

	/**
	 * Passes through $cmd to the memcache server connected by $sock; returns
	 * output as an array (null array if no output)
	 *
	 * NOTE: due to a possible bug in how PHP reads while using fgets(), each
	 *		 line may not be terminated by a \r\n.  More specifically, my testing
	 *		 has shown that, on FreeBSD at least, each line is terminated only
	 *		 with a \n.  This is with the PHP flag auto_detect_line_endings set
	 *		 to false (the default).
	 *
	 * @param	resource $sock	 Socket to send command on
	 * @param	string	$cmd	  Command to run
	 *
	 * @return  array	 Output array
	 * @access  public
	 */
	function run_command($sock, $cmd){
		if (!is_resource($sock))
			return array();

		if (!fwrite($sock, $cmd, $this->_byte_count($cmd)))
			return array();

		while (true){
			$res = fgets($sock);
			$ret[] = $res;
			if (preg_match('/^END/', $res))
				break;
			if ($this->_byte_count($res) == 0)
				break;
		}
		return $ret;
	}

	// }}}
	// {{{ set()

	/**
	 * Unconditionally sets a key to a given value in the memcache.  Returns true
	 * if set successfully.
	 *
	 * @param	string	$key	  Key to set value as
	 * @param	mixed	 $value	Value to set
	 * @param	interger $exp	  (optional) Experiation time
	 *
	 * @return  boolean  TRUE on success
	 * @access  public
	 */
	function set($key, $value, $exp=0)	{
		return $this->_set('set', $key, $value, $exp);
	}

	// }}}
	// {{{ set_compress_threshold()

	/**
	 * Sets the compression threshold
	 *
	 * @param	interger $thresh  Threshold to compress if larger than
	 *
	 * @access  public
	 */
	function set_compress_threshold($thresh)	{
		$this->_compress_threshold = $thresh;
	}

	// }}}
	// {{{ set_debug()

	/**
	 * Sets the debug flag
	 *
	 * @param	boolean  $dbg	  TRUE for debugging, FALSE otherwise
	 *
	 * @access  public
	 *
	 * @see	  memcahced::memcached
	 */
	function set_debug ($dbg)	{
		$this->_debug = $dbg;
	}

	// }}}
	// {{{ set_servers()

	/**
	 * Sets the server list to distribute key gets and puts between
	 *
	 * @param	array	 $list	 Array of servers to connect to
	 *
	 * @access  public
	 *
	 * @see	  memcached::memcached()
	 */
	function set_servers($list){
		$this->_servers = $list;
		$this->_active = count($list);
		$this->_buckets = null;
		$this->_bucketcount = 0;

		$this->_single_sock = null;
		if ($this->_active == 1)
			$this->_single_sock = $this->_servers[0];
	}

	// }}}
	// }}}
	// {{{ private methods
	// {{{ _close_sock()

	/**
	 * Close the specified socket
	 *
	 * @param	string	$sock	 Socket to close
	 *
	 * @access  private
	 */
	function _close_sock ($sock) {
		$host = array_search($sock, $this->_cache_sock);
		fclose($this->_cache_sock[$host]);
		unset($this->_cache_sock[$host]);
	}

	// }}}
	// {{{ _connect_sock()

	/**
	 * Connects $sock to $host, timing out after $timeout
	 *
	 * @param	interger $sock	 Socket to connect
	 * @param	string	$host	 Host:IP to connect to
	 * @param	float	 $timeout (optional) Timeout value, defaults to 0.25s
	 *
	 * @return  boolean
	 * @access  private
	 */
	function _connect_sock(&$sock, $host, $timeout = 0.25)	{
		list ($ip, $port) = explode(":", $host);
		if ($this->_persistant == 1){
			$sock = @pfsockopen($ip, $port, $errno, $errstr, $timeout);
		} else	{
			$sock = @fsockopen($ip, $port, $errno, $errstr, $timeout);
		}

		if (!$sock)
			return false;
		return true;
	}

	// }}}
	// {{{ _dead_sock()

	/**
	 * Marks a host as dead until 30-40 seconds in the future
	 *
	 * @param	string	$sock	 Socket to mark as dead
	 *
	 * @access  private
	 */
	function _dead_sock($sock){
		if(is_resource($sock))
			$host = array_search($sock, $this->_cache_sock);
		elseif(is_string($sock))
			$host = $sock;
		else{
			trigger_error("Bad Sock", E_USER_WARNING);
			return;
		}

		list ($ip, $port) = explode(":", $host);
		$this->_host_dead[$ip] = time() + 30 + intval(rand(0, 10));
		$this->_host_dead[$host] = $this->_host_dead[$ip];
		unset($this->_cache_sock[$host]);
	}

	// }}}
	// {{{ get_sock()

	/**
	 * get_sock
	 *
	 * @param	string	$key	  Key to retrieve value for;
	 *
	 * @return  mixed	 resource on success, false on failure
	 * @access  private
	 */
	function & get_sock($key){
		if(!$this->_active)
			return false;

		if($this->_single_sock !== null)
			return $this->sock_to_host($this->_single_sock);

		$hash = is_array($key) ? abs(intval($key[0])) : $this->_hashfunc($key);

		if($this->_buckets === null){
			$bu = array();
			foreach ($this->_servers as $v)	{
				if (is_array($v)){
					for ($i=0; $i<$v[1]; $i++)
						$bu[] = $v[0];
				} else {
					$bu[] = $v;
				}
			}
			$this->_buckets = $bu;
			$this->_bucketcount = count($bu);
		}

		$realkey = is_array($key) ? $key[1] : $key;
		for ($tries = 0; $tries<20; $tries++){
			$host = $this->_buckets[$hash % $this->_bucketcount];
			$sock = $this->sock_to_host($host);
			if (is_resource($sock))
				return $sock;
			$hash = $this->_hashfunc($tries . $realkey);
		}

		return false;
	}

	// }}}
	// {{{ _hashfunc()

	/**
	 * Creates a hash interger based on the $key
	 *
	 * @param	string	$key	  Key to hash
	 *
	 * @return  interger Hash value
	 * @access  private
	 */
	function _hashfunc($key){
		$len = $this->_byte_count($key);
		$hash = 0;
		for ($i=0; $i<$len; $i++)
			$hash ^= ($i+1)*ord($key[$i]);

		return $hash;
	}

	// }}}
	// {{{ _incrdecr()

	/**
	 * Perform increment/decriment on $key
	 *
	 * @param	string	$cmd	  Command to perform
	 * @param	string	$key	  Key to perform it on
	 * @param	interger $amt	  Amount to adjust
	 *
	 * @return  interger	 New value of $key
	 * @access  private
	 */
	function _incrdecr($cmd, $key, $amt=1)
	{
		if (!$this->_active)
			return null;

		$sock = $this->get_sock($key);
		if (!is_resource($sock))
			return null;

		$key = is_array($key) ? $key[1] : $key;
		$this->stats[$cmd]++;
		if (!fwrite($sock, "$cmd $key $amt\r\n"))
			return $this->_dead_sock($sock);

		stream_set_timeout($sock, 1, 0);
		$line = fgets($sock);
		if (!preg_match('/^(\d+)/', $line, $match))
			return null;
		return $match[1];
	}

	// }}}
	// {{{ _load_items()

	/**
	 * Load items into $ret from $sock
	 *
	 * @param	resource $sock	 Socket to read from
	 * @param	array	 $ret	  Returned values
	 *
	 * @access  private
	 */
	function _load_items($sock, &$ret){
		while (1){
			$decl = fgets($sock);
			if($decl == "END\r\n"){
				return true;
			}elseif(preg_match('/^VALUE (\S+) (\d+) (\d+)\r\n$/', $decl, $match)){
				list($rkey, $flags, $len) = array($match[1], $match[2], $match[3]);
				$bneed = $len+2;
				$offset = 0;

				$ret[$rkey] = "";
				while ($bneed > 0) {
					$data = fread($sock, $bneed);
					$n = $this->_byte_count($data);
					if ($n == 0)
						break;
					$offset += $n;
					$bneed -= $n;
					$ret[$rkey] .= $data;
				}

				if($offset != $len+2){
					// Something is borked!
					trigger_error("Something is borked! key $rkey expecting " . ($len+2) . " got $offset length from host " . array_search($sock, $this->_cache_sock), E_USER_NOTICE);

					unset($ret[$rkey]);
					$this->_close_sock($sock);
					return false;
				}

				$ret[$rkey] = rtrim($ret[$rkey]);

				if($this->_have_zlib && ($flags & MEMCACHE_COMPRESS))
					$ret[$rkey] = gzuncompress($ret[$rkey]);

				if($flags & MEMCACHE_SERIALIZED)
					$ret[$rkey] = unserialize($ret[$rkey]);

			}elseif(preg_match('/^STAT (\S+) (\S+)\r\n$/', $decl, $match)){
				$ret[$match[1]] = rtrim($match[2]);
			}else{
				trigger_error("Error parsing memcached response from " . array_search($sock, $this->_cache_sock) . ": $decl", E_USER_ERROR);
				return 0;
			}
		}
	}

	// }}}
	// {{{ _set()

	/**
	 * Performs the requested storage operation to the memcache server
	 *
	 * @param	string	$cmd	  Command to perform
	 * @param	string	$key	  Key to act on
	 * @param	mixed	 $val	  What we need to store
	 * @param	interger $exp	  When it should expire
	 *
	 * @return  boolean
	 * @access  private
	 */
	function _set($cmd, $key, $val, $exp){
		if (!$this->_active)
			return false;

		$sock = $this->get_sock($key);
		if (!is_resource($sock))
			return false;

		$this->stats[$cmd]++;

		$key = is_array($key) ? $key[1] : $key;

		$flags = 0;

		if(!is_scalar($val)){
			$val = serialize($val);
			$flags |= MEMCACHE_SERIALIZED;
			if($this->_debug)
				trigger_error("client: serializing data as it is not scalar", E_USER_NOTICE);
		}

		$len = $this->_byte_count($val);

		if ($this->_have_zlib && $this->_compress_enable &&
			$this->_compress_threshold && $len >= $this->_compress_threshold){
			$c_val = gzcompress($val, 9);
			$c_len = $this->_byte_count($c_val);

			if($c_len < $len*(1 - COMPRESS_SAVINGS)){
				if($this->_debug)
					trigger_error("client: compressing data; was $len bytes is now $c_len bytes", E_USER_NOTICE);
				$val = $c_val;
				$len = $c_len;
				$flags |= MEMCACHE_COMPRESS;
			}
		}
		if (!fwrite($sock, "$cmd $key $flags $exp $len\r\n$val\r\n"))
			return $this->_dead_sock($sock);

		$line = trim(fgets($sock));

		if ($this->_debug){
			if ($flags & MEMCACHE_COMPRESS)
				$val = 'compressed data';

			trigger_error("MemCache: $cmd $key => $val ($line)", E_USER_NOTICE);
		}
		if ($line == "STORED")
			return true;
		return false;
	}

	// }}}
	// {{{ sock_to_host()

	/**
	 * Returns the socket for the host
	 *
	 * @param	string	$host	 Host:IP to get socket for
	 *
	 * @return  mixed	 IO Stream or false
	 * @access  private
	 */
	function & sock_to_host($host){
		if (isset($this->_cache_sock[$host]))
			return $this->_cache_sock[$host];

		$now = time();
		list ($ip, $port) = explode (":", $host);
		if (isset($this->_host_dead[$host]) && $this->_host_dead[$host] > $now ||
			isset($this->_host_dead[$ip]) && $this->_host_dead[$ip] > $now)
			return null;

		if (!$this->_connect_sock($sock, $host))
			return $this->_dead_sock($host);

		// Do not buffer writes
		stream_set_write_buffer($sock, 0);

		$this->_cache_sock[$host] = $sock;

		return $this->_cache_sock[$host];
	}

	// }}}
	// }}}
	// }}}

	function _byte_count($val){
		return (function_exists('mb_strlen')) ? mb_strlen($val, 'latin1') :	strlen($val);
	}

}

// }}}
