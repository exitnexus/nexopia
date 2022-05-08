<?

//wrapper class for the pecl memcache 2 client library. Makes it interface compatible with memcached-client

class memcached {

	public $stats;
	public $memcache;

	public $name;
	public $persistant;
	public $compress_threshold;

	function __construct($args){
		$this->name = $args['name'];
		$this->persistant = isset($args['persistant']) ? $args['persistant'] : false;

		$this->compress_threshold = (isset($args['compress_threshold']) ? $args['compress_threshold'] : 8000 ); //if it won't fit in a 8kb slot, compress, use 8000 instead of 8192 due to the key and flags

		$mc = new Memcache;

		foreach($args['servers'] as $server){
			$weight = 1;
			if(is_array($server))
				list($server, $weight) = $server;

			list($ip, $port) = explode(':', $server);
			$mc->addServer($ip, $port, $this->persistant, $weight);
		}

		if($this->compress_threshold)
			$mc->setCompressThreshold($this->compress_threshold);

		$this->memcache = $mc;


		$this->stats = array(	'add' => 0,
								'set' => 0,
								'append' => 0,
								'replace' => 0,
								'get' => 0,
								'get_multi' => 0,
								'get_stats' => 0,
								'delete' => 0,
								'incr' => 0,
								'decr' => 0
								);
	}

	function add($key, $val, $exp = 0){
		$this->stats['add']++;
		return $this->memcache->add($key, $val, MEMCACHE_COMPRESSED, $exp);
	}

	function append($key, $val, $exp = 0){
		die("append not implemented");
	}

	function decr($key, $amt=1){
		$this->stats['decr']++;
		return $this->memcache->decrement($key, $amt);
	}

	function delete($key, $time = 0){
		$this->stats['delete']++;
		return $this->memcache->delete($key, $time);
	}

	function disconnect_all(){
		$this->memcache->close();
	}

	function enable_compress($enable){
	}

	function flush(){
		$this->memcache->flush();
	}

	function get($key){
		$this->stats['get']++;

		if(is_array($key)) // this version doesn't support passing the hash
			$key = $key[1];

		return $this->memcache->get($key);
	}

	function get_multi($keys){
		$this->stats['get_multi']++;

		foreach($keys as & $v)
			if(is_array($v))
				$v = $v[1];

		return $this->memcache->get($keys);
	}

	function get_stats(){
		$this->stats['get_stats']++;

		return $this->memcache->getStats();
	}

	function incr($key, $amt=1){
		$this->stats['incr']++;
		return $this->memcache->increment($key, $amt);
	}

	function replace($key, $value, $exp=0){
		$this->stats['replace']++;
		return $this->memcache->replace($key, $value, MEMCACHE_COMPRESSED, $exp);
	}

	function set($key, $value, $exp=0){
		$this->stats['set']++;
		return $this->memcache->set($key, $value, MEMCACHE_COMPRESSED, $exp);
	}

	function set_compress_threshold($thresh){
//		$this->compress_threshold = $thresh;
	}
}
