<?

	$forceserver=true;
	$login = 0;

	include_once("include/general.lib.php");
/*
	$db->prepare_query("SELECT buzzword FROM buzzwords ORDER BY rand() LIMIT 25");

	$buzzwords = $db->fetchrowset();
*/
	$buzzwords = array(
"Synergy",
"Strategic Fit",
"Gap Analysis",
"Best Practice",
"Bottom Line",
"Revisit",
"Bandwidth",
"Hardball",
"Out of the Loop",
"Benchmark",
"Value-Added",
"Proactive",
"Win-Win",
"Think Outside the Box",
"Fast Track",
"Result-Driven",
"Empower / Empowerment",
"Knowledge Base",
"Total Quality / Quality Driven",
"Touch Base",
"Mindset",
"Client Focus",
"Ball Park",
"Game Plan",
"Leverage",
"Paradigm Shift");

	shuffle($buzzwords);

?>
<html><head><title>Buzzword Bingo</title></head><body>
<?="<scr" . "ipt>"?>

var marked = new Array(<?= implode(", ", array_fill(0, 25, 0)) ?>);
var buzzwords = new Array('<?= implode("', '", $buzzwords) ?>');

function putinnerHTML(div,str){
	if(document.all){
		document.all[div].innerHTML = str;
	}else{
		eval("document.getElementById('" + div + "').innerHTML = str;");
	}
}

function mark(i){
	putinnerHTML('buzz' + i, (marked[i] ? buzzwords[i] : '<b><u>' + buzzwords[i] + '</u></b>'));
	marked[i] = !marked[i];
}

</script>

<?

	echo "<table cellspacing=0 border=1 width=650>";

	echo "<tr><td colspan=5 align=center><b><font size=7>Buzzword Bingo</font></b></td></tr>";

	echo "<tr><td colspan=5><b>How to play:</b> Mark off each block when you hear these words during a meeting, seminar or phone call.<br>When you get five blocks horizontally, vertically, or diagonally, stand up and shout <b>BULLSHIT!!</b></td></tr>";

	for($i=0; $i < 25; $i++){
		if($i % 5 == 0)
			echo "<tr height=90>";

		echo "<td align=center width=20% id=buzz$i name=buzz$i onClick=\"mark($i)\">$buzzwords[$i]</a></div></td>";

		if($i % 5 == 4)
			echo "</tr>";
	}

	echo "</table>";
?>
</body></html>

