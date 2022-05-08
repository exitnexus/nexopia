<?

	$login = 0;

	require_once("include/general.lib.php");


	$gstpercent = 6;
	$gstproductid = 3;

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
		$auth->loginRedirect();

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
			$voucher = trim(getREQval('voucher'));

			if($voucher == '')
				exit;

			$value = $payg->cardValue($voucher);

			if(isset($freeamounts[$value]))
				echo "<a class=body href=plus.php?action=free>Free Voucher</a>";
			elseif($value)
				echo "<input type=hidden name=value[] value=$value>\$$value";
			else
				echo "Invalid";
			exit;


		case "Activate":
			$voucher = getPOSTval('freevoucher');
			finishFreeVoucher($voucher);
			pluspage();
			break;

		case "Complete":
		case "Continue":

			$paymentmethod = getREQval('paymentmethod');

			$users = getREQval('user', 'array');
			$amount = getREQval('amount', 'array');
			$vouchers = getREQval('voucher', 'array');

			switch($paymentmethod){
				case "moneris":
					finishMoneris($users, $amount);
					break;

				case "credit":
					finishCredit($users, $amount);
					break;

				case "mail":
					finishMail($users, $amount);
					break;

				case "voucher":
					finishVouchers($users, $amount, $vouchers);
					break;
			}

			plusPage($paymentmethod, $users, $amount, $vouchers);
			
			
			

		default:
			pluspage();
	}






function pluspage($paymentmethod = '', $users = array(), $amount = array(), $vouchers = array()){

	$template = new template('plus/pluspage');

	$features = array(
						'Profile Skins' => "Give your profile that extra bit of flair by customizing the colours.",
						'Recent Visitors List' => "See who has visited your profile.",
						'Fewer Ads' => "Choose to disable most advertising on the site.",
						'Frame-Less Skins' => "Option to use the site without frames.",

						'Friends List Notifications' => "Receive a message whenever someone removes you as a friend.",
						'See Common Friends' => "When visiting someone's profile, your friends in common are bolded.",
						'Extra Friends' => "Four times the amount of friends you can add.",
						'Longer Profile Sections' => "Double the amount of stuff you can put in your profile.",

						'Extra Profile Pics' => "Upload 12 pictures to your profile (instead of 8).",
						'Priority Picture Approval' => "Your pictures get moderated before everybody else.",
						'Enhanced Photo Gallery' => "50 times more pictures; no tag option; larger picture resolution.",
						'Spotlight' => "A random Plus user is spotlighted every minute on the front page.",

						'Visit Anonymously' => "View other people's profiles without them knowing you were there.",
						'File Uploads and Hosting' => "Upload up to 10MB of files for storage.",
						'Message Status' => "See whether a message you sent has been read.",
						'Multiple Message' => "Send the same message to several mutual friends at once.",

						'Advanced User Search' => "More options when searching for users.",
						'Hide Profile Hits' => "Hide the number of hits to your profile.",
						'Custom Forum Rank' => "Customize the text that appears below your username in the Forums.",
						'Create a User Forum' => "Create your own User Forum and control who gets to be a part of it.",
						);
	$template->set('features', $features);


	$i = 0;
	$classList = array('body','body2');

	foreach($features as $name => $desc){
		$classes[$i] = $classList[floor($i/4) %2];
		$i++;
	}
	$template->set('classes', $classes);


	global $userData, $amounts, $gstpercent, $payg, $config;
	
	$template->set('config', $config);

	if(count($users) == 0 && $userData['loggedIn'])
		$users[] = $userData['username'];
	if(!in_array("", $users))
		$users[] = "";

	$i = 0;
	$selectAmount = array();
	$amounttotal = 0;
	foreach($users as $k => $v){
		if(isset($amount[$k])){
			$selectAmount[$k] = make_select_list_key($amounts, $amount[$k]);
			$amounttotal += $amount[$k];
		}else{
			$selectAmount[$k] = make_select_list_key($amounts);
		}
	}
	$template->set('users', $users);
	$template->set('selectAmount', $selectAmount);
	$template->set('amounttotal', number_format($amounttotal, 2));

	if(count($vouchers) == 0)
		$vouchers[] = '';

	$voucherval = array();
	$vouchertotal = 0;
	foreach($vouchers as $k => $voucher){
		if($voucher){
			$val = $payg->cardValue($voucher);

			if(isset($freeamounts[$val])){
				$voucherval[$k] = "<a class=body href=plus.php?action=free>Free Voucher</a>";
			}elseif($val){
				$voucherval[$k] = "<input type=hidden name=value[] value=$val>\$$val";
				$vouchertotal += $val;
			}else{
				$voucherval[$k] = "Invalid";
			}
		}else{
			$voucherval[$k] = "";
		}
	}

	$template->set('vouchers', $vouchers);
	$template->set('voucherval', $voucherval);

	$template->set('voucherTotal', number_format($vouchertotal, 2));

	$template->set('gstpercent', $gstpercent);
	$template->set('gst', number_format($amounttotal*$gstpercent/100, 2));
	$template->set('total', number_format($amounttotal + $amounttotal*$gstpercent/100, 2));

	$template->set('paymentmethod', $paymentmethod);
	
	
	$template->display();
	exit;
}

function printInvoice($id){
	global $shoppingcart;
	
	$res = $shoppingcart->db->prepare_query("SELECT * FROM invoice WHERE id = #", $id);

	$invoice = $res->fetchrow();

	if(!$invoice)
		return "Bad invoice id";

	$invoice['username'] = getUserName($invoice['userid']);

//	if(!$isAdmin && $invoice['userid'] != $userData['userid'])
//		die("Bad invoice id");

	$res = $shoppingcart->db->prepare_query("SELECT productid,quantity,price,name,products.input as inputtype, invoiceitems.input FROM invoiceitems,products WHERE invoiceitems.productid = products.id && invoiceitems.invoiceid = #", $id);

	$rows = array();
	$mcids = array();
	$mcs = array();

	while($line = $res->fetchrow()){
		$rows[] = $line;
		if($line['inputtype']=='mc')
			$mcids[] = $line['input'];
	}

	if(count($mcids)){
		$res = $shoppingcart->db->prepare_query("SELECT id,name FROM productinputchoices WHERE id IN (#)", $mcids);

		while($line = $res->fetchrow())
			$mcs[$line['id']] = $line['name'];
	}


	$total = 0;

	foreach($rows as $k => $row){
		if($row['inputtype'] == 'mc')
			$rows[$k]['input'] = $mcs[$row['input']];
			
		$rows[$k]['total'] = $row['price'] * $row['quantity'];

		$total += round($row['price'] * $row['quantity'], 2);
	}

	$paid = $invoice['amountpaid'];
	$due = $total - $paid;

	$template = new template('plus/printinvoice');
	$template->set('invoice', $invoice);
	$template->set('rows', $rows);
	$template->set('total', $total);
	$template->set('paid', $paid);
	$template->set('due', $due);
	return $template->toString();
}

function finishMail($users, $amount){
	$id = createInvoice($users, $amount, true);

	if($id === false)
		return false;
		
	$invoice = printInvoice($id);

	$template = new template('plus/finishMailContent');
	$template->set('id', $id);
	$template->set('invoice', $invoice);
	$content = $template->toString();


	$template = new template('plus/finishMail');
	$template->set('normalcontent', $content);
	$template->set('printcontent', preg_replace("/class ?= ?['\"]?(body|header)['\"]?/", "", $content));
	$template->display();
	exit;
}


function finishMoneris($users, $amount){
	global $config, $userData, $gstpercent;

	$id = createInvoice($users, $amount, true);

	if($id === false)
		return false;

	$total = 0;
	foreach($amount as $amt)
		$total += $amt;

	$total *= (1 + $gstpercent/100);

	$template = new template('plus/finishMoneris');
	$template->set('total', number_format($total, 2));
	$template->set('config', $config);
	$template->set('userData', $userData);
	$template->set('id', $id);
	$template->display();
	exit;
}


function finishCredit($users, $amount){
	global $config, $userData, $gstpercent;

	$id = createInvoice($users, $amount, true);

	if($id === false)
		return false;

	$total = 0;
	foreach($amount as $amt)
		$total += $amt;

	$total *= (1 + $gstpercent/100);

	$template = new template('plus/finishCredit');
	$template->set('total', number_format($total, 2));
	$template->set('config', $config);
	$template->set('userData', $userData);
	$template->Set('id', $id);
	$template->display();
	exit;
}

function createInvoice($users, $amount, $gst){ // $users = array('username', ...), $amount = array(amount, ...) . Sizes must match
	global $msgs, $shoppingcart, $userData, $amounts, $quantities;

	$valid = array();
	$total = 0;

	if(count($users) != count($amount)){
		$msgs->addMsg("Missing data");
		return false;
	}

	for($i = 0; $i < count($users); $i++){
	//both blank
		if($users[$i] == '' && $amount[$i] == 0)
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
		if($amount[$i] == 0 || !isset($amount[$i]) || !isset($amounts[$amount[$i]])){
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

	if($gst){
		global $gstpercent, $gstproductid;
		$shoppingcart->db->prepare_query("INSERT INTO invoiceitems SET invoiceid = #, productid = #, quantity = #, price = ?",
				$invoiceid, $gstproductid, 1, number_format($total*$gstpercent/100, 3));
	}

	return $invoiceid;
}










function finishVouchers($users, $amount, $vouchers){
	global $payg, $shoppingcart, $msgs, $freeamounts;

	$valid = array();

	$vtotal = 0;
	foreach($vouchers as $voucher){
		if($voucher == '')
			continue;

		if(isset($valid[$voucher])){ //skip dupe vouchers
			$msgs->addMsg("Duplicate Voucher");
			return false;
		}

		$value = $payg->cardValue($voucher); //$voucher is fixed by cardValue(), if possible

		if(isset($freeamounts[$value])){
			$msgs->addMsg(htmlentities($voucher) . " is a free voucher and must be redeemed as such.");
		}elseif($value){
			$valid[$voucher] = $value;
			$vtotal += $value;
		}else{
			$msgs->addMsg(htmlentities($voucher) . " is invalid, please try again");
		}
	}

	if($vtotal == 0)
		return false;


	$atotal = 0;
	foreach($amount as $amt)
		$atotal += $amt;

	if($vtotal != $atotal){
		$msgs->addMsg("Totals don't match");
		return false;
	}


	$id = createInvoice($users, $amount, false);

	if($id === false)
		return false;

	$stores = $payg->useCards(array_keys($valid), $id);

	if($stores){
		$output = $shoppingcart->updateInvoice($id, 'payg', implode(', ', $stores), $vtotal, implode(", ", array_keys($valid)), true);

		$template = new template('plus/finishVouchers');
		$template->display();
		exit;
	}else{
		$msgs->addMsg("One of your vouchers was invalid");
		return false;
	}
}

function finishFreeVoucher($voucher){
	global $payg, $freeamounts, $userData, $msgs;


	$value = $payg->cardValue($voucher); //$voucher is fixed by cardValue(), if possible

	if(!$value || !isset($freeamounts[$value])){
		$msgs->addMsg("Invalid Voucher");
		return false;
	}


	//check if another card from this batch has already been used by this user
	$res = $payg->db->prepare_query("SELECT t2.id FROM paygcards as t1, paygcards as t2 WHERE t1.batchid = t2.batchid && t1.secret = ? && t2.useuserid = #", $voucher, $userData['userid']);

	if($res->fetchrow()){
		$msgs->addMsg("You've already used a card from this batch");
		return false;
	}else{
		$stores = $payg->useCards($voucher, 0);
		if($stores){
			addPlus($userData['userid'], $freeamounts[$value], 0, 0);

			$template = new template('plus/finishFreeVouchers');
			$template->display();
			exit;
		}else{
			$msgs->addMsg("Invalid Voucher");
			return false;
		}
	}
}
