<?

	$login=1;

	require_once("include/general.lib.php");

	$isAdmin = $mods->isAdmin($userData['userid'],'viewinvoice');

	$page = getREQval('page', 'int');

	if($isAdmin){
		$uid = getREQval('uid');
		$id = ($uid ? getUserID($uid) : 0);
	}else{
		$uid = $userData['username'];
		$id = $userData['userid'];
	}

	if($id)
		$shoppingcart->db->prepare_query("SELECT SQL_CALC_FOUND_ROWS id, userid, username, creationdate, total, amountpaid, paymentdate, paymentmethod, paymentcontact, completed, valid FROM invoice WHERE userid = #" . ($isAdmin ? "" : " && valid = 'y'") . " ORDER BY id DESC LIMIT " . ($page*$config['linesPerPage']) . ", $config[linesPerPage]", $id);
	else
		$shoppingcart->db->query("SELECT SQL_CALC_FOUND_ROWS id, userid, username, creationdate, total, amountpaid, paymentdate, paymentmethod, paymentcontact, completed, valid FROM invoice ORDER BY id DESC LIMIT " . ($page*$config['linesPerPage']) . ", $config[linesPerPage]");

	$invoices = array();
	while($line = $shoppingcart->db->fetchrow())
		$invoices[] = $line;

	$shoppingcart->db->query("SELECT FOUND_ROWS()");
	$numrows = $shoppingcart->db->fetchfield();
	$numpages =  ceil($numrows / $config['linesPerPage']);


	incHeader(true,array('incShoppingCartMenu'));


	echo "<table>";

	echo "<tr>";
	echo "<td class=header>Invoice</td>";
	echo "<td class=header>Username</td>";
	echo "<td class=header>Date</td>";
	echo "<td class=header>Total</td>";
	echo "<td class=header>Paid</td>";
	echo "<td class=header>Method</td>";
	if($isAdmin)
		echo "<td class=header>Contact</td>";
	echo "<td class=header>Payment Date</td>";
	echo "<td class=header>Complete</td>";
	if($isAdmin)
		echo "<td class=header>Valid</td>";
	echo "</tr>";

	foreach($invoices as $invoice){
		echo "<tr>";
		echo "<td class=body><a class=body href=invoice.php?id=$invoice[id]>$invoice[id]</a></td>";
		echo "<td class=body><a class=body href=profile.php?uid=$invoice[userid]>$invoice[username]</a></td>";
		echo "<td class=body>" . userDate("F j, Y, g:i a", $invoice['creationdate']) . "</td>";
		echo "<td class=body align=right>\$$invoice[total]</td>";
		echo "<td class=body align=right>\$$invoice[amountpaid]</td>";
		echo "<td class=body>" . ($invoice['amountpaid'] != "0.00" ? $invoice['paymentmethod'] : "" ) . "</td>";
		if($isAdmin)
			echo "<td class=body>" . ($invoice['amountpaid'] != "0.00" ? $invoice['paymentcontact'] : "" ) . "</td>";
		echo "<td class=body>" . ($invoice['paymentdate'] ? userDate("M j, Y, g:i a", $invoice['paymentdate']) : "" ) . "</td>";
		echo "<td class=body>" . ($invoice['completed'] == 'y' ? "Complete" : "" ) . "</td>";
		if($isAdmin)
			echo "<td class=body>" . ($invoice['completed'] == 'y' ? "Yes" : "No" ) . "</td>";
		echo "</tr>";
	}

	echo "<tr><td class=header colspan=10>";

	echo "<table width=100%><tr>";

	if($isAdmin){
		echo "<form action=$_SERVER[PHP_SELF]><td class=header>";

		echo "User: <input type=text class=body name=uid value=$uid><input class=body type=submit value=Go>";

		echo "</td></form>";
	}

	echo "<td align=right class=header>Page: " . pageList("$_SERVER[PHP_SELF]",$page,$numpages,'header') . "</td>";

	echo "</tr></table>";
	echo "</td></tr>";
	echo "</table>";

	incFooter();

