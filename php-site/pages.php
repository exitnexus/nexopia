<?

	$login = 0;

	include_once("include/general.lib.php");

	if(!($id = getREQval('id', 'int')))
		die("bad page id");

	$content = getStaticValue($id, false);

	if(!$content)
		die("bad page id");

	incHeader();

	echo $content;

	incFooter();

