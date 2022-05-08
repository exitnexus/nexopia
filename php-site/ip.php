<?

	$login = 0;
	$forceserver = true;

	include("include/general.lib.php");


	echo getip();
	exit;

	echo "Your ip: $REMOTE_ADDR<br>\n";
if(isset($HTTP_X_FORWARDED_FOR))
	echo "Your ip: $HTTP_X_FORWARDED_FOR<br>\n";
//	echo "In long format: ". ip2int($REMOTE_ADDR) . "<br>\n";
//	echo "In hex format: ". dechex(ip2int($REMOTE_ADDR)) . "<br>\n";
	echo "Server: $SERVER_SOFTWARE<br>\n";

if(isset($test))
	echo "Test: $test<br>\n";

phpinfo();

echo "<form method=post action=ip.php><input type=text name=test><input type=submit></form>";
