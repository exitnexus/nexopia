<?

	$login=0;

	require_once("include/general.lib.php");
	require_once("include/payment.php");

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

	if(!$userData['loggedIn'] && $action)
		loginRedirect();

	switch($action){
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
	global $userData;
	if (count($users) != count($amount)) {
		$msgs->add("Missing data");
		payBasic();
	}
	
	$amount = convertAmount($amount);
	$basket = new Basket();
	$basket->setUser($userData['userid']);
	for ($i = 0; $i<count($users); $i++) {
		$item = new Item($basket, Item::PLUS, $amount[$i], getUserID($users[$i]));
		$basket->addItem($item);
	}
	
	$payment = Payment::createPayment("MailPayment", $basket);
	$payment->setAmountPending($basket->getTotal());
	$payment->showPaymentPage();
	/*
	$id = createInvoice($users, $amount);
	
	if($id === false)
		payBasic('mail', $users, $amount);
	
	$template = new template('plus/finishMail');
	$template->set('id', $id);
	$template->display();
	
	exit;*/
}

function finishMoneris($users, $amount){
	global $config, $userData;
	
	$id = createInvoice($users, $amount);
	
	if($id === false)
		payBasic('moneris', $users, $amount);
	
	$total = 0;
	foreach($amount as $amt)
		$total += $amt;
	
	$template = new template('plus/finishMoneris');
	$template->set('total', number_format($total, 2));
	$template->set('config', $config);
	$template->set('userData', $userData);
	$template->set('id', $id);
	$template->display();
	
	exit;
}


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

	$basket = new Basket();
	$amount = convertAmount($amount);
	foreach ($users as $key => $user) {
		$item = new Item($basket, Item::PLUS, $amount[$key], $user);
		$basket->addItem($item);
	}
	$payment = Payment::createPayment("VoucherPayment", $basket);
	$success = $payment->processPayment($vouchers);
	if (!$success) {
		$payment->showPaymentPage();
		exit;
	} else {
		$basket->showCompleted();
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

function convertAmount(array $amount) {
	for ($i = 0; $i<count($amount); $i++) {
		//TODO: change the form to give us a number of months rather than a price
		switch ($amount[$i]) {
			case 5:
				$amount[$i] = 1; break;
			case 10:
				$amount[$i] = 2; break;
			case 15:
				$amount[$i] = 3; break;
			case 20:
				$amount[$i] = 6; break;
			case 30:
				$amount[$i] = 12; break;
			default:			
				$amount[$i] = 0;
		}
	}
	return $amount;
}