<?

if($errorLogging){

	function userErrorHandler($errno, $errmsg, $filename, $linenum, $vars) {
		global $sitebasedir,$PHP_SELF, $userData, $debuginfousers;

		$time = gmdate("d M Y H:i:s");

	// Get the error type from the error number
		static $errortype = array ( 1   => "Error",
									2   => "Warning",
									4   => "Parsing Error",
									8   => "Notice",
									16  => "Core Error",
									32  => "Core Warning",
									64  => "Compile Error",
									128 => "Compile Warning",
									256 => "User Error",
									512 => "User Warning",
									1024=> "User Notice");
		$errlevel=$errortype[$errno];

	//Write error to log file (CSV format)
		if($errno <= 128)
			$file = "$sitebasedir/logs/site/errors.csv";
		elseif($errno == 256)
			$file = "$sitebasedir/logs/site/usererrors.csv";
		else
			$file = "$sitebasedir/logs/site/userwarnings.csv";

		$str = "\"$time\",\"$filename: $linenum\",\"($errlevel)\",\"$errmsg\",\"" . getip() . "\",\"$PHP_SELF\"\r\n";

		$errfile=fopen($file,"a");
		fputs($errfile,$str);
		fclose($errfile);

	//Terminate script if fatal error
		if($errno!=2 && $errno!=8 && $errno!=512 && $errno!=1024){
			if($userData['loggedIn'] && in_array($userData['userid'],$debuginfousers))
				die("A fatal error has occured. Script execution has been aborted:<br>\n$str");
			else
				die("A fatal error has occured. Script execution has been aborted");
		}
	}

	set_error_handler("userErrorHandler");
}
