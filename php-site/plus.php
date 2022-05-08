<?

	$login=0;

	require_once("include/general.lib.php");


	$amounts = array(	5  => "1 Month (\$5.00)",
						10 => "2 Months (\$10.00)",
						15 => "3 Months (\$15.00)",
						20 => "6 Months (\$20.00)",
						30 => "1 Year (\$30.00)",
					);
	$quantities = array(5  => 1,
						10 => 2,
						15 => 3,
						20 => 6,
						30 => 12,
					);

	$freeamounts = array( '2.00' => (7.00/31) );

	
/* : here we are checking to see if the user is returning from an interac online purchase */
	if (isset($_POST['IDEBIT_VERSION']) && isset($_POST['IDEBIT_ISSLANG']) && isset($_POST['IDEBIT_INVOICE'])) {
/* : in this case, if we get a TRACK2 number back attempt to complete the txn*/
		if (isset($_POST['IDEBIT_TRACK2'])) {
/* : at this point we also need to include the mpgClasses.php file to process the transaction */
			include_once("include/mpgClasses.php");
			$action = "completeInterac";
		} else {
/* : if there is no TRACK2 response, than we can assume the transaction was declined at the bank */
			$action = "interacBankDeclined";
		}
	}
	
	
	if(!$userData['loggedIn'] && $action)
		loginRedirect();

	switch($action){
		case "interacBankDeclined":
			interacBankDeclined();
		case "completeInterac":
			completeInterac();
		case "moneris":
		case "credit":
		case "mail":
			payBasic($action);
		
		case "voucher":
			collectVouchers();
			
		case "free":
			collectFreeVoucher();
		
		case "vouchercheck":
			$voucher = getREQval('voucher');

			$value = $payg->cardValue($voucher);
			
			if($value)
				echo "<input type=hidden name=value[] value=$value>\$$value";
			else
				echo "Invalid";
			exit;

		case "Complete":
		case "Continue":
		
			$type = getREQval('type');
	
			$users = getREQval('user', 'array');
			$amount = getREQval('amount', 'array');
			
			switch($type){
				case "moneris":
					finishMoneris($users, $amount);

				case "credit":
					finishCredit($users, $amount);

				case "mail":
					finishMail($users, $amount);

				case "voucher":
					$vouchers = getREQval('voucher', 'array');

					finishVouchers($users, $amount, $vouchers);
				
				case "free":
					$voucher = getREQval('voucher');

					finishFreeVoucher($voucher);
				
				
			}

		default:
			paymentMethod();
	}


function paymentMethod(){

	$template = new template('plus/paymentMethod');

	$features = array(	
						'Profile Skins' => "Give your profile that extra bit of flair by customizing the colours.",
						'Recent Visitors List' => "See who has visited your profile.",
						'Fewer Ads' => "Choose to disable most advertising on the site.",
						'Frame-Less Skins' => "Use the site without frames by selecting the Frame-Less version of that skin.",
						'Friends List Removal' => "Remove yourself from someone else's Friends List.",
						'Friends List Notifications' => "Receive a message whenever someone adds or removes you as a friend.",
						'See Common Friends' => "When visiting someone's profile, your friends in common are bolded.",
						'Extra Friends' => "Double the amount of friends you can add.",
						'Longer Profile Sections' => "Double the amount of stuff you can put in your profile.",
						'Extra Profile Pics' => "Upload 12 pictures to your profile (instead of 8).",
						'Priority Picture Approval' => "Your pictures get moderated before everybody else.",
						'Enhanced Photo Gallery' => "50 times more pictures; no tag option; larger picture resolution.",
						'Spotlight' => "A random Plus user is spotlighted every 5 minutes on the front page.",
						'Visit Anonymously' => "View other people's profiles without them knowing you were there.",
						'File Uploads and Hosting' => "Upload up to 10MB of files for storage.",
						'Message Status' => "See whether a message you sent has been read.",
						'Hide Profile' => "Hide your entire Profile from people not logged in or people on your Ignore List.",
						'Advanced User Search' => "More options when searching for users.",
						'Custom Forum Rank' => "Customize the text that appears below your username in the Forums.",
						'Create a User Forum' => "Create your own User Forum and control who gets to be a part of it.",
						);
	$template->set('features', $features);


	$i = -1;
	$classList = array('body','body2');

	foreach($features as $name => $desc){
		$i++;
		$classes[$i] = $classList[(floor($i/4) %2)];
	}
	$template->set('classes', $classes);
	$template->display();
	exit;
}


function payBasic($type, $users = array(), $amount = array()){
	global $userData, $amounts, $config;

	$template = new template('plus/payBasic');
	$template->set('type', $type);
	$template->set('config', $config);
	$template->set('userData', $userData);
	
	$total = 0;
	if(count($users) && count($users) == count($amount)){
		$template->set('listMultipleUsers', true);
		foreach($users as $k => $user){
			$selectAmountMultiple[$k] = make_select_list_key($amounts,$amount[$k]);
			$total += $amount[$k];
		}
		$template->set('selectAmountMultiple', $selectAmountMultiple);
	}else{
		$template->set('listMultipleUsers', false);
		$template->set('selectAmount', make_select_list_key($amounts));
	}
	$template->set('users', $users);
	$template->set('total', number_format($total, 2));
	$template->display();
	
	exit;
}

function finishMail($users, $amount){
	
	$id = createInvoice($users, $amount);
	
	if($id === false)
		payBasic('mail', $users, $amount);
	
	$template = new template('plus/finishMail');
	$template->set('id', $id);
	$template->display();
	
	exit;
}


/* FUNCTION finishMoneris */

/* SYNOPSIS
 * This function was the original end point for interac online purcheses
 * however, it has been extended to make use of the Moneris PHP API to complete
 * these transactions, and is now part of several steps
 * END SYNOPSIS */

/* HISTORY
 * Created ??? by ???
 * IncrDev May 10, 2006 by pdrapeau -- extended to use Moneris PHP API
 * END HISTORY */
function finishMoneris(
	$users,		// I: array of user names to process for
	$amount		// I: corresponding array of amounts for the users
){
	global $config, $userData;
	global $INTERAC_PAYMENT;
	
/* : create the invoice for the transactions returning the invoice ID */
	$id = createInvoice($users, $amount);
	
	if($id === false)
		payBasic('moneris', $users, $amount);

/* : calculate the total, which will be used to form the transaction */
	$total = 0;
	foreach($amount as $amt)
		$total += $amt;
		
	/* : for interac online purchases, amount must be expressed as cents */
	$totalCents = number_format($total, 2);
	$totalCents = $totalCents * 100;
	
	

	$template = new template('plus/finishMoneris');
	$template->set('postUrl', $INTERAC_PAYMENT['IDEBIT_postUrl']);
	$template->set('invoice', $id);
	$template->set('amount', $totalCents);
	$template->set('merchNum', $INTERAC_PAYMENT['IDEBIT_merchNum']);
	$template->set('currency', "CAD");
	$template->set('fundedUrl', $INTERAC_PAYMENT['IDEBIT_fundedUrl']);
	$template->set('notFundedUrl', $INTERAC_PAYMENT['IDEBIT_notFundedUrl']);
	$template->set('merchLang', "en");
	$template->set('version', "1");
	$template->set('config', $config);

	$template->display();
	
	exit;
}
/* END FUNCTION finishMoneris */


function finishCredit($users, $amount){
	global $config, $userData;
	
	$id = createInvoice($users, $amount);
	
	if($id === false)
		payBasic('credit', $users, $amount);
	
	$total = 0;
	foreach($amount as $amt)
		$total += $amt;
	
	$template = new template('plus/finishCredit');
	$template->set('total', number_format($total, 2));
	$template->set('config', $config);
	$template->set('userData', $userData);
	$template->Set('id', $id);
	$template->display();
	exit;
}

function createInvoice($users, $amount){ // $users = array('username', ...), $amount = array(amount, ...) . Sizes must match
	global $msgs, $shoppingcart, $userData, $amounts, $quantities;

	$valid = array();
	$total = 0;

	if(count($users) != count($amount)){
		$msgs->addMsg("Missing data");
		return false;
	}

	for($i = 0; $i < count($users); $i++){
	//both blank
		if($users[$i] == '' && ($amount[$i] == 0 || !isset($amounts[$amount[$i]])))
			continue;
		
	//bad username
		if($users[$i] == '' || !validUserName($users[$i])){
			$msgs->addMsg("Invalid Username");
			return false;
		}

	//dupe user
		if(isset($valid[strtolower($users[$i])])){
			$msgs->addMsg("Duplicate User");
			return false;
		}


	//bad amount
		if($amount[$i] == 0 || !isset($amount[$i])){
			$msgs->addMsg("Bad Amount");
			return false;
		}

	//fine
		$total += $amount[$i];	
		$valid[strtolower($users[$i])] = $amount[$i];
	}

	if($total == 0){
		$msgs->addMsg("Missing Amount");
		return false;
	}


	$shoppingcart->db->prepare_query("INSERT INTO invoice SET userid = #, creationdate = #, total = #", $userData['userid'], time(), $total);

	$invoiceid = $shoppingcart->db->insertid();

	foreach($valid as $user => $amt){
		$shoppingcart->db->prepare_query("INSERT INTO invoiceitems SET invoiceid = #, productid = #, quantity = #, price = ?, input = ?",
				$invoiceid, 1, $quantities[$amt], number_format($amt/$quantities[$amt], 3), $user);
	}

	return $invoiceid;
}

function collectVouchers($users = array(), $amount = array(), $vouchers = array()){
	global $userData, $amounts, $payg;

	$template = new template('plus/collectVouchers');
	$template->set('payglocations', getStaticValue('payglocations'));

	$total = 0;
	
	if(count($vouchers)){
		$template->set('vouchersExist', true);
		$i = -1;
		foreach($vouchers as $voucher){
			$i++;
			$value[$i] = $payg->cardValue($voucher);
			$total += $value[$i];
		}
		$template->set('value', $value);
	}else{
		$template->set('vouchersExist', false);
	}
	
	$template->set('voucherTotal', number_format($total, 2));

	$total = 0;
	$template->set('users', $users);
	$template->set('userData', $userData);
	$template->set('amount', $amount);
	$template->set('amounts', $amounts);
	if(count($users) && count($users) == count($amount)){
		$template->set('multipleUsers', true);
		foreach($users as $k => $user){
			$selectAmountMultiple[$k] = make_select_list_key($amounts,$amount[$k]);
			$total += $amount[$k];
		}
		$template->set('selectAmountMultiple', $selectAmountMultiple);
	}else{
		$template->set('multipleUsers', false);
		$template->set('selectAmount', make_select_list_key($amounts));
	}

	$template->set('total', number_format($total, 2));
	$template->display();
	exit;
}


function collectFreeVoucher($voucher = ''){
	global $freeamounts;

	$template = new template('plus/collectFreeVoucher');
	$template->set('voucher', $voucher);
	$template->display();
	exit;
}

function finishVouchers($users, $amount, $vouchers){
	global $payg, $shoppingcart, $msgs;

	$valid = array();

	$vtotal = 0;
	foreach($vouchers as $voucher){
		if($voucher == '')
			continue;

		if(isset($valid[$voucher])){ //skip dupe vouchers
			$msgs->addMsg("Duplicate Voucher");
			collectVouchers($users, $amount, $vouchers);
		}

		$value = $payg->cardValue($voucher); //$voucher is fixed by cardValue(), if possible
		if($value){
			$valid[$voucher] = $value;
			$vtotal += $value;
		}else{
			$msgs->addMsg(htmlentities($voucher) . " is invalid, please try again");
		}
	}

	if($vtotal == 0)
		collectVouchers($users, $amount, $vouchers);


	$atotal = 0;
	foreach($amount as $amt)
		$atotal += $amt;
	
	if($vtotal != $atotal){
		$msgs->addMsg("Totals don't match");
		collectVouchers($users, $amount, $vouchers);
	}


	$id = createInvoice($users, $amount);
	
	if($id === false)
		collectVouchers($users, $amount, $vouchers);
	
	$stores = $payg->useCards(array_keys($valid), $id);
	
	if($stores){
		$output = $shoppingcart->updateInvoice($id, 'payg', implode(', ', $stores), $vtotal, implode(", ", array_keys($valid)), true);
		
		incHeader();
		
		echo "Thanks for buying plus.";
		
		incFooter();
		exit;
	}else{
		$msgs->addMsg("One of your vouchers was invalid");
		collectVouchers($users, $amount, $vouchers);
	}

}

function finishFreeVoucher($voucher){
	global $payg, $freeamounts, $userData, $msgs;


	$value = $payg->cardValue($voucher); //$voucher is fixed by cardValue(), if possible

	if(!$value || !isset($freeamounts[$value])){
		$msgs->addMsg("Invalid Voucher");
		collectFreeVoucher($voucher);
	}


	//check if another card from this batch has already been used by this user
	$res = $payg->db->prepare_query("SELECT t2.id FROM paygcards as t1, paygcards as t2 WHERE t1.batchid = t2.batchid && t1.secret = ? && t2.useuserid = #", $voucher, $userData['userid']);

	if($res->fetchrow()){
		$msgs->addMsg("You've already used a card from this batch");
	}else{
		$stores = $payg->useCards($voucher, 0);
		if($stores){
			addPlus($userData['userid'], $freeamounts[$value], 0, 0);
			
			incHeader();
			
			echo "You've got Plus.";
			
			incFooter();
			exit;
		}else{
			$msgs->addMsg("Invalid Voucher");
			collectFreeVoucher($voucher);
		}
	}
}


/* FUNCTION completeInterac */

/* SYNOPSIS
 * This function is called when the user returns from performing an interac transaction, and it proceeds to
 * validate the posted data, and than pushes the transaction over to Moneris via a HTTPS POST
 * END SYNOPSIS */

/* HISTORY
 * Created May 10, 2006 by pdrapeau
 * END HISTORY */
function completeInterac () {
	global $shoppingcart;
	global $userData;
	global $INTERAC_PAYMENT;
	
/* : first due to the requirements from interac we must validate the received data, taking into account */
/* : that we already checked to see that IDEBIT_VERSION, IDEBIT_ISSLANG, and IDEBIT_TRACK2 have been verified */
/* : as being set */	
	$IDEBIT_TRACK2 = $_POST['IDEBIT_TRACK2'];
	$IDEBIT_VERSION = $_POST['IDEBIT_VERSION'];
	$IDEBIT_ISSLANG = $_POST['IDEBIT_ISSLANG'];
	$IDEBIT_INVOICE = $_POST['IDEBIT_INVOICE'];
	
	if (isset($_POST['IDEBIT_ISSCONF'])) {
		$IDEBIT_ISSCONF = $_POST['IDEBIT_ISSCONF'];
	} else {
		interacBankDeclined();
	}
	
	if (isset($_POST['IDEBIT_ISSNAME'])) {
		$IDEBIT_ISSNAME = $_POST['IDEBIT_ISSNAME'];
	} else {
		interacBankDeclined();
	}
	
/* : okay at this point we know the requred fields were sent in the post request, next valiate the fields */
/* : according to the supplied documentation */

/* : the IDEBIT_TRACK2 var should contain 37 characters that fall into the range of 20 to 7E hex */	
	if (strlen($IDEBIT_TRACK2) != 37) {
		interacBankDeclined();
	}

	for ($i = 0; $i < 37; $i++) {
		$stringChar = substr($IDEBIT_TRACK2, $i, 1);
		$asciiValue = ord($stringChar);
/* : 20H to 7EH is equliv to decimal 32 to 126, thus the range returned by ord */
/* : must fall into that decimal range */
		if ($asciiValue < 32 || $asciiValue > 126) {
			interacBankDeclined();
		}
	}
	
/* : the version number is initially set to "1" according to the documentation */
	if ($IDEBIT_VERSION != 1) {
		interacBankDeclined();
	}
	
/* : the language of the merchant at the issuer, since we are only supporting english, it will always be en */
	if ($IDEBIT_ISSLANG != "en") {	
		interacBankDeclined();	
	}
	
/* : the IDEBIT_INVOICE variable should be 1 to 20 characters, and should match the one generated for the user
/* : prior to leaving the site */
	if (strlen($IDEBIT_INVOICE) < 1 || strlen($IDEBIT_INVOICE) > 20) {	
		interacBankDeclined();	
	}
	
/* : now retreive the invoice number from the database corresponding to this order, confirm that its for the */
/* : user who is returning to complete this transaction, and use the amount recorded in the database when */
/* : pushing the txn to Moneris */
	$result = $shoppingcart->db->prepare_query("SELECT userid, total FROM invoice WHERE id=#", $IDEBIT_INVOICE);

	$row = $result->fetchrow();

/* : make sure we got results back from the database */
	if (!isset($row['userid']) || !isset($row['total'])) {
		interacBankDeclined();
	}

/* : check the results against the users session */
	$invoiceUserId = $row['userid'];
	$invoiceTotal = $row['total'];

	if ($invoiceUserId != $userData['userid']) {
		interacBankDeclined();
	}
		
		
/* : the IDEBIT_ISSCONF string will be 1 to 15 characters encoded in UTF-8 */
	if (strlen($IDEBIT_ISSCONF) < 1 || strlen($IDEBIT_ISSCONF) > 15) {
		interacBankDeclined();
	}
	
/* : the IDEBIT_ISSNAME string will be 1 to 30 characters encoded in UTF-8 */
/* : 20H to 7EH and */
	if (strlen($IDEBIT_ISSNAME) < 1 || strlen($IDEBIT_ISSNAME) > 30) {
		interacBankDeclined();
	}
	

/* : now setup the txn to be handed to moneris for processing */
	$txnArray=array(
		type=>'idebit_purchase',
		order_id=>$IDEBIT_INVOICE,
		amount=>number_format($invoiceTotal, 2),
		idebit_track2=>$IDEBIT_TRACK2
	);

	$mpgTxn = new mpgTransaction($txnArray);
	$mpgRequest = new mpgRequest($mpgTxn);
	$mpgHttpPost = new mpgHttpsPost($INTERAC_PAYMENT['MONERIS_storeID'], $INTERAC_PAYMENT['MONERIS_apiToken'], $mpgRequest);
	$mpgResponse = $mpgHttpPost->getMpgResponse();

/* : the block below outlines the responce that will be returned from Moneris */
/*	print ("\nCardType = " . $mpgResponse->getCardType());
	print("\nTransAmount = " . $mpgResponse->getTransAmount());
	print("\nTxnNumber = " . $mpgResponse->getTxnNumber());
	print("\nReceiptId = " . $mpgResponse->getReceiptId());
	print("\nTransType = " . $mpgResponse->getTransType());
	print("\nReferenceNum = " . $mpgResponse->getReferenceNum());
	print("\nResponseCode = " . $mpgResponse->getResponseCode());
	print("\nISO = " . $mpgResponse->getISO());
	print("\nMessage = " . $mpgResponse->getMessage());
	print("\nAuthCode = " . $mpgResponse->getAuthCode());
	print("\nComplete = " . $mpgResponse->getComplete());
	print("\nTransDate = " . $mpgResponse->getTransDate());
	print("\nTransTime = " . $mpgResponse->getTransTime());
	print("\nTicket = " . $mpgResponse->getTicket());
	print("\nTimedOut = " . $mpgResponse->getTimedOut()); */

/* : here we need to check if the response is valid at this point, and if so give the user plus */
/* : then give the correct response to the user, however for the moment there is no internal */
/* : test environment that can be used to complete this */



	exit;
}
/* END FUNCTION completeInterac */


/* FUNCTION interacBankDeclined */

/* SYNOPSIS
 * This function is a catch all for when interac transactions fail
 * END SYNOPSIS */

/* HISTORY
 * Created May 10, 2006 by pdrapeau
 * END HISTORY */
function interacBankDeclined() {
	global $config;
	
	$template = new template('plus/interacBankDeclined');
	$template->set('config', $config);
	$template->display();
	exit;
}
/* END FUNCTION interacBankDeclined */
