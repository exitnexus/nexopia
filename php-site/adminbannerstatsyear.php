<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"stats"))
		die("Permission denied");


	$start = usermktime(0,0,0,2,1,2005);
	$end = time();


	$res = $banner->db->prepare_query("SELECT size, time, views FROM bannertypestats WHERE time >= # && time <= # ORDER BY time, size", $start, $end);

	$rows = array();
	$sizes = array();
	
	while($line = $res->fetchrow()){
		$rows[$line['time']][$line['size']][] = $line['views'];
		if(!isset($sizes[$line['size']]))
			$sizes[$line['size']] = $line['size'];
	}

	ksort($rows);


	incHeader();

	echo "<table align=center>";

	echo "<tr>";
	echo "<td class=header align=center>Date</td>";
	foreach($sizes as $size)
		echo "<td class=header align=center>" . $banner->sizes[$size] . "</td>";
	echo "<td class=header align=center>Total</td>";

	echo "</tr>";


	$lastrow = false;
	
	$yesterdate = '';
	$date = '';
	
	$today = array();
	
	foreach($rows as $time => $line){
		$date = userdate("M j, Y", $time);

		if($yesterdate != $date){
			echo "<tr>";
			echo "<td class=body>$date</td>";
			
			$total = 0;
			foreach($sizes as $size){
			
				$num = 0;
				foreach($line[$size] as $v)
					$num += $v;

				echo "<td class=body align=right>" . number_format($num) . "</td>";
				$total += $num;
			}
			echo "<td class=body align=right>" . number_format($total) . "</td>";

			echo "</tr>";
			$today = array();
		}

		$yesterdate = $date;
	}


	echo "</table>";

	incFooter();

