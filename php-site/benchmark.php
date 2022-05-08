#!/usr/local/bin/php -q
<?


$n = 4;
$c = 1;
$k = false;
//$k = true; //doesn't work
$addr = "10.0.4.253";
//$addr = "10.0.4.1";
$port = 80;
$urllist = "files/benchlist";

////////////////////////////////


$wrote = 0;
$read = 0;
$success = 0;
$fail = 0;
$transfer = 0;

$urls = file($urllist);
//$urls = array("/files/1.txt", "/files/2.txt");
//$urls = array("/index.html");

$numurls = count($urls)-1;

$socks = array();

$header  = "GET %url% HTTP/1.1\r\n";
$header .= "Host: img.nexopia.com\r\n";
$header .= "Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5\r\n";
//$header .= "User-Agent: Nexopia Bench\r\n";
if($k)
	$header .= "Keep-Alive: 300\n\r";
$header .= "Connection: " . ($k ? "keep-alive" : "close") . "\r\n";
$header .= "\r\n";
//$header .= "\r\n";

echo "Sample Request:\n";
echo str_replace("%url%", trim($urls[rand(0, $numurls)]), $header);

/*
//response:

HTTP/1.x 200 OK
Connection: close
Date: Fri, 05 Aug 2005 22:45:06 GMT
Transfer-Encoding: chunked
Content-Type: text/html
Content-Encoding: gzip
Vary: Accept-Encoding
Server: lighttpd/1.3.14
*/



function connect(){
	global $socks, $addr, $port;
	if(!($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)))	myerror("socket_create failed",__LINE__, true);

//	var_dump(socket_get_option($sock, SOL_SOCKET, SO_SNDTIMEO));
//	if(!socket_set_option($sock, SOL_SOCKET, SO_SNDTIMEO, 0))	myerror("set option failed",__LINE__, true);
//	if(!socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1))	myerror("set option failed",__LINE__, true);
//	if(!socket_set_nonblock($sock))								myerror("non block failed",__LINE__, true);
	if(!socket_connect($sock, $addr, $port))					myerror("connect failed",__LINE__, true);

	$socks[$sock] = $sock;

//echo "c";flush();

	return $sock;
}

function disconnect($sock){
	global $socks;
	socket_close($sock);
	unset($socks[$sock]);
//	echo "d";flush();
}


function makerequest($sock){
	global $urls, $header, $wrote, $n, $numurls;

	if($wrote >= $n)
		return;

	++$wrote;
//	echo "w";flush();

	$url = trim($urls[rand(0, $numurls)]);
	$write = str_replace("%url%", $url, $header);
	socket_write($sock, $write, strlen($write));
//	echo "\n" . substr($write, 0, -4) . "\n";
}



echo "starting";

$starttime = gettime();

while(1){
	while(count($socks) < $c){
		$sock = connect();
		makerequest($sock);
	}

/*
	$newtime = time();

	if($time != $newtime){
		$time = $newtime;

		foreach($clientdata as $sock => $data){
			if($data['time'] < $time - 10){ //10 sec timeout
				@socket_shutdown($sock);
				socket_close($sock);
				unset($clients[$sock]); 	// remove client from arrays
			}
		}
	}
*/
	$checksocks = $socks;

	if(socket_select($checksocks, $w = NULL, $e = NULL, 0, 10) !== false){ //10 usec timeout
		// loop through sockets
		foreach($checksocks as $sock){

//			echo "r";flush();

			$buf = '';
			$reset = false;

			while(1){
				$in = socket_read($sock, 4096);//, PHP_BINARY_READ);
				if($in === false){ //closed connection
					disconnect($sock);
					$reset = true;
					break;
				}elseif($in === ''){ //end of stream
					break;
				}else{
					$buf .= $in;
					$transfer += strlen($in);
				}
			}

			$head = substr($buf, 0, strpos($buf, "\r\n\r\n"));

//			echo "\n$head\n";
//			echo "\n$buf\n";

			$lines = explode("\n", $head);

			$line = array_shift($lines);

			if(trim($line) == 'HTTP/1.1 200 OK')
				$success++;
			else
				$fail++;

			$read++;

			if($k){
				foreach($lines as $line){
					if(substr($line, 0, 10) == 'Connection' && $line{12} == 'c'){ //
						disconnect($sock);
						$reset = true;
						break;
					}
				}
			}else{
				disconnect($sock);
				$reset = true;
			}

			if($reset)
				$sock = connect();
			makerequest($sock);
		}
	}

	if($read >= $n)
		break;
}

$endtime = gettime();

//echo "<pre>";
echo "\n";
echo "Requests:    " . number_format($wrote) . "\n";
echo "Concurrency: " . number_format($c) . "\n";
echo "Time:        " . number_format(($endtime - $starttime)/10000, 3) . " secs\n";
echo "Transfered:  " . number_format(round($transfer/1024)) . " KB\n";
echo "Succeeded:   " . number_format($success) . " req\n";
echo "Failed:      " . number_format($fail) . " req\n";
echo "Req/s:       " . number_format( $wrote*10000 / ($endtime - $starttime), 2) . " req/s\n";
echo "KBytes/s     " . number_format( ($transfer/1024)*10000 / ($endtime - $starttime), 2) . " KB/s\n";



function myerror($error, $line, $die = false) {
	echo "[error] on line $line: \"$error\", " . socket_strerror(socket_last_error()) . "\n";

	if($die)
		exit;
}

function gettime(){
	list($usec, $sec) = explode(" ",microtime());
	return (10000*((float)$usec + (float)$sec));
}



