#!/usr/local/php/bin/php
<?

	if(count($argv) != 2)
		die("Bad Arguments\n");

	include("public_html/include/fileserver.php");

	$fs = new fileserver();

	$fs->addFile($argv[1]);
