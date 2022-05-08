<?

	ob_start();

	echo "Hello";

	include("include/general.lib.php");

	echo " World";

	ob_end_flush();
