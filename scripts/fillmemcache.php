#!/usr/local/php/bin/php
<?php

$forceserver=true;
$errorLogging=true;

$_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'] = ".www.nexopia.com";
chdir("/home/nexopia/public_html");

require_once "include/general.lib.php";


	$res = $masterdb->unbuffered_query("SELECT id, serverid FROM accounts");
	
	while($line = $res->fetchrow())
		$cache->put("serverid-user-$line[id]", $line['serverid'], 7*24*60*60);

