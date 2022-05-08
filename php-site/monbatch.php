<?
	set_time_limit(0);

	$login = 1;
	require_once('include/general.lib.php');

	if (! $mods->isAdmin($userData['userid'], 'viewinvoice'))
		die('Permission denied.');

	echo '<html><head><title>Moneris vs. Nexopia Batches</title><style type="text/css">.invoice { color: #000; }</style></head>';

	$submit = isset($_REQUEST['submit']);

	if (! $submit) {
		echo <<<ENDIT
<form action="{$_SERVER['PHP_SELF']}" method="post">
	<strong>Nexopia Invoices</strong><br>
	Reports > "Debit"; range beginning one day before desired report and ending as is; full report. Submit. Firefox ctrl+click first entry in "Invoice" column, scroll to bottom and shift+click last entry. Copy. Click outside blue area and wait for the blue to disappear. Paste into this box.<br><br>
	<textarea name="txtInvoices" rows="5" cols="30"></textarea><br><br>

	<strong>Moneris Report</strong><br>
	Reports > Batches > Batch Date > From; range from desired beginning and end of report. Submit. One at a time, click each "Details" button, then "Export Batch Results to File". Copy the contents of notepad and paste in this box.<br><br>
	<textarea name="txtMoneris" rows="5" cols="30"></textarea><br><br>

	<input type="submit" name="submit" value="submit">
</form>
ENDIT;
		exit;
	}

	$invoices = isset($_POST['txtInvoices']) ? preg_split('/\s+/', $_POST['txtInvoices']) : '';
	$rows = isset($_POST['txtMoneris']) ? preg_split('/\015\012|\015|\012/', $_POST['txtMoneris']) : '';

	$batches = array();

	foreach ($rows as $row) {
		if ( strlen($row = trim($row)) == 0)
			continue;

		list(
			$termid,		// = 66079697
			$batchid,		// = $batch_id
			$date,			// when transaction processed
			$trans_type,	// Puchase|Void|Refund
			$orderid,		// nexopia-xxxxx|xxxxxxxxx
			$cardtype,		// iDebit|Visa|MC
			$cardnum,		// xxxx****xxxx
			$amount,		// 5.00, etc
			$approval,		// xxxxxx
			$cardtype_num,	// 1 = debit, 27 = credit
			$result,		// Approved|
			$custid			// xxxxxxx
		) = split(',', $row);

		if ($cardtype != 'iDebit') // is not debit
			continue;

		if (! isset($batches[$batchid]))
			$batches[$batchid] = array();

		$invoice = substr($orderid, 8, 6);

		if ($trans_type == 'Purchase' && ! in_array($invoice, $invoices))
			$batches[$batchid][] = $invoice;

		else if ( ($trans_type == 'Refund' || $trans_type == 'Void') && in_array($invoice, $batches[$batchid]))
			unset($batches[$batchid][$invoice]);
	}

	$have_any = false;

	foreach ($batches as $batchid => $missing) {
		if (count($missing) == 0)
			unset($batches[$batchid]);
		else
			$have_any = true;
	}

	if (! $have_any) {
		echo "<strong>No discrepancies. Yay!</strong>";
		exit;
	}

?>

<script type="text/javascript">
	var winnexopia, winmoneris;

	function openWindows (invoice) {
		if (winnexopia)
			winnexopia.close();

		if (winmoneris)
			winmoneris.close();

		winmoneris = window.open('', 'winmoneris', 'width=1000,height=225,scrollbars=yes');
		winmoneris.moveTo(0, 0);
		winnexopia = window.open('', 'winnexopia', 'width=1000,height=600,scrollbars=yes');
		winnexopia.moveTo(0, 275);

		winmoneris.document.location.href = 'https://www3.moneris.com/mpg/view/Transactions/Info/Order_Details/index2.php?order_no=nexopia-' + invoice;
		winnexopia.document.location.href = 'http://plus.www.nexopia.com/invoice.php?id=' + invoice;
		winnexopia.scrollTo(0, 200);

		document.getElementById('invoice' + invoice).style.color = '#ddd';
	}

</script>

<?
	echo "<strong>The following batches have invoices not completed on Nexopia.</strong><br /><br />";

	foreach ($batches as $batchid => $missing) {
		if (count($missing) == 0)
			continue;

		$have_any = true;

		echo "<strong>Batch ${batchid}</strong><br /><ul>";

		foreach ($missing as $invoice)
			echo "<li> ${invoice} [ <a class=\"invoice\" id=\"invoice${invoice}\" href=\"#\" onclick=\"openWindows(${invoice}); return false;\">open</a></span> ]</li>\n";

		echo "</ul><br /><br />";
	}
