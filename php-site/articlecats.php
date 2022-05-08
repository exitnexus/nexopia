<?

	$login=1;

	require_once("include/general.lib.php");

	$data = getChildData("cats");
	$branch = makebranch($data);


	incHeader();

	echo "<table>";

	foreach($branch as $line){
		echo "<tr><td class=body>";
		for($i=0;$i<$line['depth']-1;$i++)
			echo "&nbsp;- ";
		echo "<a class=body href=articlelist.php?cat=$line[id]>$line[name]</a>";
		echo "</td></tr>\n";
	}

	echo "</table>";

    incFooter();
