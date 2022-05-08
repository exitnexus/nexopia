<?	

//	require_once("include/general.lib.php");
//	$userData = auth($userid,$key,false);

$year = 2003;
$month= 3;
$numDays=31;

	echo  date("F j, Y, g:i a");

	echo "<table border=1 cellspacing=0 cellpadding=2>";
	for($i=-25;$i<=25;$i++){
		echo "<tr>";
		putenv ("TZ=GMT" . $i);
		echo "<td>" . getenv("TZ") . "</td><td>" . date("F j, Y, g:i a") . "</td>";
//		echo "<td>" . mktime(date("G"),date("i"),date("s"),date("n"),date("j"),date("Y")) . "</td><td>" . mktime(date("G"),date("i"),date("s") + date("Z"),date("n"),date("j"),date("Y")) . "</td>";

//		echo "<td>" . gmmktime(date("G"),date("i"),date("s"),date("n"),date("j"),date("Y")) . "</td>";
//		echo "<td>" . time() . "</td>";

//		echo "<td>";
//		echo mktime(23,59,59,$month,$numDays,$year);
//		echo date("F j, Y, g:i a",mktime(23,59,59,$month,$numDays,$year));
//		echo "</td>";
		echo "</tr>";
	}
	echo "</table>";
	
