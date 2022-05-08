<?
	set_time_limit(0);

	$login = 1;
	require_once('include/general.lib.php');

	if (! $mods->isAdmin($userData['userid'], 'viewinvoice'))
		die('Permission denied');

	echo '<html><head><title>Billing People vs. Nexopia Batches</title><style type="text/css">.invoice { color: #000; }</style></head>';

	$submit = isset($_REQUEST['submit']);

	if (! $submit) {
		echo <<<ENDIT
<form action="{$_SERVER['PHP_SELF']}" method="post">
	<strong>Nexopia Invoices</strong><br>
	Reports > "Billing People"; range beginning one day before desired report and ending as is; full report. Submit. Firefox ctrl+click first entry in "Transaction ID" column, scroll to bottom and shift+click last entry. Copy. Click outside blue area and wait for blue to disappear. Paste into this box.<br><br>
	<textarea name="txtInvoices" rows="5" cols="30"></textarea><br><br>

	<strong>BP Report</strong><br>
	Reports > Sales > Date Range; range from desired beginning and end of report. Submit. One at a time, click each date's link, then select all columns with mouse drag (no holding ctrl or shift). Copy and paste into this box.<br><br>
	<textarea name="txtBP" rows="5" cols="30" onkeypress="var e = this; if (event.ctrlKey && event.which == 118) setTimeout(function () { e.value = e.value + '\\n'; }, 250);"></textarea><br><br>

	<input type="submit" name="submit" value="submit">
</form>
ENDIT;

		exit;
	}

	$invoices = isset($_POST['txtInvoices']) ? explode("\t", $_POST['txtInvoices']) : '';
	$rows = isset($_POST['txtBP']) ? preg_split('/\015\012|\015|\012/', $_POST['txtBP']) : '';

	$missing = array();

	foreach ($invoices as $key => $invoice) {
		$invoice = trim($invoice);
		if ( ($pos = strpos($invoice, ' ')) !== false )
			$invoices[$key] = substr($invoice, 0, $pos);
	}

	foreach ($rows as $row) {
		if ( strlen($row = trim($row)) == 0)
			continue;

		list(
			$transid,		// xxxxxx
			$seqid,			// xxxxxxxxx*
			$trans_type,	// charge/refund
			$result,		// completed/failed
			$product,		// "Nexopia Plus"
			$webcost,		// webmaster cost (0.00)
			$amount			// invoice amount
		) = explode("\t", $row);

		if ($product != 'Nexopia Plus' || $result != 'completed') // not for Plus, or failed transaction
			continue;

		if ($trans_type == 'charge' && $result == 'completed' && ! in_array($seqid, $invoices))
			$missing[] = $seqid;

		else if (in_array($trans_type, array('refund', 'chargeback')) && $result == 'completed' && ($key = array_search($seqid, $missing)) !== false)
			unset($missing[$key]);
	}

	if (count($missing) == 0) {
		echo "<strong>No discrepancies. Yay!</strong>";
		exit;
	}

?>

<script type="text/javascript">
	var winbp;

	function openWindows (invoice) {
		if (winbp)
			winbp.close();

		winbp = window.open('', 'winbp', 'width=1000,height=500,scrollbars=yes');
		winbp.moveTo(0, 0);

		winbp.document.location.href = 'https://secure.billingpeople.com/login/merchant/transaction_manager.php?CustomerID=' + invoice;

		document.getElementById('invoice' + invoice).style.color = '#ddd';
	}

</script>

<?
	echo "<strong>The following invoices are not completed on Nexopia.</strong><br /><br />";

	echo "<ul>";

	foreach ($missing as $invoice)
		echo "<li> ${invoice} [ <a class=\"invoice\" id=\"invoice${invoice}\" href=\"#\" onclick=\"openWindows(${invoice}); return false;\">open</a></span> ]</li>\n";

	echo "</ul>";
