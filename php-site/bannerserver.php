#!/usr/local/php-cli/bin/php
<?

$forceserver=true;
$errorLogging=false;

//*
//#!/usr/local/php-cli/bin/php
	$_SERVER['SCRIPT_NAME'] = "/bannerserver.php";
	$_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'] = "www.nexopia.com";
	$_SERVER['DOCUMENT_ROOT'] = "/home/nexopia/public_html";
	$userid = 1;
/*/
//#!/usr/local/bin/php -q
	$_SERVER['SCRIPT_NAME'] = "/bannerserver.php";
	$_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'] = "www.nexopia.sytes.net";
	$_SERVER['DOCUMENT_ROOT'] = "/htdocs/nexopia/public_html";
	$userid = 5;
//*/
	require_once($_SERVER['DOCUMENT_ROOT'] . "/include/general.lib.php");

set_time_limit (0);

$bannerserver = & new bannerserver( $bannerdb, count($bannerservers));

$socktimeout = 10; //10 sec timeout

$clients = array();
$clientdata = array();

$time = 0;

$stats = array( 'starttime' => time(), 'connect' => 0, 'get' => 0, 'click' => 0);

$window = 60;
$slidingstats = array();
for($i=0; $i < $window; $i++)
	$slidingstats[$i] = array( 'connect' => 0, 'get' => 0, 'click' => 0);

$debug = array(	'tick' => false,
				'connect' => false,
				'get' => true,
				);

if(!($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)))	myerror("socket_create failed",__LINE__, true);
if(!socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1))	myerror("set option failed",__LINE__, true);
if(!socket_set_nonblock($socket))								myerror("non block failed",__LINE__, true);
if(!socket_bind($socket, "0.0.0.0", BANNER_PORT))				myerror("bind failed",__LINE__, true);
if(!socket_listen($socket, 16))									myerror("listen failed",__LINE__, true);

$clients[$socket] = $socket;

while(1){
	if($debug['tick'])
		bannerDebug("Tick");

	$newtime = time();

	if($time != $newtime){
		$time = $newtime;

		$statstime = $time % $window;

		$slidingstats[$statstime] = array( 'connect' => 0, 'get' => 0, 'click' => 0);

		if($bannerserver->settime($time) & 4){ //stop and restart to keep memory leak in check, done at the day end
			exit(1);
		}

		foreach($clientdata as $sock => $data){
			if($data['time'] < $time - $socktimeout){
//				@socket_shutdown($clients[$sock]);
				socket_close($clients[$sock]);
				unset($clients[$sock]); 	// remove client from arrays
				unset($clientdata[$sock]);
			}
		}
	}

	// sockets we want to pay attention to
//	$socks = array_merge($socket, $clients);
	$socks = array_values($clients);

	if(socket_select($socks, $w = NULL, $e = NULL, 0, 10) !== false){ //10 usec timeout
		// loop through sockets
		foreach($socks as $sock){
			// listening socket has a connection, deal with it
			if($sock == $socket){
				if(!($newsock = socket_accept($socket))){
					myerror("connection failed",__LINE__);
				}else{
					// add socket to client list

					socket_getpeername($newsock, $addr);
					if($debug['connect'])
						bannerDebug("[connection]: $addr");

					$clients[$newsock] = $newsock;
					$clientdata[$newsock] = array('time' => $time, 'buf' => '', 'addr' => $addr);

					$stats['connect']++;
					$slidingstats[$statstime]['connect']++;
				}
			}else{
				// client socket has incoming data
				if(($read = @socket_read($sock, 1024)) === false || $read == '') {
					if($read != '')
						myerror("connection reset", __LINE__);


//					@socket_shutdown($clients[$sock]);
					socket_close($clients[$sock]);
					unset($clients[$sock]); 	// remove client from arrays
					unset($clientdata[$sock]);
				}else{

					$clientdata[$sock]['buf'] .= $read;
					$clientdata[$sock]['time'] = $time;

					// only want data with a newline
					if(strchr($clientdata[$sock]['buf'], "\n") !== false){

						$msg = trim($clientdata[$sock]['buf']);

						$msgs = explode("\n", $clientdata[$sock]['buf']);
						$clientdata[$sock]['buf'] = array_pop($msgs); //last is an empty line
						$msg = trim(array_pop($msgs)); //last request. If it missed one, ignore it.

						$clientdata[$sock]['buf'] = '';

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

								list($size, $userid, $age, $sex, $loc, $interests, $page, $passback) = explode(' ', $params);

								if($interests == '0')
									$interests = array();
								else
									$interests = explode(',', $interests);

								$ret = $bannerserver->getBanner($size, $userid, $age, $sex, $loc, $interests, $page);

								if($passback)
									$bannerserver->passbackBanner($size, $passback);

								if($debug['get'])
									bannerDebug("get $params => $ret");

								socket_write($sock, "$ret\n");

								unset($ret, $size, $userid, $age, $sex, $loc, $interests, $page, $passback);

								break;

							case "click":
								bannerDebug("click $params");
								$stats['click']++;
								$slidingstats[$statstime]['click']++;

								list($id, $age, $sex, $loc, $interests, $page) = explode(' ', $params);

								if($interests == '0')
									$interests = array();
								else
									$interests = explode(',', $interests);


								$bannerserver->clickBanner($id, $age, $sex, $loc, $interests, $page);

								unset($id, $age, $sex, $loc, $interests, $page);

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
//								@socket_shutdown($clients[$sock]);
								socket_close($clients[$sock]);
								unset($clients[$sock]); 	// remove client from arrays
								unset($clientdata[$sock]);
								break;

							case "condump":
								socket_write($sock, print_r($clients, true) . "\n");
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
								$out .= "Connections: " . count($clientdata) . "\n";

								socket_write($sock, $out . "\n");

								unset($out, $total);

								break;

							case "show":
								$debug[$params] = true;
								break;

							case "hide":
								$debug[$params] = false;
								break;

/*
							case "globals":
								ob_start();
								print_r($GLOBALS);
								$output = ob_get_contents();

								$fd = fopen("dump", "w");
								fwrite($fd, $output);
								fclose($fd);

								unset($output, $fd);

								break;
*/
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

function myerror($error, $line, $die = false) {
	echo "[error] on line $line: \"$error\", " . socket_strerror(socket_last_error()) . "\n";

	if($die)
		exit;
}


