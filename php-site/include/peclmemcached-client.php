<?php
//
// +---------------------------------------------------------------------------+
// | memcached client, PHP                                                     |
// +---------------------------------------------------------------------------+
// | Copyright (c) 2004 Timo Ewalds <timo@nexopia.com>                         |
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
// | Author: Timo Ewalds <timo@nexopia.com>                                    |
// | Heavily influenced by the PHP memcached client by Ryan T. Dean            |
// +---------------------------------------------------------------------------+
//
//

/**
 * This is a PHP client for memcached - a distributed memory cache daemon.
 * More information is available at http://www.danga.com/memcached/
 *
 * Usage example:
 *
 * require_once 'peclmemcached-client.php';
 *
 * $mc = new peclmemcached(array(
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
 * @author  Timo Ewalds <timo@nexopia.com>
 * @package peclmemcached-client
 */

// {{{ requirements
// }}}

// {{{ constants
// {{{ flags


/**
 * Minimum savings to store data compressed
 */
define("COMPRESS_SAVINGS", 0.20);

// }}}

class peclmemcached{

	/**
	 * Command statistics
	 *
	 * @var	  array
	 * @access  public
	 */
	public $stats;


	/**
	 * Cached Hosts that are connected
	 *
	 * @var	  array
	 * @access  private
	 */
	public $_cache_host;

	/**
	 * Current debug status; 0 - none to 9 - profiling
	 *
	 * @var	  boolean
	 * @access  private
	 */
	public $_debug;

	/**
	 * Dead hosts, assoc array, 'host'=>'unixtime when ok to check again'
	 *
	 * @var	  array
	 * @access  private
	 */
	public $_host_dead;

	/**
	 * Is compression available?
	 *
	 * @var	  boolean
	 * @access  private
	 */
	public $_have_zlib;

	/**
	 * Do we want to use compression?
	 *
	 * @var	  boolean
	 * @access  private
	 */
	public $_compress_enable;

	/**
	 * At how many bytes should we compress?
	 *
	 * @var	  interger
	 * @access  private
	 */
	public $_compress_threshold;

	/**
	 * Are we using persistant links?
	 *
	 * @var	  boolean
	 * @access  private
	 */
	public $_persistant;

	/**
	 * If only using one server; contains ip:port to connect to
	 *
	 * @var	  string
	 * @access  private
	 */
	public $_single_host;

	/**
	 * Array containing ip:port or array(ip:port, weight)
	 *
	 * @var	  array
	 * @access  private
	 */
	public $_servers;

	/**
	 * Our bit buckets
	 *
	 * @var	  array
	 * @access  private
	 */
	public $_buckets;

	/**
	 * Total # of bit buckets we have
	 *
	 * @var	  interger
	 * @access  private
	 */
	public $_bucketcount;

	/**
	 * # of total servers we have
	 *
	 * @var	  interger
	 * @access  private
	 */
	public $_active;


	/**
	 * Memcache initializer
	 *
	 * @param	array	 $args	 Associative array of settings
	 *
	 * @return  mixed
	 * @access  public
	 */
	function __construct($args){
		if(!extension_loaded('memcache'))
			if(!dl("memcache.so"))
				die("Failed to load memcache extension");

		$this->set_servers($args['servers']);
		$this->_debug = $args['debug'];
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
		$this->_compress_threshold = $args['compress_threshold'];
		$this->_persistant = isset($args['persistant']) ? $args['persistant'] : false;
		$this->_compress_enable = $args['compress_threshold'] > 0;

		$this->_cache_host = array();
		$this->_host_dead = array();
		if(!$this->_persistant)
			register_shutdown_function(array(&$this, "disconnect_all"));
	}


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
	function add($key, $value, $exp = 0){
		if (!$this->_active)
			return false;

		$host = $this->_get_host($key);
		if(!$host)
			return false;

		$key = is_array($key) ? $key[1] : $key;

		$this->stats['add']++;

		return $this->_cache_host[$host]->add($key, $value, $this->_compress($value), $exp);
	}


	/**
	 * Decriment a value stored on the memcache server
	 *
	 * @param	string	$key	  Key to decriment
	 * @param	interger $amt	  (optional) Amount to decriment
	 *
	 * @return  mixed	 FALSE on failure, value on success
	 * @access  public
	 */
	function decr($key, $amt=1){
		if (!$this->_active)
			return false;

		$host = $this->_get_host($key);
		if (!$host)
			return false;

		$key = is_array($key) ? $key[1] : $key;

		$this->stats['incr']++;

		return $this->_cache_host[$host]->decrement($key, $amt);
	}


	/**
	 * Deletes a key from the server, optionally after $time
	 *
	 * @param	string	$key	  Key to delete
	 * @param	interger $time	 (optional) How long to wait before deleting
	 *
	 * @return  boolean  TRUE on success, FALSE on failure
	 * @access  public
	 */
	function delete($key, $time = 0){
		if (!$this->_active)
			return false;

		$host = $this->_get_host($key);
		if (!$host)
			return false;

		$key = is_array($key) ? $key[1] : $key;

		$this->stats['delete']++;

		return $this->_cache_host[$host]->delete($key, $time);
	}

	/**
	 * Disconnects all connected hosts
	 *
	 * @access  public
	 */
	function disconnect_all (){
		foreach ($this->_cache_host as $host => $v)
			$this->_cache_host[$host]->close();

		$this->_cache_host = array();
	}


	/**
	 * Enable / Disable compression
	 *
	 * @param	boolean  $enable  TRUE to enable, FALSE to disable
	 *
	 * @access  public
	 */
	function enable_compress($enable){
		$this->_compress_enable = $enable;
	}


	/**
	 * Forget about all of the dead hosts
	 *
	 * @access  public
	 */
	function forget_dead_hosts(){
		$this->_host_dead = array();
	}


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

		$host = $this->_get_host($key);

		if (!$host)
			return false;

		$key = is_array($key) ? $key[1] : $key;

		$this->stats['get']++;

		return $this->_cache_host[$host]->get($key);
	}


	/**
	 * Get multiple keys from the server(s)
	 *
	 * @param	array	 $keys	 Keys to retrieve
	 *
	 * @return  array
	 * @access  public
	 */
	function get_multi($keys){
		if (!$this->_active)
			return false;

		if(!is_array($keys))
			$keys = array($keys);

		$this->stats['get_multi']++;

		$host_keys = array();
		$hosts = array();
		foreach ($keys as $key) {
			$host = $this->_get_host($key);
			if (!$host) continue;
			$key = is_array($key) ? $key[1] : $key;
			if (!isset($host_keys[$host])) {
				$host_keys[$host] = array();
				$hosts[] = $host;
			}
			$host_keys[$host][] = $key;
		}

		$val = array();
		if(count($hosts))
			foreach ($hosts as $host)
				$val += $this->_cache_host[$host]->get($host_keys[$host]);

		if ($this->_debug)
			foreach ($val as $k => $v)
				printf("MemCache: got %s => %s\r\n", $k, $v);

		return $val;
	}

	function get_stats(){
		if (!$this->_active)
			return false;

		$this->stats['get_stats']++;

		$val = array();

		foreach($this->_servers as $server) {
			$host = $this->_prepare_host($server);

			if(!$host)
				$val[$server] = array();
			else
				$val[$server] = $this->_cache_host[$host]->getStats();
		}

		return $val;
	}

	/**
	 * Increments $key (optionally) by $amt
	 *
	 * @param	string	$key	  Key to increment
	 * @param	interger $amt	  (optional) amount to increment
	 *
	 * @return  integer New key value?
	 * @access  public
	 */
	function incr($key, $amt=1) {
		if (!$this->_active)
			return false;

		$host = $this->_get_host($key);
		if(!$host)
			return false;

		$key = is_array($key) ? $key[1] : $key;

		$this->stats['incr']++;

		return $this->_cache_host[$host]->increment($key, $amt);
	}


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
	function replace($key, $value, $exp=0)	{
		if(!$this->_active)
			return false;

		$host = $this->_get_host($key);
		if(!$host)
			return false;

		$key = is_array($key) ? $key[1] : $key;

		$this->stats['replace']++;

		return $this->_cache_host[$host]->replace($key, $value, $this->_compress($value), $exp);
	}


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
	function set($key, $value, $exp=0){
		if (!$this->_active)
			return false;

		$host = $this->_get_host($key);
		if(!$host)
			return false;

		$key = is_array($key) ? $key[1] : $key;

		$this->stats['set']++;

		return $this->_cache_host[$host]->set($key, $value, $this->_compress($value), $exp);
	}


	/**
	 * Sets the compression threshold
	 *
	 * @param	interger $thresh  Threshold to compress if larger than
	 *
	 * @access  public
	 */
	function set_compress_threshold($thresh){
		$this->_compress_threshold = $thresh;
	}


	/**
	 * Sets the debug flag
	 *
	 * @param	boolean  $dbg	  TRUE for debugging, FALSE otherwise
	 *
	 * @access  public
	 *
	 * @see	  memcahced::memcached
	 */
	function set_debug($dbg){
		$this->_debug = $dbg;
	}


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

		$this->_single_host = null;
		if ($this->_active == 1)
			$this->_single_host = $this->_servers[0];
	}


	/**
	 * Connects $host, timing out after $timeout
	 *
	 * @param	string	$host	 Host:IP to connect to
	 * @param	float	 $timeout (optional) Timeout value, defaults to 0.25s
	 *
	 * @return  boolean
	 * @access  private
	 */
	function _connect_host($host, $timeout = 0){
		$this->_cache_host[$host] = new Memcache;

		list($ip, $port) = explode(":", $host);
		if($this->_persistant)
			$ret = @$this->_cache_host[$host]->pconnect($ip, $port);//, $timeout);
		else
			$ret = @$this->_cache_host[$host]->connect($ip, $port);//, $timeout);

		return $ret;
	}


	/**
	 * Marks a host as dead until 30-40 seconds in the future
	 *
	 * @param	string	$host	 Host to mark as dead
	 *
	 * @access  private
	 */
	function _dead_host($host){
		list($ip, $port) = explode(":", $host);
		$this->_host_dead[$host] = $this->_host_dead[$ip] = time() + 30 + intval(rand(0, 10));
		unset($this->_cache_host[$host]);
	}


	/**
	 * _get_host
	 *
	 * @param	string	$key	  Key to retrieve value for;
	 *
	 * @return  mixed	 host on success, false on failure
	 * @access  private
	 */
	function _get_host($key){
		if (!$this->_active)
			return false;

		if ($this->_single_host !== null)
			return $this->_prepare_host($this->_single_host);

		$hash = is_array($key) ? abs(intval($key[0])) : $this->_hashfunc($key);

		if ($this->_buckets === null){
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
		for($tries = 0; $tries<20; $tries++){
			$host = $this->_buckets[$hash % $this->_bucketcount];
			$host = $this->_prepare_host($host);
			if($host)
				return $host;
			$hash = $this->_hashfunc($tries . $realkey);
		}

		return false;
	}


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

	function _compress($val){
		if($this->_have_zlib && $this->_compress_enable && $this->_compress_threshold){
			if(!is_scalar($val))
				$val = serialize($val);

			$len = $this->_byte_count($val);

			if($len >= $this->_compress_threshold){
				$c_val = gzcompress($val, 9);
				$c_len = $this->_byte_count($c_val);

				if ($c_len < $len*(1 - COMPRESS_SAVINGS))
					return MEMCACHE_COMPRESSED;
			}
		}
		return 0;
	}


	/**
	 * Returns the host if the host is up, makes sure the host is either connected or dead
	 *
	 * @param	string	$host	 Host:IP to get host for
	 *
	 * @return  mixed	 host or null
	 * @access  private
	 */
	function _prepare_host($host){
		if(isset($this->_cache_host[$host]))
			return $host;

		$now = time();
		list ($ip, $port) = explode (":", $host);
		if (isset($this->_host_dead[$host]) && $this->_host_dead[$host] > $now ||
			isset($this->_host_dead[$ip]) && $this->_host_dead[$ip] > $now)
			return null;

		if(!$this->_connect_host($host))
			return $this->_dead_host($host);

		return $host;
	}

	function _byte_count($val){
		return (function_exists('mb_strlen')) ? mb_strlen($val, 'latin1') :	strlen($val);
	}
}

