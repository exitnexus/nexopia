<?

	$login=1;

	require_once("include/general.lib.php");

	$isAdmin = $mods->isAdmin($userData['userid'],'viewinvoice');

	if(!$isAdmin)
		die("don't have permissions");


	$invoices = array();

	$methods = getREQval('methods', 'array');
	$contacts = getREQval('contacts', 'array');

	$startmonth = getREQval('startmonth', 'int', userdate("n"));
	$startday = getREQval('startday', 'int', 1);
	$startyear = getREQval('startyear', 'int', userdate("Y"));

	$endmonth = getREQval('endmonth', 'int', userdate("n"));
	$endday = getREQval('endday', 'int', 31);
	$endyear = getREQval('endyear', 'int', userdate("Y"));


	$reports = array(	0 => "Full",
						1 => "Summary",
						2 => "Errors",
						3 => "Summary by Type",
						4 => "Summary by Contact",
						5 => "Daily Summary",
						6 => "Moneris Summary",

						);


	for($i=1;$i<=12;$i++)
		$months[$i] = date("F", mktime(0,0,0,$i,1,0));

	if($action == "Go"){
		$start = gmmktime(0,0,0,$startmonth,$startday,$startyear);
		$end =  gmmktime(23,59,59,$endmonth,$endday,$endyear);

		if(!isset($sortt) || ($sortt != 'creationdate' && $sortt != 'paymentdate'))
			$sortt = 'creationdate';

		$query = "SELECT SQL_CALC_FOUND_ROWS id, userid, amountpaid, paymentdate, paymentmethod, paymentcontact, txnid, total, completed FROM invoice WHERE completed = 'y' &&";

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

		$res = $shoppingcart->db->prepare_array_query($query, $params);

		$uids = array();
		while($line = $res->fetchrow()){
			$invoices[] = $line;
			$uids[$line['userid']] = $line['userid'];
		}
		
		$usernames = getUserName($uids);
		
		foreach($invoices as $k => $v)
			$invoices[$k]['username'] = $usernames[$v['userid']];
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
					echo "<td class=body nowrap><a class=body href=/invoice.php?id=$invoice[id]>$invoice[id]</a></td>";
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
						echo "<td class=body nowrap><a class=body href=/invoice.php?id=$invoice[id]>$invoice[id]</a></td>";
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

				if(!count($methods)) //set above
					$methods = array_keys($shoppingcart->paymentmethods);

				$totals = array();
				$nums = array();
				foreach($methods as $v){
					$totals[$v] = 0;
					$nums[$v] = 0;
				}

				$total = 0;
				$num = 0;
				foreach($invoices as $invoice){
					$totals[$invoice['paymentmethod']] += $invoice['amountpaid'];
					$nums[$invoice['paymentmethod']]++;

					$total += $invoice['amountpaid'];
					$num++;

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
					echo "<td class=body align=right>$nums[$method]</td>";
					echo "<td class=body align=right>\$" . number_format($totals[$method],2) . "</td>";
					echo "</tr>";
				}

				echo "<tr>";
				echo "<td class=header></td>";
				echo "<td class=header align=right>$num</td>";
				echo "<td class=header align=right>\$" . number_format($total,2) . "</td>";
				echo "</tr>";

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
				echo "<td class=header align=center rowspan=2>Date</td>";
				echo "<td class=header align=center colspan=2>Absolute</td>";
				echo "<td class=header align=center colspan=2>Week Average</td>";
				echo "</tr>";

				echo "<tr>";
				echo "<td class=header align=center>Invoices</td>";
				echo "<td class=header align=center>Revenue</td>";
				echo "<td class=header align=center>Invoices</td>";
				echo "<td class=header align=center>Revenue</td>";
				echo "</tr>";

				$nums = array();
				$totals = array();

				foreach($days as $date => $day){
					echo "<tr>";
					echo "<td class=body>$date</td>";
					echo "<td class=body align=right>$day[num]</td>";
					echo "<td class=body align=right>\$" . number_format($day['total'], 2) . "</td>";

					array_push($nums, $day['num']);
					array_push($totals, $day['total']);

					$avgnum = 0;
					$avgtotal = 0;

					if(count($nums) >= 7){
						$avgnum = array_sum($nums) / 7;
						$avgtotal = array_sum($totals) / 7;

						array_shift($nums);
						array_shift($totals);
					}

					echo "<td class=body align=right>" . number_format($avgnum) . "</td>";
					echo "<td class=body align=right>\$" . number_format($avgtotal, 2) . "</td>";

					echo "</tr>";

				}

				echo "<tr>";
				echo "<td class=header></td>";
				echo "<td class=header align=right>$num</td>";
				echo "<td class=header align=right>\$" . number_format($total, 2) . "</td>";
				echo "<td class=header align=right></td>";
				echo "<td class=header align=right></td>";
				echo "</tr>";

				if(time() > $end){
					echo "<tr>";
					echo "<td class=header>Expected</td>";
					echo "<td class=header align=right>" . number_format((($end-$start)/(time()-$start)) * $num) . "</td>";
					echo "<td class=header align=right>\$" . number_format((($end-$start)/(time()-$start)) * $total, 2) . "</td>";
					echo "<td class=header align=right></td>";
					echo "<td class=header align=right></td>";
					echo "</tr>";
				}

				echo "</table>";
				break;

			case 6: // visa/mc/debit daily totals

				$days = array();

				foreach($invoices as $invoice){

					if(!($invoice['paymentmethod'] == 'visa' || $invoice['paymentmethod'] == 'mc' || $invoice['paymentmethod'] == 'debit'))
						continue;

					$batch = substr($invoice['txnid'], 0, -4);
					$id = ltrim(substr($invoice['txnid'], -4, -1), '0');


					if(!isset($days[$batch]))
						$days[$batch] = array(	"total" => 0, "num" => 0,
												'firstid' => $id, 'lastid' => $id,
												'firstdate' => $invoice['paymentdate'],
												'lastdate' => $invoice['paymentdate'],
												'visa' => array("total" => 0, "num" => 0),
												'mc' => array("total" => 0, "num" => 0),
												'debit' => array("total" => 0, "num" => 0),
												);

					$days[$batch]['total'] += $invoice['amountpaid'];
					$days[$batch]['num']++;

					$days[$batch][$invoice['paymentmethod']]['total'] += $invoice['amountpaid'];
					$days[$batch][$invoice['paymentmethod']]['num']++;

					if($id < $days[$batch]['firstid'])		$days[$batch]['firstid'] = $id;
					if($id > $days[$batch]['lastid'])		$days[$batch]['lastid'] = $id;

					if($invoice['paymentdate'] < $days[$batch]['firstdate'])	$days[$batch]['firstdate'] = $invoice['paymentdate'];
					if($invoice['paymentdate'] > $days[$batch]['lastdate'])		$days[$batch]['lastdate'] = $invoice['paymentdate'];

				}

				echo "<table align=center>";

				echo "<tr>";
				echo "<td class=header align=center rowspan=2>Batch set</td>";
				echo "<td class=header align=center colspan=2>IDs</td>";
				echo "<td class=header align=center colspan=2>Total</td>";
				echo "<td class=header align=center colspan=2>Visa</td>";
				echo "<td class=header align=center colspan=2>MC</td>";
				echo "<td class=header align=center colspan=2>Debit</td>";
				echo "<td class=header align=center colspan=2>Dates GMT</td>";
				echo "</tr>";

				echo "<tr>";
				echo "<td class=header align=center>First</td>"; //ids
				echo "<td class=header align=center>Last</td>";
				echo "<td class=header align=center>Invoices</td>"; //total
				echo "<td class=header align=center>Revenue</td>";
				echo "<td class=header align=center>Invoices</td>"; //visa
				echo "<td class=header align=center>Revenue</td>";
				echo "<td class=header align=center>Invoices</td>"; //mc
				echo "<td class=header align=center>Revenue</td>";
				echo "<td class=header align=center>Invoices</td>"; //debit
				echo "<td class=header align=center>Revenue</td>";
				echo "<td class=header align=center>Start</td>"; //dates
				echo "<td class=header align=center>End</td>";
				echo "</tr>";

				foreach($days as $batch => $day){
					echo "<tr>";
					echo "<td class=body>$batch</td>";
					echo "<td class=body align=right>" . $day['firstid'] . "</td>";
					echo "<td class=body align=right>" . $day['lastid'] . "</td>";
					echo "<td class=body align=right>" . $day['num'] . "</td>";
					echo "<td class=body align=right>\$" . number_format($day['total'], 2) . "</td>";
					echo "<td class=body align=right>" . $day['visa']['num'] . "</td>";
					echo "<td class=body align=right>\$" . number_format($day['visa']['total'], 2) . "</td>";
					echo "<td class=body align=right>" . $day['mc']['num'] . "</td>";
					echo "<td class=body align=right>\$" . number_format($day['mc']['total'], 2) . "</td>";
					echo "<td class=body align=right>" . $day['debit']['num'] . "</td>";
					echo "<td class=body align=right>\$" . number_format($day['debit']['total'], 2) . "</td>";

					echo "<td class=body align=right>" . gmdate("M j, G:i", $day['firstdate']) . "</td>";
					echo "<td class=body align=right>" . gmdate("M j, G:i", $day['lastdate']) . "</td>";

					echo "</tr>";

				}

				echo "</table>";
				break;

		}

		if($formatting)
			incFooter();
	}

