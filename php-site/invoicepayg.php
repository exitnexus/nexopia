<?

	$login=1;

	require_once("include/general.lib.php");

	$id = getPOSTval('id', 'int');

	if(!$id)
		die("You must choose an invoice to pay");


	$isAdmin = $mods->isAdmin($userData['userid'],'editinvoice');

	$shoppingcart->db->prepare_query("SELECT userid, total, amountpaid, completed, valid FROM invoice WHERE id = ?", $id);

	if($shoppingcart->db->numrows() == 0)
		die("Bad invoice id");

	$invoice = $shoppingcart->db->fetchrow();

	if(!$isAdmin && ($invoice['userid'] != $userData['userid'] || $invoice['valid'] == 'n'))
		die("Bad invoice id");

	if($invoice['amountpaid'] > 0 || $invoice['completed'] == 'y')
		die("This invoice is already paid");


	if(!isset($cards))
		$cards = array();

	$validcards = array();

	foreach($cards as $card){
		$value = $payg->cardValue($card); //$card is fixed by cardValue(), if possible
		if($value){
			$validcards[$card] = $value;
		}else{
			$msgs->addMsg("$card is invalid, please try again");
		}
	}

	$total = 0;
	foreach($validcards as $value)
		$total += $value;


	if($total == $invoice['total'] && $action == "Complete"){

		$shoppingcart->db->begin();

		$stores = $payg->useCards(array_keys($validcards), $id);
		if($stores){
			$output = $shoppingcart->updateInvoice($id, 'payg', implode(', ', $stores), $total, implode(", ", array_keys($validcards)), true);

			$shoppingcart->db->commit();

			incHeader();

			echo "Your invoice is now complete. Thanks";

			incFooter();
			exit;
		}else{
			$shoppingcart->db->rollback();
			$msgs->addMsg("One of the cards was invalid. Please try again");
		}
	}


	incHeader(600,array('incShoppingCartMenu'));

	echo "<table>";

	echo "<tr><td class=header>Invoice ID:</td><td class=body><a class=body href=invoice.php?id=$id>$id</a></td></tr>";
	echo "<tr><td class=header>Invoice Total:</td><td class=body>\$$invoice[total]</td></tr>";
	echo "</table>";

	if(count($validcards)){
		echo "<table><form action=$_SERVER[PHP_SELF] method=post>";
		echo "<tr><td class=header>Secret ID</td><td class=header>Value</td></tr>";
		$total = 0;
		foreach($validcards as $card => $value){
			echo "<tr><td class=body>$card</td><td class=body align=right>\$" . number_format($value, 2) . "</td></tr>";
			$total += $value;
		}

		echo "<tr><td class=body></td><td class=body align=right>Total: \$" . number_format($total, 2) . "</td></tr>";

		if($total == $invoice['total']){
			foreach($validcards as $card => $value)
				echo "<input type=hidden name=cards[] value='$card'>";
			echo "<input type=hidden name=id value=$id>";
			echo "<tr><td class=body></td><td class=body align=right><input class=body type=submit name=action value=Complete></td></tr>";
		}
		echo "</form></table>";
	}

	if($total != $invoice['total']){
		echo "<table><form action=$_SERVER[PHP_SELF] method=post>";
		echo "<input type=hidden name=id value=$id>";
		foreach($validcards as $card => $value)
			echo "<input type=hidden name=cards[] value='$card'>";
		echo "<tr><td class=header>Add a Card</td></tr>";
		echo "<tr><td class=body><input class=body type=text name=cards[]><input class=body type=submit value=Add></td></tr>";

		echo "</form></table>";
	}

	incFooter();

