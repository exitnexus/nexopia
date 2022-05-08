#!/usr/local/php/bin/php
<?
	
	include("fileserver.php");
	
	$fs = new fileserver();

	$failed = $fs->testDrives();
	
	foreach($failed as $drive){
		echo "unmounting $drive\n";
		`umount $drive`;
	}