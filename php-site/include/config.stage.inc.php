<?php
	$basedomain = "stage.nexopia.com";
	$sitebasedir = "/var/nexopia/php-stage";
	$docRoot = "$sitebasedir/public_html";

	include_once inherit_config_profile('live');

	$debuginfousers[] = 423439;
	$debuginfousers[] = 1522402;

//	$memcacheoptions['delete_only'] = true;

	$config['name'] = 'stage';
	$config['title'] = 'Nexopia.com Staging Area';
?>
