<?

	$login=1;
	require_once("include/general.lib.php");

	if($userData['userid']!=5 && $userData['userid']!=1)
		die("error");


	incHeader();


	dumpStats('cache', $memcache->get_stats());

	dumpStats('pages', $pagememcache->get_stats());


	incFooter();


function dumpStats($name, $stats){

	$n = count($stats);

	echo "$name<br>";
	echo "<table width=100%>";
	echo "<tr>";
	echo "<td class=header>$n Servers</td>";
	echo "<td class=header>Uptime</td>";
	echo "<td class=header>Cur Items</td>";
	echo "<td class=header>Size MB</td>";
	echo "<td class=header>Max Size</td>";
	echo "<td class=header>Usage</td>";
	echo "<td class=header>Read MB</td>";
	echo "<td class=header>Read KB/s</td>";
	echo "<td class=header>Written MB</td>";
	echo "<td class=header>Written KB/s</td>";
	echo "<td class=header>Cur Con</td>";
	echo "<td class=header>Con</td>";
	echo "<td class=header>Con/s</td>";
	echo "<td class=header>gets</td>";
	echo "<td class=header>get misses</td>";
	echo "<td class=header>gets/s</td>";
	echo "<td class=header>sets</td>";
	echo "<td class=header>sets/s</td>";
	echo "<td class=header>gets/sets</td>";
	echo "<td class=header>hit ratio</td>";
	echo "</tr>";

	$total = array();

	foreach($stats as $server => $stat){
		list($ip, $port) = explode(":", $server);

		if(!count($total))
			$total = $stat;
		else
			foreach($stat as $k => $num)
				$total[$k] += $num;

		echo "<tr>";
		echo "<td class=header>$ip</td>";
		echo "<td class=body align=right>" . ($stat['uptime'] < 86400 ? number_format($stat['uptime']/3600, 2) . " hours" : number_format($stat['uptime']/86400, 2) . " days" ) . "</td>";
		echo "<td class=body align=right>" . number_format($stat['curr_items']) . "</td>";
		echo "<td class=body align=right>" . number_format($stat['bytes']/(1024*1024), 2) . "</td>";
		echo "<td class=body align=right>" . number_format($stat['limit_maxbytes']/(1024*1024)) . " MB</td>";
		echo "<td class=body align=right>" . number_format(100*$stat['bytes']/$stat['limit_maxbytes'], 2) . "%</td>";
		echo "<td class=body align=right>" . number_format($stat['bytes_read']/(1024*1024), 2) . "</td>";
		echo "<td class=body align=right>" . number_format($stat['bytes_read']/(1024*$stat['uptime']), 2) . "</td>";
		echo "<td class=body align=right>" . number_format($stat['bytes_written']/(1024*1024), 2) . "</td>";
		echo "<td class=body align=right>" . number_format($stat['bytes_written']/(1024*$stat['uptime']), 2) . "</td>";
		echo "<td class=body align=right>" . number_format($stat['curr_connections']) . "</td>";
		echo "<td class=body align=right>" . number_format($stat['total_connections']) . "</td>";
		echo "<td class=body align=right>" . number_format($stat['total_connections']/$stat['uptime'], 2) . "</td>";
		echo "<td class=body align=right>" . number_format($stat['cmd_get']) . "</td>";
		echo "<td class=body align=right>" . number_format($stat['get_misses']) . "</td>";
		echo "<td class=body align=right>" . number_format($stat['cmd_get']/$stat['uptime'], 2) . "</td>";
		echo "<td class=body align=right>" . number_format($stat['cmd_set']) . "</td>";
		echo "<td class=body align=right>" . number_format($stat['cmd_set']/$stat['uptime'], 2) . "</td>";
		echo "<td class=body align=right>" . number_format($stat['cmd_get']/$stat['cmd_set'], 2) . "</td>";
		echo "<td class=body align=right>" . number_format(100*(1 - $stat['get_misses']/$stat['cmd_get']), 2) . " %</td>";
		echo "</tr>";
	}

	echo "<tr>";
	echo "<td class=header>Total:</td>";
	echo "<td class=header align=right></td>";
	echo "<td class=header align=right>" . number_format($total['curr_items']) . "</td>";
	echo "<td class=header align=right>" . number_format($total['bytes']/(1024*1024), 2) . "</td>";
	echo "<td class=header align=right>" . number_format($total['limit_maxbytes']/(1024*1024)) . " MB</td>";
	echo "<td class=header align=right></td>";
	echo "<td class=header align=right>" . number_format($total['bytes_read']/(1024*1024), 2) . "</td>";
	echo "<td class=header align=right>" . number_format($total['bytes_read']*$n/(1024*$total['uptime']), 2) . "</td>";
	echo "<td class=header align=right>" . number_format($total['bytes_written']/(1024*1024), 2) . "</td>";
	echo "<td class=header align=right>" . number_format($total['bytes_written']*$n/(1024*$total['uptime']), 2) . "</td>";
	echo "<td class=header align=right>" . number_format($total['curr_connections']) . "</td>";
	echo "<td class=header align=right>" . number_format($total['total_connections']) . "</td>";
	echo "<td class=header align=right>" . number_format($total['total_connections']*$n/$total['uptime'], 2) . "</td>";
	echo "<td class=header align=right>" . number_format($total['cmd_get']) . "</td>";
	echo "<td class=header align=right>" . number_format($total['get_misses']) . "</td>";
	echo "<td class=header align=right>" . number_format($total['cmd_get']*$n/$total['uptime'], 2) . "</td>";
	echo "<td class=header align=right>" . number_format($total['cmd_set']) . "</td>";
	echo "<td class=header align=right>" . number_format($total['cmd_set']*$n/$total['uptime'], 2) . "</td>";
	echo "<td class=header align=right></td>";
	echo "<td class=header align=right></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td class=header>Average</td>";
	echo "<td class=header align=right>" . ($total['uptime']/$n < 86400 ? number_format(($total['uptime']/3600)/$n, 2) . " hours" : number_format(($total['uptime']/86400)/$n, 2) . " days" ) . "</td>";
	echo "<td class=header align=right>" . number_format($total['curr_items']/$n) . "</td>";
	echo "<td class=header align=right>" . number_format($total['bytes']/(1024*1024*$n), 2) . "</td>";
	echo "<td class=header align=right>" . number_format($total['limit_maxbytes']/(1024*1024*$n)) . " MB</td>";
	echo "<td class=header align=right>" . number_format(100*$total['bytes']/$total['limit_maxbytes'], 2) . "%</td>";
	echo "<td class=header align=right>" . number_format($total['bytes_read']/(1024*1024*$n), 2) . "</td>";
	echo "<td class=header align=right>" . number_format($total['bytes_read']/(1024*$total['uptime']), 2) . "</td>";
	echo "<td class=header align=right>" . number_format($total['bytes_written']/(1024*1024*$n), 2) . "</td>";
	echo "<td class=header align=right>" . number_format($total['bytes_written']/(1024*$total['uptime']), 2) . "</td>";
	echo "<td class=header align=right>" . number_format($total['curr_connections']/$n) . "</td>";
	echo "<td class=header align=right>" . number_format($total['total_connections']/$n) . "</td>";
	echo "<td class=header align=right>" . number_format($total['total_connections']/($total['uptime']*$n), 2) . "</td>";
	echo "<td class=header align=right>" . number_format($total['cmd_get']/$n) . "</td>";
	echo "<td class=header align=right>" . number_format($total['get_misses']/$n) . "</td>";
	echo "<td class=header align=right>" . number_format($total['cmd_get']/$total['uptime'], 2) . "</td>";
	echo "<td class=header align=right>" . number_format($total['cmd_set']/$n) . "</td>";
	echo "<td class=header align=right>" . number_format($total['cmd_set']/$total['uptime'], 2) . "</td>";
	echo "<td class=header align=right>" . number_format($total['cmd_get']/$total['cmd_set'], 2) . "</td>";
	echo "<td class=header align=right>" . number_format(100*(1 - $total['get_misses']/$total['cmd_get']), 2) . " %</td>";
	echo "</tr>";


	echo "</table>";
	echo "<br>";
}
