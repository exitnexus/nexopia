<?

	$login=0;

	require_once("include/general.lib.php");

	if(empty($id)){
		header("location: productlist.php");
		exit;
	}

	$db->prepare_query("SELECT products.*,producttext.ndescription FROM products,producttext WHERE products.id=producttext.id && products.id = ?", $id);

	if($db->numrows() == 0){
		header("location: productlist.php");
		exit;
	}

	$product = $db->fetchrow();

	$prices = array(1 => $product['unitprice']);

	if($product['bulkpricing'] == 'y'){
		$db->prepare_query("SELECT minimum, price FROM productprices WHERE productid = ?", $id);

		while($line = $db->fetchrow())
			$prices[$line['minimum']] = $line['price'];
	}

	$choices = array();
	if($product['input'] == 'mc'){
		$db->prepare_query("SELECT id,productid,name FROM productinputchoices WHERE productid = ?", $id);

		while($line = $db->fetchrow())
			$choices[$line['id']] = $line['name'];
	}



	incHeader(true,array('incShoppingCartMenu'));

	echo "<table width=100%>";

	echo "<tr><td class=header>$product[name]</td></tr>";
	echo "<tr><td class=body>";
	if($product['firstpicture'] > 0)
		echo "<img src=\"$config[productdir]/$product[firstpicture].jpg\" align=left>";


//order inputs
	echo "<table align=right><form action=cart.php>";
//price
	echo "<tr><td class=header>Price per month</td></tr>";
	echo "<tr><td class=body>";
	if(count($prices) == 1){
		echo "\$$prices[1]";
	}else{
		foreach($prices as $min => $price)
			echo "$min month" . ($min == 1 ? "" : "s") . " or more: \$" . number_format($price,2) . "<br>";
	}
	echo "</td></tr>";

//availability
	if(strlen($product['stock']) > 2){
		echo "<tr><td class=header>Availability</td></tr>";
		if(substr($product['stock'],-2) == "()"){
			$func = substr($product['stock'],-2);
			echo $func();
		}elseif($product['stock'] == 0){
			echo "Out of Stock";
		}else{
			echo "In Stock";
		}
	}
	echo "</td></tr>";

//choices
	if($product['input'] != 'none'){
		echo "<tr><td class=header>$product[inputname]:</td></tr>";
		echo "<tr><td class=body>";
		if($product['input'] == 'mc')
			echo "<select class=body name=\"choice\">" . make_select_list_key($choices) . "</select>";
		else
			echo "<input class=body type=text name=\"choice\" size=15>";
		echo "</td></tr>";
	}

//quantity
	echo "<tr><td class=header>Quantity</td></tr>";
	echo "<tr><td class=body><input class=body type=text name=quantity value=1 size=1> months</td></tr>";

//add to cart
	echo "<input type=hidden name=id value=$id>";
	echo "<tr><td class=body align=center><input class=body type=submit name=action value=\"Add to Cart\"></td></tr>";

//end order inputs
	echo "</form></table>";

//description
	echo $product['ndescription'];

	echo "</td></tr>";
	echo "</table>";

	incFooter();


