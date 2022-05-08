<?php

	$login=1;
	require_once("include/general.lib.php");

	if(!in_array($userData['userid'], $debuginfousers))
		die("error");
	
	incHeader();

	if (isset($_POST["ips"])) {
		$ips = preg_split('/\s+/', $_POST["ips"]);
		pWhoisDump($ips);
	} else if (isset($_GET["ip"])) {
		pWhoisDump(Array($_GET["ip"]));
	}

	pWhoisForm();
	incFooter();

	// end main


function pWhoisDump($ips) {
	if (!($pwresp = pWhoisLookup($ips))) {
		print "Lookup Failure<br>";
		return;
	}

	print "<pre>";
	foreach ($pwresp as $ip => $resp) {
		print "IP: " . $ip . "<br />";
		print_r($resp);
		print "<br />";
	}
	print "</pre>";
}

/**
 *
 *  Prefix WhoIs Bulk Query Interface
 *
 *  -- a native interface to Prefix WhoIs implemented in PHP*
 *
 *                                        * requires PHP >= 5
 *
 *  Simply call doPWLookupBulk(array $queryArray) and it will
 *  return an associative array of AS numbers (and other data
 *  indexed by the IP addresses passed to it in the $queryArray 
 *  argument.
 *                               -- by Victor Oppleman (2005)
 */

function pWhoisLookup($queryarray) {

	$pwserver = 'whois.pwhois.org';   // Prefix WhoIswhois Server (public)
	$pwport = 43;                     // Port to which Prefix WhoIswhois listens
	$socket_timeout = 20;             // Timeout for socket connection operations
	$socket_delay = 5;                // Timeout for socket read/write operations

	// Mostly generic code beyond this point
	$pwserver = gethostbyname($pwserver);

	// Optimize query array and renumber
	$queryarray = array_unique($queryarray);
	$i = 0;
	foreach ($queryarray as $a) { 
		$qarray[$i] = $a;
		$i++;
	}


	// Create a new socket
	$sock = stream_socket_client("tcp://".$pwserver.":".$pwport, $errno, $errstr, $socket_timeout);
	if (!$sock) {
		return 0;
	} else {
		stream_set_blocking($sock,0);         // Set stream to non-blocking
		stream_set_timeout($sock, $socket_delay); // Set stream delay timeout

		// Build, then submit bulk query
		$request = "begin\n";
		foreach ($qarray as $addr) {
			$request .= $addr . "\n";
		}
		$request .= "end\n";
		fwrite($sock, $request);

		// Keep looking for more responses until EOF or timeout
		$before_query = date(U);
		while(!feof($sock)){
			if($buf=fgets($sock,128)){
				$buffer .= $buf;
				if (date(U) > ($before_query + $socket_timeout)) break;
			}
		}

		fclose($sock);

		$response = array();
		$resp = explode("\n",$buffer);
		$entity_id = 0; $found = 0;
		foreach ($resp as $r) {
			$matcher = '';

			if (stristr($r,"origin-as")) {
				if ($found > 0) { $entity_id++; $found = 0; }
				$matcher = explode(":",$r);
				$response[$qarray[$entity_id]][strtolower($matcher[0])] = ltrim($matcher[1]);
				$found++;
			} else if (stristr($r,'prefix')) {
				$matcher = explode(":",$r);
				$response[$qarray[$entity_id]][strtolower($matcher[0])] = ltrim($matcher[1]);
			} else if (stristr($r,'as-path')) {
				$matcher = explode(":",$r);
				$response[$qarray[$entity_id]][strtolower($matcher[0])] = ltrim($matcher[1]);
			} else if (stristr($r,'org-name')) {
				$matcher = explode(":",$r);
				$response[$qarray[$entity_id]][strtolower($matcher[0])] = ltrim($matcher[1]);
			} else if (stristr($r,'net-name')) {
				$matcher = explode(":",$r);
				$response[$qarray[$entity_id]][strtolower($matcher[0])] = ltrim($matcher[1]);
			} else if (stristr($r,'cache-date')) {
				$matcher = explode(":",$r);
				$response[$qarray[$entity_id]][strtolower($matcher[0])] = ltrim($matcher[1]);
			} 

			if ($entity_id >= array_count_values($qarray)) break;
		}
		return $response;
	}
}

function pWhoisForm() {
	print '<form method="post"><textarea name="ips"></textarea><br /><input type="submit" value="Lookup"></form>';
}

?>