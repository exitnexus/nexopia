<?

	$login=0;

	require_once("include/general.lib.php");

	if(empty($cat))
		$cat = 0;

	$shoppingcart->db->prepare_query("SELECT products.id,name,firstpicture,summary,unitprice,bulkpricing,stock FROM products,producttext WHERE products.id=producttext.id && category = ?", $cat);

	$rows = array();
	while($line = $shoppingcart->db->fetchrow())
		$rows[] = $line;

	incHeader(true,array('incShoppingCartMenu'));

	echo "<table width=100%>";
	foreach($rows as $line){
		echo "<tr><td class=header colspan=2><a class=header href=product.php?id=$line[id]>$line[name]</a></td></tr>";
		echo "<tr><td class=body colspan=2>";
		if($line['firstpicture'] > 0)
			echo "<img src=\"$config[productthumbdir]/$line[firstpicture].jpg\" align=left>";
		echo "$line[summary]</td></tr>";
		echo "<tr><td class=body>Price: $line[unitprice]";
		if($line['bulkpricing'] == 'y')
			echo " Discounts on Bulk";
		echo "</td><td class=body>";
		if(strlen($line['stock']) > 2){
			if(substr($line['stock'],-2) == "()"){
				$func = substr($line['stock'],-2);
				echo $func();
			}elseif($line['stock'] == 0){
				echo "Out of Stock";
			}else{
				echo "In Stock";
			}
		}
		echo "</td></tr>";
		echo "<tr><td class=body colspan=2>&nbsp;</td></tr>";
	}

	echo "</table>";

	incFooter();



