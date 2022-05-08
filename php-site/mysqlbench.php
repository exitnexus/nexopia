<?

	$devutil = true;

	include("include/general.lib.php");

	set_time_limit(0);

	$test_num = 10000;

	$testdb = new sql_db( 
		array(
			'host' => '192.168.10.50',
			'login' => 'root',
			'passwd' => 'Hawaii',
			'db' => 'nexopia'
			)
		);

echo "<pre>";

	$timer = new timer();
	$timer->start("script start - $test_num");


	echo $timer->lap("select version()");

	for($i = 0; $i < $test_num; $i++){
		$res = $testdb->query("SELECT VERSION()");
		$res->fetchrow();
	}

	echo $timer->lap("CREATE TABLE");

	$testdb->query(
		"CREATE TEMPORARY TABLE temptable (" .
			"id INT(10) NOT NULL AUTO_INCREMENT, " .
			"num INT(10) NOT NULL, " .
			"str TEXT NOT NULL, " .
			"PRIMARY KEY (id)" .
		")" );

	echo $timer->lap("insert rows");

	$str = randstr();
	for($i = 0; $i < $test_num; $i++){
		$testdb->prepare_query("INSERT INTO temptable SET num = #, str = ?", rand(0,1000000), $str);
	}

	echo $timer->lap("check existence of a row");

	for($i = 0; $i < $test_num; $i++){
		$res = $testdb->prepare_query("SELECT id FROM temptable WHERE id = #", rand(0,$test_num)+1);
		while($res->fetchrow());
	}

	echo $timer->lap("return a full row");

	for($i = 0; $i < $test_num; $i++){
		$res = $testdb->prepare_query("SELECT * FROM temptable WHERE id = #", rand(0,$test_num)+1);
		while($res->fetchrow());
	}

	echo $timer->lap("return first 10 rows");

	for($i = 0; $i < $test_num; $i++){
		$res = $testdb->query("SELECT * FROM temptable LIMIT 10");
		while($res->fetchrow());
	}

	echo $timer->lap("return random 10 rows");

	for($i = 0; $i < $test_num; $i++){
		$res = $testdb->prepare_query("SELECT * FROM temptable LIMIT #,10", rand(0,$test_num/10));
		while($res->fetchrow());
	}

	echo $timer->lap("return all rows 10 times");

	for($i = 0; $i < 10; $i++){
		$res = $testdb->query("SELECT * FROM temptable");
		while($res->fetchrow());
	}

	echo $timer->lap("return all rows 10 times async");

	for($i = 0; $i < 10; $i++){
		$res = $testdb->unbuffered_query("SELECT * FROM temptable");
		while($res->fetchrow());
	}

	echo $timer->stop();

	$testdb->close();


echo "</pre>";

function randstr(){
	$str = "";
	for($i = 1000; $i >= 0; $i--)
		$str .= rand(0,1000000);

	return $str;
}

