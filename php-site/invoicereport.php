<?

	$login=1;

	require_once("include/general.lib.php");

	$isAdmin = isAdmin($userData['userid'],'viewinvoice');

	if(!$isAdmin)
		die("don't have permissions");



	$invoices = array();

	if(empty($methods))
		$methods = array();
	if(empty($startmonth))
		$startmonth = 1;
	if(empty($startday))
		$startday = 1;
	if(empty($startyear))
		$startmonth = 2004;

	if(empty($endmonth))
		$endmonth = 1;
	if(empty($endday))
		$endday = 31;
	if(empty($endyear))
		$endmonth = 2004;



	for($i=1;$i<=12;$i++)
		$months[$i] = date("F", mktime(0,0,0,$i,1,0));

	$paymentmethods = array('paypal','cash','debit','moneyorder','cheque');

	if($action == "Go"){
		$start = gmmktime(0,0,0,$startmonth,$startday,$startyear);
		$end =  gmmktime(23,59,59,$endmonth,$endday,$endyear);

		$db->prepare_query("SELECT SQL_CALC_FOUND_ROWS id,userid,username,amountpaid,paymentdate,paymentmethod FROM invoice WHERE completed = 'y' && paymentmethod IN (?) && paymentdate >= ? && paymentdate < ? ORDER BY creationdate", $methods, $start, $end);

		while($line = $db->fetchrow())
			$invoices[] = $line;
	}

	if(count($invoices) == 0){

		echo "<table><form action=$PHP_SELF>";

		echo "<tr>";
		echo "<td>";
			echo "<select name=methods[] size=" . count($paymentmethods) . " multiple=multiple>";
			foreach($paymentmethods as $k => $v){
				echo "<option value='$v'";
				if(in_array($k,$methods))
					echo " selected";
				echo ">$v";
			}
			echo "</select>";
		echo "</td>";
		echo "<td align=right>";
			echo "Start: <select class=body name=\"startmonth\"><option value=''>Month" . make_select_list_key($months, $startmonth) . "</select>";
			echo "<select class=body name=\"startday\"><option value=''>Day" . make_select_list(range(1,31),$startday) . "</select>";
			echo "<select class=body name=\"startyear\"><option value=''>Year" . make_select_list(range(2004,userdate("Y")),$startyear) . "</select><br>";

			echo "End: <select class=body name=\"endmonth\"><option value=''>Month" . make_select_list_key($months, $endmonth) . "</select>";
			echo "<select class=body name=\"endday\"><option value=''>Day" . make_select_list(range(1,31),$endday) . "</select>";
			echo "<select class=body name=\"endyear\"><option value=''>Year" . make_select_list(range(2004,userdate("Y")),$endyear) . "</select><br>";

			echo "<input type=submit name=action value=Go>";
		echo "</td>";
		echo "</tr>";
		echo "</form></table>";

	}else{

		echo "<table>";

		echo "<tr><td colspan=5>From " . gmdate("F j, Y, g:i a", $start) . " GMT to " . gmdate("F j, Y, g:i a", $end) . " GMT</td></tr>";
		echo "<tr><td colspan=5>Payment types: " . implode(", ", $methods) . "</td></tr>";

		echo "<tr>";
		echo "<td class=header>Invoice</td>";
		echo "<td class=header>Username</td>";
		echo "<td class=header>Paid</td>";
		echo "<td class=header>Payment Date</td>";
		echo "<td class=header>Payment Method</td>";
		echo "</tr>";

		$total = 0;
		foreach($invoices as $invoice){
			echo "<tr>";
			echo "<td class=body nowrap>$invoice[id]</td>";
			echo "<td class=body nowrap>$invoice[username]</td>";
			echo "<td class=body nowrap align=right>\$$invoice[amountpaid]</td>";
			echo "<td class=body nowrap>" . ($invoice['paymentdate'] ? gmdate("F j, Y, g:i a", $invoice['paymentdate']) . " GMT" : "" ) . "</td>";
			echo "<td class=body nowrap>$invoice[paymentmethod]</td>";
			echo "</tr>";
			$total += $invoice['amountpaid'];
		}
		echo "<tr><td class=header></td><td class=header></td><td class=header align=right>\$" . number_format($total,2) . "</td><td class=header colspan=2></td></tr>";

		echo "</table>";

	}

