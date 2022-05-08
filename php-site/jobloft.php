<?

	$login = 0;
	$simplepage = 1;
	$skintype = 'frames';

	require_once("include/general.lib.php");


	$postalcode = getREQval('postalcode');
	$skipframes = getREQval('skipframes','bool');

	$searchurl = "http://www.jobloft.com/hosting/jobsite.aspx?jsid=3&zip=" . urlencode($postalcode);

	if($skipframes){
		header("Location: $searchurl");
		exit;
	}

//track clicks to this page through a banner of type link.
	$banner->click(1159, "jobloft");


	echo "<html><head><title>$config[title]</title>\n";
	echo "<script src=$config[jsloc]general.js></script>\n";
	echo "</head>\n";

	echo "<script>";

	$topframeheight = ($skindata['menuheight'] + $skindata['menuguttersize'] + ($userData['loggedIn'] ? ($skindata['menuheight'] + $skindata['menuspacersize'] ) : 0 ) ); //menuheight
	if($userData['premium'] && $userData['skintype'] == 'normal')
		$bodyname = '_top';
	else
		$bodyname = strtoupper(substr(base_convert(md5(gettime()), 16, 36), 0, 8));

	echo "if(self==top){\n";
		if($userData['limitads']){ //plus always has the small header
			echo "h=60;\n";
		}else{
			echo "s=getWindowSize();\n";
			echo "h=(s[0]>900&&s[1]>500?90:60);\n"; //show big or small?
		}
		echo "document.write('<frameset rows=\"'+($topframeheight+h)+',*\" frameborder=0 border=0>');\n";
		echo "document.write('<frame src=\"/header.php?bodyname=$bodyname&height='+h+'&pageid=" . $banner->getpageid() . "\" name=head scrolling=no noresize marginwidth=0 marginheight=0>');\n";
		echo "document.write('<frame src=\"$searchurl\" " . ($bodyname == '_top' ? '' : "name=\"$bodyname\"" ) . " marginwidth=0 marginheight=0>');\n";
		echo "document.write('</frameset>');\n";
	echo "}else{\n";
		echo "location.href='$searchurl';\n";
	echo "}\n";

	echo "</script>\n";

	echo "</html>";


