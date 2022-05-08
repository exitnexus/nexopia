<?
	error_reporting (E_ALL);

include("include/config.inc.php");


	$link = mysql_pconnect("$mysql_host", "$mysql_login", "$mysql_passwd")	or die ("Could not connect");
	mysql_select_db ("$mysql_db");

	set_time_limit(3600);



     function format_values(&$value, $key){
		$value = str_replace(",","\,",$value);
		$value = str_replace("\n","\\n",$value);
		$value = str_replace("\"","\\\"",$value);
		$value = "\"$value\"";
	}

	$cp = fopen($docRoot . "/../backup/db/dbcreation.sql","w");


    $tableresult = mysql_list_tables($mysql_db) or die("error");

    while (list($name) = mysql_fetch_array($tableresult)) {


        fwrite($cp,"# starting table $name\n");

		echo "Starting table $name ... ";
		flush();

		$result = mysql_query("CHECK TABLE `$name` MEDIUM");
		$check = mysql_fetch_assoc($result);

		if($check['Msg_text']!='OK' && $check['Msg_text']!='Table is already up to date'){
			echo "check msg: $check[Msg_type] : $check[Msg_text], repairing\n<br>";
			$result = mysql_query("REPAIR TABLE `$name`");
			$repair = mysql_fetch_assoc($result);

			if($repair['Msg_text']!='OK')
				die("Couldn't repair database $name\n<br>");
		}

		$result = mysql_query("SHOW CREATE TABLE `$name`");
		fwrite($cp, mysql_result($result,0,1) . ";\n\n");


			$result = mysql_unbuffered_query("SELECT * FROM `$name`");

        while($line = mysql_fetch_row($result)){
        	array_walk($line,'format_values');
        	$output .= implode(",", $line) . "\n";
        }

		$str = gzencode($output);

		$gz = fopen("$docRoot/../backup/db/$name.gz", "w");
		fwrite($gz, $str);
		fclose($gz);

		echo "Created file db$name.gz<br>\n";
		flush();

		$output="";
		$str="";

	}
	fclose($cp);

	if(file_exists("$docRoot/../backup/en.old.tar.bz2"))
		unlink("$docRoot/../backup/en.old.tar.bz2");

	if(file_exists("$docRoot/../backup/en.tar.bz2"))
		rename("$docRoot/../backup/en.tar.bz2","$docRoot/../backup/en.old.tar.bz2");

	exec("tar cfj $docRoot/../backup/en.tar.bz2 $docRoot");


