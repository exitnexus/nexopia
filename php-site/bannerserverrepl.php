#!/usr/local/bin/php -q
<?

$forceserver=true;
$errorLogging=false;




/*
init_bannerserver

listen_repl

for(i = 0; i < 2; i++)
	connect_repl

if master found,
	state = slave
	getstate
else
	state = master
	announce master, id

listen_client

while(1){
	handle_repl_events
		receive connect
			reply id, state

		server_down
			if master down
				if myid is max
					state = master
					announce master, id
				else
					state = slave

		recieve announce
			if mystate = master
				if myid > newmasterid
					announce master, id
			else
				state = slave

		getstate

		get

		click

		add //add banner

		update //update banner

		delete //delete banner


	time stuff
		update time

		update bannerserver clock
			if minutely
				clear repl log
			if daily
				exit

		close expired client connections


	handle_client_events
		get
			get_banner
			log repl

		click
			click_banner
			log repl

		stats

		add //add banner
			log repl

		update //update banner
			log repl

		delete //delete banner
			log repl

	update_repl_servers
		send repl log


}
*/


/*
	$_SERVER['SCRIPT_NAME'] = "bannerserver.php";
	$_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'] = "www.nexopia.com";
	$_SERVER['DOCUMENT_ROOT'] = "/home/nexopia/public_html";
	$userid = 1;
/*/
	$_SERVER['SCRIPT_NAME'] = "bannerserver.php";
	$_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'] = "www.nexopia.sytes.net";
	$_SERVER['DOCUMENT_ROOT'] = "/htdocs/nexopia/public_html";
	$mode = 2;
//*/
	require_once($_SERVER['DOCUMENT_ROOT'] . "/include/general.lib.php");

	set_time_limit (0);

	define("BANNER_STATE_UNDEF", 0);
	define("BANNER_STATE_MASTER",1);
	define("BANNER_STATE_SLAVE", 2);
	define("BANNER_STATE_ELECT", 3);

	$bannerserver = & new bannerserver( $bannerdb );

	$serverid = rand(1, getrandmax()-1);
	$serverip = '';

	$serverstate = BANNER_STATE_UNDEF;

	$replsocks = array();
	$socks = array();
	$clientdata = array();
	$replclients = array();

//socket to listen for replication servers
	if(!($replsock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)))	myerror("socket_create failed",__LINE__, true);
	if(!socket_set_option($replsock, SOL_SOCKET, SO_REUSEADDR, 1))	myerror("set option failed",__LINE__, true);
	if(!socket_set_nonblock($replsock))								myerror("non block failed",__LINE__, true);
	if(!socket_bind($replsock, "0.0.0.0", BANNER_PORT + 1))			myerror("bind failed",__LINE__, true);
	if(!socket_listen($replsock, 16))								myerror("listen failed",__LINE__, true);

	$replsocks[$replsock] = $replsock;


//socket to listen to clients
	if(!($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)))	myerror("socket_create failed",__LINE__, true);
	if(!socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1))	myerror("set option failed",__LINE__, true);
	if(!socket_set_nonblock($socket))								myerror("non block failed",__LINE__, true);
	if(!socket_bind($socket, "0.0.0.0", BANNER_PORT))				myerror("bind failed",__LINE__, true);
	if(!socket_listen($socket, 64))									myerror("listen failed",__LINE__, true);

	$socks[$socket] = $socket;


//connect to replication servers
	foreach($banneroptions['servers'] as $repladdr){
		if(!($newsock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)))		myerror("socket_create failed",__LINE__, true);
		if(!socket_set_nonblock($newsock))									myerror("non block failed",__LINE__, true);

		socket_connect($newsock, $repladdr, BANNER_PORT+1);

		$replsocks[$newsock] = $newsock;
		$replclients[$newsock] = array( "ip" => $repladdr,
										"time" => 0,
										"id" => 0,
										"buf" => '',
									);
	}

	$time = 0;

	$stats = array( 'starttime' => time(), 'connect' => 0, 'get' => 0,'click' => 0, 'replget' => 0, 'replclick' => 0);

	$window = 60;
	$slidingstats = array();
	for($i=0; $i < $window; $i++)
		$slidingstats[$i] = array( 'connect' => 0, 'get' => 0, 'click' => 0);

	$repl = "";

	$debug = array(	'tick' => false,
					'connect' => false,
					'get' => true,
					'click' => true,
					'state' => $serverstate,
					);

	while(1){
		if($debug['tick'])
			bannerDebug("Tick");


		$newtime = time();

	//do secondly stuff
		if($time != $newtime){
			$time = $newtime;

			$statstime = $time % $window;

			$slidingstats[$statstime] = array( 'connect' => 0, 'get' => 0, 'click' => 0);

			$ret = $bannerserver->settime($time);

			if($ret & 1){ //minutely
				$repl = "";
			}

			if($ret & 2){ //hourly

			}

			if($ret & 4){ //daily
				exit(1); //stop and restart to keep memory leak in check
			}


		//timeouts
			foreach($clientdata as $sock => $data){
				if($data['time'] < $time - 10){ //10 sec timeout
	//				@socket_shutdown($socks[$sock]);
					socket_close($socks[$sock]);
					unset($socks[$sock]); 	// remove client from arrays
					unset($clientdata[$sock]);
				}
			}
		}

		//replcation server stuff
		do{
			$rsocks = array();
			$wsocks = array();

			foreach($replsocks as $sock){
				if($replclients[$sock]['time'])
					$rsocks[] = $sock;
				else
					$wsocks[] = $sock;
			}

			if(socket_select($rsocks, $wsocks, $e = NULL, 0, 10) !== false){ //10 usec timeout

				//sockets where we initiated the connection
				foreach($wsocks as $sock){
					$errno = socket_get_option($sock, SOL_SOCKET, SO_ERROR);

					switch($errno){
						case 0: //connection succeeded
							$replclients[$sock]['time'] = $time;

							socket_write($sock, "id $serverid $serverstate\n");

							break;

						case 115: //connection still in progress
							break;

						default: //error in connection, server probably down, wait for them to connect to me
							socket_close($replsocks[$sock]);
							unset($replsocks[$sock]); 	// remove client from arrays
							unset($replclients[$sock]);

							myerror("connection failed", __LINE__);
					}
				}

				// loop through sockets
				foreach($rsocks as $sock){
					// listening socket has a connection, deal with it
					if($sock == $replsocket){
						if(!($newsock = socket_accept($replsocket))){
							myerror("connection failed",__LINE__);
						}else{
							// add socket to client list

							socket_getpeername($newsock, $addr);
							if($debug['connect'])
								bannerDebug("[repl connection]: $addr");

							$replsocks[$newsock] = $newsock;
							$replclients[$newsock] = array( "ip" => $addr,
															"time" => $time,
															"id" => 0,
															"buf" => '',
															'state' => BANNER_STATE_UNDEF,
														);

							socket_write($sock, "id $serverid $serverstate\n");
						}
					}else{
						// client socket has incoming data
						if(($read = @socket_read($sock, 4096)) === false || $read == '') {
							if($read != '')
								myerror("connection reset", __LINE__);


		//					@socket_shutdown($socks[$sock]);
							socket_close($replsocks[$sock]);
							unset($replsocks[$sock]); 	// remove client from arrays
							unset($replclients[$sock]);

							$master = 0;
							foreach($replclients as $client){ //call election if no master present
								if($client['state'] == BANNER_STATE_MASTER){
									$master = $client['id'];
									break;
								}
							}
							if(!$master){
								foreach($replsocks as $wsock){
									socket_write($wsock, "elect")
								}
							}
						}else{

							$replclients[$sock]['buf'] .= $read;
							$replclients[$sock]['time'] = $time;

							if(strchr($replclients[$sock]['buf'], "\n") !== false){

								$msgs = explode("\n", $replclients[$sock]['buf']);

								$clientdata[$sock]['buf'] = array_pop($msgs);

								foreach($msgs as $msg){

									$pos = strpos($msg, ' ');
									if($pos){
										$cmd = substr($msg, 0, $pos);
										$params = substr($msg, $pos+1);
									}else{
										$cmd = substr($msg, 0);
									}

									switch($cmd){
										case "replget":
											list($size, $userid, $age, $sex, $loc, $page, $ret) = explode(' ', $params);
											$ret = $bannerserver->getBanner($size, $userid, $age, $sex, $loc, $page, $ret);

											break;

										case "replclick":
											list($id, $age, $sex, $loc, $page, $ret) = explode(' ', $params);
											$bannerserver->clickBanner($id, $age, $sex, $loc, $page, $ret);

											break;

										case "id":
											list($id, $state) = explode(" ", $params);

											if($id == $serverid){ //connect to self
												$serverip = $addr;

												socket_close($replsocks[$sock]);
												unset($replsocks[$sock]); 	// remove client from arrays
												unset($replclients[$sock]);
											}

											foreach($replclients as $client){
												if($client['id'] == $params){ //already have a connection
													socket_close($replsocks[$sock]);
													unset($replsocks[$sock]); 	// remove client from arrays
													unset($replclients[$sock]);
												}
											}

											$replclients[$sock]['id'] = $id;
											$replclients[$sock]['state'] = $state;


											break;

									}
								}
							}
						}
					}
				}
			}
		}while($serverstate == BANNER_STATE_UNKNOWN);

	// client sockets we want to pay attention to
	//	$selsocks = array_merge($socket, $socks);
		$selsocks = array_values($socks);

		if(socket_select($selsocks, $w = NULL, $e = NULL, 0, 10) !== false){ //10 usec timeout
			// loop through sockets
			foreach($selsocks as $sock){
				// listening socket has a connection, deal with it
				if($sock == $socket){
					if(!($newsock = socket_accept($socket))){
						myerror("connection failed",__LINE__);
					}else{
						// add socket to client list

						socket_getpeername($newsock, $addr);
						if($debug['connect'])
							bannerDebug("[connection]: $addr");

						$socks[$newsock] = $newsock;
						$clientdata[$newsock] = array('time' => $time, 'buf' => '', 'addr' => $addr);

						$stats['connect']++;
						$slidingstats[$statstime]['connect']++;
					}
				}else{
					// client socket has incoming data
					if(($read = @socket_read($sock, 1024)) === false || $read == '') {
						if($read != '')
							myerror("connection reset", __LINE__);


	//					@socket_shutdown($socks[$sock]);
						socket_close($socks[$sock]);
						unset($socks[$sock]); 	// remove client from arrays
						unset($clientdata[$sock]);
					}else{

						$clientdata[$sock]['buf'] .= $read;
						$clientdata[$sock]['time'] = $time;

						// only want data with a newline
						if(strchr($clientdata[$sock]['buf'], "\n") !== false){

							$msgs = explode("\n", $clientdata[$sock]['buf']);

							$clientdata[$sock]['buf'] = array_pop($msgs);

							foreach($msgs as $msg){
	/*
							$msg = trim($clientdata[$sock]['buf']);
	/* /
							$pos = strpos($clientdata[$sock]['buf'], "\n");
							$msg = substr($clientdata[$sock]['buf'], 0, $pos);
							$clientdata[$sock]['buf'] = substr($clientdata[$sock]['buf'], $pos+1);
	//*/

								$pos = strpos($msg, ' ');
								if($pos){
									$cmd = substr($msg, 0, $pos);
									$params = substr($msg, $pos+1);
								}else{
									$cmd = substr($msg, 0);
								}

								switch($cmd){
									case "get":
										$stats['get']++;
										$slidingstats[$statstime]['get']++;

										list($size, $userid, $age, $sex, $loc, $page) = explode(' ', $params);
										$ret = $bannerserver->getBanner($size, $userid, $age, $sex, $loc, $page);

										if($debug['get'])
											bannerDebug("get $params => $ret");

										socket_write($sock, "$ret\n");

										$repl .= "replget $params $ret\n";

										unset($ret, $size, $userid, $age, $sex, $loc, $page);

										break;

									case "click":
										if($debug['click'])
											bannerDebug("click $params");
										$stats['click']++;
										$slidingstats[$statstime]['click']++;

										list($id, $age, $sex, $loc, $page) = explode(' ', $params);
										$bannerserver->clickBanner($id, $age, $sex, $loc, $page);

										$repl .= "replclick $params\n";

										unset($id, $age, $sex, $loc, $page);

										break;

									case "add": // "add $id"
										$bannerserver->addBanner($params);
										bannerDebug("add $params");
										break;

									case "update": // "update $id"
										$bannerserver->updateBanner($params);
										bannerDebug("update $params");
										break;

									case "del": // "del $id"
										$bannerserver->deleteBanner($params);
										bannerDebug("delete $params");
										break;

									case "quit":
										socket_close($socks[$sock]);
										unset($socks[$sock]); 	// remove client from arrays
										unset($clientdata[$sock]);
										break;

									case "condump":
										socket_write($sock, print_r($socks, true) . "\n");
										break;

									case "buffdump":
										socket_write($sock, print_r($clientdata, true) . "\n");
										break;

									case "bannerdump":
										socket_write($sock, print_r($bannerserver, true) . "\n");
										break;

									case "stats":

										$total = array( 'connect' => 0, 'get' => 0, 'click' => 0);
										for($i=0; $i < $window; $i++){
											$total['connect'] += $slidingstats[$i]['connect'];
											$total['get'] 	  += $slidingstats[$i]['get'];
											$total['click']   += $slidingstats[$i]['click'];
										}

										$out  = "Uptime: " . ($time - $stats['starttime']) . "\n";
										$out .= "Connect: " . str_pad($stats['connect'], 9) . str_pad($total['connect'], 7) . ($slidingstats[($statstime+$window-1)%$window]['connect']) . "\n";
										$out .= "Get:     " . str_pad($stats['get'], 9) .     str_pad($total['get'], 7) .     ($slidingstats[($statstime+$window-1)%$window]['get']) . "\n";
										$out .= "Click:   " . str_pad($stats['click'], 9) .   str_pad($total['click'], 7) .   ($slidingstats[($statstime+$window-1)%$window]['click']) . "\n";
										$out .= "Connections: " . (count($clientdata) - 1) . "\n"; //-1 for the listening socket


										socket_write($sock, $out . "\n");

										unset($out, $total);

										break;

									case "show":
										$debug[$params] = true;
										break;

									case "hide":
										$debug[$params] = false;
										break;

									default:
										myerror("unknown command: '$msg'", __LINE__);
										break;
								}
								unset($pos, $msg, $params);
							}
						}
					}
				}
			}
		}
	}

function myerror($error, $line, $die = false) {
	echo "[error] on line $line: \"$error\", " . socket_strerror(socket_last_error()) . "\n";

	if($die)
		exit;
}


