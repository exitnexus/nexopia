<?

function writeConfig(){
	global $docRoot,$db;

	$output = "<?\n";
	$output .= "//Created at " . date("l F j, Y, g:i a") . "\n";
	$output .= "//Do not modify manually\n\n";

//	$output .= "\$config = array();\n";
	$output .= "\$blocks = array();\n";

//config
	$query = "SELECT name,value FROM config";
	$result = $db->query($query);

	while(list($name,$value) = $db->fetchrow($result,DB_NUM))
		$output .= "\$config['$name'] = '$value';\n";
	$output .= "\n";

	flockwrite($output, $docRoot . "/include/dynconfig/config.php");
}

function writeLocs(){
	global $docRoot,$db;

	$output = "<?\n";
	$output .= "//Created at " . date("l F j, Y, g:i a") . "\n";
	$output .= "//Do not modify manually\n\n";

	$output .= "\$locsData = array();\n";
	$output .= "\$locsChildren = array();\n";
	$output .= "\$locsParents = array();\n";

	$query = "SELECT * FROM locs";
    $result = $db->query($query);

	while($line = $db->fetchrow($result)){
		$output .= "\$locsData['" . $line['id'] . "'] = '" . addslashes($line['name']) . "';\n";
		$output .= "\$locsChildren['" . $line['parent'] . "']['" . $line['id'] . "'] = '" . addslashes($line['name']) . "';\n";
		$output .= "\$locsParents['" . $line['id'] . "']['" . $line['parent'] . "'] = '" . addslashes($line['name']) . "';\n";
	}
	$output .= "\n";

	flockwrite($output, $docRoot . "/include/dynconfig/locs.php");

	flockwrite("document.write(\"" . makeCatSelect(makeBranch(getChildData("locs"))) ."\");",
		 			$docRoot . "/include/dynconfig/locs.js");

	//check http://www.javascriptkit.com/jsref/select.shtml for way of adding them dynamically
}

function writeCats(){
	global $docRoot,$db;

	$output = "<?\n";
	$output .= "//Created at " . date("l F j, Y, g:i a") . "\n";
	$output .= "//Do not modify manually\n\n";

	$output .= "\$catsData = array();\n";
	$output .= "\$catsChildren = array();\n";
	$output .= "\$catsParents = array();\n";

	$query = "SELECT * FROM cats";
    $result = $db->query($query);

	while($line = $db->fetchrow($result)){
		$output .= "\$catsData['" . $line['id'] . "'] = '" . addslashes($line['name']) . "';\n";
		$output .= "\$catsChildren['" . $line['parent'] . "']['" . $line['id'] . "'] = '$line[name]';\n";
		$output .= "\$catsParents['" . $line['id'] . "']['" . $line['parent'] . "'] = '$line[name]';\n";
	}
	$output .= "\n";

	flockwrite($output, $docRoot . "/include/dynconfig/cats.php");
}

function writeScheduleCats(){
	global $docRoot,$db;

	$output = "<?\n";
	$output .= "//Created at " . date("l F j, Y, g:i a") . "\n";
	$output .= "//Do not modify manually\n\n";

	$output .= "\$schedulecatsData = array();\n";
	$output .= "\$schedulecatsChildren = array();\n";
	$output .= "\$schedulecatsParents = array();\n";

	$query = "SELECT * FROM schedulecats";
    $result = $db->query($query);

	while($line = $db->fetchrow($result)){
		$output .= "\$schedulecatsData['" . $line['id'] . "'] = '" . addslashes($line['name']) . "';\n";
		$output .= "\$schedulecatsChildren['" . $line['parent'] . "']['" . $line['id'] . "'] = '$line[name]';\n";
		$output .= "\$schedulecatsParents['" . $line['id'] . "']['" . $line['parent'] . "'] = '$line[name]';\n";
	}
	$output .= "\n";


	flockwrite($output, $docRoot . "/include/dynconfig/schedulecats.php");
}

function writeFaqCats(){
	global $docRoot,$db;

	$output = "<?\n";
	$output .= "//Created at " . date("l F j, Y, g:i a") . "\n";
	$output .= "//Do not modify manually\n\n";

	$output .= "\$faqcatsData = array();\n";
	$output .= "\$faqcatsChildren = array();\n";
	$output .= "\$faqcatsParents = array();\n";

	$query = "SELECT * FROM faqcats";
    $result = $db->query($query);

	while($line = $db->fetchrow($result)){
		$output .= "\$faqcatsData['" . $line['id'] . "'] = '" . addslashes($line['name']) . "';\n";
		$output .= "\$faqcatsChildren['" . $line['parent'] . "']['" . $line['id'] . "'] = '$line[name]';\n";
		$output .= "\$faqcatsParents['" . $line['id'] . "']['" . $line['parent'] . "'] = '$line[name]';\n";
	}
	$output .= "\n";

	flockwrite($output, $docRoot . "/include/dynconfig/faqcats.php");
}


