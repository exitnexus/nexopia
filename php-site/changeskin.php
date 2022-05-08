<?

	$login=0;

	require_once("include/general.lib.php");

	$template = new template('skins/changeskin');
	$template->set('selectSkin', make_select_list_col_key($skins,'name',$skin));
	
	$template->display();
