#!/usr/local/php-cli/bin/php
<?

$forceserver=true;
$errorLogging=false;

define("VIEW_WINDOWS", 5);

//*
//#!/usr/local/php-cli/bin/php
	$_SERVER['SCRIPT_NAME'] = "/bannerserver.php";
	$_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'] = "www.nexopia.com";
	$_SERVER['DOCUMENT_ROOT'] = "/home/nexopia/public_html";
	$userid = 1;
/*/
//#!/usr/bin/php
	$_SERVER['SCRIPT_NAME'] = "/bannerserver.php";
	$_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'] = "www.dev.nexopia.com";
	$_SERVER['DOCUMENT_ROOT'] = "/home/nexopia/public_html";
//	$_SERVER['DOCUMENT_ROOT'] = "/home/troy/workspace/Nexopia/branches/live.28.03.2006/public_html";
//	$_SERVER['DOCUMENT_ROOT'] = "/home/timo/nexopia/trunk/public_html";
	$userid = 5;
//*/
	require_once($_SERVER['DOCUMENT_ROOT'] . "/include/general.lib.php");

set_time_limit (0);

$logserver = "adblaster-test.nexopia.com";
$logserver_port = 5556;

$bannerserver = new bannerserver( $bannerdb, count($bannerservers));

$version = "2.2.1";

$initialsocktimeout = 10; //10 sec timeout
$socktimeout = $initialsocktimeout; 
$maximum_sockets = 1024;

$clients = array();
$clientdata = array();

$time = 0;

$stats = array( 'starttime' => time(), 
				'connect' => 0, 
				'get' => 0, 
				'getfail' => 0, 
				'click' => 0
				);

$window = 60;
$slidingstats = array();
for($i=0; $i < $window; $i++)
	$slidingstats[$i] = array( 'connect' => 0, 
								'get' => 0, 
								'getfail' => 0, 
								'click' => 0);

$debug = array(	'tick' => false,
				'connect' => false,
				'get' => false,
				'getlog' => true,
				'getfail' => true,
				'click' => false,
				'timeupdates' => false,
				'dailyrestart' => true,
				'passback' => true
				);

if(!($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)))	myerror("socket_create failed",__LINE__, true);
if(!socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1))	myerror("set option failed",__LINE__, true);
if(!socket_set_nonblock($socket))								myerror("non block failed",__LINE__, true);
if(!socket_bind($socket, "0.0.0.0", BANNER_PORT))				myerror("bind failed",__LINE__, true);
if(!socket_listen($socket, 16))									myerror("listen failed",__LINE__, true);

$clients[(int)$socket] = $socket;
$recentviews = array();
for($i = 0; $i < VIEW_WINDOWS; $i++) {
	$recentviews[$i] = array();
}
$currentwindow = 0;

$logsock = null;
if($logserver){
	$logsock = fsockopen($logserver, $logserver_port, $errno, $errstr, 0.05);
	if($logsock){
		stream_set_timeout($logsock, 0.05);
		stream_set_blocking($logsock, 0); //non blocking
	}else{
		$logsock = null;
	}
}


while(1){
	if($debug['tick'])
		bannerDebug("Tick");

	$newtime = time();

	if($time != $newtime){
		$time = $newtime;

		$statstime = $time % $window;

		foreach($slidingstats[$statstime] as $k => $v)
			$slidingstats[$statstime][$k] = 0;

		if($bannerserver->settime($time, $debug['timeupdates']) & 4){ //stop and restart to keep memory leak in check, done at the day end
			if($debug['dailyrestart'])
				exit(1);
		}

		//perform socket_timeout modifications
		$socket_count = count($clients);
		if ($socket_count < $maximum_sockets/5) {
			$socktimeout = $initialsocktimeout;
		} else {
			$socktimeout = max(1,$initialsocktimeout - $initialsocktimeout*($socket_count/$maximum_sockets));
			bannerDebug("Socket timeout reduced due to load: $socket_count sockets open, socket timeout reduced to $socktimeout");
		}

		foreach($clientdata as $sock => $data){
			if($data['time'] < $time - $socktimeout){
//				@socket_shutdown($clients[$sock]);
				socket_close($clients[(int)$sock]);
				unset($clients[(int)$sock]); 	// remove client from arrays
				unset($clientdata[(int)$sock]);
			}
		}
		
		$currentwindow = ($currentwindow+1) % VIEW_WINDOWS;
		$recentviews[$currentwindow] = array();
	}

	// sockets we want to pay attention to
//	$socks = array_merge($socket, $clients);
	$socks = array_values($clients);
	$w=NULL;
	$e=NULL;
	if(socket_select($socks, $w, $e, 0, 10) !== false){ //10 usec timeout
		// loop through sockets
		foreach($socks as $sock){
			// listening socket has a connection, deal with it
			if($sock == $socket){
				do{ //accept as many connections as are queued up
					if($newsock = @socket_accept($socket)){ // add socket to client list
						socket_getpeername($newsock, $addr);
						if($debug['connect'])
							bannerDebug("[connection]: $addr");

						$clients[(int)$newsock] = $newsock;
						$clientdata[(int)$newsock] = array('time' => $time, 'buf' => '', 'addr' => $addr);

						$stats['connect']++;
						$slidingstats[$statstime]['connect']++;
					}else{ //done accepting connections
//						myerror("connection failed",__LINE__);
						break;
					}
				}while(0); //seems to give errors when trying to accept when there is nothing to accept, so disable for now.
			}else{
				// client socket has incoming data
				if(($read = @socket_read($sock, 1024)) === false || $read == '') {
					if($read != '')
						myerror("connection reset", __LINE__);


//					@socket_shutdown($clients[$sock]);
					socket_close($clients[(int)$sock]);
					unset($clients[(int)$sock]); 	// remove client from arrays
					unset($clientdata[(int)$sock]);
				}else{

					$clientdata[(int)$sock]['buf'] .= $read;
					$clientdata[(int)$sock]['time'] = $time;

					// only want data with a newline
					if(strchr($clientdata[(int)$sock]['buf'], "\n") !== false){

						$msg = trim($clientdata[(int)$sock]['buf']);

						$msgs = explode("\n", $clientdata[(int)$sock]['buf']);
						$clientdata[(int)$sock]['buf'] = array_pop($msgs); //last is an empty line
						$msg = trim(array_pop($msgs)); //last request. If it missed one, ignore it.

						$clientdata[(int)$sock]['buf'] = '';

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

								list($usertime, $size, $userid, $age, $sex, $loc, $interests, $page, $passback) = explode(' ', $params);

								if($interests == '0')
									$interests = array();
								else
									$interests = explode(',', $interests);

								if($passback)
									$bannerserver->passbackBanner($passback, $userid);
								
									$ret = $bannerserver->getBanner($usertime, $size, $userid, $age, $sex, $loc, $interests, $page);

								if ($debug['passback']) {
									if ($passback) {
										$hasSeen = false;
										$viewsstring = "";
										foreach ($recentviews as &$viewswindow) { //reference
											if (isset($viewswindow[$userid]) && is_array($viewswindow[$userid])) {
												foreach ($viewswindow[$userid] as $view) {
													$viewsstring .= "$view ";
													if ($passback == $view) {
														$hasSeen = true;
													}
												}
											}
										}
										if (!$hasSeen) {
											bannerDebug("Invalid Passback: $passback, Recently Viewed: $viewsstring");
										}
									}
									$recentviews[$currentwindow][$userid][] = $ret;
								}
								if($debug['get'] || ($debug['getfail'] && !$ret))
									bannerDebug("get $params => $ret");

								if($debug['getlog'] && $logsock){
									if(fwrite($logsock, "get $params => $ret\n") == false){
										bannerDebug("log server connection error: $errstr ($errno)<br />");
										$logsock = null;
									}
								}

								if(!$ret){
									$stats['getfail']++;
									$slidingstats[$statstime]['getfail']++;
								}

								socket_write($sock, "$ret\n");

								unset($ret, $size, $userid, $age, $sex, $loc, $interests, $page, $passback);

								break;

							case "click":
								if($debug['click'])
								bannerDebug("click $params");

								$stats['click']++;
								$slidingstats[$statstime]['click']++;

								list($id, $age, $sex, $loc, $interests, $page, $time) = explode(' ', $params);

								if($interests == '0')
									$interests = array();
								else
									$interests = explode(',', $interests);


								$bannerserver->clickBanner($id, $age, $sex, $loc, $interests, $page, $time);

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

							case "addcampaign": // "addcampaign $id"
								$bannerserver->addCampaign($params);
								bannerDebug("addcampaign $params");
								break;

							case "updatecampaign": // "updatecampaign $id"
								$bannerserver->updateCampaign($params);
								bannerDebug("updatecampaign $params");
								break;

							case "delcampaign": // "delcampaign $id"
								$bannerserver->deleteCampaign($params);
								bannerDebug("deletecampaign $params");
								break;

							case "quit": //disconnects this connection
//								@socket_shutdown($clients[$sock]);
								socket_close($clients[(int)$sock]);
								unset($clients[(int)$sock]); 	// remove client from arrays
								unset($clientdata[(int)$sock]);
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
								$total = array();
								foreach($slidingstats as $i => $stat)
									foreach($stat as $k => $v)
										if(!isset($total[$k]))
											$total[$k] = $v;
										else
											$total[$k] += $v;

								$out  = "Uptime: " . ($time - $stats['starttime']) . "\n";
								$out .= "Connect:  " . str_pad($stats['connect'], 9) . str_pad($total['connect'], 7) . ($slidingstats[($statstime+$window-1)%$window]['connect']) . "\n";
								$out .= "Get:      " . str_pad($stats['get'], 9) .     str_pad($total['get'], 7) .     ($slidingstats[($statstime+$window-1)%$window]['get']) . "\n";
								$out .= "Get Fail: " . str_pad($stats['getfail'], 9) . str_pad($total['getfail'], 7) . ($slidingstats[($statstime+$window-1)%$window]['getfail']) . "\n";
								$out .= "Click:    " . str_pad($stats['click'], 9) .   str_pad($total['click'], 7) .   ($slidingstats[($statstime+$window-1)%$window]['click']) . "\n";
								$out .= "Connections: " . count($clientdata) . "\n";

								socket_write($sock, $out . "\n");

								unset($out, $total, $stat, $i, $k, $v);

								break;

							case "show":
								if(isset($debug[$params])){
									$debug[$params] = true;
									socket_write($sock, "debug enabled for $params\n");
								}else{
									socket_write($sock, "unknown debug variable: $params\n");
								}
								break;

							case "hide":
								if(isset($debug[$params])){
									$debug[$params] = false;
									socket_write($sock, "debug disabled for $params\n");
								}else{
									socket_write($sock, "unknown debug variable: $params\n");
								}
								break;

							case "shutdown": //dump stats, clean up most memory, and quit. Good for upgrading the server early :p
								socket_write($sock, "shutting down\n");
								bannerDebug("shutting down");
								$bannerserver->daily();
								exit;
							
							case "version":
								socket_write($sock, "$version\n");
								break;
								
							case "reconnect":
								if($logsock)
									fclose($logsock);

								if($params)
									list($logserver, $logserver_port) = explode(':', $params);

								if($logserver && $logserver_port){
									if($logsock = fsockopen($logserver, $logserver_port, $errno, $errstr, 0.05)){
										stream_set_timeout($logsock, 0.05);
										stream_set_blocking($logsock, 0); //non blocking
										socket_write($sock, "success\n");
									}else{
										socket_write($sock, "failed\n");
										$logsock = null;
									}
								}else{
									socket_write($sock, "no logserver defined\n");
								}
									
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


