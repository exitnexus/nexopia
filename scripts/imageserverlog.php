#!/usr/local/php/bin/php
<?

	include("fileserver.php");

	$statsfile = "/home/nexopia/public_html/stats.html";
	$scriptbasedir = dirname($_SERVER["PATH_TRANSLATED"]);

//status, size, time taken, file, uri, host
//LoadModule log_config_module modules/mod_log_config.so
//LogFormat "%>s %B %T %f %U" imgserver
//CustomLog "|/home/timo/nexopia/trunk/public_html/imageserverlog.php" imgserver

	$fs = new fileserver();
	$laststatstime = $lasttime = $time = time();

//init stats
	$numreq = 0;
	$totalsize = 0;
	$slow = 0;
	$fetchtotal = 0;
	$fetched = array();
	$statuss = array();
	$nums = array();
	$sizes = array();
	
	$interestlog = array();

	$cssclasses = array('body','body2');

//used to create the stats arrays
	$classes = $fs->classes;
	foreach($classes as $class => $v)
		$classes[$class] = 0;
	$classes['other'] = 0;

//macro rate limiting, stops it from getting the same file multiple times within 2 minutes
	$fetch1 = array();
	$fetch2 = array();

//micro rate limiting, get a max of X per second and never have a queue length longer than Y
	$fetchqueue = array();
	$fetchsecond = 0;      //how many were fetched this second
	$maxfetchsecond = 20;
	$maxqueuelength = 1000;

//drive failure detection
	$drivefailtolerance = 100; //number of 403 errors per minute before it suspects a failed drive

//run the processing loop
	while($line = trim(fgets(STDIN))){
		$time = time();

		if($time != $lasttime){
			$fetchsecond = 0;
		}
	
		list($status, $size, $reqtime, $file, $uri) = explode(" ", $line);


		$class = $fs->getClass($uri);

		if($class){ //ie if it's a known format
		
		//if it's a missing file, go get it
			if(substr($uri, -1) != '/' && ($status == '404' || $status == '403')){
			
			//don't get the same file twice within a 2 minutes
			//really popular files that don't exist won't get out of this loop (think /favicon.ico)
			
				if(!isset($fetch1[$uri])){
					$fetch1[$uri] = 1;

					if(!isset($fetch2[$uri]) && count($fetchqueue) < $maxqueuelength){
						$fetchqueue[$uri] = $uri;
					}
				}
			}
		}else{
			$class = 'other';
		}

	//process the fetch queue, rate limited per second, and only one request per accesslog entry
		if(count($fetchqueue) && $fetchsecond < $maxfetchsecond){
			$fetchuri = array_shift($fetchqueue);

			exec("$scriptbasedir/imageservergetfile.php $fetchuri > /dev/null &");

			$fetchsecond++;

		//fetch stats
			$fetchtotal++;

			if(!isset($fetched[$class]))
				$fetched[$class] = 0;
			$fetched[$class]++;
		}


	//basic stats
		if($reqtime > 1) //if takes more than 1 second
			$slow++;
		
		$numreq++;
		$totalsize += $size;

	//advanced stats
		if(!isset($statuss[$status])){
			$statuss[$status] = array('num' => 0, 'size' => 0);
			$nums[$status] = $classes;
			$sizes[$status] = $classes;
		}

		$statuss[$status]['num']++;
		$statuss[$status]['size']+=$size;

		$nums[$status][$class]++;
		$sizes[$status][$class]+=$size;

	//interest log
		if($status == '403' || $class == 'other'){
			if(!isset($interestlog[$uri]))
				$interestlog[$uri] = array('num' => 0, 'line' => $line);
		
			$interestlog[$uri]['num']++;
		}

	 //dump stats about once per minute, must be last section as it overwrites earlier variables
		if($time - $laststatstime >= 60){
		
		//header
			$output = "<html><head><title>Stats</title>";
			
			$output .= "<style>";
			$output .= "body { font: 10pt arial; color: #000000 }\n";
			$output .= "td.header { background-color: #F8B623; font-weight: bolder; font: 10pt arial; color: #000000}\n";
			$output .= "td.body { background-color: #EFEFEF; font: 10pt arial; color: #000000}\n";
			$output .= "td.body2 { background-color: #DFDFDF; font: 10pt arial; color: #000000}\n";
			$output .= "</style>\n";
			
			$output .= "</head><body>";
		
		//basics
			$i = 0;

			$output .= "<table cellspacing=1>";
			$output .= "<tr><td class=header>Time</td><td class=" . $cssclasses[$i = !$i] . " align=right>" . date("M d, H:i:s", $time) . "</td></tr>";
			$output .= "<tr><td class=header>Requests</td><td class=" . $cssclasses[$i = !$i] . " align=right>$numreq</td></tr>";
			$output .= "<tr><td class=header>Bytes</td><td class=" . $cssclasses[$i = !$i] . " align=right>$totalsize</td></tr>";
			$output .= "<tr><td class=header>Slow requests</td><td class=" . $cssclasses[$i = !$i] . " align=right>$slow</td></tr>";
			$output .= "<tr><td class=header>Fetched</td><td class=" . $cssclasses[$i = !$i] . " align=right>$fetchtotal</td></tr>";
			$output .= "<tr><td class=header>Fetch attempts</td><td class=" . $cssclasses[$i = !$i] . " align=right>" . count($fetch1) . "</td></tr>";
			$output .= "<tr><td class=header>Fetch queue len</td><td class=" . $cssclasses[$i = !$i] . " align=right>" . count($fetchqueue) . "</td></tr>";
			$output .= "</table>";

			$output .= "<br>";

		//request counts and sizes by class and response status
			$output .= "<table cellspacing=1>";

			ksort($statuss);

			$first = true;
			
			$i = 0;
			$colwidth = 140;

			foreach($classes as $class => $temp){
				if($first){
					$output .= "<tr><td class=header>&nbsp;</td>";
					$output .= "<td class=header align=center>Fetch</td>";
					foreach($statuss as $status => $temp)
						$output .= "<td colspan=2 align=center width=$colwidth class=header>$status</td>";
					$output .= "<td colspan=2></td>";
					$output .= "</tr>";
					
					$first = false;
				}

				$output .= "<tr>";
				$output .= "<td class=header>$class</td>";
				$output .= "<td align=right width=" . floor($colwidth/2) . " class=" . $cssclasses[$i] . ">" . (isset($fetched[$class]) && $fetched[$class] ? $fetched[$class] : '') . "</td>";
				$num = 0;
				$size = 0;
				foreach($statuss as $status => $temp){
					$output .= "<td align=right width=" . floor($colwidth/2) . " class=" . $cssclasses[$i] . ">" . ($nums[$status][$class] ? $nums[$status][$class] : ''). "</td>";
					$output .= "<td align=right width=" . floor($colwidth/2) . " class=" . $cssclasses[$i] . ">" . ($nums[$status][$class] ? floor($sizes[$status][$class]/1024) . " KB" : '') . "</td>";
					
					$num += $nums[$status][$class];
					$size += $sizes[$status][$class];
				}
				$output .= "<td align=right width=" . floor($colwidth/2) . " class=header>" . ($num ? $num : ''). "</td>";
				$output .= "<td align=right width=" . floor($colwidth/2) . " class=header>" . ($size ? floor($size/1024) . " KB" : '') . "</td>";
				$output .= "</tr>";

				$i = !$i;
			}
			
			$output .= "<tr>";
			$output .= "<td></td>";
			$output .= "<td align=right width=" . floor($colwidth/2) . " class=header>" . ($fetchtotal ? $fetchtotal : '') . "</td>";
			foreach($statuss as $status => $line){
				$output .= "<td align=right width=" . floor($colwidth/2) . " class=header>" . ($line['num'] ? $line['num'] : ''). "</td>";
				$output .= "<td align=right width=" . floor($colwidth/2) . " class=header>" . ($line['size'] ? floor($line['size']/1024) . " KB" : '') . "</td>";
			}
			$output .= "<td colspan=2></td>";
			$output .= "</tr>";

			$output .= "</table>";

			$output .= "<br>";
			
		//dump of the interestlog
			if(count($interestlog)){
				$output .= "<table cellspacing=1>";
				$output .= "<tr>";
				$output .= "<td class=header align=center>Num Req</td>";
				$output .= "<td class=header align=center>Status</td>";
				$output .= "<td class=header align=center>Size</td>";
				$output .= "<td class=header align=center>File</td>";
				$output .= "<td class=header align=center>URI</td>";
				$output .= "</tr>";
				
				$i = 0;
				foreach($interestlog as $line){
					list($status, $size, $reqtime, $file, $uri) = explode(" ", $line['line']);
					
					$output .= "<tr>";
					$output .= "<td class=" . $cssclasses[$i] . " align=right>$line[num]</td>";
					$output .= "<td class=" . $cssclasses[$i] . " align=right>$status</td>";
					$output .= "<td class=" . $cssclasses[$i] . " align=right>$size</td>";
					$output .= "<td class=" . $cssclasses[$i] . ">$file</td>";
					$output .= "<td class=" . $cssclasses[$i] . ">$uri</td>";
					$output .= "</tr>";
	
					$i = !$i;
				}
				
				$output .= "</table>";
			}

			$output .= "</body></html>";

			file_put_contents($statsfile, $output);

		//check drive integrity if needed
			if(isset($statuss['403']) && $statuss['403']['num'] > $drivefailtolerance){
//				exec("$scriptbasedir/imageservertestdrives.php > /dev/null &");
			}


			$numreq = 0;
			$totalsize = 0;
			$slow = 0;
			$fetched = 0;
			$fetchtotal = 0;
			$fetched = array();
			$nums = array();
			$sizes = array();
			$statuss = array();
			$interestlog = array();
			
			$fetch2 = $fetch1;
			$fetch1 = array();
			
			$laststatstime = $time;
		}

		$lasttime = $time;
	}

