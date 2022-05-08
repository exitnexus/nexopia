<?php

chdir(dirname(__FILE__) . "/include/abi");
require_once("abi.php");

// Allow script to run for up to 90 seconds
set_time_limit (90);

$email = isset($_REQUEST['email']) ? $_REQUEST['email'] : '';
$pass = isset($_REQUEST['password']) ? $_REQUEST['password'] : '';
$file_name = isset($_REQUEST['file_name']) ? $_REQUEST['file_name'] : '';
$file_type = isset($_REQUEST['file_type']) ? $_REQUEST['file_type'] : '';

$contactlist = null;
$errmsg = '';

if ($email && $pass) {
	$obj = new AddressBookImporter;
	$res = $obj->fetchContacts($email,$pass);

	if ($res==_ABI_AUTHENTICATION_FAILED) {
		$errmsg='Bad user name or password';
	} else if ($res==_ABI_FAILED) {
		$errmsg='Server error';
	} else if ($res==_ABI_UNSUPPORTED) {
		$errmsg='Unsupported webmail';
	} else {
		$contactlist = $res;
		$contactlist = abi_dedupe_contacts_by_email($contactlist);
		$contactlist = abi_sort_contacts_by_name($contactlist);
	}
} elseif ($file_name && $file_type) {
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
