<?

	$forceserver = true;

	$_SERVER['DOCUMENT_ROOT'] = getcwd();

	require_once($_SERVER['DOCUMENT_ROOT'] . "/include/general.lib.php");

	$file = null;
	function filewriter($buffer)
	{
		global $file;
		fwrite($file, $buffer);
	}

	// get an sql dump into struct.sql
    $file = fopen('../struct.sql', 'w');
	ob_start("filewriter");
	include('../dumpstructsql.php');
	ob_end_flush();
	fclose($file);

	// get a structured dump into struct.new
    $file = fopen('../struct.new', 'w');
	ob_start("filewriter");
	include('../dumpstruct.php');
	ob_end_flush();
	fclose($file);

	if (0 && file_exists('../struct.cur'))
	{
		// diff the old structure with the new and append it to struct.diff
		$file = fopen('../struct.diff', 'a');
		ob_start("filewriter");
		$_SERVER['argv'] = array('dbdiff.php', '../struct.cur', '../struct.new');
		$_SERVER['argc'] = 3;
		include('../dbdiff.php');
		ob_end_flush();
		fclose($file);
	}

	// move struct.new to struct.cur
    @unlink('../struct.cur');
    @rename('../struct.new', '../struct.cur');
