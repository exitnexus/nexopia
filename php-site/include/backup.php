<?

function backupdb($basedir,$status=1){
	global $db;
	set_time_limit(0);	// one hour

    function format_values(&$value, $key){
		$value = str_replace(",","\,",$value);
		$value = str_replace("\n","\\n",$value);
		$value = str_replace("\"","\\\"",$value);
		$value = "\"$value\"";
	}

	function zipflush(){
		echo "<!-- ";
		for($i=0;$i<4000;$i++)
			echo chr(rand(65,126));
		echo " -->\n";
		flush();
	}

	$time0 = time();

	$cp = fopen("$basedir/backup/db/dbcreation.sql","w");


    $tableresult = $db->listtables();

    while (list($name) = $db->fetchrow($tableresult,DB_NUM)) {


        fwrite($cp,"# starting table $name\n");

		if($status){
			echo "Starting table $name ... ";
			if($status==2)
				zipflush();
		}

		$time1 = time();

		$result = $db->query("CHECK TABLE `$name` MEDIUM");
		$check = $db->fetchrow($result);

		if($check['Msg_text']!='OK' && $check['Msg_text']!='Table is already up to date'){
			if($status){
				echo "check msg: $check[Msg_type] : $check[Msg_text], repairing\n<br>";
				if($status==2)
					zipflush();
			}
			$result = $db->query("REPAIR TABLE `$name`");
			$repair = $db->fetchrow($result);

			if($repair['Msg_text']!='OK')
				die("Couldn't repair database $name\n<br>");
		}

		$db->query("OPTIMIZE TABLE `$name`");
		$db->query("ANALYZE TABLE `$name`");

		fwrite($cp, "DROP `$name`;\n");

		$result = $db->query("SHOW CREATE TABLE `$name`");
		fwrite($cp, $db->fetchfield(1,0,$result) . ";\n\n");

		$fp = fopen("$basedir/backup/db/$name.dump", "w");

		$result = $db->unbuffered_query("SELECT * FROM `$name`");

		$output="";
        while($line = $db->fetchrow($result,DB_NUM)){
        	array_walk($line,'format_values');
        	$output .= implode(",", $line) . "\n";
        	if(strlen($output) > 256*1024){ //256kb
        		fwrite($fp,$output);
        		$output="";
        	}
        }

        fwrite($fp,$output);
		$output="";
        fclose($fp);

		$time2 = time();

		if($status)
			echo "Created file $name.dump .. " . ($time2 - $time1) . "s<br>\n";
	}

	fclose($cp);
	if($status==2)
		zipflush();

	$time2 = time();

	if($status){
		echo "dumping took: " . ($time2 - $time0) . " seconds<br>\n";

/*		echo "tarring ...<br>\n";
		if($status==2)
			zipflush();
	}

	if(file_exists("$basedir/backup/endb.old.tar.bz2"))
		unlink("$basedir/backup/endb.old.tar.bz2");

	if(file_exists("$basedir/backup/endb.tar.bz2"))
		rename("$basedir/backup/endb.tar.bz2","$basedir/backup/endb.old.tar.bz2");

	$time1=time();

	exec("tar cfj $basedir/backup/endb.tar.bz2 $basedir/backup/db");

	$time2 = time();

	if($status){
		echo "tarring took " . ($time2 - $time1) . " seconds<br>\n";

		echo "done<br>\n";
		if($status==2)
			zipflush();
*/
	}

}

function backupfiles($basedir){
	set_time_limit(0);
/*
	if(file_exists("$basedir/backup/en.old.tar.bz2"))
		unlink("$basedir/backup/en.old.tar.bz2");

	if(file_exists("$basedir/backup/en.tar.bz2"))
		rename("$basedir/backup/en.tar.bz2","$basedir/backup/en.old.tar.bz2");

	exec("tar cfj $basedir/backup/en.tar.bz2 $basedir/public_html");
*/
}



/**
 * Removes comment lines and splits up large sql files into individual queries
 *
 * Last revision: September 23, 2001 - gandon
 *
 * @param   array    the splitted sql commands
 * @param   string   the sql commands
 *
 * @return  boolean  always true
 *
 * @access  public
 */
function PMA_splitSqlFile(&$ret, $sql)
{
    $sql          = trim($sql);
    $sql_len      = strlen($sql);
    $char         = '';
    $string_start = '';
    $in_string    = FALSE;
    $time0        = time();

    for ($i = 0; $i < $sql_len; ++$i) {
        $char = $sql[$i];

        // We are in a string, check for not escaped end of strings except for
        // backquotes that can't be escaped
        if ($in_string) {
            for (;;) {
                $i         = strpos($sql, $string_start, $i);
                // No end of string found -> add the current substring to the
                // returned array
                if (!$i) {
                    $ret[] = $sql;
                    return TRUE;
                }
                // Backquotes or no backslashes before quotes: it's indeed the
                // end of the string -> exit the loop
                else if ($string_start == '`' || $sql[$i-1] != '\\') {
                    $string_start      = '';
                    $in_string         = FALSE;
                    break;
                }
                // one or more Backslashes before the presumed end of string...
                else {
                    // ... first checks for escaped backslashes
                    $j                     = 2;
                    $escaped_backslash     = FALSE;
                    while ($i-$j > 0 && $sql[$i-$j] == '\\') {
                        $escaped_backslash = !$escaped_backslash;
                        $j++;
                    }
                    // ... if escaped backslashes: it's really the end of the
                    // string -> exit the loop
                    if ($escaped_backslash) {
                        $string_start  = '';
                        $in_string     = FALSE;
                        break;
                    }
                    // ... else loop
                    else {
                        $i++;
                    }
                } // end if...elseif...else
            } // end for
        } // end if (in string)

        // We are not in a string, first check for delimiter...
        else if ($char == ';') {
            // if delimiter found, add the parsed part to the returned array
            $ret[]      = substr($sql, 0, $i);
            $sql        = ltrim(substr($sql, min($i + 1, $sql_len)));
            $sql_len    = strlen($sql);
            if ($sql_len) {
                $i      = -1;
            } else {
                // The submited statement(s) end(s) here
                return TRUE;
            }
        } // end else if (is delimiter)

        // ... then check for start of a string,...
        else if (($char == '"') || ($char == '\'') || ($char == '`')) {
            $in_string    = TRUE;
            $string_start = $char;
        } // end else if (is start of string)

        // ... for start of a comment (and remove this comment if found)...
        else if ($char == '#'
                 || ($char == ' ' && $i > 1 && $sql[$i-2] . $sql[$i-1] == '--')) {
            // starting position of the comment depends on the comment type
            $start_of_comment = (($sql[$i] == '#') ? $i : $i-2);
            // if no "\n" exits in the remaining string, checks for "\r"
            // (Mac eol style)
            $end_of_comment   = (strpos(' ' . $sql, "\012", $i+2))
                              ? strpos(' ' . $sql, "\012", $i+2)
                              : strpos(' ' . $sql, "\015", $i+2);
            if (!$end_of_comment) {
                // no eol found after '#', add the parsed part to the returned
                // array if required and exit
                if ($start_of_comment > 0) {
                    $ret[]    = trim(substr($sql, 0, $start_of_comment));
                }
                return TRUE;
            } else {
                $sql          = substr($sql, 0, $start_of_comment)
                              . ltrim(substr($sql, $end_of_comment));
                $sql_len      = strlen($sql);
                $i--;
            } // end if...else
        } // end else if (is comment)

    } // end for

    // add any rest to the returned array
    if (!empty($sql) && ereg('[^[:space:]]+', $sql)) {
        $ret[] = $sql;
    }

    return TRUE;
} // end of the 'PMA_splitSqlFile()' function


