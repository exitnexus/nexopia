<?

	include("include/general.lib.php");

	if(!isset($forceserver))
		$forceserver = false;

	if(isset($action)){
		setcookie("forceserver", isset($force),0,'/',$cookiedomain);
		$forceserver = isset($force);
	}

	echo "<form action=$_SERVER[PHP_SELF]>";
	echo "<input type=checkbox id=force name=force " . ($forceserver ? " checked" : "") . "><label for=force>  Force Server ? </label><input type=submit name=action value=Update></form>";

