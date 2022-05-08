<?

	$login=1;

	require_once("include/general.lib.php");

	if(empty($id))
		die("Bad invoice id");


	$isAdmin = $mods->isAdmin($userData['userid'],'viewinvoice');

	$db->prepare_query("SELECT userid,username,creationdate,total,paymentdate,amountpaid,completed,paymentmethod,paypaltxnid FROM invoice WHERE id = ?", $id);

	if($db->numrows() == 0)
		die("Bad invoice id");

	$invoice = $db->fetchrow();

	if(!$isAdmin && $invoice['userid'] != $userData['userid'])
		die("Bad invoice id");

	$db->prepare_query("SELECT productid,quantity,price,name,products.input as inputtype, invoiceitems.input FROM invoiceitems,products WHERE invoiceitems.productid = products.id && invoiceitems.invoiceid = ?", $id);

	$rows = array();

	$mcids = array();

	while($line = $db->fetchrow()){
		$rows[] = $line;
		if($line['inputtype']=='mc')
			$mcids[] = $line['input'];
	}

	$mcs = array();

	$db->prepare_query("SELECT id,name FROM productinputchoices WHERE id IN (?)", $mcids);

	while($line = $db->fetchrow())
		$mcs[$line['id']] = $line['name'];


	echo "<html><head><title>$config[title]</title></head><body>";


	echo "<table width=400 cellspacing=0 border=1>";
	echo "<tr><td class=header>Invoice ID:</td><td class=body>$id</td></tr>";
	echo "<tr><td class=header>Username:</td><td class=body>$invoice[username]</td></tr>";
	echo "<tr><td class=header>Userid:</td><td class=body>$invoice[userid]</td></tr>";
	echo "<td class=header>Date:</td><td class=body>" . userDate("F j, Y, g:i a", $invoice['creationdate']) . "</td></tr>";

	echo "</table><br>";

	echo "<table width=100% cellspacing=0 border=1>";

	echo "<tr>";
	echo "<td class=header>Product ID</td>";
	echo "<td class=header>Description</td>";
	echo "<td class=header>Quantity</td>";
	echo "<td class=header>Username</td>";
	echo "<td class=header>Unit Price</td>";
	echo "<td class=header>Total</td>";
	echo "</tr>";

	$total = 0;

	foreach($rows as $row){
		echo "<tr>";
		echo "<td class=body>$row[productid]</td>";
		echo "<td class=body>$row[name]</td>";
		echo "<td class=body>$row[quantity]</td>";
		echo "<td class=body>";
		switch($row['inputtype']){
			case "none": break;
			case "mc":
				echo $mcs[$row['input']];
				break;
			case "text":
				echo $row['input'];
				break;
		}
		echo "</td>";
		echo "<td class=body align=right>\$" . number_format($row['price'],2) . "</td>";
		echo "<td class=body align=right>\$" . number_format($row['price'] * $row['quantity'], 2) . "</td>";
		echo "</tr>";
		$total += round($row['price'] * $row['quantity'],2);
	}

	echo "<tr><td class=body align=right colspan=5>Total:</td><td class=body align=right>\$" . number_format($total,2) . "</td></tr>";

	echo "<tr><td class=body colspan=4>Sorry, No Refunds or Credits Allowed</td><td class=body align=right>Amount Paid:</td><td class=body align=right>\$$invoice[amountpaid]</td></tr>";

	echo "<tr></tr>";

	echo "</table>";

	echo "<script>window.print();</script>";

	echo "</body></html>";
