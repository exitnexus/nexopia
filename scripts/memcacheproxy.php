#!/usr/local/php/bin/php
<?

$port = 11212;
$memcacheservers = array( 'localhost:11211' );
$debug = false;

////////////////////////

set_time_limit (0);

$proxy = new memproxy($port, $memcacheservers);
$proxy->run();

////////////////////////

function myerror($error, $line, $die = false) {
	echo "[error] on line $line: \"$error\", " . socket_strerror(socket_last_error()) . "\n";

	if($die)
		exit;
}

function debug($output){
	global $debug;
	if($debug)
		echo "$output\n";
}


function hashfunc($key){
	$len = strlen($key);
	$hash = 0;
	
	$i = 0;
	while($i < $len)
		$hash ^= ord($key[$i])*(++$i);

	return $hash;
}

class memproxy {
	public $socket; //listen socket

	public $clients = array();     // array( $sock => memclient, ...)
	public $clientsocks = array(); // array( $sock => $sock, ...)
	
	public $servers = array();     // array( $id => memserver, ...)
	public $serverids = array();   // array( $sock => $id, ...)
	public $serversocks = array(); // array( $sock => $sock, ...)

	public $memcacheservers = array();
	public $port;

	function __construct($port, $memcacheservers){

		$this->port = $port;
		$this->memcacheservers = $memcacheservers;

		$this->clients = array();     // array( $sock => memclient, ...)
		$this->clientsocks = array(); // array( $sock => $sock, ...)
		
		$this->servers = array();     // array( $id => memserver, ...)
		$this->serverids = array();   // array( $sock => $id, ...)
		$this->serversocks = array(); // array( $sock => $sock, ...)


		if(!($this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP))) myerror("socket_create failed", __LINE__, true);
		if(!socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1))  myerror("set option failed", __LINE__, true);
		if(!socket_set_nonblock($this->socket))                             myerror("non block failed", __LINE__, true);
		if(!socket_bind($this->socket, "0.0.0.0", $this->port))             myerror("bind failed", __LINE__, true);
		if(!socket_listen($this->socket, 16))                               myerror("listen failed", __LINE__, true);
		
		//init the servers
		foreach($this->memcacheservers as $serverid => $host){
			if(!is_array($host))
				$host = explode(":", $host);
			if(count($host) == 1)
				$host[] = 11211;

			$this->servers[$serverid] = new memserver($host[0], $host[1], $serverid);
			$this->serverids[(int)$this->servers[$serverid]->sock] = $serverid;
			$this->serversocks[(int)$this->servers[$serverid]->sock] = $this->servers[$serverid]->sock;
		}
	}
	
	function run(){
		while(1){
			debug("Tick");
		
			$socks = array_merge(array_values($this->clientsocks), array_values($this->serversocks), array($this->socket));
			$w = NULL;
			$e = NULL;

			if(socket_select($socks, $w, $e, 0, 10000) !== false){

				foreach($socks as $sock){
				// listening socket has a connection, deal with it
					if($sock == $this->socket){
						$newsock = socket_accept($sock);
		
						// add socket to client list
						if($newsock){
							socket_getpeername($newsock, $addr);
							debug("[connection]: $addr");
		
							$this->clientsocks[(int)$newsock] = $newsock;
							$this->clients[(int)$newsock] = new memclient($newsock);
						}else{
							myerror("connection failed", __LINE__, false);
						}
		
				// client socket has incoming data
					}elseif(isset($this->clientsocks[(int)$sock])){
						debug("client\n");
					
						$data = @socket_read($sock, 4096, PHP_BINARY_READ);
					
						if($data !== false && $data !== ''){
							$ret = $this->clients[(int)$sock]->read($data, $this->servers, $this);
							
							if($ret)
								continue;
						}
		
						//error of some sort
						if($data == '')
							myerror("client connection reset", __LINE__);
		
						socket_close($this->clientsocks[(int)$sock]);
						unset($this->clientsocks[(int)$sock]);
						
						$this->clients[(int)$sock]->sock = null;
						unset($this->clients[(int)$sock]);
		
		
				// server socket has incoming data
					}elseif(isset($this->serversocks[(int)$sock])){
						$data = @socket_read($sock, 4096, PHP_BINARY_READ);
		
						if($data !== false && $data != ''){
							$ret = $this->servers[$this->serverids[(int)$sock]]->read($data);
		
							if($ret)
								continue;
						}

						//error of some sort
						if($data == '')
							myerror("server connection reset", __LINE__);

						$id = $this->serverids[(int)$sock];

						socket_close($this->serversocks[(int)$sock]);
						unset($this->serversocks[(int)$sock]);
						unset($this->serverids[(int)$sock]);
		
						$newsock = $this->servers[$id]->reset();
						$this->serversocks[(int)$newsock] = $newsock;
						$this->serverids[(int)$newsock] = $id;
					}
				}
			}
		}
	}
	
	function stats(){
		return "stats?\r\n";
	}
}


class memclient {
	public $sock;
	public $buf;

	public $queue;
	public $queuepos;

	function __construct($sock){
		$this->sock = $sock;
		$this->buf = "";

		$this->queue = array();
		$this->queuepos = 0;
	}
	
	function read($data, & $servers, $proxy){

		$this->buf .= $data;

		// only want data with a newline
		while(($pos = strpos($this->buf, "\n")) !== false){
			$pos++;

			$line = substr($this->buf, 0, $pos-2); //should ignore the \r\n

			$pos2 = strpos($line, ' ');
			if($pos2){
				$cmd = substr($line, 0, $pos2);
				$params = substr($line, $pos2+1);
			}else{
				$cmd = $line;
				$params = '';
			}

		//at this point, nothing is added to the queue, and the buffer is still full
		//the command parsing below will add it to the queue and empty the buffer if the command is complete

			switch($cmd){
				case "get": //get $key ...
					$keys = explode(' ', $params);

				//build the commands
					$commands = array();
					foreach($keys as $key){
						$hash = hashfunc($key);
						$serverid = $hash % count($servers);

						if(!isset($commands[$serverid]))
							$commands[$serverid] = "get";
						$commands[$serverid] .= " $key";
					}

				//send them commands
					foreach($commands as $serverid => $command)
						$servers[$serverid]->command($cmd, $this, $this->queuepos, "$command\r\n");

				//remember what to expect
					$this->queue[$this->queuepos++] = new clientqueueitem($cmd, count($commands));
					$this->buf = substr($this->buf, $pos); //take off this line

					break;


				case "set":    //set $key $flags $exp $len\r\n$val
				case "add":    //add $key $flags $exp $len\r\n$val
				case "replace"://replace $key $flags $exp $len\r\n$val
				case "append": //append $key $flags $exp $len\r\n$val
				
					list($key, $flags, $exp, $len) = explode(" ", $params);

				//haven't buffered enough to fill this request
					if(strlen($this->buf) < $pos + $len+2)
						return true;

				//figure out where to send it
					$hash = hashfunc($key);
					$serverid = $hash % count($servers);

				//pass through the whole command
					$val = substr($this->buf, 0, $pos + $len+2);

				//send the command					
					$servers[$serverid]->command($cmd, $this, $this->queuepos, $val);

				//remember what to expect
					$this->queue[$this->queuepos++] = new clientqueueitem($cmd, 1);
					$this->buf = substr($this->buf, $pos + $len+2); //take off this request
				
					break;
				
				
				case "incr": //incr $key $amt
				case "decr": //decr $key $amt
				case "delete": //delete $key $time
					list($key, $val) = explode(' ', $params);
				
				//figure out where to send it
					$hash = hashfunc($key);
					$serverid = $hash % count($servers);

				//send the command					
					$servers[$serverid]->command($cmd, $this, $this->queuepos, "$line\r\n");

				//remember what to expect
					$this->queue[$this->queuepos++] = new clientqueueitem($cmd, 1);
					$this->buf = substr($this->buf, $pos); //take off this line

					break;

				
				case "flush_all": //flush_all
				case "stats": //stats [type]
				
					foreach($servers as $serverid => $server)
						$server->command($cmd, $this, $this->queuepos, "$line\r\n");

				//remember what to expect
					$this->queue[$this->queuepos++] = new clientqueueitem($cmd, count($servers));
					$this->buf = substr($this->buf, $pos); //take off this line
				
					break;

				case "proxystats":
					$this->buf = substr($this->buf, $pos); //take off this line
					socket_write($this->sock, $proxy->stats());
					break;
				
				case "ping":
					$this->buf = substr($this->buf, $pos); //take off this line
					socket_write($this->sock, "pong\r\n");
					break;


				case "quit":
					return false;

				default:
					$this->buf = substr($this->buf, $pos); //take off this line

					myerror("unknown command: '$line'", __LINE__);
					break;
			}
		}

		return true;
	}
	
	function response($qkey, $serverid, $data, $final){
	//if this is a dead connection, don't do anything
		if(!$this->sock)
			return;

	//add the response to the right entry in the queue
		$this->queue[$qkey]->buf .= $data;

		if($final)
			$this->queue[$qkey]->responses[$serverid] = $serverid;

	//make sure to send in order
		foreach($this->queue as $qkey => & $entry){

		//if this isn't done, stop processing the send queue	
			if(count($entry->responses) != $entry->numservers)
				break;

		//done, send to the client
			switch($entry->cmd){
				case 'get':
				case "stats": //will send multiple of each, but oh well...
					socket_write($this->sock, $entry->buf . "END\r\n");
					break;

				case "set":
				case "add":
				case "replace":
				case "append":
				case "incr":
				case "decr":
				case "delete":

				case "flush_all": //sent to all servers, but just give the last response

					socket_write($this->sock, $entry->buf);
					break;					
			}
			
		//clean up the queue
			unset($entry, $this->queue[$qkey]);
		}
	}
}

class memserver {
	
	public $ip;
	public $port;
	
	public $sock;
	public $buf;

	public $serverid;
	public $queue;
	
	function __construct($ip, $port, $serverid){
		$this->ip = $ip;
		$this->port = $port;
	
		$this->serverid = $serverid;
		$this->queue = array();

		return $this->reset();
	}

	function reset(){
	
		foreach($this->queue as & $entry){
			switch($entry->cmd){
				case 'get':
				case 'stats':
					$entry->client->response($entry->qkey, $this->serverid, '', true);
					break;

				case "set":
				case "add":
				case "replace":
				case "append":
				case "incr":
				case "decr":
				case "delete":
				case "flush_all":
					$entry->client->response($entry->qkey, $this->serverid, "ERROR\r\n", true);
					break;
			}
		}
		$this->queue = array();

		$this->sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		socket_connect($this->sock, $this->ip, $this->port);
//		socket_set_option($this->sock, SOL_SOCKET, SO_REUSEADDR, 1);
//		socket_set_nonblock($this->sock);

		
		if(!$this->sock)
			myerror("Server connect failed: " . socket_last_error() . " : " . socket_strerror(socket_last_error()), __LINE__);
		else
			debug("Connected: $this->ip:$this->port => $this->sock");
		
		return $this->sock;
	}

	function read($data){

		$this->buf .= $data;

		//all responses start with a single line
		while(($pos = strpos($this->buf, "\n")) !== false){
			$pos++;

			foreach($this->queue as $k => & $next) break;
	

			switch($next->cmd){
				case "get":

				//done the request?
					if(strncmp($this->buf, "END", 3) == 0){
						$next->client->response($next->qkey, $this->serverid, '', true);
					
						array_shift($this->queue);
						$this->buf = substr($this->buf, $pos);
						break;
					}

					$line = substr($this->buf, 0, $pos-2);
					list($cmd, $key, $flags, $len) = explode(' ', $line);

					if($cmd != "VALUE")
						return false; //parse error

				//has enough to read?
					if(strlen($this->buf) < $pos + $len+2)
						return true; //true means no error, come back when enough has been read to give a response

					$next->client->response($next->qkey, $this->serverid, substr($this->buf, 0, $pos + $len+2), false);
					
					$this->buf = substr($this->buf, $pos + $len+2);

					break; //will come right back for the next value


				case "stats":
				//done the request?
					if(strncmp($this->buf, "END", 3) == 0){
						$next->client->response($next->qkey, $this->serverid, '', true);
					
						array_shift($this->queue);
						$this->buf = substr($this->buf, $pos);
						break;
					}

					$line = substr($this->buf, 0, $pos-2);
					list($cmd, $name, $val) = explode(' ', $line);

					if($cmd != "STAT")
						return false; //parse error

					$next->client->response($next->qkey, $this->serverid, "$line\r\n", false);
					
					$this->buf = substr($this->buf, $pos);

					break; //will come right back for the next value



				case "set":    //set $key $flags $exp $len\r\n$val
				case "add":    //add $key $flags $exp $len\r\n$val
				case "replace"://replace $key $flags $exp $len\r\n$val
				case "append": //append $key $flags $exp $len\r\n$val
				case "incr":   //incr $key $amt
				case "decr":   //decr $key $amt
				case "delete": //delete $key $time
				case "flush_all": //flush_all

				//just pass it through to the client
					$next->client->response($next->qkey, $this->serverid, substr($this->buf, 0, $pos), true);

					$this->buf = substr($this->buf, $pos);
					array_shift($this->queue);

					break;
			}
		}
		return true;
	}
	
	function command($cmd, $client, $qkey, $fullcommand){
		socket_write($this->sock, $fullcommand);
		$this->queue[] = new serverqueueitem($cmd, $client, $qkey);
	}
}


class clientqueueitem {
	public $cmd;
	public $numservers;
	public $responses;
	public $buf;
	
	function __construct($cmd, $numservers){
		$this->cmd = $cmd;
		$this->numservers = $numservers;
		$this->responses = array();
		$this->buf = '';
	}
}

class serverqueueitem {
	public $cmd;
	public $client;
	public $qkey;
	
	function __construct($cmd, $client, $qkey){
		$this->cmd = $cmd;
		$this->client = $client;
		$this->qkey = $qkey;
	}
}
