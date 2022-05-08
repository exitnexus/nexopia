<?

	$login = 1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"editinvoice"))
		die("Permission denied");

//todo, choose picture


	switch($action){
		case "addcat":		editcat(0);										break;
		case "editcat":		editcat($id);									break;
		case "insertcat":	insertcat($name, $description);					break;
		case "updatecat":	updatecat($id, $name, $description);			break;
		case "upcat":		moveupcat($id);									break;
		case "downcat":		movedowncat($id);								break;
		case "deletecat":	deletecat($id);									break;

		case "addproduct":		editproduct(0);				break;
		case "editproduct":		editproduct($id);			break;
		case "insertproduct":	insertproduct($data);		break;
		case "updateproduct":	updateproduct($id, $data);	break;
		case "upproduct":		moveupproduct($id);			break;
		case "downproduct":		movedownproduct($id);		break;
	}

	listProductCats();

/////////////////////////////

function listProductCats(){
	global $shoppingcart;

	$res = $shoppingcart->db->query("SELECT id, name FROM productcats ORDER BY priority");

	$cats = array();
	while($line = $res->fetchrow())
		$cats[$line['id']] = $line['name'];



	$res = $shoppingcart->db->query("SELECT id, category, name, active FROM products ORDER BY priority");

	$products = array();
	while($line = $res->fetchrow())
		$products[$line['category']][] = $line;


	incHeader();

	echo "<table align=center>";

	echo "<tr>";
	echo "<td class=header>Name</td>";
	echo "<td class=header>Discontinued</td>";
	echo "<td class=header></td>";
	echo "<td class=header></td>";
	echo "<td class=header></td>";
	echo "<td class=header></td>";
	echo "</tr>";


	foreach($cats as $catid => $catname){
		echo "<tr>";
			echo "<td class=header colspan=2>$catname</td>";
			echo "<td class=header><a class=header href=$_SERVER[PHP_SELF]?action=editcat&id=$catid>Edit</a></td>";
			echo "<td class=header><a class=header href=$_SERVER[PHP_SELF]?action=upcat&id=$catid>Up</a></td>";
			echo "<td class=header><a class=header href=$_SERVER[PHP_SELF]?action=downcat&id=$catid>Down</a></td>";
			echo "<td class=header>" . (isset($products[$catid]) ? "" : "<a class=header href=$_SERVER[PHP_SELF]?action=deletecat&id=$catid>Delete</a>") . "</td>";
		echo "</tr>";

		foreach($products[$catid] as $line){
			echo "<tr>";
			echo "<td class=body><a class=body href=/product.php?id=$line[id]>$line[name]</a></td>";
			echo "<td class=body>" . ($line['active'] == 'n' ? "Discontinued" : "") . "</td>";
			echo "<td class=body><a class=body href=$_SERVER[PHP_SELF]?action=editproduct&id=$line[id]>Edit</a></td>";
			echo "<td class=body><a class=body href=$_SERVER[PHP_SELF]?action=upproduct&id=$line[id]>Up</a></td>";
			echo "<td class=body><a class=body href=$_SERVER[PHP_SELF]?action=downproduct&id=$line[id]>Down</a></td>";
			echo "<td class=body></td>";
			echo "</tr>";
		}
	}

	echo "<tr><td class=header colspan=6 align=right><a class=header href=$_SERVER[PHP_SELF]?action=addcat>Add Category</a> | <a class=header href=$_SERVER[PHP_SELF]?action=addproduct>Add Product</a></td></tr>";

	echo "</table>";
	incFooter();
	exit;
}

function editcat($id = 0){
	global $shoppingcart;

	if($id){
		$res = $shoppingcart->db->prepare_query("SELECT name, description FROM productcats WHERE id = ?", $id);
		extract($res->fetchrow());
	}else{
		$name = "";
		$description = "";
		$picname = "";
	}

	incHeader();

	echo "<table align=center>";
	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<tr><td class=body>Name:</td><td class=body><input class=body type=text name=name value=\"$name\" maxlength=32 size=30></td></tr>";

	echo "<tr><td class=body colspan=2><textarea class=body cols=50 rows=12 name=description>" . htmlentities($description) . "</textarea></td></tr>";
	echo "<tr><td class=body align=center colspan=2>";

	if($id){
		echo "<input type=hidden name=action value=updatecat>";
		echo "<input type=hidden name=id value=$id>";
		echo "<input class=body type=submit value=Update>";
	}else{
		echo "<input type=hidden name=action value=insertcat>";
		echo "<input class=body type=submit value=Add>";
	}

	echo "</td></tr>";
	echo "</form></table>";

	incFooter();
	exit;
}

function editproduct($id = 0){
	global $shoppingcart;

	if($id){
		$res = $shoppingcart->db->prepare_query("SELECT category, name, firstpicture, unitprice, bulkpricing, shipping, input, inputname, callback, stock, active, summary, description FROM products, producttext WHERE products.id = producttext.id && products.id = ?", $id);

		extract($res->fetchrow());
	}else{
		$category 		= 0;
		$name 			= "";
		$description	= "";
		$id = 0;
		$category = 0;
		$name = "";
		$firstpicture = 0;
		$unitprice = 0;
		$bulkpricing = 'n';
		$shipping = 'n';
		$input = "none";
		$inputname = "";
		$callback = "";
		$stock = "";
		$active = 'y';
		$summary = "";
		$description = "";
	}

	$res = $shoppingcart->db->query("SELECT id, name FROM productcats ORDER BY priority");

	$cats = array();
	while($line = $res->fetchrow())
		$cats[$line['id']] = $line['name'];

	$status = array('y' => "Active", 'n' => "Discontinued");

	incHeader();

	echo "<table align=center>";
	echo "<form action=$_SERVER[PHP_SELF] method=post>";

	if($id)
		echo "<tr><td class=header colspan=2 align=center>Edit Product</td></tr>";
	else
		echo "<tr><td class=header colspan=2 align=center>Add Product</td></tr>";

	echo "<tr><td class=body>Category:</td><td class=body><select class=body name=data[category]>" . make_select_list_key($cats, $category) . "</select></td></tr>";
	echo "<tr><td class=body>Name:</td><td class=body><input class=body type=text name=data[name] value=\"" . htmlentities($name) . "\" maxlength=32 size=30></td></tr>";
	echo "<tr><td class=body>Status:</td><td class=body><select class=body name=data[active]>" . make_select_list_key($status, $active) . "</select></td></tr>";
	echo "<tr><td class=body>Summary:</td><td class=body><textarea class=body cols=80 rows=5 name=data[summary]>" . htmlentities($summary) . "</textarea></td></tr>";
	echo "<tr><td class=body>Description:</td><td class=body><textarea class=body cols=80 rows=15 name=data[description]>" . htmlentities($description) . "</textarea></td></tr>";



	echo "<tr><td class=body align=center colspan=2>";

	if($id){
		echo "<input type=hidden name=action value=updateproduct>";
		echo "<input type=hidden name=id value=$id>";
		echo "<input class=body type=submit value=Update>";
	}else{
		echo "<input type=hidden name=action value=insertproduct>";
		echo "<input class=body type=submit value=Add>";
	}

	echo "</td></tr>";
	echo "</form></table>";

	incFooter();
	exit;
}

function insertproduct($data){
	global $shoppingcart, $msgs;

	$shoppingcart->db->prepare_query("INSERT INTO products SET category = ?, name = ?, active = ?",
			$data['category'], $data['name'], $data['active']);

	$id = $shoppingcart->db->insertid();

	$ndescription = parseHTML(smilies($data['description']));

	$shoppingcart->db->prepare_query("UPDATE producttext SET summary = ?, description = ?, ndescription = ?, id = ?",
								$data['summary'], $data['description'], $ndescription, $id);

	$msgs->addMsg("Item added, incomplete!!!!");
}

function updateproduct($id, $data){
	global $shoppingcart, $msgs;


	$shoppingcart->db->prepare_query("UPDATE products SET category = ?, name = ?, active = ? WHERE id = ?",
			$data['category'], $data['name'], $data['active'], $id);

	$ndescription = parseHTML(smilies($data['description']));

	$shoppingcart->db->prepare_query("UPDATE producttext SET summary = ?, description = ?, ndescription = ? WHERE id = ?",
								$data['summary'], $data['description'], $ndescription, $id);

	$msgs->addMsg("Item Updated, if stuff changed, priorities are likely corrupt!!");
}

function moveupproduct($id){
	global $shoppingcart;

	$res = $shoppingcart->db->prepare_query("SELECT category FROM products WHERE id = ?", $id);

	$cat = $res->fetchfield();

	increasepriority($shoppingcart->db, "products", $id, $shoppingcart->db->prepare("category = ?", $cat));
}

function movedownproduct($id){
	global $shoppingcart;

	$res = $shoppingcart->db->prepare_query("SELECT category FROM products WHERE id = ?", $id);

	$cat = $res->fetchfield();

	decreasepriority($shoppingcart->db, "products", $id, $shoppingcart->db->prepare("category = ?", $cat));
}

