#!/usr/local/php/bin/php
<?


//               reqtype  status   bytes    timetaken hostname         filename               uri               querystring remoteip  useragent userid       age          sex             loc          usertype
//accesslog.format = "%m %>s %B %T \"%{Host}i\" \"%f\" \"%U\" \"%q\" \"%a\" \"%{User-agent}i\" \"%{X-LIGHTTPD-userid}o\" \"%{X-LIGHTTPD-age}o\" \"%{X-LIGHTTPD-sex}o\" \"%{X-LIGHTTPD-loc}o\" \"%{X-LIGHTTPD-usertype}o\""
//accesslog.filename = "|/home/nexopia/logstats.php"

	$statsfile = "/home/nexopia/public_html/stats/" . trim(`hostname`) . ".";

	$starttime = $lasttime = $time = time();

	$weekly   = new logstats($statsfile . "weekly.html",   86400*7);
	$daily    = new logstats($statsfile . "daily.html" ,   86400);
	$hourly   = new logstats($statsfile . "hourly.html",   3600);
	$minutely = new logstats($statsfile . "minutely.html", 60);
	$fivesec  = new logstats($statsfile . "fivesec.html",  5);

	$statsobjs = array();
	$statsobj['weekly']   = & $weekly;
	$statsobj['daily']    = & $daily;
	$statsobj['hourly']   = & $hourly;
	$statsobj['minutely'] = & $minutely;
	$statsobj['fivesec']  = & $fivesec;

//run the processing loop
	while($line = trim(fgets(STDIN))){
		$time = time();
		
		if($time != $lasttime){
			foreach($statsobj as & $obj)
				$obj->dump($time);

			$lasttime = $time;
		}


		//               reqtype  status   bytes    timetaken hostname filename uri     querystring remoteip useragent userid age    sex      loc      usertype
		if(!preg_match("/(?U)([A-Z]+) ([0-9]+) ([0-9]+) ([0-9]+) \"(.*)\" \"(.*)\" \"(.*)\" \"(.*)\" \"(.*)\" \"(.*)\" \"(.*)\" \"(.*)\" \"(.*)\" \"(.*)\" \"(.*)\"/", $line, $matches)){
			trigger_error("$line                      ");
			continue;
		}
		
		list($fullline, $reqtype, $status, $bytes, $timetaken, $hostname, $filename, $uri, $querystring, $remoteip, $useragent, $userid, $age, $sex, $loc, $usertype) = $matches;
		
		
		if($querystring){
			$ret = parseGet($querystring); //parse it
			$uri .= '?';
			
			if(isset($ret['action'])){
				$uri .= "action=$ret[action]";
				unset($ret['action']);
				if(count($ret))
					$uri .= "&";
			}
			
			$uri .= implode('&', array_keys($ret)); //don't care about values, that makes too many uris. 
		}

		foreach($statsobj as & $obj)
			$obj->hit($reqtype, $status, $bytes, $timetaken, $hostname, $filename, $uri, $remoteip, $useragent, $userid, $age, $sex, $loc, $usertype);
	}


//////////////////////


class logstats {
	public $filename;
	public $dumpfreq;
	public $lastreset;

	public $files = array();  // array( file => hits, ... )
	public $uris = array();   // array( uri => hits, ... )
	public $status = array(); // array( status => hits, ... )

	public $remoteips = array(); //user ips
	public $hosts = array(); // array( host => hits)
	public $reqtype = array(); // ie GET, POST
	public $useragent = array();
	public $timetaken = array(); 
	
	public $userids = array();
	public $agesex = array();
	public $locs = array();
	public $usertype = array();

	public $totalpages = 0;
	public $totalbytes = 0;

	function __construct($filename, $dumpfreq){
		$this->filename = $filename;
		$this->dumpfreq = $dumpfreq;

		$this->reset();
	}
	
	function reset(){
		$this->files = array(); // array( status => array(files, ...), ...)
		$this->uris = array();
		$this->status = array(); // array( status => hits, ... )
	
		$this->remoteips = array(); //user ips
		$this->hosts = array(); // array( host => hits)
		$this->reqtype = array(); // ie GET, POST
		$this->useragent = array();
		$this->timetaken = array();
	
		$this->userids = array();
		$this->agesex = array();
		$this->locs = array();
		$this->usertype = array('anon' => 0, 'user' => 0, 'plus' => 0);

		$this->totalpages = 0;
		$this->totalbytes = 0;

		$this->lastreset = time();
	}

	function hit($reqtype, $status, $bytes, $timetaken, $hostname, $filename, $uri, $remoteip, $useragent, $userid, $age, $sex, $loc, $usertype){
		$this->totalbytes += $bytes;
		$this->totalpages++;

		if(!isset($this->reqtype[$reqtype]))
			$this->reqtype[$reqtype] = 0;
		$this->reqtype[$reqtype]++;

		if(!isset($this->status[$status]))
			$this->status[$status] = 0;
		$this->status[$status]++;

		if(!isset($this->timetaken[$timetaken]))
			$this->timetaken[$timetaken] = 0;
		$this->timetaken[$timetaken]++;
		
		if(!isset($this->hosts[$hostname]))
			$this->hosts[$hostname] = 0;
		$this->hosts[$hostname]++;

		if(!isset($this->files[$filename]))
			$this->files[$filename] = 0;
		$this->files[$filename]++;

		if(!isset($this->uris[$uri]))
			$this->uris[$uri] = 0;
		$this->uris[$uri]++;
		
		if(!isset($this->remoteips[$remoteip]))
			$this->remoteips[$remoteip] = 0;
		$this->remoteips[$remoteip]++;

		if(!isset($this->useragent[$useragent]))
			$this->useragent[$useragent] = 0;
		$this->useragent[$useragent]++;

		if($userid && $userid != '-'){
			if(!isset($this->userids[$userid]))
				$this->userids[$userid] = 0;
			$this->userids[$userid]++;	
		}

		if($age & $age != '-' && $sex && $sex != '-'){
			if(!isset($this->agesex[$age]))
				$this->agesex[$age] = array('Male' => 0, 'Female' => 0);
			$this->agesex[$age][$sex]++;	
		}

		if($loc && $loc != '-'){
			if(!isset($this->locs[$loc]))
				$this->locs[$loc] = 0;
			$this->locs[$loc]++;	
		}

		if($usertype && $usertype != '-')
			$this->usertype[$usertype]++;	
	}
	
	function dump($time){
		if($time - $this->lastreset < $this->dumpfreq)
			return false;
	
		$cssclasses = array('body','body2');

		$output = "<html><head><title>Stats</title>";
		
		$output .= "<style>";
		$output .= "body { font: 10pt arial; color: #000000 }\n";
		$output .= "td.header { background-color: #F8B623; font-weight: bolder; font: 10pt arial; color: #000000}\n";
		$output .= "td.body { background-color: #EFEFEF; font: 10pt arial; color: #000000}\n";
		$output .= "td.body2 { background-color: #DFDFDF; font: 10pt arial; color: #000000}\n";
		$output .= "</style>\n";
		
		$output .= "</head><body>";


		$c = 0; //current cssclass

//general
		$output .= "<table cellspacing=1>";
		$output .= "<tr><td class=header colspan=2 align=center><b>General</b></td></tr>";
		$output .= "<tr><td class=header>Time Period</td><td class=" . $cssclasses[$c = !$c] . " align=right>" . date("M d, H:i:s", $this->lastreset) . " - " . date("M d, H:i:s") . "</td></tr>";
		$output .= "<tr><td class=header>Time Length</td><td class=" . $cssclasses[$c = !$c] . " align=right>" . number_format($this->dumpfreq) . " seconds</td></tr>";
		$output .= "<tr><td class=header>Total Pages</td><td class=" . $cssclasses[$c = !$c] . " align=right>" . number_format($this->totalpages) . "</td></tr>";
		$output .= "<tr><td class=header>Total Bytes</td><td class=" . $cssclasses[$c = !$c] . " align=right>" . number_format($this->totalbytes) . "</td></tr>";
		$output .= "</table>";
		$output .= "<br><br>";

	//files
		arsort($this->files);

		$output .= "<table cellspacing=1>";
		$output .= "<tr><td class=header colspan=2 align=center><b>Hits per file (top 100)</b></td></tr>";

		$i = 0;
		foreach($this->files as $file => $hits)
			if(++$i <= 100)
				$output .= "<tr><td class=" . $cssclasses[$c = !$c] . ">$file</td><td class=" . $cssclasses[$c] . " align=right>" . number_format($hits) . "</td></tr>";
		$output .= "<tr><td class=header colspan=2>" . number_format(count($this->files)) . " Files counted</td></tr>";
		$output .= "</table>";
		$output .= "<br><br>";
		

	//uris
		arsort($this->uris);

		$output .= "<table cellspacing=1>";
		$output .= "<tr><td class=header colspan=2 align=center><b>Hits per URI (get values cleaned) (top 100)</b></td></tr>";
		
		$i = 0;
		foreach($this->uris as $uri => $hits)
			if(++$i <= 100)
				$output .= "<tr><td class=" . $cssclasses[$c = !$c] . ">$uri</td><td class=" . $cssclasses[$c] . " align=right>" . number_format($hits) . "</td></tr>";
		$output .= "<tr><td class=header colspan=2>" . number_format(count($this->uris)) . " URIs counted</td></tr>";
		$output .= "</table>";
		$output .= "<br><br>";
	
	
	//status
		ksort($this->status);

		$output .= "<table cellspacing=1>";
		$output .= "<tr><td class=header colspan=2 align=center><b>Return Status</b></td></tr>";
		foreach($this->status as $status => $hits)
			$output .= "<tr><td class=" . $cssclasses[$c = !$c] . ">$status</td><td class=" . $cssclasses[$c] . " align=right>" . number_format($hits) . "</td></tr>";
		$output .= "</table>";
		$output .= "<br><br>";


	//ips
		arsort($this->remoteips);

		$output .= "<table cellspacing=1>";
		$output .= "<tr><td class=header colspan=2 align=center><b>Active IPs (top 100)</b></td></tr>";
		$i = 0;
		foreach($this->remoteips as $remoteip => $hits)
			if(++$i <= 100)
				$output .= "<tr><td class=" . $cssclasses[$c = !$c] . ">$remoteip</td><td class=" . $cssclasses[$c] . " align=right>" . number_format($hits) . "</td></tr>";
		$output .= "<tr><td class=header colspan=2>" . number_format(count($this->remoteips)) . " IPs counted</td></tr>";
		$output .= "</table>";
		$output .= "<br><br>";


	//hostname
		arsort($this->hosts);

		$output .= "<table cellspacing=1>";
		$output .= "<tr><td class=header colspan=2 align=center><b>Active Hostnames</b></td></tr>";
		foreach($this->hosts as $host => $hits)
			$output .= "<tr><td class=" . $cssclasses[$c = !$c] . ">$host</td><td class=" . $cssclasses[$c] . " align=right>" . number_format($hits) . "</td></tr>";
		$output .= "</table>";
		$output .= "<br><br>";


	//req type
		ksort($this->reqtype);

		$output .= "<table cellspacing=1>";
		$output .= "<tr><td class=header colspan=2 align=center><b>Request Types</b></td></tr>";
		foreach($this->reqtype as $reqtype => $hits)
			$output .= "<tr><td class=" . $cssclasses[$c = !$c] . ">$reqtype</td><td class=" . $cssclasses[$c] . " align=right>" . number_format($hits) . "</td></tr>";
		$output .= "</table>";
		$output .= "<br><br>";


	//User Agent
		arsort($this->useragent);

		$output .= "<table cellspacing=1>";
		$output .= "<tr><td class=header colspan=2 align=center><b>User Agent (top 100)</b></td></tr>";
		$i = 0;
		foreach($this->useragent as $useragent => $hits)
			if(++$i <= 100)
				$output .= "<tr><td class=" . $cssclasses[$c = !$c] . ">$useragent</td><td class=" . $cssclasses[$c] . " align=right>" . number_format($hits) . "</td></tr>";
		$output .= "<tr><td class=header colspan=2>" . number_format(count($this->useragent)) . " User Agents counted</td></tr>";
		$output .= "</table>";
		$output .= "<br><br>";


	//response time
		ksort($this->timetaken);

		$output .= "<table cellspacing=1>";
		$output .= "<tr><td class=header colspan=2 align=center><b>Response Time</b></td></tr>";
		$i = 0;
		$over = 0;
		foreach($this->timetaken as $timetaken => $hits){
			if(++$i <= 60)
				$output .= "<tr><td class=" . $cssclasses[$c = !$c] . ">$timetaken sec</td><td class=" . $cssclasses[$c] . " align=right>" . number_format($hits) . "</td></tr>";
			else
				$over += $hits;
		}
		if($over)
			$output .= "<tr><td class=header>Over 60</td><td class=header>$over</td></tr>";

		$output .= "</table>";
		$output .= "<br><br>";
		

	//users
		arsort($this->userids);

		$output .= "<table cellspacing=1>";
		$output .= "<tr><td class=header colspan=2 align=center><b>User hits (top 100 userids)</b></td></tr>";
		$i = 0;
		foreach($this->userids as $userid => $hits)
			if(++$i <= 100)
				$output .= "<tr><td class=" . $cssclasses[$c = !$c] . ">$userid</td><td class=" . $cssclasses[$c] . " align=right>" . number_format($hits) . "</td></tr>";
		$output .= "<tr><td class=header colspan=2>" . number_format(count($this->userids)) . " Userids counted</td></tr>";
		$output .= "</table>";
		$output .= "<br><br>";


	//age/sex
		ksort($this->agesex);

		$output .= "<table cellspacing=1>";
		$output .= "<tr><td class=header colspan=3 align=center><b>User hits by age</b></td></tr>";
		$output .= "<tr><td class=header>Age</td><td class=header>Male</td><td class=header>Female</td></tr>";
		foreach($this->agesex as $age => $sexhits)
			$output .= "<tr><td class=" . $cssclasses[$c = !$c] . ">$age</td><td class=" . $cssclasses[$c] . " align=right>" . number_format($sexhits['Male']) . "</td><td class=" . $cssclasses[$c] . " align=right>" . number_format($sexhits['Female']) . "</td></tr>";
		$output .= "</table>";
		$output .= "<br><br>";


	//locs
		arsort($this->locs);

		$output .= "<table cellspacing=1>";
		$output .= "<tr><td class=header colspan=2 align=center><b>Location hits (top 100)</b></td></tr>";
		$i = 0;
		foreach($this->locs as $loc => $hits)
			if(++$i <= 100)
				$output .= "<tr><td class=" . $cssclasses[$c = !$c] . ">$loc</td><td class=" . $cssclasses[$c] . " align=right>" . number_format($hits) . "</td></tr>";
		$output .= "<tr><td class=header colspan=2>" . number_format(count($this->locs)) . " Locations counted</td></tr>";
		$output .= "</table>";
		$output .= "<br><br>";


	//user type
		$output .= "<table cellspacing=1>";
		$output .= "<tr><td class=header colspan=2 align=center><b>User Types</b></td></tr>";
		$output .= "<tr><td class=header>Anonymous</td><td class=" . $cssclasses[$c = !$c] . " align=right>" . number_format($this->usertype['anon']) . "</td></tr>";
		$output .= "<tr><td class=header>Normal User</td><td class=" . $cssclasses[$c = !$c] . " align=right>" . number_format($this->usertype['user']) . "</td></tr>";
		$output .= "<tr><td class=header>Plus User</td><td class=" . $cssclasses[$c = !$c] . " align=right>" . number_format($this->usertype['plus']) . "</td></tr>";
		$output .= "</table>";
		$output .= "<br><br>";


		
		file_put_contents($this->filename, $output);
		
		$this->reset();
	}
}


function parseGET($args){ //doesn't handle multi-dimensional arrays yet
	$vals = array();
	$temp = explode('&', $args);
	foreach($temp as $v){
		if($pos = strpos($v, '=')){
			list($k, $v) = explode('=', $v);
		}else{
			$k = $v;
			$v = '';
		}
		
		$k = preg_replace("/[^a-zA-Z0-9_\[\]]/","", urldecode($k));
		$v = urldecode($v);
		
		if(preg_match("/^([a-zA-Z][a-zA-Z0-9_]*)(\[([a-zA-Z0-9_]*)\])?\$/", $k, $matches)){
			if(isset($matches[2])){ //is an array
				if($matches[3]){ //named/numbered key
					$vals[$matches[1]][$matches[3]] = $v;
				}else{
					$vals[$matches[1]][] = $v;
				}
			}else{
				$vals[$matches[1]] = $v;
			}
		}
	}
	
	return $vals;
}

