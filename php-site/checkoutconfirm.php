<?

	$login=1;

	require_once("include/general.lib.php");


	$db->query("LOCK TABLES shoppingcart WRITE, invoice WRITE, invoiceitems WRITE");

	$db->prepare_query("SELECT count(*) as count, SUM(ROUND(price*quantity,2)) as total FROM shoppingcart WHERE userid = ?", $userData['userid']);

	$line = $db->fetchrow();

	if($line['count'] == 0){
		$db->query("UNLOCK TABLES");
		header("location: productlist.php");
		exit;
	}

	$db->prepare_query("INSERT INTO invoice SET userid = ?, username = ?, creationdate = ?, total = ?", $userData['userid'], $userData['username'], time(), $line['total']);

	$invoiceid = $db->insertid();

	$db->prepare_query("INSERT INTO invoiceitems (invoiceid,productid,quantity,price,input) SELECT ? as invoiceid,productid,quantity,price,input FROM shoppingcart WHERE userid = ?", $invoiceid, $userData['userid']);

	$db->prepare_query("DELETE FROM shoppingcart WHERE userid = ?", $userData['userid']);

	$db->query("UNLOCK TABLES");


	header("location: invoice.php?id=$invoiceid");
	exit;

