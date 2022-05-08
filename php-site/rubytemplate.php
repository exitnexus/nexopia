<?php

$login = 0;

require_once('include/general.lib.php');

$modules = false;
$skeleton = false;
$width = true;
$leftblocks = array();
$rightblocks = array();

if (isset($_POST['X-modules']))
	$modules = split('/', $_POST['X-modules']);

if (isset($_POST['X-skeleton']))
	$skeleton = $_POST['X-skeleton'];

if (isset($_POST['X-Center']))
	$width = $_POST['X-Center'];

if (isset($_POST['X-LeftBlocks']))
	$leftblocks = preg_split('/,\s*/', $_POST['X-LeftBlocks']);

if (isset($_POST['X-RightBlocks']))
	$rightblocks = preg_split('/,\s*/', $_POST['X-RightBlocks']);

incHeader($width, $leftblocks, $rightblocks, $skeleton, $modules);

echo "<!--RubyReplaceThis-->";

incFooter();
