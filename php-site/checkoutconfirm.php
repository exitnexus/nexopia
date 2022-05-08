<?

header("location: /plus.php");
exit;

	$login=1;

	require_once("include/general.lib.php");

	$invoiceid = $shoppingcart->checkout();

	if($invoiceid)
		header("location: invoice.php?id=$invoiceid");
	else
		header("location: productlist.php");
	exit;

