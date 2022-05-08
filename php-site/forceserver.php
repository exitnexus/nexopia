<?

	$forceserver = true;

	include("include/general.lib.php");

	if($action){
		$force = getREQval('force','bool');
		setcookie("forceserver", $force,0,'/',$cookiedomain);
		$forceserver = $force;
	}

	echo "<form action=$_SERVER[PHP_SELF]>";
	echo "<input type=checkbox id=force name=force " . ($forceserver ? " checked" : "") . "><label for=force>  Force Server ? </label><input type=submit name=action value=Update></form>";

