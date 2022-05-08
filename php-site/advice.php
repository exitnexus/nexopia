<?

	$login=0;

	require_once("include/general.lib.php");

	$advice = $wiki->getPage("/SiteText/fulladvice");

	incHeader();
	
	echo $advice['output'];

	incFooter();
	