<?

	$login=0;
	$simplepage = true;

	require_once("include/general.lib.php");

	$type = getREQval('size', 'int', BANNER_BANNER);
	$passback = getREQval('pass', 'int');
	
	$js = getREQval('js', 'bool');
	
	$debug = getREQval('debug', 'int', 0);
	
	$str = "";

	if(!$passback || !$js){ //javascript passbacks already have this, don't add it a second time.
		$str .= "<html><body style=\"background: transparent;\">";
		$str .= "<table cellspacing=0 cellpadding=0 width=100% height=100%><tr><td align=right valign=top>";
	}

	$str .= $banner->getbanner($type, true, $passback, $debug);
	
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

