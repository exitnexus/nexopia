<?

	error_reporting (E_ALL);

	define_syslog_variables();
	openlog("PHP error", LOG_ODELAY, LOG_LOCAL0);

	$priority = LOG_ERR;

	$str = "testing";

	syslog($priority, $str);

	echo "sysloged $priority, $str\n";

