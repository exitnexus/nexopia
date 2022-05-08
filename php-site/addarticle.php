<?
	$login = 1;

	require_once('include/general.lib.php');

	$cats = new category($articlesdb, "cats");
	$branch = $cats->makebranch();

	switch ($action) {
		case 'Preview':
		case 'Post':
			$category = getPOSTval('category');
			$title = getPOSTval('title');
			$msg = getPOSTval('msg');

			if($category && $title && $msg)
				addArticle($category, $title, $msg, $action); //exit
	}


	$template = new template('articles/addarticle/index');
	$template->set('editBox', editBoxStr(''));
	$template->set('catSel', makeCatSelect($branch));
	$template->display();


	function addArticle($category, $title, $msg, $action){
		global $branch, $cats, $msgs, $userData, $articlesdb, $mods;

		$title = trim($title);

		if(empty($title) || strlen($title) < 1) {
			$action = 'Preview';
			$msgs->addMsg('Needs a Title');
		}
		if(strlen($title) > 255) {
			$action = 'Preview';
			$msgs->addMsg('Title is too short');
		}
		if(empty($msg) || strlen($msg) < 1) {
			$action = 'Preview';
			$msgs->addMsg('No text');
		}
		if(strlen($msg) > 65535) {
			$action = 'Preview';
			$msgs->addMsg('Text is too long');
		}
		if(empty($category) || !$cats->isValidCat($category)) {
			$action = 'Preview';
			$msgs->addMsg('Bad category');
		}

		$ntitle = removeHTML($title);


		$narticle = removeHTML($msg);
		$narticle2 = parseHTML($narticle);
		$narticle3 = smilies($narticle2);
		$narticle3 = nl2br($narticle3);

		if($action == 'Preview') {
			$template = new template('articles/addarticle/preview');

			$template->setMultiple(array(
				'ntitle'	=> $ntitle,
				'narticle3'	=> $narticle3,
				'selCat'	=> makeCatSelect($branch, $category),
				'title'		=> $title,
				'editBox'	=> editBoxStr($narticle),
			));

			$template->display();
			exit;
		}

		$res = $articlesdb->prepare_query(
			"SELECT id FROM articles WHERE authorid = ? && title = ? && text = ?",
			$userData['userid'], $ntitle, $narticle
		);


		if (!$res->fetchrow()) { //dupe detection
			$articlesdb->prepare_query(
				"INSERT INTO articles SET authorid = ?, submittime = ?, category = ?, title = ?, text = ?", //, ntext = ?",
				$userData['userid'], time(), $category, $ntitle, $narticle //, $narticle3
			);

			scan_string_for_notables($narticle);

			$articleID = $articlesdb->insertid();
			$mods->newItem(MOD_ARTICLE, $articleID);
		}

		header ("Location: /articlelist.php");
		exit;
	}

