#!/usr/bin/php
<?

	error_reporting (E_ALL);	

	$timeout = 5;


	$minhits = 10;
	$threshhold = 75;

	$linesoutmin = 10;
	$linesoutmax = 10;
	
	$limittypes = array();
	
	$sortcol = 'total';

	$proxies = array();

	$name = array_shift($argv); // processname

	$verbose = 1;

	while(1){
		if(!count($argv))
			break;

		$arg = array_shift($argv);

		switch($arg){
			case '-q':
				$verbose--;
				break;

			case '-v':
				$verbose++;
				break;

			case '-t':
				$timeout = array_shift($argv);
				break;

			case '-A':
				$linesoutmin = array_shift($argv);
				break;

			case '-B':
				$linesoutmax = array_shift($argv);
				break;

			case '-m':
				$minhits = array_shift($argv);
				break;

			case '--threshhold':
				$threshhold = array_shift($argv);
				break;

			case '--types':
				$limittypes = explode(',', array_shift($argv));
				$limittypes = array_combine($limittypes, $limittypes);
				break;
			
			case '--sort':
				$sortcol = array_shift($argv);
				break;

			case '--proxy':
				$proxy = array_shift($argv);
				$proxies[$proxy] = $proxy;
				break;

			case '--proxylist':
				$proxylist = file(array_shift($argv));
				foreach($proxylist as $proxy){
					$proxy = trim($proxy);

					if(($pos = strpos($proxy, '#')) !== false)
						$proxy = trim(substr($proxy, 0, $pos));

					if($proxy)
						$proxies[$proxy] = $proxy;
				}
				break;

			case '--help':
				die("$name [-q] [-t timeout] [-A minlines] [-B maxlines] [-m minhits] [--threshhold % of max avg] [--types only,these,server,types]\n");

			default:
				die("Unknown Argument $arg\n");
		}
	}

	if($linesoutmin > $linesoutmax)
		$linesoutmax = $linesoutmin;


//////////////////////////////////

	if($proxies && $verbose){
		echo "Ignoring proxies:\n";
		foreach($proxies as $k => $v)
			echo "   " . str_pad($k, 16) . "# " . gethost($k) . "\n";
		echo "\n\n";
	}

	$types = array('total' => 0);

	$starttime = $time = gettime();
	
	$ips = array();
	$totals = $counts = $types;
	
	$avgmaxes = array();

	$highlist = array();
	
	$exit = false;

    pcntl_signal(SIGTERM, 'sig_handler');
    pcntl_signal(SIGINT,  'sig_handler');

	while(!$exit && ($line = fgets(STDIN))){
		$matches = null;
		//parse the line
		preg_match("/\d+ ([A-za-z0-9]+) (.+) \"([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})\" (.*)/", $line, $matches);

		if(!$matches)
			break;

		$servertype = $matches[1];
		$ip = $matches[3];

	//skip types that aren't known
		if(count($limittypes) && !isset($limittypes[$servertype]))
			continue;

	//initialize stuff...
		if(!isset($ips[$ip]))
			$ips[$ip] = array('total' => 0, $servertype => 0);

		if(!isset($ips[$ip][$servertype]))
			$ips[$ip][$servertype] = 0;

		if(!isset($counts[$servertype])){
			$counts[$servertype] = 0;
			$types[$servertype] = 0;
			$totals[$servertype] = 0;
		}

	//keep a per ip set of counters
		$ips[$ip]['total']++;
		$ips[$ip][$servertype]++;

	//keep per type counters
		$counts['total']++;
		$counts[$servertype]++;

	//keep per type counters
		$totals['total']++;
		$totals[$servertype]++;

		$newtime = gettime();
		if($newtime - $time > $timeout*1000){
		//sort the results
			uasort($ips, 'cmpips');

		//output a header and the totals
			$str = str_repeat(" ", 16); 
			foreach($types as $type => $blah)
				$str .= "$type ";
			$str .= "\n";

			$str .= str_pad("Total:", 16);
			foreach($types as $type => $blah){
				if(isset($counts[$type]))
					$str .= str_pad($counts[$type], strlen($type), " ", STR_PAD_LEFT) . " ";
				else
					$str .= str_repeat(" ", strlen($type)+1);
			}
			$str .= round($newtime - $time) . " ms\n";

		//avgmax is for the threshhold limiting
			$avgmax = (count($avgmaxes) ? array_sum($avgmaxes) / count($avgmaxes) : 0);

			$i = 1;
			$curtotal = 0;
			
			foreach($ips as $ip => $ipcounts){
			//ignore proxies
				if(isset($proxies[$ip]))
					continue;

				if(!isset($ipcounts[$sortcol]))
					break;

				if($i > $linesoutmin && ($ipcounts[$sortcol] < $minhits || $ipcounts[$sortcol] < $avgmax*$threshhold/100))
					break;

				if($i > $linesoutmax)
					break;


			//keep a list of ips shown, and their frequency
				if(!isset($highlist[$ip])){
					$highlist[$ip] = $types;
					$highlist[$ip]['num'] = 0;
				}

				$highlist[$ip]['num']++;
				foreach($types as $type => $blah)
					if(isset($ipcounts[$type]))
						$highlist[$ip][$type] += $ipcounts[$type];

			//total of the values shown, used for threshhold limiting
				if($i <= $linesoutmin)
					$curtotal += $ipcounts[$sortcol];

			//output a row
				$str .= str_pad($ip, 16);
				foreach($types as $type => $blah){
					if(isset($ipcounts[$type]))
						$str .= str_pad($ipcounts[$type], strlen($type), " ", STR_PAD_LEFT) . " ";
					else
						$str .= str_repeat(" ", strlen($type)+1);
				}
				if($verbose >= 2)
					$str .= gethost($ip);
				$str .= "\n";

				$i++;
			}
			echo "$str\n";

		//keep the averages for threshhold limiting
			if(count($avgmaxes) > 10)
				array_shift($avgmaxes);

			$avgmaxes[] = $curtotal/$i;

		//reset the variables
			$ips = array();
			$counts = $types;
			$total = 0;
			$time = $newtime;
		}
	}

//summary output for when the user kills the process
	uasort($highlist, 'cmpips');

//output a header and the totals
	$str = str_repeat(" ", 16);
	$str .= " num ";
	foreach($types as $type => $blah)
		$str .= "$type ";
	$str .= "\n";

	$str .= str_pad("Total:", 16);
	$str .= "     ";
	foreach($types as $type => $blah){
		if(isset($counts[$type]))
			$str .= str_pad($counts[$type], strlen($type), " ", STR_PAD_LEFT) . " ";
		else
			$str .= str_repeat(" ", strlen($type)+1);
	}
	$str .= round(($time - $starttime)/1000) . " s\n";


	$i = 1;
	foreach($highlist as $ip => $ipcounts){
		if($i > $linesoutmax)
			break;

	//output a row
		$str .= str_pad($ip, 16);
		$str .= str_pad($ipcounts['num'], 4, " ", STR_PAD_LEFT) . " ";
		foreach($types as $type => $blah){
			if(isset($ipcounts[$type]))
				$str .= str_pad($ipcounts[$type], strlen($type), " ", STR_PAD_LEFT) . " ";
			else
				$str .= str_repeat(" ", strlen($type)+1);
		}
		if($verbose >= 2)
			$str .= gethost($ip);
		$str .= "\n";

		$i++;
	}
	echo "$str\n";


/////////////////////////

function sig_handler(){
	global $exit;
	$exit = true;
}

function cmpips($a, $b){
	global $sortcol;

	if(!isset($a[$sortcol])) $a[$sortcol] = 0;
	if(!isset($b[$sortcol])) $b[$sortcol] = 0;

	return $b[$sortcol] - $a[$sortcol];
}

function gettime(){ //in ms
	return microtime(true)*1000;
}

//map an ip to a hostname
function gethost($ip){
	static $hosts = array(); //a LRU queue of ip => hostname mappings

	if(isset($hosts[$ip])){
		//move to the end of the list
		$val = $hosts[$ip];
		unset($hosts[$ip]);
		$hosts[$ip] = $val;

		return $val;
	}

	$hosts[$ip] = gethostbyaddr($ip);
	
	if(count($hosts) > 10000)
		array_shift($hosts); //take one off the front of the list
	
	return $hosts[$ip];
}
