<?

function completeinvoice($id){
	global $db;

	$db->query("LOCK TABLES invoice WRITE");

	$db->prepare_query("SELECT completed FROM invoice WHERE id = ?", $id);

	$line = $db->fetchrow();

	if($line['completed'] == 'y'){
		$db->query("UNLOCK TABLES");
		return "Already complete";
	}

	$db->prepare_query("UPDATE invoice SET completed = 'y' WHERE id = ?", $id);

	$db->query("UNLOCK TABLES");

	$result = $db->prepare_query("SELECT products.callback,invoiceitems.input,invoiceitems.quantity FROM invoiceitems,products WHERE invoiceitems.productid=products.id && invoiceitems.invoiceid = ?", $id);

	$output = "";

	while($line = $db->fetchrow($result))
		$output .= $line['callback']($line['input'],$line['quantity']) . "\n";

	return $output . "Completed";
}
