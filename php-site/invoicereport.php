<?

	$login=1;

	require_once("include/general.lib.php");

	$isAdmin = $mods->isAdmin($userData['userid'],'viewinvoice');

	if(!$isAdmin)
		die("don't have permissions");


	$invoices = array();

	if(empty($methods))
		$methods = array();
	if(empty($contacts))
		$contacts = array();
	if(empty($startmonth))
		$startmonth = userdate("n");
	if(empty($startday))
		$startday = 1;
	if(empty($startyear))
		$startyear = userdate("Y");

	if(empty($endmonth))
		$endmonth = userdate("n");
	if(empty($endday))
		$endday = 31;
	if(empty($endyear))
		$endyear = userdate("Y");


	$reports = array(	0 => "Full",
						1 => "Summary",
						2 => "Errors",
						3 => "Summary by Type",
						4 => "Summary by Contact",
						5 => "Daily Summary",

						);


	for($i=1;$i<=12;$i++)
		$months[$i] = date("F", mktime(0,0,0,$i,1,0));

	if($action == "Go"){
		$start = gmmktime(0,0,0,$startmonth,$startday,$startyear);
		$end =  gmmktime(23,59,59,$endmonth,$endday,$endyear);

		if(!isset($sortt) || ($sortt != 'creationdate' && $sortt != 'paymentdate'))
			$sortt = 'creationdate';

		$query = "SELECT SQL_CALC_FOUND_ROWS id, userid, username, amountpaid, paymentdate, paymentmethod, paymentcontact, txnid, total, completed FROM invoice WHERE completed = 'y' &&";

		if(count($methods)){
			$query .= "paymentmethod IN (?) && ";
			$params[] = $methods;
		}
		if(count($contacts)){
			$query .= "paymentcontact IN (?) && ";
			$params[] = $contacts;
		}

		$query .= "paymentdate >= ? && paymentdate < ? ORDER BY $sortt";
		$params[] = $start;
		$params[] = $end;

		$shoppingcart->db->prepare_array_query($query, $params);

		while($line = $shoppingcart->db->fetchrow())
			$invoices[] = $line;
	}

	if(count($invoices) == 0){

		incHeader(true,array('incShoppingCartMenu'));

		echo "<table><form action=$_SERVER[PHP_SELF]>";

		echo "<tr>";
			echo "<td class=header>Payment Method</td>";
			echo "<td class=header>Contact</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td class=body>";
			echo "<select name=methods[] size=" . count($shoppingcart->paymentmethods) . " multiple=multiple>";
			foreach($shoppingcart->paymentmethods as $k => $v){
				echo "<option value='$k'";
				if(in_array($k,$methods))
					echo " selected";
				echo ">$v";
			}
			echo "</select>";
		echo "</td>";
		echo "<td class=body>";
			echo "<select name=contacts[] size=" . (count($shoppingcart->paymentcontacts)+1) . " multiple=multiple>";
			echo "<option value=''> ";
			foreach($shoppingcart->paymentcontacts as $k => $v){
				echo "<option value='$v'";
				if(in_array($v,$contacts))
					echo " selected";
				echo ">$v";
			}
			echo "</select>";
		echo "</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td align=right colspan=2 class=body>";
			echo "Start: <select class=body name=\"startmonth\"><option value=''>Month" . make_select_list_key($months, $startmonth) . "</select>";
			echo "<select class=body name=\"startday\"><option value=''>Day" . make_select_list(range(1,31),$startday) . "</select>";
			echo "<select class=body name=\"startyear\"><option value=''>Year" . make_select_list(range(2004,userdate("Y")),$startyear) . "</select><br>";

			echo "End: <select class=body name=\"endmonth\"><option value=''>Month" . make_select_list_key($months, $endmonth) . "</select>";
			echo "<select class=body name=\"endday\"><option value=''>Day" . make_select_list(range(1,31),$endday) . "</select>";
			echo "<select class=body name=\"endyear\"><option value=''>Year" . make_select_list(range(2004,userdate("Y")),$endyear) . "</select><br>";

			echo "Sort By: <select class=body name=\"sortt\"><option value=paymentdate>Payment Date<option value=creationdate>Creationdate</select><br>";
			echo "Include Formatting: <input type=checkbox class=body name=formatting><br>";

			echo "Report: <select class=body name=report>" . make_select_list_key($reports) . "</select><br>";

			echo "<input class=body type=submit name=action value=Go>";
		echo "</td>";
		echo "</tr>";
		echo "</form></table>";

		incFooter();

	}else{

		$report = $_REQUEST['report'];

		$formatting  = isset($_REQUEST['formatting']);

		if($formatting)
			incHeader(true,array('incShoppingCartMenu'));

		echo "<table width=100%>";

		echo "<tr><td class=body>Start:</td><td class=body>" . gmdate("F j, Y, g:i a", $start) . " GMT</td></tr>";
		echo "<tr><td class=body>End:</td><td class=body>" . gmdate("F j, Y, g:i a", $end) . " GMT</td></tr>";
		echo "<tr><td class=body>Payment types:</td><td class=body>";
		foreach($methods as $method)
			echo $shoppingcart->paymentmethods[$method] . ", ";
		echo "</td></tr>";
		echo "<tr><td class=body>Contacts:</td><td class=body>" . implode($contacts, ", ") . "</td></tr>";

		echo "</table>";
		echo "<br><br>";

		switch($report){
			case 0: //"Full":

				echo "<table width=100%>";

				echo "<tr>";
				echo "<td class=header>Invoice</td>";
				echo "<td class=header>Username</td>";
				echo "<td class=header>Payment Date</td>";
				echo "<td class=header>Paid</td>";
				echo "<td class=header>Payment Method</td>";
				echo "<td class=header>Contact</td>";
				echo "<td class=header>Transaction ID</td>";
				echo "</tr>";

				$total = 0;
				foreach($invoices as $invoice){
					echo "<tr>";
					echo "<td class=body nowrap><a class=body href=invoice.php?id=$invoice[id]>$invoice[id]</a></td>";
					echo "<td class=body nowrap>$invoice[username]</td>";
					echo "<td class=body nowrap>" . ($invoice['paymentdate'] ? gmdate("M j, y, g:i a", $invoice['paymentdate']) . " GMT" : "" ) . "</td>";
					echo "<td class=body nowrap align=right>\$$invoice[amountpaid]</td>";
					echo "<td class=body nowrap>" . $shoppingcart->paymentmethods[$invoice['paymentmethod']] . "</td>";
					echo "<td class=body nowrap>$invoice[paymentcontact]</td>";
					echo "<td class=body nowrap>$invoice[txnid]</td>";
					echo "</tr>";
					$total += $invoice['amountpaid'];
				}
				echo "<tr><td class=header colspan=3>" . count($invoices) . " invoices</td><td class=header align=right>\$" . number_format($total,2) . "</td><td class=header colspan=3></td></tr>";
				echo "</table>";
				break;

			case 1: //"Summary":
				$total = 0;
				foreach($invoices as $invoice)
					$total += $invoice['amountpaid'];

				echo "<table width=100%>";
				echo "<tr><td class=body>Number of invoices:</td><td class=body>" . count($invoices) . "</td></tr>";
				echo "<tr><td class=body>Total revenue:</td><td class=body>\$" . number_format($total, 2) . "</td></tr>";
				echo "</table>";
				break;

			case 2: //"Errors":

				echo "<table width=100%>";

				echo "<tr>";
				echo "<td class=header>Invoice</td>";
				echo "<td class=header>Username</td>";
				echo "<td class=header>Payment Date</td>";
				echo "<td class=header>Paid / Total</td>";
				echo "<td class=header>Payment Method</td>";
				echo "<td class=header>Contact</td>";
				echo "<td class=header>Transaction ID</td>";
				echo "</tr>";

				$total = 0;
				$num = 0;

				foreach($invoices as $invoice){
					if($invoice['completed'] == 'y' && $invoice['total'] != $invoice['amountpaid']){
						echo "<tr>";
						echo "<td class=body nowrap><a class=body href=invoice.php?id=$invoice[id]>$invoice[id]</a></td>";
						echo "<td class=body nowrap>$invoice[username]</td>";
						echo "<td class=body nowrap>" . ($invoice['paymentdate'] ? gmdate("M j, y, g:i a", $invoice['paymentdate']) . " GMT" : "" ) . "</td>";
						echo "<td class=body nowrap align=right>\$$invoice[amountpaid] / \$$invoice[total]</td>";
						echo "<td class=body nowrap>" . $shoppingcart->paymentmethods[$invoice['paymentmethod']] . "</td>";
						echo "<td class=body nowrap>$invoice[paymentcontact]</td>";
						echo "<td class=body nowrap>$invoice[txnid]</td>";
						echo "</tr>";
						$total += $invoice['total'];
						$num++;
					}
				}
				echo "<tr><td class=body>Number of invoices:</td><td class=body>$num</td></tr>";
				echo "<tr><td class=body>Total revenue:</td><td class=body>\$" . number_format($total, 2) . "</td></tr>";
				echo "</table>";
				break;
			case 3: //By Type

				$totals = array();
				$num = array();
				$methods = array();
				foreach($invoices as $invoice){
					if(!isset($methods[$invoice['paymentmethod']])){
						$totals[$invoice['paymentmethod']] = 0;
						$num[$invoice['paymentmethod']] = 0;
						$methods[$invoice['paymentmethod']] = $invoice['paymentmethod'];
					}

					$totals[$invoice['paymentmethod']] += $invoice['amountpaid'];
					$num[$invoice['paymentmethod']]++;
				}

				echo "<table align=center>";

				echo "<tr>";
				echo "<td class=header align=center>Method</td>";
				echo "<td class=header align=center>Invoices</td>";
				echo "<td class=header align=center>Revenue</td>";
				echo "</tr>";

				foreach($methods as $method){
					echo "<tr>";
					echo "<td class=body>" . $shoppingcart->paymentmethods[$method] . "</td>";
					echo "<td class=body align=right>$num[$method]</td>";
					echo "<td class=body align=right>\$" . number_format($totals[$method],2) . "</td>";
					echo "</tr>";

				}

				echo "</table>";
				break;


			case 4: //By Contact

				$totals = array();
				$num = array();
				$contacts = array();
				$invoicetotals = array();
				$invoicecontacttotals = array();

				foreach($invoices as $invoice){
					if(!isset($contacts[$invoice['paymentcontact']])){
						$totals[$invoice['paymentcontact']] = 0;
						$num[$invoice['paymentcontact']] = 0;
						$contacts[$invoice['paymentcontact']] = $invoice['paymentcontact'];
					}

					if(!isset($invoicetotals[$invoice['amountpaid']]))
						$invoicetotals[$invoice['amountpaid']] = $invoice['amountpaid'];

					if(!isset($invoicecontacttotals[$invoice['paymentcontact']][$invoice['amountpaid']]))
						$invoicecontacttotals[$invoice['paymentcontact']][$invoice['amountpaid']]=0;

					$invoicecontacttotals[$invoice['paymentcontact']][$invoice['amountpaid']]++;

					$totals[$invoice['paymentcontact']] += $invoice['amountpaid'];
					$num[$invoice['paymentcontact']]++;
				}

				ksort($contacts);
				sort($invoicetotals);

				echo "<table align=center>";

				echo "<tr>";
				echo "<td class=header align=center>Contact</td>";
				echo "<td class=header align=center>Invoices</td>";
				echo "<td class=header align=center>Revenue</td>";
				echo "<td class=header align=center>Average</td>";

				foreach($invoicetotals as $amount)
					echo "<td class=header align=center>\$" . number_format($amount,2) . "</td>";

				echo "</tr>";

				$totalrevenue = 0;
				$coltotals = array();

				foreach($contacts as $contact){
					echo "<tr>";
					echo "<td class=body>$contact</td>";
					echo "<td class=body align=right>$num[$contact]</td>";
					echo "<td class=body align=right>\$" . number_format($totals[$contact],2) . "</td>";
					echo "<td class=body align=right>\$" . number_format($totals[$contact]/$num[$contact],2) . "</td>";

					$totalrevenue += $totals[$contact];

					foreach($invoicetotals as $amount){
						if(!isset($invoicecontacttotals[$contact][$amount]))
							$invoicecontacttotals[$contact][$amount]=0;

						if(!isset($coltotals[$amount]))
							$coltotals[$amount] = 0;
						$coltotals[$amount] += $invoicecontacttotals[$contact][$amount];

						echo "<td class=body align=right>" . number_format($invoicecontacttotals[$contact][$amount]) . "</td>";
					}

					echo "</tr>";

				}


				echo "<tr>";
				echo "<td class=header align=center></td>";
				echo "<td class=header align=right>" . count($invoices) . "</td>";
				echo "<td class=header align=right>\$" . number_format($totalrevenue,2) . "</td>";
				echo "<td class=header align=right>\$" . number_format($totalrevenue/count($invoices),2) . "</td>";

				ksort($coltotals);

				foreach($coltotals as $amount)
					echo "<td class=header align=right>" . number_format($amount) . "</td>";

				echo "</tr>";

				echo "</table>";
				break;

			case 5: //daily
				$days = array();
				$total = 0;
				$num = 0;

				foreach($invoices as $invoice){
					$date = gmdate("M j, y", $invoice['paymentdate']);

					if(!isset($days[$date]))
						$days[$date] = array("total" => 0, "num" => 0);

					$days[$date]['total'] += $invoice['amountpaid'];
					$days[$date]['num']++;

					$total += $invoice['amountpaid'];
					$num++;
				}

				echo "<table align=center>";

				echo "<tr>";
				echo "<td class=header align=center>Date</td>";
				echo "<td class=header align=center>Invoices</td>";
				echo "<td class=header align=center>Revenue</td>";
				echo "</tr>";

				foreach($days as $date => $day){
					echo "<tr>";
					echo "<td class=body>$date</td>";
					echo "<td class=body align=right>$day[num]</td>";
					echo "<td class=body align=right>\$" . number_format($day['total'], 2) . "</td>";
					echo "</tr>";

				}

				echo "<tr>";
				echo "<td class=header></td>";
				echo "<td class=header align=right>$num</td>";
				echo "<td class=header align=right>\$" . number_format($total, 2) . "</td>";
				echo "</tr>";

				echo "</table>";
				break;

		}

		if($formatting)
			incFooter();
	}

