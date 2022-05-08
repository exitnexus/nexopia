<?

	$login=1;

	require_once("include/general.lib.php");

	$isAdmin = $mods->isAdmin($userData['userid'],'viewinvoice');


	if(empty($page) || !is_numeric($page))
		$page = 0;

	if($isAdmin)
		$db->query("SELECT SQL_CALC_FOUND_ROWS id,userid,username,creationdate,total,amountpaid,paymentdate,paymentmethod,completed FROM invoice ORDER BY creationdate DESC LIMIT " . ($page*$config['linesPerPage']) . ", $config[linesPerPage]");
	else
		$db->prepare_query("SELECT SQL_CALC_FOUND_ROWS id,userid,username,creationdate,total,amountpaid,paymentdate,paymentmethod,completed FROM invoice WHERE userid = ? ORDER BY creationdate DESC LIMIT " . ($page*$config['linesPerPage']) . ", $config[linesPerPage]", $userData['userid']);

	$invoices = array();
	while($line = $db->fetchrow())
		$invoices[] = $line;

	$db->query("SELECT FOUND_ROWS()");
	$numrows = $db->fetchfield();
	$numpages =  ceil($numrows / $config['linesPerPage']);



	incHeader(true,array('incShoppingCartMenu'));


	echo "<table>";

	echo "<tr>";
	echo "<td class=header>Invoice</td>";
	echo "<td class=header>Username</td>";
	echo "<td class=header>Date</td>";
	echo "<td class=header>Total</td>";
	echo "<td class=header>Paid</td>";
	echo "<td class=header>Payment Method</td>";
	echo "<td class=header>Payment Date</td>";
	echo "<td class=header>Complete</td>";
	echo "</tr>";

	foreach($invoices as $invoice){
		echo "<tr>";
		echo "<td class=body><a class=body href=invoice.php?id=$invoice[id]>$invoice[id]</a></td>";
		echo "<td class=body><a class=body href=profile.php?uid=$invoice[userid]>$invoice[username]</a></td>";
		echo "<td class=body>" . userDate("F j, Y, g:i a", $invoice['creationdate']) . "</td>";
		echo "<td class=body align=right>\$$invoice[total]</td>";
		echo "<td class=body align=right>\$$invoice[amountpaid]</td>";
		echo "<td class=body>" . ($invoice['amountpaid'] != "0.00" ? $invoice['paymentmethod'] : "" ) . "</td>";
		echo "<td class=body>" . ($invoice['paymentdate'] ? userDate("F j, Y, g:i a", $invoice['paymentdate']) : "" ) . "</td>";
		echo "<td class=body>" . ($invoice['completed'] == 'y' ? "Complete" : "" ) . "</td>";
		echo "</tr>";
	}

	echo "<tr><td class=header colspan=8 align=right>Page: " . pageList("$PHP_SELF",$page,$numpages,'header') . "</td></tr>";
	echo "</table>";

	incFooter();

