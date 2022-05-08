<?

	$login=1;

	require_once("include/general.lib.php");

	if(!isset($action))
		$action="";
	
	switch($action){
		case "add":
			additem();
			exit;
		case "insert":
			insertItem($data,$select);
			break;
		case "edit":

		case "update":
			break;
		case "delete":
		case "Delete":
			sqlSafe(&$checkid);
			if(isset($checkid) && is_array($checkid))
				foreach($checkid as $id)
					deleteItem($id);
			break;
	}





	incHeader();
	
	$query = "SELECT id,name,description,price FROM items";
	$result = mysql_query($query);
	
	echo "<table width=100%><form action=$PHP_SELF>";
	
	echo "<tr><td class=header></td><td class=header>Name</td><td class=header>Description</td><td class=header>Price</td></tr>";
	
	while($line = mysql_fetch_assoc($result)){
		echo "<tr>";
		echo "<td class=body><input type=checkbox name=checkid[] value=$line[id]></td>";
		echo "<td class=body><a class=body href=item.php?id=$line[id]>$line[name]</a></td>";
		echo "<td class=body>$line[description]</td>";
		echo "<td class=body>$line[price]</td>";
		echo "</tr>";
	}
	echo "<tr><td class=header colspan=4><input class=body type=submit name=action value=Delete> <a class=header href=$PHP_SELF?action=add>Add Item</a></td></tr>";
	echo "</form></table>";
	
	incFooter(array('incAdminBlock'));

function addItem(){
	global $PHP_SELF;
	
	$query = "SELECT * FROM manufacturers";
	$result = mysql_query($query);
	
	$manufacturers = array();
	while($line = mysql_fetch_assoc($result))
		$manufacturers[$line['id']] = $line['name'];
	
	incHeader();

	echo "<table><form action=$PHP_SELF method=post>";
	
	echo "<tr><td class=body>Name:</td><td class=body><input class=body type=text name=data[title]></td></tr>";
	
	echo "<tr><td class=body>Description:</td><td class=body><textarea class=body cols=40 rows=6 name=data[description]></textarea></td></tr>";
	
	echo "<tr><td class=body>Picture:</td><td class=body><input class=body type=file name=imagefile size=30></td></tr>";
	
	echo "<tr><td class=body>Item Make:</td><td class=body><select class=body name=data[itemmake]>" . make_select_list_key($manufacturers). "</select></td></tr>";
	echo "<tr><td class=body>Item Model:</td><td class=body><input class=body type=text name=data[itemmodel]></td></tr>";

	$cars = getChildData('cars');
	$carbranch = makeBranch($cars);

	echo "<tr><td class=body valign=top>Cars:</td><td class=body>Choosing an item is equivalent to choosing all sub-items.<br><select class=body name=select[] size=10 multiple=multiple>" . makeCatSelect($carbranch) . "</select></td></tr>";
	
	echo "<tr><td class=body>Quantity Available:</td><td class=body><input class=body type=text name=data[quantity] size=5></td></tr>";
	echo "<tr><td class=body>Price:</td><td class=body><input class=body type=text name=data[price] size=5></td></tr>";
	
	echo "<tr><td class=body>Sale Price:</td><td class=body><input class=body type=text name=data[saleprice] size=5></td></tr>";
	
	echo "<tr><td class=body valign=top>Sale Start Date:</td><td class=body>";
	echo "<input type=radio name=data[salestart] value=now>Now<br>";
	echo "<input type=radio name=data[salestart] value=later>";
		echo "<select class=body name=data[salestartmonth]><option value=0>Month" . make_select_list(range(1,12)) . "</select>";
		echo "<select class=body name=data[salestartday]><option value=0>Day" . make_select_list(range(1,31)) . "</select>";
		echo "<select class=body name=data[salestartyear]><option value=0>Year" . make_select_list(range(date("Y"),date("Y")+1)) . "</select>";
	echo "</td></tr>\n";

	echo "<tr><td class=body valign=top>Sale End Date:</td><td class=body>";
	echo "<input type=radio name=data[saleend] value=never>Never<br>";
	echo "<input type=radio name=data[saleend] value=later>";
		echo "<select class=body name=data[saleendmonth]><option value=0>Month" . make_select_list(range(1,12)) . "</select>";
		echo "<select class=body name=data[saleendday]><option value=0>Day" . make_select_list(range(1,31)) . "</select>";
		echo "<select class=body name=data[saleendyear]><option value=0>Year" . make_select_list(range(date("Y"),date("Y")+1)) . "</select>";
	echo "</td></tr>\n";

	
	
	echo "<tr><td class=body>Bulk Price:</td><td class=body><input class=body type=text name=data[bulkprice] size=5></td></tr>";
	echo "<tr><td class=body>Bulk Quantity:</td><td class=body><input class=body type=text name=data[bulkquantity] size=5></td></tr>";
	
	echo "<tr><td class=body></td><td clas=body><input type=hidden name=action value=insert><input class=body type=submit value=Add><input class=body type=submit name=action value=Cancel></td></tr>";
	
	echo "</form></table>";

	incFooter(array('incAdminBlock'));

	exit;
}

function insertItem($data,$select){
	global $config,$msgs,$docRoot,$imagefile,$imagefile_tmp_name,$imagefile_name;

	sqlSafe(&$data,$select);
	$commands[] = "name = '$data[title]'";
	$commands[] = "description = '$data[description]'";
	$commands[] = "quantity = '$data[quantity]'";
	$commands[] = "price = '$data[price]'";
	$commands[] = "saleprice = '$data[saleprice]'";
	$commands[] = "salestart = '" . mktime(0,0,1,$data['salestartmonth'],$data['salestartday'],$data['salestartyear']) . "'";
	$commands[] = "saleend = '" . mktime(0,0,1,$data['saleendmonth'],$data['saleendday'],$data['saleendyear']) . "'";
	$commands[] = "bulkprice = '$data[bulkprice]'";
	$commands[] = "bulkquantity = '$data[bulkquantity]'";
	
	$query = "INSERT INTO items SET " . implode (", ", $commands);
	mysql_query($query);
	
	$itemid = mysql_insert_id();
	
	foreach($select as $id){
		$query = "INSERT INTO itemcompatibility SET itemid='$itemid', carid='$id'";
		mysql_query($query);
	}
	

	if(!move_uploaded_file($imagefile, $docRoot . $config['itemdir'] . $itemid . ".jpg")) {
		$msgs->addMsg("You must upload a file");
	}
	
	$msgs->addMsg("Item Added");
}

function deleteItem($id){
	global $msgs;
	$query = "DELETE FROM itemcompatibility WHERE itemid='$id'";
	mysql_query($query);
	
	$query = "DELETE FROM items WHERE id='$id'";
	mysql_query($query);

	$query = "DELETE FROM cart WHERE itemid='$id'";
	mysql_query($query);

	$msgs->addMsg("Item deleted");
}








