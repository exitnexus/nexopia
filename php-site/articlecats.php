<?

	$login=1;

	require_once("include/general.lib.php");

	$categories = new category( $articlesdb, "cats");
	$branch = $categories->makebranch();
	
	$template = new template('articles/articlecats');
	
	$index = -1;
	foreach($branch as $line){
		$index++;
		for($i=0;$i<$line['depth']-1;$i++)
			$indent[$index] .= "&nbsp;- ";
	}

	$template->set('branch', $branch);
	$template->set('indent', $indent);
	$template->display();
