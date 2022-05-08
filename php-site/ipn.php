<?

	require_once("include/general.lib.php");


	$data = $_POST;
	$data['cmd'] = "_notify-validate";

	$result = postit($data,"www.paypal.com/cgi-bin/webscr");


	if($result['body'] == "VERIFIED")
		$output = validatePayPal($data);
	else
		$output = "Not Verified";

	ob_start();

	echo "request from: $REMOTE_ADDR\n";
	echo "request: $REQUEST_URI\n";

	echo "Post data:\n";
	print_r($_POST);

	echo "\n\nCheck data:\n";
	print_r($result);

	$output .= "\n\n\n\n" . ob_get_clean();

	smtpmail("Timo <$config[paypalemail]>", "IPN Paypal notification", $output, "From: ipn <$config[paypalemail]>");


function validatePayPal($data){
	global $config, $db;

//check that it is a possible candidate
	if($data['txn_type'] != 'web_accept')			 		return "txn_type != web_accept";
	if($data['item_name'] != $config['title'])				return "item_name != title";
	if($data['receiver_email'] != $config['paypalemail'])	return "receiver_email != paypalemail";
	if(!isset($data['invoice']))							return "invoice number not set";
	if($data['payment_status'] != "Completed")				return "payment_status not Completed: '$data[payment_status]'";
	if($data['mc_currency'] != 'CAD')						return "Wrong currency";


	$db->query("LOCK TABLES invoice WRITE");


//duplicate
	$db->prepare_query("SELECT id FROM invoice WHERE txnid = ?", $data['txn_id']);
	if($db->fetchrow()){
		$db->query("UNLOCK TABLES");
		return "Duplicate txn_id";
	}

//invalid
	$db->prepare_query("SELECT id,userid,total,completed,amountpaid FROM invoice WHERE id = ?", $data['invoice']);
	$invoice = $db->fetchrow();

	if(!$invoice){
		$db->query("UNLOCK TABLES");
		return "invalid invoice";
	}

//already complete
	if($invoice['completed'] == 'y'){
		$db->query("UNLOCK TABLES");
		return "invoice already completed";
	}

//already paid?
	if($invoice['amountpaid'] > 0){
		$db->query("UNLOCK TABLES");

		if($invoice['amountpaid'] == $invoice['total'])
			return "invoice already paid in full, but not completed";
		else
			return "invoice already partially paid";
	}

//payment doesn't match invoice total
	if($invoice['total'] != $data['mc_gross']){
		$db->query("UNLOCK TABLES");
		return "Amount paid doesn't match the amount in the invoice";
	}

//paid!
	$db->prepare_query("UPDATE invoice SET txnid = ?, paymentdate = ?, paymentmethod = ?, amountpaid = ? WHERE id = ?", $data['txn_id'], time(), 'paypal', $data['mc_gross'], $invoice['id']);

	$db->query("UNLOCK TABLES");

	$output = completeinvoice($invoice['id']);

	return $output;
}



/*
<input type="hidden" name="business" value="$config[paypalemail]">
<input type="hidden" name="item_name" value="$config[title]">
<input type="hidden" name="invoice" value="$id">
<input type="hidden" name="no_shipping" value="1">
<input type="hidden" name="no_note" value="1">
<input type="hidden" name="currency_code" value="CAD">
<input type="hidden" name="tax" value="0">
<input type="hidden" name="return" value="http://$wwwdomain/invoice.php?id=$id">
<input type="hidden" name="amount" value="$total">





    [txn_type] => web_accept
    [payment_date] => 10:42:48 Apr 30, 2004 PDT
    [last_name] => Noel
    [item_name] => Enternexus.com
    [payment_gross] =>
    [mc_currency] => CAD
    [business] => payment@enternexus.com
    [payment_type] => instant
    [payer_status] => unverified
    [verify_sign] => Aa9HLZOZ6rFvSwNuMKL8nsBUGjkcAZ8XapCJ0o3wJhPzWabaJ2FwCmJd
    [payer_email] => lindsay_noel45@hotmail.com
    [tax] => 0.00
    [txn_id] => 76W81827HT701412C
    [receiver_email] => payment@enternexus.com
    [quantity] => 1
    [first_name] => Lindsay
    [payer_id] => GT5AV9LU8SPQN
    [receiver_id] => TNP69W49BLRHN
    [item_number] =>
    [payment_status] => Completed
    [payment_fee] =>
    [mc_fee] => 0.79
    [mc_gross] => 7.00
    [custom] =>
    [notify_version] => 1.6

*/








function postit($DataStream, $URL) {
	$URL = ereg_replace("^http://", "", $URL);

	$Host = substr($URL, 0, strpos($URL, "/"));
	$URI = strstr($URL, "/");

	$ReqBody = "";

	foreach($DataStream as $key => $val){
		if($ReqBody)
			$ReqBody .= "&";
		$ReqBody .= $key . "=" . urlencode($val);
	}
	$ContentLength = strlen($ReqBody);

    $ReqHeader =
      "POST $URI HTTP/1.0\n".
      "Host: $Host\n".
      "User-Agent: Nexopia\n".
      "Content-Type: application/x-www-form-urlencoded\n".
      "Content-Length: $ContentLength\n\n".
      "$ReqBody\n";

	$socket = fsockopen($Host, 80, &$errno, &$errstr);
	if(!$socket){
		$result["errno"] = $errno;
		$result["errstr"] = $errstr;
		return $result;
	}

	fputs($socket, $ReqHeader);

	$result = array('header' => array(), 'body' => "");

	$header = true;
	while(!feof($socket)){
		$buf = fgets($socket, 4096);

		if(($buf == "\n" || $buf == "\r\n") && $header){
			$header = false;
			continue;
		}

		if($header)
			$result['header'][] = $buf;
		else
			$result['body'] .= $buf;
	}
	fclose($socket);

	return $result;
}

