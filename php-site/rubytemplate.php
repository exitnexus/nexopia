<?php

$login = 0;

require_once('include/general.lib.php');

$modules = false;
$skeleton = false;
$width = true;
$leftblocks = array();
$rightblocks = array();
$user_skin = false;

$ruby_obj = $ruby_post->fetch('X-output');

$output = $ruby_obj->generate();


if ( $ruby_obj->fetch('X-scripts') !== null )
	$scripts = $ruby_obj->fetch('X-scripts');

if ( $ruby_obj->fetch('X-skeleton') !== null )
	$skeleton = $ruby_obj->fetch('X-skeleton');

if ( $ruby_obj->fetch('X-Center') !== null )
	$width = $ruby_obj->fetch('X-Center');

if ( $ruby_obj->fetch('X-width') !== null)
	$width = $ruby_obj->fetch('X-width');

if ( $ruby_obj->fetch('X-LeftBlocks') !== null )
	$leftblocks = preg_split('/,\s*/', $ruby_obj->fetch('X-LeftBlocks'));

if ( $ruby_obj->fetch('X-RightBlocks') !== null )
	$rightblocks = preg_split('/,\s*/', $ruby_obj->fetch('X-RightBlocks'));

if( $ruby_obj->fetch('X-user-skin') != null )
	$user_skin = $ruby_obj->fetch('X-user-skin');

incHeader($width, $leftblocks, $rightblocks, $skeleton, $scripts, $user_skin);

//echo "<!--RubyReplaceThis-->";
echo "<div class=\"ruby_content\">";
echo "<div id=\"info_messages\"></div>";
echo $output;
echo "</div>";

incFooter();
