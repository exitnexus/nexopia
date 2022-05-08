<?

	$login=0;

	require_once("include/general.lib.php");

	$db->prepare_query("SELECT id,price,description FROM premiumpackages");

	$packages = array();
	while($line = $db->fetchrow())
		$packages[] = $line;

	incHeader();

	echo "<table>";

?>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_xclick">
<input type="hidden" name="business" value="payment@enternexus.com">
<input type="hidden" name="item_name" value="Premium">
<input type="hidden" name="item_number" value="package">
<input type="hidden" name="custom" value="username">
<input type="hidden" name="no_shipping" value="1">
<input type="hidden" name="no_note" value="1">
<input type="hidden" name="currency_code" value="USD">
<input type="hidden" name="tax" value="0">
<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but6.gif" border="0" name="submit" alt="Make payments with PayPal - it's fast, free and secure!">
</form>
<?


	echo "</table>";

	incFooter();
