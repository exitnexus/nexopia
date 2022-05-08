<?
	$login=0;

	require_once("include/general.lib.php");
	
	$template = new template('terms/index');
	$template->set('terms', nl2br(getterms()));
	$template->display();
