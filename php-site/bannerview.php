<?

	$login=0;
	$simplepage = true;

	require_once("include/general.lib.php");

	$type = getREQval('size', 'int', BANNER_BANNER);
	$passback = getREQval('pass', 'int');

	echo "<html><body style=\"background: transparent;\">";

	echo "<table cellspacing=0 cellpadding=0 width=100% height=100%><tr><td align=right valign=top>";

	$bannertext = $banner->getbanner($type, true, $passback);
	if($bannertext!="")
		echo $bannertext;

	echo "</td></tr></table>";
	echo "</body></html>";

//	debugOutput();

