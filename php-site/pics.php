<?

	set_time_limit(0);

	include("include/general.lib.php");

	$res = $usersdb->unbuffered_query("SELECT id FROM pics");

	$i=0;

	$pics = array();
	while($line = $res->fetchrow()){
		$pics[] = $line['id'];
		$i++;
		if($i % 10000 == 0){
			echo "$i ";
			zipflush();
		}
	}

	echo "pending: ";

	$res = $usersdb->unbuffered_query("SELECT id FROM picspending");

	while($line = $res->fetchrow()){
		$pics[] = $line['id'];
		$i++;
		if($i % 10000 == 0){
			echo "$i ";
			zipflush();
		}
	}

	$fp = fopen("$docRoot/users/pics.txt",'w');

	$contents = implode(",",$pics);

	fwrite($fp,$contents);
	fclose($fp);

