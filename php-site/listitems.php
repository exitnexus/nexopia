<?

	$login=0;

	require_once("include/general.lib.php");



	incHeader();
	
	$query = "SELECT id,name,description,price FROM items";
	$result = mysql_query($query);
	
	echo "<table width=100%>";
	
	echo "<tr><td class=header>Name</td><td class=header>Description</td><td class=header>Price</td><td class=header>Add</td></tr>";
	
	while($line = mysql_fetch_assoc($result)){
		echo "<tr>";
		
		echo "<td class=body><a class=body href=item.php?id=$line[id]>$line[name]</a></td>";
		echo "<td class=body>$line[description]</td>";
		echo "<td class=body>$line[price]</td>";
		echo "<td class=body><a class=body href=cart.php?action=add&id=$line[id]&qty=1>Add to cart</a></td>";
		echo "</tr>";
	}
	echo "</table>";
	
	incFooter();
