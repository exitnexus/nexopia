<?php

$login=1;

require_once("include/general.lib.php");

$template = new template("mynex", true);
$menu = array ();
foreach ($menus['manage']->getMenu() as $item){
	$menu[] = "<a href='javascript:void($item[addr])' onclick='\n" .
			"loadXMLDoc(\"$item[addr]\");\n" .
			"'> $item[name] </a>";
}

$scriptstring = "<script>var req;\n" .
			"function loadXMLDoc(url) {".
			"req = false;\n" .
			"    if(window.XMLHttpRequest) {".
			"    	try {  ".
			"			req = new XMLHttpRequest();\n" .
			"        } catch(e) {".
			"			req = false;\n" .
			"        }\n" .
			"    } else if(window.ActiveXObject) {".
			"       	try {".
			"        	req = new ActiveXObject(\"Msxml2.XMLHTTP\");\n" .
			"      	} catch(e) {".
			"        	try {".
			"          		req = new ActiveXObject(\"Microsoft.XMLHTTP\");".
			"        	} catch(e) {".
			"          		req = false;\n" .
			"        	}\n" .
			"		}\n" .
			"    }\n" .
			"	if(req) {".
			"		req.onreadystatechange = processReqChange;\n" .
			"		req.open(\"GET\", url, true);\n" .
			"		req.send(\"\");\n" .
			"	}\n" .
			"}\n" .
			"function processReqChange(){\n".
			"	if (req.readyState == 4) { \n".
			"        if (req.status == 200) { \n".
			"			document.getElementById('MainObj').innerHTML = \"<table height=600><tr><td>\" + req.responseText;\n + \" </td></tr>\"" .
			"        } else {".
			"            alert(req.statusText);" .
			"        }" .
			"    }" .
			"}\n" .
			"</script>" ;

$menustring = implode($skindata['menudivider'], $menu);
$template->set("menu", $scriptstring.$menustring);
$template->set("skindir", $skindir);
$template->setHeader();
$template->display(true);

?>
