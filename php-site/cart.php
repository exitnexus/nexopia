<?

	$login=1;

	require_once("include/general.lib.php");



	switch($action){
		case "Add to Cart":
			if(empty($id))
				break;

			if(empty($quantity))
				$quantity = 1;

			$db->prepare_query("SELECT unitprice,bulkpricing FROM products WHERE id = ?", $id);
			$line = $db->fetchrow();

			$price = $line['unitprice'];

			if($line['bulkpricing'] == 'y' && $quantity > 1){
				$db->prepare_query("SELECT price FROM productprices WHERE productid = ? && minimum <= ? ORDER BY minimum DESC LIMIT 1",$id, $quantity);

				if($db->numrows())
					$price = $db->fetchfield();
			}

			$db->prepare_query("INSERT INTO shoppingcart SET userid = ?, productid = ?, quantity = ?, price = ?, input = ?", $userData['userid'], $id, $quantity, $price, $choice);

			$msgs->addMsg("Item Added");
			break;
		case "Update":
			if(empty($quantity))
				$quantity = array();
			if(empty($inputs))
				$inputs = array();
			if(empty($remove))
				$remove = array();

			$itemds = array();

			foreach($quantity as $id => $qty)
				$items[$id]['quantity'] = $qty;
			foreach($inputs as $id => $input)
				$items[$id]['input'] = $input;
			foreach($remove as $id => $asdf)
				$items[$id]['remove'] = true;

			foreach($items as $id => $val){
				$set = array();
				if(isset($val['quantity'])){
					if($val['quantity'] == 0)
						$val['remove'] = true;
					else{
						$set[] = $db->prepare("quantity = ?", $val['quantity']);


						$db->prepare_query("SELECT products.id,unitprice,bulkpricing FROM products,shoppingcart WHERE products.id=shoppingcart.productid && shoppingcart.id = ?", $id);
						$line = $db->fetchrow();

						$price = $line['unitprice'];

						if($line['bulkpricing'] == 'y' && $val['quantity'] > 1){
							$db->prepare_query("SELECT price FROM productprices WHERE productid = ? && minimum <= ? ORDER BY minimum DESC LIMIT 1",$line['id'], $val['quantity']);

							if($db->numrows())
								$price = $db->fetchfield();
						}

						$set[] = $db->prepare("price = ?", $price);
					}
				}
				if(isset($val['remove'])){
						$db->prepare_query("DELETE FROM shoppingcart WHERE id = ? && userid = ?", $id, $userData['userid']);
						continue;
				}
				if(isset($val['input']))
					$set[] = $db->prepare("input = ?", $val['input']);

				$db->query("UPDATE shoppingcart SET " . implode(", ", $set) . " WHERE " . $db->prepare("id = ? && userid = ?", $id, $userData['userid']));
			}

			$msgs->addMsg("Update complete");
			break;
	}



	$db->prepare_query("SELECT shoppingcart.id,productid,quantity,price,name,unitprice,products.input as inputtype, shoppingcart.input FROM shoppingcart,products WHERE shoppingcart.productid = products.id && shoppingcart.userid = ? ORDER BY id", $userData['userid']);

	$rows = array();

	$mcids = array();

	while($line = $db->fetchrow()){
		$rows[] = $line;
		if($line['inputtype']=='mc')
			$mcids[] = $line['productid'];
	}

	$mcs = array();

	$db->prepare_query("SELECT id,productid,name FROM productinputchoices WHERE productid IN (?)", $mcids);

	while($line = $db->fetchrow())
		$mcs[$line['productid']][$line['id']] = $line['name'];


	incHeader(true,array('incShoppingCartMenu'));

	echo "<table width=100%>";
	echo "<form action=$PHP_SELF method=post>";

	echo "<tr>";
	echo "<td class=header>Remove</td>";
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
		echo "<td class=body><input type=checkbox name=remove[$row[id]]></td>";
		echo "<td class=body><a class=body href=product.php?id=$row[productid]>$row[name]</a></td>";
		echo "<td class=body><input class=body type=text size=1 name=quantity[$row[id]] value=$row[quantity]> months</td>";
		echo "<td class=body>";
		switch($row['inputtype']){
			case "none": break;
			case "mc":
				echo "<select class=body name=inputs[$row[id]]>" . make_select_list_key($mcs[$row['id']],$row['input']) . "</select>";
				break;
			case "text":
				echo "<input class=body name=inputs[$row[id]] type=text value=\"" . htmlentities($row['input']) . "\" size=15>";
				break;
		}
		echo "</td>";
		echo "<td class=body align=right>\$" . number_format($row['unitprice'],2) . "</td>";
		echo "<td class=body align=right>\$" . number_format($row['price'],2) . "</td>";
		echo "<td class=body align=right>\$" . number_format($row['price'] * $row['quantity'], 2) . "</td>";
		echo "</tr>";
		$total += round($row['price']  * $row['quantity'],2);
	}

	echo "<tr><td class=body colspan=3></td><td class=body><input class=body type=submit name=action value=Update></td><td class=body></td>";
	echo "<td class=body>Total:</td><td class=body align=right>\$" . number_format($total,2) . "</td></tr>";

	echo "</form>";
	echo "<tr><td class=body align=center colspan=6><a class=body href=product.php?id=1>Pay for a friend</a></td><td class=body align=right><form action=checkout.php><input class=body type=submit value=Checkout></form></td></tr>";

	echo "</table>";

	incFooter();

