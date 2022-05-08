<?

	$login=1;
	require_once("include/general.lib.php");

	if(!in_array($userData['userid'], $debuginfousers))
		die("error");

	$caches = array(
		'cache' => & $memcache,
		'pages' => & $pagememcache
		);


	$timediff = getREQval('timediff', 'int', 1);

	if($timediff < 0)
		$timediff = 1;
	if($timediff > 30)
		$timediff = 30;
	
	incHeader();

	echo "<script src=$config[jsloc]sorttable.js></script>";

	echo "Stats taken over the past $timediff second" . ($timediff != 1 ? 's' : '') . ". ";
	echo "Redo with time period of: ";
	foreach(array(0,1,2,3,5,10,15,20,25,30) as $period)
		echo "<a class=body href=$_SERVER[PHP_SELF]?timediff=$period>$period" . "s</a> ";
	echo "<br><br>";

	foreach($caches as $cachename => & $cacheobj)
		$stats[$cachename][0] = $cacheobj->get_stats();

	sleep($timediff);

	foreach($caches as $cachename => & $cacheobj)
		$stats[$cachename][1] = $cacheobj->get_stats();

	if($timediff == 0)
		foreach($caches as $cachename => & $cacheobj)
			foreach($stats[$cachename][0] as $k => $v)
				$stats[$cachename][0][$k] = 0;

	foreach($caches as $cachename => & $cacheobj)
		dumpStats($cachename, $stats[$cachename], $timediff);

	incFooter();

/////////////////////////////

function dumpStats($name, $stats, $timediff){
	$before = $stats[0];
	$stats = $stats[1];

	$n = count($stats);

	echo "$name<br>";
	echo "<table width=100% class=sortable>";
	echo "<tr>";
	echo "<td class=header align=center nowrap>$n Servers</td>";
	echo "<td class=header align=center nowrap>Uptime</td>";
	echo "<td class=header align=center nowrap>Cur Items</td>";
	echo "<td class=header align=center nowrap>Size MB</td>";
	echo "<td class=header align=center nowrap>Max Size</td>";
	echo "<td class=header align=center nowrap>Usage</td>";
	echo "<td class=header align=center nowrap>Read MB</td>";
	echo "<td class=header align=center nowrap>Read KB/s</td>";
	echo "<td class=header align=center nowrap>Written MB</td>";
	echo "<td class=header align=center nowrap>Written KB/s</td>";
	echo "<td class=header align=center nowrap>Cur Con</td>";
	echo "<td class=header align=center nowrap>Con</td>";
	echo "<td class=header align=center nowrap>Con/s</td>";
	echo "<td class=header align=center nowrap>gets</td>";
	echo "<td class=header align=center nowrap>get misses</td>";
	echo "<td class=header align=center nowrap>gets/s</td>";
	echo "<td class=header align=center nowrap>sets</td>";
	echo "<td class=header align=center nowrap>sets/s</td>";
	echo "<td class=header align=center nowrap>gets/sets</td>";
	echo "<td class=header align=center nowrap>hit ratio</td>";
	echo "<td class=header align=center nowrap>evicts</td>";
	echo "<td class=header align=center nowrap>evicts/s</td>";
	echo "<td class=header align=center nowrap>evict ratio</td>";
	echo "</tr>";

	$total = array();
	$totalbef = array();
	$totaluptime = 0;

	$i = 0;
	$classes = array('body', 'body2');

	foreach($stats as $server => $stat){
		$bef = $before[$server];
	
		list($ip, $port) = explode(":", $server);

		if(!count($total)){
			$total = $stat;
			$totalbef = $bef;
		}else{
			foreach($stat as $k => $num)
				$total[$k] += $num;

			foreach($bef as $k => $num)
				$totalbef[$k] += $num;
		}
		$totaluptime += $stats[$server]['uptime'];

		$td = ($timediff ? $timediff : $stats[$server]['uptime']);

		echo "<tr>";
		echo "<td class=header nowrap>$ip</td>";
		echo "<td class=$classes[$i] align=right nowrap>" . ($stat['uptime'] < 86400 ? number_format($stat['uptime']/3600, 2) . " hours" : number_format($stat['uptime']/86400, 2) . " days" ) . "</td>";
		echo "<td class=$classes[$i] align=right nowrap>" . number_format($stat['curr_items']) . "</td>";
		echo "<td class=$classes[$i] align=right nowrap>" . number_format($stat['bytes']/(1024*1024), 2) . "</td>";
		echo "<td class=$classes[$i] align=right nowrap>" . number_format($stat['limit_maxbytes']/(1024*1024)) . " MB</td>";
		echo "<td class=$classes[$i] align=right nowrap>" . number_format(100*$stat['bytes']/$stat['limit_maxbytes'], 2) . "%</td>";
		echo "<td class=$classes[$i] align=right nowrap>" . number_format($stat['bytes_read']/(1024*1024), 2) . "</td>";
		echo "<td class=$classes[$i] align=right nowrap>" . number_format(($stat['bytes_read'] - $bef['bytes_read'])/(1024*$td), 2) . "</td>";
		echo "<td class=$classes[$i] align=right nowrap>" . number_format($stat['bytes_written']/(1024*1024), 2) . "</td>";
		echo "<td class=$classes[$i] align=right nowrap>" . number_format(($stat['bytes_written'] - $bef['bytes_written'])/(1024*$td), 2) . "</td>";
		echo "<td class=$classes[$i] align=right nowrap>" . number_format($stat['curr_connections']) . "</td>";
		echo "<td class=$classes[$i] align=right nowrap>" . number_format($stat['total_connections']) . "</td>";
		echo "<td class=$classes[$i] align=right nowrap>" . number_format(($stat['total_connections'] - $bef['total_connections'])/$td, 2) . "</td>";
		echo "<td class=$classes[$i] align=right nowrap>" . number_format($stat['cmd_get']) . "</td>";
		echo "<td class=$classes[$i] align=right nowrap>" . number_format($stat['get_misses']) . "</td>";
		echo "<td class=$classes[$i] align=right nowrap>" . number_format(($stat['cmd_get'] - $bef['cmd_get'])/$td, 2) . "</td>";
		echo "<td class=$classes[$i] align=right nowrap>" . number_format($stat['cmd_set']) . "</td>";
		echo "<td class=$classes[$i] align=right nowrap>" . number_format(($stat['cmd_set'] - $bef['cmd_set'])/$td, 2) . "</td>";
		echo "<td class=$classes[$i] align=right nowrap>" . number_format($stat['cmd_get']/$stat['cmd_set'], 2) . "</td>";
		echo "<td class=$classes[$i] align=right nowrap>" . number_format(100*(1 - ($stat['get_misses'] - $bef['get_misses'])/($stat['cmd_get'] - $bef['cmd_get'])), 2) . " %</td>";
		echo "<td class=$classes[$i] align=right nowrap>" . (isset($stat['evictions']) ? number_format($stat['evictions']) : "N/A") . "</td>";
		echo "<td class=$classes[$i] align=right nowrap>" . (isset($stat['evictions']) ? number_format(($stat['evictions'] - $bef['evictions'])/$td, 2) : "N/A") . "</td>";
		echo "<td class=$classes[$i] align=right nowrap>" . (isset($stat['evictions']) ? number_format(100*($stat['evictions'] - $bef['evictions'])/($stat['cmd_set'] - $bef['cmd_set']), 2) . " %" : "N/A") . "</td>";
		echo "</tr>";
		$i = !$i;
	}

	$td = ($timediff ? $timediff : $totaluptime/$n);

	echo "<tfoot>";
	echo "<tr>";
	echo "<td class=header>Total:</td>";
	echo "<td class=header align=right></td>";
	echo "<td class=header align=right>" . number_format($total['curr_items']) . "</td>";
	echo "<td class=header align=right>" . number_format($total['bytes']/(1024*1024), 2) . "</td>";
	echo "<td class=header align=right nowrap>" . number_format($total['limit_maxbytes']/(1024*1024)) . " MB</td>";
	echo "<td class=header align=right></td>";
	echo "<td class=header align=right>" . number_format($total['bytes_read']/(1024*1024), 2) . "</td>";
	echo "<td class=header align=right>" . number_format(($total['bytes_read'] - $totalbef['bytes_read'])/(1024*$td), 2) . "</td>";
	echo "<td class=header align=right>" . number_format($total['bytes_written']/(1024*1024), 2) . "</td>";
	echo "<td class=header align=right>" . number_format(($total['bytes_written'] - $totalbef['bytes_written'])/(1024*$td), 2) . "</td>";
	echo "<td class=header align=right>" . number_format($total['curr_connections']) . "</td>";
	echo "<td class=header align=right>" . number_format($total['total_connections']) . "</td>";
	echo "<td class=header align=right>" . number_format(($total['total_connections'] - $totalbef['total_connections'])/$td, 2) . "</td>";
	echo "<td class=header align=right>" . number_format($total['cmd_get']) . "</td>";
	echo "<td class=header align=right>" . number_format($total['get_misses']) . "</td>";
	echo "<td class=header align=right>" . number_format(($total['cmd_get'] - $totalbef['cmd_get'])/$td, 2) . "</td>";
	echo "<td class=header align=right>" . number_format($total['cmd_set']) . "</td>";
	echo "<td class=header align=right>" . number_format(($total['cmd_set'] - $totalbef['cmd_set'])/$td, 2) . "</td>";
	echo "<td class=header align=right></td>";
	echo "<td class=header align=right></td>";
	echo "<td class=header align=right>" . (isset($total['evictions']) ? number_format($total['evictions']) : "N/A") . "</td>";
	echo "<td class=header align=right>" . (isset($total['evictions']) ? number_format(($total['evictions'] - $totalbef['evictions'])/$td, 2) : "N/A") . "</td>";
	echo "<td class=header align=right></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td class=header>Average:</td>";
	echo "<td class=header align=right>" . ($total['uptime']/$n < 86400 ? number_format(($total['uptime']/3600)/$n, 2) . " hours" : number_format(($total['uptime']/86400)/$n, 2) . " days" ) . "</td>";
	echo "<td class=header align=right>" . number_format($total['curr_items']/$n) . "</td>";
	echo "<td class=header align=right>" . number_format($total['bytes']/(1024*1024*$n), 2) . "</td>";
	echo "<td class=header align=right>" . number_format($total['limit_maxbytes']/(1024*1024*$n)) . " MB</td>";
	echo "<td class=header align=right>" . number_format(100*$total['bytes']/$total['limit_maxbytes'], 2) . "%</td>";
	echo "<td class=header align=right>" . number_format($total['bytes_read']/(1024*1024*$n), 2) . "</td>";
	echo "<td class=header align=right>" . number_format(($total['bytes_read'] - $totalbef['bytes_read'])/(1024*$n*$td), 2) . "</td>";
	echo "<td class=header align=right>" . number_format($total['bytes_written']/(1024*1024*$n), 2) . "</td>";
	echo "<td class=header align=right>" . number_format(($total['bytes_written'] - $totalbef['bytes_written'])/(1024*$n*$td), 2) . "</td>";
	echo "<td class=header align=right>" . number_format($total['curr_connections']/$n) . "</td>";
	echo "<td class=header align=right>" . number_format($total['total_connections']/$n) . "</td>";
	echo "<td class=header align=right>" . number_format(($total['total_connections'] - $totalbef['total_connections'])/($n*$td), 2) . "</td>";
	echo "<td class=header align=right>" . number_format($total['cmd_get']/$n) . "</td>";
	echo "<td class=header align=right>" . number_format($total['get_misses']/$n) . "</td>";
	echo "<td class=header align=right>" . number_format(($total['cmd_get'] - $totalbef['cmd_get'])/($n*$td), 2) . "</td>";
	echo "<td class=header align=right>" . number_format($total['cmd_set']/$n) . "</td>";
	echo "<td class=header align=right>" . number_format(($total['cmd_set'] - $totalbef['cmd_set'])/($n*$td), 2) . "</td>";
	echo "<td class=header align=right>" . number_format($total['cmd_get']/$total['cmd_set'], 2) . "</td>";
	echo "<td class=header align=right>" . number_format(100*(1 - ($total['get_misses'] - $totalbef['get_misses'])/($total['cmd_get'] - $totalbef['cmd_get'])), 2) . " %</td>";
	echo "<td class=header align=right>" . (isset($total['evictions']) ? number_format($total['evictions']/$n) : "N/A") . "</td>";
	echo "<td class=header align=right>" . (isset($total['evictions']) ? number_format(($total['evictions'] - $totalbef['evictions'])/($n*$td), 2) : "N/A") . "</td>";
	echo "<td class=header align=right>" . (isset($total['evictions']) ? number_format(100*($total['evictions'] - $totalbef['evictions'])/($total['cmd_set'] - $totalbef['cmd_set']), 2)  . " %": "N/A") . "</td>";
	echo "</tr>";
	echo "</tfoot>";

	echo "</table>";
	echo "<br>";
}
