<?

	$login = 0;
	$simpleauth = true;
	$simplepage = 2;
	$forceserver = true;
	$accepttype = false;

	require_once("include/general.lib.php");

	addRefreshHeaders();

	$type = getREQval('size', 'int', BANNER_BANNER);
	$passback = getREQval('pass', 'int');
	
	$pageid = getREQval('pageid', 'int', 0);
	
	$js = getREQval('js', 'bool');
	
	$debug = getREQval('debug', 'int', 0);
	
	$str = "";

	if(!$passback || !$js){ //javascript passbacks already have this, don't add it a second time.
		$str .= "<html><body style=\"background: transparent;\">";
		$str .= "<table cellspacing=0 cellpadding=0 width=100% height=100%><tr><td align=right valign=top>";
	}

	$str .= $banner->getbanner($type, true, $passback, $debug, $pageid);
	
	if(!$passback || !$js){
		$str .= "</td></tr></table>";
		$str .= "</body></html>";
	}
	
	if($js){
		header("Content-Type: text/javascript");
		echo "var str = '';\n";
		$strs = explode("\n", $str);
		foreach($strs as $str)
			echo "str += '" . str_ireplace("script", "scr'+'ipt", addslashes(trim($str))) . "\\n';\n";
		echo "document.write(str);";
	}else{
		echo $str;
	}
	
//	debugOutput();

