<?

	$forceserver = true;
	$enableCompression = true;
	$login=1;
//	$errorLogging = false;
	require_once("include/general.lib.php");

	if(!in_array($userData['userid'], $debuginfousers))
		die("error");


	echo str_repeat(" ",400). "\n";
	set_time_limit(0);
//	ignore_user_abort(true);


function outputOptions($dbs, $default, $prefix = ""){
	foreach($dbs as $type => $db){
		if(isset($db['host'])){
			if($prefix)
				$val = "$prefix-$type";
			else
				$val = $type;

			echo "<option value=\"$val\"";
			if($val == $default)
				echo " selected";
			echo ">$val - $db[host]";
		}else{
			if($prefix)
				$val = "$prefix-$type";
			else
				$val = $type;
			outputOptions($db, $default, $val);
		}
	}
}

	$dbchoice = getPOSTval('dbchoice');

	$start = getPOSTval('start');

	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<select name=action>" . make_select_list(array("Check","Analyze","Optimize","Backup","Master Status","Slave Status"), $action) . "</select>";
	echo "<select name=dbchoice>";
	outputOptions($databases, $dbchoice);
	echo "</select>";
	echo "<input type=test name=start value='$start'>";
	echo "<input type=submit value=Go>";
	echo "</form>";

	if($dbchoice){
		$check = $databases;

		$subs = explode("-", $dbchoice);
		foreach($subs as $sub)
			$check = $check[$sub];

		$check['login'] = 'root';
		$check['passwd'] = 'pRlUvi$t';
//		$check['passwd'] = 'Hawaii';

		$checkdb = & new sql_db($check);
		$time = time();
		switch($action){
			case "Check":		$checkdb->check(2); 	break;
			case "Analyze":		$checkdb->analyze(2);	break;
			case "Optimize":	$checkdb->optimize(2);	break;
			case "Backup":		$checkdb->backup("/home/nexopia/backup/backup",2);	break;

			case "Master Status":
			case "Slave Status":

				$checkdb->query("SHOW $action");

				$line = $checkdb->fetchrow();

				echo "<table border=1 cellspacing=0 cellpadding=3>";

				foreach($line as $k => $v)
					echo "<tr><td>$k</td><td><pre>$v</pre></td></tr>";

				echo "</table>";
				break;


		}
		echo "<br>total: " . (time() - $time) . " seconds<br>";
	}

