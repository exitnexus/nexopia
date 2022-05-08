<?

	$login=1;

	require_once("include/general.lib.php");

	$shoppingcart->db->prepare_query("SELECT shoppingcart.id, productid, quantity, price, name, unitprice, products.input as inputtype, shoppingcart.input FROM shoppingcart, products WHERE shoppingcart.productid = products.id && shoppingcart.userid = # ORDER BY id", $userData['userid']);

	$rows = array();

	$mcids = array();

	while($line = $shoppingcart->db->fetchrow()){
		$rows[] = $line;
		if($line['inputtype']=='mc')
			$mcids[] = $line['input'];
	}

	$mcs = array();

	if(count($mcids)){
		$shoppingcart->db->prepare_query("SELECT id,name FROM productinputchoices WHERE id IN (#)", $mcids);

		while($line = $shoppingcart->db->fetchrow())
			$mcs[$line['id']] = $line['name'];
	}

	incHeader(true,array('incShoppingCartMenu'));

	echo "<table width=100%>";

	echo "<tr>";
	echo "<td class=header>Description</td>";
	echo "<td class=header>Quantity</td>";
	echo "<td class=header>Username</td>";
	echo "<td class=header>Retail Price</td>";
	echo "<td class=header>Unit Price</td>";
	echo "<td class=header>Total</td>";
	echo "</tr>";

	$total = 0;

	foreach($rows as $row){
		echo "<tr>";
		echo "<td class=body><a class=body href=product.php?id=$row[productid]>$row[name]</a></td>";
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
		echo "<td class=body align=right>" . number_format($row['unitprice'],2) . "</td>";
		echo "<td class=body align=right>" . number_format($row['price'],2) . "</td>";
		echo "<td class=body align=right>" . number_format($row['price'] * $row['quantity'], 2) . "</td>";
		echo "</tr>";
		$total += round($row['price'] * $row['quantity'],2);
	}

	echo "<tr><td class=body colspan=4>Sorry, No Refunds or Credits Allowed</td><td class=body align=right>Total</td><td class=body align=right>\$" . number_format($total,2) . "</td></tr>";


	echo "<tr><td class=body colspan=3><a class=body href=cart.php>Back to your Shopping Cart</a></td>";
	echo "<td class=body colspan=3 align=right><form action=checkoutconfirm.php><input class=body type=submit value=Confirm></form></td></tr>";

	echo "</table>";

	incFooter();


