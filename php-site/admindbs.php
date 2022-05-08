<?

	$forceserver = true;
	$enableCompression = true;
	$errorLogging = false;

	$login=1;

	require_once("include/general.lib.php");

	if(!in_array($userData['userid'], $debuginfousers))
		die("error");


	switch($action){
		case 'tablesize':
			tablesize(!getREQval('hidearchives', 'bool'));

		case 'serverbalance':
		default:
			serverbalance();
	}

///////////////////////

function menu(){
	echo "<center>";
	echo "<a class=body href=$_SERVER[PHP_SELF]?action=serverbalance>Server Balance</a> | ";
	echo "<a class=body href=$_SERVER[PHP_SELF]?action=tablesize>Table Size</a> (<a class=body href=$_SERVER[PHP_SELF]?action=tablesize&hidearchives=1>Hide Archives</a>)";
	echo "</center>";
}

function tablesize($showarchives = true){
	global $usersdb;

	$tables = array();

	$res = $usersdb->prepare_query("SHOW TABLE STATUS");
	while($line = $res->fetchrow()){
		if(!$showarchives && strpos($line['Name'], 'archive') !== false)
			continue;

		if(!isset($tables[$line['Name']]))
			$tables[$line['Name']] = array('data' => 0, 'index' => 0);

		$tables[$line['Name']]['data'] += $line['Data_length'];
		$tables[$line['Name']]['index'] += $line['Index_length'];
		$tables[$line['Name']]['rows'] += $line['Rows'];
		$tables[$line['Name']]['rowsize'] += $line['Avg_row_length'];
	}

	$totals = array();
	foreach($tables as $name => $data){
		foreach($data as $k => $v){
			if(!isset($totals[$k]))
				$totals[$k] = 0;
			$totals[$k] += $v;
		}
	}


	incHeader();

	menu();

	echo "<table align=center>";

	echo "<tr>";
	echo "<td class=header>Table</td>";
	echo "<td class=header align=center>Data</td>";
	echo "<td class=header align=center>Index</td>";
	echo "<td class=header align=center>Rows</td>";
	echo "<td class=header align=center>Row Size</td>";
	echo "</tr>";

	$classes = array('body','body2');
	$i = 0;

	foreach($tables as $table => $data){
		echo "<tr>";
		echo "<td class=$classes[$i]>$table</td>";
		echo "<td class=$classes[$i] align=right>" . number_format($data['data']/1024) . " KB</td>";
		echo "<td class=$classes[$i] align=right>" . number_format($data['index']/1024) . " KB</td>";
		echo "<td class=$classes[$i] align=right>" . number_format($data['rows']) . "</td>";
		echo "<td class=$classes[$i] align=right>" . number_format($data['rowsize']) . " B</td>";
		echo "</tr>";

		$i = !$i;
	}

	echo "<tr>";
	echo "<td class=header>Total:</td>";
	echo "<td class=header align=right>" . number_format($totals['data']/1024) . " KB</td>";
	echo "<td class=header align=right>" . number_format($totals['index']/1024) . " KB</td>";
	echo "<td class=header align=right>" . number_format($totals['rows']) . "</td>";
	echo "<td class=header></td>";
	echo "</tr>";

	echo "</table>";

	incFooter();
	exit;
}

function serverbalance(){
	global $masterdb, $usersdb, $config, $typeid;

	$time = time();
	
	$type = $typeid->getTypeID("User");
	
	$res = $masterdb->prepare_query("SELECT * FROM serverbalance WHERE type = #", $type);
	
	$servers = array();
	while($line = $res->fetchrow()){
		$line['users'] = 0;
		$line['active'] = 0;
		$line['online'] = 0;
		$line['size'] = 0;
		$line['archivesize'] = 0;

		$servers[$line['serverid']] = $line;
	}
	
	
	$split = $usersdb->getSplitDBs();

	$totalcount = 0;
	$totalusers = 0;
	$totalactive = 0;
	$totalonline = 0;
	$totalsize = 0;
	$totalarchive = 0;

	foreach($split as $id => & $udb){
		$servers[$id]['dbname'] = $udb->dbname;
		$servers[$id]['ip'] = $udb->server;

		$res = $udb->prepare_query("SELECT count(*) FROM users");
		$servers[$id]['users'] = $res->fetchfield();

		$res = $udb->prepare_query("SELECT count(*) FROM useractivetime WHERE activetime > #", $time - 86400*7);
		$servers[$id]['active'] = $res->fetchfield();

		$res = $udb->prepare_query("SELECT count(*) FROM useractivetime WHERE online = 'y'");
		$servers[$id]['online'] = $res->fetchfield();

		$res = $udb->prepare_query("SHOW TABLE STATUS");
		while($line = $res->fetchrow()){
			if(strpos($line['Name'], 'archive') === false)
				$servers[$id]['size'] += $line['Data_length'] + $line['Index_length'];
			else
				$servers[$id]['archivesize'] += $line['Data_length'] + $line['Index_length'];
		}

		$totalaccounts += $servers[$id]['totalaccounts'];
		$totalrealaccounts += $servers[$id]['realaccounts'];
		$totalusers += $servers[$id]['users'];
		$totalactive += $servers[$id]['active'];
		$totalonline += $servers[$id]['online'];
		$totalsize += $servers[$id]['size'];
		$totalarchive += $servers[$id]['archivesize'];
	}


	incHeader();

	menu();

	echo "<script src=$config[jsloc]sorttable.js></script>";

	echo "<table align=center class=sortable>";
	echo "<tr>";
	echo "<td class=header>ID</td>";
	echo "<td class=header>Server</td>";
	echo "<td class=header>Database</td>";
	echo "<td class=header>Accounts</td>";
	echo "<td class=header>Real Accounts</td>";
	echo "<td class=header>Users</td>";
	echo "<td class=header>Active</td>";
	echo "<td class=header>Online</td>";
	echo "<td class=header>Data Size</td>";
	echo "<td class=header>Archive Size</td>";
	echo "</tr>";

	$i = 0;
	$classes = array('body', 'body2');

	foreach($servers as $row){
		echo "<tr>";
		echo "<td class=$classes[$i] align=right>$row[serverid]</td>";
		echo "<td class=$classes[$i]>$row[ip]</td>";
		echo "<td class=$classes[$i]>$row[dbname]</td>";
		echo "<td class=$classes[$i] align=right>" . number_format($row['totalaccounts']) . "</td>";
		echo "<td class=$classes[$i] align=right>" . number_format($row['realaccounts']) . "</td>";
		echo "<td class=$classes[$i] align=right>" . number_format($row['users']) . "</td>";
		echo "<td class=$classes[$i] align=right>" . number_format($row['active']) . "</td>";
		echo "<td class=$classes[$i] align=right>" . number_format($row['online']) . "</td>";
		echo "<td class=$classes[$i] align=right>" . number_format($row['size']/1024/1024) . " MB</td>";
		echo "<td class=$classes[$i] align=right>" . number_format($row['archivesize']/1024/1024) . " MB</td>";
		echo "</tr>";
		$i = !$i;
	}

	$numservers = count($servers);

	echo "<tfoot>";
	echo "<tr>";
	echo "<td class=header colspan=3>Total: $numservers Servers</td>";
	echo "<td class=header align=right>" . number_format($totalaccounts) . "</td>";
	echo "<td class=header align=right>" . number_format($totalrealaccounts) . "</td>";
	echo "<td class=header align=right>" . number_format($totalusers) . "</td>";
	echo "<td class=header align=right>" . number_format($totalactive) . "</td>";
	echo "<td class=header align=right>" . number_format($totalonline) . "</td>";
	echo "<td class=header align=right>" . number_format($totalsize/1024/1024) . " MB</td>";
	echo "<td class=header align=right>" . number_format($totalarchive/1024/1024) . " MB</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td class=header colspan=3>Average</td>";
	echo "<td class=header align=right>" . number_format($totalaccounts/$numservers) . "</td>";
	echo "<td class=header align=right>" . number_format($totalrealaccounts/$numservers) . "</td>";
	echo "<td class=header align=right>" . number_format($totalusers/$numservers) . "</td>";
	echo "<td class=header align=right>" . number_format($totalactive/$numservers) . "</td>";
	echo "<td class=header align=right>" . number_format($totalonline/$numservers) . "</td>";
	echo "<td class=header align=right>" . number_format($totalsize/1024/1024/$numservers) . " MB</td>";
	echo "<td class=header align=right>" . number_format($totalarchive/1024/1024/$numservers) . " MB</td>";
	echo "</tr>";
	echo "</tfoot>";

	echo "</table>";

	incFooter();
	exit;
}
