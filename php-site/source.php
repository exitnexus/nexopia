<?

	$login=1;

	require_once("include/general.lib.php");

	$validUsers = array();

	if(!in_array($userData['userid'],$validUsers))
		die("You don't have permission to see this");


	$illigalNames = array("include/config.inc.php","ircbot.php");
	
	incHeader();
	
	if(!isset($url))
		$url="";

	$legal = true;
	if(in_array($url,$illigalNames))
		$legal = false;
	
	if(strpos(realpath($docRoot . "/" . $url),$docRoot)!==0)
		$legal = false;
	

	
	
	echo "<form action=$_SERVER[PHP_SELF]>";
	echo "Show source of <input type=text name=url value='$url'><input type=submit value=Look></form><hr>";

	echo "Source of: ", htmlentities($url), ":<br>"; 
	
	if(!$legal){
		echo "That isn't a legal file";
		incFooter();
		exit;
	}
	
	
	$page_name=$docRoot. "/" . $url;
	
	if (file_exists($page_name) && !is_dir($page_name)) {
	    show_source($page_name);
	} elseif (@is_dir($page_name)) {
	    echo "<p>No file specified.  Can't show source for a directory.</p>\n";
	}else {
	    echo "<p>This file does not exist.</p>\n";
	}
	
	incFooter();
