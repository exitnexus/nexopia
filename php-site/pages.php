<?

	$login = 0;

	include_once("include/general.lib.php");

	if(!($id = getREQval('id', 'int')))
		die("bad page id");

	$data = getStaticValue($id, false, true);

	if(!$data)
		die("bad page id");

	incHeader( ($data['pagewidth'] ? $data['pagewidth'] : true) );

	echo $data['content'];

	incFooter();

