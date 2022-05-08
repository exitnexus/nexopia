<?

	$login=0;

	require_once("include/general.lib.php");

	if(!isset($id)){
		header("location: /");
		exit();
	}

	$fastdb->prepare_query("SELECT link FROM banners WHERE id = ?", $id);

	$link = $fastdb->fetchfield();

	$fastdb->prepare_query("UPDATE banners SET clicks = clicks+1 WHERE id = ?", $id);



	header("location: $link");
	exit(0);
