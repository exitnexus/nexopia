<?php
chdir(dirname(__FILE__) . "/include/abi");
require_once("abi.php");

// Allow script to run for up to 90 seconds
set_time_limit (90);

$email = isset($_POST['email']) ? $_POST['email'] : '';
$pass = isset($_POST['password']) ? $_POST['password'] : '';
$file_name = isset($_POST['file_name']) ? $_POST['file_name'] : '';
$file_type = isset($_POST['file_type']) ? $_POST['file_type'] : '';

$contactlist = null;
$errmsg = '';

if ($email && $pass) {
	$obj = new AddressBookImporter;
	$res = $obj->fetchContacts($email,$pass);

	if ($res==_ABI_AUTHENTICATION_FAILED) {
		$errmsg='Bad user name or password';
	}
	else if ($res==_ABI_FAILED) {
		$errmsg='Server error';
	}
	else if ($res==_ABI_UNSUPPORTED) {
		$errmsg='Unsupported webmail';
	}
	else if ($res==_ABI_CAPTCHA_RAISED) {
		$errmsg='Captcha challenge was raised during login';
	}
	else if ($res==_ABI_USER_INPUT_REQUIRED) {
	    echo 'Need to answer some questions in the webmail service';
	}
	else if (is_array($res)) {
		$contactlist = $res;
		$contactlist = abi_dedupe_contacts_by_email($contactlist);
		$contactlist = abi_sort_contacts_by_name($contactlist);
	}
	else {
		$errmsg='Unknown error';
	}
} elseif ($file_name && $file_type && strlen($file_name) > 0 && strlen($file_type) > 0) {
	$file_name = "$file_name";
	if (file_exists($file_name)) {
		$csv = file_get_contents($file_name);
		if (!empty($csv)) {
		 	//Parse the CSV file
			if ($file_type =="outlook") {
				$res = abi_extractContactsFromCsv($csv);
			} else if ($file_type=="thunderbird_csv") {
				$res = abi_extractContactsFromThunderbirdCsv($csv);
			} else if ($file_type=="thunderbird_ldif") {
				$res = abi_extractContactsFromLdif($csv);
			}
			//Figure out error
			if (is_int($res)) {
				$errmsg='Bad/Unrecognized format';
			} else {
				$contactlist = $res;
			}
		}
	}
}

$n = count($contactlist);
for ($i=0; $i<$n; $i++) {
 	$contact = $contactlist[$i];
	echo "$contact->name: $contact->email\n";
}
