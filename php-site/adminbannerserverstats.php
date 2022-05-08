<?

	$login=1;
	require_once("include/general.lib.php");

	if(!in_array($userData['userid'], $debuginfousers) && !$mods->isAdmin($userData['userid'],"listbanners"))
		die("error");


	$stats = array();
	
	foreach($banner->hosts as $host){

		$stat = array(	'Uptime' => 0,
						'Connect' => array(0,0,0),
						'Get' => array(0,0,0),
						'Click' => array(0,0,0),
						'Connections' => 0);

		
		$sock = fsockopen($host, BANNER_PORT, $errno, $errstr, 0.02); //20ms timeout
		
		fwrite($sock, "stats\n");

		$line = "";
		while($line = fgets($sock, 256)){
			if($line == "\n")
				break;

			$line = explode(":", $line);
			
			switch($line[0]){
				case "Uptime":
				case "Connections":
					$stat[$line[0]] = trim($line[1]);
					break;
				case "Connect":
				case "Get":
				case "Get Fail":
				case "Click":
					preg_match("/\s*(\d+)\s+(\d+)\s+(\d+)\s*/", $line[1], $matches);
					$stat[$line[0]][0] = $matches[1];
					$stat[$line[0]][1] = $matches[2];
					$stat[$line[0]][2] = $matches[3];
					break;
			}
		}
		
		fclose($sock);
		
		$stats[$host] = $stat;
	}


	incHeader();



	$n = count($stats);

	echo "<table align=center>";
	echo "<tr>";
	echo "<td class=header rowspan=2 align=center>$n Servers</td>";
	echo "<td class=header rowspan=2 align=center>Uptime</td>";
	echo "<td class=header colspan=5 align=center>Connections</td>";
	echo "<td class=header colspan=4 align=center>Gets</td>";
	echo "<td class=header colspan=4 align=center>Failed Gets</td>";
	echo "<td class=header colspan=4 align=center>Clicks</td>";
	echo "</tr>";
	
	echo "<tr>";
	echo "<td class=header align=center>Total</td>";
	echo "<td class=header align=center>Total/s</td>";
	echo "<td class=header align=center>Min</td>";
	echo "<td class=header align=center>Sec</td>";
	echo "<td class=header align=center>Cur</td>";
	
	echo "<td class=header align=center>Total</td>";
	echo "<td class=header align=center>Total/s</td>";
	echo "<td class=header align=center>Min</td>";
	echo "<td class=header align=center>Sec</td>";

	echo "<td class=header align=center>Total</td>";
	echo "<td class=header align=center>Total/s</td>";
	echo "<td class=header align=center>Min</td>";
	echo "<td class=header align=center>Sec</td>";

	echo "<td class=header align=center>Total</td>";
	echo "<td class=header align=center>Total/s</td>";
	echo "<td class=header align=center>Min</td>";
	echo "<td class=header align=center>Sec</td>";
	echo "</tr>";
	

	$total = array();
	
	$classes = array('body','body2');
	$i=0;

	foreach($stats as $host => $stat){

		if(!count($total)){
			$total = $stat;
		}else{
			foreach($stat as $a => $b){
				if(is_array($b)){
					foreach($b as $c => $d)
						$total[$a][$c] += $d;
				}else{
					$total[$a] += $b;
				}
			}
		}

		echo "<tr>";
		echo "<td class=header nowrap>$host</td>";
		
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . ($stat['Uptime'] < 86400 ? number_format($stat['Uptime']/3600, 2) . " hours" : number_format($stat['Uptime']/86400, 2) . " days" ) . "</td>";

		$i = !$i;
		
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($stat['Connect'][0]) . "</td>";
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($stat['Connect'][0]/$stat['Uptime'],2) . "</td>";
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($stat['Connect'][1]) . "</td>";
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($stat['Connect'][2]) . "</td>";
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($stat['Connections']) . "</td>";

		$i = !$i;

		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($stat['Get'][0]) . "</td>";
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($stat['Get'][0]/$stat['Uptime'],2) . "</td>";
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($stat['Get'][1]) . "</td>";
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($stat['Get'][2]) . "</td>";

		$i = !$i;

		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($stat['Get Fail'][0]) . "</td>";
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($stat['Get Fail'][0]/$stat['Uptime'],2) . "</td>";
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($stat['Get Fail'][1]) . "</td>";
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($stat['Get Fail'][2]) . "</td>";

		$i = !$i;

		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($stat['Click'][0]) . "</td>";
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($stat['Click'][0]/$stat['Uptime'],2) . "</td>";
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($stat['Click'][1]) . "</td>";
		echo "<td class=" . $classes[$i] . " align=right nowrap>" . number_format($stat['Click'][2]) . "</td>";

		echo "</tr>";
//		$i = !$i;
	}


	echo "<tr>";
	echo "<td class=header nowrap>Total</td>";
	
	echo "<td class=header align=right nowrap></td>";

	echo "<td class=header align=right nowrap>" . number_format($total['Connect'][0]) . "</td>";
	echo "<td class=header align=right nowrap>" . number_format($total['Connect'][0]*$n/$total['Uptime']) . "</td>";
	echo "<td class=header align=right nowrap>" . number_format($total['Connect'][1]) . "</td>";
	echo "<td class=header align=right nowrap>" . number_format($total['Connect'][2]) . "</td>";
	echo "<td class=header align=right nowrap>" . number_format($total['Connections']) . "</td>";

	echo "<td class=header align=right nowrap>" . number_format($total['Get'][0]) . "</td>";
	echo "<td class=header align=right nowrap>" . number_format($total['Get'][0]*$n/$total['Uptime']) . "</td>";
	echo "<td class=header align=right nowrap>" . number_format($total['Get'][1]) . "</td>";
	echo "<td class=header align=right nowrap>" . number_format($total['Get'][2]) . "</td>";

	echo "<td class=header align=right nowrap>" . number_format($total['Get Fail'][0]) . "</td>";
	echo "<td class=header align=right nowrap>" . number_format($total['Get Fail'][0]*$n/$total['Uptime']) . "</td>";
	echo "<td class=header align=right nowrap>" . number_format($total['Get Fail'][1]) . "</td>";
	echo "<td class=header align=right nowrap>" . number_format($total['Get Fail'][2]) . "</td>";

	echo "<td class=header align=right nowrap>" . number_format($total['Click'][0]) . "</td>";
	echo "<td class=header align=right nowrap>" . number_format($total['Click'][0]*$n/$total['Uptime']) . "</td>";
	echo "<td class=header align=right nowrap>" . number_format($total['Click'][1]) . "</td>";
	echo "<td class=header align=right nowrap>" . number_format($total['Click'][2]) . "</td>";

	echo "</tr>";


	echo "<tr>";
	echo "<td class=header nowrap>Average</td>";
	
	echo "<td class=header align=right nowrap>" . ($total['Uptime']/$n < 86400 ? number_format($total['Uptime']/(3600*$n), 2) . " hours" : number_format($total['Uptime']/(86400*$n), 2) . " days" ) . "</td>";

	echo "<td class=header align=right nowrap>" . number_format($total['Connect'][0]/$n) . "</td>";
	echo "<td class=header align=right nowrap>" . number_format($total['Connect'][0]/$total['Uptime']) . "</td>";
	echo "<td class=header align=right nowrap>" . number_format($total['Connect'][1]/$n) . "</td>";
	echo "<td class=header align=right nowrap>" . number_format($total['Connect'][2]/$n) . "</td>";
	echo "<td class=header align=right nowrap>" . number_format($total['Connections']/$n) . "</td>";

	echo "<td class=header align=right nowrap>" . number_format($total['Get'][0]/$n) . "</td>";
	echo "<td class=header align=right nowrap>" . number_format($total['Get'][0]/$total['Uptime']) . "</td>";
	echo "<td class=header align=right nowrap>" . number_format($total['Get'][1]/$n) . "</td>";
	echo "<td class=header align=right nowrap>" . number_format($total['Get'][2]/$n) . "</td>";

	echo "<td class=header align=right nowrap>" . number_format($total['Get Fail'][0]/$n) . "</td>";
	echo "<td class=header align=right nowrap>" . number_format($total['Get Fail'][0]/$total['Uptime']) . "</td>";
	echo "<td class=header align=right nowrap>" . number_format($total['Get Fail'][1]/$n) . "</td>";
	echo "<td class=header align=right nowrap>" . number_format($total['Get Fail'][2]/$n) . "</td>";

	echo "<td class=header align=right nowrap>" . number_format($total['Click'][0]/$n) . "</td>";
	echo "<td class=header align=right nowrap>" . number_format($total['Click'][0]/$total['Uptime']) . "</td>";
	echo "<td class=header align=right nowrap>" . number_format($total['Click'][1]/$n) . "</td>";
	echo "<td class=header align=right nowrap>" . number_format($total['Click'][2]/$n) . "</td>";

	echo "</tr>";

	echo "</table>";


	incFooter();
	
	
