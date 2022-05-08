<?

	$login = 0;
	$simplepage = 1;
	$simpleauth = true;
	$skintype = 'frames';

	require_once("include/general.lib.php");

	createHeader(getREQval('height', 'int', 60), 
	             getREQval('bodyname', 'string', 'body'),
	             getREQval('pageid', 'int'));

